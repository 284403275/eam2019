<?php


namespace App\SAP\Core\Bapis;


use App\SAP\Core\SAP;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class WorkOrder extends SAP
{
    protected $fm;

    protected $order;

    public function find($order)
    {
        $this->fm = $this->fm('BAPI_ALM_ORDERHEAD_GET_LIST');

        $this->fm->addParameter('IT_RANGES', [['FIELD_NAME' => 'OPTIONS_FOR_ORDERID', 'LOW_VALUE' => $order]]);
        $this->fm->addParameter('IT_RANGES', $this->setDefaults());

        return $this->get();
    }

    public function comments()
    {
        $this->results()->transform(function($object) {
            $object['comments'] = (new NotificationComments())->find($object['notification']);

            return $object;
        });

        return $this;
    }

    public function operations()
    {
        $this->results()->transform(function($object) {
            $object['operations'] = (new Operations())->find(ltrim($object['order'], 0));

            return $object;
        });

        return $this;
    }

    public function confirmations()
    {
        $this->results()->transform(function($object) {
            $object['confirmations'] = (new Confirmations())->find($object['order']);

            return $object;
        });

        return $this;
    }

    public function set($id)
    {
        $this->order = $id;

        return $this;
    }

    public function setRelations()
    {

    }

    public function updateDates(array $dates)
    {
        $this->fm = $this->fm('BAPI_ALM_ORDER_MAINTAIN');

        $this->fm->addParameter('IT_METHODS',[
            [
                'REFNUMBER' => '1',
                'OBJECTTYPE' => 'HEADER',
                'METHOD' => 'CHANGE',
                'OBJECTKEY' => $this->order
            ],
            [
                'REFNUMBER' => '1',
                'OBJECTTYPE' => '',
                'METHOD' => 'SAVE',
                'OBJECTKEY' => $this->order
            ]
        ]);

        $this->fm->addParameter('IT_HEADER', [
            [
                'ORDERID' => $this->order,
                'START_DATE' => $dates['start_date'],
                'BASICSTART' => $dates['start_time'],
                'FINISH_DATE' => $dates['end_date'],
                'BASIC_FIN' => $dates['end_time']
            ]
        ]);

        $this->fm->addParameter('IT_HEADER_UP', [
            [
                'START_DATE' => 'X',
                'BASICSTART' => 'X',
                'FINISH_DATE' => 'X',
                'BASIC_FIN' => 'X'
            ]
        ]);

        return $this;
    }

    protected function setDefaults()
    {
        return [
            [
                'FIELD_NAME' => 'SHOW_OPEN_DOCUMENTS',
                'LOW_VALUE' => 'X'
            ],
            [
                'FIELD_NAME' => 'SHOW_DOCUMENTS_IN_PROCESS',
                'LOW_VALUE' => 'X'
            ],
            [
                'FIELD_NAME' => 'SHOW_COMPLETED_DOCUMENTS',
                'LOW_VALUE' => 'X'
            ],
            [
                'FIELD_NAME' => 'SHOW_HISTORICAL_DOCUMENTS',
                'LOW_VALUE' => 'X'
            ],
            [
                'FIELD_NAME' => 'SHOW_DOCS_WITH_FROM_DATE',
                'LOW_VALUE' => '00010101'
            ],
            [
                'FIELD_NAME' => 'SHOW_DOCS_WITH_TO_DATE',
                'LOW_VALUE' => '99991231'
            ]
        ];
    }

    public function toArray($item)
    {
        $user_status = collect(explode(' ', $item['U_STATUS']))->filter()->values();
        $system_status = collect(explode(' ', $item['S_STATUS']))->filter()->values();

        return [
            'order' => $item['ORDERID'],
            'id' => $item['ORDERID'],
            'notification' => $item['NOTIF_NO'],
            'main_work_center' => trim($item['MN_WK_CTR']),
            'type' => $item['ORDER_TYPE'],
            'type_text' => orderType($item['ORDER_TYPE']),
            'title' => trim($item['SHORT_TEXT']),
            'text' => trim($item['SHORT_TEXT']),
            'abc' => $item['ABCINDIC'],
            'priority' => $item['PRIORITY'],
            'tag_id' => trim($item['SORTFIELD']),
            'maintenance_plan' => $item['MAINTPLAN'],
            'user_status' => $user_status,
            'system_status' => $system_status,
            'can_release' => $this->canRelease($system_status),
            'created_on' => $item['ENTER_DATE'],
            'last_edited' => $item['CHANGE_DATE'],
            'actual_start_date' => $item['ACTUAL_START_DATE'],
            'actual_start_time' => $item['ACTUAL_START_TIME'],
            'actual_finish_date' => $item['CONFIRMED_FINISH_DATE'],
            'actual_finish_time' => $item['ACTUAL_FINISH_TIME'],
            'basic_start' => Carbon::parse($item['START_DATE'] . ' ' . $item['BASICSTART'])->format('Y-m-d H:i:s'),
            'basic_finish' => Carbon::parse($item['FINISH_DATE'] . ' ' . $item['BASIC_FIN'])->format('Y-m-d H:i:s'),
            'basic_start_date' => $item['START_DATE'],
            'basic_finish_date' => $item['FINISH_DATE'],
            'basic_start_time' => $item['BASICSTART'],
            'basic_finish_time' => $item['BASIC_FIN'],
            'scheduled_start_date' => $item['PRODUCTION_START_DATE'],
            'scheduled_start_time' => $item['PRODUCTION_START_TIME'],
            'scheduled_finish_date' => $item['PRODUCTION_FINISH_DATE'],
            'scheduled_finish_time' => $item['PRODUCTION_FINISH_TIME']
        ];
    }

    protected function canRelease(Collection $status) {
        return $status->contains('CRTD');
    }
}