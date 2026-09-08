<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AppSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/** Super admin sets the outgoing mail account here, so no password travels through chat or email. */
class MailSettingsController extends Controller
{
    private function guard(): void
    {
        abort_unless(admin_is_super(), 403);
    }

    public function edit()
    {
        $this->guard();
        AppSettings::applyMail();
        return view('admin.mail_settings', [
            'host'     => config('mail.mailers.smtp.host'),
            'port'     => config('mail.mailers.smtp.port'),
            'username' => config('mail.mailers.smtp.username'),
            'hasPassword' => (bool) config('mail.mailers.smtp.password'),
            'source'   => AppSettings::get('mail.password') ? 'portal' : 'server file',
        ]);
    }

    public function update(Request $request)
    {
        $this->guard();
        $request->validate(['username' => 'required|email', 'password' => 'required|string|min:6']);
        AppSettings::set('mail.username', $request->input('username'));
        AppSettings::set('mail.password', $request->input('password'));
        return redirect('/admin/settings/mail')->with('success', 'Mail account saved. Send a test to confirm it works.');
    }

    public function test(Request $request)
    {
        $this->guard();
        AppSettings::applyMail();
        $to = $request->input('to') ?: config('mail.from.address');
        try {
            Mail::raw('Test email from homemoka.com sent ' . now()->format('d M Y H:i') . '. Outgoing mail is working.', fn ($m) => $m->to($to)->subject('MOKA mail test'));
            return redirect('/admin/settings/mail')->with('success', 'Test email sent to ' . $to . '. Check the inbox.');
        } catch (\Throwable $e) {
            return redirect('/admin/settings/mail')->with('error', 'Sending failed: ' . substr($e->getMessage(), 0, 220));
        }
    }
}
