<?php


namespace App\SAP\Models;


use App\SAP\Scopes\EquipmentScope;
use App\SAP\Traits\EquipmentTrait;
use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    use EquipmentTrait;

    protected $connection = 'oracle';

    protected $table = 'EQUI';

    protected $primaryKey = 'EQUI.EQUNR';

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new EquipmentScope());
    }
}