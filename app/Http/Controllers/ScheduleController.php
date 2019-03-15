<?php


namespace App\Http\Controllers;




use App\Forecast;
use App\SAP\Models\Schedule;
use App\SAP\Resources\ScheduleCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ScheduleController extends Controller
{
    public function index(Request $request, Forecast $forecast)
    {
        $all = $forecast->distinct()->select([
            'Maintenance Plan',
            'Call Number',
            'Planned Date',
            'Maintenance Item Text',
            'Work Center'
        ])->whereBetween('Planned Date', [request('start'), request('end')])
            ->whereNull('Work Order')
            ->get()
            ->map(function ($item) {
                return [
                    'start' => $item['Planned Date'],
                    'title' => $item['Maintenance Item Text'],
                    'allDay' => true,
                    'color' => '#999999',
                    'editable' => false,
                    'op_work_center' => $item['Work Center'],
                    'is_future' => true,
                    'system_status' => []
                ];
            })->toArray();


        $schedule = Schedule::basicStartDateBetween(
            Carbon::parse($request->get('start'))->format('Ymd'),
            Carbon::parse($request->get('end'))->format('Ymd')
        )->distinct()->get();


        $json = (new ScheduleCollection($schedule))->resolve();

        return response()->json(array_merge($json, $all));
    }
}