<?php

namespace App\Http\Controllers\Admin;

use App\Announcement;
use App\Group;
use App\Listing;
use App\ListingCategory;
use App\Role;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class OwnerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::join('role_user', 'users.id', '=', 'role_user.user_id')
            ->where('role_id', 3)->get();
        return view('admin.user.ownerList', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.user.ownerCreate');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->only("name", "last_name", "email", "phone", "password", "status", "country_code");
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'last_name' => 'required|string|max:150',
            'email' => 'required|string|max:200',
            'country_code' => 'required|numeric',
            'phone' => 'required|numeric',
            'password' => 'required|string|max:100',
            'status' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }


        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        $role = Role::find(3);
        $user->attachRole($role);
        return redirect('/admin/owners')->with('success', 'Owner is created successfully!');
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
        $user = User::find($id);
        return view('admin.user.ownerEdit', compact('user'));
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
        $data = $request->only("name", "last_name", "email", "phone", "status", "country_code");
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'last_name' => 'required|string|max:150',
            'email' => 'required|string|max:200',
            'country_code' => 'required|numeric',
            'phone' => 'required|numeric',
            'password' => 'nullable|string|max:100',
            'status' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::find($id);
        if (!empty($request->password)) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        return redirect('/admin/owners')->with('success', 'Owner is updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::find($id);
        $user->delete();
        return back()->with('success', 'User is deleted successfully!');
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function ownerListing($id)
    {
        $listings = Listing::where('user_id', $id)->get();
        $user = User::find($id);
        return view('admin.listing.index', compact('listings', 'user'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function ownerAddListing($id)
    {
        $user = User::find($id);
        return view('admin.listing.listingCreate', compact('user'));
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function ownerListingEdit($id, $listingId)
    {
        $user = User::find($id);
        $listing = Listing::find($listingId);
        $groups = Group::all();
        $categories = ListingCategory::where('listing_id', $id)->pluck('category_id')->toArray();
        return view('admin.listing.listingEdit', compact('listing', 'user', 'groups', 'categories'));
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function announcement()
    {
        // $subs = DB::table('subscribe')->get();
        return view('admin.home.announcement');
    }


    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function announcementCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:150',
            'text' => 'required|string|max:150',
            'link' => 'required|string|max:200',

        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $request->only("title", "text", "link");
        $announcement = Announcement::create($data);

        return redirect('/announcement')->with('success', 'Announcement is created successfully!');
    }
}
