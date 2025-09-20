<?php
$credentials = require __DIR__ . '/credentials.php';

$aws = $credentials['aws'] ?? [];
$ses = $aws['ses'] ?? [];

$sesKey    = $ses['credentials']['key'] ?? ($aws['access_key'] ?? '');
$sesSecret = $ses['credentials']['secret'] ?? ($aws['secret_key'] ?? '');

return [
    'AWS_SES_REGION' => $ses['region'] ?? 'us-east-1',
    'AWS_KEY'        => $sesKey,
    'AWS_SECRET'     => $sesSecret,
    'AWS_SES_SENDER' => $ses['sender'] ?? 'Arrendamiento Seguro <polizas@arrendamientoseguro.app>',
    'AWS_SES_REPLYTO'=> $ses['reply_to'] ?? 'polizas@arrendamientoseguro.app',
];
