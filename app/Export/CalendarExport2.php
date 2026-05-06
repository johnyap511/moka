<?php

namespace App\Export;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeExport;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeSheet;

class CalendarExport2 implements WithEvents
{

    public function __construct(int $week)
    {
        $this->week = $week;
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            // Handle by a closure.
//            BeforeExport::class => function(BeforeExport $event) {
//                $event->writer->getProperties()->setCreator('Patrick');
//            },

            // Array callable, refering to a static method.
            AfterSheet::class => [self::class, 'beforeWriting'],

        ];
    }

    public static function beforeWriting(AfterSheet $event)
    {
        $week=1;
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


}