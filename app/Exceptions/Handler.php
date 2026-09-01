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
