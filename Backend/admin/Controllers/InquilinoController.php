<?php

declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Models/InquilinoModel.php';
require_once __DIR__ . '/../Helpers/NormalizadoHelper.php';
require_once __DIR__ . '/../Helpers/S3Helper.php';
require_once __DIR__ . '/../Helpers/SlugHelper.php';

use App\Helpers\NormalizadoHelper;
use App\Helpers\S3Helper;
use App\Helpers\SlugHelper;
use App\Middleware\AuthMiddleware;
use App\Models\InquilinoModel;

class InquilinoController
{
    /** @var InquilinoModel */
    private $model;

    public function __construct()
    {
        // Verificación de sesión en cada request del controlador
        AuthMiddleware::verificarSesion();
        $this->model = new InquilinoModel();
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
            $id = str_replace(['inq#', 'obl#', 'fia#'], '', $pk);
            $inq['profile']['slug'] = SlugHelper::fromName($nombreCompleto) . '-' . $id;

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
        $slug = SlugHelper::fromName($nombreCompleto !== '' ? $nombreCompleto : $pk) . '-' . $id;

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
                'ok'     => $ok,
                'mensaje'=> $ok ? 'Datos personales actualizados.' : 'No fue posible actualizar los datos.',
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
     * Muestra el detalle de un inquilino a partir del slug amigable.
     */
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
}
