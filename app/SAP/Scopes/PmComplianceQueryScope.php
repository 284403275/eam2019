<?php


namespace App\SAP\Scopes;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\DB;

class PmComplianceQueryScope implements Scope
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
            DB::raw("CASE WHEN AFKO.GETRI != '00000000' THEN to_char(to_date(AFKO.GETRI,'YYYYMMDD'),'IYYY') ELSE NULL END AS \"finish_year\""),
            DB::raw("CASE WHEN AFKO.GETRI != '00000000' THEN to_char(to_date(AFKO.GETRI,'YYYYMMDD'),'iw') ELSE NULL END AS \"finish_ww\""),
            DB::raw('AUFK.VAPLZ AS main_work_center'),
            DB::raw('COUNT(AUFK.VAPLZ) AS "total"'),
            DB::raw("SUM(CASE WHEN (A.NPLDA != '00000000' AND AFKO.GETRI != '00000000') THEN
                CASE
                    WHEN to_number(to_char(to_date(AFKO.GETRI ,'YYYYMMDD') - to_date(A.NPLDA ,'YYYYMMDD'))) > CASE WHEN A.\"cycle\" <= 7 THEN 3 ELSE CEIL(A.\"cycle\" * .1) END
                        THEN '1'
                    WHEN to_number(to_char(to_date(AFKO.GETRI ,'YYYYMMDD') - to_date(A.NPLDA ,'YYYYMMDD'))) * -1 > CASE WHEN A.\"cycle\" <= 7 THEN 3 ELSE CEIL(A.\"cycle\" * .1) END
                        THEN '1'
                    ELSE NULL
                    END
                END) AS \"ooc\""),
            DB::raw("SUM(CASE WHEN AFKO.GETRI BETWEEN AFKO.GSTRP AND AFKO.GLTRP THEN NULL ELSE 1 END) AS \"v_ooc\"")
        ])
            ->join('AFKO', 'AFKO.AUFNR', '=', 'AFIH.AUFNR')
            ->join('AUFK', 'AUFK.AUFNR', '=', 'AFIH.AUFNR')
            ->join(DB::raw("(" . $this->subQuery()->toSql() . ") A"), function($join) {
                $join->on(DB::raw('A.WARPL'), '=', 'AFIH.WARPL');
                    $join->on(DB::raw('A.ABNUM'), '=', 'AFIH.ABNUM');
                        }, null,null,'full outer')
            ->addBinding($this->subQuery()->getBindings(), 'join')
            ->whereIn('AUFK.AUART', ['8F02'])
            ->where('AUFK.LOEKZ', '!=', 'X')
            ->whereRaw("CASE WHEN AFKO.GETRI != '00000000' THEN to_char(to_date(AFKO.GETRI,'YYYYMMDD'),'IYYY') ELSE NULL END > 2016")
            ->whereRaw("TO_CHAR(TO_DATE(AFKO.GETRI,'YYYYMMDD'),'iw') != TO_CHAR(CURRENT_DATE,'iw')")
            ->groupBy([
                DB::raw("CASE WHEN AFKO.GETRI != '00000000' THEN to_char(to_date(AFKO.GETRI,'YYYYMMDD'),'IYYY') ELSE NULL END"),
                DB::raw("CASE WHEN AFKO.GETRI != '00000000' THEN to_char(to_date(AFKO.GETRI,'YYYYMMDD'),'iw') ELSE NULL END"),
                DB::raw('AUFK.VAPLZ'),
            ])
            ->orderBy(DB::raw("CASE WHEN AFKO.GETRI != '00000000' THEN to_char(to_date(AFKO.GETRI,'YYYYMMDD'),'IYYY') ELSE NULL END"))
            ->orderBy(DB::raw("CASE WHEN AFKO.GETRI != '00000000' THEN to_char(to_date(AFKO.GETRI,'YYYYMMDD'),'iw') ELSE NULL END"))
        ;
    }

    protected function subQuery()
    {
        return DB::connection('oracle')->table('MHIS')->select([
            'WARPL',
            'NPLDA',
            'ABNUM',
            DB::raw('MAX(ROUND(ZYKZT / 3600 / 24, 2)) AS "cycle"')
        ])
            ->groupBy([
                'WARPL',
                'NPLDA',
                'ABNUM'
            ]);
    }
}