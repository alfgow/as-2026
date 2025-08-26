<?php
// Backend/admin/Helpers/url.php

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') == 443);
        $scheme  = $isHttps ? 'https' : 'http';
        $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';

        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $scriptDir = rtrim($scriptDir, '/');

        $base = $scheme . '://' . $host . $scriptDir;

        if ($path !== '') {
            $path = '/' . ltrim($path, '/');
        }
        return $base . $path;
    }
}

if (!function_exists('admin_url')) {
    function admin_url(string $path = ''): string
    {
        $base = rtrim(base_url(), '/');

        // cambiar admin
        // Evita duplicar "/admin"
        if (!preg_match('#/admin$#', $base)) {
            $base .= '';
        }

        if ($path !== '') {
            $path = '/' . ltrim($path, '/');
        }
        return $base . $path;
    }
}

if (!function_exists('asset_url')) {
    function asset_url(string $path): string
    {
        // Usa base_url directamente para que no duplique "admin"
        $base = rtrim(base_url(), '/');

        // Asegura que haya solo un "/assets"
        return $base . '/assets/' . ltrim($path, '/');
    }
}


