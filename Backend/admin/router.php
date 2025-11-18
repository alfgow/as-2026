<?php
// admin/router.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/Helpers/url.php';
require_once __DIR__ . '/aws-sdk-php/aws-autoloader.php';

$requestedPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$requestedPath = $requestedPath !== '' ? $requestedPath : '/';

$adminBaseUrl  = admin_base_url();
$adminBasePath = parse_url($adminBaseUrl, PHP_URL_PATH) ?? '';
$adminBasePath = rtrim($adminBasePath, '/');

$uri = $requestedPath;

if ($adminBasePath !== '' && str_starts_with($uri, $adminBasePath)) {
    $uri = substr($uri, strlen($adminBasePath));
}

$uri = $uri === '/' ? '' : '/' . ltrim($uri, '/');
$isApi = str_starts_with($uri, '/api');
$requestIsApi = $isApi;

if (!defined('REQUEST_IS_API')) {
    define('REQUEST_IS_API', $requestIsApi);
}

if ($isApi) {
    $uri = substr($uri, 4);
    $uri = $uri === '' ? '' : '/' . ltrim($uri, '/');
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($isApi) {
    $apiUri = $uri === '' ? '' : $uri;

    $jsonResponse = static function (array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    };

    $methodNotAllowed = static function () use ($jsonResponse): void {
        $jsonResponse(['error' => 'method_not_allowed'], 405);
    };

    $requiresAuth = !($apiUri === '/auth/login' || $apiUri === '/auth/refresh' || str_starts_with($apiUri, '/auth/'));

    if ($requiresAuth) {
        require_once __DIR__ . '/Middleware/ApiTokenMiddleware.php';

        try {
            (new \App\Middleware\ApiTokenMiddleware())->handle();
        } catch (\App\Middleware\ApiTokenException $exception) {
            $jsonResponse(['error' => $exception->reason()], 401);
            exit;
        }
    }

    switch (true) {
        case $apiUri === '/auth/login':
            if ($method !== 'POST') {
                $methodNotAllowed();
                exit;
            }

            require __DIR__ . '/Controllers/Api/AuthApiController.php';
            (new \App\Controllers\Api\AuthApiController())->loginApi();
            exit;

        case $apiUri === '/auth/refresh':
            if ($method !== 'POST') {
                $methodNotAllowed();
                exit;
            }

            require __DIR__ . '/Controllers/Api/AuthApiController.php';
            (new \App\Controllers\Api\AuthApiController())->refreshToken();
            exit;

        case $apiUri === '/prospectos/code' && $method === 'POST':
            require __DIR__ . '/Controllers/ProspectAccessController.php';
            (new \App\Controllers\ProspectAccessController(true))->issue();
            exit;

        case $apiUri === '/prospectos/sendEmails' && $method === 'POST':
            require __DIR__ . '/Controllers/ProspectAccessController.php';
            (new \App\Controllers\ProspectAccessController(true))->sendEmails();
            exit;

        case $apiUri === '/arrendadores' && $method === 'GET':
            require __DIR__ . '/Controllers/ArrendadorController.php';
            (new \App\Controllers\ArrendadorController(true))->index();
            exit;

        case $apiUri === '/polizas' && $method === 'GET':
            require __DIR__ . '/Controllers/PolizaController.php';
            (new \App\Controllers\PolizaController(true))->index();
            exit;

        case $apiUri === '/polizas' && $method === 'POST':
            require __DIR__ . '/Controllers/PolizaController.php';
            (new \App\Controllers\PolizaController(true))->store();
            exit;

        case $apiUri === '/polizas/actualizar' && $method === 'POST':
            require __DIR__ . '/Controllers/PolizaController.php';
            (new \App\Controllers\PolizaController(true))->actualizar();
            exit;

        default:
            if ($apiUri === '/auth' || str_starts_with($apiUri, '/auth/')) {
                $jsonResponse(['error' => 'not_found'], 404);
                exit;
            }

            $jsonResponse(['error' => 'not_found'], 404);
            exit;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Rutas públicas (NO requieren sesión)

// Flags
$isAdmin    = $adminBasePath === '' ? true : str_starts_with($requestedPath, $adminBasePath);
$isLogin    = ($uri === '/login');
$isCallback = ($uri === '/validaciones/demandas/callback');

// Si estoy en el área admin y NO es /login ni el callback,
// y no hay sesión → redirige a /login (una sola vez).
if (
    !$isApi
    && $isAdmin
    && !$isLogin
    && !$isCallback
    && (!isset($_SESSION['user']) || empty($_SESSION['user']['id']))
) {
    header('Location: ' . admin_base_url('login'), true, 302);
    exit;
}

// Redirección a login si no está autenticado
$publicRoutes = ['/login'];
if (!$isApi && !isset($_SESSION['user']) && !in_array($uri, $publicRoutes)) {
    header('Location: ' . admin_base_url('login'));
    exit;
}

// Esta variable será usada SIEMPRE por el layout
$contentView = null;

switch (true) {
    // Mostrar formulario de login
    case $uri === '/login' && $_SERVER['REQUEST_METHOD'] === 'GET':
        require __DIR__ . '/Controllers/AuthController.php';
        (new \App\Controllers\AuthController())->showLoginForm();
        exit;

        // Procesar login
    case $uri === '/login' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/AuthController.php';
        (new \App\Controllers\AuthController())->login();
        exit;

        // Validaciones - Status (para progress bar)
    case preg_match('#^/validaciones/status$#', $uri) && $_SERVER['REQUEST_METHOD'] === 'GET':
        require __DIR__ . '/Controllers/ValidacionLegalController.php';
        (new \App\Controllers\ValidacionLegalController())->status($_GET['id'] ?? 0);
        exit;
        break;


    // GET /prospectos/code  -> muestra la vista con tu layout
    case preg_match('#^/prospectos/code$#', $uri) && $_SERVER['REQUEST_METHOD'] === 'GET':
        require __DIR__ . '/Controllers/ProspectAccessController.php';
        (new \App\Controllers\ProspectAccessController())->code();
        exit;
        break;

    // POST /prospectos/code -> genera OTP + Magic Link (JSON)
    case preg_match('#^/prospectos/code$#', $uri) && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/ProspectAccessController.php';
        (new \App\Controllers\ProspectAccessController())->issue();
        exit;
        break;

    case $uri === '/integrations/clients' && $_SERVER['REQUEST_METHOD'] === 'GET':
        require __DIR__ . '/Controllers/ApiClientController.php';
        (new \App\Controllers\ApiClientController())->index();
        exit;
        break;

    case $uri === '/integrations/clients' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/ApiClientController.php';
        (new \App\Controllers\ApiClientController())->store();
        exit;
        break;

    case $uri === '/integrations/clients/rotate-secret' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/ApiClientController.php';
        (new \App\Controllers\ApiClientController())->rotateSecret();
        exit;
        break;

    // Logout
    case $uri === '/logout':
        require __DIR__ . '/Controllers/AuthController.php';
        (new \App\Controllers\AuthController())->logout();
        exit;

    case $uri === '' || $uri === '/dashboard':
        require __DIR__ . '/Controllers/DashboardController.php';
        (new \App\Controllers\DashboardController())->index();
        exit;
        break;

    case $uri === '/prospectos/sendEmails' && $method === 'POST':
        require __DIR__ . '/Controllers/ProspectAccessController.php';
        (new \App\Controllers\ProspectAccessController())->sendEmails();
        exit;
        break;

    case $uri === '/inquilinos/archivos' && $method === 'GET':
        require __DIR__ . '/Controllers/InquilinoValidacionAWSController.php';
        (new \App\Controllers\InquilinoValidacionAWSController())->obtenerArchivos();
        exit;
        break;

    case preg_match('#^/inquilino/([^/]+)/archivos-presignados$#i', $uri, $matches) && $_SERVER['REQUEST_METHOD'] === 'GET':
        require __DIR__ . '/Controllers/InquilinoValidacionAWSController.php';
        (new \App\Controllers\InquilinoValidacionAWSController())->obtenerArchivosPorSlug($matches[1]);
        exit;
        break;



    // GET /inquilino/{slug}/archivos-presignados
    // Blog - listado principal
    case $uri === '/blog':
        require __DIR__ . '/Controllers/BlogController.php';
        (new \App\Controllers\BlogController())->index();
        exit;
        break;

    // Blog - formulario nuevo
    case $uri === '/blog/create':
        require __DIR__ . '/Controllers/BlogController.php';
        (new \App\Controllers\BlogController())->create();
        exit;
        break;
    // Blog - almacenar nuevo post
    case $uri === '/blog/store':
        require __DIR__ . '/Controllers/BlogController.php';
        (new \App\Controllers\BlogController())->store();
        exit;
        break;

    // Blog - actualizar post existente
    case $uri === '/blog/update' && $method === 'POST':
        require __DIR__ . '/Controllers/BlogController.php';
        (new \App\Controllers\BlogController())->update();
        exit;
        break;

    // Blog - editar (ejemplo: /blog/edit?id=4)
    case preg_match('#^/blog/edit$#', $uri):
        require __DIR__ . '/Controllers/BlogController.php';
        (new \App\Controllers\BlogController())->edit();
        exit;
        break;

    // Blog - eliminar (ejemplo: /blog/delete?id=4)
    case preg_match('#^/blog/delete$#', $uri):
        require __DIR__ . '/Controllers/BlogController.php';
        (new \App\Controllers\BlogController())->delete();
        exit;
        break;

    // Asesores - listado principal
    case $uri === '/asesores':
        require __DIR__ . '/Controllers/AsesorController.php';
        (new \App\Controllers\AsesorController())->index();
        exit;
        break;

    // Asesores - almacenar
    case $uri === '/asesores/store':
        require __DIR__ . '/Controllers/AsesorController.php';
        (new \App\Controllers\AsesorController())->store();
        exit;
        break;

    // Asesores - actualizar
    case $uri === '/asesores/update':
        require __DIR__ . '/Controllers/AsesorController.php';
        (new \App\Controllers\AsesorController())->update();
        exit;
        break;

    // Asesores - eliminar
    case $uri === '/asesores/delete':
        require __DIR__ . '/Controllers/AsesorController.php';
        (new \App\Controllers\AsesorController())->delete();
        exit;
        break;

    // Arrendadores - listado
    case $uri === '/arrendadores':
        require __DIR__ . '/Controllers/ArrendadorController.php';
        (new \App\Controllers\ArrendadorController())->index();
        exit;
        break;

    // Arrendador detalle por slug
    case preg_match('#^/arrendadores/(?!por-asesor(?:/|$))([a-z0-9-]+)$#i', $uri, $m):
        require __DIR__ . '/Controllers/ArrendadorController.php';
        (new \App\Controllers\ArrendadorController())->detalle($m[1]);
        exit;
        break;

    // PDF de la póliza
    case preg_match('#^/polizas/pdf/(\d+)$#', $uri, $matches) && $_SERVER['REQUEST_METHOD'] === 'GET':
        require __DIR__ . '/Controllers/PolizaController.php';
        (new \App\Controllers\PolizaController())->pdf($matches[1]);
        exit;
        break;


    // Generar contrato para póliza por número de póliza
    case preg_match('#^/polizas/generacion-contrato/(\d+)$#', $uri, $matches) && $_SERVER['REQUEST_METHOD'] === 'GET':
        require __DIR__ . '/Controllers/PolizaController.php';
        (new \App\Controllers\PolizaController())->generacionContrato($matches[1]);
        exit;

        // Procesar formulario de generación de contrato
    case $uri === '/polizas/generar-pdf-contrato' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/PolizaController.php';
        (new \App\Controllers\PolizaController())->generarContratoDesdeFormulario();
        exit;


    case preg_match('#^/arrendadores/por-asesor/(\d+)$#', $uri, $m):
        require __DIR__ . '/Controllers/ArrendadorController.php';
        (new \App\Controllers\ArrendadorController())->arrendadoresPorAsesor((int)$m[1]);
        exit;
        break;

    // Inmuebles
    case $uri === '/inmuebles':
        require __DIR__ . '/Controllers/InmuebleController.php';
        (new \App\Controllers\InmuebleController())->index();
        exit;
        break;

    case $uri === '/inmuebles/crear':
        require __DIR__ . '/Controllers/InmuebleController.php';
        (new \App\Controllers\InmuebleController())->crear();
        exit;
        break;

    case $uri === '/inmuebles/store' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/InmuebleController.php';
        (new \App\Controllers\InmuebleController())->store();
        exit;
        break;

    case preg_match('#^/inmuebles/editar/([^/]+)/([^/]+)$#', $uri, $m):

        require __DIR__ . '/Controllers/InmuebleController.php';
        (new \App\Controllers\InmuebleController())->editar($m[1], $m[2]);
        exit;
        break;

    case preg_match('#^/inmuebles/editar/(\d+)$#', $uri, $m):
        require __DIR__ . '/Controllers/InmuebleController.php';
        (new \App\Controllers\InmuebleController())->editar((string)$m[1]);

        exit;
        break;

    case $uri === '/inmuebles/update' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/InmuebleController.php';
        (new \App\Controllers\InmuebleController())->update();
        exit;
        break;

    case $uri === '/inmuebles/delete' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/InmuebleController.php';
        (new \App\Controllers\InmuebleController())->delete();
        exit;
        break;

    case preg_match('#^/inmuebles/por-arrendador/([^/]+)$#', $uri, $m):
        require __DIR__ . '/Controllers/InmuebleController.php';
        (new \App\Controllers\InmuebleController())->inmueblesPorArrendador($m[1]);
        exit;
        break;

    case preg_match('#^/inmuebles/info/([^/]+)/([^/]+)$#', $uri, $m):
        require __DIR__ . '/Controllers/InmuebleController.php';
        (new \App\Controllers\InmuebleController())->info($m[1], $m[2]);
        exit;
        break;

    case preg_match('#^/inmuebles/info/(\d+)$#', $uri, $m):
        require __DIR__ . '/Controllers/InmuebleController.php';
        (new \App\Controllers\InmuebleController())->info((string)$m[1]);
        exit;
        break;

    case preg_match('#^/inmuebles/([^/]+)/([^/]+)$#', $uri, $m):
        require __DIR__ . '/Controllers/InmuebleController.php';
        (new \App\Controllers\InmuebleController())->ver($m[1], $m[2]);
        exit;
        break;

    case preg_match('#^/inmuebles/(\d+)$#', $uri, $m):
        require __DIR__ . '/Controllers/InmuebleController.php';
        (new \App\Controllers\InmuebleController())->ver((string) $m[1]);

        exit;
        break;

    // Nuevo inmueble
    // Vencimientos próximos
    case $uri === '/vencimientos':
        require __DIR__ . '/Controllers/VencimientosController.php';
        (new \App\Controllers\VencimientosController())->index();
        exit;
        break;

    // Buscar pólizas por número
    case $uri === '/polizas/buscar':
        require __DIR__ . '/Controllers/PolizaController.php';
        (new \App\Controllers\PolizaController())->buscar();
        exit;
        break;

    case $uri === '/polizas' && $_SERVER['REQUEST_METHOD'] === 'GET':
        require __DIR__ . '/Controllers/PolizaController.php';
        (new \App\Controllers\PolizaController())->index();
        exit;
        break;

    case $uri === '/polizas/nueva':
        require __DIR__ . '/Controllers/PolizaController.php';
        (new \App\Controllers\PolizaController())->nueva();
        exit;
        break;

    case $uri === '/polizas/store' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/PolizaController.php';
        (new \App\Controllers\PolizaController())->store();
        exit;
        break;

    case preg_match('#^/polizas/(\d+)/renta$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET':
        require __DIR__ . '/Controllers/PolizaController.php';
        (new \App\Controllers\PolizaController())->renta((int)$m[1]);
        exit;
        break;

    case preg_match('#^/polizas/editar/(\d+)$#', $uri, $m):
        require __DIR__ . '/Controllers/PolizaController.php';
        (new \App\Controllers\PolizaController())->editar((int)$m[1]);
        exit;
        break;

    case preg_match('#^/polizas/renovar/(\d+)$#', $uri, $m):
        require __DIR__ . '/Controllers/PolizaController.php';
        (new \App\Controllers\PolizaController())->renovar((int)$m[1]);
        exit;
        break;

    case $uri === '/polizas/actualizar' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/PolizaController.php';
        (new \App\Controllers\PolizaController())->actualizar();
        exit;
        break;

    case $uri === '/polizas/eliminar' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/PolizaController.php';
        (new \App\Controllers\PolizaController())->eliminar();
        exit;
        break;

    case $uri === '/ia' && $_SERVER['REQUEST_METHOD'] === 'GET':
        require __DIR__ . '/Controllers/IAController.php';
        (new \Backend\admin\Controllers\IAController())->index();
        exit;
        break;

    case $uri === '/ia/chat' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/IAController.php';
        (new \Backend\admin\Controllers\IAController())->chat();
        exit;
        break;

    case $uri === '/ia/historial' && $_SERVER['REQUEST_METHOD'] === 'GET':
        require __DIR__ . '/Controllers/IAHistorialController.php';
        (new \Backend\admin\Controllers\IAHistorialController())->index();
        exit;
        break;

    case preg_match('#^/ia/historial/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET':
        require __DIR__ . '/Controllers/IAHistorialController.php';
        (new \Backend\admin\Controllers\IAHistorialController())->ver((int)$m[1]);
        exit;
        break;

    case preg_match('#^/polizas/(\d+)$#', $uri, $m):
        require __DIR__ . '/Controllers/PolizaController.php';
        (new \App\Controllers\PolizaController())->mostrar((int)$m[1]);
        exit;
        break;

    case $uri === '/arrendador/actualizar-datos-personales' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/ArrendadorController.php';
        (new \App\Controllers\ArrendadorController())->actualizarDatosPersonales();
        exit;
        break;

    case $uri === '/arrendador/actualizar-info-bancaria' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/ArrendadorController.php';
        (new \App\Controllers\ArrendadorController())->actualizarInfoBancaria();
        exit;
        break;

    case $uri === '/arrendador/actualizar-comentarios' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/ArrendadorController.php';
        (new \App\Controllers\ArrendadorController())->actualizarComentarios();
        exit;
        break;

    case $uri === '/arrendador/actualizar-asesor' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/ArrendadorController.php';
        (new \App\Controllers\ArrendadorController())->actualizarAsesor();
        exit;
        break;

    // Prospecto - listado principal
    case $uri === '/inquilino' || $uri === '/inquilino/index':
        require __DIR__ . '/Controllers/InquilinoController.php';
        (new \App\Controllers\InquilinoController())->index();
        exit;
        break;

    case $uri === '/inquilino/editar_datos_personales' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/InquilinoController.php';
        (new \App\Controllers\InquilinoController())->editarDatosPersonales();
        exit;
        break;
    case $uri === '/inquilino/editar_domicilio' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/InquilinoController.php';
        (new \App\Controllers\InquilinoController())->editarDomicilio();
        exit;
        break;
    case $uri === '/inquilino/editar_trabajo' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/InquilinoController.php';
        (new \App\Controllers\InquilinoController())->editarTrabajo();
        exit;
        break;

    case $uri === '/inquilino/editar_fiador' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/InquilinoController.php';
        (new \App\Controllers\InquilinoController())->editarFiador();
        exit;
        break;
    case $uri === '/inquilino/editar_historial_vivienda' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/InquilinoController.php';
        (new \App\Controllers\InquilinoController())->editarHistorialVivienda();
        exit;
        break;

    case $uri === '/inquilino/editar_asesor' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/InquilinoController.php';
        (new \App\Controllers\InquilinoController())->editarAsesor();
        exit;
        break;

    case $uri === '/financieros' || $uri === '/financieros/index':
        require __DIR__ . '/Controllers/FinancieroController.php';
        (new \App\Controllers\FinancieroController())->index();
        exit;
        break;

    case $uri === '/financieros/registro':
        require __DIR__ . '/Controllers/FinancieroController.php';
        (new \App\Controllers\FinancieroController())->registroVenta();
        exit;
        break;



    // Prospecto - AJAX edición datos personales (POST)
    // Crear inmueble nuevo (AJAX POST)
    case $uri === '/inmueble/crear' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/InmuebleController.php';
        (new \App\Controllers\InmuebleController())->crear();
        exit;
        break;

    // Eliminar inmueble (AJAX POST)
    case $uri === '/inmueble/eliminar' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/InmuebleController.php';
        (new \App\Controllers\InmuebleController())->delete();
        exit;
        break;


    // Crear inmueble vía AJAX
    case $uri === '/inmueble/guardar-ajax' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/InmuebleController.php';
        (new \App\Controllers\InmuebleController())->guardarAjax();
        exit;
        break;

    // Eliminar archivo arrendador
    case $uri === '/arrendador/eliminar-archivo' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/ArrendadorController.php';
        (new \App\Controllers\ArrendadorController())->eliminarArchivo();
        exit;
        break;

    // Cambiar archivo arrendador
    case $uri === '/arrendador/cambiar-archivo' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/ArrendadorController.php';
        (new \App\Controllers\ArrendadorController())->cambiarArchivo();
        exit;
        break;

    // Generar y descargar póliza en PDF
    case preg_match('#^/polizas/generar-pdf/(\d+)$#', $uri, $matches) && $_SERVER['REQUEST_METHOD'] === 'GET':
        require __DIR__ . '/Controllers/PolizaController.php';
        (new \App\Controllers\PolizaController())->generarPdf((int)$matches[1]);
        exit;
        break;



    // Reemplazo de archivo (AJAX POST)
    case $uri === '/inquilino/subir-archivo' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/InquilinoController.php';
        (new \App\Controllers\InquilinoController())->subirArchivo();
        exit;
        break;

    case $uri === '/inquilino/reemplazar_archivo' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/InquilinoController.php';
        (new \App\Controllers\InquilinoController())->reemplazarArchivo();
        exit;
        break;

    case $uri === '/inquilino/eliminar_archivo' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/InquilinoController.php';
        (new \App\Controllers\InquilinoController())->eliminarArchivo();
        exit;
        break;

    case $uri === '/inquilino/eliminar' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/InquilinoController.php';
        (new \App\Controllers\InquilinoController())->eliminar();
        exit;
        break;

    case $uri === '/inquilino/editar-validaciones' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/InquilinoController.php';
        (new \App\Controllers\InquilinoController())->editarValidaciones();
        exit;
        break;

    case $uri === '/inquilino/editar-status' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/InquilinoController.php';
        (new \App\Controllers\InquilinoController())->editarStatus();
        exit;
        break;

    case preg_match('#^/inquilino/([^/]+)/validaciones$#i', $uri, $matches) && $_SERVER['REQUEST_METHOD'] === 'GET':
        require __DIR__ . '/Controllers/ValidacionLegalController.php';
        (new \App\Controllers\ValidacionLegalController())->historialPorSlug($matches[1]);
        exit;
        break;

    // Validación manual con AWS (inicial, sin llamadas a AWS aún)
    case preg_match('#^/inquilino/([a-z0-9\-]+)/validar$#', $uri, $matches) && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/ValidacionAwsController.php';
        (new \App\Controllers\ValidacionAwsController())->validar($matches[1]);
        exit;
        break;

    // POST /inquilino/{slug}/validar  → InquilinoValidacionAWSController::validar($slug)
    // /inquilino/{slug}/validar  → InquilinoValidacionAWSController::validar($slug)
    case preg_match('#^/inquilino/([a-z0-9\-]+)/validar$#i', $uri, $m)
        && (
            $_SERVER['REQUEST_METHOD'] === 'POST' ||
            ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['check']) && in_array($_GET['check'], ['archivos', 'faces', 'ocr', 'parse', 'nombres', 'kv', 'match', 'save_match', 'save_face', 'status', 'ingresos_list', 'ingresos_ocr', 'status', 'resumen_full', 'verificamex']))
        ):
        require_once __DIR__ . '/Controllers/InquilinoValidacionAWSController.php';
        (new \App\Controllers\InquilinoValidacionAWSController())->validar($m[1]);
        exit;
        break;



    // case $uri === '/inquilino/editar_validaciones' && $_SERVER['REQUEST_METHOD'] === 'POST':
    //     require __DIR__ . '/Controllers/InquilinoController.php';
    //     (new \App\Controllers\InquilinoController())->editar_validaciones();
    //     exit;
    //     break;

    // Media presign 1
    case preg_match('#^/media/presign$#', $uri) && $_SERVER['REQUEST_METHOD'] === 'GET':
        require __DIR__ . '/Controllers/MediaController.php';
        (new \App\Controllers\MediaController())->presign();
        exit;
        break;

    // Media presign many
    case preg_match('#^/media/presign-many$#', $uri) && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/MediaController.php';
        (new \App\Controllers\MediaController())->presignMany();
        exit;
        break;



    // Vista detalle de inquilino
    case preg_match('#^/inquilino/([a-z0-9\-]+)$#i', $uri, $matches) && $_SERVER['REQUEST_METHOD'] === 'GET':
        require __DIR__ . '/Controllers/InquilinoController.php';
        (new \App\Controllers\InquilinoController())->mostrar($matches[1]);
        exit;
        break;

    // Validación de Identidad (GET)
    case preg_match('#^/inquilino/([^/]+)/validar-identidad$#', $uri, $matches) && $_SERVER['REQUEST_METHOD'] === 'GET':
        require __DIR__ . '/Controllers/ValidacionIdentidadController.php';
        (new \App\Controllers\ValidacionIdentidadController())->index($matches[1]);
        exit;
        break;

    // POST: Procesar validación
    case preg_match('#^/inquilino/([^/]+)/validar-identidad$#', $uri, $matches) && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/ValidacionIdentidadController.php';
        (new \App\Controllers\ValidacionIdentidadController())->procesar($matches[1]);
        exit;
        break;

    // Vista de resultados
    case preg_match('#^/inquilino/([^/]+)/validar-identidad/resultado$#', $uri, $matches):
        require __DIR__ . '/Controllers/ValidacionIdentidadController.php';
        (new \App\Controllers\ValidacionIdentidadController())->resultado($matches[1]);
        exit;
        break;

    // Ejecutar validación (bitácora Paso 1)
    case preg_match('#^/validaciones/demandas/run/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/ValidacionLegalController.php';
        (new \App\Controllers\ValidacionLegalController())->run($m[1]);
        exit;

        // Obtener último reporte (por inquilino; opcional ?portal=...)
    case preg_match('#^/validaciones/demandas/ultimo/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET':
        require __DIR__ . '/Controllers/ValidacionLegalController.php';
        (new \App\Controllers\ValidacionLegalController())->ultimo($m[1]);
        exit;
        break;

    // Toggle Demandas
    case preg_match('#^/inquilino/(\d+)/toggle-demandas$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/ValidacionLegalController.php';
        (new \App\Controllers\ValidacionLegalController())->toggleDemandas((int)$m[1]);
        exit;
        break;


    // Historial de validaciones jurídicas
    case preg_match('#^/validaciones/demandas/historial/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET':
        require __DIR__ . '/Controllers/ValidacionLegalController.php';
        (new \App\Controllers\ValidacionLegalController())->historialJson((int)$m[1]);
        exit;
        break;


    // Historial jurídico por slug
    case preg_match('#^/inquilino/([^/]+)/validaciones/demandas$#', $uri, $matches):
        require __DIR__ . '/Controllers/ValidacionLegalController.php';
        (new \App\Controllers\ValidacionLegalController())->historialPorSlug($matches[1]);
        exit;
        break;



    // ...otras rutas...

    default:
        http_response_code(404);
        // Asigna la ruta absoluta del 404 como contentView
        $contentView = __DIR__ . '/Views/404.php';
        $headerTitle = 'Página no encontrada';
        break;
}

// Seguridad: si el controlador no definió $contentView, lánzalo a 404
if (empty($contentView)) {
    $contentView = __DIR__ . '/Views/404.php';
    $headerTitle = 'Página no encontrada';
}

include __DIR__ . '/Views/layouts/main.php';
