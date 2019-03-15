<?php


namespace App\SAP\Traits;


use Illuminate\Database\Eloquent\Builder;

trait WorkOrderTraits
{
    public function scopeBasicStartDateBetween($query, $start, $end)
    {
        return $query->whereBetween('AFKO.GSTRP', [$start, $end]);
    }

    public function scopeFindOrder($query, $order)
    {
        return $query->where('AUFK.AUFNR', $order);
    }

    public function scopeFindOrders($query, $orders)
    {
        return $query->whereIn('AUFK.AUFNR', $orders);
    }

    public function scopeOrderTypeIn($query, array $types)
    {
        return $query->whereIn('AUFK.AUART', $types);
    }

    public function scopeUserStatus($query, array $statuses, $boolean = 'and', $not = false)
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
                ->whereColumn('JEST.OBJNR', 'AUFK.OBJNR')
                ->whereIn('TJ30T.TXT04', $statuses);
        }, $boolean, $not);
    }

    public function scopeUserStatusExcludes($query, array $statuses, $boolean = 'and')
    {
        return $this->scopeUserStatus($query, $statuses, $boolean, true);
    }

    public function scopeSystemStatus($query, array $statuses, $boolean = 'and', $not = false)
    {
        return $query->whereExists(function ($query) use ($statuses) {
            $query->select('TJ02T.TXT04')
                ->from('JEST')
                ->join('TJ02T', 'JEST.STAT', '=', 'TJ02T.ISTAT')
                ->whereColumn('JEST.OBJNR', 'AUFK.OBJNR')
                ->where('JEST.INACT', '!=', 'X')
                ->where('TJ02T.SPRAS', '=', 'E')
                ->whereIn('TJ02T.TXT04', $statuses);
        }, $boolean, $not);
    }

    public function scopeSystemStatusExcludes($query, array $statuses, $boolean = 'and')
    {
        return $this->scopeSystemStatus($query, $statuses, $boolean, true);
    }

    public function scopeOperationSystemStatus($query, array $statuses, $boolean = 'and', $not = false)
    {
        return $query->whereExists(function ($query) use ($statuses) {
            $query->select('TJ02T.TXT04')
                ->from('JEST')
                ->join('TJ02T', 'JEST.STAT', '=', 'TJ02T.ISTAT')
                ->whereColumn('JEST.OBJNR', 'AFVC.OBJNR')
                ->where('JEST.INACT', '!=', 'X')
                ->where('TJ02T.SPRAS', '=', 'E')
                ->whereIn('TJ02T.TXT04', $statuses);
        }, $boolean, $not);
    }

    public function scopeOperationSystemStatusExcludes($query, array $statuses, $boolean = 'and')
    {
        return $this->scopeOperationSystemStatus($query, $statuses, $boolean, true);
    }

    public function scopeOnlyRequiredOperations($query)
    {
        return $query->whereIn('AFVC.STEUS', ['8F01']);
    }

    /**
     * Run the backlog query
     *
     * @method backlog() backlog(string $date)
     * @param $query
     * @param $date
     * @return Builder
     * @internal param array|mixed $column
     */
    public function scopeBacklog($query, $date): Builder
    {
        return $query->where(function ($q) use ($date) {
            $q->where('AFKO.GLTRP', '<', $date)
                ->orWhereNull('AFKO.GLTRP');
        })->orderTypeIn(['8F01', '8F02', '8F03', '8F06'])
            ->where(function ($q) {
                $q->systemStatusExcludes(['DLFL', 'CNF', 'TECO', 'CLSD'])->systemStatus(['REL']);
            })
            ->where(function ($q) {
                $q->userStatusExcludes(['CNCL']);
            });
    }
}