<?php


namespace App\SAP\Core\Bapis;



use App\SAP\Core\SAP;
use ReflectionClass;

class BAPI_USER_UNLOCK
{
    protected $bapi;

    public function __construct(SAP $sap)
    {
        try {
            $this->bapi = $sap->fm((new ReflectionClass($this))->getShortName());
        } catch (\ReflectionException $e) {
        }
    }

    public function unlock($username)
    {
        return $this->bapi->addParameter('USERNAME', $username)->invoke();
    }
}