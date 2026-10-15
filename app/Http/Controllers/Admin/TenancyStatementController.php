<?php

namespace App\Http\Controllers\Admin;

use App\DataLog;
use App\Http\Controllers\Controller;
use App\Models\TenancyStatement;
use App\Support\TenancyBill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Tenancy settlement statements for Finance (Sam, 15 Oct 2026).
 *
 * The calculator page previews live in the browser; "Finalise" posts the
 * inputs here, the server recomputes every line (source of truth), numbers
 * the statement and stores inputs + lines. The browser then draws the PDF from
 * the server's figures, uploads it once for safekeeping, and downloads it.
 */
class TenancyStatementController extends Controller
{
    public function __construct()
    {
        $this->middleware(fn ($request, $next) => admin_can('tenancy.view') ? $next($request) : abort(403));
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $rows = TenancyStatement::query()
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('no', 'like', "%{$q}%")->orWhere('tenant_name', 'like', "%{$q}%")
                      ->orWhere('booking_ref', 'like', "%{$q}%")->orWhere('unit_names', 'like', "%{$q}%")
                      ->orWhere('property', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')->paginate(30)->withQueryString();

        $stats = [
            'month'  => TenancyStatement::whereNull('voided_at')->where('created_at', '>=', now()->startOfMonth())->count(),
            'refund' => (float) TenancyStatement::whereNull('voided_at')->where('created_at', '>=', now()->startOfMonth())->where('balance', '>', 0)->sum('balance'),
            'owed'   => (float) abs(TenancyStatement::whereNull('voided_at')->where('created_at', '>=', now()->startOfMonth())->where('balance', '<', 0)->sum('balance')),
            'nopdf'  => TenancyStatement::whereNull('voided_at')->whereNull('pdf_path')->count(),
        ];

        return view('admin.tenancy.index', ['rows' => $rows, 'q' => $q, 'stats' => $stats]);
    }

    public function create(Request $request)
    {
        abort_unless(admin_can('tenancy.manage'), 403);
        $seed = null;
        if ($request->filled('from')) {
            $src = TenancyStatement::find((int) $request->input('from'));
            $seed = $src?->input;
        }
        $user = Auth::user();
        return view('admin.tenancy.create', [
            'seed'       => $seed,
            'preparedBy' => trim(($user->name ?? '') . ' ' . ($user->last_name ?? '')),
            'company'    => config('moka.company'),
        ]);
    }

    /** Validate, recompute, number and save. Returns the figures the PDF is drawn from. */
    public function store(Request $request)
    {
        abort_unless(admin_can('tenancy.manage'), 403);
        $in = $request->input('input');
        if (!is_array($in)) {
            return response()->json(['ok' => false, 'message' => 'Nothing received.'], 422);
        }
        $in = self::clean($in);
        $computed = TenancyBill::compute($in);
        if ($why = TenancyBill::blocker($in, $computed)) {
            return response()->json(['ok' => false, 'message' => $why], 422);
        }
        $d = $computed['data'];

        $row = DB::transaction(function () use ($in, $d) {
            $year = now()->format('Y');
            $seq  = (int) TenancyStatement::where('no', 'like', "MKS-{$year}-%")->lockForUpdate()->count() + 1;
            $no   = sprintf('MKS-%s-%04d', $year, $seq);
            return TenancyStatement::create([
                'no'          => $no,
                'tenant_name' => mb_substr($d['tenant'], 0, 120),
                'booking_ref' => mb_substr($d['ref'], 0, 80) ?: null,
                'property'    => mb_substr($d['property'], 0, 160) ?: null,
                'unit_names'  => mb_substr(implode(', ', $d['unitNames']), 0, 255) ?: null,
                'prepared_by' => mb_substr($d['preparedBy'], 0, 80) ?: null,
                'stay_from'   => $d['stayFrom'],
                'stay_to'     => $d['stayTo'],
                'stay_days'   => $d['stayDays'],
                'inclusive'   => $d['inclusive'],
                'bill_total'  => $d['billTotal'],
                'addon_total' => $d['addTotal'],
                'arrears'     => $d['arrears'],
                'total'       => $d['total'],
                'deposit'     => $d['deposit'],
                'balance'     => $d['balance'],
                'remarks'     => $d['remarks'] ?: null,
                'input'       => $in,
                'lines'       => $d,
                'created_by'  => Auth::id(),
            ]);
        });

        $this->log($row, 'Tenancy statement issued', ['total' => $d['total'], 'balance' => $d['balance']]);

        return response()->json([
            'ok'   => true,
            'id'   => $row->id,
            'no'   => $row->no,
            'issued' => $row->created_at->format('j M Y \a\t H:i'),
            'data' => $d,
            'url'  => route('admin.tenancy.show', $row),
        ]);
    }

    /** The PDF as issued, stored once; a second upload for the same statement is refused. */
    public function attachPdf(Request $request, TenancyStatement $statement)
    {
        abort_unless(admin_can('tenancy.manage'), 403);
        if ($statement->pdf_path) {
            return response()->json(['ok' => false, 'message' => 'This statement already has its PDF on file.'], 409);
        }
        $request->validate(['pdf' => ['required', 'file', 'max:5120', 'mimetypes:application/pdf']]);
        $file = $request->file('pdf');
        $raw  = file_get_contents($file->getRealPath());
        if (substr($raw, 0, 5) !== '%PDF-') {
            return response()->json(['ok' => false, 'message' => 'That file is not a PDF.'], 422);
        }
        $path = 'tenancy-statements/' . $statement->created_at->format('Y') . '/' . $statement->no . '.pdf';
        Storage::disk('local')->put($path, $raw);
        $statement->update(['pdf_path' => $path, 'pdf_size' => strlen($raw), 'pdf_sha1' => sha1($raw)]);
        return response()->json(['ok' => true]);
    }

    public function show(TenancyStatement $statement)
    {
        return view('admin.tenancy.show', ['s' => $statement, 'd' => $statement->lines, 'company' => config('moka.company')]);
    }

    public function download(TenancyStatement $statement)
    {
        if (!$statement->pdf_path || !Storage::disk('local')->exists($statement->pdf_path)) {
            return redirect()->route('admin.tenancy.show', $statement)->with('error', 'No PDF is on file for this statement. Issue a new one from "Copy as new statement".');
        }
        $name = $statement->no . ' - ' . preg_replace('/[\\\\\/:*?"<>|]+/', ' ', $statement->tenant_name) . '.pdf';
        return response()->file(Storage::disk('local')->path($statement->pdf_path), [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
        ]);
    }

    public function void(Request $request, TenancyStatement $statement)
    {
        abort_unless(admin_can('tenancy.manage'), 403);
        if ($statement->isVoid()) {
            return back()->with('error', 'Already void.');
        }
        $request->validate(['reason' => ['required', 'string', 'min:3', 'max:255']], ['reason.required' => 'Give the reason for voiding.', 'reason.min' => 'Give the reason for voiding.']);
        $statement->update(['voided_at' => now(), 'voided_by' => Auth::id(), 'void_reason' => $request->input('reason')]);
        $this->log($statement, 'Tenancy statement voided', ['reason' => $request->input('reason')]);
        return back()->with('success', $statement->no . ' is now void. It stays on file for the record.');
    }

    /** Keep only known keys and sane sizes; the calculation ignores anything else. */
    private static function clean(array $in): array
    {
        $str = fn ($v, int $max) => mb_substr(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', trim((string) (is_scalar($v) ? $v : ''))), 0, $max);
        $out = [
            'tenant'     => $str($in['tenant'] ?? '', 120),
            'bookref'    => $str($in['bookref'] ?? '', 80),
            'preparedBy' => $str($in['preparedBy'] ?? '', 80),
            'property'   => $str($in['property'] ?? '', 160),
            'remarks'    => $str($in['remarks'] ?? '', 1000),
            'deposit'    => $str($in['deposit'] ?? '', 12),
            'arrears'    => $str($in['arrears'] ?? '', 12),
            'inclusive'  => !empty($in['inclusive']),
            'units'      => [],
            'addons'     => [],
        ];
        foreach (array_slice(array_values((array) ($in['units'] ?? [])), 0, 16) as $u) {
            $bills = [];
            foreach (array_slice(array_values((array) ($u['bills'] ?? [])), 0, 40) as $b) {
                $bills[] = ['label' => $str($b['label'] ?? '', 60), 'start' => $str($b['start'] ?? '', 10), 'end' => $str($b['end'] ?? '', 10), 'amount' => $str($b['amount'] ?? '', 12)];
            }
            $out['units'][] = ['name' => $str($u['name'] ?? '', 60), 'from' => $str($u['from'] ?? '', 10), 'to' => $str($u['to'] ?? '', 10), 'bills' => $bills];
        }
        foreach (array_slice(array_values((array) ($in['addons'] ?? [])), 0, 40) as $a) {
            $out['addons'][] = ['label' => $str($a['label'] ?? '', 60), 'qtyLab' => $str($a['qtyLab'] ?? 'Qty', 20), 'rateLab' => $str($a['rateLab'] ?? 'Rate', 20),
                'qty' => $str($a['qty'] ?? '', 8), 'rate' => $str($a['rate'] ?? '', 12), 'auto' => ($a['auto'] ?? '') === 'months' ? 'months' : '', 'touched' => !empty($a['touched'])];
        }
        return $out;
    }

    private function log(TenancyStatement $s, string $title, array $data): void
    {
        try {
            DataLog::create([
                'related_id' => $s->id,
                'title'      => $title,
                'data'       => json_encode($data + ['no' => $s->no, 'tenant' => $s->tenant_name, 'by' => Auth::user()?->name]),
                'status'     => 1,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
