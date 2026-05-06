<?php

namespace App\Export;

use App\Booking;
use App\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use \Maatwebsite\Excel\Sheet;

class CalendarExport implements WithEvents, FromArray, ShouldAutoSize
{
    use RegistersEventListeners;

    public function __construct(int $week)
    {
        $this->week = $week;
    }

    public function array(): array
    {
        $week=$this->week;
        $weekDay = date('w');
        $start = Carbon::now()->modify('-'.$weekDay.' days');
        $dif = 6 - $weekDay + (($week-1)*7);
        $end = Carbon::now()->modify('+'.$dif.' days');

        $exportData[0]= ['',''];
        $exportData[1]= ['',''];
        while ($start <= $end){
            $exportData[0][]= date_format($start, 'F');
            $exportData[0][]= "";
            $exportData[1][]= date_format($start, 'D dS');
            $exportData[1][]= "";
            $start->modify('+1 day');
        }

        return $exportData;
    }


    public static function beforeSheet(BeforeSheet $event)
    {
        $event->sheet->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);

//        $event->sheet->styleCells(
//            'B2:G8',
//            [
//                'borders' => [
//                    'outline' => [
//                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
//                        'color' => ['argb' => 'FFFF0000'],
//                    ],
//                ]
//            ]
//        );
//        $event->sheet->mergeCells('C1:D1');

    }


    public static function afterSheet(AfterSheet $event)
    {

//        $event->sheet->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);

//        $event->sheet->styleCells(
//            'B2:G8',
//            [
//                'borders' => [
//                    'outline' => [
//                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
//                        'color' => ['argb' => 'FFFF0000'],
//                    ],
//                ]
//            ]
//        );
        $event->sheet->mergeCells('C1:D1');

    }




}