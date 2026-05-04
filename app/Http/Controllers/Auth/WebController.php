<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\EstimateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WebController extends Controller
{
    public function newHomeNew()
    {
        $stats = Cache::remember('home_stats', 3600, function () {
            return [
                'trips'       => '20,000+',
                'occupancy'   => '70%',
                'revenue'     => 'RM10M+',
                'rating'      => '4.9/5',
                'award'       => 'Superhost',
            ];
        });

        return view('v2.pages.home', compact('stats'));
    }

    public function HomeAbout()
    {
        return view('v2.pages.about');
    }

    public function HomeService()
    {
        return view('v2.pages.service');
    }

    public function getEstimate()
    {
        return view('v2.pages.estimate');
    }

    public function submitEstimate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'phone'   => 'required|string|max:30',
            'address' => 'required|string|max:500',
            'bedroom' => 'required|integer|min:1|max:10',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            EstimateRequest::create([
                'name'    => $request->name,
                'email'   => $request->email,
                'phone'   => $request->phone,
                'address' => $request->address,
                'bedroom' => $request->bedroom,
            ]);
        } catch (\Exception $e) {
            Log::error('Estimate submit failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Thank you! We will contact you within 24 hours with your estimate.');
    }

    public function locationSearch(Request $request)
    {
        $zone     = $request->get('zone', '');
        $checkIn  = $request->get('check_in', '');
        $checkOut = $request->get('check_out', '');
        $guestNo  = $request->get('guest_no', 1);
        $children = $request->get('children', 0);
        $sort     = $request->get('sort', 'recommended');
        $perPage  = 12;

        $query = Listing::active()->with('images');

        if ($zone) {
            $query->where(function ($q) use ($zone) {
                $q->where('zone', 'like', "%{$zone}%")
                  ->orWhere('title', 'like', "%{$zone}%")
                  ->orWhere('address', 'like', "%{$zone}%");
            });
        }

        if ($guestNo) {
            $query->where('max_guests', '>=', (int) $guestNo);
        }

        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price_per_night', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price_per_night', 'desc');
                break;
            case 'rating':
                $query->orderBy('rating', 'desc');
                break;
            default:
                $query->orderBy('featured', 'desc')->orderBy('created_at', 'desc');
        }

        $listings     = $query->paginate($perPage)->withQueryString();
        $selectedZone = $zone;

        return view('v2.pages.property-search', compact(
            'listings', 'selectedZone', 'checkIn', 'checkOut', 'guestNo', 'children', 'sort'
        ));
    }

    public function propertyDetail($key)
    {
        $listing = Listing::active()
            ->with(['images', 'amenities', 'reviews'])
            ->where('slug', $key)
            ->orWhere('id', $key)
            ->firstOrFail();

        $similar = Listing::active()
            ->where('id', '!=', $listing->id)
            ->where('zone', $listing->zone)
            ->limit(3)
            ->get();

        return view('v2.pages.property-detail', compact('listing', 'similar'));
    }
}
