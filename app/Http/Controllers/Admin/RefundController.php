<?php

namespace App\Http\Controllers\Admin;

use App\Booking;
use App\DataLog;
use App\Http\Controllers\Controller;
use App\Models\RefundRequest;
use App\Support\Refunds;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Finance side of the deposit refund portal: requests beside their booking,
 * bank details for refunds.manage only, mark paid, CSV for the payment run,
 * and the guest feedback tab (team KPI counts verified stays only).
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
        if (!admin_can('refunds.view')) {
            $tab = 'feedback';
        }
        $q    = trim((string) $request->input('q', ''));
        $prop = (string) $request->input('property', '');
        [$from, $to] = $this->range($request);
        $ym   = preg_match('/^\d{4}-\d{2}$/', (string) $request->input('month')) ? $request->input('month') : now()->format('Y-m');

        $counts = $this->counts() + [
            'rejected' => RefundRequest::where('status', 'rejected')->count(),
            'today'    => RefundRequest::where('created_at', '>=', today())->count(),
        ];

        $rows = collect();
        if ($tab !== 'feedback') {
            $rows = $this->filtered($tab, $q, $prop, $from, $to)->paginate(40)->withQueryString();
            if (admin_can('refunds.manage')) {
                foreach ($rows as $r) {
                    $r->suggestions = ($r->status === 'pending' && $r->match !== 'matched') ? Refunds::suggestions($r) : [];
                }
            }
        }

        return view('admin.refunds.index', [
            'tab' => $tab, 'q' => $q, 'prop' => $prop, 'from' => $from, 'to' => $to, 'ym' => $ym, 'rows' => $rows, 'counts' => $counts,
            'feedback'   => $tab === 'feedback' ? $this->feedback($ym) : null,
            'link'       => Refunds::currentLink(),
            'properties' => array_keys(Refunds::PROPERTIES),
            'canManage'  => admin_can('refunds.manage'),
        ]);
    }

    /** Optional date range (Y-m-d). On the Paid tab it applies to the paid date, elsewhere to the submission date. */
    private function range(Request $request): array
    {
        $ok = fn ($v) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v) ? $v : '';
        return [$ok($request->input('from')), $ok($request->input('to'))];
    }

    private function filtered(string $tab, string $q, string $prop, string $from = '', string $to = '')
    {
        $query = RefundRequest::with(['booking.listing', 'booking.user', 'staff', 'payer'])->orderByDesc('id');
        $dateCol = $tab === 'paid' ? 'paid_at' : 'created_at';
        if ($from !== '') {
            $query->where($dateCol, '>=', $from . ' 00:00:00');
        }
        if ($to !== '') {
            $query->where($dateCol, '<=', $to . ' 23:59:59');
        }
        if (in_array($tab, ['pending', 'paid', 'rejected'], true)) {
            $query->where('status', $tab);
        }
        if ($tab === 'verify') {
            $query->where('status', 'pending')->where('match', '!=', 'matched');
        }
        if ($prop !== '' && isset(Refunds::PROPERTIES[$prop])) {
            $query->where('property', $prop);
        }
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $d = Refunds::digits($q);
            $query->where(function ($w) use ($q, $like, $d) {
                $w->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('unit_no', 'like', $like)->orWhere('reference', 'like', $like);
                if ($d !== '') {
                    $w->orWhere('phone_digits', 'like', '%' . $d . '%');
                }
                if (ctype_digit($q)) {
                    $w->orWhere('id', (int) $q)->orWhere('booking_id', (int) $q);
                }
            });
        }
        return $query;
    }

    private function feedback(string $ym): array
    {
        $from = Carbon::createFromFormat('Y-m', $ym)->startOfMonth();
        $to   = $from->copy()->addMonth();
        $all  = RefundRequest::with('staff')->where('created_at', '>=', $from)->where('created_at', '<', $to)->orderByDesc('id')->get();

        // KPI counts only requests tied to a real stay, so nobody can pad a
        // colleague's score (or sink it) with made-up submissions.
        $verified   = $all->filter(fn ($r) => $r->booking_id && $r->match !== 'duplicate');
        $unverified = $all->count() - $verified->count();

        $staff = $verified->whereNotNull('staff_user_id')->groupBy('staff_user_id')->map(function ($g) {
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

        $properties = $verified->groupBy('property')->map(fn ($g) => [
            'count'   => $g->count(),
            'clean'   => $g->whereNotNull('rating_clean')->count() ? round($g->avg('rating_clean'), 2) : null,
            'service' => $g->whereNotNull('rating_service')->count() ? round($g->avg('rating_service'), 2) : null,
        ])->sortByDesc('count');

        return [
            'ym'         => $ym,
            'month'      => $from->format('F Y'),
            'prev'       => $from->copy()->subMonth()->format('Y-m'),
            'next'       => $from->copy()->addMonth()->format('Y-m'),
            'total'      => $all->count(),
            'verified'   => $verified->count(),
            'unverified' => $unverified,
            'clean'      => $verified->whereNotNull('rating_clean')->count() ? round($verified->avg('rating_clean'), 2) : null,
            'service'    => $verified->whereNotNull('rating_service')->count() ? round($verified->avg('rating_service'), 2) : null,
            'staff'      => $staff,
            'properties' => $properties,
            'comments'   => $all->filter(fn ($r) => filled($r->comments))->values(),
        ];
    }

    /** CSV of the current list, for the bank payment run. Finance only: it carries account numbers. */
    public function export(Request $request): StreamedResponse
    {
        $this->manage();
        $tab  = (string) $request->input('tab', 'pending');
        [$from, $to] = $this->range($request);
        $rows = $this->filtered($tab === 'feedback' ? 'all' : $tab, trim((string) $request->input('q', '')), (string) $request->input('property', ''), $from, $to)->get();
        $this->log(null, 'Refund CSV exported', ['tab' => $tab, 'from' => $from, 'to' => $to, 'rows' => $rows->count()]);

        $name = 'deposit-refunds-' . $tab . ($from || $to ? '-' . ($from ?: 'start') . '-to-' . ($to ?: 'today') : '') . '-' . now()->format('Ymd-Hi') . '.csv';
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            // Column order follows Najihah's working sheet (12 Oct 2026); "Finance review" is left blank for her notes.
            fputcsv($out, ['Request', 'Submitted', 'Status', 'Due', 'Guest', 'Property', 'Unit', 'Check-out', 'Account holder', 'Account number', 'Bank', 'Phone', 'Email', 'Booking', 'Match', 'Note', 'Finance review', 'Paid on', 'Paid by', 'Reference', 'Cleanliness', 'Service', 'Team member', 'Staff rating']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->id, $r->created_at->format('Y-m-d H:i'), $r->status, $r->due_on?->format('Y-m-d'),
                    $r->name, $r->property, $r->unit_no, $r->check_out->format('Y-m-d'),
                    $r->bank_holder, "\t" . $r->bank_account, $r->bank_name,   // tab keeps Excel from mangling long numbers
                    $r->phone, $r->email,
                    $r->booking_id, $r->match, $r->match_note, '',
                    $r->paid_at?->format('Y-m-d'), $r->payer->name ?? '', $r->reference,
                    $r->rating_clean, $r->rating_service, $r->staff ? trim($r->staff->name . ' ' . $r->staff->last_name) : '', $r->staff_rating,
                ]);
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Guest contact list for email and message blasts (Sam, 12 Oct 2026): one row
     * per guest who ticked the consent box, newest stay first, no bank details.
     */
    public function contacts(): StreamedResponse
    {
        if (!admin_can('refunds.view')) {
            abort(403);
        }
        $rows = RefundRequest::where('marketing_opt_in', true)->orderByDesc('id')->get()
            ->unique(fn ($r) => strtolower((string) $r->email) . '|' . $r->phone_digits)->values();
        $this->log(null, 'Refund contacts exported', ['rows' => $rows->count()]);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Email', 'Phone', 'Property', 'Unit', 'Last check-out', 'Consent given on']);
            foreach ($rows as $r) {
                fputcsv($out, [$r->name, $r->email, $r->phone, $r->property, $r->unit_no, $r->check_out->format('Y-m-d'), $r->created_at->format('Y-m-d')]);
            }
            fclose($out);
        }, 'moka-guest-contacts-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
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
        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'id' => $refund->id, 'message' => 'Request #' . $refund->id . ' marked paid.', 'counts' => $this->counts()]);
        }
        return back()->with('success', 'Request #' . $refund->id . ' marked paid.');
    }

    /** Tick several pending requests and mark them paid in one go (Sam, 13 Oct 2026). */
    public function bulkPaid(Request $request)
    {
        $this->manage();
        $request->validate(['ids' => 'required|array|min:1|max:200', 'ids.*' => 'integer', 'reference' => 'nullable|string|max:120']);
        $ref = $request->input('reference') ?: null;
        $rows = RefundRequest::whereIn('id', $request->input('ids'))->where('status', 'pending')->get();
        $done = [];
        foreach ($rows as $r) {
            $r->update(['status' => 'paid', 'paid_at' => now(), 'paid_by' => Auth::id(), 'reference' => $ref]);
            $done[] = $r->id;
        }
        $this->log(null, 'Refunds paid in bulk', ['ids' => $done, 'reference' => $ref]);
        $skipped = count($request->input('ids')) - count($done);
        $msg = count($done) . ' request' . (count($done) === 1 ? '' : 's') . ' marked paid' . ($ref ? ' (ref ' . $ref . ')' : '') . ($skipped ? '; ' . $skipped . ' skipped, not pending' : '') . '.';
        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'ids' => $done, 'message' => $msg, 'counts' => $this->counts()]);
        }
        return back()->with('success', $msg);
    }

    private function counts(): array
    {
        return [
            'pending' => RefundRequest::where('status', 'pending')->count(),
            'verify'  => RefundRequest::where('status', 'pending')->where('match', '!=', 'matched')->count(),
            'overdue' => RefundRequest::where('status', 'pending')->where('due_on', '<', today())->count(),
            'paid'    => RefundRequest::where('status', 'paid')->count(),
        ];
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
        $request->validate(['booking_id' => 'nullable|string|max:40']);
        $id = trim((string) $request->input('booking_id'));
        if ($id !== '') {
            $booking = Refunds::resolveReference($id, $refund);
            if (!$booking) {
                return back()->with('error', '"' . $id . '" did not resolve to a booking. Use the booking number, the eZee folio (FN4525) or the reservation (RES4465).');
            }
            $booking->load('listing');
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
