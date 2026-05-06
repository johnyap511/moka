<?php

namespace App\Http\Controllers\Admin;

use App\Listing;
use App\ListingImages;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;

class ListingImagesController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $images = ListingImages::where('listing_id', $id)->get();
        return view('admin.listing.listingImages', compact('images', 'id'));
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:jpeg,jpg,JPG,JPEG,PNG,png|max:4096',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $photo=$request->image;
        $filename  = 'bn'.time() . '.jpg';
        $path = public_path('images/listing/' . $filename);

        $watermark = public_path('images/layout/logo4.png');
        $watermark = Image::make($watermark);

        Image::make($photo)->resize(1400, null, function ($constraint) {
            $constraint->aspectRatio();
        })->insert($watermark, 'center')->save($path);

        ListingImages::create(['listing_id'=>$id, 'image'=>$filename]);
        return back()->with('success', 'Image uploaded successfully!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, $imageId)
    {
        $image = ListingImages::where('id', $imageId)->first();
        if(!empty($image->image)){
            \File::delete('images/listing/' . $image->image);
        }
        $image->delete();
        return back()->with('success', 'Image deleted successfully!');
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function videoShow($id)
    {
        $listing = Listing::find($id);
        return view('admin.listing.listingVideo', compact('listing', 'id'));
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function videoStore(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'video' => 'nullable|string|max:50',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $listing = Listing::find($id);
        $video = $request->video;
        $listing->update(['video'=>$video]);
        return back()->with('success', 'Video updated successfully!');
    }
}
