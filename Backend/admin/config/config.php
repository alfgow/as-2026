<?php
$credentials = require __DIR__ . '/credentials.php';

$appUrl = $credentials['app']['url'] ?? 'https://crm.arrendamientoseguro.app';

define('APP_URL', $appUrl);

$aws = $credentials['aws'] ?? [];
$ses = $aws['ses'] ?? [];

$sesKey    = $ses['credentials']['key'] ?? ($aws['access_key'] ?? '');
$sesSecret = $ses['credentials']['secret'] ?? ($aws['secret_key'] ?? '');

// Configuración de AWS SES

define('AWS_SES_REGION', $ses['region'] ?? 'us-east-1');
define('AWS_KEY', $sesKey);
define('AWS_SECRET', $sesSecret);
define('AWS_SES_SENDER', $ses['sender'] ?? 'Arrendamiento Seguro <polizas@arrendamientoseguro.app>');
define('AWS_SES_REPLYTO', $ses['reply_to'] ?? 'polizas@arrendamientoseguro.app');
