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
        abort_unless(admin_can('sales.view'), 403);
        $ym = preg_match('/^\d{4}-\d{2}$/', (string) $request->input('month')) ? $request->input('month') : date('Y-m');
        $person = $request->input('person') ?: null;
        $data = SalesCommission::month($ym, $person);
        $uploads = DB::table('sales_report_uploads')->orderByDesc('id')->limit(15)->get();
        $people = DB::table('sales_transactions')->distinct()->orderBy('sales_person')->pluck('sales_person');
        $coverage = DB::table('sales_report_uploads')->select('hotel_code', DB::raw('MAX(period_to) last_day'), DB::raw('MAX(created_at) last_upload'))->groupBy('hotel_code')->get()->keyBy('hotel_code');

        return view('admin.sales.index', compact('ym', 'person', 'data', 'uploads', 'people', 'coverage') + ['hotels' => SalesCommission::HOTELS]);
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
}
