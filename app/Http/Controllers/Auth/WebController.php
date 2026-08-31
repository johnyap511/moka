<?php

namespace App\Http\Controllers\Auth;

use App\Booking;
use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationMail;
use App\Role;
use App\User;
use App\Verification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WebController extends Controller
{

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('welcome');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function newHome(Request $request)
    {
        if (Auth::check()) {
            if (Auth::user()->hasRole("owner")) {
                return redirect('/owner/dashboard');
            } elseif (Auth::user()->hasRole("admin")) {
                return redirect('/admin/dashboard');
            }
        }

        $modal = $request->modal;
        // dd("ok");
        $listingIds = Booking::where('status', '>=', 5)->select('listing_id')->get()->groupBy('listing_id')->toArray();

        // dd($listingIds);
        array_multisort(array_map('count', $listingIds), SORT_DESC, $listingIds);
        $listingIdsCount = sizeof($listingIds);
        $listingIds = array_slice($listingIds, 0, 10);
        if (!empty($request->alert)) {
            \Session::put('error', $request->alert);
        }
        return view('auth.newTheme.home', compact('listingIds', 'modal', 'listingIdsCount'));
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function newHomeNew(Request $request)
    {
        if (Auth::check()) {
            if (Auth::user()->hasRole("owner")) {
                return redirect('/owner/dashboard');
            } elseif (Auth::user()->hasRole("admin")) {
                return redirect('/admin/dashboard');
            }
        }

        $modal = $request->modal;
        // dd("ok");
        $listingIds = Booking::where('status', '>=', 5)->select('listing_id')->get()->groupBy('listing_id')->toArray();

        // dd($listingIds);
        array_multisort(array_map('count', $listingIds), SORT_DESC, $listingIds);
        $listingIdsCount = sizeof($listingIds);
        $listingIds = array_slice($listingIds, 0, 10);
        if (!empty($request->alert)) {
            \Session::put('error', $request->alert);
        }
        $featuredListings = \App\Listing::where('status', 1)->limit(6)->get();
        return view('v2.pages.home', compact('listingIds', 'modal', 'listingIdsCount', 'featuredListings'));
    }
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function newHomepage()
    {
        return view('auth.newTheme.homepage');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function HomeAbout()
    {
        return view('v2.pages.about');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function HomeService()
    {
        return view('auth.newTheme.service');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function HomeDesigns()
    {
        return view('auth.newTheme.design23');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function contact()
    {
        return view('auth.contact');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function contactStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required',
            'message' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect('/contact')
                ->withErrors($validator)
                ->withInput();
        }

        $email = "sam@homemoka.com";
        $data22 = $request->only('name', 'phone', 'email', 'message');
        //        Mail::to($email)->queue(new ContactWebMail($data22));

        return back()->with("success", "Message sent successfully!");
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return redirect('/')
                ->withErrors($validator)
                ->withInput();
        }

        $exist = DB::table('subscribe')->where('email', $request->email)->first();
        if (!empty($exist)) {
            return back()->with("error", "You are subscribed before!");
        }
        $now = Carbon::now();
        DB::table('subscribe')->insert(['email' => $request->email, 'created_at' => $now, 'updated_at' => $now]);
        return back()->with("success", "Subscribed successfully!");
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function login()
    {
        return redirect('/?modal=login');
        return view('auth.login');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function loginStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect('/?modal=login')
                ->withErrors($validator)
                ->withInput();
        }

        $users = User::where('email', $request->email)->get();
        // dd(count($users), empty($users));
        if (count($users) == 0) {
            $users = User::where('phone', $request->email)->get();
            if (count($users) == 0) {
                return back()->with('error', "Email address or password is wrong!");
            }
        }
        foreach ($users as $user) {
            if (Hash::check($request->password, $user->password)) {

                if ($user->status == 6) {
                    return back()->with('error', "Your account is blocked, please contact support team for more information!");
                }

                if ($user->status == 2) {
                    return back()->with('error', "Your account is not activated, please check your email and click on verification link!");
                }

                if (Hash::check($request->password, $user->password)) {
                    Auth::login($user);
                    if ($user->hasRole("user") === true) {
                        return back();
                        return redirect('/user/dashboard');
                    } elseif ($user->hasRole("owner") === true) {
                        return redirect('/owner/dashboard');
                    } elseif ($user->hasRole("admin") === true) {
                        return redirect('/admin/dashboard');
                    }
                    Auth::guard()->logout();
                    $request->session()->invalidate();
                    return redirect('/?modal=login');
                }
            }
        }
        return back()->with("error", "Email or password is wrong!");
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function loginStoreNew(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect('/?modal=login')
                ->withErrors($validator)
                ->withInput();
        }

        $user = User::where('email', $request->email)->first();
        if (empty($user)) {
            $user = User::where('phone', $request->email)->first();
            if (empty($user)) {
                return back()->with('error', "Email address or password is wrong!");
            }
        }
        if ($user->status == 6) {
            return back()->with('error', "Your account is blocked, please contact support team for more information!");
        }

        if ($user->status == 2) {
            return back()->with('error', "Your account is not activated, please check your email and click on verification link!");
        }

        if (Hash::check($request->password, $user->password)) {
            Auth::login($user);
            if ($user->hasRole("user") === true) {
                return back();
                return redirect('/user/dashboard');
            } elseif ($user->hasRole("owner") === true) {
                return redirect('/owner/dashboard');
            } elseif ($user->hasRole("admin") === true) {
                return redirect('/admin/dashboard');
            }
            Auth::guard()->logout();
            $request->session()->invalidate();
            return redirect('/?modal=login');
        }
        return back()->with("error", "Email or password is wrong!");
    }
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function register()
    {
        return redirect('/?modal=register');
        return view('auth.register');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function registerStore(Request $request)
    {
        $regData = $request->only('name', 'email', 'phone', 'country_code');
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:150',
            'phone' => 'required|numeric',
            'country_code' => 'required|numeric',
            'password' => 'required|string|max:120',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $exist = User::where([['email', $request->email], ['ezee_tmp', 0]])->first();
        if (!empty($exist)) {
            return back()->with('error', "This email address is registered before!");
        }

        if (substr($request->phone, 0, 1) == '0') {
            $regData['phone'] = substr($request->phone, 1);
        }
        $phone = $regData['country_code'] . $regData['phone'];
        $exist = User::where([['phone', $phone], ['ezee_tmp', 0]])->first();
        if (!empty($exist)) {
            return back()->with('error', "This phone number is registered before!");
        }

        //this status should be 2 if need email send verification code
        $regData['status'] = 2;
        $regData['password'] = Hash::make($request->password);

        $user = User::where([['email', $request->email], ['ezee_tmp', 1]])->orWhere([['phone', $phone], ['ezee_tmp', 1]])->first();
        if (empty($user)) {
            $user = User::create($regData);
            $role = Role::find(2);
            $user->attachRole($role);
        } else {
            $user->update($regData);
        }

        $email = $request->email;
        $code = Str::random(40);
        Verification::create(['user_id' => $user->id, 'user' => $email, 'code' => $code, 'last_try' => Carbon::now(), 'tries' => 1]);
        $data22['code'] = $code;
        $data22['name'] = $request->name;
        Mail::to($email)->queue(new EmailVerificationMail($data22));
        return back()->with("success", "You are registered successfully. Please check your email at INBOX MAIL or SPAM MAIL to click on the verification link to activate your account.");
        //              Auth::login($user);
        return redirect('/user/dashboard');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function registerOwnerStore(Request $request)
    {
        $regData = $request->only('name', 'email', 'phone', 'address', 'country_code');
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:150',
            'phone' => 'required|numeric',
            'country_code' => 'required|numeric',
            'password' => 'required|string|max:120',
            'address' => 'required|string|max:250',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $exist = User::where('email', $request->email)->first();
        if (!empty($exist)) {
            return back()->with('error', "This email address is registered before!");
        }

        if (substr($request->phone, 0, 1) == '0') {
            $regData['phone'] = substr($request->phone, 1);
        }
        $phone = $regData['country_code'] . $regData['phone'];
        $exist = User::where('phone', $phone)->first();
        if (!empty($exist)) {
            return back()->with('error', "This phone number is registered before!");
        }

        //this status should be 2 if need email send verification code
        $regData['status'] = 2;
        $regData['password'] = Hash::make($request->password);
        $user = User::create($regData);

        $role = Role::find(3);
        $user->attachRole($role);
        $email = $request->email;
        $code = Str::random(40);
        Verification::create(['user_id' => $user->id, 'user' => $email, 'code' => $code, 'last_try' => Carbon::now(), 'tries' => 1]);
        $data22['code'] = $code;
        $data22['name'] = $request->name;
        Mail::to($email)->queue(new EmailVerificationMail($data22));
        return back()->with("success", "You are registered successfully. Please check your email at INBOX MAIL or SPAM MAIL to click on the verification link to activate your account.");
        //        Auth::login($user);
        return redirect('/user/dashboard');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function verificationCode($code)
    {
        $verify = Verification::where('code', $code)->first();
        if (empty($verify)) {
            return redirect('/')->with('error', "The link is expired, please contact support team for help!");
        }
        $user = User::find($verify->user_id);
        if (empty($user)) {
            return redirect('/')->with('error', "Invalid code!");
        }
        if ($user->status != 2) {
            return redirect('/')->with('error', "Your account is verified already!");
        }
        $user->update(['status' => 1]);
        return redirect('/')->with("success", "Your email address is verified and your account is active now, please login using below form!");
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function policy()
    {
        return view('auth.policy');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function terms()
    {
        return view('auth.terms');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function logout(Request $request)
    {
        Auth::guard()->logout();
        $request->session()->invalidate();

        return redirect('/');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function logouts(Request $request)
    {
        Auth::guard()->logout();
        $request->session()->invalidate();

        return redirect('/homepage');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function newAbout()
    {

        return view('auth.newTheme.about');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function listProperty()
    {
        return view('auth.newTheme.listProperty');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function estimate()
    {
        return view('auth.newTheme.estimate');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function consultation()
    {
        return view('auth.newTheme.consultation');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function confirm(Request $request)
    {
        $modal1 = $request->modal1;
        $modal2 = $request->modal2;
        return view('auth.newTheme.confirm', compact('modal1', 'modal2'));
    }

    public function getEstimate()
    {
        return view('v2.pages.estimate');
    }

    public function submitEstimate(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:150',
            'phone'   => 'required',
            'address' => 'required|string|max:250',
            'bedroom' => 'required',
            'type'    => 'required',
        ]);
        \App\OtherModel\PropertyEstimate::create($request->only('name', 'email', 'phone', 'address', 'bedroom', 'type'));
        return back()->with('success', 'Thank you! We will contact you shortly.');
    }

    public function locationSearch(Request $request)
    {
        $query = $request->get('q', '');
        $listings = \App\Listing::where('status', 1)
            ->when($query, function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('address', 'like', "%{$query}%");
            })
            ->with(['amenities', 'images', 'zones'])
            ->paginate(12);
        $selectedZone = $query;
        $checkIn  = $request->get('check_in', '');
        $checkOut = $request->get('check_out', '');
        $guestNo  = $request->get('guests', 1);
        return view('v2.pages.property-search', compact('listings', 'query', 'selectedZone', 'checkIn', 'checkOut', 'guestNo'));
    }

    public function propertyDetail($key)
    {
        $listing = \App\Listing::where(function ($q) use ($key) {
            $q->where('key', $key)->orWhere('name', $key);
        })->where('status', 1)
          ->with(['amenities', 'details', 'images', 'reviews', 'zones'])
          ->firstOrFail();

        $similar = \App\Listing::where('status', 1)
            ->where('id', '!=', $listing->id)
            ->with(['images', 'zones'])
            ->inRandomOrder()
            ->limit(3)
            ->get();

        return view('v2.pages.property-detail', compact('listing', 'similar'));
    }
}
