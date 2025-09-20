<?php
return [
    'app' => [
        'url' => 'https://tu-dominio.com',
    ],
    'database' => [
        'host'     => '',
        'port'     => 3306,
        'name'     => '',
        'user'     => '',
        'password' => '',
        'charset'  => 'utf8mb4',
        'ssl_ca'   => null,
    ],
    'aws' => [
        'access_key' => '',
        'secret_key' => '',
        's3' => [
            'inquilinos' => [
                'bucket' => '',
                'region' => 'mx-central-1',
            ],
            'arrendadores' => [
                'bucket' => '',
                'region' => 'mx-central-1',
            ],
            'blog' => [
                'bucket' => '',
                'region' => 'mx-central-1',
            ],
        ],
        'bedrock' => [
            'region'                => 'us-east-1',
            'model_id'             => '',
            'guardrail_identifier' => '',
            'guardrail_version'    => '1',
        ],
        'ses' => [
            'region'    => 'us-east-1',
            'sender'    => 'Arrendamiento Seguro <polizas@arrendamientoseguro.app>',
            'reply_to'  => 'polizas@arrendamientoseguro.app',
        ],
    ],
    'verificamex' => [
        'token' => '',
    ],
];
