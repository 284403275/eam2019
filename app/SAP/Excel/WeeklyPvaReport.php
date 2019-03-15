<?php


namespace App\SAP\Excel;


use App\SAP\Excel\Exports\GrowthExport;
use App\SAP\Excel\Exports\WeeklyCompletedExport;
use App\SAP\Excel\Exports\WeeklyPvAResultsExport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\BeforeWriting;

class WeeklyPvaReport implements WithMultipleSheets, WithEvents
{
    use Exportable, RegistersEventListeners;
    
    public $sheets = [];

    public $ww;

    public function __construct($ww)
    {
        $this->ww = $ww;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        return $this->sheets;
    }

    public function addGrowth($growth, $wc)
    {
        $this->sheets[] = new GrowthExport($growth, $wc);
    }

    public function addReview(Collection $review)
    {
        $this->sheets[] = new WeeklyCompletedExport($review, $this->ww);
    }

    public function addSummary(Collection $report, $year, $ww)
    {
        array_unshift($this->sheets, new WeeklyPvAResultsExport($report, $year, $ww));
    }

    public function registerEvents(): array
    {
        return [
            BeforeWriting::class => function(BeforeWriting $event) {
                $event->writer->setActiveSheetIndex(0);
            },
        ];
    }
}