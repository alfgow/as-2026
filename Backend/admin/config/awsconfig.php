<?php
$credentials = require __DIR__ . '/credentials.php';

$aws        = $credentials['aws'] ?? [];
$dynamo     = $aws['dynamo'] ?? [];
$baseKey    = $aws['access_key'] ?? '';
$baseSecret = $aws['secret_key'] ?? '';

$key    = $dynamo['credentials']['key'] ?? $baseKey;
$secret = $dynamo['credentials']['secret'] ?? $baseSecret;

return [
    'table'       => $dynamo['table'] ?? 'as-db',
    'region'      => $dynamo['region'] ?? 'mx-central-1',
    'version'     => $dynamo['version'] ?? 'latest',
    'credentials' => [
        'key'    => $key,
        'secret' => $secret,
    ],
];
