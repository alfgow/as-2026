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
    /**
     * Muestra el Dashboard con KPIs, últimos inquilinos y vencimientos próximos.
     */
    public function index(): void
    {
        // ====== Títulos para la vista / layout ======
        $title       = 'Dashboard - AS';
        $headerTitle = 'Panel de Control';

        // ====== Modelos ======
        $inquilinoModel = new InquilinoModel();
        $polizaModel    = new PolizaModel();

        // ====== KPIs ======
        // Total de inquilinos nuevos (status=1)
        $totalInquilinosNuevos = (int) $inquilinoModel->contarInquilinosNuevos();

        // Lista (máxima) de inquilinos nuevos con selfie desde S3
        $inquilinosNuevos = (array) $inquilinoModel->getInquilinosNuevosConSelfie();

        // Pólizas próximas a vencer
        $vencimientosProximos = (array) $polizaModel->obtenerVencimientosProximos();

        // Última póliza emitida (número de póliza o '0')
        $ultimaPoliza = (string) $polizaModel->obtenerUltimaPolizaEmitida();

        // ====== Render ======
        $contentView = __DIR__ . '/../Views/dashboard/index.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }
}
