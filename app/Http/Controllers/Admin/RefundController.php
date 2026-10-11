<?php

namespace App\Http\Controllers\Admin;

use App\Booking;
use App\Http\Controllers\Controller;
use App\Models\RefundRequest;
use App\DataLog;
use App\Support\Refunds;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Finance side of the deposit refund portal: requests beside their booking,
 * bank details for refunds.manage only, mark paid, and the guest feedback tab.
 */
class RefundController extends Controller
{
    public function __construct()
    {
        $this->middleware(fn ($request, $next) => admin_can('refunds.view') || admin_can('refunds.feedback') ? $next($request) : abort(403));
    }

    public function index(Request $request)
    {
        $tab = $request->input('tab', admin_can('refunds.view') ? 'pending' : 'feedback');
        $q   = trim((string) $request->input('q', ''));
        $ym  = $request->input('month', now()->format('Y-m'));

        $base = RefundRequest::query();
        $counts = [
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'verify'  => (clone $base)->where('status', 'pending')->where('match', '!=', 'matched')->count(),
            'overdue' => (clone $base)->where('status', 'pending')->where('due_on', '<', today())->count(),
            'paid'    => (clone $base)->where('status', 'paid')->count(),
        ];

        $rows = collect();
        if ($tab !== 'feedback' && admin_can('refunds.view')) {
            $query = RefundRequest::with(['booking.listing', 'booking.user', 'staff', 'payer'])->orderByDesc('id');
            if ($tab === 'pending') {
                $query->where('status', 'pending');
            } elseif ($tab === 'paid') {
                $query->where('status', 'paid');
            } elseif ($tab === 'rejected') {
                $query->where('status', 'rejected');
            }
            if ($q !== '') {
                $d = Refunds::digits($q);
                $query->where(function ($w) use ($q, $d) {
                    $w->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%")->orWhere('unit_no', 'like', "%$q%")->orWhere('reference', 'like', "%$q%");
                    if ($d !== '') {
                        $w->orWhere('phone_digits', 'like', "%$d%");
                    }
                    if (ctype_digit($q)) {
                        $w->orWhere('id', (int) $q)->orWhere('booking_id', (int) $q);
                    }
                });
            }
            $rows = $query->paginate(40)->withQueryString();
        }

        $feedback = null;
        if ($tab === 'feedback') {
            $feedback = $this->feedback($ym);
        }

        return view('admin.refunds.index', [
            'tab' => $tab, 'q' => $q, 'ym' => $ym, 'rows' => $rows, 'counts' => $counts, 'feedback' => $feedback,
            'link' => Refunds::currentLink(),
            'canManage' => admin_can('refunds.manage'),
        ]);
    }

    private function feedback(string $ym): array
    {
        $from = Carbon::createFromFormat('Y-m', $ym)->startOfMonth();
        $to   = $from->copy()->addMonth();
        $all  = RefundRequest::with('staff')->where('created_at', '>=', $from)->where('created_at', '<', $to)->orderByDesc('id')->get();

        $staff = $all->whereNotNull('staff_user_id')->groupBy('staff_user_id')->map(function ($g) {
            $rated = $g->whereNotNull('staff_rating');
            return [
                'user'     => $g->first()->staff,
                'count'    => $g->count(),
                'avg'      => $rated->count() ? round($rated->avg('staff_rating'), 2) : null,
                'fives'    => $rated->where('staff_rating', 5)->count(),
                'low'      => $rated->where('staff_rating', '<=', 2)->count(),
                'comments' => $g->filter(fn ($r) => filled($r->staff_comment))->values(),
            ];
        })->sortByDesc('count')->values();

        $properties = $all->groupBy('property')->map(fn ($g) => [
            'count'   => $g->count(),
            'clean'   => $g->whereNotNull('rating_clean')->count() ? round($g->avg('rating_clean'), 2) : null,
            'service' => $g->whereNotNull('rating_service')->count() ? round($g->avg('rating_service'), 2) : null,
        ])->sortByDesc('count');

        return [
            'month'      => $from->format('F Y'),
            'total'      => $all->count(),
            'clean'      => $all->whereNotNull('rating_clean')->count() ? round($all->avg('rating_clean'), 2) : null,
            'service'    => $all->whereNotNull('rating_service')->count() ? round($all->avg('rating_service'), 2) : null,
            'staff'      => $staff,
            'properties' => $properties,
            'comments'   => $all->filter(fn ($r) => filled($r->comments))->values(),
        ];
    }

    /** A fresh 48-hour link, on request. */
    public function newLink()
    {
        if (!admin_can('refunds.link')) {
            abort(403);
        }
        $link = Refunds::link(Auth::id(), true);
        $this->log(null, 'Refund link issued', ['token' => $link->token, 'expires_at' => $link->expires_at->toDateTimeString()]);
        return back()->with('success', 'New refund link issued, valid until ' . $link->expires_at->format('D d M, H:i') . '.');
    }

    public function paid(Request $request, RefundRequest $refund)
    {
        $this->manage();
        $request->validate(['reference' => 'nullable|string|max:120']);
        if ($refund->status === 'paid') {
            return back()->with('error', 'Request #' . $refund->id . ' is already marked paid.');
        }
        $refund->update(['status' => 'paid', 'paid_at' => now(), 'paid_by' => Auth::id(), 'reference' => $request->input('reference') ?: null]);
        $this->log($refund, 'Refund paid', ['reference' => $refund->reference]);
        return back()->with('success', 'Request #' . $refund->id . ' marked paid.');
    }

    public function reject(Request $request, RefundRequest $refund)
    {
        $this->manage();
        $request->validate(['note' => 'required|string|max:255']);
        $refund->update(['status' => 'rejected', 'note' => $request->input('note')]);
        $this->log($refund, 'Refund rejected', ['note' => $refund->note]);
        return back()->with('success', 'Request #' . $refund->id . ' rejected. The record is kept.');
    }

    public function reopen(RefundRequest $refund)
    {
        $this->manage();
        $refund->update(['status' => 'pending', 'paid_at' => null, 'paid_by' => null]);
        $this->log($refund, 'Refund reopened', []);
        return back()->with('success', 'Request #' . $refund->id . ' is pending again.');
    }

    /** Finance ties a "verify" request to the booking they found, or clears a wrong match. */
    public function attach(Request $request, RefundRequest $refund)
    {
        $this->manage();
        $request->validate(['booking_id' => 'nullable|integer']);
        $id = $request->input('booking_id');
        if ($id) {
            $booking = Booking::with('listing')->find($id);
            if (!$booking) {
                return back()->with('error', 'Booking #' . $id . ' not found.');
            }
            $dup = RefundRequest::where('booking_id', $booking->id)->where('id', '!=', $refund->id)->orderBy('id')->first();
            $refund->update([
                'booking_id'   => $booking->id,
                'match'        => $dup ? 'duplicate' : 'matched',
                'duplicate_of' => $dup?->id,
                'match_note'   => Str::limit('Linked by ' . Auth::user()->name . ' on ' . now()->format('d M') . ($dup ? '; same booking as request #' . $dup->id : ''), 250),
            ]);
            $this->log($refund, 'Refund linked to booking', ['booking_id' => $booking->id]);
            return back()->with('success', 'Request #' . $refund->id . ' linked to booking #' . $booking->id . ' (' . ($booking->listing->name ?? '') . ').');
        }
        $refund->update(['booking_id' => null, 'match' => 'verify', 'duplicate_of' => null, 'match_note' => Str::limit('Match cleared by ' . Auth::user()->name . ' on ' . now()->format('d M'), 250)]);
        return back()->with('success', 'Request #' . $refund->id . ' unlinked.');
    }

    private function manage(): void
    {
        if (!admin_can('refunds.manage')) {
            abort(403);
        }
    }

    /** System-wide trail. A logging failure must never undo or block the action itself. */
    private function log(?RefundRequest $r, string $title, array $data): void
    {
        try {
            DataLog::create([
                'related_id' => $r?->booking_id ?: ($r?->id ?: 0),
                'title'      => $title,
                'data'       => json_encode($data + ['request_id' => $r?->id, 'by' => Auth::user()?->name]),
                'status'     => 1,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
