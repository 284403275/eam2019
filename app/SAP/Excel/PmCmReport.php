<?php


namespace App\SAP\Excel;


use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class PmCmReport implements FromCollection, WithHeadings, ShouldAutoSize, WithStrictNullComparison, WithTitle, WithMapping, Responsable, WithEvents
{

    use Exportable, RegistersEventListeners;

    private $fileName = 'PmCmRatioReport.xlsx';

    public $collection;

    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        return $this->collection;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'System',
            'Description',
            'Tag',
            'Description',
            'Type',
            'PM',
            'CM',
            'Ratio',
            'PM Hours',
            'CM Hours',
            'W Ratio'
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'PM-CM Ratio';
    }

    /**
     * @param mixed $row
     *
     * @return array
     */
    public function map($row): array
    {
        return [
            $row['system'],
            $row['floc_description'],
            $row['tag'],
            $row['eq_description'],
            $row['eq_type'],
            $row['pm'],
            $row['cm'],
            $row['ratio'],
            $row['pm_work'],
            $row['cm_work'],
            $row['work_ratio'],
        ];
    }

    public static function afterSheet(AfterSheet $event)
    {
        $event->getSheet()->getDelegate()->setAutoFilter('A1:K1');

        $event->getSheet()->getDelegate()->freezePane('A2');

        $event->sheet->getDelegate()->getStyle('A1:K1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12
            ],
        ]);
    }
}