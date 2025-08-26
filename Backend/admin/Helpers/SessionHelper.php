<?php
namespace App\Helpers;

class SessionHelper
{
    public static function startAndVerifyTimeout($timeoutSeconds = 60)
    {
        $baseUrl = '/as-2026/Backend/admin' ?? '/';
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Verifica si el usuario está logueado
        if (!isset($_SESSION['user'])) {
            header("Location: $baseUrl/login");
            exit;
        }
        // Verifica tiempo de inactividad
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeoutSeconds) {
            session_unset();
            session_destroy();
            header("Location: $baseUrl/login?expired=true");
            exit;
        }

        // Refresca tiempo de actividad
        $_SESSION['last_activity'] = time();
    }
}
