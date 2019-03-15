<?php


namespace App\SAP\Models;


use App\SAP\Scopes\MaintenanceScoreScope;
use Illuminate\Database\Eloquent\Model;

class MaintenanceScore extends Model
{
    protected $connection = 'oracle';

    protected $table = 'AUFK';

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new MaintenanceScoreScope());
    }

    public function scopeBetween($query, $start, $end)
    {
        return $query->where('AFKO.GETRI', '>=', $start)->where('AFKO.GETRI', '<=', $end);
    }

    public function scopeOrder($query, $order)
    {
        return $query->where("AUFK.AUFNR", $order);
    }
}