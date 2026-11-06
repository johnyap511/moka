<?php

namespace App\Http\Controllers\Admin;

use App\DataLog;
use App\Http\Controllers\Controller;
use App\Models\PmsDifference;
use App\Models\PmsReconcileRun;
use App\Support\PmsMonthSheet;
use App\Support\PmsReconcile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** The PMS check page: open differences by kind, resolve with a note, run the check for a month. */
class PmsDifferenceController extends Controller
{
    public function __construct()
    {
        $this->middleware(fn ($request, $next) => admin_can('ezee.view') ? $next($request) : abort(403));
    }

    public function index(Request $request)
    {
        $tab   = $request->input('tab') === 'resolved' ? 'resolved' : 'open';
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->input('month')) ? $request->input('month') : '';
        $kind  = preg_match('/^[A-H]$/', (string) $request->input('kind')) ? $request->input('kind') : '';

        $base = PmsDifference::query()->when($month, fn ($q) => $q->where('month', $month));
        $rows = (clone $base)->when($tab === 'open', fn ($q) => $q->open(), fn ($q) => $q->whereNotNull('resolved_at'))
            ->when($kind, fn ($q) => $q->where('kind', $kind))
            ->orderBy('kind')->orderBy('stay_from')->paginate(60)->withQueryString();

        $byKind = (clone $base)->open()->selectRaw('kind, count(*) n')->groupBy('kind')->pluck('n', 'kind');
        $months = PmsDifference::open()->selectRaw('month, count(*) n')->groupBy('month')->orderByDesc('month')->pluck('n', 'month');
        $runs   = PmsReconcileRun::with('user')->orderByDesc('id')->limit(8)->get();

        return view('admin.pms.index', [
            'tab' => $tab, 'month' => $month, 'kind' => $kind, 'rows' => $rows, 'byKind' => $byKind, 'months' => $months,
            'runs' => $runs, 'latest' => PmsReconcile::latestRun(), 'kinds' => PmsReconcile::KINDS,
            'openTotal' => PmsReconcile::openCount(), 'gate' => $month ? PmsReconcile::gate($month) : null,
        ]);
    }

    /** The month sheet: every booking of the month with its eZee figure, a verdict and what to do. */
    public function sheet(Request $request)
    {
        $month  = preg_match('/^\d{4}-\d{2}$/', (string) $request->input('month')) ? $request->input('month') : now()->subMonthNoOverflow()->format('Y-m');
        $show   = in_array($request->input('show'), ['all', 'attention', 'ok', 'review', 'notok', 'ezee'], true) ? $request->input('show') : 'attention';
        $q      = trim((string) $request->input('q'));
        $sheet  = PmsMonthSheet::build($month);
        $rows   = collect($sheet['rows'])->filter(fn ($r) => self::shown($r, $show))
            ->when($q !== '', fn ($c) => $c->filter(fn ($r) => stripos((string) $r['unit'], $q) !== false || (string) $r['booking_id'] === $q || stripos((string) $r['res'], $q) !== false || stripos((string) $r['folio'], $q) !== false))
            ->values();

        return view('admin.pms.sheet', [
            'month' => $month, 'show' => $show, 'q' => $q, 'rows' => $rows, 'summary' => $sheet['summary'],
            'thresholds' => $sheet['thresholds'], 'gate' => PmsReconcile::gate($month),
            'monthRun' => \App\Models\PmsReconcileRun::where('scope', 'month')->where('month', $month)->orderByDesc('id')->first(),
        ]);
    }

    /** The same sheet as an Excel file, every row, for the person ticking against eZee. */
    public function sheetExport(Request $request)
    {
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->input('month')) ? $request->input('month') : now()->subMonthNoOverflow()->format('Y-m');
        $sheet = PmsMonthSheet::build($month);
        $wb = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $wb->getActiveSheet();
        $ws->setTitle('Month sheet ' . $month);
        $head = ['Booking Id', 'RES', 'Folio', 'Unit', 'Check-in', 'Check-out', 'Nights', 'Source (Homemoka)', 'Source (eZee)', 'Homemoka total', 'eZee total', 'Difference', 'Status', 'Code', 'Reason', 'What to do'];
        $ws->fromArray($head, null, 'A1');
        $r = 2;
        foreach ($sheet['rows'] as $x) {
            $ws->fromArray([$x['booking_id'], $x['res'], $x['folio'], $x['unit'], $x['check_in'], $x['check_out'], $x['nights'], $x['source'], $x['ezee_source'],
                $x['ours'], $x['theirs'], $x['diff'], $x['status'], $x['code'], $x['reason'], $x['action']], null, 'A' . $r);
            $fill = ['OK' => 'DCFCE7', 'REVIEW' => 'FEF3C7', 'NOT OK' => 'FEE2E2', 'EZEE ONLY' => 'EDE9FE'][$x['status']] ?? null;
            if ($fill) {
                $ws->getStyle('M' . $r)->getFill()->setFillType('solid')->getStartColor()->setRGB($fill);
            }
            $r++;
        }
        $ws->getStyle('A1:P1')->getFont()->setBold(true);
        $ws->getStyle('J2:L' . $r)->getNumberFormat()->setFormatCode('#,##0.00');
        foreach (range('A', 'P') as $col) {
            $ws->getColumnDimension($col)->setAutoSize(true);
        }
        $ws->freezePane('A2');
        $ws->setAutoFilter('A1:P' . max(2, $r - 1));
        $s = $sheet['summary'];
        $ws->setCellValue('R1', 'Summary');
        $ws->fromArray([['Rows', $s['total']], ['OK', $s['OK']], ['REVIEW', $s['REVIEW']], ['NOT OK', $s['NOT OK']], ['eZee only', $s['EZEE ONLY']]], null, 'R2');

        $file = 'PMS-month-sheet-' . $month . '.xlsx';
        return response()->streamDownload(function () use ($wb) {
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($wb))->save('php://output');
        }, $file, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private static function shown(array $r, string $show): bool
    {
        return match ($show) {
            'all'    => true,
            'ok'     => $r['status'] === PmsMonthSheet::OK,
            'review' => $r['status'] === PmsMonthSheet::REVIEW,
            'notok'  => $r['status'] === PmsMonthSheet::NOT_OK,
            'ezee'   => $r['status'] === PmsMonthSheet::EZEE_ONLY,
            default  => $r['status'] !== PmsMonthSheet::OK,
        };
    }

    public function run(Request $request)
    {
        abort_unless(admin_can('ezee.manage'), 403);
        $month = (string) $request->input('month');
        $run = preg_match('/^\d{4}-\d{2}$/', $month) ? PmsReconcile::month($month, Auth::id()) : PmsReconcile::daily(60, Auth::id());
        $this->log('PMS check run', ['scope' => $run->scope, 'month' => $run->month, 'open' => $run->open, 'new' => $run->new, 'cleared' => $run->cleared]);
        return redirect()->route('admin.pms.index', array_filter(['month' => $run->month]))
            ->with('success', sprintf('Checked %d reservations and %d bookings (%s → %s): %d new, %d cleared, %d open.',
                $run->reservations, $run->bookings, $run->from->format('j M'), $run->to->format('j M'), $run->new, $run->cleared, $run->open));
    }

    public function resolve(Request $request, PmsDifference $difference)
    {
        abort_unless(admin_can('ezee.manage'), 403);
        if ($difference->resolved_at) {
            return back()->with('error', 'Already resolved.');
        }
        $request->validate(['resolution' => ['required', 'string', 'min:3', 'max:255']], ['resolution.required' => 'Say what was checked or done.', 'resolution.min' => 'Say what was checked or done.']);
        $difference->update(['resolved_at' => now(), 'resolved_by' => Auth::id(), 'resolution' => $request->input('resolution'), 'auto_resolved' => false]);
        $this->log('PMS difference resolved', ['difference' => $difference->id, 'kind' => $difference->kind, 'res' => $difference->sub_booking_id, 'booking' => $difference->booking_id, 'resolution' => $difference->resolution]);
        return back()->with('success', 'Resolved. It will not be raised again unless the data changes.');
    }

    public function reopen(PmsDifference $difference)
    {
        abort_unless(admin_can('ezee.manage'), 403);
        $difference->update(['resolved_at' => null, 'resolved_by' => null, 'resolution' => null, 'auto_resolved' => false]);
        return back()->with('success', 'Reopened.');
    }

    private function log(string $title, array $data): void
    {
        try {
            DataLog::create(['related_id' => (int) ($data['booking'] ?? 0), 'title' => $title, 'data' => json_encode($data + ['by' => Auth::user()?->name]), 'status' => 1]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
