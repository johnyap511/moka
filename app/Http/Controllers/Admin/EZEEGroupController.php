<?php

namespace App\Http\Controllers\Admin;

use App\EzeeGroup;
use App\EzeeGroupListing;
use App\Listing;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class EZEEGroupController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function groupList(){
        $groups = EzeeGroup::all();
        return view('admin.listing.ezeeGroup.groupList',compact('groups'));
    }


    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create(){
        return view('admin.listing.ezeeGroup.groupCreate');
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->only("name","hotel_code","auth_key");
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:200',
            'hotel_code' => 'required|string|max:230',
            'auth_key' => 'required|string|max:230',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        EzeeGroup::create($data);
        return redirect('/admin/ezee/group')->with('success', 'EZEE group is created successfully!');
    }



    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function addListing($groupId){
        $group = EzeeGroup::find($groupId);
        $listings = Listing::all();
        $addedListings = EzeeGroupListing::where('ezee_group_id',$groupId)->get();
        return view('admin.listing.ezeeGroup.addListing', compact('group', 'listings','addedListings'));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addListingStore(Request $request,$groupId)
    {
        $validator = Validator::make($request->all(), [
            'listing_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        EzeeGroupListing::create(['listing_id'=>$request->listing_id, 'ezee_group_id'=>$groupId]);
        return back()->with('success', 'Listing added to EZEE group successfully!');
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function listingDestroy(Request $request,$groupId)
    {
        $validator = Validator::make($request->all(), [
            'listing_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        EzeeGroupListing::where([['listing_id',$request->listing_id], ['ezee_group_id',$groupId]])->delete();
        return back()->with('success', 'Listing deleted from EZEE group successfully!');
    }

}
