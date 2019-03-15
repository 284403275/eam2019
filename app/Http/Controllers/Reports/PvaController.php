<?php


namespace App\Http\Controllers\Reports;


use App\Http\Controllers\Controller;
use App\PAS;
use App\SAP\Excel\WeeklyPvaReport;
use App\SAP\Models\Confirmation;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PvaController extends Controller
{
    public function run($year = null, $ww = null)
    {
        if(!$year || !$ww || $ww > 54) {
            $week_start = new DateTime();
            $ww = (int)$week_start->format("W") - 1;

            $week_start->setISODate(Carbon::now()->format('Y'), $ww, 1);
            $year = Carbon::now()->year;
        } else {
            $week_start = (new DateTime)->setISODate($year, $ww, 1);
        }

        $start = Carbon::parse($week_start->format('Y-m-d 00:00:00'))->subDay();
        $finish = $start->copy()->addDays(6)->endOfDay();

//        dd($start, $finish);

        $wc = ['MIC_P', 'FMG_P', 'IC', 'WTR_P', 'ELEC_P'];

        $excel = new WeeklyPvaReport($ww);

        $schedule = PAS::ww($ww, $year)->whereIn('order_type', ['8F01', '8F02', '8F03', '8F06'])->whereIn('op_work_center', $wc)->whereIn('report_id', function ($q) use ($ww) {
            $q->addSelect(DB::raw("(" . self::maxId($ww)->toSql() . ")"));
        })->get();

        $confirmations = Confirmation::workCenters($wc)
            ->actuallyCompletedBetween($start, $finish)
            ->get();

        $results = $schedule->map(function ($item) use ($confirmations, $start, $finish) {
            if ($item['order']) {
                $time = $confirmations->where('order', $item['order'])
                    ->where('operation_num', $item['op_number']);

                $complete = $time->where('is_final', 'X')->where('is_reversed', ' ')->count() > 0;
            } else {
                $time = $confirmations->where('maintenance_plan', $item['maintenance_plan'])
                    ->where('maintenance_item', $item['item'])
                    ->where('operation_num', $item['op_number'])
                    ->where('call_number', $item['call_number']);
                $complete = $time->where('is_final', 'X')->where('is_reversed', ' ')->count() > 0;
            }

            return [
                'key' => $item['key'],
                'order' => $item['order'],
                'order_type' => $item['order_type'],
                'operation' => $item['op_number'],
                'description' => $item['op_description'],
                'maintenance_plan' => $item['maintenance_plan'],
                'maintenance_item' => $item['item'],
                'work_center' => $item['op_work_center'],
                'completed' => $complete,
                'work_recorded' => $time->sum('actual_work'),
                'planned_work' => $item->planned
            ];
        });

        $excel->addReview($results);

        $growth = $confirmations->map(function ($confirmation) use ($schedule) {

            $entries = $schedule->where('order', $confirmation['order'])->where('op_number', $confirmation['operation_num']);

            if ($entries->count() == 0) {
                $entries = $schedule->where('key', $confirmation['future_key']);
            }

            if ($entries->count() == 0)
                return $confirmation;
        })->filter()->values();


        $report = $results->groupBy('work_center')->map(function ($item, $wc) use ($growth, $confirmations, $excel) {
            $g = $growth->where('work_center', $wc);

            $excel->addGrowth($g, $wc);

            return [
                'planned_tasks' => $item->count(),
                'planned_work' => round($item->sum('planned_work'), 2),
                'planned_completed' => $item->where('completed', true)->count(),
                'work_recorded_on_planned' => round($item->sum('work_recorded'), 2),
                'pva_tasks' => round($item->where('completed', true)->count() / $item->count() * 100, 2),
                'task_growth' => $g->unique('key')->count(),
                'work_done_on_growth' => round($g->sum('actual_work'), 2),
                'total_work_recorded' => round($confirmations->where('work_center', $wc)->sum('actual_work'), 2),
                'work_center' => $wc
            ];
        });

        $excel->addSummary($report, $year, $ww);

        return $excel->download($year . ' - ' . $ww . ' Maintenance Review.xlsx');

        return $type == 'excel' ? $excel->download('Work Week ' . $ww . ' Review.xlsx') : $report;
    }

    public static function maxId($ww)
    {
        return DB::table('pas')->select([DB::raw("max(report_id)")])->whereRaw('week(scheduled_for, 6) = ' . $ww);
    }
}