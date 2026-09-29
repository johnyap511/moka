<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SalesCommission;
use App\Support\SalesScheme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesCommissionController extends Controller
{
    public function index(Request $request)
    {
        $all = admin_can('sales.view');
        $own = $all ? null : SalesCommission::personFor(Auth::user());
        abort_unless($all || ($own && admin_can('sales.own')), 403, 'Your login is not tied to a sales person yet. Ask the office to tie it on the Sales Commission page.');

        $ym = preg_match('/^\d{4}-\d{2}$/', (string) $request->input('month')) ? $request->input('month') : date('Y-m', strtotime('first day of last month'));
        $person = $own ?: ($request->input('person') ?: null);
        $data = SalesCommission::month($ym, $person);
        $team = SalesScheme::month($ym);
        $view = ['ym' => $ym, 'person' => $person, 'own' => $own, 'data' => $data, 'team' => $team, 'hotels' => SalesCommission::HOTELS,
            'statement' => $person ? ($team['rows'][$person] ?? null) : null,
            'history' => SalesScheme::history($person),
            'prev' => date('Y-m', strtotime($ym . '-01 -1 month')), 'next' => date('Y-m', strtotime($ym . '-01 +1 month')),
            'final' => SalesCommission::isFinal($ym), 'payout' => SalesCommission::payoutLabel($ym), 'sop' => is_file(SalesScheme::sopPath()),
            'coverage' => DB::table('sales_report_uploads')->select('hotel_code', DB::raw('MAX(period_to) last_day'), DB::raw('MAX(created_at) last_upload'))->groupBy('hotel_code')->get()->keyBy('hotel_code')];
        if ($person && isset($team['rows'][$person])) {
            $view['deferred'] = SalesScheme::deferred($person, $team['rows'][$person]->id);
        }

        if ($all) {
            SalesCommission::syncPersons();
            $view['uploads']  = DB::table('sales_report_uploads')->orderByDesc('id')->limit(15)->get();
            $view['people']   = DB::table('sales_persons')->orderBy('name')->pluck('name');
            $view['persons']  = DB::table('sales_persons')->leftJoin('users', 'users.id', '=', 'sales_persons.user_id')
                ->select('sales_persons.id', 'sales_persons.name', 'sales_persons.user_id', 'sales_persons.confirmed_on', 'sales_persons.left_on', 'users.email', 'users.admin_role')->orderBy('sales_persons.name')->get();
            $view['staff']    = DB::table('users')->join('role_user', 'role_user.user_id', '=', 'users.id')->where('role_user.role_id', 1)
                ->whereNotNull('users.email')->select('users.id', 'users.name', 'users.email')->orderBy('users.email')->get();
            $view['adjustments'] = DB::table('sales_adjustments')->join('sales_persons', 'sales_persons.id', '=', 'sales_adjustments.sales_person_id')
                ->where('ym', $ym)->select('sales_adjustments.*', 'sales_persons.name')->orderByDesc('sales_adjustments.id')->get();
        }

        return view('admin.sales.index', $view);
    }

    public function upload(Request $request)
    {
        abort_unless(admin_can('sales.manage'), 403);
        $request->validate(['files' => 'required', 'files.*' => 'file|mimes:xlsx,xls|max:20480', 'hotel' => 'nullable|string|max:10']);
        $done = [];
        $failed = [];
        foreach ((array) $request->file('files') as $f) {
            try {
                $u = SalesCommission::import($f->getRealPath(), $f->getClientOriginalName(), $request->input('hotel') ?: null, Auth::id());
                $done[] = sprintf('%s: %s → %s, %d rows stored, %d already-final nights kept as paid, %d earlier rows replaced%s.',
                    SalesCommission::HOTELS[$u->hotel_code], $u->period_from, $u->period_to, $u->rows_stored, $u->rows_skipped_locked, $u->rows_replaced,
                    $u->clawbacks ? ", {$u->clawbacks} clawback(s) raised" : '');
            } catch (\Throwable $e) {
                $failed[] = $f->getClientOriginalName() . ': ' . $e->getMessage();
            }
        }

        return redirect()->route('admin.sales.index', ['month' => $request->input('month') ?: date('Y-m')])
            ->with('success', $done ? implode(' ', $done) : null)
            ->with('error', $failed ? implode(' ', $failed) : null);
    }

    /** Tie a Sales Person name to the staff login that may see it; set confirmation and leaving dates. */
    public function mapPerson(Request $request)
    {
        abort_unless(admin_can('sales.manage'), 403);
        $request->validate(['id' => 'required|integer|exists:sales_persons,id', 'user_id' => 'nullable|integer|exists:users,id', 'confirmed_on' => 'nullable|date', 'left_on' => 'nullable|date']);
        $userId = $request->input('user_id') ?: null;
        if ($userId && DB::table('sales_persons')->where('user_id', $userId)->where('id', '<>', $request->input('id'))->exists()) {
            return back()->with('error', 'That login is already tied to another sales person.');
        }
        $set = ['updated_at' => now()];
        if ($request->has('user_id')) {
            $set['user_id'] = $userId;
        }
        if ($request->has('confirmed_on')) {
            $set['confirmed_on'] = $request->input('confirmed_on') ?: null;
        }
        if ($request->has('left_on')) {
            $set['left_on'] = $request->input('left_on') ?: null;
        }
        DB::table('sales_persons')->where('id', $request->input('id'))->update($set);

        return back()->with('success', 'Saved.');
    }

    /** The month's HR inputs for everyone: absences, unapproved absence, lateness, disciplinary, employed full month. */
    public function saveKpi(Request $request)
    {
        abort_unless(admin_can('sales.manage'), 403);
        $request->validate(['ym' => 'required|regex:/^\d{4}-\d{2}$/', 'kpi' => 'required|array']);
        // KPI inputs are HR facts, so a final month may still be corrected; the page says so.
        foreach ($request->input('kpi') as $pid => $k) {
            if (!DB::table('sales_persons')->where('id', (int) $pid)->exists()) {
                continue;
            }
            DB::table('sales_kpis')->updateOrInsert(['sales_person_id' => (int) $pid, 'ym' => $request->input('ym')], [
                'employed_full_month' => !empty($k['employed_full_month']), 'absences' => max(0, min(31, (int) ($k['absences'] ?? 0))),
                'unapproved_absence' => !empty($k['unapproved_absence']), 'lateness_min' => max(0, (int) ($k['lateness_min'] ?? 0)),
                'disciplinary' => !empty($k['disciplinary']), 'note' => mb_substr(trim((string) ($k['note'] ?? '')), 0, 255) ?: null, 'updated_at' => now(), 'created_at' => now(),
            ]);
        }

        return back()->with('success', 'KPI inputs saved for ' . date('F Y', strtotime($request->input('ym') . '-01')) . '.');
    }

    /** A manual adjustment: a clawback, a management-approved booking, a correction. */
    public function addAdjustment(Request $request)
    {
        abort_unless(admin_can('sales.manage'), 403);
        $request->validate(['sales_person_id' => 'required|integer|exists:sales_persons,id', 'ym' => 'required|regex:/^\d{4}-\d{2}$/', 'amount' => 'required|numeric|not_in:0', 'reason' => 'required|string|max:255']);
        if (SalesCommission::isFinal($request->input('ym'))) {
            return back()->with('error', 'That month is final; add the adjustment to the current month instead.');
        }
        DB::table('sales_adjustments')->insert(['sales_person_id' => $request->input('sales_person_id'), 'ym' => $request->input('ym'), 'amount' => round((float) $request->input('amount'), 2),
            'kind' => 'manual', 'reason' => $request->input('reason'), 'created_by' => Auth::id(), 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Adjustment added.');
    }

    public function deleteAdjustment(Request $request, $id)
    {
        abort_unless(admin_can('sales.manage'), 403);
        $a = DB::table('sales_adjustments')->find($id);
        if ($a && !SalesCommission::isFinal($a->ym)) {
            DB::table('sales_adjustments')->where('id', $id)->delete();
        }

        return back()->with('success', 'Removed.');
    }

    /** A payout of deferred commission for a year. */
    public function addPayout(Request $request)
    {
        abort_unless(admin_can('sales.manage'), 403);
        $request->validate(['sales_person_id' => 'required|integer|exists:sales_persons,id', 'year' => 'required|integer|min:2020|max:2100', 'amount' => 'required|numeric|min:0.01', 'paid_on' => 'required|date', 'note' => 'nullable|string|max:255']);
        DB::table('sales_deferred_payouts')->insert(['sales_person_id' => $request->input('sales_person_id'), 'year' => $request->input('year'), 'amount' => round((float) $request->input('amount'), 2),
            'paid_on' => $request->input('paid_on'), 'note' => $request->input('note'), 'created_by' => Auth::id(), 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Deferred payout recorded.');
    }

    /** Undo one upload: its rows go, including rows in a final month (an explicit correction). */
    public function deleteUpload(Request $request, $id)
    {
        abort_unless(admin_can('sales.manage'), 403);
        $u = DB::table('sales_report_uploads')->find($id);
        if (!$u) {
            return back()->with('error', 'Upload not found.');
        }
        $n = DB::transaction(function () use ($u) {
            $n = DB::table('sales_transactions')->where('upload_id', $u->id)->delete();
            DB::table('sales_report_uploads')->where('id', $u->id)->delete();

            return $n;
        });

        return back()->with('success', "Upload removed: {$n} row(s) taken out. Upload the corrected file.");
    }

    /** The SOP, readable by anyone who can see the page. */
    public function sop()
    {
        abort_unless(admin_can('sales.view') || admin_can('sales.own'), 403);
        abort_unless(is_file(SalesScheme::sopPath()), 404, 'The SOP has not been uploaded yet.');

        return response()->file(SalesScheme::sopPath(), ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="MOKA-Front-Desk-Commission-SOP.pdf"']);
    }

    public function uploadSop(Request $request)
    {
        abort_unless(admin_can('sales.manage'), 403);
        $request->validate(['sop' => 'required|file|mimes:pdf|max:10240']);
        @mkdir(dirname(SalesScheme::sopPath()), 0750, true);
        $request->file('sop')->move(dirname(SalesScheme::sopPath()), basename(SalesScheme::sopPath()));

        return back()->with('success', 'SOP replaced.');
    }
}
