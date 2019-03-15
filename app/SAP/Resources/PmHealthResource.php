<?php


namespace App\SAP\Resources;


use Illuminate\Http\Resources\Json\Resource;

class PmHealthResource extends Resource
{
    public function toArray($request)
    {
        return [
            'order' => $this->order
        ];
    }
}