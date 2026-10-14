<?php

namespace App\Export;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Deposit refund requests as a real Excel file. Every cell is written as text,
 * so a unit like "11-19" stays "11-19" instead of becoming November 2019, and
 * an account number keeps its leading zeros (Sakinah, 14 Oct 2026, on the CSV).
 * Amount columns are written as numbers so Finance can sum them.
 */
class RefundRequestsExport extends DefaultValueBinder implements FromArray, WithHeadings, WithCustomValueBinder, WithColumnWidths, WithStyles
{
    private const NUMERIC = ['Deposit', 'Deduction', 'Refund due', 'Amount paid', 'Cleanliness', 'Service', 'Staff rating'];

    public function __construct(private Collection $rows) {}

    public function headings(): array
    {
        // Order per Finance (Sakinah, 14 Oct 2026): bank and contact right after the stay, workflow columns at the end.
        return ['Request', 'Group', 'Submitted', 'Status', 'Due', 'Guest', 'Property', 'Unit', 'Check-out', 'Account holder', 'Account number', 'Bank', 'Phone', 'Email', 'Booking', 'Match', 'Note', 'Finance review', 'Paid on', 'Paid by', 'Amount paid', 'Reference', 'Cleanliness', 'Service', 'Team member', 'Staff rating', 'Deposit', 'Deduction', 'Deduction reason', 'Ops cut-off', 'Refund due', 'Deposit received'];
    }

    public function array(): array
    {
        return $this->rows->map(fn ($r) => [
            $r->id, $r->group_key ? $r->group_index . ' of ' . $r->group_size : '', $r->created_at->format('Y-m-d H:i'), $r->status, $r->due_on?->format('Y-m-d'),
            $r->name, $r->property, $r->unit_no, $r->check_out->format('Y-m-d'),
            $r->bank_holder, $r->bank_account, $r->bank_name, $r->phone, $r->email,
            $r->booking_id, $r->match, $r->match_note, '',
            $r->paid_at?->format('Y-m-d'), $r->payer->name ?? '', $r->amount_paid, $r->reference,
            $r->rating_clean, $r->rating_service, $r->staff ? trim($r->staff->name . ' ' . $r->staff->last_name) : '', $r->staff_rating,
            $r->deposit_amount, $r->deduction_amount > 0 ? $r->deduction_amount : null, $r->deduction_reason, $r->ops_cutoff_at?->format('Y-m-d H:i'), $r->refundDue(),
            $r->received_at ? $r->received_at->format('Y-m-d') . ' ' . ($r->receiver->name ?? '') : '',
        ])->all();
    }

    /** Text everywhere except the amount and rating columns. */
    public function bindValue(Cell $cell, $value)
    {
        $col = $cell->getColumn();
        $heading = $this->headings()[\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($col) - 1] ?? '';
        if ($cell->getRow() > 1 && in_array($heading, self::NUMERIC, true) && $value !== null && $value !== '') {
            $cell->setValueExplicit((float) $value, DataType::TYPE_NUMERIC);
            return true;
        }
        $cell->setValueExplicit($value === null ? '' : (string) $value, DataType::TYPE_STRING);
        return true;
    }

    public function columnWidths(): array
    {
        return ['A' => 9, 'B' => 8, 'C' => 16, 'D' => 9, 'E' => 11, 'F' => 26, 'G' => 20, 'H' => 12, 'I' => 11, 'J' => 26, 'K' => 18, 'L' => 16, 'M' => 15, 'N' => 26, 'O' => 9, 'P' => 10, 'Q' => 40, 'R' => 24, 'S' => 11, 'T' => 14, 'U' => 11, 'V' => 14, 'W' => 10, 'X' => 8, 'Y' => 16, 'Z' => 10, 'AA' => 9, 'AB' => 10, 'AC' => 24, 'AD' => 16, 'AE' => 10, 'AF' => 22];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        return [1 => ['font' => ['bold' => true]]];
    }
}
