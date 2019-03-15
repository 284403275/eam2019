<?php


namespace App\SAP\Traits;


use Carbon\Carbon;

trait ConfirmationTrait
{
    /*
     * Date the user entered the confirmation
     */
    public function scopeEnteredBetween($query, array $dates)
    {
        return $query->whereBetween('AFRU.ERSDA', $dates);
    }

    /*
     * When the user actually said they did the work
     */
    public function scopeActuallyCompletedBetween($query, Carbon $start, Carbon $end)
    {
        return $query->whereRaw('AFRU.IEDD || AFRU.IEDZ >= ' . $start->format('YmdHis'))
            ->whereRaw('AFRU.IEDD || AFRU.IEDZ <= ' . $end->format('YmdHis'));
    }

    public function scopeWorkCenters($query, array $wc)
    {
        return $query->whereIn('CRHD.ARBPL', $wc);
    }

    public function scopeOnlyRequiredOperations($query)
    {
        return $query->whereIn('AFVC.STEUS', ['8F01']);
    }

    public function scopeFindOrder($query, $order)
    {
        return $query->where('AFIH.AUFNR', $order);
    }
}