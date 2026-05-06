<?php

namespace App\Http\Controllers\Admin;

use App\Role;
use App\User;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(Auth::user()->id != 1){
            return redirect('/admin/dashboard');
        }
        $users = User::join('role_user', 'users.id', '=', 'role_user.user_id')
            ->where('role_id', 1)->get();
        return view('admin.user.adminList', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.user.adminCreate');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if(Auth::user()->id != 1){
            return redirect('/admin/dashboard');
        }
        $data = $request->only("name","last_name","email","phone","password","status","country_code");
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'last_name' => 'required|string|max:150',
            'email' => 'required|string|max:200',
            'phone' => 'required|numeric',
            'country_code' => 'required|numeric',
            'password' => 'required|string|max:100',
            'status' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }


        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        $role = Role::find(1);
        $user->attachRole($role);
        return redirect('/admin/admin')->with('success', 'Admin is created successfully!');
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
        if(Auth::user()->id != 1){
            return redirect('/admin/dashboard');
        }
        $user = User::find($id);
        return view('admin.user.adminEdit', compact('user'));
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
        if(Auth::user()->id != 1){
            return redirect('/admin/dashboard');
        }
        $data = $request->only("name","last_name","email","phone","status","country_code");
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
        if(!empty($request->password)){
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        return redirect('/admin/admin')->with('success', 'Admin is updated successfully!');
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
}