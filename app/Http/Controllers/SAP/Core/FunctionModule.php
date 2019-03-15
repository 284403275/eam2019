<?php


namespace App\SAP\Core;


use App\SAP\Core\Exceptions\FunctionModuleParameterBindException;

class FunctionModule
{
    public $sap;

    protected $name;

    protected $fm;

    protected $attributes;

    protected $parameters = [];

    protected $response;

    public function __construct(SAP $sap, $name, $parameters = [])
    {
        $this->sap = $sap;

        $this->name = $name;

        $this->parameters = (new Parameters())->addParameterArray($parameters);

        $this->response = new SapResponse();

        $this->init();
    }

    protected function init()
    {
        $this->fm = $this->sap->getHandle()->getFunction($this->name);

        $this->attributes = collect(
            json_decode(
                json_encode($this->fm),
                true
            )
        )->except('name');
    }

    public function invoke()
    {
        $return = $this->fm->invoke($this->parameters->get());

        return $return;
    }

    public function commit()
    {
        $commit = $this->sap->getHandle()->getFunction('BAPI_TRANSACTION_COMMIT');
//        $commit->invoke()
//        $this->response->addResponse('commit', $commit->invoke([]));

        return $this;
    }

    public function response($key = null)
    {
        return $this->response->getResponse($key);
    }

    public function raw()
    {
        return $this->response->getRaw();
    }

    public function addParameter($param, $value)
    {
        $this->hasAttribute($param);

        $this->parameters->addParameter($param, $value);

        return $this;
    }

    protected function hasAttribute($value)
    {
        if (!$this->attributes->has($value))
            throw new FunctionModuleParameterBindException('Function module ' . $this->name . ' does not contain the attribute ' . $value);
    }
}