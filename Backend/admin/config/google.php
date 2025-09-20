<?php
$credentials = require __DIR__ . '/credentials.php';

$google = $credentials['google'] ?? [];

return [
    'google' => [
        'api_key' => $google['api_key'] ?? '',
        'cx'      => $google['cx'] ?? '',
    ],
];
