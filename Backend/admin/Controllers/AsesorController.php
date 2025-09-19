<?php
namespace App\Controllers;

require_once __DIR__ . '/../Models/AsesorModel.php';
require_once __DIR__ . '/../Models/InquilinoModel.php';
require_once __DIR__ . '/../Models/ArrendadorModel.php';
use App\Models\AsesorModel;
use App\Models\InquilinoModel;
use App\Models\ArrendadorModel;

require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
use App\Middleware\AuthMiddleware;
AuthMiddleware::verificarSesion();

/**
 * Controlador para la gestión de Asesores Inmobiliarios
 */
class AsesorController
{
    protected $model;

    public function __construct()
    {
        $this->model = new AsesorModel();
    }

    /**
     * Lista todos los asesores
     */
    public function index()
    {
        $asesores    = $this->enrichAsignaciones($this->model->all());
        $title       = 'Asesores - AS';
        $headerTitle = 'Asesores Inmobiliarios';
        $contentView = __DIR__ . '/../Views/asesores/index.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }

    public function store(): void
    {
        $this->ensurePost();

        try {
            $data = $this->sanitizarDatos($_POST);

            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('El correo electrónico no es válido.');
            }

            $id     = $this->model->create($data);
            $asesor = $this->model->find($id);

            if ($asesor === null) {
                throw new \RuntimeException('No se pudo recuperar el asesor recién creado.');
            }

            [$asesor] = $this->enrichAsignaciones([$asesor]);

            $this->jsonResponse([
                'ok'      => true,
                'message' => 'Asesor creado correctamente.',
                'asesor'  => $asesor,
            ]);
        } catch (\RuntimeException $e) {
            $this->jsonResponse([
                'ok'    => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function update(): void
    {
        $this->ensurePost();

        try {
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($id <= 0) {
                throw new \RuntimeException('Identificador de asesor inválido.');
            }

            $data = $this->sanitizarDatos($_POST);

            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('El correo electrónico no es válido.');
            }

            $this->model->update($id, $data);
            $asesor = $this->model->find($id);

            if ($asesor === null) {
                throw new \RuntimeException('No se pudo recuperar la información actualizada del asesor.');
            }

            [$asesor] = $this->enrichAsignaciones([$asesor]);

            $this->jsonResponse([
                'ok'      => true,
                'message' => 'Asesor actualizado correctamente.',
                'asesor'  => $asesor,
            ]);
        } catch (\RuntimeException $e) {
            $this->jsonResponse([
                'ok'    => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function delete(): void
    {
        $this->ensurePost();

        try {
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($id <= 0) {
                throw new \RuntimeException('Identificador de asesor inválido.');
            }

            if (!$this->model->delete($id)) {
                throw new \RuntimeException('El asesor tiene inquilinos asignados, reasigna antes de eliminar.');
            }

            $this->jsonResponse([
                'ok'      => true,
                'message' => 'Asesor eliminado correctamente.',
                'id'      => $id,
            ]);
        } catch (\RuntimeException $e) {
            $this->jsonResponse([
                'ok'    => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Sanitiza los datos recibidos de un formulario
     */
    private function sanitizarDatos(array $input): array
    {
        return [
            'nombre_asesor' => trim($input['nombre_asesor'] ?? ''),
            'email'         => trim($input['email'] ?? ''),
            'celular'       => trim($input['celular'] ?? ''),
        ];
    }

    private function ensurePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->jsonResponse([
                'ok'    => false,
                'error' => 'Método no permitido.',
            ], 405);
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonResponse(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * @param array<int, array<string, mixed>> $asesores
     * @return array<int, array<string, mixed>>
     */
    private function enrichAsignaciones(array $asesores): array
    {
        if (empty($asesores)) {
            return $asesores;
        }

        $normalized = [];
        $pks        = [];

        foreach ($asesores as $index => $asesor) {
            $id = isset($asesor['id']) ? (int) $asesor['id'] : 0;
            $pk = (string) ($asesor['pk'] ?? '');

            if ($pk !== '') {
                $pk = strtolower($pk);
            }

            if ($pk === '' && $id > 0) {
                $pk = sprintf('ase#%d', $id);
            }

            $asesor['pk']                 = $pk;
            $asesor['inquilinos_total']   = (int) ($asesor['inquilinos_total'] ?? 0);
            $asesor['arrendadores_total'] = (int) ($asesor['arrendadores_total'] ?? 0);
            $normalized[$index]           = $asesor;

            if ($pk !== '') {
                $pks[$pk] = $pk;
            }
        }

        if (empty($pks)) {
            return array_values($normalized);
        }

        $inquilinoModel  = new InquilinoModel();
        $arrendadorModel = new ArrendadorModel();
        $pkList          = array_values($pks);

        $inquilinos   = $inquilinoModel->contarPorAsesores($pkList);
        $arrendadores = $arrendadorModel->contarPorAsesores($pkList);

        foreach ($normalized as &$asesor) {
            $pk = (string) ($asesor['pk'] ?? '');
            if ($pk === '') {
                continue;
            }

            $asesor['inquilinos_total']   = (int) ($inquilinos[$pk] ?? 0);
            $asesor['arrendadores_total'] = (int) ($arrendadores[$pk] ?? 0);
        }
        unset($asesor);

        return array_values($normalized);
    }
}
