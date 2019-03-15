<?php


namespace App\SAP\Models;


use App\BaseModel;
use App\SAP\Scopes\ConfirmationScope;
use App\SAP\Traits\ConfirmationTrait;

class Confirmation extends BaseModel
{
    use ConfirmationTrait;

    protected $connection = 'oracle';

    protected $table = 'AFRU';

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new ConfirmationScope());
    }
}