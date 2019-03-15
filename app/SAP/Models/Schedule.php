<?php


namespace App\SAP\Models;



use App\SAP\Scopes\ScheduleScope;
use App\SAP\Traits\WorkOrderTraits;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use WorkOrderTraits;

    protected $connection = 'oracle';

    protected $table = 'AFIH';

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new ScheduleScope());
    }

    public function partners()
    {
        return $this->hasMany(Partners::class, 'objnr', 'objnr');
    }
}