<?php


namespace App\SAP\Core;


class Parameters
{
    protected $parameters = [];

    public function addParameter($key, $value)
    {
        key_exists($key, $this->parameters) ? null : $this->parameters[$key] = [];

        if(is_array($value))
            $this->parameters[$key] = array_merge($this->parameters[$key], $value);
        else
            $this->parameters[$key] = $value;

        return $this;
    }

    public function addParameterArray(array $parameters)
    {
        array_merge($this->parameters, $parameters);

        return $this;
    }

    public function get()
    {
        return $this->parameters;
    }
}