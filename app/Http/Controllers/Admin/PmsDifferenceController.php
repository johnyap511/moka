<?php

namespace App\Http\Controllers\Admin;

use App\DataLog;
use App\Http\Controllers\Controller;
use App\Models\PmsDifference;
use App\Models\PmsReconcileRun;
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
