<?php

namespace App\Http\Controllers;

use App\Models\JobApplication;
use App\Support\FormGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Public careers page: the open roles from config careers.roles and one application form.
 * Every application is saved first; the CV is a private file (never under public/);
 * then it is emailed to the addresses in config careers.to / careers.bcc.
 */
class CareersController extends Controller
{
    public function index()
    {
        return view('v2.pages.careers', ['roles' => config('careers.roles', [])]);
    }

    public function store(Request $request)
    {
        if ($request->filled('website_url')) { return back(); } // honeypot: real visitors never see this field

        $roles = collect(config('careers.roles', []))->pluck('title', 'slug')->all();
        $roles = ['general' => 'General application'] + $roles;

        $validator = Validator::make($request->all(), [
            'name'    => 'required|string|max:120',
            'email'   => 'required|email|max:150',
            'phone'   => 'required|string|max:40',
            'role'    => ['required', Rule::in(array_keys($roles))],
            'link'    => 'nullable|url|max:255',
            'message' => 'required|string|max:4000',
            'cv'      => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'agree'   => 'accepted',
        ], [
            'cv.mimes'       => 'The CV must be a PDF or Word file.',
            'cv.max'         => 'The CV must be 5 MB or smaller.',
            'cv.uploaded'    => 'The CV could not be uploaded. Please try a smaller file.',
            'link.url'       => 'The link must start with https://',
            'agree.accepted' => 'Please tick the box to let us keep your details for this application.',
        ]);
        if ($validator->fails()) {
            return redirect('/careers#cr-alert')->withErrors($validator)->withInput();
        }

        $data = $request->only('name', 'email', 'phone', 'role', 'link', 'message');
        $thanks = 'Thank you. We have your application and will contact you if there is a match.';

        // Links are welcome here (LinkedIn, portfolio), so they are taken out before the bot check looks at the message.
        $spam = FormGuard::reason($request, 'job_applications', (string) preg_replace('~(https?://|www\.)\S+~i', '', $data['message']), (string) $data['name'], (string) $data['email']);

        $cvPath = null; $cvName = null;
        if ($spam === null && $request->hasFile('cv') && $request->file('cv')->isValid()) {
            $file   = $request->file('cv');
            $ext    = strtolower($file->getClientOriginalExtension() ?: $file->extension());
            $cvPath = $file->storeAs('careers', now()->format('Ymd-His') . '-' . Str::random(16) . '.' . $ext, 'local');
            $cvName = Str::limit(preg_replace('/[^A-Za-z0-9._ -]+/', '', $file->getClientOriginalName()) ?: ('cv.' . $ext), 120, '');
        }

        $application = JobApplication::create($data + [
            'role_title'  => $roles[$data['role']],
            'cv_path'     => $cvPath ?: null,
            'cv_name'     => $cvName,
            'ip'          => $request->ip(),
            'spam'        => $spam !== null,
            'spam_reason' => $spam,
        ]);

        // A bot's post is kept for the record without its file, and never emailed; the bot sees the usual thank-you.
        if ($spam !== null) {
            return redirect('/careers#cr-done')->with('success', $thanks);
        }

        try {
            $body = "New application from the homemoka.com careers page\n\n"
                . "Role:    {$application->role_title}\n"
                . "Name:    {$application->name}\n"
                . "Email:   {$application->email}\n"
                . "Phone:   {$application->phone}\n"
                . 'Link:    ' . ($application->link ?: '-') . "\n"
                . 'CV:      ' . ($cvPath ? 'attached (' . $cvName . ')' : 'not attached') . "\n\n"
                . "Message:\n{$application->message}\n\n"
                . 'Received ' . now()->format('d M Y H:i') . ". Reply to this email to answer them. Also listed in Homemoka admin under Job applications.";
            Mail::raw($body, function ($m) use ($application, $cvPath, $cvName) {
                $m->to(config('careers.to', ['hello@homemoka.com']))
                    ->replyTo($application->email, $application->name)
                    ->subject('Job application: ' . $application->role_title . ', ' . $application->name);
                if ($bcc = array_filter((array) config('careers.bcc', []))) {
                    $m->bcc($bcc);
                }
                if ($cvPath) {
                    $m->attach(storage_path('app/' . $cvPath), ['as' => $cvName]);
                }
            });
        } catch (\Throwable $e) {
            Log::warning('Job application email not sent: ' . $e->getMessage(), ['id' => $application->id]);
        }

        return redirect('/careers#cr-done')->with('success', $thanks);
    }
}
