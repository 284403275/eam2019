<?php


namespace App\SAP\Models;


use App\SAP\Scopes\PmCmRatioScope;
use App\SAP\Traits\WorkOrderTraits;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PmCmRatio extends Model
{
    use WorkOrderTraits;

    protected $connection = 'oracle';

    protected $table = 'AFIH';

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new PmCmRatioScope());
    }

    public function scopeEquipment($query, $eq)
    {
        $query->where(function($q) use ($eq) {
            $q->whereIn('AFIH.EQUNR', $eq)
                ->orWhereIn('AFVC.EQUNR', $eq);
        });
//        $query->whereIn('AFIH.EQUNR', $eq);
    }

    public function scopeForYear($query, $year)
    {
        $date = Carbon::createFromFormat('Y', $year);

        $query->whereBetween('AUFK.ERDAT', [$date->firstOfYear()->format('Ymd'), $date->lastOfYear()->format('Ymd')]);
    }

    public function scopeRunningYear($query)
    {
        $date = Carbon::now();

        $query->whereBetween('AUFK.ERDAT', [$date->subYear()->format('Ymd'), $date->format('Ymd')]);
    }
}