<?php

declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . '/../Core/RequestContext.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Models/InquilinoModel.php';
require_once __DIR__ . '/../Models/AsesorModel.php';
require_once __DIR__ . '/../Helpers/NormalizadoHelper.php';
require_once __DIR__ . '/../Helpers/S3Helper.php';
require_once __DIR__ . '/../Helpers/SlugHelper.php';

use App\Core\RequestContext;
use App\Helpers\NormalizadoHelper;
use App\Helpers\S3Helper;
use App\Helpers\SlugHelper;
use App\Middleware\AuthMiddleware;
use App\Models\AsesorModel;
use App\Models\InquilinoModel;
use RuntimeException;

class InquilinoController
{
    /** @var InquilinoModel */
    private $model;
    private AsesorModel $asesorModel;

    public function __construct()
    {
        // Verificación de sesión en cada request del controlador
        AuthMiddleware::verificarSesion();
        $this->model = new InquilinoModel();
        $this->asesorModel = new AsesorModel();
    }

    /**
     * Index con búsqueda de inquilinos
     */
    public function index()
    {
        $s3    = new S3Helper('inquilinos');
        $query = NormalizadoHelper::lower(trim($_GET['q'] ?? ''));

        // 1) Buscar inquilinos (el modelo devuelve profile + archivos_ids + validaciones_ids + polizas_ids)
        $inquilinos = $query !== '' ? $this->model->searchByTerm($query) : [];

        foreach ($inquilinos as &$inq) {

            $selfieUrl = null;

            // 🔹 Transformar archivos en presigned URLs
            if (!empty($inq['archivos'])) {
                foreach ($inq['archivos'] as &$archivo) {
                    if (!empty($archivo['s3_key'])) {
                        $archivo['url'] = $s3->getPresignedUrl($archivo['s3_key']);
                    }
                }
                unset($archivo);

                // 🔹 Buscar selfie
                foreach ($inq['archivos'] as $archivo) {
                    if (strtolower($archivo['tipo'] ?? '') === 'selfie') {
                        if (!empty($archivo['url'])) {
                            $selfieUrl = $archivo['url'];
                        } elseif (!empty($archivo['s3_key'])) {
                            $selfieUrl = $s3->getPresignedUrl($archivo['s3_key']);
                        }
                        break;
                    }
                }
            }

            $inq['selfie_url'] = $selfieUrl; // listo para la vista

            // 🔹 Resolver nombre con fallback
            if (!empty($inq['profile']['nombre'])) {
                $nombreCompleto = trim($inq['profile']['nombre']);
            } else {
                $nombreCompleto = trim(
                    ($inq['profile']['nombre_inquilino'] ?? '') . ' ' .
                        ($inq['profile']['apellidop_inquilino'] ?? '') . ' ' .
                        ($inq['profile']['apellidom_inquilino'] ?? '')
                );
            }

            // 🔹 Slug amigable
            $pk = $inq['profile']['pk'] ?? '';   // ej. inq#1241 / obl#99 / fia#77
            $id = trim(str_replace(['inq#', 'obl#', 'fia#'], '', (string) $pk));
            $slugBase = SlugHelper::fromName($nombreCompleto);
            $inq['profile']['slug'] = $id !== '' ? $id . '-' . $slugBase : $slugBase;

            // 🔹 Mantener limpio el resultado: solo profile + selfie_url
            $inq = [
                'profile'    => $inq['profile'],
                'selfie_url' => $inq['selfie_url'],
            ];
        }
        unset($inq);

        // 3) Preparar datos para la vista
        $title       = 'Inquilinos - AS';
        $headerTitle = 'Inquilinos';
        $contentView = __DIR__ . '/../Views/inquilino/index.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Actualiza datos personales principales del inquilino.
     */
    public function editarDatosPersonales(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Metodo no permitido']);
            return;
        }

        $pk = trim($_POST['pk'] ?? '');
        if ($pk === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'PK requerida']);
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0 && preg_match('/^[a-z]+#(\d+)$/i', $pk, $m)) {
            $id = (int)$m[1];
        }

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'ID invalido']);
            return;
        }

        $tipoPersona = NormalizadoHelper::lower(trim($_POST['tipo'] ?? 'inquilino'));
        $nombre    = NormalizadoHelper::lower(trim($_POST['nombre_inquilino'] ?? ''));
        $apPaterno = NormalizadoHelper::lower(trim($_POST['apellidop_inquilino'] ?? ''));
        $apMaterno = NormalizadoHelper::lower(trim($_POST['apellidom_inquilino'] ?? ''));
        $email     = NormalizadoHelper::lower(trim($_POST['email'] ?? ''));
        $celular   = NormalizadoHelper::lower(trim($_POST['celular'] ?? ''));
        $curp      = NormalizadoHelper::lower(trim($_POST['curp'] ?? ''));
        $rfc       = NormalizadoHelper::lower(trim($_POST['rfc'] ?? ''));
        $estadoCivil = NormalizadoHelper::lower(trim($_POST['estadocivil'] ?? ''));
        $nacionalidad = NormalizadoHelper::lower(trim($_POST['nacionalidad'] ?? ''));
        $tipoId    = NormalizadoHelper::lower(trim($_POST['tipo_id'] ?? ''));
        $numId     = NormalizadoHelper::lower(trim($_POST['num_id'] ?? ''));

        $nombreCompleto = trim($nombre . ' ' . $apPaterno . ' ' . $apMaterno);
        $slugBase = SlugHelper::fromName($nombreCompleto !== '' ? $nombreCompleto : $pk);
        $slug = $id > 0 ? $id . '-' . $slugBase : $slugBase;

        try {
            $ok = $this->model->actualizarDatosPersonalesPorPk($pk, [
                'tipo'                  => $tipoPersona,
                'nombre_inquilino'     => $nombre,
                'apellidop_inquilino'  => $apPaterno,
                'apellidom_inquilino'  => $apMaterno,
                'nombre'               => $nombreCompleto,
                'email'                => $email,
                'celular'              => $celular,
                'curp'                 => $curp,
                'rfc'                  => $rfc,
                'estadocivil'          => $estadoCivil,
                'nacionalidad'         => $nacionalidad,
                'tipo_id'              => $tipoId,
                'num_id'               => $numId,
                'slug'                 => $slug,
            ]);

            echo json_encode([
                'ok'      => $ok,
                'mensaje' => $ok ? 'Datos personales actualizados.' : 'No fue posible actualizar los datos.',
                'slug'    => $slug,
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Actualiza el domicilio principal del inquilino.
     */
    public function editarDomicilio(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Metodo no permitido']);
            return;
        }

        $pk = trim($_POST['pk'] ?? '');
        if ($pk === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'PK requerida']);
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0 && preg_match('/^[a-z]+#(\d+)$/i', $pk, $m)) {
            $id = (int)$m[1];
        }

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'ID invalido']);
            return;
        }

        $domicilio = [
            'calle'         => NormalizadoHelper::lower(trim($_POST['calle'] ?? '')),
            'num_exterior'  => NormalizadoHelper::lower(trim($_POST['num_exterior'] ?? '')),
            'num_interior'  => NormalizadoHelper::lower(trim($_POST['num_interior'] ?? '')),
            'colonia'       => NormalizadoHelper::lower(trim($_POST['colonia'] ?? '')),
            'alcaldia'      => NormalizadoHelper::lower(trim($_POST['alcaldia'] ?? '')),
            'ciudad'        => NormalizadoHelper::lower(trim($_POST['ciudad'] ?? '')),
            'codigo_postal' => NormalizadoHelper::lower(trim($_POST['codigo_postal'] ?? '')),
        ];

        try {
            $ok = $this->model->actualizarDomicilioPorPk($pk, $domicilio);

            echo json_encode([
                'ok'      => $ok,
                'mensaje' => $ok ? 'Domicilio actualizado.' : 'No fue posible actualizar el domicilio.',
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'ok'    => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Actualiza la información laboral del inquilino.
     */
    public function editarTrabajo(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Metodo no permitido']);
            return;
        }

        $pk = trim($_POST['pk'] ?? '');
        if ($pk === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'PK requerida']);
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0 && preg_match('/^[a-z]+#(\d+)$/i', $pk, $m)) {
            $id = (int)$m[1];
        }

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'ID invalido']);
            return;
        }

        $empresa = trim((string)($_POST['empresa'] ?? ''));
        $puesto  = trim((string)($_POST['puesto'] ?? ''));

        if ($empresa === '' || $puesto === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Empresa y puesto son obligatorios']);
            return;
        }

        $parseMonto = static function ($valor): ?float {
            if ($valor === null) {
                return null;
            }

            if (!is_string($valor)) {
                $valor = (string)$valor;
            }

            $limpio = preg_replace('/[^0-9.,-]/', '', $valor ?? '');
            $limpio = str_replace(',', '', (string)$limpio);
            $limpio = trim((string)$limpio);

            if ($limpio === '' || $limpio === '-') {
                return null;
            }

            return (float)$limpio;
        };

        $trabajo = [
            'empresa'           => $empresa,
            'puesto'            => $puesto,
            'direccion_empresa' => trim((string)($_POST['direccion_empresa'] ?? '')),
            'telefono_empresa'  => trim((string)($_POST['telefono_empresa'] ?? '')),
            'antiguedad'        => trim((string)($_POST['antiguedad'] ?? '')),
            'sueldo'            => $parseMonto($_POST['sueldo'] ?? null),
            'otrosingresos'     => $parseMonto($_POST['otrosingresos'] ?? null),
            'nombre_jefe'       => trim((string)($_POST['nombre_jefe'] ?? '')),
            'tel_jefe'          => trim((string)($_POST['tel_jefe'] ?? '')),
            'web_empresa'       => trim((string)($_POST['web_empresa'] ?? '')),
        ];

        try {
            $ok = $this->model->actualizarTrabajoPorPk($pk, $trabajo);

            echo json_encode([
                'ok'      => $ok,
                'mensaje' => $ok ? 'Información laboral actualizada.' : 'No fue posible actualizar la información laboral.',
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'ok'    => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Actualiza la información del inmueble en garantía registrado para el fiador.
     */
    public function editarFiador(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Metodo no permitido']);
            return;
        }

        $pk = trim($_POST['pk'] ?? '');
        if ($pk === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'PK requerida']);
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0 && preg_match('/^[a-z]+#(\d+)$/i', $pk, $m)) {
            $id = (int)$m[1];
        }

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'ID invalido']);
            return;
        }

        $fiador = [
            'calle_inmueble'   => trim((string)($_POST['calle_inmueble'] ?? '')),
            'num_ext_inmueble' => trim((string)($_POST['num_ext_inmueble'] ?? '')),
            'num_int_inmueble' => trim((string)($_POST['num_int_inmueble'] ?? '')),
            'colonia_inmueble' => trim((string)($_POST['colonia_inmueble'] ?? '')),
            'alcaldia_inmueble'=> trim((string)($_POST['alcaldia_inmueble'] ?? '')),
            'estado_inmueble'  => trim((string)($_POST['estado_inmueble'] ?? '')),
            'cp_inmueble'      => trim((string)($_POST['cp_inmueble'] ?? '')),
            'numero_escritura' => trim((string)($_POST['numero_escritura'] ?? '')),
            'fecha_escritura'  => trim((string)($_POST['fecha_escritura'] ?? '')),
            'numero_notario'   => trim((string)($_POST['numero_notario'] ?? '')),
            'nombre_notario'   => trim((string)($_POST['nombre_notario'] ?? '')),
            'estado_notario'   => trim((string)($_POST['estado_notario'] ?? '')),
            'folio_real'       => trim((string)($_POST['folio_real'] ?? '')),
        ];

        $camposRequeridos = [
            'calle_inmueble',
            'num_ext_inmueble',
            'colonia_inmueble',
            'alcaldia_inmueble',
            'estado_inmueble',
            'cp_inmueble',
        ];

        foreach ($camposRequeridos as $campo) {
            if ($fiador[$campo] === '') {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Completa todos los campos obligatorios del inmueble']);
                return;
            }
        }

        try {
            $ok = $this->model->actualizarFiadorPorPk($pk, $fiador);

            echo json_encode([
                'ok'      => $ok,
                'mensaje' => $ok ? 'Datos del fiador actualizados.' : 'No fue posible actualizar la información del fiador.',
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'ok'    => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Actualiza el historial de vivienda del inquilino.
     */
    public function editarHistorialVivienda(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Metodo no permitido']);
            return;
        }

        $pk = trim((string)($_POST['pk'] ?? ''));
        if ($pk === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'PK requerida']);
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0 && preg_match('/^[a-z]+#(\d+)$/i', $pk, $m)) {
            $id = (int)$m[1];
        }

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'ID invalido']);
            return;
        }

        $parseMonto = static function ($valor): ?float {
            if ($valor === null) {
                return null;
            }

            if (!is_string($valor)) {
                $valor = (string)$valor;
            }

            $limpio = preg_replace('/[^0-9.,-]/', '', $valor ?? '');
            $limpio = str_replace(',', '', (string)$limpio);
            $limpio = trim((string)$limpio);

            if ($limpio === '' || $limpio === '-') {
                return null;
            }

            return (float)$limpio;
        };

        $historial = [
            'vive_actualmente'      => NormalizadoHelper::lower(trim((string)($_POST['vive_actualmente'] ?? ''))),
            'renta_actualmente'     => NormalizadoHelper::lower(trim((string)($_POST['renta_actualmente'] ?? ''))),
            'arrendador_actual'     => NormalizadoHelper::lower(trim((string)($_POST['arrendador_actual'] ?? ''))),
            'cel_arrendador_actual' => trim((string)($_POST['cel_arrendador_actual'] ?? '')),
            'monto_renta_actual'    => $parseMonto($_POST['monto_renta_actual'] ?? null),
            'tiempo_habitacion_actual' => NormalizadoHelper::lower(trim((string)($_POST['tiempo_habitacion_actual'] ?? ''))),
            'motivo_arrendamiento'  => NormalizadoHelper::lower(trim((string)($_POST['motivo_arrendamiento'] ?? ''))),
        ];

        try {
            $ok = $this->model->actualizarHistorialViviendaPorPk($pk, $historial);

            echo json_encode([
                'ok'      => $ok,
                'mensaje' => $ok ? 'Historial de vivienda actualizado.' : 'No fue posible actualizar el historial de vivienda.',
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'ok'    => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Muestra el detalle de un inquilino a partir del slug amigable.
     */
    public function subirArchivo(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Metodo no permitido']);
            return;
        }

        $idInquilino = (int)($_POST['id_inquilino'] ?? 0);
        $tipoArchivo = trim((string)($_POST['tipo'] ?? ''));
        $categoria = trim((string)($_POST['categoria'] ?? '')) ?: null;

        if ($idInquilino <= 0 || $tipoArchivo === '' || empty($_FILES['archivo'])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Datos incompletos']);
            return;
        }

        try {
            $archivo = $this->manejarSubidaArchivo($idInquilino, $tipoArchivo, $_FILES['archivo'], $categoria);

            echo json_encode([
                'ok'      => true,
                'archivo' => $archivo,
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    public function reemplazarArchivo(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Metodo no permitido']);
            return;
        }

        $idInquilino = (int)($_POST['id_inquilino'] ?? 0);
        $tipoArchivo = trim((string)($_POST['tipo'] ?? ''));
        $archivoId   = trim((string)($_POST['archivo_id'] ?? ''));
        $categoria   = trim((string)($_POST['categoria'] ?? '')) ?: null;

        if ($idInquilino <= 0 || $tipoArchivo === '' || $archivoId === '' || empty($_FILES['archivo'])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Datos incompletos']);
            return;
        }

        try {
            $archivoActual = $this->model->obtenerArchivo($idInquilino, $archivoId);
            if (!$archivoActual) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'error' => 'Archivo no encontrado']);
                return;
            }

            $s3 = new S3Helper('inquilinos');

            $this->model->eliminarArchivo($idInquilino, $archivoId);

            if (!empty($archivoActual['s3_key'])) {
                $s3->deleteFile((string)$archivoActual['s3_key']);
            }

            $archivo = $this->manejarSubidaArchivo($idInquilino, $tipoArchivo, $_FILES['archivo'], $categoria, $s3);

            echo json_encode([
                'ok'      => true,
                'archivo' => $archivo,
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Valida y sube un archivo a S3, registrándolo en MySQL.
     */
    private function manejarSubidaArchivo(
        int $idInquilino,
        string $tipoArchivo,
        array $archivo,
        ?string $categoria = null,
        ?S3Helper $s3 = null
    ): array {
        $tipoArchivo = strtolower(trim($tipoArchivo));
        $tipoArchivo = preg_replace('/[^a-z0-9_\-]/', '_', $tipoArchivo) ?: 'archivo';

        if (empty($archivo['tmp_name']) || !is_uploaded_file($archivo['tmp_name'])) {
            throw new \RuntimeException('Archivo no recibido');
        }

        $mime = '';
        if (function_exists('mime_content_type')) {
            $mime = (string)@mime_content_type($archivo['tmp_name']);
        }
        if ($mime === '') {
            $mime = (string)($archivo['type'] ?? '');
        }
        $mime = strtolower($mime);

        $permitidos = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/webp',
            'application/pdf',
        ];

        if (!in_array($mime, $permitidos, true)) {
            throw new \RuntimeException('Tipo de archivo no permitido');
        }

        $inquilino = $this->model->obtenerPorId($idInquilino);
        if (!$inquilino) {
            throw new \RuntimeException('Inquilino no encontrado');
        }

        $pk = (string)($inquilino['pk'] ?? $inquilino['profile']['pk'] ?? '');
        if ($pk === '') {
            throw new \RuntimeException('PK no disponible para el inquilino');
        }

        [$prefix, $idStr] = explode('#', $pk . '#', 2);
        $prefix = strtolower($prefix ?: 'inq');

        $idReal = (int)($inquilino['id'] ?? 0);
        if ($idReal <= 0) {
            $idReal = $idInquilino > 0 ? $idInquilino : (int)$idStr;
        }

        $nombreCompleto = trim((string)($inquilino['nombre'] ?? ''));
        if ($nombreCompleto === '') {
            $nombreCompleto = trim(
                ($inquilino['nombre_inquilino'] ?? '') . ' ' .
                ($inquilino['apellidop_inquilino'] ?? '') . ' ' .
                ($inquilino['apellidom_inquilino'] ?? '')
            );
        }
        if ($nombreCompleto === '') {
            $nombreCompleto = $pk;
        }

        $ext = strtolower(pathinfo($archivo['name'] ?? '', PATHINFO_EXTENSION));
        if ($ext === '') {
            $ext = $this->extensionDesdeMime($mime);
        }
        if ($ext === '') {
            throw new \RuntimeException('Extensión de archivo no reconocida');
        }

        $token = strtolower(bin2hex(random_bytes(6)));

        $s3Key = NormalizadoHelper::generarS3Key(
            $prefix,
            $idReal,
            $nombreCompleto,
            $tipoArchivo,
            $ext,
            $token
        );

        $s3 = $s3 ?? new S3Helper('inquilinos');

        if (!$s3->uploadFileWithKey($archivo, $s3Key)) {
            throw new \RuntimeException('Error al subir archivo a S3');
        }

        $meta = [
            'mime_type'     => $mime,
            'size'          => (int)($archivo['size'] ?? 0),
            'original_name' => (string)($archivo['name'] ?? ''),
        ];

        if ($categoria) {
            $meta['categoria'] = $categoria;
        }

        $registro = $this->model->registrarArchivo($idInquilino, $tipoArchivo, $s3Key, $meta, $token);
        $registro['url'] = $s3->getPresignedUrl($s3Key);

        return $registro;
    }

    public function eliminarArchivo(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Metodo no permitido']);
            return;
        }

        $idInquilino = (int)($_POST['id_inquilino'] ?? 0);
        $archivoId   = trim((string)($_POST['archivo_id'] ?? ''));

        if ($idInquilino <= 0 || $archivoId === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Datos incompletos']);
            return;
        }

        try {
            $archivo = $this->model->obtenerArchivo($idInquilino, $archivoId);
            if (!$archivo) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'error' => 'Archivo no encontrado']);
                return;
            }

            $s3 = new S3Helper('inquilinos');

            $this->model->eliminarArchivo($idInquilino, $archivoId);

            if (!empty($archivo['s3_key'])) {
                $s3->deleteFile((string)$archivo['s3_key']);
            }

            echo json_encode(['ok' => true]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    public function eliminar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Metodo no permitido']);
            return;
        }

        $idInquilino = (int)($_POST['id'] ?? $_POST['id_inquilino'] ?? 0);

        if ($idInquilino <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'ID de inquilino inválido']);
            return;
        }

        try {
            $this->model->eliminarInquilino($idInquilino);
            echo json_encode(['ok' => true]);
        } catch (RuntimeException $e) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'No se pudo eliminar al inquilino']);
        }
    }

    public function editarValidaciones(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Metodo no permitido']);
            return;
        }

        $idInquilino = (int)($_POST['id_inquilino'] ?? 0);
        if ($idInquilino <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'id_inquilino requerido']);
            return;
        }

        $campos = [
            'proceso_validacion_archivos'    => 'archivos',
            'proceso_validacion_rostro'      => 'rostro',
            'proceso_validacion_id'          => 'identidad',
            'proceso_validacion_documentos'  => 'documentos',
            'proceso_validacion_ingresos'    => 'ingresos',
            'proceso_pago_inicial'           => 'pago_inicial',
            'proceso_inv_demandas'           => 'demandas',
            'proceso_validacion_verificamex' => 'verificamex',
        ];

        $actualizados = [];

        foreach ($campos as $campo => $tipo) {
            if (!array_key_exists($campo, $_POST)) {
                continue;
            }

            $estado = (int)$_POST[$campo];
            if (!in_array($estado, [0, 1, 2], true)) {
                $estado = 2;
            }

            $resumenKey = str_replace('proceso_', '', $campo) . '_resumen';
            $resumen = $_POST[$resumenKey] ?? null;

            $jsonKey = $campo . '_json';
            $payload = [];
            if (isset($_POST[$jsonKey])) {
                $decoded = json_decode((string)$_POST[$jsonKey], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $payload = $decoded;
                } else {
                    $payload = ['raw' => (string)$_POST[$jsonKey]];
                }
            }

            if (empty($payload) || !is_array($payload)) {
                $payload = [];
            }

            $payload['timestamp'] = $payload['timestamp'] ?? date('c');
            if (!isset($payload['estado'])) {
                $payload['estado'] = match ($estado) {
                    1       => 'confirmado',
                    0       => 'no_ok',
                    default => 'pendiente',
                };
            }

            if (!isset($payload['origen'])) {
                $payload['origen'] = 'manual';
            }

            if (!isset($payload['usuario'])) {
                $payload['usuario'] = $this->resolvePayloadUser();
            }

            $descripciones = [
                'archivos'     => 'Validación de archivos',
                'rostro'       => 'Validación de rostro',
                'identidad'    => 'Validación de identidad',
                'documentos'   => 'Validación de documentos',
                'ingresos'     => 'Validación de ingresos',
                'pago_inicial' => 'Pago inicial',
                'demandas'     => 'Investigación de demandas',
                'verificamex'  => 'Validación VerificaMex',
            ];

            if ($resumen === null) {
                $baseTexto = $descripciones[$tipo] ?? ('Validación de ' . str_replace('_', ' ', $tipo));
                $resumen = $estado === 1
                    ? $baseTexto . ' confirmada manualmente'
                    : $baseTexto . ' pendiente';
            }

            try {
                $ok = false;
                switch ($campo) {
                    case 'proceso_validacion_archivos':
                        $ok = $this->model->guardarValidacionArchivos($idInquilino, $estado, $payload, $resumen);
                        $actualizados[] = $tipo;
                        break;
                    case 'proceso_validacion_rostro':
                        $ok = $this->model->guardarValidacionRostro($idInquilino, $estado, $payload, $resumen);
                        $actualizados[] = $tipo;
                        break;
                    case 'proceso_validacion_id':
                        $ok = $this->model->guardarValidacionIdentidad($idInquilino, $payload, $resumen, $estado);
                        $actualizados[] = $tipo;
                        break;
                    case 'proceso_validacion_documentos':
                        $ok = $this->model->guardarValidacionDocumentos($idInquilino, $estado, $payload, $resumen);
                        $actualizados[] = $tipo;
                        break;
                    case 'proceso_validacion_ingresos':
                        $ok = $this->model->guardarValidacionIngresosSimple($idInquilino, $estado, $payload, $resumen);
                        $actualizados[] = $tipo;
                        break;
                    case 'proceso_pago_inicial':
                        $ok = $this->model->guardarPagoInicial($idInquilino, $estado, $payload, $resumen);
                        $actualizados[] = $tipo;
                        break;
                    case 'proceso_inv_demandas':
                        $ok = $this->model->guardarInvestigacionDemandas($idInquilino, $estado, $payload, $resumen);
                        $actualizados[] = $tipo;
                        break;
                    case 'proceso_validacion_verificamex':
                        $textoResumen = $resumen ?? ($estado === 1
                            ? 'Validación VerificaMex confirmada manualmente'
                            : 'Validación VerificaMex pendiente');
                        $ok = $this->model->guardarValidacionVerificaMex($idInquilino, $estado, $payload, $textoResumen);
                        $actualizados[] = $tipo;
                        break;
                }

                if (!$ok) {
                    throw new \RuntimeException('No se pudo guardar el estado de ' . $campo);
                }
            } catch (\Throwable $e) {
                http_response_code(500);
                echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
                return;
            }
        }

        if (!$actualizados) {
            echo json_encode(['ok' => false, 'error' => 'Sin cambios por guardar']);
            return;
        }

        echo json_encode(['ok' => true, 'actualizados' => $actualizados]);
    }

    public function editarStatus(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Metodo no permitido']);
            return;
        }

        $idInquilino = (int)($_POST['id_inquilino'] ?? 0);
        $status = trim((string)($_POST['status'] ?? ''));

        if ($idInquilino <= 0 || $status === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Datos incompletos']);
            return;
        }

        if (!in_array($status, ['1', '2', '3', '4'], true)) {
            $status = '1';
        }

        try {
            $ok = $this->model->actualizarStatus($idInquilino, $status);
            if (!$ok) {
                http_response_code(500);
                echo json_encode(['ok' => false, 'error' => 'No se pudo actualizar el status']);
                return;
            }

            echo json_encode(['ok' => true, 'status' => $status]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Determina extensión en base al mime type recibido.
     */
    private function extensionDesdeMime(string $mime): string
    {
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                return 'jpg';
            case 'image/png':
                return 'png';
            case 'image/webp':
                return 'webp';
            case 'application/pdf':
                return 'pdf';
            default:
                return '';
        }
    }

    public function mostrar(string $slug): void
    {
        $slug = trim($slug);
        if ($slug === '') {
            http_response_code(404);
            include __DIR__ . '/../Views/404.php';
            return;
        }

        $inquilino = $this->model->obtenerPorSlug($slug);
        if (!$inquilino) {
            http_response_code(404);
            include __DIR__ . '/../Views/404.php';
            return;
        }

        $profile  = $inquilino['profile'] ?? [];
        $archivos = $inquilino['archivos'] ?? [];
        $validaciones = $inquilino['validaciones'] ?? [];
        $polizas = $inquilino['polizas'] ?? [];

        $asesorActual = $this->resolverAsesorDesdeProfile($profile);
        if ($asesorActual !== null) {
            $profile['asesor']    = $asesorActual;
            $profile['asesor_id'] = $asesorActual['id'] ?? ($profile['asesor_id'] ?? null);
            if (!isset($profile['asesor_pk']) && isset($asesorActual['pk'])) {
                $profile['asesor_pk'] = $asesorActual['pk'];
            }
        } else {
            $profile['asesor'] = [];
        }

        $asesores = $this->asesorModel->all();

        $s3 = new S3Helper('inquilinos');
        foreach ($archivos as &$archivo) {
            if (!empty($archivo['s3_key'])) {
                $archivo['url'] = $s3->getPresignedUrl($archivo['s3_key']);
            }
        }
        unset($archivo);

        $selfieUrl = $inquilino['selfie_url'] ?? null;
        if ($selfieUrl && strpos($selfieUrl, 'http') !== 0) {
            $selfieUrl = $s3->getPresignedUrl($selfieUrl);
        }

        // Ordena las pólizas por vigencia descendente si está disponible
        if (!empty($polizas)) {
            usort($polizas, static function ($a, $b) {
                return strcmp(($b['vigencia'] ?? ''), ($a['vigencia'] ?? ''));
            });
        }

        $title       = 'Inquilino - ' . ucwords($profile['nombre'] ?? $profile['nombre_inquilino'] ?? '');
        $headerTitle = 'Detalle del inquilino';
        $contentView = __DIR__ . '/../Views/inquilino/detalle.php';

        include __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * @param array<string, mixed> $profile
     * @return array<string, mixed>|null
     */
    private function resolverAsesorDesdeProfile(array $profile): ?array
    {
        $asesor = $profile['asesor'] ?? null;
        if (is_array($asesor) && !empty($asesor)) {
            if (!isset($asesor['id']) && isset($asesor['pk']) && preg_match('/^ase#(\d+)$/i', (string) $asesor['pk'], $m)) {
                $asesor['id'] = (int) $m[1];
            }
            if (!isset($asesor['pk']) && isset($asesor['id'])) {
                $asesor['pk'] = sprintf('ase#%d', (int) $asesor['id']);
            }
            return $asesor;
        }

        $asesorId = null;
        if (isset($profile['asesor_id']) && (int) $profile['asesor_id'] > 0) {
            $asesorId = (int) $profile['asesor_id'];
        } elseif (!empty($profile['asesor_pk']) && preg_match('/^ase#(\d+)$/i', (string) $profile['asesor_pk'], $m)) {
            $asesorId = (int) $m[1];
        }

        if (!$asesorId) {
            return null;
        }

        $asesorData = $this->asesorModel->find($asesorId);
        if ($asesorData === null) {
            return null;
        }

        if (!isset($asesorData['pk'])) {
            $asesorData['pk'] = sprintf('ase#%d', (int) ($asesorData['id'] ?? $asesorId));
        }

        return $asesorData;
    }

    public function editarAsesor(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
            return;
        }

        $idInquilino = (int) ($_POST['id_inquilino'] ?? 0);
        $idAsesor    = (int) ($_POST['id_asesor'] ?? 0);

        if ($idInquilino <= 0 || $idAsesor <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Parámetros inválidos']);
            return;
        }

        try {
            $asesor = $this->asesorModel->find($idAsesor);
            if (!$asesor) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'error' => 'Asesor no encontrado']);
                return;
            }

            $payload = $this->model->cambiarAsesor($idInquilino, $asesor);

            echo json_encode([
                'ok'      => true,
                'mensaje' => 'Asesor actualizado.',
                'asesor'  => $payload,
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'ok'    => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resolvePayloadUser(): ?string
    {
        $contextUser = RequestContext::user();
        if (is_array($contextUser)) {
            return $contextUser['email']
                ?? $contextUser['usuario']
                ?? $contextUser['nombre']
                ?? null;
        }

        if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
            return $_SESSION['user']['email']
                ?? $_SESSION['user']['usuario']
                ?? $_SESSION['user']['nombre']
                ?? null;
        }

        return null;
    }
}
