<?php

declare(strict_types=1);

// admin/Controllers/ValidacionIdentidadController.php
namespace App\Controllers;

require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Models/InquilinoModel.php';
require_once __DIR__ . '/../Helpers/S3Helper.php';

use App\Middleware\AuthMiddleware;
use App\Models\InquilinoModel;
use App\Helpers\S3Helper;

/**
 * Controlador: Validación de Identidad de Inquilinos
 *
 * Responsabilidades:
 *  - Mostrar pantalla de validación de identidad por slug del inquilino.
 *  - Procesar la validación (stub/API externa).
 *  - Mostrar resultados de la validación.
 *
 * Estructura actual del proyecto:
 *  - Todo lo relativo a inquilinos y sus archivos quedó concentrado en InquilinoModel.
 *  - Se usa S3Helper para convertir s3_key → URL pública.
 *
 * Convenciones:
 *  - Las vistas viven en Views/inquilino/ (respetando mayúsculas/minúsculas).
 *  - El layout principal se incluye vía Views/layouts/main.php, usando $contentView.
 *  - $title y $headerTitle se exponen al layout.
 */
class ValidacionIdentidadController
{
    public function __construct()
    {
        // Aplica verificación de sesión a todo el controlador
        AuthMiddleware::verificarSesion();
    }

    /**
     * Muestra la pantalla principal de validación de identidad para un inquilino.
     *
     * Ruta esperada (ejemplo):
     *  GET /admin/validacion-identidad/{slug}
     *
     * @param string $slug Slug amigable del inquilino (columna `slug` en `inquilinos`).
     */
    public function index(string $slug): void
    {
        if ($slug === '') {
            http_response_code(404);
            echo 'Inquilino no encontrado';
            exit;
        }

        // 1) Obtener datos del inquilino
        $inquilinoModel = new InquilinoModel();
        $inquilino      = $inquilinoModel->obtenerPorSlug($slug);

        if (!$inquilino) {
            http_response_code(404);
            echo 'Inquilino no encontrado';
            exit;
        }

        // 2) Obtener archivos de identidad (concentrado en el InquilinoModel)
        //    Este método debe devolver un arreglo asociativo con claves por tipo,
        //    p. ej.: ['selfie' => 's3/key.jpg', 'ine_frontal' => 's3/key.jpg', ...]
        //    Si tu modelo usa otro nombre de método, cámbialo aquí.
        $archivos = $inquilinoModel->obtenerArchivosIdentidad((int)$inquilino['id']);

        // 3) Construcción de URLs públicas desde s3_key usando S3Helper
        $s3 = new S3Helper('inquilinos'); // bucketKey definido en config/s3config.php

        $url_selfie   = !empty($archivos['selfie'])       ? $s3->getS3Url($archivos['selfie'])       : null;
        $url_frontal  = !empty($archivos['ine_frontal'])  ? $s3->getS3Url($archivos['ine_frontal'])  : null;
        $url_reverso  = !empty($archivos['ine_reverso'])  ? $s3->getS3Url($archivos['ine_reverso'])  : null;
        // Opcionales si existen en tu flujo actual:
        $url_pasaporte        = !empty($archivos['pasaporte'])        ? $s3->getS3Url($archivos['pasaporte'])        : null;
        $url_forma_migratoria = !empty($archivos['forma_migratoria']) ? $s3->getS3Url($archivos['forma_migratoria']) : null;

        // 4) Variables de layout / vista
        $nombreCompleto = trim(($inquilino['nombre_inquilino'] ?? '') . ' ' .
            ($inquilino['apellidop_inquilino'] ?? '') . ' ' .
            ($inquilino['apellidom_inquilino'] ?? ''));
        $title       = 'Validar Identidad - ' . trim($nombreCompleto);
        $headerTitle = 'Validación de Identidad';

        // Exponer variables que la vista pudiera necesitar
        $slug  = $slug;
        // Imagen principal y variantes según tipo de ID disponible
        $url_selfie   = $url_selfie;
        $url_frontal  = $url_frontal;
        $url_reverso  = $url_reverso;
        $url_pasaporte = $url_pasaporte;
        $url_forma_migratoria = $url_forma_migratoria;
        $inquilino    = $inquilino;

        // 5) Render
        $contentView = __DIR__ . '/../Views/inquilino/validacion_identidad.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Procesa la validación de identidad (stub).
     *
     * Ruta esperada (ejemplo):
     *  POST /admin/validacion-identidad/procesar
     *
     * En producción debería:
     *  - Recibir archivos/campos del formulario.
     *  - Enviar a un servicio externo (OCR/Liveness/Face Match).
     *  - Persistir resultado en BD (e.g., actualizar `inquilinos_validaciones`).
     *  - Responder con payload útil al frontend.
     *
     * Respuesta: JSON { success: bool, msg: string, ...extra }
     */
    public function procesar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'msg' => 'Método no permitido']);
            exit;
        }

        // TODO: integrar lógica real de validación (servicio externo + persistencia)
        echo json_encode([
            'success' => true,
            'msg'     => 'Validación exitosa',
        ]);
        exit;
    }

    /**
     * Muestra la pantalla de resultados de la validación de identidad.
     *
     * Ruta esperada (ejemplo):
     *  GET /admin/validacion-identidad/{slug}/resultado
     *
     * @param string $slug Slug amigable del inquilino.
     */
    public function resultado(string $slug): void
    {
        $s3    = new S3Helper('inquilinos');
        if ($slug === '') {
            http_response_code(404);
            echo 'Inquilino no encontrado';
            exit;
        }

        $inquilinoModel = new InquilinoModel();
        $inquilino      = $inquilinoModel->obtenerPorSlug($slug);

        if (!$inquilino) {
            http_response_code(404);
            echo 'Inquilino no encontrado';
            exit;
        }

        $idInquilino = (int)($inquilino['id'] ?? 0);

        $nombreCompleto = trim(
            ($inquilino['nombre_inquilino'] ?? '') . ' ' .
                ($inquilino['apellidop_inquilino'] ?? '') . ' ' .
                ($inquilino['apellidom_inquilino'] ?? '')
        );

        $title       = 'Resultado Validación - ' . $nombreCompleto;
        $headerTitle = 'Resultado de Validación';

        // ✅ Generar presigned URLs para los archivos
        $archivos = $inquilino['archivos'] ?? [];
        if ($archivos === [] && $idInquilino > 0) {
            $archivos = $inquilinoModel->obtenerArchivos($idInquilino);
        }

        foreach ($archivos as &$archivo) {
            if (!empty($archivo['s3_key'])) {
                $archivo['url'] = $s3->getPresignedUrl($archivo['s3_key']);
            }
        }
        unset($archivo);
        $inquilino['archivos'] = $archivos;

        // ✅ Normalizar validaciones desde MySQL
        $validaciones = $inquilino['validaciones'] ?? [];
        $validacionesData = [];
        foreach ($validaciones as $tipo => $info) {
            if (!is_array($info)) {
                continue;
            }

            $payload = $info['json'] ?? [];
            if (!is_array($payload)) {
                $payload = [];
            }

            $validacionesData[$tipo] = [
                'resumen'    => $info['resumen'] ?? null,
                'payload'    => $payload,
                'proceso'    => $info['proceso'] ?? null,
                'updated_at' => $payload['updated_at']
                    ?? $payload['fecha']
                    ?? $payload['timestamp']
                    ?? null,
            ];
        }
        $inquilino['validaciones_data'] = $validacionesData;

        // Render directo (sin layout main)
        $contentView = __DIR__ . '/../Views/inquilino/validacion_identidad_resultado.php';
        include $contentView;
    }
}
