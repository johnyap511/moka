<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Admin roles restructured (Sam, 6 Oct 2026): Super Admin, Admin, Operation Manager,
// Operation Team, Finance Manager, Finance Team, Sales Person. 'manager' becomes 'admin';
// Eddie is the Operation Manager; Afiq's per-user grants are now the Finance Team default.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('admin_role', 'manager')->update(['admin_role' => 'admin']);
        DB::table('users')->where('email', 'eddie@homemoka.com')->update(['admin_role' => 'operations_manager']);
        // Drop per-user grants that the new role defaults already give (keep explicit denials).
        $role = config('admin_permissions.roles');
        foreach (DB::table('users')->whereNotNull('admin_role')->where('admin_role', '<>', '')->get(['id', 'admin_role']) as $u) {
            $perms = $role[$u->admin_role]['permissions'] ?? [];
            if ($perms && $perms !== ['*']) {
                DB::table('admin_user_permissions')->where('user_id', $u->id)->where('granted', 1)->whereIn('permission', $perms)->delete();
            }
        }
    }

    public function down(): void
    {
        DB::table('users')->where('admin_role', 'admin')->update(['admin_role' => 'manager']);
        DB::table('users')->where('admin_role', 'operations_manager')->update(['admin_role' => 'operations']);
    }
};
