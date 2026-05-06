<?php

namespace App\Http\Controllers\Admin;

use App\Amenities;
use App\Listing;
use App\OtherModel\ListingAmenities;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;

class AmenitiesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $amenities = Amenities::all();
        return view('admin.setting.amenities.index', compact('amenities'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.setting.amenities.createAmenities');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->only("name","order","chinese","malay");
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'order' => 'required|numeric',
            'image' => 'required|mimes:jpeg,jpg,JPG,JPEG,PNG,png|max:1000',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $photo=$request->image;
        $filename  = 'amenities_'.time().'.png';
        $path = public_path('images/listing/' . $filename);
        Image::make($photo)->save($path);
        $data['image'] = $filename;

        Amenities::create($data);
        return redirect('/admin/setting/amenities')->with('success', 'Amenities is created successfully!');
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
        $amenities = Amenities::find($id);
        if(empty($amenities)){
            return back()->with('error', 'Amenities not found!');
        }
        return view('admin.setting.amenities.editAmenities', compact('amenities'));
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
        $data = $request->only("name","order","chinese","malay");
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'order' => 'required|numeric',
            'image' => 'nullable|mimes:jpeg,jpg,JPG,JPEG,PNG,png|max:1000',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $amenities = Amenities::find($id);
        if(empty($amenities)){
            return back()->with('error', 'Amenities not found!');
        }

        $photo=$request->image;
        if(!empty($photo)){
            $filename  = 'amenities_'.time().'.png';
            $path = public_path('images/listing/' . $filename);
            Image::make($photo)->save($path);
            $data['image'] = $filename;
            \File::delete('images/listing/'.$amenities->image);
        }

        $amenities->update($data);
        return redirect('/admin/setting/amenities')->with('success', 'Amenities is updated successfully!');
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
    public function listingAmenities($listingId)
    {
        $listing = Listing::find($listingId);
        if(empty($listing)){
            return back()->with('error', 'Listing not found!');
        }
        $amenities = Amenities::all();
        return view('admin.setting.amenities.listingAmenities', compact('listing', 'amenities'));
    }


    /**
     * @param $listingId
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function listingAmenitiesStore(Request $request,$listingId)
    {
        ListingAmenities::where('listing_id', $listingId)->delete();
        foreach ($request->amenities as $amenity){
            ListingAmenities::create(['amenities_id'=>$amenity, 'listing_id'=>$listingId]);
        }
        return back()->with('success', 'Listing amenities updated successfully!');
    }



}
