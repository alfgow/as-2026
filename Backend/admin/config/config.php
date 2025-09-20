<?php
$credentials = require __DIR__ . '/credentials.php';

$appUrl = $credentials['app']['url'] ?? 'https://crm.arrendamientoseguro.app';

define('APP_URL', $appUrl);

if (!function_exists('admin_base_url')) {
    function admin_base_url(string $path = ''): string
    {
        static $baseUrl;

        if ($baseUrl === null) {
            $envBaseUrl = getenv('ADMIN_BASE_URL');

            if ($envBaseUrl !== false) {
                $envBaseUrl = trim($envBaseUrl);
            }

            if (!empty($envBaseUrl)) {
                $baseUrl = $envBaseUrl;
            } elseif (defined('APP_URL') && APP_URL !== '') {
                $baseUrl = APP_URL;
            } else {
                $baseUrl = '/as-2026/Backend/admin';
            }
        }

        $normalizedBase = rtrim($baseUrl, '/');

        if ($path === '') {
            return $normalizedBase;
        }

        return $normalizedBase . '/' . ltrim($path, '/');
    }
}

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
