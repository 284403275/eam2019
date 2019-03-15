<?php


namespace App\SAP\Scopes;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\DB;

class EquipmentScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $builder->select([
            DB::raw('EQUI.OBJNR AS "ojbect_num"'),
            DB::raw('EQUI.EQUNR AS "equipment"'),
            DB::raw('EQUZ.HEQUI AS "superior"'),
            DB::raw('ILOA.EQFNR AS "tag"'),
            DB::raw('CRHD.ARBPL AS "work_center"'),
            DB::raw('EQKT.EQKTX AS "description"'),
            DB::raw('ILOA.ABCKZ AS "abc"'),
            DB::raw('EQUI.EQART AS "object_type"'),
            DB::raw('ILOA.TPLNR AS "floc"'),
            DB::raw('IFLOTX.PLTXT AS "system"')
        ])
            ->addSelect(DB::raw("(" . self::userStatus()->toSql() . ") AS \"user_status\""))
            ->addBinding(self::userStatus()->getBindings(), 'select')
            ->addSelect(DB::raw("(" . static::systemStatus()->toSql() . ") AS \"system_status\""))
            ->addBinding(static::systemStatus()->getBindings(), 'select')
            ->join('EQUZ', function($q) {
                $q->on('EQUZ.EQUNR', '=', 'EQUI.EQUNR');
                $q->on('EQUZ.MANDT', '=', 'EQUI.MANDT');
                $q->where('EQUZ.DATBI', '=', '99991231');
            })
            ->join('EQKT', 'EQKT.EQUNR', '=', 'EQUI.EQUNR')
            ->join('ILOA', 'ILOA.ILOAN', '=', 'EQUZ.ILOAN')
            ->join('IFLOTX', 'IFLOTX.TPLNR', '=', 'ILOA.TPLNR')
            ->join('CRHD', 'CRHD.OBJID', '=', 'EQUZ.GEWRK')
            ->where('EQUI.EQTYP', '8')
            ->where('EQUI.MANDT', '210')
            ->where('ILOA.SWERK', '8000')
            ->whereExists(function ($query) {
                $query->select('TJ02T.TXT04')
                    ->from('JEST')
                    ->join('TJ02T', 'JEST.STAT', '=', 'TJ02T.ISTAT')
                    ->whereColumn('JEST.OBJNR', 'EQUI.OBJNR')
                    ->where('JEST.INACT', '!=', 'X')
                    ->where('TJ02T.SPRAS', '=', 'E')
                    ->whereIn('TJ02T.TXT04', ['INST', 'AVLB', 'ASEQ']);
            });
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
            ->whereColumn('JEST.OBJNR', 'EQUI.OBJNR');
    }

    private static function systemStatus()
    {
        return
            DB::connection('oracle')->table('JEST')->select(
                DB::raw("LISTAGG(TJ02T.TXT04, ' ') WITHIN GROUP (ORDER BY TJ02T.TXT04)"))
                ->join('TJ02T', 'JEST.STAT', '=', 'TJ02T.ISTAT')
                ->where('JEST.INACT', '!=', 'X')
                ->where('TJ02T.SPRAS', '=', 'E')
                ->whereColumn('JEST.OBJNR', 'EQUI.OBJNR');
    }
}