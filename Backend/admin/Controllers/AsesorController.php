<?php
namespace App\Controllers;

require_once __DIR__ . '/../Models/AsesorModel.php';
use App\Models\AsesorModel;

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
        $asesores    = $this->model->all();
        $title       = 'Asesores - AS';
        $headerTitle = 'Asesores Inmobiliarios';
        $contentView = __DIR__ . '/../Views/asesores/index.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Muestra el formulario de creación de un asesor
     */
    public function create()
    {
        $title       = 'Nuevo Asesor';
        $headerTitle = 'Nuevo Asesor';
        $contentView = __DIR__ . '/../Views/asesores/create.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Guarda un nuevo asesor en la base de datos
     */
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->sanitizarDatos($_POST);

            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                // Aquí podrías agregar manejo de errores con SweetAlert o similar
                header('Location: ' . getBaseUrl() . '/asesores');
                exit;
            }

            $this->model->create($data);
        }
        header('Location: ' . getBaseUrl() . '/asesores');
        exit;
    }

    /**
     * Muestra el formulario de edición de un asesor
     */
    public function edit()
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: ' . getBaseUrl() . '/asesores');
            exit;
        }

        $asesor = $this->model->find($id);
        if (!$asesor) {
            header('Location: ' . getBaseUrl() . '/asesores');
            exit;
        }

        $title       = 'Editar Asesor';
        $headerTitle = 'Editar Asesor';
        $contentView = __DIR__ . '/../Views/asesores/edit.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Actualiza los datos de un asesor existente
     */
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($id > 0) {
                $data = $this->sanitizarDatos($_POST);

                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    header('Location: ' . getBaseUrl() . '/asesores');
                    exit;
                }

                $this->model->update($id, $data);
            }
        }
        header('Location: ' . getBaseUrl() . '/asesores');
        exit;
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
            'telefono'      => trim($input['telefono'] ?? '')
        ];
    }
}
