<?php

namespace App\Export;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

/** Consenting guests for email / message blasts, every cell as text (phones keep their leading zero). */
class RefundContactsExport extends DefaultValueBinder implements FromArray, WithHeadings, WithCustomValueBinder
{
    public function __construct(private Collection $rows) {}

    public function headings(): array
    {
        return ['Name', 'Email', 'Phone', 'Property', 'Unit', 'Last check-out', 'Consent given on'];
    }

    public function array(): array
    {
        return $this->rows->map(fn ($r) => [$r->name, $r->email, $r->phone, $r->property, $r->unit_no, $r->check_out->format('Y-m-d'), $r->created_at->format('Y-m-d')])->all();
    }

    public function bindValue(Cell $cell, $value)
    {
        $cell->setValueExplicit($value === null ? '' : (string) $value, DataType::TYPE_STRING);
        return true;
    }
}
