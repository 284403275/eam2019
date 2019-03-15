<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SAP Defaults
    |--------------------------------------------------------------------------
    |
    | These options will set how Laravel connects with SAP
    */

    'default' => [
        'connection' => 'Production'
    ],

    /**
     * SAP connection settings. Default will be set automatically unless no default
     * exists.
     */
    'connections' => [
        'Production' => [
            'ashost' => env('SAP_ASHOST_PROD'),
            'sysnr' => env('SAP_SYSNR_PROD'),
            'client' => env('SAP_CLIENT_PROD'),
            'lang' => env('SAP_LANG_PROD'),
        ],
        'Staging' => [
            'ashost' => env('SAP_ASHOST_STAGING'),
            'sysnr' => env('SAP_SYSNR_STAGING'),
            'client' => env('SAP_CLIENT_STAGING'),
            'lang' => env('SAP_LANG_STAGING'),
        ],
        'Sandbox' => [
            'ashost' => env('SAP_ASHOST_SANDBOX'),
            'sysnr' => env('SAP_SYSNR_SANDBOX'),
            'client' => env('SAP_CLIENT_SANDBOX'),
            'lang' => env('SAP_LANG_SANDBOX'),
        ]
    ]
];