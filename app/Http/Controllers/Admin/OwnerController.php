<?php

namespace App\Http\Controllers\Admin;

use App\Announcement;
use App\Group;
use App\Listing;
use App\ListingCategory;
use App\ListingGroup;
use App\ListingPriceDetail;
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
        // The form sends name, email, phone, password and status only (19 Oct 2026: it also
        // required last_name and country_code, which the form never had, so no owner could
        // be created). Phone keeps its digits; a blank password emails a set-password link.
        $request->merge(['phone' => preg_replace('/\D+/', '', (string) $request->input('phone'))]);
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:150',
            'email'    => 'required|email|max:200|unique:users,email',
            'phone'    => 'required|digits_between:7,15',
            'password' => 'nullable|string|min:8|max:100|confirmed',
            'status'   => 'required|integer',
        ], [
            'name.required'      => 'Enter the owner\'s name.',
            'email.required'     => 'Enter an email address.',
            'email.email'        => 'Enter a valid email address.',
            'email.unique'       => 'An account with this email already exists.',
            'phone.required'     => 'Enter a phone number.',
            'phone.digits_between' => 'Enter the phone number with digits only, e.g. 0123456789.',
            'password.min'       => 'The password needs at least 8 characters, or leave it blank to email a set-password link.',
            'password.confirmed' => 'The two passwords do not match.',
            'status.required'    => 'Choose an account status.',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $sendLink = $request->input('password') === null || $request->input('password') === '';
        $data = $request->only('name', 'email', 'phone', 'status');
        $data['last_name']    = '';
        $data['country_code'] = '60';
        $data['password']     = Hash::make($sendLink ? \Illuminate\Support\Str::random(32) : $request->input('password'));
        $user = User::create($data);

        $role = Role::find(3);
        $user->attachRole($role);
        if ($sendLink) {
            $sent = \Illuminate\Support\Facades\Password::sendResetLink(['email' => $user->email, 'id' => $user->id]) === \Illuminate\Support\Facades\Password::RESET_LINK_SENT;
            return redirect('/admin/owners')->with($sent ? 'success' : 'error', $sent
                ? $user->name . ' created. A set-your-password link has been emailed to ' . $user->email . ' (valid 60 minutes).'
                : $user->name . ' created, but the email could not be sent. Edit the owner to set a password.');
        }
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
            'name'         => 'required|string|max:150',
            'last_name'    => 'nullable|string|max:150',
            'email'        => 'required|string|max:200',
            'country_code' => 'nullable',
            'phone'        => 'nullable',
            'password'     => 'nullable|string|max:100',
            'status'       => 'required|integer',
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
        // The listing index also powers the per-owner list; it has no archive tab here.
        return view('admin.listing.index', compact('listings', 'user') + ['showArchived' => false, 'archivedCount' => 0]);
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
        // Was keyed on $id (the owner) rather than $listingId, so the rental
        // category never loaded on this route.
        $categories = ListingCategory::where('listing_id', $listingId)->pluck('category_id')->toArray();
        $owners = User::join('role_user', 'users.id', '=', 'role_user.user_id')
            ->where('role_id', 3)
            ->orderBy('users.email')
            ->get(['users.id', 'users.name', 'users.last_name', 'users.email']);
        $listingGroup = ListingGroup::where('listing_id', $listingId)->first();
        $listingPriceDetail = ListingPriceDetail::where('listing_id', $listingId)->first();
        if (empty($listingPriceDetail)) {
            $listingPriceDetail = ListingPriceDetail::create(['listing_id' => $listingId]);
        }
        return view('admin.listing.listingEdit', compact('listing', 'user', 'groups', 'categories', 'owners', 'listingGroup', 'listingPriceDetail'));
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
