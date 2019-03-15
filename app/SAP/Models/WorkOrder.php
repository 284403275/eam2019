<?php


namespace App\SAP\Models;


use App\BaseModel;
use App\SAP\Scopes\WorkOrderScope;
use App\SAP\Traits\WorkOrderTraits;

class WorkOrder extends BaseModel
{
    use WorkOrderTraits;

    protected $connection = 'oracle';

    protected $table = 'AFIH';

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new WorkOrderScope());
    }
}