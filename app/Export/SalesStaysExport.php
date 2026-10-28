<?php

namespace App\Export;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * The month's stay list behind the Sales Commission page, one row per stay (property +
 * folio), for managers to reconcile against their own workings (Sam, 28 Oct 2026).
 * Text cells everywhere except nights and amounts, so folios and units keep their form.
 */
class SalesStaysExport extends DefaultValueBinder implements FromArray, WithHeadings, WithCustomValueBinder, WithColumnWidths, WithStyles
{
    private const NUMERIC = ['Nights in month', 'Room charges RM', '2% RM'];

    /** @param iterable<object> $stays rows from SalesCommission::month()['stays'] */
    public function __construct(private iterable $stays, private string $ym) {}

    public function headings(): array
    {
        return ['Sales person', 'Guest', 'Source', 'RES', 'Folio', 'Property', 'Unit', 'Arrival', 'Departure', 'Nights in month', 'First night', 'Last night', 'Room charges RM', '2% RM', 'Status', 'Note'];
    }

    public function array(): array
    {
        $out = [];
        foreach ($this->stays as $s) {
            $notes = [];
            foreach ($s->other_months ?? [] as $om) {
                $notes[] = ($om->before ? 'Also ' : 'Then ') . $om->nights . ' night' . ($om->nights == 1 ? '' : 's') . ' in ' . Carbon::parse($om->ym . '-01')->format('M Y') . ': RM ' . number_format($om->commission, 2);
            }
            if (!empty($s->cross) && empty($s->other_months)) {
                $notes[] = $s->arrival && $s->arrival < $this->ym . '-01' ? 'Started before this month' : 'Continues into next month';
            }
            $out[] = [
                $s->sales_person, $s->guest, $s->source, $s->res_no ?: '', $s->folio_no, $s->property, $s->room,
                $s->arrival, $s->departure, (int) $s->nights, $s->first_night, $s->last_night,
                round((float) $s->net, 2), $s->warn ? null : round((float) $s->commission, 2),
                $s->warn ? 'On hold' : (!empty($s->cross) ? 'Cross-month' : ''),
                trim(($s->warn ? $s->warn . ' ' : '') . implode('; ', $notes)),
            ];
        }
        return $out;
    }

    public function bindValue(Cell $cell, $value)
    {
        $heading = $this->headings()[Coordinate::columnIndexFromString($cell->getColumn()) - 1] ?? '';
        if ($cell->getRow() > 1 && in_array($heading, self::NUMERIC, true) && $value !== null && $value !== '') {
            $cell->setValueExplicit((float) $value, DataType::TYPE_NUMERIC);
            return true;
        }
        $cell->setValueExplicit($value === null ? '' : (string) $value, DataType::TYPE_STRING);
        return true;
    }

    public function columnWidths(): array
    {
        $w = [14, 28, 14, 11, 10, 22, 16, 11, 11, 9, 11, 11, 14, 10, 12, 60];
        $out = [];
        foreach ($w as $i => $width) {
            $out[Coordinate::stringFromColumnIndex($i + 1)] = $width;
        }
        return $out;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        return [1 => ['font' => ['bold' => true]]];
    }
}
