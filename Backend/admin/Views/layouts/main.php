<!-- admin/Views/layouts/main.php -->
<?php
require_once __DIR__ . '/../../Helpers/TextHelper.php';

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
if (! function_exists('getBaseUrl')) {
    function getBaseUrl()
    {
        $protocol   = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $host       = $_SERVER['HTTP_HOST'] ?? '';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir  = str_replace('\\', '/', dirname($scriptName));
        $basePath   = rtrim(preg_replace('#/index\.php$#', '', $scriptDir), '/');
        if ($host && $basePath !== false) {
            $url = $protocol . '://' . $host . $basePath;
            $url = preg_replace('#(?<!:)//+#', '/', $url);
            return $url;
        }
        return '/';
    }
}
$baseUrl = getBaseUrl();
?>
<?php
// Calcula el prefijo base sólo con la ruta (no dominio)
$__envBase = $_ENV['ADMIN_BASE_URL'] ?? getenv('ADMIN_BASE_URL'); // opcional: puedes setear /admin aquí
if ($__envBase) {
    // admite tanto /admin como http://dominio/admin → nos quedamos con la ruta
    $__parsed = parse_url($__envBase);
    $__basePath = rtrim($__parsed['path'] ?? $__envBase, '/');
} else {
    // infiere del script actual (sirve cuando el panel está bajo /admin, /as, etc.)
    $__dir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
    $__basePath = ($__dir === '' || $__dir === '/' || $__dir === '.') ? '' : $__dir;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $title ?? 'Panel de administración' ?></title>
    <link rel="stylesheet" href="<?php echo $baseUrl ?>/assets/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class=" bg-transparent text-gray-200 min-h-screen font-sans">
    <!-- Loader Global Among Us -->
    <div id="global-loader" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 flex flex-col items-center shadow-xl">
            <div class="flex space-x-6">
                <div class="amongus amongus-rosa"></div>
                <div class="amongus amongus-acento"></div>
                <div class="amongus amongus-dark"></div>
            </div>
            <p class="text-[var(--primary-dark)] font-semibold mt-4">Procesando información...</p>
        </div>
    </div>
    <div class="fixed top-[10%] left-[10%] w-[300px] h-[300px] rounded-full bg-gradient-to-br from-primary to-purple-600 blur-[40px] opacity-50 animate-float z-0"></div>
    <div class="fixed bottom-[10%] right-[15%] w-[200px] h-[200px] rounded-full bg-gradient-to-br from-secondary to-blue-500 blur-[40px] opacity-50 animate-float animation-delay-1000 z-0"></div>
    <div class="fixed top-1/2 right-[20%] w-[150px] h-[150px] rounded-full bg-gradient-to-br from-purple-500 to-pink-500 blur-[40px] opacity-50 animate-float animation-delay-2000 z-0"></div>

    <!-- SIDEBAR -->
    <aside id="sidebar"
        class="fixed z-40 inset-y-0 left-0 w-64 bg-transparent h-full transition-transform transform -translate-x-full xl:translate-x-0  xl:shadow-lg xl:border-r xl:border-indigo-900/30
      flex-shrink-0 pointer-events-auto select-none">
        <?php include __DIR__ . '/_sidebar.php'; ?>
    </aside>

    <!-- BACKDROP para móvil -->
    <div id="sidebar-backdrop"
        class="fixed inset-0 bg-black/10 backdrop-blur-sm z-30 hidden md:hidden transition-all duration-300"></div>

    <!-- MAIN CONTENT -->
    <div class="xl:ml-64">
        <header class="sticky top-0 z-20 flex items-center justify-between px-4 py-4 bg-white/0 backdrop-blur-md ">
            <!-- Hamburguesa solo móvil -->
            <button id="menu-btn" class="block xl:hidden p-2 rounded bg-[#23243a] text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 z-50">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 8h16M4 16h16" />
                </svg>
            </button>
            <div class="no-print w-full flex justify-center items-center py-2 select-none">
                <img src="https://alfgow.s3.mx-central-1.amazonaws.com/Logo+Circular.png"
                    alt="Buró Inmobiliario Logo"
                    class="logo-float"
                    style="width: 120px; height: 120px; border: 1px solid white;" />

            </div>
        </header>
        <main class="min-h-screen px-4  md:pt-6 transition-all duration-300 relative">
            <?php
            if (!function_exists('estadoPolizaTexto')) {
                function estadoPolizaTexto($codigo)
                {
                    return match ((string) $codigo) {
                        '1' => 'Vigente',
                        '2' => 'Concluida',
                        '3' => 'Término Anticipado',
                        '4' => 'Incumplimiento',
                        default => 'Desconocido',
                    };
                }
            }

            if (!function_exists('estadoBadgeColor')) {
                function estadoBadgeColor(string $estado): string
                {
                    return match ($estado) {
                        '1' => 'bg-green-500',
                        '2' => 'bg-gray-500',
                        '3' => 'bg-red-500',
                        '4' => 'bg-yellow-500',
                        default => 'bg-gray-300',
                    };
                }
            }
            if (! empty($contentView) && file_exists($contentView)) {
                include $contentView;
            } else {
                include __DIR__ . '/../404.php'; // Respaldo absoluto, pero realmente nunca debe llegar aquí ya.
            }
            ?>

        </main>
    </div>
    <script>
        const BASE_URL = "<?php echo rtrim(getBaseUrl(), '/'); ?>";
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if (!empty($_SESSION['flash'])): ?>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    title: 'Bienvenido Iron Man',
                    text: 'Propulsores encendidos...',
                    imageUrl: 'https://media.giphy.com/media/26AHLBZUC1n53ozi8/giphy.gif',
                    imageWidth: 200,
                    imageHeight: 200,
                    imageAlt: 'Cohete GIF',
                    timer: 3000,
                    showConfirmButton: false
                });
            });
        </script>
    <?php unset($_SESSION['flash']);
    endif; ?>

    <script src="<?php echo $baseUrl ?>/assets/main.js"></script>
    <script>
        // Prefijo para todas las rutas del panel (ej. "/admin")
        window.ADMIN_BASE = <?= json_encode($__basePath) ?>;
        window.baseurl = window.ADMIN_BASE;
        window.joinAdmin = (p) => (window.ADMIN_BASE ? window.ADMIN_BASE.replace(/\/$/, '') : '') +
            (p.startsWith('/') ? p : '/' + p);

        // Funciones Loader global
        function showLoader(msg = "Procesando información...") {
            const loader = document.getElementById("global-loader");
            if (!loader) return;
            const msgEl = loader.querySelector("p");
            if (msgEl) msgEl.textContent = msg;
            loader.classList.remove("hidden");
        }

        function hideLoader() {
            const loader = document.getElementById("global-loader");
            if (!loader) return;
            loader.classList.add("hidden");
        }
    </script>
    <script>
        // Ocultar loader cuando todo está listo (HTML + imágenes + recursos)
        window.addEventListener("load", () => {
            hideLoader();
        });
    </script>


</body>

</html>