<?php
return [
    'app' => [
        'url' => 'https://tu-dominio.com',
    ],
    'database' => [
        'host'     => 'localhost',
        'port'     => 3306,
        'name'     => 'new_as_2026',
        'user'     => 'root',
        'password' => 'root',
        'charset'  => '',
        'ssl_ca'   => null,
    ],
    'aws' => [
        // Claves globales por defecto. Puedes sobreescribirlas por servicio.
        'access_key' => '',
        'secret_key' => '',
        's3' => [
            'inquilinos' => [
                'bucket' => '',
                'region' => '',
            ],
            'arrendadores' => [
                'bucket' => '',
                'region' => '',
            ],
            'blog' => [
                'bucket' => '',
                'region' => '',
            ],
        ],
        'bedrock' => [
            'region'                => '',
            'model_id'             => '',
            'guardrail_identifier' => '',
            'guardrail_version'    => '1',
        ],
        'ses' => [
            'region' => '',
            'sender' => '',
            'reply_to' => '',
        ],
    ],
    'verificamex' => [
        'token' => '',
    ],
];
