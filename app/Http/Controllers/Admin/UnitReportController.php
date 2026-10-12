<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Owner\HomeController as OwnerDashboard;
use App\Listing;
use Illuminate\Http\Request;

/**
 * Unit report: exactly what an owner sees on their dashboard (revenue,
 * occupancy, average rate, sources, 6-month trend), for any unit, picked from
 * a dropdown grouped by property. Same numbers, same code path as the owner
 * side, so what staff see is what the owner sees.
 */
class UnitReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(fn ($request, $next) => admin_can('listings.view') ? $next($request) : abort(403));
    }

    public function index(Request $request)
    {
        $allListings = Listing::where('status', 1)->orderBy('name')->get();
        $owners = \App\User::whereIn('id', $allListings->pluck('user_id')->filter()->unique())->pluck('name', 'id');
        foreach ($allListings as $l) {
            $l->owner_name = $owners[$l->user_id] ?? null;
        }
        $id = (int) $request->input('listing_id') ?: ($allListings->first()->id ?? null);
        $listing = $id ? Listing::withArchived()->find($id) : null;

        $grouped = $allListings->groupBy(fn ($l) => preg_replace('/(\s+(Extra\s+Room|\S*\d\S*))+$/u', '', $l->name) ?: $l->name)->sortKeys();

        return view('owner.home.index', OwnerDashboard::metrics($listing, $id, $request->input('date')) + [
            'allListings'     => $allListings,
            'groupedListings' => $grouped,
            'layout'          => 'admin.layout',
            'pageTitle'       => 'Unit report',
            'heading'         => 'Unit report',
            'formAction'      => '/admin/unit-report',
        ]);
    }
}
