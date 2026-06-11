<?php

use App\AdminUserPermission;
use Illuminate\Support\Facades\Auth;

if (! function_exists('admin_can')) {
    /**
     * Check whether the given (or currently authenticated) admin user has a permission.
     *
     * Resolution order:
     *  1. Explicit per-user override in admin_user_permissions table.
     *  2. Role defaults from config/admin_permissions.php.
     *  3. Null / empty admin_role → treated as super_admin (full access).
     */
    function admin_can(string $permission, $user = null): bool
    {
        if ($user === null) {
            $user = Auth::user();
        }

        if (! $user) {
            return false;
        }

        // 1. Check explicit per-user override.
        try {
            $override = AdminUserPermission::where('user_id', $user->id)
                ->where('permission', $permission)
                ->first();

            if ($override !== null) {
                return (bool) $override->granted;
            }
        } catch (\Throwable $e) {
            // Table may not exist yet during migrations – fall through to role check.
        }

        // 2. Determine role (default to super_admin for backward compatibility).
        $role = (isset($user->admin_role) && ! empty($user->admin_role))
            ? $user->admin_role
            : 'super_admin';

        // 3. super_admin always has everything.
        if ($role === 'super_admin') {
            return true;
        }

        // 4. Look up role permissions from config.
        $rolePermissions = config("admin_permissions.roles.{$role}.permissions", []);

        if (in_array('*', $rolePermissions, true)) {
            return true;
        }

        return in_array($permission, $rolePermissions, true);
    }
}
