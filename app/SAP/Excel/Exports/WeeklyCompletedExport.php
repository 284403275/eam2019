<?php


namespace App\SAP\Excel\Exports;


use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class WeeklyCompletedExport implements ShouldAutoSize, FromCollection, WithHeadings, WithMapping, WithColumnFormatting, WithTitle, WithEvents
{

    use Exportable, RegistersEventListeners;

    public $results;

    public $ww;

    public function __construct(Collection $results, $ww)
    {
        $this->results = $results;
        $this->ww = $ww;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Work Center',
            'Order Number',
            'Order Type',
            'Operation Number',
            'Operation Description',
            'Maintenance Plan',
            'Maintenance Item',
            'Completed',
            'Work Recorded',
            'Work Planned',
            'Delta'
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
            $row['work_center'],
            $row['order'],
            $row['order_type'],
            $row['operation'],
            $row['description'],
            $row['maintenance_plan'],
            $row['maintenance_item'],
            $row['completed'],
            $row['work_recorded'],
            $row['planned_work'],
            $row['work_recorded'] - $row['planned_work'],
        ];
    }

    /**
     * @return array
     */
    public function columnFormats(): array
    {
        return [
            'F' => NumberFormat::FORMAT_NUMBER,
            'I' => NumberFormat::FORMAT_NUMBER,
            'J' => NumberFormat::FORMAT_NUMBER,
            'K' => NumberFormat::FORMAT_NUMBER,
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'WW ' . $this->ww . ' Review';
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        return $this->results;
    }

    public static function afterSheet(AfterSheet $event)
    {
        $event->getSheet()->getDelegate()->setAutoFilter('A1:K1');

        $event->getSheet()->getDelegate()->freezePane('A2');

        $event->sheet->getDelegate()->getStyle('A1:K1')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
        ]);
    }
}