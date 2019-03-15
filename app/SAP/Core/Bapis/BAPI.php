<?php


namespace App\SAP\Core\Bapis;


use App\SAP\Core\FunctionModule;
use App\SAP\Core\SAP;
use Illuminate\Support\Collection;

abstract class BAPI extends FunctionModule
{
    public $bapi;

    protected $parameters;

    protected $results;

    protected $meta = [
        'current_page' => 1,
        'per_page' => 50
    ];

    protected $sap;

    protected $fm;

    public function __construct(SAP $sap)
    {
        $this->sap = $sap;

        $this->fm = $this->sap->fm($this->bapi);
    }

    public function setDefaults() {
        return $this;
    }

    public function addParameter($parameter, $value)
    {
        $this->fm->addParameter($parameter, $value);

        return $this;
    }

    public function invoke()
    {
        $this->setDefaults();

        return $this->fm->invoke();
    }

    public function limit($limit)
    {
        $this->meta['per_page'] = $limit;

        return $this;
    }

    public function page($page)
    {
        $this->meta['page'] = $page;

        return $this;
    }

    public function get()
    {
        $this->addDisplayParameter('PAGELENGTH', $this->meta['per_page']);
        $this->addDisplayParameter('SHOW_PAGE_NUMBER', $this->meta['current_page']);

        return $this->transform($this->fm($this->bapi, $this->parameterBag->toArray())->call());
    }

    protected function transform($items)
    {
        $this->meta = [
            'total' => $items['NAVIGATION_DATA']['NUMBER_OF_HITS'],
            'from' => $items['NAVIGATION_DATA']['FIRST_SHOWN_HIT'],
            'last_page' => $items['NAVIGATION_DATA']['LAST_PAGE'],
            'per_page' => $this->meta['per_page'],
            'to' => $items['NAVIGATION_DATA']['LAST_SHOWN_HIT'],
            'current_page' => $items['NAVIGATION_DATA']['ACTUAL_PAGE']
        ];

        $this->results = (new Collection($items['ET_RESULT']))->map(function($item) {
            return $this->toArray($item);
        });

        return $this;
    }

    public function toArray($item)
    {
        return $item;
    }

    public function items() : Collection
    {
        return $this->results;
    }

    public function append($index, $key, $item)
    {
        dd($index, $key, $item, $this->results[$index]['Test'] = 'wtf');
        $this->results[$index][$key] = $item;
    }
}