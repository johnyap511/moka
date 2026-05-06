<?php

namespace App\Http\Controllers\Admin;

use App\Listing;
use App\OtherModel\ListingZone;
use App\OtherModel\Zone;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ZoneController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $zones = Zone::all();
        return view('admin.setting.zone.index', compact('zones'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.setting.zone.createZone');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->only("name","status");
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:200',
            'status' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        Zone::create($data);
        return redirect('/admin/setting/zone')->with('success', 'Zone is created successfully!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $zone = Zone::find($id);
        if(empty($zone)){
            return back()->with('error', 'Zone not found!');
        }
        return view('admin.setting.zone.editZone', compact('zone'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = $request->only("name","status");
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:200',
            'status' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $zone = Zone::find($id);
        if(empty($zone)){
            return back()->with('error', 'Zone not found!');
        }
        $zone->update($data);
        return redirect('/admin/setting/zone')->with('success', 'Zone is updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }


    /**
     * @param $listingId
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function listingZone($listingId)
    {
        $listing = Listing::find($listingId);
        if(empty($listing)){
            return back()->with('error', 'Listing not found!');
        }
        $zones = Zone::all();
        return view('admin.setting.zone.listingZone', compact('listing', 'zones'));
    }


    /**
     * @param $listingId
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function listingZoneStore(Request $request,$listingId)
    {
        ListingZone::where('listing_id', $listingId)->delete();
        foreach ($request->zone as $zone){
            ListingZone::create(['zone_id'=>$zone, 'listing_id'=>$listingId]);
        }
        return back()->with('success', 'Listing zones updated successfully!');
    }

}
