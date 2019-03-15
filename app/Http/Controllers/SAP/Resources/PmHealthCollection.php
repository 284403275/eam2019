<?php


namespace App\SAP\Resources;


use Illuminate\Http\Resources\Json\ResourceCollection;

class PmHealthCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return  PmHealthResource::collection($this->collection);
    }
}