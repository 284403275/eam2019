<?php


namespace App\Http\Controllers;


use App\Notifications\ScheduleUpdated;
use App\SAP\Core\Bapis\Confirmations;
use App\SAP\Core\Bapis\Operations;
use App\SAP\Core\Bapis\WorkOrder;
use Carbon\Carbon;

class WorkOrderController extends Controller
{
    public function show($orderId, WorkOrder $workOrder)
    {
        return $workOrder->find($orderId)->with(['comments', 'operations', 'confirmations'])->results()->first();
    }

    public function update($orderId, WorkOrder $order)
    {
        $item = $order->find($orderId)->results()->first();

        $call = $order->set($orderId)->updateDates([
            'start_date' => Carbon::parse(request('start'))->format('Ymd'),
            'end_date' => Carbon::parse(request('end'))->format('Ymd'),
            'start_time' => Carbon::parse(request('start'))->format('His'),
            'end_time' => Carbon::parse(request('end'))->format('His')
        ])->save();

        $message = ltrim($item['order']) . ' (' . $item['title'] . ') has been updated.';

        $fields = [
            'User' => auth()->user()->first_name . ' ' . auth()->user()->last_name,
            'Old Start' => $item['basic_start'],
            'New Start' => Carbon::parse(request('start'))->format('Y-m-d H:i:s')
        ];

        auth()->user()->notify(new ScheduleUpdated($message, $fields));

        return response()->json([
            'data' => [
                'order' => $orderId,
                'start' => Carbon::parse(request('start'))->format('Y-m-d H:i:s'),
                'end' => Carbon::parse(request('end'))->format('Y-m-d H:i:s'),
            ],
            'response' => $call
        ]);
//        $item = $order->setOrder($orderId)->get()->items()->first();
//
//        $call = $order->updateDates([
//            'start_date' => Carbon::parse(request('start'))->format('Ymd'),
//            'end_date' => Carbon::parse(request('end'))->format('Ymd'),
//            'start_time' => Carbon::parse(request('start'))->format('His'),
//            'end_time' => Carbon::parse(request('end'))->format('His')
//        ]);
//
//        $message = ltrim($item['order']) . ' (' . $item['title'] . ') has been updated.';
//
//        $fields = [
//            'User' => auth()->user()->first_name . ' ' . auth()->user()->last_name,
//            'Old Start' => $item['basic_start'],
//            'New Start' => Carbon::parse(request('start'))->format('Y-m-d H:i:s')
//        ];
//
//        auth()->user()->notify(new ScheduleUpdated($message, $fields));

//        $operations = (new Operations())->order($orderId)->get();
//        $orders = (new WorkOrders())->workOrders($orderId)->get();
//        $schedule = new Schedule();

//        return response()->json([
//            'data' => [
//                'order' => $orderId,
//                'start' => Carbon::parse(request('start'))->format('Y-m-d H:i:s'),
//                'end' => Carbon::parse(request('end'))->format('Y-m-d H:i:s'),
//            ],
//            'server' => $call
//
//        ]);
    }

    public function release()
    {
//        $order->load($orderId)->release();

//        return response()->json((new WorkOrders)->load($orderId)->items()->first());
    }
}