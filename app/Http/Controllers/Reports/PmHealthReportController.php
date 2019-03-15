<?php


namespace App\Http\Controllers\Reports;


use App\Http\Controllers\Controller;
use App\Mail\WeeklyMaintenanceReport;
use App\SAP\Excel\OnTimeInTimeReport;
use App\SAP\Models\MaintenanceScore;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;


class PmHealthReportController extends Controller
{
    public function index($year = null, $ww = null)
    {
        if(!$year || !$ww || $ww > 54) {
            $week_start = new DateTime();
            $ww = (int)$week_start->format("W") - 1;

            $week_start->setISODate(Carbon::now()->format('Y'), $ww);
            $year = Carbon::now()->year;
        } else {
            $week_start = (new DateTime)->setISODate($year, $ww);
        }

        $start = Carbon::parse($week_start->format('Y-m-d 00:00:00'));//->subDay();
        $finish = $start->copy()->addDays(6)->endOfDay();

//        dd($start, $finish);

        $items = MaintenanceScore::between($start->format('Ymd'), $finish->format('Ymd'))->get();

        $report = new OnTimeInTimeReport($start, $finish, $items);

        return $report->breakdown();

//        Mail::to('christopher.carver@globalfoundries.com')->send(new WeeklyMaintenanceReport());

//        return 'Check mail';

        return Excel::download($report, $year . '-' . $ww .' On-Time In-Time Report.xlsx');

        //->order('000800370751')
        $scores = MaintenanceScore::between('20190113', '20190119')->get();

        $combined = $scores->groupBy('order')->transform(function($item) {
            return [
                'order' => (int) ltrim($item->pluck('order')->first(), 0),
                'work_center' => transformWorkCenter($item->pluck('work_center')->first()),
                'in_time' => $this->inTime($item),
                'on_time' => (int) $item->pluck('on_time')->first(),
                'planned_work' => round($item->sum('planned_work'), 2),
                'actual_work' => round($item->sum('actual_work'), 2),
                'total_capacity' => (int) $item->pluck('total_capacity')->first(),
                'warning_count' => $this->warnings($item)->count(),
                'operations' => $item->count(),
                //'warnings' => $this->warnings($item)
            ];
        })->values();

        return $combined;
    }

    public function inTime(Collection $orders)
    {
        $planned = $orders->sum('planned_work');
        $actual = $orders->sum('actual_work');

        if($planned > 0 && $actual > 0)
            return round((1 - abs(($actual - $planned) / (($planned + (5 / $planned) + $actual)))) * 100, 2);
        return 0;
    }

    public function warnings(Collection $operations)
    {
        return $operations->map(function($op) {
            if((float) $op['in_time'] < 95.00)
                return [
                    'operation' => $op['op_number'],
                    'in_time' => (float) $op['in_time'],
                    'work_center' => $op['op_work_center'],
                    'actual_work' => (float) $op['actual_work'],
                    'planned_work' => (float) $op['planned_work'],
                    'planned_capacity' => (int) $op['planned_capacity'],
                    'actual_capacity' => (int) $op['actual_capacity']
                ];
        })->filter();
    }
}