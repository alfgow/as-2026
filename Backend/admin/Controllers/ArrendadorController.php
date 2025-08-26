<?php
namespace App\Controllers;

require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
use App\Middleware\AuthMiddleware;
AuthMiddleware::verificarSesion();

require_once __DIR__ . '/../Models/ArrendadorModel.php';
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
     * Listado con búsqueda y paginación
     */
    public function index()
    {
        $query     = trim($_GET['q'] ?? '');
        $pagina    = max(1, intval($_GET['pagina'] ?? 1));
        $porPagina = 10;
        $offset    = ($pagina - 1) * $porPagina;

        $arrendadores     = $this->model->buscarConPaginacion($query, $offset, $porPagina);
        $totalResultados  = $this->model->contarTotalResultados($query);
        $totalPaginas     = ceil($totalResultados / $porPagina);
        $indicadores      = $this->model->obtenerIndicadores();

        $title       = 'Arrendadores - AS';
        $headerTitle = 'Arrendadores';
        $contentView = __DIR__ . '/../Views/arrendadores/index.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Vista de detalle
     */
    public function detalle($id)
    {
        $arrendador = $this->model->obtenerPorId((int) $id);
        if (!$arrendador) {
            http_response_code(404);
            $contentView = __DIR__ . '/../Views/404.php';
            include __DIR__ . '/../Views/layouts/main.php';
            return;
        }

        $title       = 'Detalle Arrendador';
        $headerTitle = 'Detalle Arrendador';
        $contentView = __DIR__ . '/../Views/arrendadores/detalle.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Formulario de edición
     */
    public function editar($id)
    {
        $arrendador = $this->model->obtenerPorId((int) $id);
        if (!$arrendador) {
            header('Location: ' . getBaseUrl() . '/arrendadores');
            exit;
        }

        $title       = 'Editar Arrendador';
        $headerTitle = 'Editar Arrendador';
        $contentView = __DIR__ . '/../Views/arrendadores/editar.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Actualiza arrendador desde formulario tradicional
     */
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0) {
                $data = $this->sanitizarDatosBasicos($_POST);
                $this->model->update($id, $data);
            }
        }
        header('Location: ' . getBaseUrl() . '/arrendadores');
        exit;
    }

    /**
     * Carga vista para agregar inmueble
     */
    public function nuevoInmueble($id)
    {
        $this->cargarVistaAsociada($id, 'Agregar Inmueble', 'crear_inmueble.php');
    }

    /**
     * Carga vista para registrar póliza
     */
    public function nuevaPoliza($id)
    {
        $this->cargarVistaAsociada($id, 'Registrar Póliza', 'crear_poliza.php');
    }

    /**
     * Actualización vía AJAX con retorno HTML parcial
     */
    public function updateAjax()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['id'])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'ID requerido']);
            return;
        }

        $data = $this->sanitizarDatosBasicos($input);
        $this->model->update((int) $input['id'], $data);

        $arr = $this->model->obtenerPorId((int) $input['id']);
        if (!$arr || !file_exists(__DIR__ . '/../Views/arrendadores/_fila.php')) {
            echo json_encode(['ok' => false, 'error' => 'No se pudo renderizar']);
            return;
        }

        ob_start();
        include __DIR__ . '/../Views/arrendadores/_fila.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html]);
    }

    /**
     * Actualiza datos personales vía AJAX
     */
    public function actualizarDatosPersonales()
    {
        if (!$this->validarMetodoPost()) return;
        $id = $this->validarId($_POST['id'] ?? null);
        if (!$id) return;

        $data = [
            'nombre_arrendador'    => trim($_POST['nombre_arrendador'] ?? ''),
            'email'                => trim($_POST['email'] ?? ''),
            'celular'              => trim($_POST['celular'] ?? ''),
            'telefono'             => trim($_POST['telefono'] ?? ''),
            'direccion_arrendador' => trim($_POST['direccion_arrendador'] ?? ''),
            'estadocivil'          => trim($_POST['estadocivil'] ?? ''),
            'nacionalidad'         => trim($_POST['nacionalidad'] ?? ''),
            'rfc'                  => trim($_POST['rfc'] ?? ''),
            'tipo_id'              => trim($_POST['tipo_id'] ?? ''),
            'num_id'               => trim($_POST['num_id'] ?? ''),
        ];

        $ok = $this->model->actualizarDatosPersonales($id, $data);
        echo json_encode(['ok' => $ok]);
    }

    /**
     * Actualiza información bancaria vía AJAX
     */
    public function actualizarInfoBancaria()
    {
        if (!$this->validarMetodoPost()) return;
        $id = $this->validarId($_POST['id'] ?? null);
        if (!$id) return;

        $data = [
            'banco'  => trim($_POST['banco'] ?? ''),
            'cuenta' => trim($_POST['cuenta'] ?? ''),
            'clabe'  => trim($_POST['clabe'] ?? ''),
        ];

        $ok = $this->model->actualizarInfoBancaria($id, $data);
        echo json_encode(['ok' => $ok]);
    }

    /**
     * Actualiza comentarios vía AJAX
     */
    public function actualizarComentarios()
    {
        if (!$this->validarMetodoPost()) return;
        $id = $this->validarId($_POST['id'] ?? null);
        if (!$id) return;

        $comentarios = trim($_POST['comentarios'] ?? '');
        $ok = $this->model->actualizarComentarios($id, $comentarios);
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

    private function validarId($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            echo json_encode(['ok' => false, 'error' => 'ID inválido']);
            return false;
        }
        return $id;
    }

    private function sanitizarDatosBasicos(array $input): array
    {
        return [
            'nombre_arrendador' => trim($input['nombre_arrendador'] ?? ''),
            'email'             => trim($input['email'] ?? ''),
            'celular'           => trim($input['celular'] ?? ''),
            'telefono'          => trim($input['telefono'] ?? ''),
            'rfc'               => trim($input['rfc'] ?? ''),
        ];
    }

    private function cargarVistaAsociada($id, string $titulo, string $vista)
    {
        $arrendador = $this->model->obtenerPorId((int) $id);
        if (!$arrendador) {
            header('Location: ' . getBaseUrl() . '/arrendadores');
            exit;
        }
        $title       = $titulo;
        $headerTitle = $titulo;
        $contentView = __DIR__ . '/../Views/arrendadores/' . $vista;
        include __DIR__ . '/../Views/layouts/main.php';
    }
}
