<?php

namespace App\Middleware;

class AuthMiddleware
{
    public static function verificarSesion($timeoutSeconds = 1800)
    {
        require_once __DIR__ . '/../config/config.php';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Si no hay usuario logueado
        if (empty($_SESSION['user'])) {
            header('Location: ' . \admin_base_url('login'));
            exit;
        }

        // Si pasó el tiempo de inactividad
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
