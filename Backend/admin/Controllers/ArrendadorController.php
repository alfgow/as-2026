<?php

namespace App\Controllers;

require_once __DIR__ . '/../Middleware/AuthMiddleware.php';

use App\Middleware\AuthMiddleware;

AuthMiddleware::verificarSesion();

require_once __DIR__ . '/../Models/ArrendadorModel.php';
require_once __DIR__ . '/../Helpers/S3Helper.php';
require_once __DIR__ . '/../Helpers/NormalizadoHelper.php';

use App\Helpers\S3Helper;
use App\Helpers\NormalizadoHelper;
use App\Models\ArrendadorModel;

/**
 * Controlador de gestión de arrendadores
 * Incluye listado, detalle, edición, actualización y endpoints AJAX.
 */
class ArrendadorController
{
    protected $model;

    public function __construct()
    {
        $this->model = new ArrendadorModel();
    }

    /**
     * Index con busqueda
     */
    public function index()
    {
        $s3    = new S3Helper('arrendadores');
        $query = NormalizadoHelper::lower(trim($_GET['q'] ?? ''));

        // 1) Buscar arrendadores (el modelo devuelve archivos y profile)
        $arrendadores = $query !== '' ? $this->model->buscar($query) : [];

        foreach ($arrendadores as &$a) {
            $selfieUrl = null;

            if (!empty($a['archivos'])) {
                foreach ($a['archivos'] as $archivo) {
                    if (NormalizadoHelper::lower($archivo['tipo'] ?? '') === 'selfie' && !empty($archivo['s3_key'])) {
                        $selfieUrl = $s3->getPresignedUrl($archivo['s3_key']);
                        break;
                    }
                }
            }

            $a['selfie_url'] = $selfieUrl;

            $nombre = $a['profile']['nombre_arrendador'] ?? '';
            $pk     = $a['profile']['pk'] ?? '';   // ej. arr#557
            $id     = str_replace('arr#', '', $pk);

            $a['profile']['slug'] = NormalizadoHelper::slug($nombre) . '-' . $id;
        }
        unset($a); // buena práctica para evitar referencias colgantes

        // 3) Preparar datos para la vista
        $title       = 'Arrendadores - AS';
        $headerTitle = 'Arrendadores';
        $contentView = __DIR__ . '/../Views/arrendadores/index.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }


    /**
     * Vista de detalle
     */
    public function detalle(string $slug)
    {
        $slug = trim($slug);
        $arrendador = $slug !== '' ? $this->model->obtenerPorSlug($slug) : null;

        if (!$arrendador && preg_match('/-(\d+)$/', $slug, $matches)) {
            $arrendador = $this->model->obtenerPorId((int) $matches[1]);
        }

        if (!$arrendador && ctype_digit($slug)) {
            $arrendador = $this->model->obtenerPorId((int) $slug);
        }

        if (!$arrendador) {
            http_response_code(404);
            include __DIR__ . '/../Views/404.php';
            return;
        }

        // Presignar archivos
        $s3 = new S3Helper('arrendadores');
        foreach ($arrendador['archivos'] as &$f) {
            if (!empty($f['s3_key'])) {
                $f['url'] = $s3->getPresignedUrl($f['s3_key']);
            }
        }

        $title       = 'Detalle Arrendador';
        $headerTitle = $arrendador['profile']['nombre_arrendador'];
        $contentView = __DIR__ . '/../Views/arrendadores/detalle.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Cambiar Archivo
     */
    public function cambiarArchivo(): void
    {
        header('Content-Type: application/json');

        try {
            $idArrendador = isset($_POST['id_arrendador']) ? (int)$_POST['id_arrendador'] : 0;
            $tipo         = trim((string)($_POST['tipo'] ?? ''));

            if ($idArrendador <= 0 || $tipo === '' || empty($_FILES['archivo'])) {
                echo json_encode(['ok' => false, 'error' => 'Datos incompletos']);
                return;
            }

            // Traer perfil completo del arrendador
            $arr = $this->model->obtenerPorId($idArrendador);

            if (!$arr) {
                echo json_encode(['ok' => false, 'error' => 'Arrendador no encontrado']);
                return;
            }

            // Normalizar nombre para carpeta en S3
            $nombreNorm = S3Helper::buildPersonKeyFromParts(
                $arr['profile']['nombre_arrendador'] ?? '',
                '',
                ''
            );

            // Extensión archivo nuevo
            $ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
            $ext = preg_replace('/[^a-z0-9]/', '', $ext) ?: 'dat';
            $folder  = $idArrendador . '_' . ($nombreNorm ?: 'arrendador');
            $nuevoKey = sprintf('%s/%s_%s.%s', $folder, strtolower($tipo), $nombreNorm ?: 'arrendador', $ext);

            $s3 = new S3Helper('arrendadores');

            $previo = $this->model->obtenerArchivoPorTipo($idArrendador, $tipo);
            if ($previo && !empty($previo['s3_key'])) {
                try {
                    $s3->deleteFile($previo['s3_key']);
                } catch (\Throwable $e) {
                    error_log('⚠️ No se pudo borrar archivo previo de S3: ' . $e->getMessage());
                }
            }

            $okUpload = $s3->uploadFileWithKey($_FILES['archivo'], $nuevoKey);
            if (!$okUpload) {
                echo json_encode(['ok' => false, 'error' => 'No se pudo subir a S3']);
                return;
            }

            $this->model->guardarArchivo($idArrendador, $tipo, $nuevoKey);

            echo json_encode(['ok' => true, 's3_key' => $nuevoKey]);
        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    public function eliminarArchivo(): void
    {
        header('Content-Type: application/json');

        try {
            $idArrendador = isset($_POST['id_arrendador']) ? (int)$_POST['id_arrendador'] : 0;
            $tipo         = trim((string)($_POST['tipo'] ?? ''));

            if ($idArrendador <= 0 || $tipo === '') {
                echo json_encode(['ok' => false, 'error' => 'Datos incompletos']);
                return;
            }

            $archivo = $this->model->obtenerArchivoPorTipo($idArrendador, $tipo);
            if ($archivo) {
                // 1. Borrar en S3
                $s3 = new S3Helper('arrendadores');
                $s3->deleteFile($archivo['s3_key']);

                // 2. Borrar en base
                $this->model->eliminarArchivo($idArrendador, $tipo);
            }

            echo json_encode(['ok' => true]);
        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Actualiza datos personales vía AJAX
     */
    public function actualizarDatosPersonales()
    {
        $pk = $_POST['id'] ?? ''; // ahora viene algo como "arr#557"

        if (!$pk || !str_starts_with($pk, 'arr#')) {
            echo json_encode(['ok' => false, 'error' => 'ID inválido']);
            return;
        }

        $data = [
            'nombre_arrendador'    => NormalizadoHelper::lower($_POST['nombre_arrendador'] ?? ''),
            'email'                => NormalizadoHelper::lower($_POST['email'] ?? ''),
            'celular'              => NormalizadoHelper::lower($_POST['celular'] ?? ''),
            'direccion_arrendador' => NormalizadoHelper::lower($_POST['direccion_arrendador'] ?? ''),
            'estadocivil'          => NormalizadoHelper::lower($_POST['estadocivil'] ?? ''),
            'nacionalidad'         => NormalizadoHelper::lower($_POST['nacionalidad'] ?? ''),
            'rfc'                  => NormalizadoHelper::lower($_POST['rfc'] ?? ''),
            'tipo_id'              => NormalizadoHelper::lower($_POST['tipo_id'] ?? ''),
            'num_id'               => NormalizadoHelper::lower($_POST['num_id'] ?? ''),
        ];

        $ok = $this->model->actualizarDatosPersonales($pk, $data);

        echo json_encode([
            'ok'    => $ok,
            'error' => $ok ? null : 'No se pudo actualizar'
        ]);
    }

    /**
     * Actualiza información bancaria vía AJAX
     */
    public function actualizarInfoBancaria()
    {
        if (!$this->validarMetodoPost()) return;

        // pk = "arr#557"
        $pk = $_POST['pk'] ?? null;
        $id = null;

        if ($pk && preg_match('/^arr#(\d+)$/', $pk, $matches)) {
            $id = (int)$matches[1];
        }

        if (!$id) {
            echo json_encode(['ok' => false, 'error' => 'ID inválido']);
            return;
        }

        $data = [
            'banco'  => NormalizadoHelper::lower(trim($_POST['banco'] ?? '')),
            'cuenta' => NormalizadoHelper::lower(trim($_POST['cuenta'] ?? '')),
            'clabe'  => NormalizadoHelper::lower(trim($_POST['clabe'] ?? '')),
        ];

        $ok = $this->model->actualizarInfoBancaria($id, $data);
        echo json_encode(['ok' => $ok]);
    }

    /**
     * Actualiza comentarios (MySQL)
     */
    public function actualizarComentarios(): void
    {
        if (!$this->validarMetodoPost()) return;

        $pk = $_POST['pk'] ?? null;
        $comentarios = NormalizadoHelper::lower(trim($_POST['comentarios'] ?? ''));

        if (!$pk) {
            echo json_encode(['ok' => false, 'error' => 'PK inválido']);
            return;
        }

        $ok = $this->model->actualizarComentarios($pk, $comentarios);
        echo json_encode(['ok' => $ok]);
    }

    /**
     * Lista arrendadores de un asesor (JSON)
     */
    public function arrendadoresPorAsesor(int $id)
    {
        header('Content-Type: application/json');
        $arrendadores = $this->model->obtenerPorAsesor($id);
        echo json_encode($arrendadores);
    }

    /* ================= Métodos auxiliares internos ================= */

    private function validarMetodoPost(): bool
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
            return false;
        }
        return true;
    }
}
