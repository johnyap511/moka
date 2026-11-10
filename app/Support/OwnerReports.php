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

    public const MAPS  = ['mg_units', 'office_rent', 'alinea_rent_ovr', 'lt_override'];
    public const LISTS = ['owner_stay', 'str_retag'];

    /** Form fields → override tables. Maps are "Unit name: amount" per line; lists are one unit per line. */
    public static function overridesFromForm(array $in): ?array
    {
        $out = [];
        foreach (self::MAPS as $k) {
            $map = [];
            foreach (preg_split('/\r?\n/', (string) ($in[$k] ?? '')) as $line) {
                $line = trim($line);
                if ($line === '') continue;
                if (!preg_match('/^(.+?)\s*[:=]\s*([\d,]+(?:\.\d+)?)\s*$/', $line, $m)) {
                    throw new \InvalidArgumentException("Can't read \"$line\" under " . self::label($k) . ': write one unit per line as "Unit name: amount".');
                }
                $map[trim($m[1])] = (float) str_replace(',', '', $m[2]);
            }
            $out[$k] = $map;
        }
        foreach (self::LISTS as $k) {
            $out[$k] = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) ($in[$k] ?? ''))), fn ($v) => $v !== ''));
        }
        return $out;
    }

    /** Override tables → form field text. */
    public static function formFromOverrides(array $ov): array
    {
        $f = [];
        foreach (self::MAPS as $k) {
            $f[$k] = implode("\n", array_map(fn ($u, $a) => $u . ': ' . rtrim(rtrim(number_format((float) $a, 2, '.', ''), '0'), '.'), array_keys((array) ($ov[$k] ?? [])), array_values((array) ($ov[$k] ?? []))));
        }
        foreach (self::LISTS as $k) {
            $f[$k] = implode("\n", (array) ($ov[$k] ?? []));
        }
        return $f;
    }

    public static function label(string $k): string
    {
        return ['mg_units' => 'Minimum guarantee', 'owner_stay' => 'Owner stayed', 'office_rent' => 'Office rent', 'alinea_rent_ovr' => 'Alinea fixed rent', 'str_retag' => 'Treat as short stay', 'lt_override' => 'Force long term'][$k] ?? $k;
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
            // Empty maps must reach the engine as {} not []: PHP arrays lose the distinction.
            $ov = $run->overrides;
            foreach (['mg_units', 'office_rent', 'alinea_rent_ovr', 'lt_override'] as $k) {
                if (array_key_exists($k, $ov) && is_array($ov[$k]) && !$ov[$k]) {
                    $ov[$k] = new \stdClass();
                }
            }
            $c['overrides'] = $ov;
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
                'flags' => $j['flags'] ?? [],
                // Keep the gate override note (if any) above the engine's log.
                'log' => trim((string) ($run->log ?? '')) !== '' ? rtrim((string) $run->log) . "\n\n" . ($j['log'] ?? '') : ($j['log'] ?? null),
            ]);
            try {
                self::captureResults($run->fresh());
            } catch (\Throwable $e) {
                report($e);   // the pack is done; a capture problem must not mark the run as failed
            }
        } catch (\Throwable $e) {
            $run->update(['status' => 'error', 'finished_at' => now(), 'error' => $e->getMessage()]);
        }
    }

    /** One spelling for a unit name, whoever wrote it: the engine, the master or Homemoka. */
    public static function unitKey(string $s): string
    {
        $s = strtolower(trim(preg_replace('/\s*\(.*?\)\s*/', ' ', $s)));
        $s = str_replace(['bell suites', 'bell suite'], 'bs', $s);
        $s = preg_replace('/^mesahill\s+/', '', $s);
        return preg_replace('/\s+/', '', $s);
    }

    /**
     * Before a pack is generated: which Homemoka units have bookings this month but no row in
     * the uploaded master (the engine would silently give them no report), and which master
     * rows match no Homemoka unit. Extra rooms are company revenue and are left out.
     * Found necessary in the 10 Nov 2026 audit (Ekocheras H-23-12, RM 3,053 of October bookings,
     * was not in the master). Returns null when the master tab is not known yet.
     *
     * @return array{missing: array<int, array{unit: string, bookings: int, total: float}>, unknown: array<int, string>}|null
     */
    public static function coverage(ReportRun $run): ?array
    {
        if (!$run->master_sheet || !is_file($run->file('master.xlsx'))) {
            return null;
        }
        $reader = IOFactory::createReader('Xlsx')->setReadDataOnly(true)->setLoadSheetsOnly([$run->master_sheet]);
        $rows = $reader->load($run->file('master.xlsx'))->getSheet(0)->toArray(null, true, false, false);
        $master = [];
        foreach (array_slice($rows, 2) as $r) {
            $name = trim((string) ($r[1] ?? ''));
            if ($name !== '') {
                $master[self::unitKey($name)] = $name;
            }
        }
        $inMaster = function (string $k) use ($master): bool {
            if (isset($master[$k])) {
                return true;
            }
            foreach ($master as $mk => $_) {
                if (strlen($mk) >= 6 && (str_ends_with($k, $mk) || str_ends_with($mk, $k))) {
                    return true;
                }
            }
            return false;
        };
        $units = [];
        foreach (OwnerReportSheet::rows($run->month) as $row) {
            $name = (string) $row[5];
            if ($name === '' || stripos($name, 'Extra Room') !== false) {
                continue;
            }
            $units[$name]['bookings'] = ($units[$name]['bookings'] ?? 0) + 1;
            $units[$name]['total'] = ($units[$name]['total'] ?? 0) + (float) $row[17];
        }
        $missing = [];
        foreach ($units as $name => $u) {
            if (!$inMaster(self::unitKey($name))) {
                $missing[] = ['unit' => $name, 'bookings' => $u['bookings'], 'total' => round($u['total'], 2)];
            }
        }
        usort($missing, fn ($a, $b) => $b['total'] <=> $a['total']);
        $listingKeys = [];
        foreach (\App\Listing::withoutGlobalScopes()->pluck('name') as $n) {
            $listingKeys[self::unitKey((string) $n)] = true;
        }
        $unknown = [];
        foreach ($master as $mk => $name) {
            $hit = isset($listingKeys[$mk]);
            if (!$hit) {
                foreach ($listingKeys as $lk => $_) {
                    if (strlen($mk) >= 6 && str_ends_with($lk, $mk)) {
                        $hit = true;
                        break;
                    }
                }
            }
            if (!$hit) {
                $unknown[] = $name;
            }
        }
        return ['missing' => $missing, 'unknown' => $unknown];
    }

    /**
     * Read the finished pack and keep one row per unit in report_unit_results: payable to the
     * landlord, Moka net profit, category, the owner at the time, and the unit's report file
     * inside the zip (Sam, 6 Nov 2026: the basis for owner statements, yearly statements and
     * profit analysis). Re-runnable: rows for the run are replaced. Returns the row count.
     */
    public static function captureResults(ReportRun $run): int
    {
        $zipPath = $run->file('report.zip');
        if ($run->status !== 'done' || !is_file($zipPath)) {
            return 0;
        }
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Cannot open the report zip of run #' . $run->id);
        }
        $names = [];
        $compIndex = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $n = $zip->getNameIndex($i);
            $names[] = $n;
            // The folder is "Group & Compilation", so match the file name, not the path.
            if ($compIndex === null && str_contains($n, '/Excel/') && str_starts_with(basename($n), 'Compilation') && str_ends_with($n, '.xlsx')) {
                $compIndex = $i;
            }
        }
        if ($compIndex === null) {
            $zip->close();
            throw new \RuntimeException('No compilation workbook in the zip of run #' . $run->id);
        }
        $tmp = tempnam(sys_get_temp_dir(), 'comp');
        file_put_contents($tmp, $zip->getFromIndex($compIndex));
        $zip->close();
        $ws = IOFactory::createReader('Xlsx')->setReadDataOnly(true)->load($tmp)->getSheet(0);
        $rows = $ws->toArray(null, true, false, false);
        @unlink($tmp);

        // Unit names as the engine prints them vs Homemoka listing names: case, spaces, a
        // "Mesahill " prefix, "Bell Suite" vs "Bell Suites", and bracketed suffixes.
        $key = fn (string $s): string => self::unitKey($s);
        $listings = [];
        foreach (\App\Listing::withoutGlobalScopes()->get(['id', 'name', 'user_id']) as $l) {
            $listings[$key((string) $l->name)] = $l;
        }
        $groupFile = ['Group BS' => 'Group BS_', 'Group Alinea' => 'Group Alinea_', 'Group Mesahill' => 'Group Mesahill_'];
        $find = function (string $needle, string $ext) use ($names): ?string {
            foreach ($names as $n) {
                if (str_ends_with($n, '.' . $ext) && str_contains($n, $needle)) {
                    return $n;
                }
            }
            return null;
        };

        $header = null;
        $out = [];
        foreach ($rows as $r) {
            if ($header === null) {
                if (isset($r[1]) && trim((string) $r[1]) === 'Unit') {
                    $header = true;
                }
                continue;
            }
            $unit = trim((string) ($r[1] ?? ''));
            $cat  = trim((string) ($r[2] ?? ''));
            if ($unit === '' || $cat === '' || !is_numeric($r[3] ?? null) || stripos($unit, 'total') !== false) {
                continue;
            }
            $uk = $key($unit);
            $l  = $listings[$uk] ?? null;
            if (!$l) {
                // The master names Forum and office units without the property ("RS-35-10",
                // "J-06-01"): take the one listing whose name ends with the unit code.
                $hits = array_values(array_filter(array_keys($listings), fn ($k) => str_ends_with($k, $uk) && strlen($k) > strlen($uk)));
                $l = count($hits) === 1 ? $listings[$hits[0]] : null;
            }
            $needle = str_starts_with($cat, 'Group ') ? ($groupFile[preg_replace('/\s*\(.*\)$/', '', $cat)] ?? null) : '/R_' . $unit . '_';
            $out[] = [
                'run_id' => $run->id, 'month' => $run->month, 'unit' => mb_substr($unit, 0, 120),
                'listing_id' => $l?->id, 'owner_id' => $l?->user_id ?: null, 'category' => mb_substr($cat, 0, 40),
                'payable' => round((float) $r[3], 2), 'moka_np' => is_numeric($r[4] ?? null) ? round((float) $r[4], 2) : null,
                'pdf_path' => $needle ? $find($needle, 'pdf') : null, 'xlsx_path' => $needle ? $find($needle, 'xlsx') : null,
                'captured_at' => now(),
            ];
        }
        \App\Models\ReportUnitResult::where('run_id', $run->id)->delete();
        foreach (array_chunk($out, 200) as $chunk) {
            \App\Models\ReportUnitResult::insert($chunk);
        }
        return count($out);
    }
}
