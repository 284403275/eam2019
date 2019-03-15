<?php


namespace App\Http\Controllers\Reports;


use App\Http\Controllers\Controller;
use App\PAS;
use App\SAP\Excel\WeeklySchedule;
use App\SAP\Models\SingleCyclePlans;
use App\SAP\Models\StrategyPlans;
use App\SAP\Models\WorkOrderOperations;
use Illuminate\Support\Carbon;

class SnapshotController extends Controller
{
    public function run()
    {
        $s = microtime(true);

        $start = Carbon::parse('next sunday');
        $end = Carbon::parse('next sunday')->addDays(6);

        $id = PAS::max('report_id') + 1;

        $singleCycle = SingleCyclePlans::onlyRequiredOperations()->wherePlannedDateBetween($start->format('Ymd'), $end->format('Ymd'))->hasNotBeenCalled()->get();
        $strat = StrategyPlans::onlyRequiredOperations()->wherePlannedDateBetween($start->format('Ymd'), $end->format('Ymd'))->hasNotBeenCalled()->get();
        $planned = WorkOrderOperations::onlyRequiredOperations()->basicStartDateBetween($start->format('Ymd'), $end->format('Ymd'))
            ->systemStatusExcludes(['CNF', 'TECO', 'CLSD'])
            ->userStatusExcludes(['CNCL'])
            ->operationSystemStatusExcludes(['CNF', 'TECO'])
            ->get();

        $complete = $singleCycle->map(function ($item) use ($id) {
            return [
                'report_id' => $id,
                'maintenance_plan' => $item['maintenance_plan'],
                'maintenance_item' => $item['maintenance_item_text'],
                'item' => $item['maintenance_item'],
                'call_number' => $item['call_number'],
                'order' => $item['order'],
                'control_key' => $item['control_key'],
                'order_type' => $item['order_type'],
                'op_number' => $item['op_number'],
                'op_work_center' => $item['op_work_center'],
                'op_description' => $item['op_description'],
                'scheduled_for' => Carbon::createFromFormat('YmdHis', $item['planned_date'] . '000000')->format('Y-m-d H:i:s'),
                'actual_finish' => null,
                'planned_work' => $item['work'],
                'planned_capacity' => $item['capacity'],
                'actual_work' => 0,
                'type' => 'planned',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
            ];
        });

        $strat->map(function ($item) use ($complete, $id) {
            $complete->push([
                'report_id' => $id,
                'maintenance_plan' => $item['maintenance_plan'],
                'maintenance_item' => $item['maintenance_item_text'],
                'item' => $item['maintenance_item'],
                'call_number' => $item['call_number'],
                'order' => $item['order'],
                'control_key' => $item['control_key'],
                'order_type' => $item['order_type'],
                'op_number' => $item['op_number'],
                'op_work_center' => $item['op_work_center'],
                'op_description' => $item['op_description'],
                'scheduled_for' => Carbon::createFromFormat('YmdHis', $item['planned_date'] . '000000')->format('Y-m-d H:i:s'),
                'actual_finish' => null,
                'planned_work' => $item['work'],
                'planned_capacity' => $item['capacity'],
                'actual_work' => 0,
                'type' => 'planned',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
            ]);
        });

        $planned->map(function ($item) use ($complete, $id) {
            $complete->push([
                'report_id' => $id,
                'maintenance_plan' => $item['maintenance_plan'],
                'maintenance_item' => $item['description'],
                'item' => $item['maintenance_item'],
                'call_number' => $item['maintenance_call_number'],
                'order' => $item['order'],
                'control_key' => $item['control_key'],
                'order_type' => $item['order_type'],
                'op_number' => $item['op_number'],
                'op_work_center' => $item['op_work_center'],
                'op_description' => $item['op_description'],
                'scheduled_for' => Carbon::createFromFormat('YmdHis', $item['basic_start_date'] . $item['basic_start_time'])->format('Y-m-d H:i:s'),
                'actual_finish' => null,
                'planned_work' => $item['planned_worked'],
                'planned_capacity' => $item['planned_capacity'],
                'actual_work' => $item['actual_work'],
                'type' => 'planned',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
            ]);
        });

        PAS::insertAll($complete->toArray());

        $time_elapsed_secs = microtime(true) - $s;

        return "Done, inserted " . $complete->count() . " records in " . $time_elapsed_secs . ' seconds for the period ' . $start->toDateString() . ' - ' . $end->toDateString();
    }

    public function download($year, $ww)
    {
        return (new WeeklySchedule($ww, $year))->download('Work Week ' . $ww . ' Schedule.xlsx');
    }
}