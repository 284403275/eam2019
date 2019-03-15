<?php


namespace App\SAP\Models;


use App\SAP\Scopes\WorkOrdersWithOperationsScope;
use App\SAP\Traits\WorkOrderTraits;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static onlyRequiredOperations()
 */
class WorkOrderOperations extends Model
{
    use WorkOrderTraits;

    protected $connection = 'oracle';

    protected $table = 'AFIH';

    protected $primaryKey = 'AFIH.AUFNR';

    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new WorkOrdersWithOperationsScope());
    }
}