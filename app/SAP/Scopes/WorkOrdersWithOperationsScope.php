<?php


namespace App\SAP\Scopes;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\DB;

class WorkOrdersWithOperationsScope implements Scope
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
            DB::raw('AUFK.OBJNR AS "object"'),
            DB::raw('AFKO.AUFPL AS "routing_number"'),
            DB::raw('AFIH.ILART AS "activity"'),
            DB::raw('AFIH.PRIOK AS "priority"'),
            DB::raw('ILOA.EQFNR AS "tag"'),
            DB::raw('ILOA.ABCKZ AS "abc"'),
            DB::raw('AUFK.KTEXT AS "description"'),
            DB::raw('AFIH.QMNUM AS "notification"'),
            DB::raw('AUFK.AUFNR AS "order"'),
            DB::raw('AUFK.VAPLZ AS "work_center"'),
            DB::raw('CRHD.ARBPL AS "op_work_center"'),
            DB::raw('AUFK.AUART AS "order_type"'),
            DB::raw('AFKO.GSTRP AS "basic_start_date"'),
            DB::raw('AFKO.GSUZP AS "basic_start_time"'),
            DB::raw('AFKO.GLTRP AS "basic_finish_date"'),
            DB::raw('AFKO.GLUZP AS "basic_finish_time"'),
            DB::raw('AFKO.GETRI AS "actual_finish_date"'),
            DB::raw('AFKO.GEUZI AS "actual_finish_time"'),
            DB::raw('AFVV.IEDD AS "actual_op_finish_date"'),
            DB::raw('AFVV.IEDZ AS "actual_op_finish_time"'),
            DB::raw('AFVC.VORNR AS "op_number"'),
            DB::raw('AFVC.STEUS AS "control_key"'),
            DB::raw('AFVC.LTXA1 AS "op_description"'),
            DB::raw('AUFK.AUFNR || AFVC.VORNR || AFIH.WARPL || AFIH.WAPOS || AFIH.ABNUM AS "key"'),
            DB::raw('CASE WHEN AFKO.GETRI != \'00000000\' THEN to_char(to_date(AFKO.GETRI,\'YYYYMMDD\'),\'YYYY\') ELSE NULL END AS "finish_year"'),
            DB::raw('CASE WHEN AFKO.GETRI != \'00000000\' THEN to_char(to_date(AFKO.GETRI,\'YYYYMMDD\'),\'ww\') ELSE NULL END AS "finish_ww"'),
            DB::raw('FLOOR(SYSDATE - TO_DATE(AUFK.ERDAT, \'YYYYMMDD\')) AS "days_open"'),
            DB::raw('CASE AFKO.GLTRP WHEN \'00000000\' THEN FLOOR(SYSDATE - TO_DATE(AUFK.ERDAT, \'YYYYMMDD\')) ELSE FLOOR(SYSDATE - TO_DATE(AFKO.GLTRP, \'YYYYMMDD\')) END AS "overdue_days"'),
            DB::raw('AFVC.ANZZL AS "planned_capacity"'),
            DB::raw('AFVV.DAUNO AS "planned_worked"'),
            DB::raw('AFVV.ISMNW AS "actual_work"'),
            //DB::raw('AFVV.EPEND AS "operation_finish_date"'),
            //DB::raw('AFVV.IEDZ AS "operation_finish_time"'),
            DB::raw('ltrim(AFVC.RMZHL,\'0\') AS "number_confirmations"'),
            DB::raw('AFIH.WARPL AS "maintenance_plan"'),
            DB::raw('AFIH.WAPOS AS "maintenance_item"'),
            DB::raw('AFIH.ABNUM AS "maintenance_call_number"'),
            DB::raw('AUFK.ZZ_COMPLSTART_DATE AS "compliance_start"'),
            DB::raw('AUFK.ZZ_COMPLEND_DATE AS "compliance_end"'),
            DB::raw('FLOOR(SYSDATE - TO_DATE(AUFK.ERDAT, \'YYYYMMDD\')) AS "days_open"'),
        ])
            ->addSelect(DB::raw("(" . self::userStatus()->toSql() . ") AS \"user_status\""))
            ->addBinding(self::userStatus()->getBindings(), 'select')
            ->addSelect(DB::raw("(" . static::systemStatus()->toSql() . ") AS \"system_status\""))
            ->addBinding(static::systemStatus()->getBindings(), 'select')
            ->addSelect(DB::raw("(" . static::operationSystemStatus()->toSql() . ") AS \"operation_system_status\""))
            ->addBinding(static::operationSystemStatus()->getBindings(), 'select')
            ->join('AUFK', 'AUFK.AUFNR', '=', 'AFIH.AUFNR')
            ->join('AFKO', 'AFKO.AUFNR', '=', 'AFIH.AUFNR')
            ->join('AFVC', 'AFVC.AUFPL', '=', 'AFKO.AUFPL')
            ->join('AFVV', function($q) {
                $q->on('AFVV.AUFPL', '=', 'AFVC.AUFPL')
                    ->on('AFVV.APLZL', '=', 'AFVC.APLZL');
            })
            ->join('CRHD', 'CRHD.OBJID', '=', 'AFVC.ARBID')
            ->join('ILOA', 'ILOA.ILOAN', '=', 'AFIH.ILOAN', 'FULL OUTER')
            ->join('IFLOTX', 'IFLOTX.TPLNR', '=', 'ILOA.TPLNR')
            ->whereIn('AUFK.AUART', ['8F01', '8F02', '8F03', '8F06'])
            ->where('AUFK.LOEKZ', '!=', 'X');
    }

    private static function userStatus()
    {
        return DB::connection('oracle')->table('JEST')->select(
            DB::raw("LISTAGG(TJ30T.TXT04, ' ') WITHIN GROUP (ORDER BY TJ30T.TXT04)"))
            ->join('JSTO', 'JSTO.OBJNR', '=', 'JEST.OBJNR')
            ->join('TJ30T', function ($q) {
                $q->on('JSTO.STSMA', '=', 'TJ30T.STSMA')
                    ->on('JEST.STAT', '=', 'TJ30T.ESTAT');
            })
            ->whereRaw("JEST.INACT != 'X'")
            ->whereRaw("TJ30T.SPRAS = 'E'")
            ->whereColumn('JEST.OBJNR', 'AUFK.OBJNR');
    }

    private static function systemStatus()
    {
        return
            DB::connection('oracle')->table('JEST')->select(
                DB::raw("LISTAGG(TJ02T.TXT04, ' ') WITHIN GROUP (ORDER BY TJ02T.TXT04)"))
                ->join('TJ02T', 'JEST.STAT', '=', 'TJ02T.ISTAT')
                ->where('JEST.INACT', '!=', 'X')
                ->where('TJ02T.SPRAS', '=', 'E')
                ->whereColumn('JEST.OBJNR', 'AUFK.OBJNR');
    }

    private static function operationSystemStatus()
    {
        return
            DB::connection('oracle')->table('JEST')->select(
                DB::raw("LISTAGG(TJ02T.TXT04, ' ') WITHIN GROUP (ORDER BY TJ02T.TXT04)"))
                ->join('TJ02T', 'JEST.STAT', '=', 'TJ02T.ISTAT')
                ->where('JEST.INACT', '!=', 'X')
                ->where('TJ02T.SPRAS', '=', 'E')
                ->whereColumn('JEST.OBJNR', 'AFVC.OBJNR');
    }
}