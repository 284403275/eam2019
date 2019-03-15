<?php


namespace App\SAP\Excel\Exports;


use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WeeklyPvAResultsExport implements WithEvents, WithTitle
{
    use Exportable, RegistersEventListeners;

    protected static $report;

    protected static $year;

    protected static $ww;

    public function __construct(Collection $report, $year, $ww)
    {
        self::$report = $report;
        self::$year = $year;
        self::$ww = $ww;
    }

    public static function afterSheet(AfterSheet $event)
    {
        $sheet = $event->getSheet()->getDelegate();

        $headingCell = ['A1'];

        $merges = [
            'A1:P2',
            'B3:E3', // Water
            'G3:J3', // Electrical
            'L3:O3', // CMT
            'B16:E16', // IC
            'G16:J16', // FMT
            'L16:O16', // All
            'B9:C10', 'B11:C12',
            'G9:H10', 'G11:H12',
            'L9:M10', 'L11:M12',
            'B22:C23', 'B24:C25',
            'G22:H23', 'G24:H25',
            'L22:M23', 'L24:M25',

            'D9:D10', 'D11:D12', 'E9:E10', 'E11:E12',
            'I9:I10', 'I11:I12', 'J9:J10', 'J11:J12',
            'N9:N10', 'N11:N12', 'O9:O10', 'O11:O12',

            'D22:D23', 'D24:D25', 'E22:E23', 'E24:E25',
            'I22:I23', 'I24:I25', 'J22:J23', 'J24:J25',
            'N22:N23', 'N24:N25', 'O22:O23', 'O24:O25',
        ];

        self::mergeCells($merges, $sheet);

        $titleCells = ['B3', 'G3', 'L3', 'B16', 'G16', 'L16'];

        $headingFormatting = [
            'font' => [
                'bold' => true,
                'size' => 26
            ],
        ];

        $titleFormatting = [
            'font' => [
                'bold' => true,
                'size' => 18
            ]
        ];

        $subFormatting = [
            'font' => [
                'bold' => true,
                'size' => 11
            ]
        ];

        $detailFormatting = [
            'font' => [
                'bold' => false,
                'size' => 11
            ]
        ];

        $sheet->getCell('A1')->setValue('Work Week ' . self::$ww . ' Review');

        $sheet->getCell('B3')->setValue('Water');

        $sheet->getCell('G3')->setValue('Electrical');

        $sheet->getCell('L3')->setValue('CMT');

        $sheet->getCell('B16')->setValue('IC');

        $sheet->getCell('G16')->setValue('FMT');

        $sheet->getCell('L16')->setValue('PvA');


        $planned = ['C4', 'H4', 'M4', 'C17', 'H17', 'M17'];
        $actual = ['D4', 'I4', 'N4', 'D17', 'I17', 'N17'];
        $growth = ['E4', 'J4', 'O4', 'E17', 'J17', 'O17'];

        $taskCells = ['B5', 'G5', 'L5', 'B18', 'G18', 'L18', 'D8', 'I8', 'N8', 'D21', 'I21', 'N21'];
        $workCells = ['B6', 'G6', 'L6', 'B19', 'G19', 'L19', 'E8', 'J8', 'O8', 'E21', 'J21', 'O21'];

        $pvaCells = ['B9', 'G9', 'L9', 'B22', 'G22', 'L22'];
        $growthCells = ['B11', 'G11', 'L11', 'B24', 'G24', 'L24'];
        $valueCells = [
            'D9', 'D11', 'E9', 'E11',
            'I9', 'I11', 'J9', 'J11',
            'N9', 'N11', 'O9', 'O11',
            'D22', 'D24', 'E22', 'E24',
            'I22', 'I24', 'J22', 'J24',
            'N22', 'N24', 'O22', 'O24'
        ];

        $detailCells = [
            'C5','C6','D5','D6','E5','E6','H5',
            'H6','I5','I6','J5','J6','M5','M6',
            'N5','N6','O5','O6','C18','C19','D18',
            'D19','E18','E19','H18','H19','I18','I19','J18','J19',
        ];

        $subs = array_merge($planned, $actual, $growth, $taskCells, $workCells);

        self::setTextValues($planned, $sheet, 'Planned');
        self::setTextValues($actual, $sheet, 'Actual');
        self::setTextValues($growth, $sheet, 'Growth');
        self::setTextValues($taskCells, $sheet, 'Tasks');
        self::setTextValues($workCells, $sheet, 'Hours');
        self::setTextValues($pvaCells, $sheet, 'PvA');
        self::setTextValues($growthCells, $sheet, 'Growth');

        self::setCellFormatting($headingCell, $sheet, $headingFormatting, true);
        self::setCellFormatting($subs, $sheet, $subFormatting, true);
        self::setCellFormatting($titleCells, $sheet, $titleFormatting, true);
        self::setCellFormatting($detailCells, $sheet, $detailFormatting, true);
        self::setCellFormatting(array_merge($pvaCells, $growthCells, $valueCells), $sheet, $titleFormatting, true, true);

        self::setTextValues(['C5'], $sheet, self::$report['WTR_P']['planned_tasks']);
        self::setTextValues(['C6'], $sheet, self::$report['WTR_P']['planned_work']);
        self::setTextValues(['D5'], $sheet, self::$report['WTR_P']['planned_completed']);
        self::setTextValues(['D6'], $sheet, self::$report['WTR_P']['work_recorded_on_planned']);
        self::setTextValues(['E5'], $sheet, self::$report['WTR_P']['task_growth']);
        self::setTextValues(['E6'], $sheet, self::$report['WTR_P']['work_done_on_growth']);
        self::setTextValues(['H5'], $sheet, self::$report['ELEC_P']['planned_tasks']);
        self::setTextValues(['H6'], $sheet, self::$report['ELEC_P']['planned_work']);
        self::setTextValues(['I5'], $sheet, self::$report['ELEC_P']['planned_completed']);
        self::setTextValues(['I6'], $sheet, self::$report['ELEC_P']['work_recorded_on_planned']);
        self::setTextValues(['J5'], $sheet, self::$report['ELEC_P']['task_growth']);
        self::setTextValues(['J6'], $sheet, self::$report['ELEC_P']['work_done_on_growth']);
        self::setTextValues(['M5'], $sheet, self::$report['MIC_P']['planned_tasks']);
        self::setTextValues(['M6'], $sheet, self::$report['MIC_P']['planned_work']);
        self::setTextValues(['N5'], $sheet, self::$report['MIC_P']['planned_completed']);
        self::setTextValues(['N6'], $sheet, self::$report['MIC_P']['work_recorded_on_planned']);
        self::setTextValues(['O5'], $sheet, self::$report['MIC_P']['task_growth']);
        self::setTextValues(['O6'], $sheet, self::$report['MIC_P']['work_done_on_growth']);
        self::setTextValues(['C18'], $sheet, self::$report['IC']['planned_tasks']);
        self::setTextValues(['C19'], $sheet, self::$report['IC']['planned_work']);
        self::setTextValues(['D18'], $sheet, self::$report['IC']['planned_completed']);
        self::setTextValues(['D19'], $sheet, self::$report['IC']['work_recorded_on_planned']);
        self::setTextValues(['E18'], $sheet, self::$report['IC']['task_growth']);
        self::setTextValues(['E19'], $sheet, self::$report['IC']['work_done_on_growth']);
        self::setTextValues(['H18'], $sheet, self::$report['FMG_P']['planned_tasks']);
        self::setTextValues(['H19'], $sheet, self::$report['FMG_P']['planned_work']);
        self::setTextValues(['I18'], $sheet, self::$report['FMG_P']['planned_completed']);
        self::setTextValues(['I19'], $sheet, self::$report['FMG_P']['work_recorded_on_planned']);
        self::setTextValues(['J18'], $sheet, self::$report['FMG_P']['task_growth']);
        self::setTextValues(['J19'], $sheet, self::$report['FMG_P']['work_done_on_growth']);

        self::setTextValues(['D9'], $sheet, self::taskPva('WTR_P'));
        self::setTextValues(['E9'], $sheet, self::hoursPva('WTR_P'));
        self::setTextValues(['D11'], $sheet, self::taskGrowth('WTR_P'));
        self::setTextValues(['E11'], $sheet, self::hoursGrowth('WTR_P'));

        self::setTextValues(['I9'], $sheet, self::taskPva('ELEC_P'));
        self::setTextValues(['J9'], $sheet, self::hoursPva('ELEC_P'));
        self::setTextValues(['I11'], $sheet, self::taskGrowth('ELEC_P'));
        self::setTextValues(['J11'], $sheet, self::hoursGrowth('ELEC_P'));

        self::setTextValues(['N9'], $sheet, self::taskPva('MIC_P'));
        self::setTextValues(['O9'], $sheet, self::hoursPva('MIC_P'));
        self::setTextValues(['N11'], $sheet, self::taskGrowth('MIC_P'));
        self::setTextValues(['O11'], $sheet, self::hoursGrowth('MIC_P'));

        self::setTextValues(['D22'], $sheet, self::taskPva('IC'));
        self::setTextValues(['E22'], $sheet, self::hoursPva('IC'));
        self::setTextValues(['D24'], $sheet, self::taskGrowth('IC'));
        self::setTextValues(['E24'], $sheet, self::hoursGrowth('IC'));

        self::setTextValues(['I22'], $sheet, self::taskPva('FMG_P'));
        self::setTextValues(['J22'], $sheet, self::hoursPva('FMG_P'));
        self::setTextValues(['I24'], $sheet, self::taskGrowth('FMG_P'));
        self::setTextValues(['J24'], $sheet, self::hoursGrowth('FMG_P'));

        $report = self::$report->values();

        self::setTextValues(['M18'], $sheet, $report->sum('planned_tasks'));
        self::setTextValues(['M19'], $sheet, $report->sum('planned_work'));
        self::setTextValues(['N18'], $sheet, $report->sum('planned_completed'));
        self::setTextValues(['N19'], $sheet, $report->sum('work_recorded_on_planned'));
        self::setTextValues(['O18'], $sheet, $report->sum('task_growth'));
        self::setTextValues(['O19'], $sheet, $report->sum('work_done_on_growth'));

        self::setTextValues(['N22'], $sheet, round($report->sum('planned_completed') / $report->sum('planned_tasks') * 100) . '%');
        self::setTextValues(['O22'], $sheet, round($report->sum('work_recorded_on_planned') / $report->sum('planned_work') * 100) . '%');
        self::setTextValues(['N24'], $sheet, round($report->sum('task_growth') / $report->sum('planned_tasks') * 100) . '%');
        self::setTextValues(['O24'], $sheet, round($report->sum('work_done_on_growth') / $report->sum('planned_work') * 100) . '%');

        $sheet->setSelectedCell('A1');
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Summary';
    }

    protected static function taskPva($wc)
    {
        return round(self::$report[$wc]['planned_completed'] / self::$report[$wc]['planned_tasks'] * 100) . '%';
    }

    protected static function taskGrowth($wc)
    {
        return round(self::$report[$wc]['task_growth'] / self::$report[$wc]['planned_tasks'] * 100) . '%';
    }

    protected static function hoursPva($wc)
    {
        return round(self::$report[$wc]['work_recorded_on_planned'] / self::$report[$wc]['planned_work'] * 100) . '%';
    }

    protected static function hoursGrowth($wc)
    {
        return round(self::$report[$wc]['work_done_on_growth'] / self::$report[$wc]['planned_work'] * 100) . '%';
    }

    protected static function setTextValues(array $cells, Worksheet $sheet, $string)
    {
        foreach ($cells as $cell) {
            $sheet->getCell($cell)->setValue($string);
        }
    }

    protected static function setCellFormatting(array $cells, Worksheet $sheet, array $formatting, $center = false, $vcenter = false)
    {
        foreach ($cells as $cell) {
            $temp = $sheet->getStyle($cell)->applyFromArray($formatting);

            if ($center) {
                $temp->getAlignment()->setHorizontal('center');
            }

            if ($vcenter) {
                $temp->getAlignment()->setVertical('center');
            }
        }
    }

    protected static function mergeCells(array $cells, Worksheet $sheet)
    {
        foreach ($cells as $cell) {
            $sheet->mergeCells($cell);
        }
    }
}