<?php


namespace App\Http\Controllers;

use App\SAP\Models\Equipment;
use Illuminate\Support\Facades\DB;

class EquipmentController extends Controller
{
    public function show($equipment)
    {
        return Equipment::where('EQUI.EQUNR', '00000000' . $equipment)->first();
    }

    public function index()
    {
        return response()->json([]);
    }

    public function getTechnicalObjects()
    {
        return Equipment::withoutGlobalScopes()
            ->select([DB::raw('EQUI.EQART AS "object_type"')])
            ->distinct()
            ->get()
            ->transform(function ($item) {
                return [
                    'id' => $item['object_type'],
                    'text' => $item['object_type']
                ];
            });
    }

    public function getEquipmentStatus()
    {
        return Equipment::withoutGlobalScopes()
            ->hasUserStatus(['EMRG', 'LOTO', 'UAVL'])
            ->statusDashboard()->get()
            ->map(function ($eq) {
                return [
                    'tag' => $eq['tag'],
                    'description' => $eq['description'],
                    'status' => explode(' ', $eq['user_status']),
                    'orders' => array_map(function($v) {
                        return ltrim($v, '0');
                    }, explode(' ', $eq['open_orders']))
                ];
            });
    }
}