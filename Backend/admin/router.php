<?php
// admin/router.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/Helpers/url.php';
require_once __DIR__ . '/aws-sdk-php/aws-autoloader.php';

$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
 $base = '/as-2026/Backend/admin';
// $base = 'https://crm.arrendamientoseguro.app'; // Cambia aquí si tu base cambia en prod/dev
$uri  = str_replace($base, '', $uri);
$uri  = rtrim($uri, '/'); // Evita problemas con barras al final
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
// Rutas públicas (NO requieren sesión)

// Ajusta solo esta constante si cambia el prefijo
$ADMIN_BASE = '/as-2026/Backend/admin';

// Flags
$isAdmin    = str_starts_with($uri, $ADMIN_BASE);
$isLogin    = ($uri === "$ADMIN_BASE/login");
$isCallback = ($uri === "$ADMIN_BASE/validaciones/demandas/callback");

// Si estoy en el área admin y NO es /login ni el callback,
// y no hay sesión → redirige a /login (una sola vez).
if ($isAdmin && !$isLogin && !$isCallback && empty($_SESSION['user_id'])) {
    header("Location: $ADMIN_BASE/login", true, 302);
    exit;
}

// Redirección a login si no está autenticado
$publicRoutes = ['/login'];
if (!isset($_SESSION['user']) && !in_array($uri, $publicRoutes)) {
    header('Location: ' . $base . '/login');
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



        // GET /inquilino/{slug}/archivos-presignados
case preg_match('#^/inquilino/([^/]+)/archivos-presignados$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET':
    require __DIR__ . '/Controllers/InquilinoController.php';
    (new \App\Controllers\InquilinoController())->archivosPresignados(urldecode($m[1]));
    exit;
    break;

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

    // Asesores - formulario nuevo
    case $uri === '/asesores/create':
        require __DIR__ . '/Controllers/AsesorController.php';
        (new \App\Controllers\AsesorController())->create();
        exit;
        break;

    // Asesores - almacenar
    case $uri === '/asesores/store':
        require __DIR__ . '/Controllers/AsesorController.php';
        (new \App\Controllers\AsesorController())->store();
        exit;
        break;

    // Asesores - editar
    case preg_match('#^/asesores/edit$#', $uri):
        require __DIR__ . '/Controllers/AsesorController.php';
        (new \App\Controllers\AsesorController())->edit();
        exit;
        break;

    // Asesores - actualizar
    case $uri === '/asesores/update':
        require __DIR__ . '/Controllers/AsesorController.php';
        (new \App\Controllers\AsesorController())->update();
        exit;
        break;

    // Arrendadores - listado
    case $uri === '/arrendadores':
        require __DIR__ . '/Controllers/ArrendadorController.php';
        (new \App\Controllers\ArrendadorController())->index();
        exit;
        break;

    // Arrendador detalle por slug
    case preg_match('#^/arrendadores/([a-zA-Z0-9-]+)$#', $uri, $m):
        require __DIR__ . '/Controllers/ArrendadorController.php';
        (new \App\Controllers\ArrendadorController())->detalle($m[1]);
        exit;
        break;

    // Arrendador editar
    case preg_match('#^/arrendadores/editar/(\d+)$#', $uri, $m):
        require __DIR__ . '/Controllers/ArrendadorController.php';
        (new \App\Controllers\ArrendadorController())->editar($m[1]);
        exit;
        break;

    case $uri === '/arrendadores/update':
        require __DIR__ . '/Controllers/ArrendadorController.php';
        (new \App\Controllers\ArrendadorController())->update();
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

    case preg_match('#^/inmuebles/editar/(\d+)$#', $uri, $m):
        require __DIR__ . '/Controllers/InmuebleController.php';
        (new \App\Controllers\InmuebleController())->editar((int)$m[1]);
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

    case preg_match('#^/inmuebles/por-arrendador/(\d+)$#', $uri, $m):
        require __DIR__ . '/Controllers/InmuebleController.php';
        (new \App\Controllers\InmuebleController())->inmueblesPorArrendador((int)$m[1]);
        exit;
        break;

    case preg_match('#^/inmuebles/info/(\d+)$#', $uri, $m):
        require __DIR__ . '/Controllers/InmuebleController.php';
        (new \App\Controllers\InmuebleController())->info((int)$m[1]);
        exit;
        break;

    case preg_match('#^/inmuebles/(\d+)$#', $uri, $m):
        require __DIR__ . '/Controllers/InmuebleController.php';
        (new \App\Controllers\InmuebleController())->ver((int)$m[1]);
        exit;
        break;

    // Nuevo inmueble
    case preg_match('#^/arrendadores/(\d+)/inmueble/nuevo$#', $uri, $m):
        require __DIR__ . '/Controllers/ArrendadorController.php';
        (new \App\Controllers\ArrendadorController())->nuevoInmueble($m[1]);
        exit;
        break;

    // Nueva póliza
    case preg_match('#^/arrendadores/(\d+)/poliza/nueva$#', $uri, $m):
        require __DIR__ . '/Controllers/ArrendadorController.php';
        (new \App\Controllers\ArrendadorController())->nuevaPoliza($m[1]);
        exit;
        break;

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
    
    case $uri === '/arrendador/update-ajax' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/ArrendadorController.php';
        (new \App\Controllers\ArrendadorController)->updateAjax();
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

// Prospecto - listado principal
case $uri === '/inquilino' || $uri === '/inquilino/index':
    require __DIR__ . '/Controllers/InquilinoController.php';
    (new \App\Controllers\InquilinoController())->index();
    exit;
    break;

    // Guardar validaciones (AJAX)
case $uri === '/inquilino/editar-validaciones' && $_SERVER['REQUEST_METHOD'] === 'POST':
    require __DIR__ . '/Controllers/InquilinoController.php';
    (new \App\Controllers\InquilinoController())->editarValidaciones();
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

    // Edición AJAX: Historial de Vivienda
    case $uri === '/inquilino/editar_historial_vivienda' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/InquilinoController.php';
        (new \App\Controllers\InquilinoController())->editarHistorialVivienda();
        exit;
        break;
    
    // Subida de archivo nuevo (AJAX POST)
    case $uri === '/inquilino/subir-archivo' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/InquilinoController.php';
        (new \App\Controllers\InquilinoController())->subirArchivo();
        exit;
        break;

    // Reemplazo de archivo (AJAX POST)
    case $uri === '/inquilino/reemplazar_archivo' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/InquilinoController.php';
        (new \App\Controllers\InquilinoController())->reemplazarArchivo();
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
          ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['check']) && in_array($_GET['check'], ['archivos','faces', 'ocr', 'parse', 'nombres', 'kv', 'match', 'save_match', 'save_face','status','ingresos_list', 'ingresos_ocr','status','resumen_full','verificamex']))
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

    case $uri === '/inquilino/editar_asesor' && $_SERVER['REQUEST_METHOD'] === 'POST':
        require __DIR__ . '/Controllers/InquilinoController.php';
        (new \App\Controllers\InquilinoController())->editarAsesor();
        exit;
        break;

case preg_match('#^/inquilino/([^/]+)/validaciones$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET':
    require __DIR__ . '/Controllers/InquilinoController.php';
    (new \App\Controllers\InquilinoController())->validaciones($m[1]); // $m[1] = slug
    exit;
    break;

// Actualizar estatus de inquilino
case preg_match('#^/inquilino/editar-status$#', $uri) && $_SERVER['REQUEST_METHOD'] === 'POST':
    require __DIR__ . '/Controllers/InquilinoController.php';
    (new \App\Controllers\InquilinoController())->editarStatus();
    exit;
    break;


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



    // Prospecto - detalle por slug
    case preg_match('#^/inquilino/([a-z0-9-]+)$#', $uri, $matches):
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
