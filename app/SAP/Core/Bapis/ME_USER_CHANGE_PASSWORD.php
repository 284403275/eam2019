<?php


namespace App\SAP\Core\Bapis;



use App\SAP\Core\SAP;
use ReflectionClass;

class ME_USER_CHANGE_PASSWORD
{
    protected $bapi;

    public function __construct(SAP $sap)
    {
        $this->bapi = $sap->fm((new ReflectionClass($this))->getShortName());
    }

    public function resetPassword($username, $old, $new)
    {
        return $this->bapi->addParameter('USERNAME', $username)
            ->addParameter('PASSWORD', $old)
            ->addParameter('NEW_PASSWORD', $new)
            ->invoke();
    }
}