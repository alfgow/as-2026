<?php

namespace App\Controllers;

require_once __DIR__ . '/../Middleware/AuthMiddleware.php';

use App\Middleware\AuthMiddleware;

if (!defined('REQUEST_IS_API') || REQUEST_IS_API === false) {
    AuthMiddleware::verificarSesion();
}

require_once __DIR__ . '/../Models/ArrendadorModel.php';
require_once __DIR__ . '/../Models/AsesorModel.php';
require_once __DIR__ . '/../Helpers/S3Helper.php';
require_once __DIR__ . '/../Helpers/NormalizadoHelper.php';
require_once __DIR__ . '/../Helpers/SlugHelper.php';

use App\Helpers\S3Helper;
use App\Helpers\NormalizadoHelper;
use App\Helpers\SlugHelper;
use App\Models\ArrendadorModel;
use App\Models\AsesorModel;

/**
 * Controlador de gestión de arrendadores
 * Incluye listado, detalle, edición, actualización y endpoints AJAX.
 */
class ArrendadorController
{
    protected $model;
    private AsesorModel $asesorModel;
    private bool $requestIsApi;

    public function __construct(bool $requestIsApi = false)
    {
        $this->requestIsApi = $requestIsApi;
        $this->model        = new ArrendadorModel();
        $this->asesorModel  = new AsesorModel();
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

            $slugActual = trim((string) ($a['profile']['slug'] ?? ''));

            if ($slugActual === '' && $id !== '') {
                $nombreBase = trim((string) $nombre);
                $slugBase   = $nombreBase !== '' ? SlugHelper::fromName($nombreBase) : 'arrendador';
                $a['profile']['slug'] = $id . '-' . $slugBase;
            }
        }
        unset($a); // buena práctica para evitar referencias colgantes

        if ($this->requestIsApi) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok'            => true,
                'query'         => $query,
                'total'         => count($arrendadores),
                'arrendadores'  => $arrendadores,
            ]);
            return;
        }

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
        unset($f);

        $asesorActual = null;
        $profile      = $arrendador['profile'] ?? [];
        if (!empty($profile['id_asesor'])) {
            $asesorActual = $this->asesorModel->find((int) $profile['id_asesor']);
        }

        $asesores = $this->asesorModel->all();

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

        $slugActualizado = $this->model->actualizarDatosPersonales($pk, $data);

        if ($slugActualizado === null) {
            echo json_encode([
                'ok'    => false,
                'error' => 'No se pudo actualizar'
            ]);
            return;
        }

        echo json_encode([
            'ok'   => true,
            'slug' => $slugActualizado,
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

    public function actualizarAsesor(): void
    {
        if (!$this->validarMetodoPost()) {
            return;
        }

        header('Content-Type: application/json');

        $idArrendador = isset($_POST['id_arrendador']) ? (int) $_POST['id_arrendador'] : 0;
        $idAsesor     = isset($_POST['id_asesor']) ? (int) $_POST['id_asesor'] : 0;

        if ($idArrendador <= 0) {
            echo json_encode(['ok' => false, 'error' => 'Arrendador inválido']);
            return;
        }

        if ($idAsesor <= 0) {
            echo json_encode(['ok' => false, 'error' => 'Selecciona un asesor válido']);
            return;
        }

        try {
            $arrendador = $this->model->obtenerPorId($idArrendador);
            if (!$arrendador) {
                echo json_encode(['ok' => false, 'error' => 'Arrendador no encontrado']);
                return;
            }

            $asesor = $this->asesorModel->find($idAsesor);
            if (!$asesor) {
                echo json_encode(['ok' => false, 'error' => 'Asesor no encontrado']);
                return;
            }

            $payload = $this->model->cambiarAsesor($idArrendador, $asesor);

            echo json_encode([
                'ok'     => true,
                'asesor' => $payload,
            ]);
        } catch (\Throwable $e) {
            echo json_encode([
                'ok'    => false,
                'error' => $e->getMessage(),
            ]);
        }
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
