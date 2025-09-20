<?php
$credentials = require __DIR__ . '/credentials.php';

$appUrl = $credentials['app']['url'] ?? '';

if ($appUrl === null) {
    $appUrl = '';
}

if (!is_string($appUrl)) {
    $appUrl = (string) $appUrl;
}

$appUrl = trim($appUrl);

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
            } else {
                $appUrl = defined('APP_URL') ? trim(APP_URL) : '';
                $currentHost = $_SERVER['HTTP_HOST'] ?? '';
                $appHost = $appUrl !== '' ? parse_url($appUrl, PHP_URL_HOST) : null;
                $hostsMatch = $appUrl !== '' && !empty($currentHost) && !empty($appHost)
                    && strcasecmp($appHost, $currentHost) === 0;

                if ($appUrl !== '' && ($hostsMatch || $currentHost === '')) {
                    $baseUrl = $appUrl;
                } else {
                    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                        || (($_SERVER['SERVER_PORT'] ?? '') == 443);
                    $scheme = $isHttps ? 'https' : 'http';
                    $host = $currentHost ?: ($appHost ?: 'localhost');

                    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
                    $scriptDir = dirname($scriptName);
                    if ($scriptDir === '\\' || $scriptDir === '/' || $scriptDir === '.') {
                        $scriptDir = '';
                    }
                    $scriptDir = str_replace('\\', '/', $scriptDir);
                    $scriptDir = rtrim($scriptDir, '/');

                    $base = $scheme . '://' . $host;
                    if ($scriptDir !== '') {
                        $base .= $scriptDir;
                    }

                    $baseUrl = $base;
                }
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
