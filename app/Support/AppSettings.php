<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AppSettings
{
    public static function get(string $key): ?string
    {
        try {
            if (!Schema::hasTable('app_settings')) return null;
            $v = DB::table('app_settings')->where('key', $key)->value('value');
            return $v === null ? null : Crypt::decryptString($v);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function set(string $key, ?string $value): void
    {
        DB::table('app_settings')->updateOrInsert(['key' => $key], ['value' => $value === null ? null : Crypt::encryptString($value), 'updated_at' => now(), 'created_at' => now()]);
    }

    /** Apply portal-set values over the .env config. Called at boot. */
    public static function applyMail(): void
    {
        if ($pw = self::get('mail.password')) {
            config(['mail.mailers.smtp.password' => $pw]);
        }
        if ($user = self::get('mail.username')) {
            config(['mail.mailers.smtp.username' => $user, 'mail.from.address' => $user]);
        }
    }
}
