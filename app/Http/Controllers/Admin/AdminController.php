<?php

namespace App\Http\Controllers\Admin;

use App\AdminUserPermission;
use App\Role;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
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

        $all = User::join('role_user', 'users.id', '=', 'role_user.user_id')
            ->where('role_id', 1)
            ->select('users.*')
            ->orderBy('users.name')
            ->get();
        $archived = request()->boolean('archived');   // the Inactive tab
        $users = $all->filter(fn ($u) => ((int) $u->status !== 1) === $archived)->values();

        return view('admin.user.adminList', [
            'users' => $users, 'archived' => $archived,
            'counts' => ['active' => $all->where('status', 1)->count(), 'archived' => $all->where('status', '<>', 1)->count()],
        ]);
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
            'password'     => 'nullable|string|min:6|max:100',
            'status'       => 'required|integer',
            'admin_role'   => 'nullable|string|in:' . implode(',', array_keys(config('admin_permissions.roles'))),
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $request->only('name', 'last_name', 'email', 'phone', 'country_code', 'status');
        // No password given: the new person sets their own from an emailed link (6 Oct 2026).
        $sendLink = $request->password === null || $request->password === '';
        $data['password']   = Hash::make($sendLink ? Str::random(32) : $request->password);
        $data['admin_role'] = $request->admin_role ?: null;

        $user = User::create($data);

        $role = Role::find(1);
        $user->attachRole($role);
        if ($sendLink) {
            $sent = Password::sendResetLink(['email' => $user->email]) === Password::RESET_LINK_SENT;
            return redirect('/admin/admin')->with($sent ? 'success' : 'error', $sent
                ? $user->name . ' created. A set-your-password link has been emailed to ' . $user->email . ' (valid 60 minutes).'
                : $user->name . ' created, but the email could not be sent. Use "Send password link" on the edit page or check Mail settings.');
        }

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
            'admin_role'   => 'nullable|string|in:' . implode(',', array_keys(config('admin_permissions.roles'))),
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
        if ((int) $user->status === 1) {
            return redirect('/admin/admin')->with('error', 'Set the login to Inactive first; delete is only for inactive logins.');
        }
        $user->delete();

        return redirect('/admin/admin?archived=1')->with('success', 'Admin deleted.');
    }

    /** Click on the status badge: Active ⇄ Inactive. Inactive = no admin access; record and history stay. */
    public function toggle($id)
    {
        if (! admin_can('roles.manage')) {
            return redirect('/admin/dashboard');
        }
        if ((int) $id === Auth::id()) {
            return redirect('/admin/admin')->with('error', 'You cannot deactivate your own account.');
        }
        $user = User::findOrFail($id);
        if ((int) $user->status === 1) {
            $user->update(['status' => 0, 'archived_at' => now()]);
            $left = DB::table('sales_persons')->where('user_id', $user->id)->whereNull('left_on')->update(['left_on' => now()->toDateString()]);

            return redirect('/admin/admin')->with('success', $user->name . ' is now inactive and cannot sign in.' . ($left ? ' Sales person marked as left today.' : ''));
        }
        $user->update(['status' => 1, 'archived_at' => null]);

        return redirect('/admin/admin?archived=1')->with('success', $user->name . ' is active again. Check the sales person leaving date if one was set.');
    }

    public function archive($id)
    {
        return $this->toggle($id);
    }

    /** Email a set/reset-password link to an admin login (valid 60 minutes). */
    public function sendReset($id)
    {
        if (! admin_can('roles.manage')) {
            return redirect('/admin/dashboard');
        }
        $user = User::findOrFail($id);
        $status = Password::sendResetLink(['email' => $user->email]);

        return back()->with($status === Password::RESET_LINK_SENT ? 'success' : 'error',
            $status === Password::RESET_LINK_SENT ? 'Password link emailed to ' . $user->email . '.' : 'Could not send: ' . __($status));
    }

    public function restore($id)
    {
        return $this->toggle($id);
    }
}
