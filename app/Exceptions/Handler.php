<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        // A form loaded before a deploy, or a leftover cookie from the old
        // site, carries a token this session does not know. Send the person
        // back to the form with a plain sentence instead of a bare 419 page.
        // Laravel wraps the token exception in a 419 HttpException before the
        // callbacks run, so that is the type to catch.
        $this->renderable(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Your session expired. Reload the page and try again.'], 419);
            }
            return redirect()->back()->withInput($request->except('password', '_token'))
                ->withErrors(['email' => 'Your session expired. Please try again.']);
        });

        $this->reportable(function (Throwable $e) {
            //
        });

        // A double booking is refused by the Booking model itself. Turn that
        // into a message the person sees, wherever it is triggered from, rather
        // than a 500 they cannot act on.
        $this->renderable(function (OverlappingBookingException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage())->withInput();
        });
    }
}
