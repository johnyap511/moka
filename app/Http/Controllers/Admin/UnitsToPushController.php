<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\RevenueDesk;

/**
 * Units to push: which units are behind and have empty nights to sell.
 * For the finance and operation managers (reports.push). Reads only.
 */
class UnitsToPushController extends Controller
{
    public function __construct()
    {
        $this->middleware(fn ($request, $next) => admin_can('reports.push') ? $next($request) : abort(403));
    }

    public function index()
    {
        $c = RevenueDesk::cached('push');

        return view('admin.units_to_push', ['d' => $c['data'], 'at' => $c['at'], 'canUnitReport' => admin_can('reports.units')]);
    }
}
