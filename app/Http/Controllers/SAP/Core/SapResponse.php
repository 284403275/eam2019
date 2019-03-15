<?php


namespace App\SAP\Core;


class SapResponse
{
    protected $bag = [];


    public function add($response)
    {
        if (key_exists('RETURN', $response)) {
            foreach ($response['RETURN'] as $line) {
                if (is_array($line)) {
                    if (key_exists('TYPE', $line))
                        if ($line['TYPE'] == 'S') {
                            $this->bag['success'] = true;
                        }
                    $this->bag['text'][] = trim($line['MESSAGE']);
                }
            }
        } else {
            $this->bag[] = $response;
        }
        return $this;
    }

    public function get()
    {
        return $this->bag;
    }
}