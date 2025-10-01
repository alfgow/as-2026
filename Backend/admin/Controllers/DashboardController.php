<?php
declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . '/../Models/InquilinoModel.php';
require_once __DIR__ . '/../Models/PolizaModel.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';

use App\Models\InquilinoModel;
use App\Models\PolizaModel;
use App\Middleware\AuthMiddleware;
use DateTimeImmutable;
use DateTimeInterface;
use Throwable;

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
        $vencimientosProximos  = array_map(function (array $poliza): array {
            $poliza['fecha_vencimiento_normalizada'] = $this->normalizarFechaVencimiento($poliza);

            return $poliza;
        }, $vencimientosProximos);

        usort(
            $vencimientosProximos,
            function (array $a, array $b): int {
                $fechaA = $a['fecha_vencimiento_normalizada'] ?? null;
                $fechaB = $b['fecha_vencimiento_normalizada'] ?? null;

                if (!$fechaA instanceof DateTimeInterface && !$fechaB instanceof DateTimeInterface) {
                    return 0;
                }

                if (!$fechaA instanceof DateTimeInterface) {
                    return 1;
                }

                if (!$fechaB instanceof DateTimeInterface) {
                    return -1;
                }

                return $fechaA->getTimestamp() <=> $fechaB->getTimestamp();
            }
        );
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

    private function normalizarFechaVencimiento(array $poliza): ?DateTimeImmutable
    {
        $fechaFin = $poliza['fecha_fin'] ?? null;

        if (!empty($fechaFin)) {
            try {
                return (new DateTimeImmutable((string) $fechaFin))->setTime(0, 0);
            } catch (Throwable $exception) {
                // Continuar con las demás opciones de normalización
            }
        }

        $vigencia = $poliza['vigencia'] ?? null;

        if (is_string($vigencia)) {
            $patron = '/al\s+(\d{1,2})\s+de\s+([[:alpha:]]+)\s+de\s+(\d{4})/iu';

            if (preg_match($patron, $vigencia, $coincidencias)) {
                $dia       = (int) $coincidencias[1];
                $mesNombre = mb_strtolower($coincidencias[2], 'UTF-8');
                $anio      = (int) $coincidencias[3];

                $meses = [
                    'enero'      => 1,
                    'febrero'    => 2,
                    'marzo'      => 3,
                    'abril'      => 4,
                    'mayo'       => 5,
                    'junio'      => 6,
                    'julio'      => 7,
                    'agosto'     => 8,
                    'septiembre' => 9,
                    'setiembre'  => 9,
                    'octubre'    => 10,
                    'noviembre'  => 11,
                    'diciembre'  => 12,
                ];

                if (array_key_exists($mesNombre, $meses)) {
                    $fecha = sprintf('%04d-%02d-%02d', $anio, $meses[$mesNombre], $dia);

                    try {
                        return (new DateTimeImmutable($fecha))->setTime(0, 0);
                    } catch (Throwable $exception) {
                        // Continuar con las demás opciones de normalización
                    }
                }
            }
        }

        $mes  = $poliza['mes_vencimiento'] ?? null;
        $anio = $poliza['year_vencimiento'] ?? null;

        if ($mes !== null && $anio !== null) {
            $mesFormateado   = str_pad((string) $mes, 2, '0', STR_PAD_LEFT);
            $fechaConstruida = sprintf('%s-%s-01', (string) $anio, $mesFormateado);

            try {
                return (new DateTimeImmutable($fechaConstruida))->setTime(0, 0);
            } catch (Throwable $exception) {
                return null;
            }
        }

        return null;
    }
}
