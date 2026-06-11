<?php

namespace App\Http\Controllers\Admin;

use App\AdminUserPermission;
use App\Role;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    /**
     * Display a listing of admin users.
     */
    public function index()
    {
        if (! admin_can('roles.manage')) {
            return redirect('/admin/dashboard');
        }

        $users = User::join('role_user', 'users.id', '=', 'role_user.user_id')
            ->where('role_id', 1)
            ->select('users.*')
            ->get();

        return view('admin.user.adminList', compact('users'));
    }

    /**
     * Show the form for creating a new admin user.
     */
    public function create()
    {
        if (! admin_can('roles.manage')) {
            return redirect('/admin/dashboard');
        }

        return view('admin.user.adminCreate');
    }

    /**
     * Store a newly created admin user.
     */
    public function store(Request $request)
    {
        if (! admin_can('roles.manage')) {
            return redirect('/admin/dashboard');
        }

        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:150',
            'last_name'    => 'required|string|max:150',
            'email'        => 'required|string|email|max:200|unique:users,email',
            'phone'        => 'required|numeric',
            'country_code' => 'required|numeric',
            'password'     => 'required|string|min:6|max:100',
            'status'       => 'required|integer',
            'admin_role'   => 'nullable|string|in:super_admin,manager,finance,operations',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $request->only('name', 'last_name', 'email', 'phone', 'country_code', 'status');
        $data['password']   = Hash::make($request->password);
        $data['admin_role'] = $request->admin_role ?: null;

        $user = User::create($data);

        $role = Role::find(1);
        $user->attachRole($role);

        return redirect('/admin/admin')->with('success', 'Admin created successfully!');
    }

    /**
     * Display the specified resource (unused).
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing an admin user + their permission overrides.
     */
    public function edit($id)
    {
        if (! admin_can('roles.manage')) {
            return redirect('/admin/dashboard');
        }

        $user = User::findOrFail($id);

        // Build a keyed map of explicit overrides: ['permission.key' => true/false]
        $overrides = AdminUserPermission::where('user_id', $id)
            ->get()
            ->keyBy('permission')
            ->map(fn ($row) => $row->granted);

        $permissionGroups = config('admin_permissions.groups', []);

        return view('admin.user.adminEdit', compact('user', 'overrides', 'permissionGroups'));
    }

    /**
     * Update an admin user's details and permission overrides.
     */
    public function update(Request $request, $id)
    {
        if (! admin_can('roles.manage')) {
            return redirect('/admin/dashboard');
        }

        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:150',
            'last_name'    => 'required|string|max:150',
            'email'        => 'required|string|email|max:200',
            'phone'        => 'required|numeric',
            'country_code' => 'required|numeric',
            'password'     => 'nullable|string|min:6|max:100',
            'status'       => 'required|integer',
            'admin_role'   => 'nullable|string|in:super_admin,manager,finance,operations',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::findOrFail($id);

        $data = $request->only('name', 'last_name', 'email', 'phone', 'country_code', 'status');
        $data['admin_role'] = $request->admin_role ?: null;

        if (! empty($request->password)) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        // Sync per-user permission overrides.
        $role            = $data['admin_role'] ?? 'super_admin';
        $rolePermissions = config("admin_permissions.roles.{$role}.permissions", []);
        $isSuperAdmin    = $role === 'super_admin' || in_array('*', $rolePermissions, true);

        $submittedPermissions = $request->input('permissions', []); // ['perm.key' => '1']
        $allPermissions       = [];
        foreach (config('admin_permissions.groups', []) as $perms) {
            foreach (array_keys($perms) as $key) {
                $allPermissions[] = $key;
            }
        }

        foreach ($allPermissions as $permKey) {
            $checked      = isset($submittedPermissions[$permKey]);
            $roleDefault  = $isSuperAdmin || in_array($permKey, $rolePermissions, true);

            if ($checked === $roleDefault) {
                // No override needed – delete any existing one to keep the table clean.
                AdminUserPermission::where('user_id', $id)
                    ->where('permission', $permKey)
                    ->delete();
            } else {
                // Store an explicit override.
                AdminUserPermission::updateOrCreate(
                    ['user_id' => $id, 'permission' => $permKey],
                    ['granted' => $checked]
                );
            }
        }

        return redirect('/admin/admin')->with('success', 'Admin updated successfully!');
    }

    /**
     * Delete an admin user (cannot delete yourself).
     */
    public function destroy($id)
    {
        if (! admin_can('roles.manage')) {
            return redirect('/admin/dashboard');
        }

        if ((int) $id === Auth::id()) {
            return redirect('/admin/admin')->with('error', 'You cannot delete your own account.');
        }

        $user = User::findOrFail($id);
        $user->delete();

        return redirect('/admin/admin')->with('success', 'Admin deleted successfully.');
    }
}
