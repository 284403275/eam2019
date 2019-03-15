<?php


namespace App\SAP\Models;


use App\SAP\Scopes\PmComplianceQueryScope;
use Illuminate\Database\Eloquent\Model;

class Compliance extends Model
{

    protected $connection = 'oracle';

    protected $table = 'AFIH';

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new PmComplianceQueryScope());
    }
}