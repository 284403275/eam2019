<?php


namespace App\SAP\Resources;


use Illuminate\Http\Resources\Json\ResourceCollection;

class ScheduleCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return  ScheduleResource::collection($this->collection);
    }
}