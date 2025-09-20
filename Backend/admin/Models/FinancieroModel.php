<?php
declare(strict_types=1);

namespace App\Models;
require_once __DIR__ . '/../Core/Database.php';
use App\Core\Database;
use PDO;

/**
 * Modelo Financiero
 *
 * Fuente de datos: ventasvillanuevagarcia
 *  - id_venta (PK)
 *  - id_usuario (int)
 *  - canal_venta (varchar)
 *  - concepto_venta (varchar)
 *  - monto_venta (decimal)
 *  - comision_asesor (decimal)
 *  - ganancia_neta (decimal)
 *  - mes_venta (varchar 'mm YYYY')  // histórico
 *  - year_venta (varchar)           // usar para filtros por año
 *  - fecha_venta (timestamp)        // usar para filtros por mes/año
 */
class FinancieroModel extends Database
{
    public function __construct()
    {
        parent::__construct();
    }

    /* ==========================================================
       RESÚMENES / MÉTRICAS
       ========================================================== */

    /**
     * Recomendado: resumen por año/mes usando fecha_venta.
     * @return array{total_mes:float, polizas_mes:int}
     */
    public function obtenerResumenPorAnioMes(int $anio, int $mes): array
    {
        $sql = "SELECT
                    COALESCE(SUM(ganancia_neta),0) AS total_mes,
                    COUNT(*)                       AS polizas_mes
                FROM ventasvillanuevagarcia
                WHERE YEAR(fecha_venta) = :y
                  AND MONTH(fecha_venta) = :m";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':y' => $anio, ':m' => $mes]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_mes' => 0, 'polizas_mes' => 0];
        return [
            'total_mes'   => (float)$r['total_mes'],
            'polizas_mes' => (int)$r['polizas_mes'],
        ];
    }

    /**
     * Ingresos anuales usando fecha_venta como fuente de verdad.
     */
    public function obtenerIngresosAnuales(string $anio): float
    {
        $sql = "SELECT COALESCE(SUM(ganancia_neta),0) AS total_anual
                FROM ventasvillanuevagarcia
                WHERE YEAR(fecha_venta) = :y";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':y' => $anio]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_anual' => 0];
        return (float)$resultado['total_anual'];
    }

    /**
     * Ingresos por mes dentro de un año.
     * Devuelve filas con: mes (1..12), total (monto_venta) y neto (ganancia_neta).
     *
     * Nota: ordena por MONTH(fecha_venta) para asegurar cronología.
     * Si prefieres usar el string `mes_venta`, considera inconsistencia de formato.
     */
    public function obtenerIngresosPorMes(string $anio): array
    {
        $sql = "SELECT
                    MONTH(fecha_venta)               AS mes,
                    COALESCE(SUM(monto_venta),0)     AS total,
                    COALESCE(SUM(ganancia_neta),0)   AS neto
                FROM ventasvillanuevagarcia
                WHERE YEAR(fecha_venta) = :y
                GROUP BY MONTH(fecha_venta)
                ORDER BY MONTH(fecha_venta)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':y' => $anio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recomendado: sumatorias por canal para año/mes (usa fecha_venta).
     */
    public function obtenerSumatoriasPorCanalAnioMes(int $anio, int $mes): array
    {
        $sql = "SELECT
                    COALESCE(SUM(CASE WHEN canal_venta = 'Arrendamiento Seguro'
                        THEN ganancia_neta ELSE 0 END),0) AS total_arrendamiento,
                    COALESCE(SUM(CASE WHEN canal_venta <> 'Arrendamiento Seguro'
                        THEN ganancia_neta ELSE 0 END),0) AS total_inmobiliaria
                FROM ventasvillanuevagarcia
                WHERE YEAR(fecha_venta) = :y
                  AND MONTH(fecha_venta) = :m";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':y' => $anio, ':m' => $mes]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_arrendamiento' => 0, 'total_inmobiliaria' => 0];
        return [
            'total_arrendamiento' => (float)$row['total_arrendamiento'],
            'total_inmobiliaria'  => (float)$row['total_inmobiliaria'],
        ];
    }

    /* ==========================================================
       REGISTRO DE VENTAS
       ========================================================== */

    /**
     * Inserta una venta derivada de una póliza.
     * Campos mínimos esperados en $data:
     *  - numero_poliza (int|string)
     *  - year_vencimiento (int|string)  // se usa en concepto
     *  - monto_poliza (float|numeric-string)
     *
     * Aplica split 20% comisión / 80% ganancia neta.
     */
    public function registrarVentaAutomatica(array $data): bool
    {
        $monto = (float) ($data['monto_poliza'] ?? 0);
        $comision = round($monto * 0.20, 2);
        $neto     = round($monto * 0.80, 2);

        $concepto = sprintf(
            '%s / %s',
            (string)($data['numero_poliza'] ?? ''),
            (string)($data['year_vencimiento'] ?? '')
        );

        $sql = "INSERT INTO ventasvillanuevagarcia (
                    id_usuario, canal_venta, concepto_venta, monto_venta,
                    comision_asesor, ganancia_neta, mes_venta, year_venta, fecha_venta
                ) VALUES (
                    :id_usuario, :canal_venta, :concepto_venta, :monto_venta,
                    :comision_asesor, :ganancia_neta, :mes_venta, :year_venta, :fecha_venta
                )";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id_usuario'      => 1, // TODO: parametrizar usuario real de sesión
            ':canal_venta'     => 'Arrendamiento Seguro',
            ':concepto_venta'  => $concepto,
            ':monto_venta'     => $monto,
            ':comision_asesor' => $comision,
            ':ganancia_neta'   => $neto,
            ':mes_venta'       => date('m Y'),          // mantiene compat con campo existente
            ':year_venta'      => date('Y'),
            ':fecha_venta'     => date('Y-m-d H:i:s'),  // fuente para queries por año/mes
        ]);
    }

    /* ==========================================================
       EXTRAS ÚTILES (opcionales)
       ========================================================== */

    /**
     * Ventas por rango de fechas (incluye totales).
     * @return array{rows: array<int, array<string,mixed>>, total_bruto:float, total_neto:float}
     */
    public function ventasPorRango(string $inicioYmd, string $finYmd): array
    {
        $sql = "SELECT *
                FROM ventasvillanuevagarcia
                WHERE fecha_venta BETWEEN :ini AND :fin
                ORDER BY fecha_venta ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ini' => $inicioYmd . ' 00:00:00', ':fin' => $finYmd . ' 23:59:59']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $sql2 = "SELECT
                    COALESCE(SUM(monto_venta),0)   AS total_bruto,
                    COALESCE(SUM(ganancia_neta),0) AS total_neto
                 FROM ventasvillanuevagarcia
                 WHERE fecha_venta BETWEEN :ini AND :fin";
        $stmt2 = $this->db->prepare($sql2);
        $stmt2->execute([':ini' => $inicioYmd . ' 00:00:00', ':fin' => $finYmd . ' 23:59:59']);
        $tot = $stmt2->fetch(PDO::FETCH_ASSOC) ?: ['total_bruto' => 0, 'total_neto' => 0];

        return [
            'rows'        => $rows,
            'total_bruto' => (float)$tot['total_bruto'],
            'total_neto'  => (float)$tot['total_neto'],
        ];
    }
    
    public function listarVentasPorAnioMes(int $anio, int $mes): array
    {
        $sql = "SELECT
                    id_venta,
                    fecha_venta,
                    canal_venta,
                    concepto_venta,
                    monto_venta,
                    comision_asesor,
                    ganancia_neta
                FROM ventasvillanuevagarcia
                WHERE YEAR(fecha_venta) = :y
                AND MONTH(fecha_venta) = :m
                ORDER BY fecha_venta DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':y' => $anio, ':m' => $mes]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

}