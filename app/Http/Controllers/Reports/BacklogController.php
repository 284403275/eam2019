<?php


namespace App\Http\Controllers\Reports;


use App\Http\Controllers\Controller;
use App\SAP\Models\WorkOrder;

class BacklogController extends Controller
{
    public function index()
    {
        return response()->json(['data' => WorkOrder::backlog(now()->format('Ymd'))->get()]);
    }
}