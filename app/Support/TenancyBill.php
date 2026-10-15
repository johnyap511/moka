<?php

namespace App\Support;

use DateTimeImmutable;
use DateTimeZone;

/**
 * The tenancy settlement calculation, server side. Mirrors the calculator
 * Finance used before (Moka-Tenancy-Bill-Calculator), so the page preview and
 * the saved statement agree:
 *
 *  - a utility bill is divided by the days in its billing period (both ends
 *    included) and charged for the days the tenant occupied that unit within
 *    the period; each bill is measured against its own unit's dates only;
 *  - fixed charges are quantity × rate, never split by days;
 *  - Indah Water counts the calendar months the stay touched;
 *  - every line is rounded to the sen, so the lines add up to the total.
 */
class TenancyBill
{
    private const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    private const LETTERS = 'ABCDEFGHIJKLMNOP';

    /** @return array{data: array, flags: string[]} */
    public static function compute(array $in): array
    {
        $inclusive = (bool) ($in['inclusive'] ?? true);
        $units     = array_values($in['units'] ?? []);
        $multi     = count($units) > 1;

        $flags = [];
        $totalStayDays = 0;
        $spanStart = $spanEnd = null;
        $billTotal = 0.0;
        $outUnits = [];
        $unitNames = [];

        foreach ($units as $u => $unit) {
            $letter  = self::LETTERS[$u] ?? '+';
            $rawName = trim((string) ($unit['name'] ?? ''));
            $name    = $rawName !== '' ? $rawName : 'Unit ' . $letter;
            if ($rawName !== '') {
                $unitNames[] = $rawName;
            }

            $uin     = self::day($unit['from'] ?? null);
            $uoutRaw = self::day($unit['to'] ?? null);
            $occEnd  = $uoutRaw === null ? null : ($inclusive ? $uoutRaw : $uoutRaw - 1);

            $stayDays = 0;
            if ($uin !== null && $occEnd !== null) {
                $stayDays = $occEnd - $uin + 1;
                if ($stayDays < 1) {
                    $stayDays = 0;
                    $flags[] = $name . ': the dates for this unit run backwards.';
                }
            }
            if ($stayDays) {
                $totalStayDays += $stayDays;
                $spanStart = $spanStart === null ? $uin : min($spanStart, $uin);
                $spanEnd   = $spanEnd === null ? $uoutRaw : max($spanEnd, $uoutRaw);
            }

            $lines = [];
            $unitTotal = 0.0;
            $hasBill = false;
            foreach (($unit['bills'] ?? []) as $bill) {
                $label = trim((string) ($bill['label'] ?? '')) ?: 'Unnamed bill';
                $amt   = self::num($bill['amount'] ?? null);
                if (!$amt) {
                    continue;
                }
                $hasBill = true;
                $bs = self::day($bill['start'] ?? null);
                $be = self::day($bill['end'] ?? null);
                if ($bs === null || $be === null) {
                    $flags[] = $name . ' – ' . $label . ': amount keyed in, but the billing period is missing.';
                    $lines[] = ['label' => $label, 'period' => '', 'basis' => 'Billing period not keyed in', 'calc' => 'billing period not keyed in', 'amt' => 0.0, 'bad' => true];
                    continue;
                }
                if ($be < $bs) {
                    $flags[] = $name . ' – ' . $label . ': the billing period runs backwards.';
                    $lines[] = ['label' => $label, 'period' => 'Bill ' . self::range($bs, $be), 'basis' => 'Period end is before period start', 'calc' => 'period runs backwards', 'amt' => 0.0, 'bad' => true];
                    continue;
                }
                if ($uin === null || $occEnd === null || !$stayDays) {
                    $lines[] = ['label' => $label, 'period' => 'Bill ' . self::range($bs, $be), 'basis' => "Waiting on this unit's dates", 'calc' => "waiting on this unit's dates", 'amt' => 0.0, 'bad' => true];
                    continue;
                }
                $periodDays = $be - $bs + 1;
                $oStart = max($bs, $uin);
                $oEnd   = min($be, $occEnd);
                $overlap = $oEnd >= $oStart ? $oEnd - $oStart + 1 : 0;
                $daily = $amt / $periodDays;
                $share = round($daily * $overlap, 2);
                $unitTotal += $share;
                if ($overlap === 0) {
                    $flags[] = $name . ' – ' . $label . ": the billing period doesn't overlap this unit's dates.";
                    $lines[] = ['label' => $label, 'period' => 'Bill ' . self::range($bs, $be), 'basis' => "No overlap with this unit's dates", 'calc' => "no overlap with this unit's dates", 'amt' => 0.0, 'bad' => true];
                } else {
                    $basis = $overlap . ' of ' . $periodDays . ' days @ RM ' . number_format($daily, 4, '.', '') . '/day';
                    $lines[] = [
                        'label' => $label, 'period' => 'Bill ' . self::range($bs, $be), 'basis' => $basis,
                        'calc'  => $overlap . ' of ' . $periodDays . ' days · RM ' . number_format($daily, 4, '.', '') . '/day · ' . self::short($oStart) . ' → ' . self::short($oEnd),
                        'amt'   => $share, 'bad' => false,
                    ];
                }
            }
            $billTotal += $unitTotal;
            if ($hasBill) {
                $meta = $stayDays ? self::short($uin) . ' → ' . self::short($uoutRaw) . ' · ' . $stayDays . ($inclusive ? ' days' : ' nights') : 'dates not set';
                $outUnits[] = ['letter' => $letter, 'name' => $name, 'meta' => $meta, 'lines' => $lines, 'total' => round($unitTotal, 2)];
            }
        }
        $billTotal = round($billTotal, 2);

        // Fixed charges
        $months = self::monthSpan($spanStart, $spanEnd, $totalStayDays > 0);
        $addons = [];
        $addTotal = 0.0;
        foreach (($in['addons'] ?? []) as $a) {
            $qty = self::num($a['qty'] ?? null);
            if (($a['auto'] ?? '') === 'months' && empty($a['touched'])) {
                $qty = (float) $months['count'];
            }
            $label = trim((string) ($a['label'] ?? '')) ?: 'Unnamed charge';
            $rate  = self::num($a['rate'] ?? null);
            $amt   = round($qty * $rate, 2);
            if (!$amt) {
                continue;
            }
            $addTotal += $amt;
            $unitWord = strtolower(trim((string) ($a['qtyLab'] ?? '')));
            $calc = self::qty($qty) . ' × ' . self::money($rate) . ($unitWord === 'months' ? ' a month' : ' each');
            $addons[] = ['label' => $label, 'calc' => $calc, 'period' => '', 'basis' => $calc, 'amt' => $amt, 'qty' => $qty, 'rate' => $rate];
        }
        $addTotal = round($addTotal, 2);

        $arrears = round(self::num($in['arrears'] ?? null), 2);
        $deposit = round(self::num($in['deposit'] ?? null), 2);
        $total   = round($billTotal + $addTotal + $arrears, 2);
        $balance = $deposit > 0 ? round($deposit - $total, 2) : null;

        $data = [
            'tenant'     => trim((string) ($in['tenant'] ?? '')),
            'ref'        => trim((string) ($in['bookref'] ?? '')),
            'preparedBy' => trim((string) ($in['preparedBy'] ?? '')),
            'property'   => trim((string) ($in['property'] ?? '')),
            'remarks'    => trim((string) ($in['remarks'] ?? '')),
            'unitNames'  => $unitNames,
            'when'       => $totalStayDays ? self::long($spanStart) . ' – ' . self::long($spanEnd) : '',
            'stayFrom'   => $spanStart === null ? null : self::iso($spanStart),
            'stayTo'     => $spanEnd === null ? null : self::iso($spanEnd),
            'stayDays'   => $totalStayDays,
            'inclusive'  => $inclusive,
            'unitCount'  => count($units),
            'units'      => $outUnits,
            'billTotal'  => $billTotal,
            'addons'     => $addons,
            'addTotal'   => $addTotal,
            'arrears'    => $arrears,
            'total'      => $total,
            'deposit'    => $deposit,
            'balance'    => $balance,
            'months'     => $months,
        ];
        return ['data' => $data, 'flags' => $flags];
    }

    /** Why a statement cannot be issued yet, or null when it can. */
    public static function blocker(array $in, array $computed): ?string
    {
        if (trim((string) ($in['tenant'] ?? '')) === '') {
            return 'Key in the tenant name first.';
        }
        $d = $computed['data'];
        if (!$d['units'] && !$d['addons'] && !$d['arrears']) {
            return 'Nothing to put on a statement yet. Key in a bill or a charge first.';
        }
        foreach ($d['units'] as $u) {
            foreach ($u['lines'] as $l) {
                if ($l['bad']) {
                    return 'Fix the orange warnings first: ' . $u['name'] . ' – ' . $l['label'] . ' (' . $l['basis'] . ').';
                }
            }
        }
        if ($computed['flags']) {
            return 'Fix the warnings first: ' . $computed['flags'][0];
        }
        return null;
    }

    public static function money(float $n): string
    {
        $neg = $n < -0.004;
        return ($neg ? '−RM ' : 'RM ') . number_format(abs($n), 2);
    }

    // ---- helpers: dates are day numbers (days since epoch, UTC) so arithmetic is exact ----

    private static function day(?string $iso): ?int
    {
        if (!$iso || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $iso)) {
            return null;
        }
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $iso, new DateTimeZone('UTC'));
        if (!$d || $d->format('Y-m-d') !== $iso) {
            return null;
        }
        return intdiv($d->getTimestamp(), 86400);
    }

    private static function dt(int $day): DateTimeImmutable
    {
        return (new DateTimeImmutable('@' . ($day * 86400)))->setTimezone(new DateTimeZone('UTC'));
    }

    private static function iso(int $day): string
    {
        return self::dt($day)->format('Y-m-d');
    }

    private static function long(int $day): string
    {
        $d = self::dt($day);
        return (int) $d->format('j') . ' ' . self::MONTHS[(int) $d->format('n') - 1] . ' ' . $d->format('Y');
    }

    private static function short(int $day): string
    {
        $d = self::dt($day);
        return (int) $d->format('j') . ' ' . self::MONTHS[(int) $d->format('n') - 1];
    }

    private static function range(int $a, int $b): string
    {
        $da = self::dt($a);
        $db = self::dt($b);
        $left = $da->format('Y') === $db->format('Y') ? self::short($a) : self::long($a);
        return $left . ' – ' . self::long($b);
    }

    private static function num($v): float
    {
        if ($v === null || $v === '') {
            return 0.0;
        }
        return is_numeric($v) ? (float) $v : 0.0;
    }

    private static function qty(float $q): string
    {
        return floor($q) == $q ? (string) (int) $q : rtrim(rtrim(number_format($q, 2, '.', ''), '0'), '.');
    }

    /** @return array{count:int, text:string} */
    private static function monthSpan(?int $a, ?int $b, bool $ok): array
    {
        if ($a === null || $b === null || !$ok) {
            return ['count' => 0, 'text' => ''];
        }
        $s = self::dt($a);
        $e = self::dt($b);
        $sy = (int) $s->format('Y');
        $sm = (int) $s->format('n') - 1;
        $ey = (int) $e->format('Y');
        $em = (int) $e->format('n') - 1;
        $count = ($ey - $sy) * 12 + ($em - $sm) + 1;
        if ($count < 1) {
            return ['count' => 0, 'text' => ''];
        }
        $names = [];
        for ($k = 0; $k < $count && $k < 4; $k++) {
            $m = ($sm + $k) % 12;
            $y = $sy + intdiv($sm + $k, 12);
            $names[] = self::MONTHS[$m] . ' ' . $y;
        }
        $text = $count > 4 ? implode(', ', array_slice($names, 0, 2)) . ' … ' . self::MONTHS[$em] . ' ' . $ey : implode(', ', $names);
        return ['count' => $count, 'text' => $text];
    }
}
