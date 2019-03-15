<?php


namespace App\SAP\Scopes;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\DB;

class ScheduleScope implements Scope
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
            DB::raw('AFIH.QMNUM AS "notification"'),
            DB::raw('AFIH.AUFNR AS "order"'),
            DB::raw('AUFK.AUART AS "order_type"'),
            DB::raw('ILOA.EQFNR AS "tag_id"'),
            DB::raw('AUFK.KTEXT AS "description"'),
            DB::raw('AUFK.VAPLZ AS "main_work_center"'),
            DB::raw('CRHD.ARBPL AS "op_work_center"'),
            DB::raw('MAX(AFVV.DAUNO) OVER (PARTITION BY AFIH.AUFNR || CRHD.ARBPL) AS "duration"'),
            DB::raw('SUM(AFVV.ISMNW) OVER (PARTITION BY AFIH.AUFNR) AS "actual_work"'),
            DB::raw('SUM(AFVC.ANZZL) OVER (PARTITION BY AFIH.AUFNR) AS "planned_capacity"'),
            DB::raw('SUM(AFVV.ARBEI) OVER (PARTITION BY AFIH.AUFNR || CRHD.ARBPL) AS "total_duration"'),
            DB::raw('AFKO.GSTRP AS "basic_start_date"'),
            DB::raw('AFKO.GSUZP AS "basic_start_time"'),
            DB::raw('AFKO.GLTRP AS "basic_finish_date"'),
            DB::raw('AFKO.GLUZP AS "basic_finish_time"'),
            DB::raw('CASE WHEN A.NPLDA != \'00000000\' THEN to_number(to_char(to_date(A.NPLDA ,\'YYYYMMDD\') - CASE WHEN A."cycle" <= 7 THEN 3 ELSE CEIL(A."cycle" * .1) END, \'YYYYMMDD\')) ELSE NULL END AS "window_start"'),
            DB::raw('to_number(A.NPLDA) AS "planned_date"'),
            DB::raw('A."cycle"'),
            DB::raw("AUFK.OBJNR"),
            DB::raw('CASE WHEN A.NPLDA != \'00000000\' THEN to_number(to_char(to_date(A.NPLDA ,\'YYYYMMDD\') + CASE WHEN A."cycle" <= 7 THEN 3 ELSE CEIL(A."cycle" * .1) END, \'YYYYMMDD\')) ELSE NULL END AS "window_end"'),
            DB::raw('CASE WHEN AUFK.VAPLZ = CRHD.ARBPL THEN 1 ELSE 0 END AS "can_edit"'),
            DB::raw('AFKO.GETRI AS "confirmed_finish_date"'),
            DB::raw('AFKO.GEUZI AS "confirmed_finish_time"')
        ])
            ->addSelect(DB::raw("(" . self::userStatus()->toSql() . ") AS \"user_status\""))
            ->addBinding(self::userStatus()->getBindings(), 'select')
            ->addSelect(DB::raw("(" . static::systemStatus()->toSql() . ") AS \"system_status\""))
            ->addBinding(static::systemStatus()->getBindings(), 'select')
            ->addSelect(DB::raw("(" . self::partners()->toSql() . ") AS \"partners\""))
            ->addBinding(self::partners()->getBindings(), 'select')
            ->join('AFKO', 'AFKO.AUFNR', '=', 'AFIH.AUFNR')
            ->join('AUFK', 'AUFK.AUFNR', '=', 'AFIH.AUFNR')
            ->join('AFVC', 'AFVC.AUFPL', '=', 'AFKO.AUFPL')
            ->join('CRHD', 'CRHD.OBJID', '=', 'AFVC.ARBID')
            //->join('IHPA', 'IHPA.OBJNR', '=', 'AUFK.OBJNR')
            ->join('AFVV', function ($j) {
                $j->on('AFVV.AUFPL', '=', 'AFVC.AUFPL')
                    ->on('AFVV.APLZL', '=', 'AFVC.APLZL');
            })
            ->join('ILOA', 'ILOA.ILOAN', '=', 'AFIH.ILOAN')
            ->join(DB::raw("(" . self::planning()->toSql() . ") A"), function ($j) {
                $j->on(DB::raw('A.WARPL'), '=', 'AFIH.WARPL')
                    ->on(DB::raw('A.ABNUM'), '=', 'AFIH.ABNUM')
                    ->addBinding(self::planning()->getBindings());
            }, NULL, NULL, 'FULL OUTER')
            ->whereIn('AUFK.AUART', ['8F06', '8F03', '8F02', '8F01', '8L04', '8L05']);
    }

    protected static function planning()
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
        return
            DB::connection('oracle')->table('IHPA')->select(
                DB::raw("LISTAGG(IHPA.PARNR, ',') WITHIN GROUP (ORDER BY IHPA.PARNR)"))
                ->where('IHPA.KZLOESCH', '!=', 'X')
                ->whereColumn('IHPA.OBJNR', 'AUFK.OBJNR');
    }

    protected static function userStatus()
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

    protected static function systemStatus()
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