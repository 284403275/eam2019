<?php


namespace App\SAP\Scopes;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\DB;

class WorkOrderScope implements Scope
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
            DB::raw('AUFK.AUFNR AS "order"'),
            DB::raw('AUFK.VAPLZ AS "work_center"'),
            DB::raw('AUFK.AUART AS "order_type"'),
            DB::raw('A."cycle"'),
            DB::raw('CASE WHEN A."cycle" <= 7 THEN 3 ELSE CEIL(A."cycle" * .1) END AS "window"'),
            DB::raw('CASE WHEN A.NPLDA != \'00000000\' THEN to_number(to_char(to_date(A.NPLDA ,\'YYYYMMDD\') - CASE WHEN A."cycle" <= 7 THEN 3 ELSE CEIL(A."cycle" * .1) END, \'YYYYMMDD\')) ELSE NULL END AS "window_start"'),
            DB::raw('to_number(A.NPLDA) AS "planned_date"'),
            DB::raw('CASE WHEN A.NPLDA != \'00000000\' THEN to_number(to_char(to_date(A.NPLDA ,\'YYYYMMDD\') + CASE WHEN A."cycle" <= 7 THEN 3 ELSE CEIL(A."cycle" * .1) END, \'YYYYMMDD\')) ELSE NULL END AS "window_end"'),
            DB::raw('AFKO.GSTRP AS "basic_start_date"'),
            DB::raw('AFKO.GLTRP AS "basic_finish_date"'),
            DB::raw('AFKO.GLUZP AS "basic_finish_time"'),
            DB::raw('AFKO.GETRI AS "actual_finish_date"'),
            DB::raw('CASE WHEN (A.NPLDA != \'00000000\' AND AFKO.GETRI != \'00000000\') THEN to_number(to_char(to_date(AFKO.GETRI ,\'YYYYMMDD\') - to_date(A.NPLDA ,\'YYYYMMDD\'))) ELSE NULL END AS "difference"'),
            DB::raw('CASE WHEN AFKO.GETRI != \'00000000\' THEN to_char(to_date(AFKO.GETRI,\'YYYYMMDD\'),\'YYYY\') ELSE NULL END AS "finish_year"'),
            DB::raw('CASE WHEN AFKO.GETRI != \'00000000\' THEN to_char(to_date(AFKO.GETRI,\'YYYYMMDD\'),\'ww\') ELSE NULL END AS "finish_ww"'),
            DB::raw('
                CASE WHEN (A.NPLDA != \'00000000\' AND AFKO.GETRI != \'00000000\') THEN
                    CASE 
                        WHEN to_number(to_char(to_date(AFKO.GETRI ,\'YYYYMMDD\') - to_date(A.NPLDA ,\'YYYYMMDD\'))) > CASE WHEN A."cycle" <= 7 THEN 3 ELSE CEIL(A."cycle" * .1) END 
                            THEN \'1\'
                        WHEN to_number(to_char(to_date(AFKO.GETRI ,\'YYYYMMDD\') - to_date(A.NPLDA ,\'YYYYMMDD\'))) * -1 > CASE WHEN A."cycle" <= 7 THEN 3 ELSE CEIL(A."cycle" * .1) END
                            THEN \'1\'
                        ELSE NULL
                        END
                    END AS "ooc"
            '),
            DB::raw('CASE WHEN AFKO.GETRI BETWEEN AFKO.GSTRP AND AFKO.GLTRP THEN NULL ELSE 1 END AS "v_ooc"'),
            DB::raw('FLOOR(SYSDATE - TO_DATE(AUFK.ERDAT, \'YYYYMMDD\')) AS "days_open"'),
            DB::raw('CASE AFKO.GLTRP WHEN \'00000000\' THEN FLOOR(SYSDATE - TO_DATE(AUFK.ERDAT, \'YYYYMMDD\')) ELSE FLOOR(SYSDATE - TO_DATE(AFKO.GLTRP, \'YYYYMMDD\')) END AS "overdue_days"'),
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
            ->addSelect(DB::raw("(" . self::partners()->toSql() . ") AS \"partners\""))
            ->addBinding(self::partners()->getBindings(), 'select')
            ->join('AUFK', 'AUFK.AUFNR', '=', 'AFIH.AUFNR')
            ->join('AFKO', 'AFKO.AUFNR', '=', 'AFIH.AUFNR')
            ->join('EQKT', 'EQKT.EQUNR', '=', 'AFIH.EQUNR', 'FULL OUTER')
            ->join('ILOA', 'ILOA.ILOAN', '=', 'AFIH.ILOAN', 'FULL OUTER')
            ->join('IFLOTX', 'IFLOTX.TPLNR', '=', 'ILOA.TPLNR')
            ->join(DB::raw("(" . self::planning()->toSql() . ") A"), function ($j) {
                $j->on(DB::raw('A.WARPL'), '=' ,'AFIH.WARPL')
                    ->on(DB::raw('A.ABNUM'), '=', 'AFIH.ABNUM')
                    ->addBinding(self::planning()->getBindings());
            }, NULL, NULL, 'FULL OUTER')
            ->whereNotIn('AUFK.AUART', ['8O01'])
            ->where('AUFK.LOEKZ', '!=', 'X');
    }

    private static function planning()
    {
        return DB::connection('oracle')->table('MHIS')->select([
            'WARPL',
            'NPLDA',
            'ABNUM',
            DB::raw('MAX(ROUND(ZYKZT / 3600 / 24, 2)) AS "cycle"'),
        ])
            ->groupBy('WARPL', 'NPLDA', 'ABNUM');
    }

    private static function partners()
    {
        return DB::connection('oracle')->table('IHPA')->select([
            DB::raw("LISTAGG(PA0002.VNAMC || ' ' || PA0002.NCHMC, ', ') WITHIN GROUP (ORDER BY IHPA.PARNR)"),
        ])
            ->join('PA0002', function($q) {
                $q->on('PA0002.PERNR', '=', 'IHPA.PARNR');
            })
            ->where('PA0002.ENDDA', '=', '99991231')
            ->whereColumn('IHPA.OBJNR', 'AUFK.OBJNR');
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
}