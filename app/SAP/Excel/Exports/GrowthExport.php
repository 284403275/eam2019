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
use Maatwebsite\Excel\Events\BeforeExport;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class GrowthExport implements ShouldAutoSize, FromCollection, WithHeadings, WithMapping, WithColumnFormatting, WithTitle, WithEvents
{

    use Exportable, RegistersEventListeners;

    public $growth;

    public $wc;

    public function __construct(Collection $growth, $wc)
    {
        $this->growth = $growth;
        $this->wc = $wc;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Work Center',
            'Order Number',
            'Order Description',
            'Order Type',
            'Operation Number',
            'Operation Description',
            'Completed',
            'Work Recorded',
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
            $row['order_description'],
            $row['order_type'],
            $row['operation_num'],
            $row['operation_description'],
            $row['is_final'],
            $row['actual_work'],
        ];
    }

    /**
     * @return array
     */
    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_NUMBER,
            'H' => NumberFormat::FORMAT_NUMBER,
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return $this->wc . ' Growth';
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        return $this->growth;
    }

    public static function afterSheet(AfterSheet $event)
    {
        $event->getSheet()->getDelegate()->setAutoFilter('A1:H1');

        $event->getSheet()->getDelegate()->freezePane('A2');

        $event->sheet->getDelegate()->getStyle('A1:H1')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
        ]);
    }
}