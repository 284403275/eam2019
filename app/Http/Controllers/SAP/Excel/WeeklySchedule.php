<?php


namespace App\SAP\Excel;


use App\SAP\Excel\Exports\ScheduleExport;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class WeeklySchedule implements WithMultipleSheets
{
    use Exportable;

    protected $ww;

    protected $year;

    public function __construct(int $ww, $year)
    {
        $this->ww = $ww;

        $this->year = $year;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets[] = new ScheduleExport($this->ww, $this->year);
        //$sheets[] = new ActuallyCompletedExport();

        return $sheets;
    }
}