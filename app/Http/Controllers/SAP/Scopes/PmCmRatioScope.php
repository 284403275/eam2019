<?php


namespace App\SAP\Scopes;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\DB;

class PmCmRatioScope implements Scope
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
            DB::raw('ILOA.EQFNR AS "tag_id"'),
            DB::raw('EQKT.EQKTX AS "eq_description"'),
            DB::raw('IFLOTX.PLTXT AS "floc_description"'),
            DB::raw('ILOA.ABCKZ AS "abc"'),
            DB::raw('ILOA.TPLNR AS "floc"'),
            DB::raw('EQUI.EQART AS "eq_type"'),
            DB::raw('CRHD.ARBPL AS "eq_work_center"'),
            DB::raw('SUM(CASE WHEN AUFK.AUART = \'8F01\' THEN AFVV.ISMNW ELSE 0 END) AS "cm_work"'),
            DB::raw('SUM(CASE WHEN AUFK.AUART = \'8F02\' THEN AFVV.ISMNW ELSE 0 END) AS "pm_work"'),
            DB::raw('COUNT(CASE WHEN AUFK.AUART = \'8F01\' THEN 1 ELSE null END) AS "cm"'),
            DB::raw('COUNT(CASE WHEN AUFK.AUART = \'8F02\' THEN 1 ELSE null END) AS "pm"')
        ])->join('AUFK', 'AUFK.AUFNR', '=', 'AFIH.AUFNR')
            ->join('AFKO', 'AFKO.AUFNR', '=', 'AUFK.AUFNR')
            ->join('AFVC', 'AFVC.AUFPL', '=', 'AFKO.AUFPL')
            ->join('AFVV', function ($q) {
                $q->on('AFVV.AUFPL', '=', 'AFVC.AUFPL')
                    ->on('AFVV.APLZL', '=', 'AFVC.APLZL');
            })
            ->join('EQUI', 'EQUI.EQUNR', '=', 'AFIH.EQUNR')
            ->join('EQKT', 'EQKT.EQUNR', '=', 'EQUI.EQUNR', 'full outer')
            ->join('EQUZ', function ($q) {
                $q->on('EQUZ.EQUNR', '=', 'EQUI.EQUNR')
                    ->on('EQUZ.MANDT', '=', 'EQUI.MANDT')
                    ->where('EQUZ.DATBI', '=', '99991231');
            })
            ->join('CRHD', 'CRHD.OBJID', '=', 'EQUZ.GEWRK')
            ->join('ILOA', 'ILOA.ILOAN', '=', 'EQUZ.ILOAN', 'full outer')
            ->join('IFLOTX', 'IFLOTX.TPLNR', '=', 'ILOA.TPLNR', 'full outer')
            ->whereIn('AUFK.AUART', ['8F01', '8F02'])
            ->groupBy(['ILOA.EQFNR', 'EQKT.EQKTX', 'IFLOTX.PLTXT', 'ILOA.ABCKZ', 'EQUI.EQART', 'ILOA.TPLNR', 'CRHD.ARBPL']
            )->userStatusExcludes(['CNCL'])
            ->systemStatusExcludes(['DLFL']);
    }
}