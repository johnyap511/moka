<?php

namespace App\Support;

/**
 * Option values for the listing forms.
 *
 * The utility codes are single letters because that is what the owner-report
 * calculation branches on (ListingController::reportImports and friends compare
 * $listing->water == "A" and so on). The labels here must stay aligned with
 * those branches — swapping two would silently change how owner payouts are
 * split.
 */
class ListingOptions
{
    /**
     * Rental categories, stored in listing_categories.category_id.
     *
     * The ids carry meaning: PaymentController enforces a one-month minimum
     * stay on 2 and a six-month minimum on 3.
     */
    public const CATEGORIES = [
        1 => 'Short Term Rental (daily/weekly)',
        2 => 'Medium Term Rental (3 months and above)',
        3 => 'Long Term Rental (1 year and above)',
    ];

    /**
     * Water, internet, electricity and MF+SF handling.
     *
     * How each behaves in the owner report:
     *   A  cost split between both parties by the profit share
     *   B  owner credited, Moka debited
     *   C  owner bears the full cost
     *   D  Moka bears the cost
     *   E  added to the owner only
     *   F  added to Moka only
     */
    public const UTILITY_OPTIONS = [
        'A' => 'Moka Pay, Profit Share with Owner',
        'B' => 'Owner Pay, Moka to Refund Owner',
        'C' => 'Owner Pay Only',
        'D' => 'Moka Pay Only',
        'E' => 'Add Owner Only',
        'F' => 'Add Moka Only',
    ];

    /**
     * Profit sharing, labelled owner:Moka.
     *
     * The stored value is Moka's share — the calculation reads
     * $hostProfit = $listing->profit and derives the owner's as 100 - that.
     */
    public const PROFIT_SHARING = [
        30 => '70:30',
        25 => '75:25',
        20 => '80:20',
        10 => '90:10',
    ];

    public const TOURISM_TAX_TYPES = [
        'fixed'      => 'Fixed (RM)',
        'percentage' => 'Percentage (%)',
    ];

    public const STATUSES = [
        1 => 'Active',
        0 => 'Inactive',
    ];
}
