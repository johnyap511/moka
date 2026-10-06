<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password'];
    protected $hidden   = ['password', 'remember_token'];
    protected $casts    = ['email_verified_at' => 'datetime'];

    /**
     * The record to sign in for an email that several records share (a staff login and the
     * guest profile a booking created): the admin login first, then owner, then guest.
     */
    public static function preferredForEmail(string $email): ?self
    {
        return static::whereRaw('LOWER(email) = ?', [strtolower(trim($email))])
            ->leftJoin('role_user', 'role_user.user_id', '=', 'users.id')
            ->select('users.*')->selectRaw('MIN(role_user.role_id) AS best_role')
            ->groupBy('users.id')
            ->orderByRaw('CASE WHEN MIN(role_user.role_id) = 1 THEN 0 WHEN MIN(role_user.role_id) = 3 THEN 1 ELSE 2 END, users.status DESC, users.id')
            ->first();
    }
}
