<?php


namespace App\SAP\Scopes;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\DB;

class ConfirmationScope implements Scope
{

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->select([
            DB::raw('AFIH.AUFNR AS "order"'),
            DB::raw('AUFK.KTEXT AS "order_description"'),
            DB::raw('AFRU.VORNR AS "operation_num"'),
            DB::raw('AUFK.AUART AS "order_type"'),
            DB::raw('CASE AFRU.STOKZ WHEN \'X\' THEN AFRU.ISMNW * -1 ELSE AFRU.ISMNW END AS "actual_work"'),
            DB::raw('AFRU.ISMNE AS "actual_work_uom"'),
            DB::raw("AFIH.AUFNR || AFRU.VORNR || CASE WHEN AFIH.WARPL = ' ' THEN '' ELSE AFIH.WARPL END || CASE WHEN AFIH.WAPOS = ' ' THEN '' ELSE AFIH.WAPOS END || AFIH.ABNUM AS \"key\""),
            DB::raw("AFRU.VORNR || CASE WHEN AFIH.WARPL = ' ' THEN '' ELSE AFIH.WARPL END || CASE WHEN AFIH.WAPOS = ' ' THEN '' ELSE AFIH.WAPOS END || AFIH.ABNUM AS \"future_key\""),
            DB::raw('AFIH.WARPL AS "maintenance_plan"'),
            DB::raw('AFIH.WAPOS AS "maintenance_item"'),
            DB::raw('AFVC.LTXA1 AS "operation_description"'),
            DB::raw('AFVC.STEUS AS "control_key"'),
            DB::raw('AFIH.ABNUM AS "call_number"'),
            DB::raw('AFIH.ILART AS "activity_type"'),
            DB::raw('AFRU.ERNAM AS "entered_by"'),
            DB::raw('AFRU.PERNR AS "entered_for"'),
            DB::raw('CRHD.ARBPL AS "work_center"'),
            DB::raw('AFRU.ERSDA AS "entered_date"'),
            DB::raw('AFRU.ERZET AS "entered_time"'),
            DB::raw('AFRU.AUERU AS "is_final"'),
            DB::raw('AFKO.GETRI AS "order_finish_date"'),
            DB::raw('AFKO.GEUZI AS "order_finish_time"'),
            DB::raw('AFRU.IEDD AS "operation_finish_date"'),
            DB::raw('AFRU.IEDZ AS "operation_finish_time"'),
            DB::raw('AFRU.STOKZ AS "is_reversed"'),
            DB::raw('AFRU.LTXA1 AS "confirmation_text"'),
        ])
            ->join('AFVC', 'AFVC.RUECK', '=', 'AFRU.RUECK')
            ->join('AFKO', 'AFKO.AUFPL', '=', 'AFVC.AUFPL')
            ->join('AUFK', 'AUFK.AUFNR', '=', 'AFKO.AUFNR')
            ->join('AFIH', 'AFIH.AUFNR', '=', 'AUFK.AUFNR')
            ->join('AFVV', function($q) {
                $q->on('AFVV.AUFPL', '=', 'AFVC.AUFPL')->on('AFVV.APLZL' ,'=', 'AFVC.APLZL');
            })
            ->join('CRHD', 'CRHD.OBJID', '=', 'AFRU.ARBID')
            ->whereIn('AUFK.AUART', ['8F01', '8F02', '8F03', '8F06']);
    }
}