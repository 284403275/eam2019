<?php


namespace App\SAP\Core;

use App\Connection;
use Illuminate\Support\Collection;
use SAPNWRFC\Connection as SapConnection;

class SAP
{
    protected $config;

    protected $handle;

    protected $results;

    protected $meta = [
        'current_page' => 1,
        'per_page' => 50
    ];

    public function __construct($connection = null)
    {
        $con = $connection ? $connection : config('sap.default.connection');

        $this->config = config('sap.connections.' . $con);
    }

    public function connect($username, $password, $connection = null)
    {
        if ($connection) {
            $this->config = config('sap.connections.' . $connection);
        }

        try {
            $this->handle = new SapConnection(array_merge($this->config, [
                'user' => $username,
                'passwd' => $password
            ]));
        } catch (\Exception $exception) {
            return $this;
        }

        return $this;
    }

    public function ping()
    {
        return $this->handle ? $this->handle->ping() : false;
    }

    public function getHandle()
    {
        return $this->handle;
    }

    public function fm($name, $parameters = [])
    {
        $sap = $this->getHandle() ? $this : app('App\SAP\Core\SAP');

        return new FunctionModule($sap, $name, $parameters);
    }

    public function get($key = 'ET_RESULT')
    {
        $this->results = $this->transform($this->fm->invoke(), $key);

        return $this;
    }

    public function save()
    {
        if(isset($this->fm))
            $call = $this->fm->invoke();

        $commit = $this->fm('BAPI_TRANSACTION_COMMIT');

        $commit->invoke();

        return (new SapResponse())->add(isset($call) ? $call : $commit)->get();
    }

    protected function transform($items, $key = 'ET_RESULT')
    {
        if(key_exists('NAVIGATION_DATA', $items))
            $this->meta = [
                'total' => $items['NAVIGATION_DATA']['NUMBER_OF_HITS'],
                'from' => $items['NAVIGATION_DATA']['FIRST_SHOWN_HIT'],
                'last_page' => $items['NAVIGATION_DATA']['LAST_PAGE'],
                'per_page' => $this->meta['per_page'],
                'to' => $items['NAVIGATION_DATA']['LAST_SHOWN_HIT'],
                'current_page' => $items['NAVIGATION_DATA']['ACTUAL_PAGE']
            ];

        return (new Collection($items[$key]))->map(function ($item) {
            return $this->toArray($item);
        });
    }

    public function toArray($item)
    {
        return $item;
    }

    public function with(array $functions)
    {
        foreach ($functions as $function) {
            call_user_func([$this, $function]);
        }

        return $this;
    }

    public function results() : Collection
    {
        return $this->results;
    }

    public function items()
    {
        return [
            'meta' => $this->meta,
            'results' => $this->results
        ];
    }

    public function checkAllConnections()
    {
        $connections = Connection::select([
            'id', 'name', 'description'
        ])->with('credentials')->get();

        $local = $connections->map(function ($c) {
            if ($c['credentials'])
                return [
                    'id' => $c['id'],
                    'name' => $c['name'],
                    'description' => $c['description'],
                    'status' => (new SAP($c['name']))->connect($c['credentials']['username'], decrypt($c['credentials']['password']))->ping(),
                    'is_default' => auth()->user()['server_id'] === $c['id']
                ];

            return [
                'id' => $c['id'],
                'name' => $c['name'],
                'description' => $c['description'],
                'status' => false,
                'is_default' => auth()->user()['server_id'] === $c['id']
            ];
        });

        return [
            'state' => [
                'user' => [
                    'sap' => [
                        'connected' => $this->ping(),
                        'connection' => auth()->user()->connection->name
                    ]
                ]
            ],
            'local' => $local
        ];
    }

}