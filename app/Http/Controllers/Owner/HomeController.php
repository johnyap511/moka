<?php

namespace App\Http\Controllers\Owner;

use App\Booking;
use App\Http\Controllers\Controller;
use App\Listing;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $authId      = Auth::user()->id;
        $allListings = Listing::withArchived()->where('user_id', $authId)->where('status', 1)->get();

        // Selected listing
        $id      = $request->listing_id ?? ($allListings->first()->id ?? null);
        $listing = $id ? Listing::find($id) : null;
        if ($listing && $listing->user_id != $authId) {
            return back()->with('error', 'Access denied.');
        }

        // Selected month (default = now)
        $selDate = $request->date
            ? Carbon::parse($request->date . '-01')
            : Carbon::now()->startOfMonth();

        $monthStart  = $selDate->copy()->startOfMonth()->toDateString();
        $monthEnd    = $selDate->copy()->endOfMonth()->toDateString();
        $yearStart   = $selDate->copy()->startOfYear()->toDateString();
        $yearEnd     = $selDate->copy()->endOfYear()->addDay()->toDateString();
        $daysInMonth = $selDate->daysInMonth;

        // Defaults
        $bookingCount      = 0;
        $monthRevenue      = 0;
        $occupancy         = 0;
        $accumulatedSales  = 0;
        $avgDailyRate      = 0;
        $avgLengthOfStay   = 0;
        $sourceBreakdown   = [];
        $categoryBreakdown = [];
        $graphArray        = [];
        $graphavg          = [];

        if ($id) {
            // Bookings overlapping selected month
            $monthBooks = Booking::where('listing_id', $id)
                ->where('status', '>=', 5)
                ->where('check_out', '>', $monthStart)
                ->where('check_in', '<=', $monthEnd)
                ->get();

            // Booked days and revenue within the month window
            $bookedDays   = 0;
            $totalRevenue = 0;
            foreach ($monthBooks as $b) {
                $cin  = max($b->check_in, $monthStart);
                $cout = min($b->check_out, $monthEnd);
                $n    = max(0, (int) Carbon::parse($cin)->diffInDays(Carbon::parse($cout)));
                $bookedDays   += $n;
                $totalRevenue += ($b->price_night ?? 0) * $n;
            }

            $bookingCount    = $monthBooks->count();
            $monthRevenue    = round($totalRevenue, 2);
            $occupancy       = $daysInMonth > 0 ? round(($bookedDays / $daysInMonth) * 100, 2) : 0;
            $avgDailyRate    = $bookedDays > 0 ? round($monthRevenue / $bookedDays, 2) : 0;
            $avgLengthOfStay = $bookingCount > 0 ? round($bookedDays / $bookingCount) : 0;

            // Accumulated sales for the year
            $yearBooks = Booking::where('listing_id', $id)
                ->where('status', '>=', 5)
                ->where('check_in', '>=', $yearStart)
                ->where('check_in', '<', $yearEnd)
                ->get();
            $accumulatedSales = $yearBooks->sum('price');

            // Source breakdown (month)
            $total       = max($bookingCount, 1);
            $knownSrc    = ['Airbnb', 'Website', 'Booking.com', 'Expedia', 'Agoda', 'Traveloka'];
            $usedSrc     = [];
            foreach ($knownSrc as $src) {
                $cnt = $monthBooks->filter(fn($b) => ($b->source ?? '') === $src)->count();
                $sourceBreakdown[$src] = ['count' => $cnt, 'pct' => round($cnt / $total * 100, 1)];
                if ($cnt) $usedSrc[] = $src;
            }
            // Group PMS / Walk-in / Owner / Others
            $otherSrc = $monthBooks->filter(fn($b) => !in_array($b->source ?? '', $knownSrc))->count();
            $sourceBreakdown['Others'] = ['count' => $otherSrc, 'pct' => round($otherSrc / $total * 100, 1)];

            // Category breakdown (month)
            $knownCats = ['Vacation', 'Co-living', 'Event', 'Tours', 'Business', 'Others'];
            foreach ($knownCats as $cat) {
                $cnt = $monthBooks->filter(fn($b) => ($b->category ?? '') === $cat)->count();
                $categoryBreakdown[$cat] = ['count' => $cnt, 'pct' => round($cnt / $total * 100, 1)];
            }
            // Uncategorized → add to Others
            $uncatCnt = $monthBooks->filter(fn($b) => empty($b->category))->count();
            if ($uncatCnt) {
                $categoryBreakdown['Others']['count'] = ($categoryBreakdown['Others']['count'] ?? 0) + $uncatCnt;
                $categoryBreakdown['Others']['pct']   = round($categoryBreakdown['Others']['count'] / $total * 100, 1);
            }

            // 6-month trend ending at selected month
            for ($i = 5; $i >= 0; $i--) {
                $m      = $selDate->copy()->subMonths($i);
                $mStart = $m->copy()->startOfMonth()->toDateString();
                $mEnd   = $m->copy()->endOfMonth()->toDateString();
                $mDays  = $m->daysInMonth;

                $mBooks = Booking::where('listing_id', $id)
                    ->where('status', '>=', 5)
                    ->where('check_out', '>', $mStart)
                    ->where('check_in', '<=', $mEnd)
                    ->get();

                $mBooked = 0; $mRev = 0;
                foreach ($mBooks as $b) {
                    $cin  = max($b->check_in, $mStart);
                    $cout = min($b->check_out, $mEnd);
                    $n    = max(0, (int) Carbon::parse($cin)->diffInDays(Carbon::parse($cout)));
                    $mBooked += $n;
                    $mRev    += ($b->price_night ?? 0) * $n;
                }

                $graphArray[] = [$m->format("M'y"), $mDays > 0 ? round(($mBooked / $mDays) * 100, 2) : 0];
                $graphavg[]   = [$m->format("M'y"), $mBooked > 0 ? round($mRev / $mBooked, 2) : 0];
            }
        }

        return view('owner.home.index', compact(
            'allListings', 'listing', 'id', 'selDate',
            'bookingCount', 'monthRevenue', 'occupancy',
            'accumulatedSales', 'avgDailyRate', 'avgLengthOfStay',
            'sourceBreakdown', 'categoryBreakdown',
            'graphArray', 'graphavg'
        ));
    }

    public function change_password()
    {
        $user_detail = User::where('id', Auth::user()->id)->first();
        return view('owner.home.profile', compact('user_detail'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'password'   => 'nullable|string|max:100',
            'c_password' => 'nullable|string|max:100',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $user = User::find($id);
        $data = [];
        if (!empty($request->password)) {
            $data['password'] = Hash::make($request->password);
        }
        if ($request->password == $request->c_password) {
            $user->update($data);
            return back()->with('success', 'Password changed successfully!');
        }
        return back()->with('error', 'Both password & confirm password must be same!');
    }
}
