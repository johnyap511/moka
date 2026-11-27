<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Listing;
use App\Models\RefundRequest;
use App\Support\PortfolioAnalytics;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sales > Newsletter lists (Sam, 27 Nov 2026): the only two lists that may be loaded into the
 * email service. Owners with a live unit, and guests who ticked the offers box on the refund
 * form. Nobody else: older guest emails carry no consent record. The email service keeps the
 * unsubscribes, so a fresh download each month only adds new people.
 */
class NewsletterListController extends Controller
{
    public function index()
    {
        abort_unless(admin_can('settings.view'), 403);

        [$owners, $skipped] = $this->owners();
        $guests = RefundRequest::where('marketing_opt_in', true)->get(['email', 'created_at']);

        return view('admin.sales.lists', [
            'owners'      => $owners->count(),
            'ownerUnits'  => $owners->sum('units'),
            'skipped'     => $skipped,
            'guests'      => $guests->map(fn ($r) => strtolower(trim((string) $r->email)))->filter()->unique()->count(),
            'guestsMonth' => $guests->where('created_at', '>=', now()->subDays(30))->map(fn ($r) => strtolower(trim((string) $r->email)))->unique()->count(),
        ]);
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
