<?php
namespace Backend\admin\Controllers;

require_once __DIR__ . '/../Models/IAModel.php';
use App\Models\IAModel;

class IAHistorialController
{
    private $model;

    public function __construct()
    {
        $this->model = new IAModel();
    }

    public function index()
    {
        $title        = 'Historial IA - AS';
        $headerTitle  = 'Historial de Interacciones IA';
        $items        = $this->model->listar(50); // últimos 50
        $contentView  = __DIR__ . '/../Views/ia/historial.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }

    public function ver(int $id)
    {
        $title        = 'Detalle Interacción IA - AS';
        $headerTitle  = 'Detalle de Interacción';
        $row          = $this->model->obtener($id);
        $contentView  = __DIR__ . '/../Views/ia/detalle.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }
}
