<?php

namespace App\Controllers;

require_once __DIR__ . '/../Models/InquilinoModel.php';
require_once __DIR__ . '/../Models/ValidacionLegalModel.php';
require_once __DIR__ . '/../Helpers/S3Helper.php';

use App\Models\InquilinoModel;
use App\Models\ValidacionLegalModel;
use App\Helpers\S3Helper;

class ValidacionLegalController
{
    protected ValidacionLegalModel $model;

    public function __construct()
    {
        $this->model = new ValidacionLegalModel();
    }

    public function status(int $idInquilino): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if ($idInquilino <= 0) {
                echo json_encode(['ok' => false, 'mensaje' => 'Id inválido']);
                return;
            }

            $data = $this->model->obtenerValidaciones($idInquilino);

            echo json_encode([
                'ok'   => true,
                'data' => $data
            ]);
        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
        }
    }


    /**
     * POST /validaciones/demandas/run/{id}
     */
    public function run($idInquilino)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $inqModel = new InquilinoModel();
            $inq = $inqModel->obtenerPorId((int)$idInquilino);

            if (!$inq) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'mensaje' => 'Inquilino no encontrado']);
                return;
            }

            $nombreSolo  = trim($inq['nombre_inquilino'] ?? '');
            $apellido_p  = trim($inq['apellidop_inquilino'] ?? '');
            $apellido_m  = trim($inq['apellidom_inquilino'] ?? '');
            $curp        = $inq['curp'] ?? null;
            $rfc         = $inq['rfc'] ?? null;

            $nombreCompleto = trim("$nombreSolo $apellido_p $apellido_m");

            if ($nombreSolo === '' || $apellido_p === '') {
                echo json_encode(['ok' => false, 'mensaje' => 'Nombre y apellido paterno son obligatorios']);
                return;
            }

            $res = $this->model->buscarEnGoogle(
                (int)$idInquilino,
                $nombreCompleto,
                $nombreSolo,
                $apellido_p,
                $apellido_m,
                $curp,
                $rfc
            );

            echo json_encode($res);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'mensaje' => 'Error interno', 'error' => $e->getMessage()]);
        }
    }

    /**
     * GET /validaciones/demandas/ultimo/{id}
     */
    public function ultimo($idInquilino)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $reporte = $this->model->obtenerUltimoReportePorInquilino((int)$idInquilino);

            if ($reporte) {
                // 🔧 Normalizamos el campo resultado para que siempre sea un array
                $decoded = [];
                if (!empty($reporte['resultado'])) {
                    $tmp = json_decode($reporte['resultado'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $decoded = $tmp;
                    }
                }
                $reporte['resultado'] = $decoded;
            }

            echo json_encode([
                'ok'      => true,
                'reporte' => $reporte
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'ok'      => false,
                'mensaje' => 'Error interno',
                'error'   => $e->getMessage()
            ]);
        }
    }

    /**
     * GET /validaciones/demandas/historial/{id}
     * Renderiza vista HTML con historial
     */
    public function historial(int $idInquilino): void
    {
        try {
            $inqModel  = new InquilinoModel();
            $inquilino = $inqModel->obtenerPorId($idInquilino);

            if (!$inquilino) {
                http_response_code(404);
                include __DIR__ . '/../Views/404.php';
                return;
            }

            $validaciones = $inqModel->obtenerValidaciones($idInquilino);
            $archivos     = $inqModel->obtenerArchivos($idInquilino);

            $s3 = new S3Helper('inquilinos');
            if (!empty($archivos)) {
                foreach ($archivos as &$archivo) {
                    if (!empty($archivo['s3_key'])) {
                        $archivo['url'] = $s3->getPresignedUrl($archivo['s3_key']);
                    }
                }
                unset($archivo);
            }

            $categorias = [];
            if (!empty($archivos)) {
                foreach ($archivos as $archivo) {
                    $tipo = strtolower((string)($archivo['tipo'] ?? ''));
                    if ($tipo === '') {
                        $tipo = 'otros';
                    }
                    $categorias[$tipo][] = $archivo;
                }
            }
            $historial    = $this->model->obtenerHistorialPorInquilino($idInquilino);

            $admin_base_url = '';

            if (function_exists('admin_base_url')) {
                $admin_base_url = admin_base_url();
            }

            if ($admin_base_url === '' || $admin_base_url === null) {
                $envAdminBase = $_ENV['ADMIN_BASE_URL'] ?? getenv('ADMIN_BASE_URL') ?? '';

                if (!is_string($envAdminBase)) {
                    $envAdminBase = (string) $envAdminBase;
                }

                $envAdminBase = trim($envAdminBase);

                if ($envAdminBase !== '') {
                    $admin_base_url = $envAdminBase;
                } else {
                    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                        || (($_SERVER['SERVER_PORT'] ?? '') == 443);
                    $scheme = $isHttps ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

                    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
                    $scriptDir = dirname($scriptName);
                    $scriptDir = str_replace('\\', '/', $scriptDir);
                    if ($scriptDir === '\\' || $scriptDir === '/' || $scriptDir === '.') {
                        $scriptDir = '';
                    } else {
                        $scriptDir = trim($scriptDir, '/');
                    }

                    $admin_base_url = $scheme . '://' . $host;
                    if ($scriptDir !== '') {
                        $admin_base_url .= '/' . $scriptDir;
                    }
                }
            }

            $admin_base_url = is_string($admin_base_url) ? trim($admin_base_url) : '';
            if ($admin_base_url === '') {
                $admin_base_url = '/';
            } else {
                $admin_base_url = rtrim($admin_base_url, '/');
                if ($admin_base_url === '') {
                    $admin_base_url = '/';
                }
            }

            $title = "Validaciones del inquilino";
            $headerTitle = "Validaciones del inquilino #{$idInquilino}";
            $contentView = __DIR__ . '/../Views/inquilino/validaciones.php';

            include __DIR__ . '/../Views/layouts/main.php';
        } catch (\Throwable $e) {
            http_response_code(500);
            echo "Error interno: " . $e->getMessage();
        }
    }

    public function historialJson(int $idInquilino)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $historial = $this->model->obtenerHistorialPorInquilino($idInquilino);
            echo json_encode(['ok' => true, 'historial' => $historial]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    public function toggleDemandas(int $idInquilino): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            $estado = (int)($input['proceso_inv_demandas'] ?? 2);

            $ok = $this->model->actualizarProcesoDemandas($idInquilino, $estado);

            echo json_encode([
                'ok' => $ok,
                'nuevo_estado' => $estado
            ]);
        } catch (\Throwable $e) {
            echo json_encode([
                'ok' => false,
                'mensaje' => $e->getMessage()
            ]);
        }
    }



    /**
     * GET /inquilino/{slug}/validaciones/demandas
     */
    public function historialPorSlug(string $slug): void
    {
        try {
            $inquilinoModel = new InquilinoModel();
            $inquilino = $inquilinoModel->obtenerPorSlug($slug);
            if (!$inquilino || empty($inquilino['id'])) {
                http_response_code(404);
                echo "Inquilino no encontrado";
                return;
            }
            $this->historial((int)$inquilino['id']);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo "Error interno: " . $e->getMessage();
        }
    }
}
