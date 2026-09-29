<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SalesCommission;
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
        $history = SalesCommission::history($person);
        $view = ['ym' => $ym, 'person' => $person, 'own' => $own, 'data' => $data, 'history' => $history, 'hotels' => SalesCommission::HOTELS,
            'prev' => date('Y-m', strtotime($ym . '-01 -1 month')), 'next' => date('Y-m', strtotime($ym . '-01 +1 month')),
            'final' => SalesCommission::isFinal($ym), 'payout' => SalesCommission::payoutLabel($ym)];

        if ($all) {
            SalesCommission::syncPersons();
            $view['uploads']  = DB::table('sales_report_uploads')->orderByDesc('id')->limit(15)->get();
            $view['people']   = DB::table('sales_persons')->orderBy('name')->pluck('name');
            $view['persons']  = DB::table('sales_persons')->leftJoin('users', 'users.id', '=', 'sales_persons.user_id')
                ->select('sales_persons.id', 'sales_persons.name', 'sales_persons.user_id', 'users.email', 'users.admin_role')->orderBy('sales_persons.name')->get();
            $view['staff']    = DB::table('users')->join('role_user', 'role_user.user_id', '=', 'users.id')->where('role_user.role_id', 1)
                ->whereNotNull('users.email')->select('users.id', 'users.name', 'users.email')->orderBy('users.email')->get();
            $view['coverage'] = DB::table('sales_report_uploads')->select('hotel_code', DB::raw('MAX(period_to) last_day'), DB::raw('MAX(created_at) last_upload'))->groupBy('hotel_code')->get()->keyBy('hotel_code');
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
                $done[] = sprintf('%s: %s → %s, %d rows stored, %d skipped (reported months), %d earlier rows replaced.',
                    SalesCommission::HOTELS[$u->hotel_code], $u->period_from, $u->period_to, $u->rows_stored, $u->rows_skipped_locked, $u->rows_replaced);
            } catch (\Throwable $e) {
                $failed[] = $f->getClientOriginalName() . ': ' . $e->getMessage();
            }
        }

        return redirect()->route('admin.sales.index', ['month' => $request->input('month') ?: date('Y-m')])
            ->with('success', $done ? implode(' ', $done) : null)
            ->with('error', $failed ? implode(' ', $failed) : null);
    }

    /** Tie a Sales Person name to the staff login that may see it. */
    public function mapPerson(Request $request)
    {
        abort_unless(admin_can('sales.manage'), 403);
        $request->validate(['id' => 'required|integer|exists:sales_persons,id', 'user_id' => 'nullable|integer|exists:users,id']);
        $userId = $request->input('user_id') ?: null;
        if ($userId && DB::table('sales_persons')->where('user_id', $userId)->where('id', '<>', $request->input('id'))->exists()) {
            return back()->with('error', 'That login is already tied to another sales person.');
        }
        DB::table('sales_persons')->where('id', $request->input('id'))->update(['user_id' => $userId, 'updated_at' => now()]);

        return back()->with('success', 'Saved.');
    }
}
