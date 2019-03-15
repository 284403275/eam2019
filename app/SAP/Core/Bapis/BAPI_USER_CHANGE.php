<?php


namespace App\SAP\Core\Bapis;


use App\SAP\Core\SAP;
use ReflectionClass;

class BAPI_USER_CHANGE
{
    protected $bapi;

    public function __construct(SAP $sap)
    {
        $this->bapi = $sap->fm((new ReflectionClass($this))->getShortName());
    }

    public function resetPassword($username, $password)
    {
        return $this->bapi->addParameter('USERNAME', $username)
            ->addParameter('PASSWORD', ['BAPIPWD' => $password])
            ->addParameter('PASSWORDX', ['BAPIPWD' => 'X'])
            ->invoke();
    }
}