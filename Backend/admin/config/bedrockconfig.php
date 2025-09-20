<?php
$credentials = require __DIR__ . '/credentials.php';

$aws        = $credentials['aws'] ?? [];
$bedrock    = $aws['bedrock'] ?? [];
$baseKey    = $aws['access_key'] ?? '';
$baseSecret = $aws['secret_key'] ?? '';

$key    = $bedrock['credentials']['key'] ?? $baseKey;
$secret = $bedrock['credentials']['secret'] ?? $baseSecret;

return [
    'region' => $bedrock['region'] ?? 'us-east-1',
    'credentials' => [
        'key'    => $key,
        'secret' => $secret,
    ],
    'models' => [
        'claude' => $bedrock['model_id'] ?? 'anthropic.claude-3-5-sonnet-20240620-v1:0',
    ],
    'guardrail' => [
        'identifier' => $bedrock['guardrail_identifier'] ?? '',
        'version'    => $bedrock['guardrail_version'] ?? '1',
    ],
];
