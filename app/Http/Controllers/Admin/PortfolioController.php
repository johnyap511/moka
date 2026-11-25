<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\PortfolioAnalytics;
use App\Support\RevenueDesk;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Portfolio: all units or one property for a month, with charts. Admin and managers (reports.units). */
class PortfolioController extends Controller
{
    public function __construct()
    {
        // Super admin only for now (Sam, 12 Oct 2026); widen to reports.units when he says so.
        $this->middleware(fn ($request, $next) => admin_is_super() ? $next($request) : abort(403));
    }

    public function index(Request $request)
    {
        $properties = PortfolioAnalytics::properties();
        $property   = (string) $request->input('property', '');
        if ($property !== '' && !$properties->has($property)) {
            $property = '';
        }
        $sel = preg_match('/^\d{4}-\d{2}$/', (string) $request->input('date')) ? Carbon::createFromFormat('Y-m', $request->input('date'))->startOfMonth() : now()->startOfMonth();

        $listings = $property === '' ? $properties->flatten(1) : $properties->get($property);
        $data = PortfolioAnalytics::build($listings, $sel, $property === '' ? 'Whole portfolio' : $property);

        // "What to decide" looks ahead, so it only makes sense for the month we are in.
        $outlook = null; $desk = null;
        if ($sel->isSameMonth(now())) {
            try {
                $outlook = RevenueDesk::cached('outlook', $property ?: null)['data'];
                $desk = ['demand' => RevenueDesk::cached('demand')['data']['kpi'], 'lead' => RevenueDesk::cached('ahead')['data']['kpi']];
            } catch (\Throwable $e) {
                report($e);   // the rest of the page must still open
            }
        }

        return view('admin.portfolio', ['d' => $data, 'properties' => $properties, 'property' => $property, 'sel' => $sel, 'outlook' => $outlook, 'desk' => $desk]);
    }
}
