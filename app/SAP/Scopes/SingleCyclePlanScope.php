<?php


namespace App\SAP\Scopes;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\DB;

class SingleCyclePlanScope implements Scope
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
            DB::raw('MHIS.WARPL AS "maintenance_plan"'),
            DB::raw('MPOS.AUART AS "order_type"'),
            DB::raw('ROUND(MHIS.ZYKZT / 3600 / 24, 2) AS "cycle"'),
            DB::raw('AFIH.AUFNR AS "order"'),
            DB::raw('CRHD.ARBPL AS "work_center"'),
            DB::raw('MHIS.NPLDA AS "planned_date"'),
            DB::raw('MHIS.HORDA AS "call_date"'),
            DB::raw('AFKO.GSTRP AS "basic_start_date"'),
            DB::raw('AFKO.GSUZP AS "basic_start_time"'),
            DB::raw('AFKO.GLTRP AS "basic_finish_date"'),
            DB::raw('AFKO.GETRI AS "confirmed_finish_date"'),
            DB::raw('MPLA.WPTXT AS "maintenance_plan_text"'),
            DB::raw('MPOS.WAPOS AS "maintenance_item"'),
            DB::raw('MPOS.PSTXT AS "maintenance_item_text"'),
            DB::raw('MPLA.STRAT AS "maintenance_strategy"'),
            DB::raw('MHIS.ABNUM AS "call_number"'),
            DB::raw('TO_CHAR(NULL) AS "package"'),
            DB::raw('TO_CHAR(NULL) AS "package_text"'),
            DB::raw('MPOS.EQUNR AS "equipment_number"'),
            DB::raw('ILOA.EQFNR AS "sort_field"'),
            DB::raw('ILOA.TPLNR AS "floc"'),
            DB::raw('IFLOTX.PLTXT AS "floc_text"'),
            DB::raw('op.ARBPL AS "op_work_center"'),
            DB::raw('PLPO.STEUS AS "control_key"'),
            DB::raw('PLPO.VORNR AS "op_number"'),
            DB::raw('PLPO.LTXA1 AS "op_description"'),
            DB::raw('PLPO.ANZZL AS "capacity"'),
            DB::raw('PLPO.DAUNO AS "work"'),
            DB::raw('PLPO.ARBEI AS "total_work_estimate"'),
        ])
            ->addSelect(DB::raw("(" . self::userStatus()->toSql() . ") AS \"user_status\""))
            ->addBinding(self::userStatus()->getBindings(), 'select')
            ->addSelect(DB::raw("(" . static::systemStatus()->toSql() . ") AS \"system_status\""))
            ->addBinding(static::systemStatus()->getBindings(), 'select')
        ->join('MPLA', function($j) {
            $j->on('MPLA.WARPL', '=', 'MHIS.WARPL')->whereRaw("LENGTH(MPLA.STRAT) = 1");//;
        })
            ->join('MPOS', 'MPOS.WARPL', '=', 'MHIS.WARPL', 'left')
            ->join('PLKO', function($q) {
                $q->on('PLKO.PLNNR', '=', 'MPOS.PLNNR')
                ->on('PLKO.PLNAL', '=', 'MPOS.PLNAL');
            })
            ->join('PLAS', function($q) {
                $q->on('PLAS.PLNNR', '=', 'PLKO.PLNNR')
                ->on('PLAS.PLNAL', '=', 'PLKO.PLNAL')
                ->where('PLAS.PLNTY', '=', 'A')
                ->where('PLAS.LOEKZ', '!=', 'X');
            })
            ->join('PLPO', function($q) {
                $q->on('PLPO.PLNTY', '=', 'PLAS.PLNTY')
                ->on('PLPO.PLNNR', '=', 'PLAS.PLNNR')
                ->on('PLPO.PLNKN', '=', 'PLAS.PLNKN')
                ->on('PLPO.ZAEHL', '=', 'PLAS.ZAEHL');
            })
            ->join('ILOA', 'ILOA.ILOAN', '=', 'MPOS.ILOAN', 'left')
            ->join('IFLOTX', 'IFLOTX.TPLNR', '=', 'ILOA.TPLNR', 'left')
            ->join('CRHD', 'CRHD.OBJID', '=', 'MPOS.GEWRK', 'left')
            ->join('CRHD op', 'op.OBJID', '=', 'PLPO.ARBID', 'left')
            ->join('AFIH', function($j) {
                $j->on('AFIH.WARPL', '=', 'MPOS.WARPL')
                    ->on('AFIH.WAPOS', '=', 'MPOS.WAPOS')
                    ->on('AFIH.ABNUM', '=', 'MHIS.ABNUM');
            }, null, null, 'left')
            ->join('AFKO', 'AFKO.AUFNR', '=', 'AFIH.AUFNR', 'left')
            ->join('AUFK', 'AUFK.AUFNR', '=', 'AFIH.AUFNR', 'left')
            ->whereNotExists(function ($query) {
                $query->select('TJ02T.TXT04')
                    ->from('JEST')
                    ->join('TJ02T', 'JEST.STAT', '=', 'TJ02T.ISTAT')
                    ->whereColumn('JEST.OBJNR', 'MPLA.OBJNR')
                    ->where('JEST.INACT', '!=', 'X')
                    ->where('TJ02T.SPRAS', '=', 'E')
                    ->whereIn('TJ02T.TXT04', ['DLFL', 'INAC']);
            })
            ->where('MPOS.IWERK', '8000');
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
            ->whereColumn('JEST.OBJNR', 'MPLA.OBJNR');
    }

    protected static function systemStatus()
    {
        return
            DB::connection('oracle')->table('JEST')->select(
                DB::raw("LISTAGG(TJ02T.TXT04, ' ') WITHIN GROUP (ORDER BY TJ02T.TXT04)"))
                ->join('TJ02T', 'JEST.STAT', '=', 'TJ02T.ISTAT')
                ->where('JEST.INACT', '!=', 'X')
                ->where('TJ02T.SPRAS', '=', 'E')
                ->whereColumn('JEST.OBJNR', 'MPLA.OBJNR');
    }
}