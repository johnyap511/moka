<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\RevenueDesk;

/**
 * Revenue desk: the forward tabs beside "Units to push" (Sam, 25 Nov 2026).
 * Demand ahead and Booked ahead are for the finance and operation managers
 * (reports.push). Buildings is also open to Business Development (reports.buildings).
 * Reads only.
 */
class RevenueDeskController extends Controller
{
    private function allow(string ...$permissions): void
    {
        foreach ($permissions as $p) {
            if (admin_can($p)) {
                return;
            }
        }
        abort(403);
    }

    public function demand()
    {
        $this->allow('reports.push');

        return view('admin.revenue.demand', ['d' => RevenueDesk::demand(), 'lead' => RevenueDesk::ahead()['kpi']]);
    }

    public function ahead()
    {
        $this->allow('reports.push');

        return view('admin.revenue.ahead', ['d' => RevenueDesk::ahead(), 'canUnitReport' => admin_can('reports.units')]);
    }

    public function buildings()
    {
        $this->allow('reports.push', 'reports.buildings');

        return view('admin.revenue.buildings', ['d' => RevenueDesk::buildings()]);
    }
}
