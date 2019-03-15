<?php


namespace App;


use Illuminate\Database\Eloquent\Model;

class Forecast extends Model
{
    protected $connection = 'sqlsrv';

    protected $table = 'SAPPM_SchView_Conv';
}