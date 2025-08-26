<?php
declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Models/InquilinoModel.php';
require_once __DIR__ . '/../Helpers/S3Helper.php';
use App\Helpers\S3Helper;
use App\Middleware\AuthMiddleware;
use App\Models\InquilinoModel;

/**
 * Controlador de Inquilinos
 *
 * Responsabilidades:
 * - Listado con filtros y paginación.
 * - Vista de detalle por slug.
 * - Edición AJAX de secciones (datos personales, domicilio, trabajo, fiador, historial, validaciones, asesor).
 * - Reemplazo de archivos (subida a S3 y actualización en BD).
 *
 * Notas:
 * - Este controller asume que el layout principal se incluye a través de
 *   Views/layouts/main.php usando $contentView (ruta absoluta), y variables
 *   $title y $headerTitle para los encabezados.
 * - Se eliminan funciones de migración/unificación (ya no se usan).
 * - Se homogeneiza el uso de "Views" (no "views") para evitar problemas en Linux.
 */
class InquilinoController
{
    public function __construct()
    {
        // Verificación de sesión en cada request del controlador
        AuthMiddleware::verificarSesion();
    }

    /**
     * Listado de inquilinos con filtros y paginación.
     *
     * GET /inquilino
     * Parámetros:
     * - q:      búsqueda por texto (nombre, email, celular, etc.)
     * - tipo:   tipo de inquilino (p. ej. "Persona Física", "Persona Moral", etc.)
     * - estatus: entero que mapea el campo `status` de la tabla `inquilinos`
     * - pagina: número de página (>=1)
     */
    public function index(): void
    {
        $model = new InquilinoModel();

        $q        = $_GET['q']        ?? '';
        $tipo     = $_GET['tipo']     ?? '';
        $estatus  = $_GET['estatus']  ?? '';
        $pagina   = isset($_GET['pagina']) ? max(1, (int) $_GET['pagina']) : 1;
        $limite   = 9;
        $offset   = ($pagina - 1) * $limite;

        // Modelo
        $inquilinos   = $model->buscarConFiltros($q, $tipo, $estatus, $limite, $offset);
        $total        = $model->contarTotalConFiltros($q, $tipo, $estatus);
        $totalPaginas = (int) ceil(($total ?: 0) / $limite);
        $paginaActual = $pagina;

        // 👇 alias que la vista espera
        $prospectos = $inquilinos;

        // Layout
        $title       = 'Inquilinos - AS';
        $headerTitle = 'Listado de Inquilinos';
        $contentView = __DIR__ . '/../Views/inquilino/index.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }


    /**
     * Muestra el detalle de un inquilino por slug.
     *
     * GET /inquilino/{slug}
     *
     * @param string $slug Slug amigable del inquilino.
     */
    public function mostrar(string $slug): void
    {
        $model     = new InquilinoModel();
        $inquilino = $model->obtenerPorSlug($slug);

        // Lista de asesores (para selector en detalle)
        $asesores = $model->obtenerTodosAsesores();

        if (!$inquilino) {
            http_response_code(404);
            // Vista 404 unificada en "Views"
            require __DIR__ . '/../Views/404.php';
            exit;
        }

        $title       = 'Inquilino - AS';
        $headerTitle = 'Detalle del Inquilino';

        $contentView = __DIR__ . '/../Views/inquilino/detalle.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }


    

    /**
     * Actualiza datos personales de un inquilino (AJAX).
     *
     * POST /inquilino/editar-datos-personales
     * Campos requeridos:
     * - id, nombre_inquilino, apellidop_inquilino, email, celular, rfc, curp, nacionalidad,
     *   estadocivil, conyuge, tipo_id, num_id
     *
     * Respuesta: JSON { ok: bool, error?: string }
     */
    public function editarDatosPersonales(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
            exit;
        }

        $campos = [
            'id',
            'nombre_inquilino', 'apellidop_inquilino', 'apellidom_inquilino',
            'email', 'celular', 'rfc', 'curp', 'nacionalidad',
            'estadocivil', 'conyuge', 'tipo_id', 'num_id',
        ];
        foreach ($campos as $campo) {
            if (!isset($_POST[$campo])) {
                echo json_encode(['ok' => false, 'error' => "Falta el campo {$campo}"]);
                exit;
            }
        }

        $id = (int) $_POST['id'];
        $data = [
            'nombre_inquilino'    => trim((string) $_POST['nombre_inquilino']),
            'apellidop_inquilino' => trim((string) $_POST['apellidop_inquilino']),
            'apellidom_inquilino' => trim((string) $_POST['apellidom_inquilino']),
            'email'               => trim((string) $_POST['email']),
            'celular'             => trim((string) $_POST['celular']),
            'rfc'                 => trim((string) $_POST['rfc']),
            'curp'                => trim((string) $_POST['curp']),
            'nacionalidad'        => trim((string) $_POST['nacionalidad']),
            'estadocivil'         => trim((string) $_POST['estadocivil']),
            'conyuge'             => trim((string) $_POST['conyuge']),
            'tipo_id'             => trim((string) $_POST['tipo_id']),
            'num_id'              => trim((string) $_POST['num_id']),
        ];

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['ok' => false, 'error' => 'Email inválido']);
            exit;
        }
        if ($data['nombre_inquilino'] === '' || $data['apellidop_inquilino'] === '') {
            echo json_encode(['ok' => false, 'error' => 'Nombre y apellido paterno son obligatorios']);
            exit;
        }

        $model  = new InquilinoModel();
        $result = $model->actualizarDatosPersonales($id, $data);

        echo json_encode(['ok' => (bool) $result, 'error' => $result ? null : 'No se pudo actualizar.']);
        exit;
    }

    /**
     * Actualiza domicilio del inquilino (AJAX).
     *
     * POST /inquilino/editar-domicilio
     * Campos requeridos:
     * - id_inquilino, calle, num_exterior, colonia, alcaldia, ciudad, codigo_postal
     *
     * Respuesta: JSON { ok: bool, error?: string }
     */
    public function editarDomicilio(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
            exit;
        }

        $id_inquilino  = $_POST['id_inquilino'] ?? null;
        $calle         = trim((string) ($_POST['calle'] ?? ''));
        $num_exterior  = trim((string) ($_POST['num_exterior'] ?? ''));
        $num_interior  = trim((string) ($_POST['num_interior'] ?? ''));
        $colonia       = trim((string) ($_POST['colonia'] ?? ''));
        $alcaldia      = trim((string) ($_POST['alcaldia'] ?? ''));
        $ciudad        = trim((string) ($_POST['ciudad'] ?? ''));
        $codigo_postal = trim((string) ($_POST['codigo_postal'] ?? ''));

        if (!$id_inquilino || !$calle || !$num_exterior || !$colonia || !$alcaldia || !$ciudad || !$codigo_postal) {
            echo json_encode(['ok' => false, 'error' => 'Faltan campos obligatorios.']);
            exit;
        }

        $model   = new InquilinoModel();
        $success = $model->actualizarDomicilio((int) $id_inquilino, [
            'calle'         => $calle,
            'num_exterior'  => $num_exterior,
            'num_interior'  => $num_interior,
            'colonia'       => $colonia,
            'alcaldia'      => $alcaldia,
            'ciudad'        => $ciudad,
            'codigo_postal' => $codigo_postal,
        ]);

        echo json_encode(['ok' => (bool) $success, 'error' => $success ? null : 'No se pudo actualizar el domicilio.']);
        exit;
    }

    /**
     * Actualiza información laboral del inquilino (AJAX).
     *
     * POST /inquilino/editar-trabajo
     * Campos requeridos:
     * - id_inquilino, empresa, puesto
     * Campos opcionales: direccion_empresa, antiguedad, sueldo, otrosingresos, nombre_jefe, tel_jefe, web_empresa
     *
     * Respuesta: JSON { ok: bool, error?: string }
     */
    public function editarTrabajo(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
            exit;
        }

        $id_inquilino      = $_POST['id_inquilino'] ?? null;
        $empresa           = trim((string) ($_POST['empresa'] ?? ''));
        $puesto            = trim((string) ($_POST['puesto'] ?? ''));
        $direccion_empresa = trim((string) ($_POST['direccion_empresa'] ?? ''));
        $antiguedad        = trim((string) ($_POST['antiguedad'] ?? ''));
        $sueldo            = $_POST['sueldo'] ?? null;
        $otrosingresos     = $_POST['otrosingresos'] ?? null;
        $nombre_jefe       = trim((string) ($_POST['nombre_jefe'] ?? ''));
        $tel_jefe          = trim((string) ($_POST['tel_jefe'] ?? ''));
        $web_empresa       = trim((string) ($_POST['web_empresa'] ?? ''));
        $telefono_empresa  = trim((string) ($_POST['telefono_empresa'] ?? '')); // 👈 nuevo

        if (!$id_inquilino || !$empresa || !$puesto) {
            echo json_encode(['ok' => false, 'error' => 'Faltan campos obligatorios.']);
            exit;
        }

        $model   = new InquilinoModel();
        $success = $model->actualizarTrabajo((int) $id_inquilino, [
            'empresa'           => $empresa,
            'puesto'            => $puesto,
            'direccion_empresa' => $direccion_empresa,
            'antiguedad'        => $antiguedad,
            'sueldo'            => $sueldo,
            'otrosingresos'     => $otrosingresos,
            'nombre_jefe'       => $nombre_jefe,
            'tel_jefe'          => $tel_jefe,
            'web_empresa'       => $web_empresa,
            'telefono_empresa'  => $telefono_empresa, // 👈 nuevo
        ]);

        echo json_encode(['ok' => (bool) $success, 'error' => $success ? null : 'No se pudo actualizar la información de trabajo.']);
        exit;
    }


    /**
     * Actualiza información del fiador del inquilino (AJAX).
     *
     * POST /inquilino/editar-fiador
     * Campos requeridos:
     * - id_inquilino
     * Campos opcionales: calle_inmueble, num_ext_inmueble, num_int_inmueble, colonia_inmueble,
     *                    alcaldia_inmueble, estado_inmueble, numero_escritura, numero_notario,
     *                    estado_notario, folio_real
     *
     * Respuesta: JSON { ok: bool, error?: string }
     */
    public function editarFiador(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
            exit;
        }

        $data = [
            'id_inquilino'      => $_POST['id_inquilino'] ?? null,
            'calle_inmueble'    => trim((string) ($_POST['calle_inmueble'] ?? '')),
            'num_ext_inmueble'  => trim((string) ($_POST['num_ext_inmueble'] ?? '')),
            'num_int_inmueble'  => trim((string) ($_POST['num_int_inmueble'] ?? '')),
            'colonia_inmueble'  => trim((string) ($_POST['colonia_inmueble'] ?? '')),
            'alcaldia_inmueble' => trim((string) ($_POST['alcaldia_inmueble'] ?? '')),
            'estado_inmueble'   => trim((string) ($_POST['estado_inmueble'] ?? '')),
            'numero_escritura'  => trim((string) ($_POST['numero_escritura'] ?? '')),
            'numero_notario'    => trim((string) ($_POST['numero_notario'] ?? '')),
            'estado_notario'    => trim((string) ($_POST['estado_notario'] ?? '')),
            'folio_real'        => trim((string) ($_POST['folio_real'] ?? '')),
        ];

        if (empty($data['id_inquilino'])) {
            echo json_encode(['ok' => false, 'error' => 'Falta el campo id_inquilino']);
            exit;
        }

        $model = new InquilinoModel();
        $ok    = $model->actualizarFiador($data);

        echo json_encode(['ok' => (bool) $ok, 'error' => $ok ? null : 'Error al actualizar datos de fiador']);
        exit;
    }

    /**
     * Actualiza historial de vivienda (AJAX).
     *
     * POST /inquilino/editar-historial-vivienda
     * Campos requeridos:
     * - id
     * Campos opcionales: vive_actualmente, renta_actualmente, arrendador_actual, cel_arrendador_actual,
     *                    monto_renta_actual, tiempo_habitacion_actual, motivo_arrendamiento
     *
     * Respuesta: JSON { ok: bool, error?: string }
     */
    public function editarHistorialVivienda(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
            exit;
        }

        $data = [
            'id'                       => $_POST['id'] ?? null,
            'vive_actualmente'         => trim((string) ($_POST['vive_actualmente'] ?? '')),
            'renta_actualmente'        => trim((string) ($_POST['renta_actualmente'] ?? '')),
            'arrendador_actual'        => trim((string) ($_POST['arrendador_actual'] ?? '')),
            'cel_arrendador_actual'    => trim((string) ($_POST['cel_arrendador_actual'] ?? '')),
            'monto_renta_actual'       => trim((string) ($_POST['monto_renta_actual'] ?? '')),
            'tiempo_habitacion_actual' => trim((string) ($_POST['tiempo_habitacion_actual'] ?? '')),
            'motivo_arrendamiento'     => trim((string) ($_POST['motivo_arrendamiento'] ?? '')),
        ];

        if (empty($data['id'])) {
            echo json_encode(['ok' => false, 'error' => 'Falta el identificador del historial.']);
            exit;
        }

        $model = new InquilinoModel();
        $ok    = $model->actualizarHistorialVivienda($data);

        echo json_encode(['ok' => (bool) $ok, 'error' => $ok ? null : 'No se pudo actualizar el historial de vivienda.']);
        exit;
    }

    /**
     * Reemplaza un archivo del inquilino (sube a S3 y actualiza en BD).
     *
     * POST /inquilino/reemplazar-archivo
     * Campos requeridos:
     * - archivo_id (int), archivo (file)
     * Campos opcionales:
     * - tipo, nombre_inquilino (preferible recuperar el nombre desde BD, pero se admite compatibilidad)
     *
     * Respuesta: JSON { ok: bool, mensaje?: string, error?: string }
     */
    public function reemplazarArchivo(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (
            empty($_POST['archivo_id']) ||
            !isset($_FILES['archivo']) ||
            $_FILES['archivo']['error'] !== UPLOAD_ERR_OK
        ) {
            echo json_encode(['ok' => false, 'error' => 'Datos insuficientes o archivo inválido.']);
            exit;
        }

        $archivo_id = (int) $_POST['archivo_id'];
        $tipo       = $_POST['tipo'] ?? null;

        // Subida a S3
        $s3 = new S3Helper('inquilinos'); // bucketKey configurada en tu s3config
        $nombreInquilino = $_POST['nombre_inquilino'] ?? 'Inquilino';

        // Mantengo el método de helper usado previamente para compatibilidad
        $s3Key = $s3->uploadInquilinoFile($_FILES['archivo'], $nombreInquilino);

        if (!$s3Key) {
            echo json_encode(['ok' => false, 'error' => 'Error al subir archivo a S3.']);
            exit;
        }

        $model  = new InquilinoModel();
        $update = $model->reemplazarArchivo($archivo_id, $s3Key, $tipo);

        if (is_array($update) && !empty($update['ok'])) {
            echo json_encode(['ok' => true, 'mensaje' => 'Archivo reemplazado correctamente.']);
        } else {
            $mensajeError = is_array($update) && !empty($update['error']) ? $update['error'] : 'Error al actualizar archivo.';
            echo json_encode(['ok' => false, 'error' => $mensajeError]);
        }
        exit;
    }

    /**
     * Sube un archivo nuevo del inquilino (a S3) y crea el registro en BD.
     *
     * POST /inquilino/subir-archivo
     * Campos requeridos:
     * - id_inquilino (int), tipo (string: selfie|ine_frontal|ine_reverso|comprobante_ingreso), archivo (file)
     * Opcional:
     * - nombre_inquilino (string) solo para nombrar el objeto en S3
     *
     * Respuesta: JSON { ok: bool, file?: {id,tipo,s3_key,mime_type,size,url}, error?: string }
     */
    public function subirArchivo(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
            exit;
        }

        $idInquilino = isset($_POST['id_inquilino']) ? (int) $_POST['id_inquilino'] : 0;
        $tipo        = $_POST['tipo'] ?? '';
        $fileOk      = isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK;

        $tiposPermitidos = ['selfie', 'ine_frontal', 'ine_reverso', 'pasaporte', 'comprobante_ingreso'];

        if ($idInquilino <= 0 || !$tipo || !in_array($tipo, $tiposPermitidos, true) || !$fileOk) {
            echo json_encode(['ok' => false, 'error' => 'Datos insuficientes o inválidos.']);
            exit;
        }

        try {
            // 1) Subir a S3
            $s3 = new \App\Helpers\S3Helper('inquilinos');
            $nombreInquilino = $_POST['nombre_inquilino'] ?? 'Inquilino';
            $s3Key = $s3->uploadInquilinoFile($_FILES['archivo'], $nombreInquilino);

            if (!$s3Key) {
                echo json_encode(['ok' => false, 'error' => 'No se pudo subir a S3.']);
                exit;
            }

            // 2) Registrar en BD
            $mime = $_FILES['archivo']['type'] ?? null;
            $size = (int)($_FILES['archivo']['size'] ?? 0);

            $model = new \App\Models\InquilinoModel();
            $newId = $model->crearArchivo($idInquilino, $tipo, $s3Key, $mime, $size);

            if (!$newId) {
                echo json_encode(['ok' => false, 'error' => 'No se pudo guardar el archivo en BD.']);
                exit;
            }

            // 3) URL presignada (10 min) para previsualizar de inmediato
            $url = $s3->getPresignedUrl($s3Key, '+10 minutes', [
                'ContentDisposition' => 'inline',
                'ContentType'        => $mime
            ]) ?: '';

            echo json_encode([
                'ok'   => true,
                'file' => [
                    'id'        => $newId,
                    'tipo'      => $tipo,
                    's3_key'    => $s3Key,
                    'mime_type' => $mime,
                    'size'      => $size,
                    'url'       => $url,
                ]
            ]);
            exit;

        } catch (\Throwable $e) {
            error_log('[subirArchivo] ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Excepción en el servidor.']);
            exit;
        }
    }


    /**
     * Actualiza banderas de validaciones y comentarios (AJAX).
     *
     * POST /inquilino/editar-validaciones
     * Campos requeridos:
     * - id_inquilino
     * Resto de campos: se toman del POST tal cual para el modelo.
     *
     * Respuesta: JSON { ok: bool, error?: string }
     */
    public function editarValidaciones(): void
{
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
        exit;
    }
    if (empty($_POST['id_inquilino'])) {
        echo json_encode(['ok' => false, 'error' => 'Faltan datos']);
        exit;
    }

    $id    = (int) $_POST['id_inquilino'];
    $data  = $_POST;
    $debug = (isset($_GET['debug']) && $_GET['debug'] === '1');

    try {
        $model = new InquilinoModel();
        $ok    = $model->actualizarValidaciones($id, $data);

        if (!$ok) {
            $resp = ['ok' => false, 'error' => 'No se pudo actualizar (ver logs)'];
            if ($debug) {
                $resp['debug'] = [
                    'sql'       => $model->getLastSql(),
                    'params'    => $model->getLastParams(),
                    'pdo_error' => $model->getLastError(),
                ];
            }
            echo json_encode($resp);
            exit;
        }

        echo json_encode(['ok' => true]);
        exit;

    } catch (\Throwable $e) {
        error_log('[editarValidaciones] ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'ok'    => false,
            'error' => 'Excepción al actualizar',
            'debug' => $debug ? ['exception' => $e->getMessage()] : null,
        ]);
        exit;
    }
}



    /**
     * Actualiza el asesor asignado al inquilino (AJAX).
     *
     * POST /inquilino/editar-asesor
     * Campos requeridos:
     * - id_inquilino (int), id_asesor (int)
     *
     * Respuesta: JSON { ok: bool, error?: string }
     */
    public function editarAsesor(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
            exit;
        }

        $idInquilino = isset($_POST['id_inquilino']) ? (int) $_POST['id_inquilino'] : null;
        $idAsesor    = isset($_POST['id_asesor']) ? (int) $_POST['id_asesor'] : null;

        if (empty($idInquilino) || empty($idAsesor)) {
            echo json_encode(['ok' => false, 'error' => 'Faltan datos']);
            exit;
        }

        $model = new InquilinoModel();
        $ok    = $model->actualizarAsesor($idInquilino, $idAsesor);

        echo json_encode(['ok' => (bool) $ok, 'error' => $ok ? null : 'No se pudo actualizar el asesor']);
        exit;
    }

    /**
 * Vista de Validaciones del inquilino.
 * GET /inquilino/{slug}/validaciones
 */
public function validaciones(string $slug): void
{
    $model     = new InquilinoModel();
    $inquilino = $model->obtenerPorSlug($slug);

    if (!$inquilino) {
        http_response_code(404);
        require __DIR__ . '/../Views/404.php';
        exit;
    }

    // Archivos (para tarjetas/dropzones)
    try {
        $inquilino['archivos'] = $model->archivosPorInquilinoId((int) $inquilino['id']);
    } catch (\Throwable $e) {
        $inquilino['archivos'] = [];
    }

    /**
     * Base para endpoints AJAX de la vista:
     * 1) Usa ENV ADMIN_BASE_URL si existe.
     * 2) Si no, infiere del directorio del script.
     * 3) Si todo falla, deja cadena vacía (rutas absolutas).
     */
    $admin_base_url = '';
    $envBase = $_ENV['ADMIN_BASE_URL'] ?? getenv('ADMIN_BASE_URL');
    if ($envBase) {
        $admin_base_url = rtrim((string)$envBase, '/');
    } else {
        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        if ($scriptDir !== '' && $scriptDir !== '.' && $scriptDir !== '/') {
            $admin_base_url = $scriptDir;
        }
    }

    // Layout + vista
    $title       = 'Validaciones - AS';
    $headerTitle = 'Validaciones del Inquilino';
    $contentView = __DIR__ . '/../Views/inquilino/validaciones.php';

    // vars disponibles en la vista: $inquilino, $admin_base_url
    include __DIR__ . '/../Views/layouts/main.php';
}

public function archivosPresignados(string $slug): void
{
    header('Content-Type: application/json; charset=utf-8');

    try {
        // 1) Solo BD en el modelo
        $model = new InquilinoModel();
        $files = $model->obtenerArchivosPorSlug($slug);

        // 2) Presignado en el controller (S3Helper)
        $s3 = new S3Helper('inquilinos'); // <-- cambia si usas otro bucketKey
        foreach ($files as &$f) {
            $f['url'] = $s3->getPresignedUrl(
                $f['s3_key'],
                '+10 minutes',
                [
                    'ContentDisposition' => 'inline',
                    'ContentType'        => $f['mime_type'] ?? null,
                ]
            ) ?: '';
        }

        echo json_encode(['ok' => true, 'files' => $files]);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
}

}