<?php
// Views/layouts/auth.php

// Cargar helpers globales de URL
require_once __DIR__ . '/../../Helpers/url.php';

// Título
$title = $title ?? 'Login';

// Si quieres tener a mano la base del admin en la vista:
$baseUrl = admin_url();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>

    <!-- CSS propio -->
    <link rel="stylesheet" href="<?= asset_url('style.css') ?>">

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        /* Por si usas la clase animate-float en los blobs del fondo */
        @keyframes float {
            0%,100% { transform: translateY(0) }
            50%     { transform: translateY(-12px) }
        }
        .animate-float { animation: float 6s ease-in-out infinite; }
        .animation-delay-1000 { animation-delay: 1s; }
        .animation-delay-2000 { animation-delay: 2s; }
    </style>
</head>
<body class="bg-transparent text-gray-200 min-h-screen flex items-center justify-center">
    <!-- Blobs decorativos -->
    <div class="fixed top-[10%] left-[10%] w-[300px] h-[300px] rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 blur-[40px] opacity-50 animate-float z-0"></div>
    <div class="fixed bottom-[10%] right-[15%] w-[200px] h-[200px] rounded-full bg-gradient-to-br from-pink-500 to-blue-500 blur-[40px] opacity-50 animate-float animation-delay-1000 z-0"></div>
    <div class="fixed top-1/2 right-[20%] w-[150px] h-[150px] rounded-full bg-gradient-to-br from-purple-500 to-pink-500 blur-[40px] opacity-50 animate-float animation-delay-2000 z-0"></div>

    <!-- Contenido -->
    <?php if (!empty($contentView) && file_exists($contentView)) { include $contentView; } ?>

    <!-- JS globales -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Exponer contexto de login al JS (sin meter PHP en login.js) -->
   <script>
        window.loginContext = {
            error: <?= isset($error) ? json_encode($error) : 'null' ?>,
            loggedout: <?= json_encode(!empty($_GET['loggedout'])) ?>,
            expired: <?= json_encode(!empty($_GET['expired'])) ?>,
            posted: <?= json_encode($_SERVER['REQUEST_METHOD'] === 'POST') ?> // 👈 clave
        };
    </script>
    <script src="<?= asset_url('login.js') ?>"></script>
</body>
</html>