<?php
namespace App\Models;

require_once __DIR__ . '/../Core/Database.php';

use App\Core\Database;
use PDO;

class ValidacionLegalModel extends Database
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Registra un intento vacío (antes de llamar AWS), útil para bitácora.
     */
    public function registrarIntento(array $data): int
    {
        $sql = "INSERT INTO validaciones_legal
                (id_inquilino, nombre, apellido_p, apellido_m, curp, rfc, portal, query_usada, status, searched_at)
                VALUES (:id_inquilino, :nombre, :apellido_p, :apellido_m, :curp, :rfc, :portal, :query_usada, :status, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_inquilino' => (int)$data['id_inquilino'],
            ':nombre'       => trim($data['nombre'] ?? ''),
            ':apellido_p'   => trim($data['apellido_p'] ?? ''),
            ':apellido_m'   => $data['apellido_m'] !== null ? trim($data['apellido_m']) : null,
            ':curp'         => $data['curp'] ?? null,
            ':rfc'          => $data['rfc'] ?? null,
            ':portal'       => trim($data['portal']),
            ':query_usada'  => json_encode($data['query_usada'], JSON_UNESCAPED_UNICODE),
            ':status'       => $data['status'] ?? 'no_data',
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Guarda el resultado de un intento (tras scraping/consulta).
     */
    public function guardarResultado(int $id, array $resultado = null, int $scoreMax = 0, string $clasificacion = 'sin_evidencia',
                                     ?string $evidenciaKey = null, ?string $rawKey = null,
                                     string $status = 'ok', ?string $errorMessage = null): bool
    {
        $sql = "UPDATE validaciones_legal SET
                    resultado = :resultado,
                    score_max = :score_max,
                    clasificacion = :clasificacion,
                    evidencia_s3_key = :evidencia_s3_key,
                    raw_json_s3_key = :raw_json_s3_key,
                    status = :status,
                    error_message = :error_message,
                    updated_at = NOW()
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':resultado'        => $resultado ? json_encode($resultado, JSON_UNESCAPED_UNICODE) : null,
            ':score_max'        => $scoreMax,
            ':clasificacion'    => $clasificacion,
            ':evidencia_s3_key' => $evidenciaKey,
            ':raw_json_s3_key'  => $rawKey,
            ':status'           => $status,
            ':error_message'    => $errorMessage,
            ':id'               => $id,
        ]);
    }

    /**
     * Obtiene el último reporte por inquilino, opcionalmente filtrado por portal.
     */
    public function obtenerUltimoReportePorInquilino(int $idInquilino, ?string $portal = null): ?array
    {
        if ($portal) {
            $sql = "SELECT * FROM validaciones_legal
                    WHERE id_inquilino = ? AND portal = ?
                    ORDER BY searched_at DESC, id DESC
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idInquilino, $portal]);
        } else {
            $sql = "SELECT * FROM validaciones_legal
                    WHERE id_inquilino = ?
                    ORDER BY searched_at DESC, id DESC
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idInquilino]);
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
 * Resumen por portal para los chips.
 * 1) Intenta desde validaciones_legal (real).
 * 2) Si no hay filas, sintetiza un item desde inquilinos_validaciones.inv_demandas_json (placeholder).
 */
public function obtenerResumenPorPortal(int $idInquilino): array
{
    // 1) Intento normal: tomar el último por portal desde validaciones_legal
    $sql = "SELECT t.*
            FROM validaciones_legal t
            JOIN (
                SELECT portal, MAX(searched_at) AS max_ts
                FROM validaciones_legal
                WHERE id_inquilino = :id1
                GROUP BY portal
            ) x ON x.portal = t.portal AND x.max_ts = t.searched_at
            WHERE t.id_inquilino = :id2
            ORDER BY t.portal ASC";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([':id1' => $idInquilino, ':id2' => $idInquilino]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($rows && count($rows) > 0) {
        return $rows;
    }

    // 2) Fallback: usar el agregado guardado en inquilinos_validaciones.inv_demandas_json
    $stmt2 = $this->db->prepare("
        SELECT proceso_inv_demandas AS proceso, inv_demandas_json
        FROM inquilinos_validaciones
        WHERE id_inquilino = ?
        LIMIT 1
    ");
    $stmt2->execute([$idInquilino]);
    $row = $stmt2->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return []; // sin nada que mostrar
    }

    $json = $row['inv_demandas_json'] ?? null;
    $agg  = $json ? json_decode($json, true) : null;

    // Mapeo mínimo para un chip sintético
    $proceso = isset($row['proceso']) ? (int)$row['proceso'] : null;
    $status  = $agg['status'] ?? (($proceso === 1) ? 'ok' : 'no_data');

    $item = [
        'portal'           => 'juridico_agg',
        'status'           => $status,
        'clasificacion'    => $agg['clasificacion'] ?? 'sin_evidencia',
        'score_max'        => (int)($agg['scoring'] ?? $agg['score'] ?? $agg['score_max'] ?? 0),
        'resultado'        => json_encode($agg['evidencias'] ?? [], JSON_UNESCAPED_UNICODE),
        'query_usada'      => json_encode(['fuente' => 'inquilinos_validaciones.inv_demandas_json', 'fecha' => date('Y-m-d')]),
        'searched_at'      => date('Y-m-d H:i:s'),
        'evidencia_s3_key' => null,
        'raw_json_s3_key'  => null,
        'error_message'    => null,
    ];

    return [$item];
}



    public function obtenerValidacionDemandas(int $idInquilino): ?array
    {
        $stmt = $this->db->prepare("
            SELECT inv_demandas_json
            FROM inquilinos_validaciones
            WHERE id_inquilino = ?
            LIMIT 1
        ");
        $stmt->execute([$idInquilino]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || empty($row['inv_demandas_json'])) {
            return null;
        }

        return json_decode($row['inv_demandas_json'], true);
    }

    public function actualizarSnapshotInquilino(
        int $idInquilino,
        array $payload,
        string $status,
        ?string $resumen = null
    ): bool {
        // Map a un estado entero (ajústalo si ya tienes tu convenio)
        $map = [
            'ok'              => 1,
            'no_data'         => 2,
            'error'           => 3,
            'manual_required' => 4,
        ];
        $proceso = $map[$status] ?? 1;

        $sql = "UPDATE inquilinos_validaciones
                SET proceso_inv_demandas = :proceso,
                    inv_demandas_resumen = :resumen,
                    inv_demandas_json    = :json,
                    updated_at           = NOW()
                WHERE id_inquilino = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':proceso' => $proceso,
            ':resumen' => $resumen ?? '',
            ':json'    => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ':id'      => $idInquilino,
        ]);
    }

public function obtenerHistorialPorInquilino(int $idInquilino): array
{
    $sql = "SELECT *
            FROM validaciones_legal
            WHERE id_inquilino = ?
            ORDER BY searched_at DESC";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([$idInquilino]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


}
