<?php
declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Models/FinancieroModel.php';

use App\Middleware\AuthMiddleware;
use App\Models\FinancieroModel;

// Verifica sesión activa
AuthMiddleware::verificarSesion();

/**
 * Controlador de reportes financieros
 */
class FinancieroController
{
    protected FinancieroModel $model;

    public function __construct()
    {
        $this->model = new FinancieroModel();
    }

    /**
     * Vista principal de financieros
     */
    public function index(): void
    {
        // Obtener mes desde query param o usar mes actual (validando formato)
        $mesSeleccionado = $_GET['mes'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $mesSeleccionado)) {
            $mesSeleccionado = date('Y-m');
        }

        // Ej: compats legadas
        $anioConsulta = (int)date('Y', strtotime($mesSeleccionado));
        $mesNumerico  = (int)date('m', strtotime($mesSeleccionado));

        // --- Consultas al modelo ---
        $ingresosPorMesRaw   = $this->model->obtenerIngresosPorMes((string)$anioConsulta) ?? [];
        $resumen             = $this->model->obtenerResumenPorAnioMes($anioConsulta, $mesNumerico) ?? ['total_mes' => 0, 'polizas_mes' => 0];
        $ingresosAcumulados  = $this->model->obtenerIngresosAnuales((string)$anioConsulta) ?? 0.0;
        $sumatorias          = $this->model->obtenerSumatoriasPorCanalAnioMes($anioConsulta, $mesNumerico) ?? ['total_arrendamiento' => 0, 'total_inmobiliaria' => 0];

        // --- Variables planas para la vista ---
        $ingresosMes        = (float)($resumen['total_mes'] ?? 0);
        $polizasMes         = (int)  ($resumen['polizas_mes'] ?? 0);
        $totalArrendamiento = (float)($sumatorias['total_arrendamiento'] ?? 0);
        $totalInmobiliaria  = (float)($sumatorias['total_inmobiliaria'] ?? 0);

        // Normalizar datos para gráfico
        $ingresosPorMes = array_map(static function ($row) {
            return [
                'mes'   => (int)($row['mes']   ?? 0),   // 1..12
                'total' => (float)($row['total'] ?? 0), // bruto
                'neto'  => (float)($row['neto']  ?? 0), // ganancia
            ];
        }, $ingresosPorMesRaw);

        // --- NUEVO: Ventas del periodo (tabla) ---
        $ventasPeriodo = $this->model->listarVentasPorAnioMes($anioConsulta, $mesNumerico);
        $totalBrutoPeriodo = 0.0;
        $totalNetoPeriodo  = 0.0;
        foreach ($ventasPeriodo as $v) {
            $totalBrutoPeriodo += (float)$v['monto_venta'];
            $totalNetoPeriodo  += (float)$v['ganancia_neta'];
        }

        // Variables para la vista
        $title       = 'Financieros - AS';
        $headerTitle = 'Financieros';
        $contentView = __DIR__ . '/../Views/financieros/index.php';

        // Renderizar vista
        include __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Vista para registrar una nueva venta
     */
    public function registroVenta(): void
    {
        $title       = 'Registrar Venta - AS';
        $headerTitle = 'Registrar Venta';
        $contentView = __DIR__ . '/../Views/financieros/registro-venta.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }
}
