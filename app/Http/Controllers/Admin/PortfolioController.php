<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\PortfolioAnalytics;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Portfolio: all units or one property for a month, with charts. Admin and managers (reports.units). */
class PortfolioController extends Controller
{
    public function __construct()
    {
        $this->middleware(fn ($request, $next) => admin_can('reports.units') ? $next($request) : abort(403));
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

        return view('admin.portfolio', ['d' => $data, 'properties' => $properties, 'property' => $property, 'sel' => $sel]);
    }
}
