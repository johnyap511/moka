<?php

namespace App\Http\Controllers;

use App\Models\RefundLink;
use App\Support\Refunds;
use Illuminate\Http\Request;

/** Guest side of the deposit refund portal. No login; the link in the URL is the key. */
class RefundController extends Controller
{
    private function link(string $token): ?RefundLink
    {
        return RefundLink::where('token', $token)->first();
    }

    public function show(string $token)
    {
        $link = $this->link($token);
        if (!$link || !$link->isValid()) {
            return response()->view('refund.expired', [], 410);
        }
        return view('refund.form', [
            'link'       => $link,
            'properties' => array_keys(Refunds::PROPERTIES),
            'staff'      => Refunds::staff(),
        ]);
    }

    public function store(Request $request, string $token)
    {
        $link = $this->link($token);
        if (!$link || !$link->isValid()) {
            return response()->view('refund.expired', [], 410);
        }

        // Honeypot: bots fill every field; people never see this one.
        if ($request->filled('website')) {
            return redirect()->route('refund.thanks');
        }

        $in = $request->validate([
            'name'           => 'required|string|max:120',
            'email'          => 'required|email|max:150',
            'phone'          => 'required|string|max:40',
            'property'       => 'required|string|in:' . implode(',', array_keys(Refunds::PROPERTIES)),
            'unit_no'        => 'required|string|max:40',
            'check_out'      => 'required|date|after:' . now()->subDays(400)->toDateString() . '|before:' . now()->addDays(2)->toDateString(),
            'bank_name'      => 'required|string|max:80',
            'bank_holder'    => 'required|string|max:120',
            'bank_account'   => ['required', 'string', 'max:40', 'regex:/^[\d\s-]{6,40}$/'],
            'rating_clean'   => 'required|integer|between:1,5',
            'rating_service' => 'required|integer|between:1,5',
            'comments'       => 'nullable|string|max:2000',
            'staff_user_id'  => 'nullable|integer|in:' . (Refunds::staff()->pluck('id')->implode(',') ?: '0'),
            'staff_rating'   => 'nullable|integer|between:1,5',
            'staff_comment'  => 'nullable|string|max:1000',
        ], [
            'bank_account.regex' => 'Please enter the account number with digits only.',
            'check_out.after'    => 'Please check the check-out date.',
            'check_out.before'   => 'Please check the check-out date.',
        ]);

        Refunds::submit($in, $link, $request->ip());

        return redirect()->route('refund.thanks');
    }

    public function thanks()
    {
        return view('refund.thanks');
    }
}
