<?php


namespace App\SAP\Core\Bapis;


use App\SAP\Core\SAP;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Operations extends SAP
{
    protected $fm;

    public function withOperations()
    {

    }

    public function find($order)
    {
        $order = ltrim($order, '0');

        $this->fm = $this->fm('BAPI_ALM_ORDEROPER_GET_LIST');

        $this->fm->addParameter('IT_RANGES', [['FIELD_NAME' => 'SHOW_OPEN_DOCUMENTS', 'LOW_VALUE' => 'X']]);
        $this->fm->addParameter('IT_RANGES', [['FIELD_NAME' => 'SHOW_COMPLETED_DOCUMENTS', 'LOW_VALUE' => 'X']]);
        $this->fm->addParameter('IT_RANGES', [['FIELD_NAME' => 'OPTIONS_FOR_PLANT', 'LOW_VALUE' => '8000']]);

        $this->fm->addParameter('IT_RANGES', [['FIELD_NAME' => 'OPTIONS_FOR_ORDERID', 'LOW_VALUE' => '000' . $order]]);

        return $this->get()->results();
    }

    public function toArray($item)
    {
        $sys_status = collect(explode(' ', $item['S_STATUS']))->filter()->values();
        $user_status = collect(explode(' ', $item['U_STATUS']))->filter()->values();

        return [
            'order' => $item['ORDERID'],
            'parent' => $item['ORDERID'],
            'id' => $item['ORDERID']. '-' . $item['ACTIVITY'],
            'operation_work_center' => trim($item['WORK_CNTR']),
            'control_key' => $item['CONTROL_KEY'],
            'type' => $item['ORDER_TYPE'],
            'priority' => $item['PRIOTYPE_DESC'],
            'operation' => $item['ACTIVITY'],
            'operation_description' => trim($item['DESCRIPTION']),
            'operation_system_status' => $sys_status,
            'operation_user_status' => $user_status,
            'status' => $this->statusText($sys_status),
            'status_color' => $this->statusColor($sys_status),
            'table_row_class' => $this->rowColor($sys_status),
            'early_start_date' => $item['EARL_SCHED_START_DATE'],
            'early_start_time' => $item['EARL_SCHED_START_TIME'],
            'early_finish_date' => $item['EARL_SCHED_FINISH_DATE'],
            'early_finish_time' => $item['EARL_SCHED_FINISH_TIME'],
            'sched_start' => Carbon::parse($item['EARL_SCHED_START_DATE'] . ' ' . $item['EARL_SCHED_START_TIME'])->format('Y-m-d H:i:s'),
            'sched_finish' => Carbon::parse($item['EARL_SCHED_FINISH_DATE'] . ' ' . $item['EARL_SCHED_FINISH_TIME'])->format('Y-m-d H:i:s'),
            'actual_start_date' => $item['ACTUAL_START_DATE'],
            'actual_start_time' => $item['ACTUAL_START_TIME'],
            'actual_finish_date' => $item['ACTUAL_FIN_DATE'],
            'actual_finish_time' => $item['ACTUAL_FIN_TIME'],
            'equipment' => $item['EQUIPMENT'],
            'tag_id' => trim($item['SORTFIELD']),
            'duration' => $item['DURATION_NORMAL'],
            'man_hours' => $item['WORK_ACTIVITY'],
            'capacity' => $item['NUMBER_OF_CAPACITIES'],
            'actual_duration' => $item['WORK_ACTUAL']
        ];
    }

    public function statusText(Collection $status)
    {
        if($status->contains('CNF'))
            return 'Complete';

        if($status->contains('PCNF'))
            return 'In Progress';

        if($status->contains('REL'))
            return 'Released';

        if($status->contains('CRTD'))
            return 'Created';

        return 'Unknown';
    }

    public function statusColor(Collection $status)
    {
        if($status->contains('CNF'))
            return 'green-800';

        if($status->contains('PCNF'))
            return 'green';

        if($status->contains('REL'))
            return 'info-800';

        if($status->contains('CRTD'))
            return 'teal-800';

        return 'Unknown';
    }

    public function rowColor(Collection $status)
    {
        if($status->contains('CNF'))
            return 'table-success';

        return null;
    }
}