<?php


namespace App\SAP\Models;


use App\SAP\Scopes\SingleCyclePlanScope;
use App\SAP\Traits\MaintenancePlanTraits;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static onlyRequiredOperations()
 * @method static wherePlannedDateBetween()
 */
class SingleCyclePlans extends Model
{
    use MaintenancePlanTraits;

    protected $connection = 'oracle';

    protected $table = 'MHIS';

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new SingleCyclePlanScope());
    }
}