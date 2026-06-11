<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class AdminRoleController extends Controller
{
    /**
     * Show a read-only reference page of all roles and their permissions.
     * GET /admin/setting/admin-roles
     */
    public function index()
    {
        if (! admin_can('roles.manage')) {
            return redirect('/admin/dashboard');
        }

        $groups = config('admin_permissions.groups', []);
        $roles  = config('admin_permissions.roles', []);

        // Build a flat list of all permission keys for the matrix header.
        $allPermissions = [];
        foreach ($groups as $groupName => $perms) {
            foreach ($perms as $key => $label) {
                $allPermissions[$key] = ['group' => $groupName, 'label' => $label];
            }
        }

        return view('admin.settings.admin_roles', compact('groups', 'roles', 'allPermissions'));
    }
}
