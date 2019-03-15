<?php


namespace App\SAP\Core\Bapis;


use App\SAP\Core\SAP;
use App\Services\GlobalUser;
use Illuminate\Support\Carbon;

class Confirmations extends SAP
{
    protected $fm;

    protected $globalUser;

    public function __construct()
    {
        $this->globalUser = new GlobalUser();
    }

    public function find($order)
    {
        $this->fm = $this->fm('Z_UI5_8FWEX_GET_TIME_CONFS');

        $this->fm->addParameter('IV_WORKORDER', $order);

        return $this->get('ET_TIME_CONFS')->results();
    }

    public function toArray($item)
    {
        return [
            'confirmation_id' => $item['CONF_NO'],
            'confirmation_count' => $item['CONF_CNT'],
            'operation' => $item['OPERATION'],
            'user_name' => trim($item['USER_NAME']),
            'created_by' => $this->globalUser->findOrCreateByAccount(trim($item['CREATED_BY']))->only(['account', 'first_name', 'last_name', 'email', 'avatar']),
            'created_on' => Carbon::parse($item['CREATED_DATE'] . ' ' . $item['CREATED_TIME'])->format('l, F jS Y \\@ h:i:s A'),
            'is_final' => $item['FIN_CONF'],
            'work' => $item['ACT_WORK_2'],
            'work_uom' => $item['UN_WORK_2']
        ];
    }
}