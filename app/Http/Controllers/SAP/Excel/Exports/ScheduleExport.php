<?php


namespace App\SAP\Excel\Exports;


use App\Pas;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ScheduleExport implements ShouldAutoSize, FromQuery, WithHeadings, WithMapping, WithColumnFormatting, WithTitle
{
    use Exportable;

    public $ww;

    public $year;

    public function __construct($ww, $year)
    {
        $this->ww = $ww;

        $this->year = $year;
    }

    /**
     * @return Builder
     */
    public function query()
    {
        return Pas::ww($this->ww, $this->year);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Maintenance Plan',
            'Description',
            'Maintenance Item',
            'Call Number',
            'Order Number',
            'Order Type',
            'Operation Number',
            'Operation Work Center',
            'Operation Description',
            'Scheduled For',
            'Planned Work',
            'Planned Capacity',
            'Actual Work Done'
        ];
    }

    /**
     * @param mixed $row
     *
     * @return array
     */
    public function map($row): array
    {
        return [
            $row->maintenance_plan,
            $row->maintenance_item,
            $row->item,
            $row->call_number,
            $row->order,
            $row->order_type,
            $row->op_number,
            $row->op_work_center,
            $row->op_description,
            Date::dateTimeToExcel(Carbon::parse($row->scheduled_for)),
            $row->planned_work,
            $row->planned_capacity,
            $row->actual_work
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_NUMBER,
            'J' => NumberFormat::FORMAT_DATE_XLSX22,
            'K' => NumberFormat::FORMAT_NUMBER,
            'L' => NumberFormat::FORMAT_NUMBER,
            'M' => NumberFormat::FORMAT_NUMBER,
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'WW' . $this->ww . ' Planned';
    }
}