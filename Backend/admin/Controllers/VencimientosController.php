<?php
namespace App\Controllers;
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
use App\Middleware\AuthMiddleware;
AuthMiddleware::verificarSesion();

require_once __DIR__ . '/../Models/PolizaModel.php';

use App\Models\PolizaModel;

class VencimientosController
{
    public function index()
    {
        $model = new PolizaModel();

        $mes  = isset($_GET['mes']) ? (int) $_GET['mes'] : null;
        $anio = isset($_GET['anio']) ? (int) $_GET['anio'] : null;

        if ($mes && $anio) {
            $polizas = $model->obtenerVencimientosPorMesAnio($mes, $anio);
        } else {
            $polizas = $model->obtenerVencimientosProximos();

            $mesActual  = (int) date('n');
            $anioActual = (int) date('Y');
            $mes  = $mesActual + 1;
            $anio = $anioActual;
            if ($mes > 12) {
                $mes = 1;
                $anio++;
            }
        }

        $mesSeleccionado  = $mes;
        $anioSeleccionado = $anio;

        $title       = 'Vencimientos - AS';
        $headerTitle = 'Vencimientos próximos';
        $contentView = __DIR__ . '/../Views/vencimientos/index.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }
}
