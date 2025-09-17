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
    public function detalle(int $id)
    {
        $arrendador = $this->model->obtenerPorId($id);

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
            $idArrendador = $_POST['id_arrendador'] ?? null;
            $tipo         = $_POST['tipo'] ?? null;

            if (!$idArrendador || !$tipo || empty($_FILES['archivo'])) {
                echo json_encode(['ok' => false, 'error' => 'Datos incompletos']);
                return;
            }

            // Traer perfil completo del arrendador
            $arr = $this->model->obtenerPorId($idArrendador);

            if (!$arr) {
                echo json_encode(['ok' => false, 'error' => 'Arrendador no encontrado']);
                return;
            }

            // 🔧 Normalizar arrays
            if (!isset($arr['archivos']) || !is_array($arr['archivos'])) {
                $arr['archivos'] = [];
            }
            if (!isset($arr['archivos_ids']) || !is_array($arr['archivos_ids'])) {
                $arr['archivos_ids'] = [];
            }

            // Normalizar nombre para carpeta en S3
            $nombreNorm = \App\Helpers\S3Helper::buildPersonKeyFromParts(
                $arr['profile']['nombre_arrendador'] ?? '',
                $arr['profile']['apellidop_arrendador'] ?? '',
                $arr['profile']['apellidom_arrendador'] ?? ''
            );

            // Extensión archivo nuevo
            $ext      = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
            $nuevoKey = "{$idArrendador}_{$nombreNorm}/" . strtolower($tipo) . "_{$nombreNorm}.{$ext}";

            $s3     = new \App\Helpers\S3Helper('arrendadores');
            $client = \App\Core\Dynamo::client();
            $table  = \App\Core\Dynamo::table();

            // Buscar archivo previo de este tipo
            $previo = null;
            foreach ($arr['archivos'] as $a) {
                if ($a['tipo'] === $tipo) {
                    $previo = $a;
                    break;
                }
            }

            // Si existe previo → borrar en Dynamo + S3
            $replaceMode = false;
            if ($previo) {
                $replaceMode = true;
                $oldSk  = $previo['sk'];
                $oldKey = $previo['s3_key'];

                // Borrar de Dynamo (item independiente)
                try {
                    $client->deleteItem([
                        'TableName' => $table,
                        'Key' => [
                            'pk' => ['S' => "arr#{$idArrendador}"],
                            'sk' => ['S' => $oldSk]
                        ]
                    ]);
                } catch (\Exception $e) {
                    error_log("⚠️ No se pudo borrar de Dynamo: " . $e->getMessage());
                }

                // Borrar de S3
                try {
                    $s3->deleteFile($oldKey);
                } catch (\Exception $e) {
                    error_log("⚠️ No se pudo borrar de S3: " . $e->getMessage());
                }
            }

            // Subir nuevo archivo a S3
            $okUpload = $s3->uploadFileWithKey($_FILES['archivo'], $nuevoKey);
            if (!$okUpload) {
                echo json_encode(['ok' => false, 'error' => 'No se pudo subir a S3']);
                return;
            }

            // Guardar nuevo archivo como item independiente
            $newFileId = "arrfile#" . uniqid();
            $client->putItem([
                'TableName' => $table,
                'Item' => [
                    'pk'           => ['S' => "arr#{$idArrendador}"],
                    'sk'           => ['S' => $newFileId],
                    'tipo'         => ['S' => $tipo],
                    's3_key'       => ['S' => $nuevoKey],
                    'fecha_subida' => ['S' => date('Y-m-d H:i:s')],
                ]
            ]);

            // 🔹 Actualizar profile.archivos_ids
            if ($replaceMode) {
                // Traer lista actualizada de Dynamo
                $result = $client->getItem([
                    'TableName' => $table,
                    'Key' => [
                        'pk' => ['S' => "arr#{$idArrendador}"],
                        'sk' => ['S' => 'profile']
                    ]
                ]);

                $currentIds = [];
                if (!empty($result['Item']['archivos_ids']['L'])) {
                    foreach ($result['Item']['archivos_ids']['L'] as $id) {
                        $currentIds[] = $id['S'];
                    }
                }

                // Quitar el viejo SK y agregar el nuevo
                $currentIds = array_values(
                    array_filter($currentIds, fn($id) => $id !== $previo['sk'])
                );
                $currentIds[] = $newFileId;

                // SET con lista completa corregida
                $client->updateItem([
                    'TableName' => $table,
                    'Key' => [
                        'pk' => ['S' => "arr#{$idArrendador}"],
                        'sk' => ['S' => 'profile']
                    ],
                    'UpdateExpression' => 'SET archivos_ids = :ids',
                    'ExpressionAttributeValues' => [
                        ':ids' => ['L' => array_map(fn($id) => ['S' => $id], $currentIds)]
                    ]
                ]);
            } else {
                // Nuevo archivo → usar append
                $client->updateItem([
                    'TableName' => $table,
                    'Key' => [
                        'pk' => ['S' => "arr#{$idArrendador}"],
                        'sk' => ['S' => 'profile']
                    ],
                    'UpdateExpression' => 'SET archivos_ids = list_append(if_not_exists(archivos_ids, :empty), :new)',
                    'ExpressionAttributeValues' => [
                        ':new'   => ['L' => [['S' => $newFileId]]],
                        ':empty' => ['L' => []]
                    ]
                ]);
            }

            echo json_encode(['ok' => true, 's3_key' => $nuevoKey]);
        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    public function eliminarArchivo(): void
    {
        header('Content-Type: application/json');

        try {
            $idArrendador = $_POST['id_arrendador'] ?? null;
            $tipo         = $_POST['tipo'] ?? null;

            if (!$idArrendador || !$tipo) {
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
     * Actualiza comentarios (Dynamo)
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
