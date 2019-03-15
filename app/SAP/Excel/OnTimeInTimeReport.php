<?php


namespace App\SAP\Excel;


use App\SAP\Models\MaintenanceScore;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class OnTimeInTimeReport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents, WithStrictNullComparison, WithTitle, WithMapping, WithColumnFormatting
{
    use Exportable, RegistersEventListeners;

    public $start;

    public $finish;

    public $collection;

    protected $target;

    public function __construct(Carbon $start, Carbon $finish, Collection $collection)
    {
        $this->start = $start;

        $this->finish = $finish;

        $this->collection = $collection;

        $this->target = 80;
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        return $this->collection;
    }

    public function breakdown()
    {
        return $this->collection->groupBy('order')->transform(function ($item) {
            return [
                'order' => (int)ltrim($item->pluck('order')->first(), 0),
                'work_center' => transformWorkCenter($item->pluck('work_center')->first()),
                'in_time' => (int)$this->inTime($item),
                'on_time' => (int)$item->pluck('on_time')->first() === 1 ? 'Yes' : 'No',
                'planned_work' => round($item->sum('planned_work'), 2),
                'actual_work' => round($item->sum('actual_work'), 2),
                'total_capacity' => (int)$item->pluck('total_capacity')->first(),
                'warning_count' => (int)$this->warnings($item)->count(),
                'operations' => $item->count(),
                'warnings' => $this->warnings($item)
            ];
        })->values()->groupBy('work_center')->transform(function ($items) {
            return [
                'work_center' => $items->pluck('work_center')->first(),
                'total' => $items->count(),
                'on_time' => $items->where('on_time', 'Yes')->count(),
                'on_time_p' => round($items->where('on_time', 'Yes')->count() / $items->count() * 100, 2),
                'in_time' => $items->where('in_time', '>=', $this->target)->count(),
                'in_time_p' => round($items->where('in_time', '>=', $this->target)->count() / $items->count() * 100, 2),
                'flags' => $items->sum('warning_count')
            ];
        });
    }

    public static function afterSheet(AfterSheet $event)
    {
        $event->getSheet()->getDelegate()->setAutoFilter('A1:R1');

        $event->getSheet()->getDelegate()->freezePane('A2');

        $event->sheet->getDelegate()->getStyle('A1:R1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12
            ],
        ]);
    }

    public function inTime(Collection $orders)
    {
        $planned = $orders->sum('planned_work');
        $actual = $orders->sum('actual_work');

        if ($planned > 0 && $actual > 0)
            return round((1 - abs(($actual - $planned) / (($planned + (5 / $planned) + $actual)))) * 100, 2);
        return 0;
    }

    public function calcOrderInTime($order)
    {
        $orders = $this->collection->where('order', $order);

        $planned = $orders->sum('planned_work');
        $actual = $orders->sum('actual_work');

        if ($planned > 0 && $actual > 0)
            return round((1 - abs(($actual - $planned) / (($planned + (5 / $planned) + $actual)))) * 100, 2);
        return 0;
    }

    public function warnings(Collection $operations)
    {
        return $operations->map(function ($op) {
            if ((float)$op['in_time'] < $this->target)
                return [
                    'operation' => $op['op_number'],
                    'in_time' => (float)$op['in_time'],
                    'work_center' => $op['op_work_center'],
                    'actual_work' => (float)$op['actual_work'],
                    'planned_work' => (float)$op['planned_work'],
                    'planned_capacity' => (int)$op['planned_capacity'],
                    'actual_capacity' => (int)$op['actual_capacity']
                ];
        })->filter();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Order',
            'Operation',
            'Tag ID',
            'Description',
            'Work Center',
            'Operation WC',
            'Capacity',
            'Duration',
            'Planned Work',
            'Actual Capacity',
            'Actual Work',
            'Delta',
            'In Time',
            'Order In Time',
            'On Time',
            'Maintenance Plan',
            'Call',
            'Cycle'
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return $this->finish->format('Y') . '-' . $this->finish->format('W') . ' On Time - In Time';
    }

    /**
     * @param mixed $row
     *
     * @return array
     */
    public function map($row): array
    {
        return [
            $row['order'],
            $row['op_number'],
            $row['tag'],
            $row['description'],
            transformWorkCenter($row['work_center']),
            transformWorkCenter($row['op_work_center']),
            $row['planned_capacity'],
            $row['duration'],
            $row['planned_work'],
            $row['actual_capacity'],
            $row['actual_work'],
            $row['actual_work'] - $row['planned_work'],
            $row['in_time'],
            $this->calcOrderInTime($row['order']),
            $row['on_time'],
            $row['maintenance_plan'],
            $row['call_number'],
            $row['cycle'],
        ];
    }

    /**
     * @return array
     */
    public function columnFormats(): array
    {
        return [
            'P' => NumberFormat::FORMAT_NUMBER
        ];
    }
}