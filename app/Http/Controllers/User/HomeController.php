<?php

namespace App\Http\Controllers\User;

use App\Booking;
use App\Listing;
use App\ListingCategory;
use App\User;
use App\VerifyAccount;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;

class HomeController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(){

        $listings = Listing::where('status', 1)->get();
       
        return view('user.home.index', compact('listings'));
    }


    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function bookingHistory(){
        $books = Booking::where([['user_id', Auth::user()->id],['status', '>=', 3]])->get();
        return view('user.home.bookingHistory', compact('books'));
    }


    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function listingFilter(Request $request){
        $type = $request->type;
        $listingId = ListingCategory::whereIn('category_id', $type)->pluck('listing_id')->toArray();
        $listings = Listing::whereIn('id', $listingId)->get();
        return view('user.home.index', compact('listings', 'type'));
    }


    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function profileEdit(){
        $user = Auth::user();
        return view('user.home.profileEdit', compact('user'));
    }




    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function profileUpdate(Request $request){
        $regData = $request->only('name','last_name','email','phone','country_code');
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:120',
            'last_name' => 'required|string|max:120',
            'email' => 'required|email|max:150',
            'phone' => 'required|numeric',
            'country_code' => 'required|numeric',
            'password' => 'nullable|string|max:120',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = Auth::user();

        $exist = User::where('email', $request->email)->first();
        if(!empty($exist) && $user->id != $exist->id){
            return back()->with('error', "This email address is registered before!");
        }

        if(substr($request->phone,0,1) == '0'){
            $regData['phone'] = substr($request->phone,1);
        }
        $phone = $regData['country_code'].$regData['phone'];
        $exist = User::where('phone', $phone)->first();
        if(!empty($exist) && $user->id != $exist->id){
            return back()->with('error', "This phone number is registered before!");
        }


        if(!empty($request->password)){
            $regData['password'] = bcrypt($request->password);
        }

        $user->update($regData);

        return back()->with("success", "Your profile is updated successfully.");
    }



    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function verify(){
        return view('user.home.verification');
    }


    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function verifyStore(Request $request){
        $verifyData = $request->only('document_type', 'document_number');
        $validator = Validator::make($request->all(), [
            'document_type' => 'required|string|max:120',
            'document_number' => 'required|string|max:120',
            'document_image' => 'required|mimes:jpeg,jpg,JPG,JPEG,PNG,png|max:4096',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $userId = Auth::user()->id;
        $photo=$request->document_image;
        if(!empty($photo)){
            $filename  = $userId.time() . '.jpg';
            $path = public_path('images/verification/' . $filename);;
            Image::make($photo)->resize(1400, null, function ($constraint) {
                $constraint->aspectRatio();
            })->save($path);
            $verifyData['document_image'] = $filename;
        }

        $verifyData['status'] = 1;

        $exist = VerifyAccount::where('user_id', $userId)->first();
        if(!empty($exist)){
            if(!empty($exist->document_image)){
                \File::delete('images/verification/' . $exist->document_image);
            }
            $exist->update($verifyData);
        }else{
            $verifyData['user_id'] = $userId;
            VerifyAccount::create($verifyData);
        }

        return redirect('/user/profile')->with("success", "Your document for verification is submitted successfully.");
    }
}
