<?php

namespace App\Controllers;

require_once __DIR__ . '/../Helpers/url.php';
require_once __DIR__ . '/../Helpers/SessionHelper.php';

require_once __DIR__ . '/../Models/UserModel.php';

use App\Models\UserModel;

class AuthController
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = admin_url();
    }

    private function redirect(string $path)
    {
        header('Location: ' . admin_url($path), true, 303); // 303 evita re-post
        exit;
    }

    private function startSessionIfNeeded(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Deriva dominio de tu URL de admin
            $admin = admin_url(); // ej: https://crm.arrendamientoseguro.app
            $host  = parse_url($admin, PHP_URL_HOST) ?: $_SERVER['HTTP_HOST'] ?? '';
            // Para subdominios, conviene .dominio
            $cookieDomain = (strpos($host, '.') !== false) ? ('.' . ltrim($host, '.')) : $host;

            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => $cookieDomain, // ej. .arrendamientoseguro.app
                'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'), // true en prod
                'httponly' => true,
                'samesite' => 'Lax', // suficiente para login clásico
            ]);
            session_start();
        }
    }

    public function showLoginForm(string $error = '')
    {
        $this->startSessionIfNeeded();
        $title       = 'Login';
        $contentView = __DIR__ . '/../Views/auth/login.php';
        $baseUrl     = $this->baseUrl; // Para la vista
        // $error ya queda disponible para la vista
        include __DIR__ . '/../Views/layouts/auth.php';
    }

    public function login()
    {
        \App\Helpers\SessionHelper::start(); // en vez de startSessionIfNeeded()

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            // ⚠️ Evitar bucle: solo POST debería entrar aquí
            $this->redirect('/login');
        }

        $user     = trim($_POST['user'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($user === '' || $password === '') {
            $this->showLoginForm('Usuario y contraseña requeridos.');
            return;
        }

        $userModel = new UserModel();
        $loginUser = $userModel->findByUser($user);

        if ($loginUser && !empty($loginUser['password']) && password_verify($password, $loginUser['password'])) {
            // 🔒 seguridad: regenerar ID de sesión
            session_regenerate_id(true);

            // Guardar datos mínimos del usuario
            $_SESSION['user'] = [
                'id'            => $loginUser['id'],
                'nombre'        => $loginUser['nombre_usuario'],
                'usuario'       => $loginUser['usuario'],
                'tipo'          => $loginUser['tipo_usuario'],
                'corto_usuario' => $loginUser['corto_usuario'],
                'email'         => $loginUser['mail_usuario'] ?? '',
            ];

            // Flash de bienvenida
            $_SESSION['flash'] = '¡Bienvenido, ' . htmlspecialchars($loginUser['nombre_usuario']) . '!';

            // Marcar hora de última actividad (importante para timeout)
            $_SESSION['last_activity'] = time();

            // Redirigir a la landing
            $this->redirect('/ia');
        } else {
            $this->showLoginForm('Credenciales incorrectas.');
        }
    }


    public function logout()
    {
        $this->startSessionIfNeeded();

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();

        $this->redirect('/login?loggedout=true');
    }
}
