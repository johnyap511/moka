<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReportRun;
use App\Support\OwnerReports;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

/**
 * Owner reports: the monthly landlord-payout report pack, generated from the two
 * month-end files staff already prepare (Master_Utilities and Reconciled_FINAL).
 * See App\Support\OwnerReports for the service call.
 */
class OwnerReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(fn ($request, $next) => admin_can('reports.generate') ? $next($request) : abort(403));
    }

    public function index()
    {
        $runs = ReportRun::with('user')->orderByDesc('id')->paginate(24);
        $active = ReportRun::whereIn('status', ['queued', 'running'])->count();
        return view('admin.reports.index', [
            'runs' => $runs,
            'active' => $active,
            'defaultMonth' => now()->subMonth()->format('Y-m'),
            'configured' => OwnerReports::configured(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'month'  => 'required|date_format:Y-m',
            'master' => 'required|file|mimes:xlsx|max:30720',
            'recon'  => 'nullable|file|mimes:xlsx|max:30720',
            'prev'   => 'nullable|file|mimes:xlsx|max:30720',
        ], [], ['master' => 'Master file', 'recon' => 'Reconciled file', 'prev' => 'previous month master']);

        // Month-end gate (Sam, 15 Oct 2026): no report run while eZee and Homemoka disagree on
        // that month. A super admin may override with a reason, which is logged on the run.
        $gate = \App\Support\PmsReconcile::gate($request->input('month'));
        if (!$gate['ok']) {
            $override = admin_is_super() && trim((string) $request->input('gate_override')) !== '';
            if (!$override) {
                return back()->withInput()->with('error', 'PMS check: ' . $gate['why'] . ' Open the PMS check page, run the month check and clear the differences first.');
            }
        }

        $run = ReportRun::create([
            'month' => $request->input('month'), 'status' => 'draft', 'user_id' => Auth::id(),
            'master_name' => $request->file('master')->getClientOriginalName(),
            'recon_name' => $request->file('recon') ? $request->file('recon')->getClientOriginalName() : 'Built by Homemoka',
            'prev_name' => $request->file('prev') ? $request->file('prev')->getClientOriginalName() : null,
        ]);
        if (!$gate['ok']) {
            $run->update(['log' => 'PMS gate overridden by ' . (Auth::user()->name ?? 'super admin') . ': ' . trim((string) $request->input('gate_override')) . "\n"]);
        }
        File::ensureDirectoryExists($run->dir(), 0755);
        $request->file('master')->move($run->dir(), 'master.xlsx');
        if ($request->file('recon')) {
            $request->file('recon')->move($run->dir(), 'recon.xlsx');
        } else {
            // Phase 2: the bookings sheet comes from Homemoka itself (the reconciled data lives here now).
            $n = \App\Support\OwnerReportSheet::write($run->month, $run->file('recon.xlsx'));
            $run->update(['recon_name' => 'Built by Homemoka: ' . $n . ' bookings checking in ' . $run->monthLabel()]);
        }
        if ($request->file('prev')) {
            $request->file('prev')->move($run->dir(), 'prev.xlsx');
        }

        try {
            $sheets = OwnerReports::sheetNames($run->file('master.xlsx'));
            $recon = OwnerReports::sheetNames($run->file('recon.xlsx'));
        } catch (\Throwable $e) {
            File::deleteDirectory($run->dir());
            $run->delete();
            return back()->with('error', 'Could not read the Excel files: ' . $e->getMessage());
        }
        if (!in_array('Moka', $recon, true)) {
            File::deleteDirectory($run->dir());
            $run->delete();
            return back()->with('error', 'The Reconciled file has no sheet named "Moka" (found: ' . implode(', ', $recon) . '). Upload the reconciled bookings file.');
        }

        $data = ['sheets' => $sheets, 'master_sheet' => OwnerReports::guessSheet($sheets, $run->month)];
        if ($run->prev_name) {
            $data['prev_sheets'] = OwnerReports::sheetNames($run->file('prev.xlsx'));
            $data['prev_sheet'] = OwnerReports::guessSheet($data['prev_sheets'], \Carbon\Carbon::parse($run->month . '-01')->subMonth()->format('Y-m'));
        } else {
            // No previous master uploaded: reuse the master from last month's finished run,
            // so the engine can still flag units that are new or gone.
            $last = ReportRun::where('status', 'done')->where('month', '<', $run->month)->orderByDesc('month')->orderByDesc('id')->first();
            if ($last && is_file($last->file('master.xlsx')) && copy($last->file('master.xlsx'), $run->file('prev.xlsx'))) {
                $data['prev_name'] = 'Master from the ' . $last->monthLabel() . ' run';
                $data['prev_sheets'] = $last->sheets;
                $data['prev_sheet'] = $last->master_sheet;
            }
        }
        $run->update($data);

        return redirect()->route('admin.reports.show', $run);
    }

    public function show(ReportRun $run)
    {
        $prefill = $run->overrides;
        $source = $run->overrides ? 'this run' : null;
        if (!$prefill && in_array($run->status, ['draft', 'error'])) {
            $last = ReportRun::where('status', 'done')->where('id', '<>', $run->id)->whereNotNull('overrides')->orderByDesc('month')->orderByDesc('id')->first();
            if ($last) {
                $prefill = $last->overrides; $source = 'the ' . $last->monthLabel() . ' run';
            } else {
                $prefill = OwnerReports::defaults(); $source = $prefill ? 'the engine defaults' : null;
            }
        }
        return view('admin.reports.show', [
            'run' => $run,
            'form' => OwnerReports::formFromOverrides($prefill ?: []),
            'formSource' => $source,
        ]);
    }

    public function generate(Request $request, ReportRun $run)
    {
        abort_unless(in_array($run->status, ['draft', 'error']), 409, 'This run is not editable.');
        $request->validate([
            'master_sheet' => 'required|string',
            'prev_sheet' => 'nullable|string',
            'overrides' => 'nullable|string|max:20000',
        ]);
        if (!in_array($request->input('master_sheet'), $run->sheets ?? [], true)) {
            return back()->with('error', 'Pick the data tab from the list.');
        }
        try {
            $overrides = $request->filled('overrides')
                ? OwnerReports::parseOverrides($request->input('overrides'))
                : OwnerReports::overridesFromForm($request->only(OwnerReports::TABLES));
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
        $run->update([
            'status' => 'queued',
            'master_sheet' => $request->input('master_sheet'),
            'prev_sheet' => $run->prev_name ? ($request->input('prev_sheet') ?: $run->prev_sheet) : null,
            'overrides' => $overrides,
            'error' => null, 'log' => null,
        ]);
        return redirect()->route('admin.reports.show', $run)->with('success', 'Queued. The pack takes a few minutes; this page refreshes itself.');
    }

    public function status(ReportRun $run)
    {
        return response()->json(['status' => $run->status]);
    }

    public function download(ReportRun $run)
    {
        abort_unless($run->status === 'done' && is_file($run->file('report.zip')), 404);
        return response()->download($run->file('report.zip'), $run->zip_name ?: 'report.zip');
    }

    /** Only unfinished runs can go: a finished pack is the record of what owners received. */
    public function destroy(ReportRun $run)
    {
        abort_unless(in_array($run->status, ['draft', 'error']), 409, 'Finished runs are kept.');
        File::deleteDirectory($run->dir());
        $run->delete();
        return redirect()->route('admin.reports.index')->with('success', 'Removed.');
    }
}
