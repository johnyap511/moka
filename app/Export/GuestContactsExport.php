<?php

namespace App\Export;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

/** The one guest newsletter list: every guest who asked for our news, whichever form they used. Every cell as text. */
class GuestContactsExport extends DefaultValueBinder implements FromArray, WithHeadings, WithCustomValueBinder
{
    public function __construct(private Collection $rows) {}

    public function headings(): array
    {
        return ['Name', 'Email', 'Phone', 'Building', 'Last check-out', 'Last stay', 'Joined from', 'Added on'];
    }

    public function array(): array
    {
        return $this->rows->map(fn ($r) => [$r['name'], $r['email'], $r['phone'], $r['building'], $r['last_out'], $r['stay'], $r['source'], $r['joined']])->all();
    }

    public function bindValue(Cell $cell, $value)
    {
        $cell->setValueExplicit($value === null ? '' : (string) $value, DataType::TYPE_STRING);
        return true;
    }
}
