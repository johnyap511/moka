<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Listing;
use App\Models\RefundRequest;
use App\Support\PortfolioAnalytics;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Sales > Newsletter lists (Sam, 27 Nov 2026): one owner list and one guest list, the only two
 * that go into the email service. Owners: everyone with a live unit. Guests: everyone who asked
 * for our news, whichever form they used: the offers box on the refund form, or the sign-up box
 * on the website. The email service keeps the unsubscribes, so a fresh download each month only
 * adds new people.
 */
class NewsletterListController extends Controller
{
    public function index()
    {
        abort_unless(admin_can('settings.view'), 403);

        [$owners, $skipped] = $this->owners();
        $guests = $this->guests();

        return view('admin.sales.lists', [
            'owners'      => $owners->count(),
            'ownerUnits'  => $owners->sum('units'),
            'skipped'     => $skipped,
            'guests'      => $guests->count(),
            'guestsMonth' => $guests->whereIn('source', ['Refund form', 'Website sign-up'])->where('joined', '>=', now()->subDays(30)->toDateString())->count(),
            'fromRefund'  => $guests->where('source', 'Refund form')->count(),
            'fromWebsite' => $guests->where('source', 'Website sign-up')->count(),
            'fromEarlier' => $guests->whereNotIn('source', ['Refund form', 'Website sign-up'])->count(),
            'byStay'      => $guests->countBy('stay')->all(),
        ]);
    }

    public function guestsExport()
    {
        abort_unless(admin_can('settings.view'), 403);

        $guests = $this->guests();
        Log::info('Guest newsletter list exported', ['rows' => $guests->count(), 'by' => auth()->id()]);

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Export\GuestContactsExport($guests), 'moka-guest-contacts-' . now()->format('Ymd') . '.xlsx');
    }

    /** Website sign-ups count from this day: the 61 older ones (2020 to 2021) are not guests we know. */
    private const WEBSITE_SIGNUPS_FROM = '2026-11-27';

    /**
     * The one guest list, one row per email address. A guest who ticked the offers box on the
     * refund form (their latest request), then anyone who signed up on the website, then the
     * guests brought in from the old Google refund form (table newsletter_guests), each only if
     * not already there. A guest who asked to stop is left out. No bank details: only who they
     * are and where they last stayed. 'stay' says how long ago that was, for sending in stages.
     */
    private function guests(): Collection
    {
        $rows = [];
        foreach (RefundRequest::where('marketing_opt_in', true)->orderBy('id')->get() as $r) {
            $email = strtolower(trim((string) $r->email));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $rows[$email] = [
                'name'     => (string) $r->name,
                'email'    => $email,
                'phone'    => (string) $r->phone,
                'building' => (string) $r->property,
                'last_out' => $r->check_out ? $r->check_out->format('Y-m-d') : '',
                'source'   => 'Refund form',
                'joined'   => $rows[$email]['joined'] ?? $r->created_at->format('Y-m-d'),
            ];
        }
        foreach (DB::table('subscribe')->where('created_at', '>=', self::WEBSITE_SIGNUPS_FROM)->orderBy('id')->get(['email', 'created_at']) as $s) {
            $email = strtolower(trim((string) $s->email));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || isset($rows[$email])) {
                continue;
            }
            $rows[$email] = ['name' => '', 'email' => $email, 'phone' => '', 'building' => '', 'last_out' => '', 'source' => 'Website sign-up', 'joined' => substr((string) $s->created_at, 0, 10)];
        }
        $stopped = [];
        if (Schema::hasTable('newsletter_guests')) {
            foreach (DB::table('newsletter_guests')->orderByDesc('last_check_out')->get() as $g) {
                $email = strtolower(trim((string) $g->email));
                if ($g->unsubscribed_at) {
                    $stopped[$email] = true;
                    continue;
                }
                if (isset($rows[$email])) {
                    continue;
                }
                $rows[$email] = ['name' => (string) $g->name, 'email' => $email, 'phone' => (string) $g->phone, 'building' => (string) $g->building,
                    'last_out' => (string) $g->last_check_out, 'source' => (string) $g->source, 'joined' => substr((string) $g->created_at, 0, 10)];
            }
        }
        $today = now()->startOfDay();
        $stay  = function (string $date) use ($today): string {
            if ($date === '') {
                return 'Not known';
            }
            $months = Carbon::parse($date)->diffInDays($today) / 30.4;

            return $months <= 6 ? 'A: last 6 months' : ($months <= 12 ? 'B: 6 to 12 months' : ($months <= 24 ? 'C: 1 to 2 years' : ($months <= 36 ? 'D: 2 to 3 years' : 'E: over 3 years')));
        };

        return collect(array_values(array_diff_key($rows, $stopped)))->map(fn ($r) => $r + ['stay' => $stay($r['last_out'])])
            ->sortBy([['stay', 'asc'], ['last_out', 'desc']])->values();
    }

    public function ownersExport()
    {
        abort_unless(admin_can('settings.view'), 403);

        [$owners] = $this->owners();
        Log::info('Owner newsletter list exported', ['rows' => $owners->count(), 'by' => auth()->id()]);

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Export\OwnerContactsExport($owners), 'moka-owner-contacts-' . now()->format('Ymd') . '.xlsx');
    }

    /**
     * One row per email address: owners who have a unit that is not archived. Extra rooms and
     * company rooms are not an owner's unit. Rows without a usable address, and our own
     * @homemoka.com addresses, are left out and counted.
     * @return array{0: Collection, 1: array{no_email: int, ours: int}}
     */
    private function owners(): array
    {
        $byOwner = [];
        foreach (Listing::whereNotNull('user_id')->get(['name', 'user_id']) as $l) {
            if (stripos((string) $l->name, 'extra room') !== false || stripos((string) $l->name, 'company') !== false) {
                continue;
            }
            $byOwner[$l->user_id][] = PortfolioAnalytics::propertyOf((string) $l->name);
        }
        $rows = []; $skipped = ['no_email' => 0, 'ours' => 0];
        foreach (DB::table('users')->whereIn('id', array_keys($byOwner))->orderBy('name')->get(['id', 'name', 'last_name', 'email', 'phone', 'phone_e164']) as $u) {
            $email = strtolower(trim((string) $u->email));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped['no_email']++;
                continue;
            }
            if (str_ends_with($email, '@homemoka.com')) {
                $skipped['ours']++;
                continue;
            }
            $buildings = $byOwner[$u->id];
            if (isset($rows[$email])) {   // one person signed in twice: one row, units added together
                $buildings = array_merge($rows[$email]['_b'], $buildings);
            }
            $rows[$email] = [
                'name'      => $rows[$email]['name'] ?? trim($u->name . ' ' . ($u->last_name && stripos((string) $u->name, (string) $u->last_name) === false ? $u->last_name : '')),
                'email'     => $email,
                'phone'     => $rows[$email]['phone'] ?? (string) ($u->phone_e164 ?: $u->phone),
                'buildings' => implode(', ', array_keys(array_count_values($buildings))),
                'units'     => count($buildings),
                '_b'        => $buildings,
            ];
        }

        return [collect(array_values($rows))->map(fn ($r) => array_diff_key($r, ['_b' => 1])), $skipped];
    }
}
