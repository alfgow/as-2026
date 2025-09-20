<?php

namespace App\Helpers;

class SessionHelper
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // ⚠️ Ajusta el dominio según entorno
            $cookieDomain = '';
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => $cookieDomain,
                'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public static function verify(int $timeoutSeconds = 1800): void
    {
        self::start();

        // Usuario no logueado
        if (empty($_SESSION['user'])) {
            header('Location: ' . \admin_base_url('login'));
            exit;
        }

        // Timeout de inactividad
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeoutSeconds) {
            session_unset();
            session_destroy();
            header('Location: ' . \admin_base_url('login?expired=true'));
            exit;
        }

        // Refrescar timestamp
        $_SESSION['last_activity'] = time();
    }
}
