<?php


namespace App\Http\Controllers\Reports;


use App\Http\Controllers\Controller;
use App\SAP\Models\Schedule;
use App\SAP\Models\WorkOrderOperations;
use Illuminate\Support\Carbon;

class DailyController extends Controller
{
    public function index()
    {
//        $schedule = Schedule::basicStartDateBetween(
//            Carbon::today()->startOfDay()->format('Ymd'),
//            Carbon::today()->endOfDay()->format('Ymd')
//        )->distinct()->get();

        $schedule = WorkOrderOperations::onlyRequiredOperations()->basicStartDateBetween(
            Carbon::today()->startOfDay()->format('Ymd'),
            Carbon::today()->endOfDay()->format('Ymd')
        )//->systemStatusExcludes(['CNF', 'TECO', 'CLSD', 'CNCL'])
            //->operationSystemStatusExcludes(['CNF', 'TECO'])
            ->get();

        $today = $schedule->where('op_work_center', 'FMG_P')->map(function($item) {
            $op_complete = collect(explode(' ', $item['system_status']))->contains('CNF');
            return [
                'notification' => $item['notification'],
                'order' => $item['order'],
                'o_number' => $item['op_number'],
                'description' => $item['description'],
                'operation' => $item['op_description'],
                'is_complete' => $item['actual_finish_date'] !== '00000000',
                'is_op_complete' => $op_complete,
                'completed_on' => $op_complete ? Carbon::createFromFormat('YmdHis', $item['actual_op_finish_date'].$item['actual_op_finish_time'])->format('Y-m-d H:i:s') : null,
                'capacity' => $item['planned_capacity'],
                'actual_work' => $item['actual_work'],
                'duration' => $item['planned_worked'],
            ];
        })->values();

        return $today;
    }
}