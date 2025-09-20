<?php
$credentials = require __DIR__ . '/credentials.php';

$aws        = $credentials['aws'] ?? [];
$s3         = $aws['s3'] ?? [];
$baseKey    = $aws['access_key'] ?? '';
$baseSecret = $aws['secret_key'] ?? '';

$buildConfig = static function (array $bucketConfig) use ($baseKey, $baseSecret): array {
    $override = $bucketConfig['credentials'] ?? [];

    return [
        'region'      => $bucketConfig['region'] ?? 'mx-central-1',
        'bucket'      => $bucketConfig['bucket'] ?? '',
        'credentials' => [
            'key'    => $override['key'] ?? $baseKey,
            'secret' => $override['secret'] ?? $baseSecret,
        ],
    ];
};

return [
    'inquilinos'   => $buildConfig($s3['inquilinos'] ?? []),
    'arrendadores' => $buildConfig($s3['arrendadores'] ?? []),
    'blog'         => $buildConfig($s3['blog'] ?? []),
];
