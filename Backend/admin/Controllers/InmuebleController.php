<?php
declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . '/../Models/InmueblesModel.php';
require_once __DIR__ . '/../Models/ArrendadorModel.php';
require_once __DIR__ . '/../Models/AsesorModel.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';

use App\Models\InmuebleModel;
use App\Models\ArrendadorModel;
use App\Models\AsesorModel;
use App\Middleware\AuthMiddleware;

/**
 * Controlador de Inmuebles
 *
 * Funcionalidades:
 * - Listado con búsqueda y paginación
 * - Ver detalle
 * - Crear / Editar / Eliminar (JSON)
 * - Endpoints auxiliares: inmueblesPorArrendador, info
 *
 * Notas:
 * - Normaliza montos (renta, mantenimiento, depósito) a formato decimal "####.##"
 * - Maneja correctamente checkbox de estacionamiento (1/0) y mascotas (SI/NO)
 * - Respuestas JSON coherentes con Content-Type y mensajes
 */
AuthMiddleware::verificarSesion();

class InmuebleController
{
    private InmuebleModel $model;
    private ArrendadorModel $arrendadorModel;
    private AsesorModel $asesorModel;

    public function __construct()
    {
        $this->model = new InmuebleModel();
        $this->arrendadorModel = new ArrendadorModel();
        $this->asesorModel = new AsesorModel();
    }

    /**
     * Listado con búsqueda y paginación básica
     */
    public function index(): void
    {
        $porPagina = 10;
        $pagina = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $offset = ($pagina - 1) * $porPagina;

        $query = trim((string)($_GET['q'] ?? ''));

        if ($query !== '') {
            $inmuebles = $this->model->buscarPaginados($query, $porPagina, $offset);
            $totalInmuebles = (int)$this->model->contarBusqueda($query);
        } else {
            $inmuebles = $this->model->obtenerPaginados($porPagina, $offset);
            $totalInmuebles = (int)$this->model->contarTodos();
        }

        $totalPaginas = (int) ceil($totalInmuebles / $porPagina);

        $title = 'Inmuebles - AS';
        $headerTitle = 'Listado de inmuebles';
        $contentView = __DIR__ . '/../Views/inmuebles/index.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Ver detalle de un inmueble
     */
    public function ver(int $id): void
    {
        $inmueble = $this->model->obtenerPorId($id);
        if (!$inmueble) {
            http_response_code(404);
            $title = 'No encontrado';
            $headerTitle = 'Recurso no encontrado';
            $contentView = __DIR__ . '/../Views/404.php';
            include __DIR__ . '/../Views/layouts/main.php';
            return;
        }

        $title = 'Detalle de inmueble';
        $headerTitle = 'Detalle de inmueble';
        $contentView = __DIR__ . '/../Views/inmuebles/detalle.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Formulario de creación
     */
    public function crear(): void
    {
        $arrendadores = $this->arrendadorModel->obtenerTodos();
        $asesores = $this->asesorModel->all();

        $title = 'Nuevo inmueble';
        $headerTitle = 'Nuevo inmueble';
        $contentView = __DIR__ . '/../Views/inmuebles/form.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Formulario de edición
     */
    public function editar(int $id): void
    {
        $inmueble = $this->model->obtenerPorId($id);
        if (!$inmueble) {
            header('Location: ' . getBaseUrl() . '/inmuebles');
            exit;
        }

        $arrendadores = $this->arrendadorModel->obtenerTodos();
        $asesores = $this->asesorModel->all();
        $editMode = true;

        $title = 'Editar inmueble';
        $headerTitle = 'Editar inmueble';
        $contentView = __DIR__ . '/../Views/inmuebles/form.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Crear (JSON)
     */
    public function store(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
            return;
        }

        try {
            $data = $this->buildInmuebleDataFromPost();

            $ok = $this->model->crear($data);
            echo json_encode(['ok' => (bool)$ok]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'mensaje' => 'Error al crear inmueble', 'error' => $e->getMessage()]);
        }
    }

    /**
     * Actualizar (JSON)
     */
    public function update(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
            return;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'mensaje' => 'ID inválido']);
            return;
        }

        try {
            $data = $this->buildInmuebleDataFromPost(/* isUpdate */ true);

            $ok = $this->model->actualizar($id, $data);
            echo json_encode(['ok' => (bool)$ok]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'mensaje' => 'Error al actualizar inmueble', 'error' => $e->getMessage()]);
        }
    }

    /**
     * Eliminar (JSON)
     */
    public function delete(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
            return;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'mensaje' => 'ID inválido']);
            return;
        }

        try {
            $ok = $this->model->eliminar($id);
            echo json_encode(['ok' => (bool)$ok]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'mensaje' => 'Error al eliminar inmueble', 'error' => $e->getMessage()]);
        }
    }

    /**
     * Devuelve inmuebles por arrendador (JSON)
     */
    public function inmueblesPorArrendador(int $id): void
    {
        header('Content-Type: application/json');
        $id = (int)$id;

        try {
            $inmuebles = $this->model->obtenerPorArrendador($id);
            echo json_encode($inmuebles ?? []);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'mensaje' => 'Error al consultar inmuebles', 'error' => $e->getMessage()]);
        }
    }

    /**
     * Devuelve la información de un inmueble específico en formato JSON
     */
    public function info(int $id): void
    {
        header('Content-Type: application/json');
        $id = (int)$id;

        try {
            $inmueble = $this->model->obtenerPorId($id);
            echo json_encode($inmueble ?: []);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'mensaje' => 'Error al consultar inmueble', 'error' => $e->getMessage()]);
        }
    }

    // =========================
    // Métodos auxiliares
    // =========================

    /**
     * Construye el arreglo $data para crear/actualizar inmuebles, saneando y normalizando.
     *
     * @param bool $isUpdate Si es true, procesa 'estacionamiento' aceptando 0/1 directos además de checkbox
     * @return array<string, string|int>
     */
    private function buildInmuebleDataFromPost(bool $isUpdate = false): array
    {
        // IDs seguros
        $idArrendador = isset($_POST['id_arrendador']) ? (int)$_POST['id_arrendador'] : 0;
        $idAsesor     = isset($_POST['id_asesor']) ? (int)$_POST['id_asesor'] : 0;

        // Estacionamiento:
        // - En crear (checkbox): isset → 1/0
        // - En update aceptamos '0'/'1' o checkbox
        $estacionamiento = 0;
        if ($isUpdate) {
            if (isset($_POST['estacionamiento'])) {
                // Si viene de input hidden/select
                $val = trim((string)$_POST['estacionamiento']);
                $estacionamiento = (int)($val === '1' || $val === 'SI' || $val === 'true' || $val === 'on' || $val === 'yes');
            } else {
                $estacionamiento = 0;
            }
        } else {
            $estacionamiento = isset($_POST['estacionamiento']) ? 1 : 0;
        }

        // Mascotas: solo SI/NO
        $mascotasRaw = strtoupper(trim((string)($_POST['mascotas'] ?? 'NO')));
        $mascotas = ($mascotasRaw === 'SI') ? 'SI' : 'NO';

        return [
            'id_arrendador'       => $idArrendador,
            'id_asesor'           => $idAsesor,
            'direccion_inmueble'  => trim((string)($_POST['direccion_inmueble'] ?? '')),
            'tipo'                => trim((string)($_POST['tipo'] ?? '')),
            'renta'               => $this->normalizarMonto((string)($_POST['renta'] ?? '0')),
            'mantenimiento'       => trim((string)($_POST['mantenimiento'] ?? '')),
            'monto_mantenimiento' => $this->normalizarMonto((string)($_POST['monto_mantenimiento'] ?? '0')),
            'deposito'            => $this->normalizarMonto((string)($_POST['deposito'] ?? '0')),
            'estacionamiento'     => $estacionamiento, // 1 | 0
            'mascotas'            => $mascotas,        // 'SI' | 'NO'
            'comentarios'         => trim((string)($_POST['comentarios'] ?? '')),
        ];
    }

    /**
     * Convierte montos de "$3,800.00" | "3,800" | "3800,00" → "3800.00"
     */
    private function normalizarMonto(string $valor): string
    {
        $v = trim($valor);
        if ($v === '') return '0.00';

        // Quitar símbolo de moneda y espacios
        $v = str_replace(['$', ' '], '', $v);

        // Si trae separadores miles (,) y punto decimal, limpiamos miles y dejamos punto
        // También soporta formatos tipo "3.800,50" → "3800.50"
        if (preg_match('/^\d{1,3}(\.\d{3})+,\d{2}$/', $v)) {
            // Formato europeo: miles con punto, decimales con coma
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
        } else {
            // Quitar comas de miles
            $v = str_replace(',', '', $v);
        }

        // Si termina sin decimales, agregamos .00
        if (!str_contains($v, '.')) {
            $v .= '.00';
        } else {
            // Normalizar a 2 decimales
            $parts = explode('.', $v, 2);
            $dec = substr($parts[1] . '00', 0, 2);
            $v = $parts[0] . '.' . $dec;
        }

        // Asegurar que sólo queden dígitos y un punto
        if (!preg_match('/^\d+(\.\d{2})$/', $v)) {
            // Fallback seguro
            return '0.00';
        }

        return $v;
    }
}