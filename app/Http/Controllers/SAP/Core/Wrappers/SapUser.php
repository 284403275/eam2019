<?php


namespace App\SAP\Core\Wrappers;




use App\SAP\Core\Bapis\BAPI_USER_CHANGE;
use App\SAP\Core\Bapis\BAPI_USER_UNLOCK;
use App\SAP\Core\Bapis\ME_USER_CHANGE_PASSWORD;
use App\SAP\Core\SAP;

class SapUser
{
    protected $sap;

    public function __construct(SAP $sap)
    {
        $this->sap = $sap;
    }

    public function unlock($username)
    {
        return (new BAPI_USER_UNLOCK($this->sap))->unlock($username);
    }

    public function resetPassword($username, $password = 'Global123')
    {
        return (new BAPI_USER_CHANGE($this->sap))->resetPassword($username, $password);
    }

    public function setPassword($username, $old, $new)
    {
        return (new ME_USER_CHANGE_PASSWORD($this->sap))->resetPassword($username, $old, $new);
    }
}