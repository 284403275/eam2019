<?php


namespace App\SAP\Resources;


use Carbon\Carbon;
use Illuminate\Http\Resources\Json\Resource;

class ScheduleResource extends Resource
{
    protected $systemStatus;

    protected $userStatus;

    public function toArray($request)
    {
        $this->userStatus = collect(explode(' ', $this->user_status));

        $this->systemStatus = collect(explode(' ', $this->system_status));


        if($this->cycle)
            $title = $this->tag_id . ' (' . $this->cycleToText($this->cycle) . ') - ' . $this->description;
        else
            $title = $this->tag_id . ' - ' . $this->description;

        return [
            'order' => $this->order,
            'tag_id' => $this->tag_id,
            'description' => $this->description,
            //'main_work_center' => $this->main_work_center,
            'op_work_center' => $this->op_work_center,
            //'duration' => $this->duration,
            //'basic_start_date' => $this->basic_start_date,
            //'basic_start_time' => $this->basic_start_time,
            //'window_start' => $this->window_start,
            //'planned_date' => $this->planned_date,
            //'window_end' => $this->window_end,
            //'can_edit' => $this->can_edit,
            //'cycle' => $this->cycle,
            'user_status' => $this->userStatus->toArray(),
            'system_status' => $this->systemStatus->toArray(),
            'start_date' => Carbon::parse($this->calcStartDate())->toIso8601String(),//->format('Y-m-d'),
            'type' => $this->order_type,
            'start' => $this->calcStartDate(),
            'end' => $this->calcEndDate(),
            'title' => $title,
            'editable' => $this->canEdit(),
            'color' => $this->eventColor(),
            'durationEditable' => false,
            'is_future' => false,
            'is_complete' => $this->isComplete(),
            'resourceIds' => $this->buildResources($this->partners)
        ];
    }

    protected function buildResources($resources)
    {
        $resources = array_map(function($item) {
            return ltrim($item, '0');
        }, explode(',', $resources));

        if(empty($resources[0]))
            return [];
        return $resources;
    }

    protected function cycleToText($cycle)
    {
        if($cycle > 0 && $cycle <= 1)
            return 'D';
        elseif($cycle > 1 && $cycle <= 7)
            return 'W';
        elseif($cycle > 7 && $cycle <= 14)
            return 'BW';
        elseif($cycle > 14 && $cycle <= 30)
            return 'M';
        elseif($cycle > 30 && $cycle <= 90)
            return 'Q';
        elseif ($cycle > 90 && $cycle <= 180)
            return 'SA';
        elseif ($cycle > 180 && $cycle <= 365)
            return 'A';
        elseif ($cycle > 365 && $cycle <= 730)
            return 'BA';
        elseif ($cycle > 730 && $cycle <= 1190)
            return '3Y';
        elseif ($cycle > 1190 && $cycle <= 1556)
            return '4Y';
        elseif ($cycle > 1556 && $cycle <= 2287)
            return '5Y';

        return $cycle;
    }

    protected function isComplete()
    {
        return $this->systemStatus->contains('CNF') || $this->systemStatus->contains('TECO') || $this->systemStatus->contains('CLSD');
    }

    protected function calcStartDate()
    {
        if($this->isClosed())
            return Carbon::parse($this->confirmed_finish_date . ' ' . $this->confirmed_finish_time)->format('Y-m-d H:i:s');
        return Carbon::parse($this->basic_start_date . ' ' . $this->basic_start_time)->format('Y-m-d H:i:s');
    }

    protected function calcEndDate()
    {
        if($this->isClosed())
            return Carbon::parse($this->confirmed_finish_date . ' ' . $this->confirmed_finish_time)->addMinutes($this->actual_work * 60)->format('Y-m-d H:i:s');
        return Carbon::parse($this->basic_start_date . ' ' . $this->basic_start_time)->addMinutes($this->duration * 60)->format('Y-m-d H:i:s');
    }

    protected function isClosed()
    {
        return ($this->systemStatus->contains('CNF') || $this->systemStatus->contains('TECO') || $this->systemStatus->contains('CLSD')) && !$this->userStatus->contains('CNCL');
    }

    protected function canEdit()
    {
        if(!collect(config('sap.users'))->contains(auth()->user()->account))
            return false;

        if ($this->systemStatus->contains('CNF') || $this->systemStatus->contains('CLSD'))
            return false;

        return $this->main_work_center === auth()->user()->work_center;
    }

    protected function eventColor()
    {

        if ($this->systemStatus->contains('CNF') && !$this->systemStatus->contains('TECO'))
            return '#AED581';

        if (($this->systemStatus->contains('CNF') && $this->systemStatus->contains('TECO')) || $this->systemStatus->contains('CLSD'))
            return '#558B2F';

        if ($this->systemStatus->contains('CRTD'))
            return '#29B6F6';

        if($this->main_work_center != $this->op_work_center)
            return '#7986CB';


        if($this->order_type == '8F01')
            return '#795548';
        if($this->order_type == '8F02')
            return '#FF9800';
        if($this->order_type == '8F03')
            return '#777777';
        return '#607D8B';
    }
}