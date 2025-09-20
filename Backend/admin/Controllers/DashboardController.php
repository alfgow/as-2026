<?php
declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . '/../Models/InquilinoModel.php';
require_once __DIR__ . '/../Models/PolizaModel.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';

use App\Models\InquilinoModel;
use App\Models\PolizaModel;
use App\Middleware\AuthMiddleware;

/**
 * Controlador del Dashboard
 *
 * Nota importante:
 * - Este controlador ya no usa el término "prospecto".
 * - Todo se nombra como "inquilino" o "inquilinos".
 * - Requiere que el InquilinoModel exponga los métodos:
 *      - contarInquilinosNuevos(): int
 *      - getInquilinosNuevosConSelfie(): array
 *   Si tus métodos siguen llamándose contarProspectosNuevos() / getProspectosNuevosConSelfie(),
 *   o actualiza el modelo o cambia las llamadas aquí.
 */

// Verifica que el usuario tenga sesión activa
AuthMiddleware::verificarSesion();

class DashboardController
{
    public function __construct(
        private readonly InquilinoModel $inquilinoModel = new InquilinoModel(),
        private readonly PolizaModel $polizaModel = new PolizaModel(),
    ) {
    }

    /**
     * Muestra el Dashboard con KPIs, últimos inquilinos y vencimientos próximos.
     */
    public function index(): void
    {
        // ====== Títulos para la vista / layout ======
        $title       = 'Dashboard - AS';
        $headerTitle = 'Panel de Control';

        // ====== KPIs ======
        $totalInquilinosNuevos = $this->obtenerTotalInquilinosNuevos();
        $inquilinosNuevos      = $this->obtenerInquilinosNuevosConSelfie();
        $vencimientosProximos  = $this->obtenerVencimientosProximos();
        $ultimaPoliza          = $this->obtenerUltimaPolizaEmitida();

        // ====== Render ======
        $contentView = __DIR__ . '/../Views/dashboard/index.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }

    private function obtenerTotalInquilinosNuevos(): int
    {
        return (int) $this->inquilinoModel->contarInquilinosNuevos();
    }

    private function obtenerInquilinosNuevosConSelfie(): array
    {
        return (array) $this->inquilinoModel->getInquilinosNuevosConSelfie();
    }

    private function obtenerVencimientosProximos(): array
    {
        return (array) $this->polizaModel->obtenerVencimientosProximos();
    }

    private function obtenerUltimaPolizaEmitida(): string
    {
        return (string) $this->polizaModel->obtenerUltimaPolizaEmitida();
    }
}
