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
            'received' => RefundRequest::where('created_at', '>=', today())->count(),
        ];

        $rows = collect();
        if ($tab !== 'feedback') {
            $rows = $this->filtered($tab, $q, $prop, $from, $to)->paginate(40)->withQueryString();
            if (admin_can('refunds.manage') || admin_can('refunds.deduct')) {
                foreach ($rows as $r) {
                    $r->suggestions = ($r->status === 'pending' && $r->match !== 'matched') ? Refunds::suggestions($r) : [];
                }
            }
            // Other requests claiming the same booking: one deposit per booking, so Finance sees both sides.
            $byBooking = RefundRequest::whereIn('booking_id', $rows->pluck('booking_id')->filter()->unique())->get(['id', 'booking_id', 'name', 'status', 'created_at', 'paid_at'])->groupBy('booking_id');
            foreach ($rows as $r) {
                $r->siblings = $r->booking_id ? ($byBooking->get($r->booking_id, collect())->where('id', '!=', $r->id)->values()) : collect();
            }
        }

        return view('admin.refunds.index', [
            'tab' => $tab, 'q' => $q, 'prop' => $prop, 'from' => $from, 'to' => $to, 'ym' => $ym, 'rows' => $rows, 'counts' => $counts,
            'feedback'   => $tab === 'feedback' ? $this->feedback($ym) : null,
            'link'       => Refunds::currentLink(),
            'properties' => array_keys(Refunds::PROPERTIES),
            'canManage'  => admin_can('refunds.manage'),
            'canDeduct'  => admin_can('refunds.deduct'),
            'paidTotal'  => $tab === 'paid' ? (clone $this->filtered('paid', $q, $prop, $from, $to))->sum('amount_paid') : null,
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
        $query = RefundRequest::with(['booking.listing', 'booking.user', 'staff', 'payer', 'deductor', 'receiver']);
        // Pending work is ordered by the 3-working-day target: most urgent first. Paid by payment date.
        if ($tab === 'paid') {
            $query->orderByDesc('paid_at')->orderByDesc('id');
        } elseif (in_array($tab, ['pending', 'verify'], true)) {
            $query->orderBy('due_on')->orderBy('id');
        } else {
            $query->orderByDesc('id');
        }
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
    public function export(Request $request)
    {
        $this->manage();
        $tab  = (string) $request->input('tab', 'pending');
        [$from, $to] = $this->range($request);
        $rows = $this->filtered($tab === 'feedback' ? 'all' : $tab, trim((string) $request->input('q', '')), (string) $request->input('property', ''), $from, $to)->get();
        $this->log(null, 'Refund CSV exported', ['tab' => $tab, 'from' => $from, 'to' => $to, 'rows' => $rows->count()]);

        $name = 'deposit-refunds-' . $tab . ($from || $to ? '-' . ($from ?: 'start') . '-to-' . ($to ?: 'today') : '') . '-' . now()->format('Ymd-Hi') . '.xlsx';
        // Excel file rather than CSV: Excel turned units like "11-19" into dates and dropped leading zeros (Sakinah, 14 Oct 2026).
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Export\RefundRequestsExport($rows), $name);
    }

    /**
     * Guest contact list for email and message blasts (Sam, 12 Oct 2026): one row
     * per guest who ticked the consent box, newest stay first, no bank details.
     */
    public function contacts()
    {
        if (!admin_can('refunds.view')) {
            abort(403);
        }
        $rows = RefundRequest::where('marketing_opt_in', true)->orderByDesc('id')->get()
            ->unique(fn ($r) => strtolower((string) $r->email) . '|' . $r->phone_digits)->values();
        $this->log(null, 'Refund contacts exported', ['rows' => $rows->count()]);

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Export\RefundContactsExport($rows), 'moka-guest-contacts-' . now()->format('Ymd') . '.xlsx');
    }

    /** A fresh 48-hour link, on request. */
    /** The guest's uploaded Alipay / Touch 'n Go QR code; bank details are for refunds.manage only. */
    public function qr(RefundRequest $refund)
    {
        abort_unless(admin_can('refunds.manage'), 403);
        abort_unless($refund->qr_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($refund->qr_path), 404);
        return response()->file(\Illuminate\Support\Facades\Storage::disk('local')->path($refund->qr_path));
    }

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
        $request->validate(['reference' => 'nullable|string|max:120', 'amount_paid' => 'nullable|numeric|min:0|max:100000']);
        if ($refund->status === 'paid') {
            return back()->with('error', 'Request #' . $refund->id . ' is already marked paid.');
        }
        $amount = $request->filled('amount_paid') ? round((float) $request->input('amount_paid'), 2) : $refund->refundDue();
        $refund->update(['status' => 'paid', 'paid_at' => now(), 'paid_by' => Auth::id(), 'reference' => $request->input('reference') ?: null, 'amount_paid' => $amount]);
        $this->log($refund, 'Refund paid', ['reference' => $refund->reference, 'amount_paid' => $amount]);
        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'id' => $refund->id, 'message' => 'Request #' . $refund->id . ' marked paid.', 'counts' => $this->counts()]);
        }
        return back()->with('success', 'Request #' . $refund->id . ' marked paid.');
    }

    /**
     * One bulk endpoint for the tick column (Sam, 13 Oct 2026): action = paid (Finance,
     * confirmed-received rows only), received (Finance), nodeduct (Operations or Finance:
     * records "checked, nothing to deduct" so Finance can pay the full deposit).
     */
    public function bulk(Request $request)
    {
        $request->validate(['action' => 'required|in:paid,received,nodeduct', 'ids' => 'required|array|min:1|max:200', 'ids.*' => 'integer', 'reference' => 'nullable|string|max:120']);
        $action = $request->input('action');
        if ($action === 'paid') {
            return $this->bulkPaid($request);
        }
        if ($action === 'received') {
            $this->manage();
        } else {
            $this->manageOrDeduct();
        }
        $rows = RefundRequest::whereIn('id', $request->input('ids'))->where('status', 'pending')->get();
        $done = []; $skipped = 0;
        foreach ($rows as $r) {
            if ($action === 'received') {
                if ($r->received_at) { $skipped++; continue; }
                $r->update(['received_at' => now(), 'received_by' => Auth::id()]);
            } else {
                if ($r->deduction_at || ($r->opsCutoffPassed() && !admin_can('refunds.manage'))) { $skipped++; continue; }
                $r->update(['deduction_amount' => 0, 'deduction_reason' => null, 'deduction_by' => Auth::id(), 'deduction_at' => now()]);
            }
            $done[] = $r->id;
        }
        $this->log(null, $action === 'received' ? 'Refund deposits confirmed received in bulk' : 'Refunds checked, no deduction, in bulk', ['ids' => $done]);
        $what = $action === 'received' ? 'confirmed received' : 'checked, no deduction';
        $msg = count($done) . ' request' . (count($done) === 1 ? '' : 's') . ' ' . $what . ($skipped ? '; ' . $skipped . ' skipped (already done, not pending, or past the cut-off)' : '') . '.';
        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'action' => $action, 'ids' => $done, 'message' => $msg, 'counts' => $this->counts(), 'by' => Auth::user()->name, 'at' => now()->format('d M H:i')]);
        }
        return back()->with('success', $msg);
    }

    /** Tick several pending requests and mark them paid in one go (Sam, 13 Oct 2026). */
    public function bulkPaid(Request $request)
    {
        $this->manage();
        $request->validate(['ids' => 'required|array|min:1|max:200', 'ids.*' => 'integer', 'reference' => 'nullable|string|max:120']);
        $ref = $request->input('reference') ?: null;
        $rows = RefundRequest::whereIn('id', $request->input('ids'))->where('status', 'pending')->get();
        $done = []; $unreceived = 0;
        foreach ($rows as $r) {
            if (!$r->received_at) {
                $unreceived++;
                continue;
            }
            $r->update(['status' => 'paid', 'paid_at' => now(), 'paid_by' => Auth::id(), 'reference' => $ref, 'amount_paid' => $r->refundDue()]);
            $done[] = $r->id;
        }
        $this->log(null, 'Refunds paid in bulk', ['ids' => $done, 'reference' => $ref]);
        $skipped = count($request->input('ids')) - count($done);
        $skipped -= $unreceived;
        $msg = count($done) . ' request' . (count($done) === 1 ? '' : 's') . ' marked paid' . ($ref ? ' (ref ' . $ref . ')' : '') . ($unreceived ? '; ' . $unreceived . ' skipped: deposit not confirmed received' : '') . ($skipped ? '; ' . $skipped . ' skipped, not pending' : '') . '.';
        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'ids' => $done, 'message' => $msg, 'counts' => $this->counts()]);
        }
        return back()->with('success', $msg);
    }

    private function counts(): array
    {
        $pending = RefundRequest::where('status', 'pending');
        $month = RefundRequest::where('status', 'paid')->where('paid_at', '>=', now()->subDays(30))->get(['paid_at', 'due_on']);
        $paidMonth = $month->count();
        $onTime = $month->filter(fn ($r) => $r->due_on && $r->paid_at && $r->paid_at->lte($r->due_on->copy()->endOfDay()))->count();
        return [
            'pending'  => (clone $pending)->count(),
            'verify'   => (clone $pending)->where('match', '!=', 'matched')->count(),
            'overdue'  => (clone $pending)->where('due_on', '<', today())->count(),
            'today'    => (clone $pending)->where('due_on', today())->count(),
            'ready'    => (clone $pending)->whereNotNull('received_at')->where(fn ($q) => $q->whereNotNull('deduction_at')->orWhere('ops_cutoff_at', '<', now()))->count(),
            'paid'     => RefundRequest::where('status', 'paid')->count(),
            'paid30'   => $paidMonth,
            'ontime30' => $paidMonth ? (int) round($onTime / $paidMonth * 100) : null,
        ];
    }

    public function reject(Request $request, RefundRequest $refund)
    {
        $this->manage();
        $request->validate(['note' => 'required|string|max:255']);
        $refund->update(['status' => 'rejected', 'note' => $request->input('note')]);
        Refunds::settleClaims($refund->booking_id);
        $this->log($refund, 'Refund rejected', ['note' => $refund->note]);
        return back()->with('success', 'Request #' . $refund->id . ' rejected. The record is kept.');
    }

    public function reopen(RefundRequest $refund)
    {
        $this->manage();
        $refund->update(['status' => 'pending', 'paid_at' => null, 'paid_by' => null]);
        Refunds::settleClaims($refund->booking_id);
        $this->log($refund, 'Refund reopened', []);
        return back()->with('success', 'Request #' . $refund->id . ' is pending again.');
    }

    /** Finance ties a "verify" request to the booking they found, or clears a wrong match. */
    public function attach(Request $request, RefundRequest $refund)
    {
        $this->manageOrDeduct();
        $request->validate(['booking_id' => 'nullable|string|max:40']);
        $id = trim((string) $request->input('booking_id'));
        if ($id !== '') {
            $booking = Refunds::resolveReference($id, $refund);
            if (!$booking) {
                return back()->with('error', '"' . $id . '" did not resolve to a booking. Use the booking number, the eZee folio (FN4525) or the reservation (RES4465).');
            }
            $booking->load('listing');
            $dup = RefundRequest::where('booking_id', $booking->id)->where('id', '!=', $refund->id)->where('status', '!=', 'rejected')->orderBy('id')->first();
            $old = $refund->booking_id;
            $refund->update([
                'booking_id'   => $booking->id,
                'match'        => $dup ? 'duplicate' : 'matched',
                'duplicate_of' => $dup?->id,
                'match_note'   => Str::limit('Linked by ' . Auth::user()->name . ' on ' . now()->format('d M') . ($dup ? '; same booking as request #' . $dup->id : ''), 250),
            ]);
            if ($old && $old !== $booking->id) {
                Refunds::settleClaims($old);
            }
            $this->log($refund, 'Refund linked to booking', ['booking_id' => $booking->id, 'duplicate_of' => $dup?->id]);
            if ($dup) {
                return back()->with('error', 'Request #' . $refund->id . ' linked to booking #' . $booking->id . ' (' . ($booking->listing->name ?? '') . '), but request #' . $dup->id . ' (' . $dup->name . ') already claims this booking, so it is flagged Duplicate. One deposit per booking: reject one of the two.');
            }
            return back()->with('success', 'Request #' . $refund->id . ' matched to booking #' . $booking->id . ' (' . ($booking->listing->name ?? '') . ').');
        }
        $old = $refund->booking_id;
        $refund->update(['booking_id' => null, 'match' => 'verify', 'duplicate_of' => null, 'match_note' => Str::limit('Match cleared by ' . Auth::user()->name . ' on ' . now()->format('d M'), 250)]);
        Refunds::settleClaims($old);
        return back()->with('success', 'Request #' . $refund->id . ' unlinked.');
    }

    /** Finance confirms the deposit was received (bank statement / SQL accounting) before paying. Click again to undo. */
    public function received(Request $request, RefundRequest $refund)
    {
        $this->manage();
        if ($refund->received_at) {
            $refund->update(['received_at' => null, 'received_by' => null]);
            $msg = 'Request #' . $refund->id . ': deposit receipt unconfirmed.';
        } else {
            $refund->update(['received_at' => now(), 'received_by' => Auth::id()]);
            $msg = 'Request #' . $refund->id . ': deposit confirmed received.';
        }
        $this->log($refund, 'Refund deposit receipt ' . ($refund->received_at ? 'confirmed' : 'unconfirmed'), []);
        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'id' => $refund->id, 'received' => (bool) $refund->received_at, 'by' => Auth::user()->name, 'at' => now()->format('d M H:i'), 'message' => $msg]);
        }
        return back()->with('success', $msg);
    }

    /** Operation Team records the deposit held and any deduction with its reason, so Finance knows what to refund. */
    public function deduct(Request $request, RefundRequest $refund)
    {
        if (!admin_can('refunds.deduct')) {
            abort(403);
        }
        if ($refund->status === 'paid') {
            return back()->with('error', 'Request #' . $refund->id . ' is already paid; reopen it first.');
        }
        $in = $request->validate([
            'deposit_amount'   => 'nullable|numeric|min:0|max:100000',
            'deduction_amount' => 'nullable|numeric|min:0|max:100000',
            'deduction_reason' => 'nullable|string|max:255',
        ]);
        $deduction = round((float) ($in['deduction_amount'] ?? 0), 2);
        if ($deduction > 0 && blank($in['deduction_reason'] ?? null)) {
            return back()->with('error', 'Give the reason for the deduction on request #' . $refund->id . '.');
        }
        $deposit = $request->filled('deposit_amount') ? round((float) $in['deposit_amount'], 2) : $refund->deposit_amount;
        if ($deposit !== null && $deduction > $deposit) {
            return back()->with('error', 'The deduction (RM ' . number_format($deduction, 2) . ') is more than the deposit (RM ' . number_format($deposit, 2) . ') on request #' . $refund->id . '.');
        }
        $refund->update([
            'deposit_amount' => $deposit, 'deduction_amount' => $deduction, 'deduction_reason' => $deduction > 0 ? $in['deduction_reason'] : null,
            'deduction_by' => Auth::id(), 'deduction_at' => now(),
        ]);
        $this->log($refund, 'Refund deposit / deduction recorded', ['deposit' => $deposit, 'deduction' => $deduction, 'reason' => $refund->deduction_reason]);
        $due = $refund->refundDue();
        return back()->with('success', 'Request #' . $refund->id . ': deposit ' . ($deposit === null ? 'not set' : 'RM ' . number_format($deposit, 2)) . ', deduction RM ' . number_format($deduction, 2) . ($due !== null ? ', refund RM ' . number_format($due, 2) : '') . '.');
    }

    /** Split a request naming two units into a second request for the other unit (same guest, same bank details). */
    public function split(Request $request, RefundRequest $refund)
    {
        $this->manageOrDeduct();
        $request->validate(['unit_no' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9 \\/.-]{2,40}$/']]);
        $new = Refunds::split($refund, $request->input('unit_no'));
        $this->log($new, 'Refund split', ['from' => $refund->id, 'unit' => $new->unit_no, 'match' => $new->match, 'booking_id' => $new->booking_id]);
        return back()->with('success', 'Request #' . $new->id . ' created for unit ' . $new->unit_no . ' (' . ($new->match === 'matched' ? 'matched to booking #' . $new->booking_id : 'needs verifying') . '), grouped with #' . $refund->id . '.');
    }

    /** Identifying the booking (Link, Pick, Split) is open to Operations too; paying is not. */
    private function manageOrDeduct(): void
    {
        if (!admin_can('refunds.manage') && !admin_can('refunds.deduct')) {
            abort(403);
        }
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
