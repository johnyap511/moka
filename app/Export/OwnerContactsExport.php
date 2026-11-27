<?php

namespace App\Export;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

/** Owners with a live unit, for the owner newsletter; every cell as text (phones keep their leading zero). */
class OwnerContactsExport extends DefaultValueBinder implements FromArray, WithHeadings, WithCustomValueBinder
{
    public function __construct(private Collection $rows) {}

    public function headings(): array
    {
        return ['Name', 'Email', 'Phone', 'Buildings', 'Units'];
    }

    public function array(): array
    {
        return $this->rows->map(fn ($r) => [$r['name'], $r['email'], $r['phone'], $r['buildings'], $r['units']])->all();
    }

    public function bindValue(Cell $cell, $value)
    {
        $cell->setValueExplicit($value === null ? '' : (string) $value, DataType::TYPE_STRING);
        return true;
    }
}
