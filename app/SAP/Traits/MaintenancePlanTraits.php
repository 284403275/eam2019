<?php


namespace App\SAP\Traits;


trait MaintenancePlanTraits
{
    public function scopeWherePlannedDateBetween($query, $start, $end)
    {
        return $query->whereBetween('MHIS.NPLDA', [$start, $end]);
    }

    public function scopeHasNotBeenCalled($query)
    {
        return $query->whereNull('AFIH.AUFNR');
    }

    public function scopeOnlyRequiredOperations($query)
    {
        return $query->whereIn('PLPO.STEUS', ['8F01']);
    }
}