<?php


namespace App\SAP\Traits;


use Illuminate\Support\Facades\DB;

trait EquipmentTrait
{
    public function scopeEquipmentCount($query)
    {
        return $query->select([
            DB::raw('COUNT(*) AS "count"'),
            DB::raw('CRHD.ARBPL AS "work_center"'),
        ])->join('EQUZ', function($q) {
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
            })
            ->groupBy('CRHD.ARBPL');
    }

    public function scopeAbc($query, array $values)
    {
        return $query->whereIn('ILOA.ABCKZ', $values);
    }

    public function scopeWithAbc($query)
    {
        return $query->addSelect([DB::raw('ILOA.ABCKZ AS "abc"')])->groupBy('ILOA.ABCKZ');
    }

    public function scopeWhereFloc($query, $floc)
    {
        return $query->where('ILOA.TPLNR', $floc);
    }

    public function scopeWhereFlocsIn($query, array $flocs)
    {
        return $query->whereIn('ILOA.TPLNR', $flocs);
    }

    public function scopeHasUserStatus($query, array $statuses, $boolean = 'and', $not = false)
    {
        return $query->whereExists(function ($query) use ($statuses) {
            $query->select('TJ30T.TXT04')
                ->from('JEST')
                ->join('JSTO', 'JSTO.OBJNR', '=', 'JEST.OBJNR')
                ->join('TJ30T', function ($q) {
                    $q->on('JSTO.STSMA', '=', 'TJ30T.STSMA')
                        ->on('JEST.STAT', '=', 'TJ30T.ESTAT');
                })
                ->where('JEST.INACT', '!=', 'X')
                ->where('TJ30T.SPRAS', '=', 'E')
                ->whereColumn('JEST.OBJNR', 'EQUI.OBJNR')
                ->whereIn('TJ30T.TXT04', $statuses);
        }, $boolean, $not);
    }

    public function scopeStatusDashboard($query)
    {
        return $query->select([
            DB::raw('ILOA.EQFNR AS "tag"'),
            DB::raw('EQKT.EQKTX AS "description"'),
        ])->addSelect(DB::raw("(" . self::openOrders()->toSql() . ") AS \"open_orders\""))
            ->addBinding(static::openOrders()->getBindings(), 'select')
            ->addSelect(DB::raw("(" . self::userStatus()->toSql() . ") AS \"user_status\""))
            ->addBinding(self::userStatus()->getBindings(), 'select')
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
            })
            ->whereIn('CRHD.ARBPL', ['FMG_P', 'MIC_P'])
            ;
    }

    private static function openOrders()
    {
        return DB::connection('oracle')->table('AFIH')->select(
            DB::raw("LISTAGG(AFIH.AUFNR, ' ') WITHIN GROUP (ORDER BY AFIH.AUFNR)"))
            ->join('AUFK', 'AUFK.AUFNR', '=', 'AFIH.AUFNR')
            ->whereColumn('AFIH.EQUNR', 'EQUI.EQUNR')
            ->whereExists(function ($query) {
                $query->select('TJ02T.TXT04')
                    ->from('JEST')
                    ->join('TJ02T', 'JEST.STAT', '=', 'TJ02T.ISTAT')
                    ->whereColumn('JEST.OBJNR', 'AUFK.OBJNR')
                    ->where('JEST.INACT', '!=', 'X')
                    ->where('TJ02T.SPRAS' ,'=', 'E')
                    ->whereIn("TJ02T.TXT04", ['NEW', 'REL']);
            })->whereNotExists(function ($query) {
                $query->select('TJ02T.TXT04')
                    ->from('JEST')
                    ->join('TJ02T', 'JEST.STAT', '=', 'TJ02T.ISTAT')
                    ->whereColumn('JEST.OBJNR', 'AUFK.OBJNR')
                    ->where('JEST.INACT', '!=', 'X')
                    ->where('TJ02T.SPRAS', '=', 'E')
                    ->whereIn("TJ02T.TXT04", ['CNF', 'TECO']);
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
}