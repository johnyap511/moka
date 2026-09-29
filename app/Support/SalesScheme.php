<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * The Front Desk Commission, Bonus & KPI SOP v2.0 (effective 1 Sep 2026), applied
 * to the recognised sales that SalesCommission collects from eZee.
 *
 *  §3  probation: nothing; first three complete months after confirmation: the
 *      RM15,000 minimum is waived; from the fourth month it applies.
 *  §6  2% of recognised direct-booking revenue (room charges before SST).
 *  §8  gates in order: minimum sales, attendance (1 absence = 50%, 2+ = nil),
 *      absence without approval = nil, lateness over 120 min = nil.
 *  §9  team bonus pool RM500 / 1,000 / 2,000 when at least 80% of counted staff
 *      reach RM15k / 20k / 30k; shared equally by eligible recipients.
 *  §11 70% paid with the following month's payroll, 30% deferred and paid after
 *      the year's audited accounts; the deferred balance restarts each calendar year.
 *  §12 a paid stay later cancelled or voided is clawed back from the next payout.
 */
class SalesScheme
{
    public const MIN_SALES   = 15000;
    public const TIERS       = [3 => [30000, 2000], 2 => [20000, 1000], 1 => [15000, 500]];
    public const THRESHOLD   = 0.8;
    public const PAY_NOW     = 0.7;
    public const GRACE_MIN   = 120;
    public const TRANSITION_MONTHS = 3;

    /** probation | transition | full | left, for a person in a month. */
    public static function status(?string $confirmedOn, ?string $leftOn, string $ym): string
    {
        if ($leftOn && substr($leftOn, 0, 7) < $ym) {
            return 'left';
        }
        if (!$confirmedOn || substr($confirmedOn, 0, 7) >= $ym) {
            return 'probation';
        }
        // The transition is the three complete months after the month confirmation took effect.
        $first = date('Y-m', strtotime(substr($confirmedOn, 0, 7) . '-01 +1 month'));
        $last  = date('Y-m', strtotime(substr($confirmedOn, 0, 7) . '-01 +' . self::TRANSITION_MONTHS . ' months'));

        return ($ym >= $first && $ym <= $last) ? 'transition' : 'full';
    }

    /**
     * Everyone's statement for a month: recognised sales, gates, personal commission,
     * team bonus, adjustments, the 70/30 split. Keyed by sales person name.
     */
    public static function month(string $ym): array
    {
        $rate    = SalesCommission::rate();
        $persons = DB::table('sales_persons')->orderBy('name')->get()->keyBy('name');
        $sales   = DB::table('sales_transactions')->selectRaw('sales_person, SUM(net_amount) net, COUNT(*) nights, COUNT(DISTINCT CONCAT(hotel_code, "|", folio_no)) stays')
            ->where('payable', 1)->whereBetween('tran_date', [$ym . '-01', date('Y-m-t', strtotime($ym . '-01'))])->groupBy('sales_person')->get()->keyBy('sales_person');
        $kpis    = DB::table('sales_kpis')->where('ym', $ym)->get()->keyBy('sales_person_id');
        $adj     = DB::table('sales_adjustments')->where('ym', $ym)->get()->groupBy('sales_person_id');

        $rows = [];
        foreach ($persons as $name => $p) {
            $k = $kpis[$p->id] ?? null;
            $s = $sales[$name] ?? null;
            $r = (object) [
                'id' => $p->id, 'name' => $name, 'status' => self::status($p->confirmed_on, $p->left_on, $ym),
                'confirmed_on' => $p->confirmed_on, 'left_on' => $p->left_on,
                'sales' => (float) ($s->net ?? 0), 'nights' => (int) ($s->nights ?? 0), 'stays' => (int) ($s->stays ?? 0),
                'kpi' => (object) ['employed_full_month' => $k ? (bool) $k->employed_full_month : true, 'absences' => (int) ($k->absences ?? 0),
                    'unapproved_absence' => $k ? (bool) $k->unapproved_absence : false, 'lateness_min' => (int) ($k->lateness_min ?? 0),
                    'disciplinary' => $k ? (bool) $k->disciplinary : false, 'note' => $k->note ?? null, 'entered' => (bool) $k],
                'gross' => 0.0, 'pct' => 0.0, 'personal' => 0.0, 'gates' => [], 'tier_reached' => 0,
                'bonus_eligible' => false, 'bonus' => 0.0, 'adjustments' => 0.0, 'adjustment_rows' => $adj[$p->id] ?? collect(),
                'total' => 0.0, 'pay_now' => 0.0, 'deferred' => 0.0,
            ];
            if (!$s && $r->status !== 'full' && $r->status !== 'transition') {
                // No sales and not in the scheme: nothing to show for this month.
            }
            $r->gross = round($r->sales * $rate, 2);
            foreach (self::TIERS as $tier => [$min]) {
                if ($r->sales >= $min) {
                    $r->tier_reached = $tier;
                    break;
                }
            }
            // §8.5 order of application.
            $minRequired = $r->status === 'full';
            $r->gates = [
                'eligible'   => in_array($r->status, ['full', 'transition'], true),
                'min_sales'  => !$minRequired || $r->sales >= self::MIN_SALES,
                'attendance' => $r->kpi->absences <= 1,
                'approved'   => !$r->kpi->unapproved_absence,
                'punctual'   => $r->kpi->lateness_min <= self::GRACE_MIN,
            ];
            $ok = !in_array(false, $r->gates, true);
            $r->pct = $ok ? ($r->kpi->absences === 1 ? 0.5 : 1.0) : 0.0;
            $r->personal = round($r->gross * $r->pct, 2);
            $rows[$name] = $r;
        }

        // §9 team bonus: counted staff are confirmed, employed the full month, past the transition.
        $counted = array_filter($rows, fn ($r) => $r->status === 'full' && $r->kpi->employed_full_month);
        $tier = 0;
        $pool = 0;
        if ($counted) {
            foreach (self::TIERS as $t => [$min, $amount]) {
                $hit = count(array_filter($counted, fn ($r) => $r->sales >= $min));
                if ($hit / count($counted) >= self::THRESHOLD) {
                    $tier = $t;
                    $pool = $amount;
                    break;
                }
            }
        }
        $eligible = [];
        if ($pool) {
            foreach ($rows as $name => $r) {
                $inScheme = in_array($r->status, ['full', 'transition'], true);
                $reached  = $r->status === 'transition' || $r->sales >= self::MIN_SALES;
                $r->bonus_eligible = $inScheme && $reached && $r->gates['attendance'] && $r->gates['approved'] && $r->gates['punctual'] && !$r->kpi->disciplinary;
                if ($r->bonus_eligible) {
                    $eligible[] = $name;
                }
            }
            $share = count($eligible) ? $pool / count($eligible) : 0;
            foreach ($eligible as $name) {
                $rows[$name]->bonus = round($share * ($rows[$name]->kpi->absences === 1 ? 0.5 : 1), 2);
            }
        }

        foreach ($rows as $r) {
            $r->adjustments = round((float) $r->adjustment_rows->sum('amount'), 2);
            $entitlement    = $r->personal + $r->bonus;
            $r->deferred    = round($entitlement * (1 - self::PAY_NOW), 2);
            $r->pay_now     = round($entitlement - $r->deferred + $r->adjustments, 2);
            $r->total       = round($entitlement + $r->adjustments, 2);
        }

        return ['rows' => $rows, 'tier' => $tier, 'pool' => $pool, 'counted' => count($counted), 'eligible' => count($eligible),
            'hit' => $tier ? count(array_filter($counted, fn ($r) => $r->sales >= self::TIERS[$tier][0])) : 0, 'rate' => $rate];
    }

    /** One person's month, or null when the name is unknown. */
    public static function statement(string $person, string $ym): ?array
    {
        $m = self::month($ym);
        if (!isset($m['rows'][$person])) {
            return null;
        }

        return ['row' => $m['rows'][$person], 'team' => $m];
    }

    /** Twelve months of statements for a person (or everyone summed), newest first. */
    public static function history(?string $person, int $months = 12): array
    {
        $out = [];
        for ($i = 0; $i < $months; $i++) {
            $ym = date('Y-m', strtotime(date('Y-m-01') . " -$i months"));
            $m  = self::month($ym);
            $rows = $person ? array_filter($m['rows'], fn ($r) => $r->name === $person) : $m['rows'];
            if (!$rows) {
                continue;
            }
            $sum = fn ($f) => round(array_sum(array_map(fn ($r) => $r->$f, $rows)), 2);
            $has = array_filter($rows, fn ($r) => $r->sales > 0 || $r->adjustments != 0);
            if (!$has && $ym !== date('Y-m')) {
                continue;
            }
            $one = $person ? reset($rows) : null;
            $out[] = (object) ['ym' => $ym, 'sales' => $sum('sales'), 'nights' => $sum('nights'), 'personal' => $sum('personal'), 'bonus' => $sum('bonus'),
                'adjustments' => $sum('adjustments'), 'pay_now' => $sum('pay_now'), 'deferred' => $sum('deferred'), 'total' => $sum('total'),
                'tier' => $m['tier'], 'pct' => $one ? $one->pct : null, 'status' => $one ? $one->status : null, 'gates' => $one ? $one->gates : null,
                'final' => SalesCommission::isFinal($ym), 'payout' => SalesCommission::payoutLabel($ym)];
        }

        return $out;
    }

    /** Deferred commission per calendar year for a person: accrued, paid out, outstanding. */
    public static function deferred(string $person, int $personId): array
    {
        $years = [];
        $first = DB::table('sales_transactions')->where('sales_person', $person)->min('tran_date');
        $from  = $first ? (int) substr($first, 0, 4) : (int) date('Y');
        for ($y = (int) date('Y'); $y >= $from; $y--) {
            $accrued = 0.0;
            $final   = 0.0;
            for ($mo = 1; $mo <= 12; $mo++) {
                $ym = sprintf('%d-%02d', $y, $mo);
                if ($ym > date('Y-m')) {
                    break;
                }
                $m = self::month($ym);
                if (isset($m['rows'][$person])) {
                    $accrued += $m['rows'][$person]->deferred;
                    if (SalesCommission::isFinal($ym)) {
                        $final += $m['rows'][$person]->deferred;
                    }
                }
            }
            $paid = (float) DB::table('sales_deferred_payouts')->where('sales_person_id', $personId)->where('year', $y)->sum('amount');
            $years[] = (object) ['year' => $y, 'accrued' => round($accrued, 2), 'final' => round($final, 2), 'paid' => round($paid, 2), 'outstanding' => round($final - $paid, 2),
                'payouts' => DB::table('sales_deferred_payouts')->where('sales_person_id', $personId)->where('year', $y)->orderBy('paid_on')->get()];
        }

        return $years;
    }

    /** Where the SOP PDF lives; staff read it from the page. */
    public static function sopPath(): string
    {
        return storage_path('app/private/commission-sop.pdf');
    }
}
