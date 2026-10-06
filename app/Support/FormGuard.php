<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Keeps bots out of the public forms without making visitors solve puzzles.
 * On 5–6 Oct 2026 one address posted 194 "RobertBeert" enquiries in a day,
 * straight to /contact without loading the page, skipping the honeypot.
 *
 *  - token: the form page issues a signed timestamp; a post without it, older
 *    than two hours, or quicker than four seconds after the page loaded is a bot;
 *  - cap: more than five posts from one address in a day;
 *  - repeat: the same name and email more than twice in a day;
 *  - look: a link in the message, or a message written mostly in a script no
 *    guest of ours writes to us in (Cyrillic, Greek, Arabic, Hebrew, Thai, CJK).
 *
 * A caught post is stored with spam = 1, no email goes out, and the visitor
 * sees the normal thank-you, so the bot learns nothing.
 */
class FormGuard
{
    public static function token(): string
    {
        return Crypt::encryptString((string) time());
    }

    /** The reason a post looks like a bot, or null when it looks human. */
    public static function reason(Request $request, string $table, string $message, string $name = '', string $email = ''): ?string
    {
        if ($request->filled('website_url')) {
            return 'honeypot';
        }
        try {
            $issued = (int) Crypt::decryptString((string) $request->input('ft'));
        } catch (\Throwable $e) {
            $issued = 0;
        }
        if (!$issued) {
            return 'no form token';
        }
        $age = time() - $issued;
        if ($age < 4) {
            return 'posted ' . $age . 's after the page loaded';
        }
        if ($age > 7200) {
            return 'form token expired';
        }
        $since = now()->subDay();
        if (DB::table($table)->where('ip', $request->ip())->where('created_at', '>=', $since)->count() >= 5) {
            return 'more than 5 posts from this address today';
        }
        if ($name !== '' && $email !== '' && DB::table($table)->where('email', $email)->where('name', $name)->where('created_at', '>=', $since)->count() >= 2) {
            return 'same name and email more than twice today';
        }
        if (preg_match('~https?://|www\.~i', $message)) {
            return 'link in message';
        }
        $letters = preg_match_all('/\p{L}/u', $message);
        $foreign = preg_match_all('/[\p{Cyrillic}\p{Greek}\p{Arabic}\p{Hebrew}\p{Thai}\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}]/u', $message);
        if ($letters >= 6 && $foreign / $letters > 0.5) {
            return 'message not in a script our guests use';
        }

        return null;
    }
}
