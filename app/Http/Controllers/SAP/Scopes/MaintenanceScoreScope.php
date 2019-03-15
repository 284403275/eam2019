<?php


namespace App\SAP\Scopes;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\DB;

class MaintenanceScoreScope implements Scope
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
            DB::raw('AUFK.AUFNR AS "order"'),
            DB::raw('AFVC.VORNR AS "op_number"'),
            DB::raw('AUFK.KTEXT AS "description"'),
            DB::raw('AFIH.WARPL AS "maintenance_plan"'),
            DB::raw('A."cycle"'),
            DB::raw('A.ABNUM AS "call_number"'),
            DB::raw('AUFK.VAPLZ AS "work_center"'),
            DB::raw('CRHD.ARBPL AS "op_work_center"'),
            DB::raw('ILOA.EQFNR AS "tag"'),
            DB::raw("CASE WHEN (A.NPLDA != '00000000' AND AFKO.GETRI != '00000000') THEN
                CASE
                    WHEN to_number(to_char(to_date(AFKO.GETRI ,'YYYYMMDD') - to_date(A.NPLDA ,'YYYYMMDD'))) > CASE WHEN A.\"cycle\" <= 7 THEN 3 ELSE CEIL(A.\"cycle\" * .1) END
                        THEN 0
                    WHEN to_number(to_char(to_date(AFKO.GETRI ,'YYYYMMDD') - to_date(A.NPLDA ,'YYYYMMDD'))) * -1 > CASE WHEN A.\"cycle\" <= 7 THEN 3 ELSE CEIL(A.\"cycle\" * .1) END
                        THEN 0
                    ELSE 1
                    END
                    ELSE 0
                END AS \"on_time\""),
            DB::raw('AFVC.ANZZL AS "planned_capacity"'),
            DB::raw('AFVV.DAUNO AS "duration"'),
            DB::raw('AFVV.ARBEI AS "planned_work"'),
            DB::raw('AFVV.ISMNW AS "actual_work"'),
            DB::raw('COUNT(DISTINCT AFRU.PERNR) OVER (PARTITION BY AFRU.RUECK) AS "actual_capacity"'),
            DB::raw('COUNT(DISTINCT AFRU.PERNR) OVER (PARTITION BY AUFK.AUFNR) AS "total_capacity"'),
            DB::raw('CASE WHEN AFVV.ARBEI > 0 AND AFVV.ISMNW > 0 THEN ROUND((1 - ABS((AFVV.ISMNW - AFVV.ARBEI) / ((AFVV.ARBEI + (5 / AFVV.ARBEI) + AFVV.ISMNW)))) * 100, 2) ELSE 0 END AS "in_time"')
        ])->distinct()
            ->join('AFIH', 'AFIH.AUFNR', '=', 'AUFK.AUFNR')
            ->join('AFKO', 'AFKO.AUFNR', '=', 'AFIH.AUFNR')
            ->join('AFVC', 'AFVC.AUFPL', '=', 'AFKO.AUFPL')
            ->join('AFVV', function($q) {
                $q->on('AFVV.AUFPL', '=', 'AFVC.AUFPL')
                    ->on('AFVV.APLZL', '=', 'AFVC.APLZL');
            })
            ->join('AFRU', 'AFRU.RUECK', '=', 'AFVC.RUECK')
            ->join('CRHD', 'CRHD.OBJID', '=', 'AFVC.ARBID')
            ->join('ILOA', 'ILOA.ILOAN', '=', 'AFIH.ILOAN', 'FULL OUTER')
            ->join(DB::raw("(" . self::planning()->toSql() . ") A"), function ($j) {
                $j->on(DB::raw('A.WARPL'), '=', 'AFIH.WARPL')
                    ->on(DB::raw('A.ABNUM'), '=', 'AFIH.ABNUM')
                    ->addBinding(self::planning()->getBindings());
            }, NULL, NULL, 'FULL OUTER')
        ->where('AUFK.AUART', '8F02')
        ->whereIn('AUFK.VAPLZ', ['TAB_P', 'FMG_P', 'MIC_P', 'IC', 'WTR_P', 'ELEC_P', 'BS', 'TGM']);
    }

    protected function planning()
    {
        return DB::connection('oracle')->table('MHIS')->select([
            'WARPL',
            'NPLDA',
            'ABNUM',
            DB::raw('MAX(ROUND(ZYKZT / 3600 / 24, 2)) AS "cycle"'),
        ])
            ->groupBy('WARPL', 'NPLDA', 'ABNUM');
    }
}