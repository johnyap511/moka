<?php

namespace App\Support;

use App\Models\ReportRun;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Owner reports (6 Oct 2026). The monthly landlord-payout reports are produced by the
 * locked Python engine (repo moka-report-service) running as a private Cloud Run
 * service. Staff upload the two month-end files here; the scheduler hands a queued
 * run to the service, which returns totals and flags and leaves the zip in a bucket;
 * we copy the zip into storage/app/reports/<id>/ so Homemoka keeps the archive.
 */
class OwnerReports
{
    public const VERSION = 'V21';
    public const TABLES = ['mg_units', 'owner_stay', 'office_rent', 'alinea_rent_ovr', 'str_retag', 'lt_override'];

    public static function url(): string
    {
        return rtrim((string) config('moka.reports_url'), '/');
    }

    public static function configured(): bool
    {
        return self::url() !== '';
    }

    /**
     * Identity token for the private service. On the VM it comes from the GCE
     * metadata server for the VM's own service account (which holds run.invoker);
     * elsewhere MOKA_REPORTS_TOKEN may carry one for a manual test.
     */
    public static function token(): ?string
    {
        if ($t = config('moka.reports_token')) {
            return $t;
        }
        try {
            $r = Http::withHeaders(['Metadata-Flavor' => 'Google'])->timeout(5)
                ->get('http://metadata.google.internal/computeMetadata/v1/instance/service-accounts/default/identity', [
                    'audience' => self::url(),
                ]);
            return $r->ok() ? trim($r->body()) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected static function client(int $timeout)
    {
        $req = Http::timeout($timeout)->acceptJson();
        $t = self::token();
        return $t ? $req->withToken($t) : $req;
    }

    /** The engine's built-in judgment tables, to pre-fill the Advanced box. Empty when unreachable. */
    public static function defaults(): array
    {
        if (!self::configured()) {
            return [];
        }
        try {
            return Cache::remember('owner-reports.defaults', 3600, function () {
                $r = self::client(20)->get(self::url() . '/defaults');
                return $r->ok() ? (array) $r->json() : [];
            }) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function sheetNames(string $path): array
    {
        return IOFactory::createReader('Xlsx')->listWorksheetNames($path);
    }

    /** Pick the data tab: the only one, else the one naming the report month, else nothing (staff choose). */
    public static function guessSheet(array $sheets, string $month): ?string
    {
        if (count($sheets) === 1) {
            return $sheets[0];
        }
        $m = Carbon::parse($month . '-01');
        $names = [strtolower($m->format('F')), strtolower($m->format('M'))]; // "august", "aug"
        foreach ($sheets as $s) {
            $l = strtolower($s);
            foreach ($names as $n) {
                if (preg_match('/\\b' . $n . '\\b/', $l) && str_contains($l, $m->format('Y'))) {
                    return $s;
                }
            }
        }
        return null;
    }

    /** Validate the Advanced JSON; returns the decoded tables or throws with a plain message. */
    public static function parseOverrides(?string $json): ?array
    {
        $json = trim((string) $json);
        if ($json === '') {
            return null;
        }
        $d = json_decode($json, true);
        if (!is_array($d)) {
            throw new \InvalidArgumentException('The Advanced settings are not valid JSON.');
        }
        $unknown = array_diff(array_keys($d), self::TABLES);
        if ($unknown) {
            throw new \InvalidArgumentException('Unknown table(s) in Advanced settings: ' . implode(', ', $unknown) . '. Allowed: ' . implode(', ', self::TABLES) . '.');
        }
        foreach (['owner_stay', 'str_retag'] as $k) {
            if (isset($d[$k]) && !array_is_list($d[$k])) {
                throw new \InvalidArgumentException("$k must be a list of unit names.");
            }
        }
        foreach (['mg_units', 'office_rent', 'alinea_rent_ovr', 'lt_override'] as $k) {
            if (isset($d[$k]) && array_is_list($d[$k]) && $d[$k] !== []) {
                throw new \InvalidArgumentException("$k must map unit name to amount.");
            }
        }
        return $d;
    }

    public static function config(ReportRun $run): array
    {
        $m = Carbon::parse($run->month . '-01');
        $c = [
            'month_label'  => $m->format('M-y'),
            'month_full'   => strtoupper($m->format('F Y')),
            'month_num'    => $m->format('mY'),
            'period_start' => $m->format('d/m/Y'),
            'period_end'   => $m->copy()->endOfMonth()->format('d/m/Y'),
            'master_sheet' => $run->master_sheet,
            'version'      => self::VERSION,
        ];
        if ($run->prev_name && $run->prev_sheet) {
            $c['prev_sheet'] = $run->prev_sheet;
        }
        if ($run->overrides) {
            $c['overrides'] = $run->overrides;
        }
        return $c;
    }

    /** Run one queued report end to end. Called by reports:run from the scheduler. */
    public static function run(ReportRun $run): void
    {
        $run->update(['status' => 'running', 'started_at' => now(), 'error' => null]);
        try {
            if (!self::configured()) {
                throw new \RuntimeException('MOKA_REPORTS_URL is not set.');
            }
            $req = self::client(900)
                ->attach('master', fopen($run->file('master.xlsx'), 'r'), 'Master_Utilities.xlsx')
                ->attach('recon', fopen($run->file('recon.xlsx'), 'r'), 'Reconciled_FINAL.xlsx');
            if ($run->prev_name && $run->prev_sheet && is_file($run->file('prev.xlsx'))) {
                $req = $req->attach('prev_master', fopen($run->file('prev.xlsx'), 'r'), 'Master_Prev_Month.xlsx');
            }
            $res = $req->post(self::url() . '/generate', ['config' => json_encode(self::config($run))]);
            $j = (array) $res->json();
            if (!$res->ok() || empty($j['ok'])) {
                $run->update([
                    'status' => 'error', 'finished_at' => now(),
                    'error' => $j['error'] ?? ('The report service answered HTTP ' . $res->status() . '.'),
                    'log' => $j['log'] ?? mb_substr($res->body(), 0, 20000),
                ]);
                return;
            }
            $zip = $run->file('report.zip');
            $dl = self::client(600)->sink($zip)->get(self::url() . '/download/' . $j['object']);
            if (!$dl->ok() || !is_file($zip) || filesize($zip) < 1000) {
                throw new \RuntimeException('Report built but the zip could not be downloaded (HTTP ' . $dl->status() . ').');
            }
            @chmod($zip, 0644);
            $run->update([
                'status' => 'done', 'finished_at' => now(),
                'zip_name' => $j['zip_name'] ?? ('Reports_' . $j['month'] . '_' . self::VERSION . '.zip'),
                'object' => $j['object'], 'size' => filesize($zip),
                'total_payable' => $j['total_payable'] ?? null, 'total_moka_np' => $j['total_moka_np'] ?? null,
                'flags' => $j['flags'] ?? [], 'log' => $j['log'] ?? null,
            ]);
        } catch (\Throwable $e) {
            $run->update(['status' => 'error', 'finished_at' => now(), 'error' => $e->getMessage()]);
        }
    }
}
