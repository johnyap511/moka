<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $listings = DB::table('listings')->where('status', 'active')->get();
        return view('v2.pages.dashboard', compact('listings'));
    }

    public function bookings()
    {
        return view('v2.pages.dashboard-bookings');
    }

    public function revenue()
    {
        return view('v2.pages.dashboard-revenue');
    }

    public function calendar()
    {
        return view('v2.pages.dashboard-calendar');
    }
}
