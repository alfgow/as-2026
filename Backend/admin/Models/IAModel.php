<?php

namespace App\Models;

require_once __DIR__ . '/../Core/Database.php';

use App\Core\Database;
use PDO;

class IAModel extends Database
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Inserta una interacción IA.
     * - usuario_id: int|null
     * - modelo_key: varchar(20)
     * - modelo_id : varchar(200)
     * - prompt    : text (requerido)
     * - respuesta : longtext|null
     * - duration_ms: int (default 0)
     * - ip        : varchar(45)|null
     * - user_agent: varchar(255)|null
     */
    public function registrarInteraccion(array $data): bool
    {
        // Saneo de longitudes para evitar errores por overflow en columnas.
        $modeloKey = mb_substr((string)$data['modelo_key'], 0, 20);
        $modeloId  = mb_substr((string)$data['modelo_id'], 0, 200);
        $ip        = isset($data['ip']) ? mb_substr((string)$data['ip'], 0, 45) : null;
        $userAgent = isset($data['user_agent']) ? mb_substr((string)$data['user_agent'], 0, 255) : null;
        $durMs     = isset($data['duration_ms']) ? (int)$data['duration_ms'] : 0;
        $contexto  = isset($data['contexto']) ? (string)$data['contexto'] : null;

        $sql = "INSERT INTO ia_interacciones
            (usuario_id, modelo_key, modelo_id, prompt, respuesta, duration_ms, ip, user_agent, contexto)
            VALUES (:usuario_id, :modelo_key, :modelo_id, :prompt, :respuesta, :duration_ms, :ip, :user_agent, :contexto)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':usuario_id'  => $data['usuario_id'] ?? null,
            ':modelo_key'  => $modeloKey,
            ':modelo_id'   => $modeloId,
            ':prompt'      => $data['prompt'],
            ':respuesta'   => $data['respuesta'] ?? null,
            ':duration_ms' => $durMs,
            ':ip'          => $ip,
            ':user_agent'  => $userAgent,
            ':contexto'    => $contexto,
        ]);
    }

    /**
     * Obtiene la última interacción registrada para un usuario dado.
     */
    public function obtenerUltimaInteraccion(): ?array
    {
        $sql = "SELECT * 
            FROM ia_interacciones 
            ORDER BY id DESC 
            LIMIT 1";

        $stmt = $this->db->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Si hay contexto en JSON, lo decodificamos
            if (!empty($row['contexto'])) {
                $decoded = json_decode($row['contexto'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $row['contexto'] = $decoded;
                }
            }
            return $row;
        }

        return null;
    }




    /**
     * Lista interacciones más recientes con límite y offset.
     */
    public function listar(int $limit = 50, int $offset = 0): array
    {
        $limit  = max(1, $limit);
        $offset = max(0, $offset);

        $sql = "SELECT id, usuario_id, modelo_key, modelo_id, duration_ms, created_at
                FROM ia_interacciones
                ORDER BY id DESC
                LIMIT :lim OFFSET :off";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Obtiene un registro completo por ID.
     */
    public function obtener(int $id): ?array
    {
        $sql = "SELECT * FROM ia_interacciones WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Búsqueda filtrada (opcional) con paginación sencilla.
     * $filtros soportados: usuario_id, modelo_key (LIKE), desde (fecha), hasta (fecha)
     */
    public function buscar(array $filtros = [], int $limit = 50, int $offset = 0): array
    {
        $where = [];
        $params = [];

        if (!empty($filtros['usuario_id'])) {
            $where[] = 'usuario_id = :usuario_id';
            $params[':usuario_id'] = (int)$filtros['usuario_id'];
        }
        if (!empty($filtros['modelo_key'])) {
            $where[] = 'modelo_key LIKE :modelo_key';
            $params[':modelo_key'] = '%' . $filtros['modelo_key'] . '%';
        }
        if (!empty($filtros['desde'])) {
            $where[] = 'created_at >= :desde';
            $params[':desde'] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $where[] = 'created_at <= :hasta';
            $params[':hasta'] = $filtros['hasta'];
        }

        $sql = "SELECT id, usuario_id, modelo_key, modelo_id, duration_ms, created_at
                FROM ia_interacciones";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= " ORDER BY id DESC LIMIT :lim OFFSET :off";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':lim', max(1, $limit), PDO::PARAM_INT);
        $stmt->bindValue(':off', max(0, $offset), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Cuenta total para la misma búsqueda de `buscar()`.
     */
    public function contar(array $filtros = []): int
    {
        $where = [];
        $params = [];

        if (!empty($filtros['usuario_id'])) {
            $where[] = 'usuario_id = :usuario_id';
            $params[':usuario_id'] = (int)$filtros['usuario_id'];
        }
        if (!empty($filtros['modelo_key'])) {
            $where[] = 'modelo_key LIKE :modelo_key';
            $params[':modelo_key'] = '%' . $filtros['modelo_key'] . '%';
        }
        if (!empty($filtros['desde'])) {
            $where[] = 'created_at >= :desde';
            $params[':desde'] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $where[] = 'created_at <= :hasta';
            $params[':hasta'] = $filtros['hasta'];
        }

        $sql = "SELECT COUNT(*) FROM ia_interacciones";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    /**
     * Busca inquilinos en MySQL utilizando nombre, correo, teléfono o identificadores.
     * Devuelve un arreglo normalizado compatible con la versión anterior basada en Dynamo.
     */
    public function buscarInquilinosPorTexto(string $term, int $limit = 10): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }

        $limit = max(1, $limit);

        $conditions = [
            "CONCAT_WS(' ', i.nombre_inquilino, i.apellidop_inquilino, i.apellidom_inquilino) LIKE :term_like",
            'i.email LIKE :term_like',
            'i.celular LIKE :term_like',
            'COALESCE(i.rfc, "") LIKE :term_like',
            'COALESCE(i.curp, "") LIKE :term_like',
        ];

        $params = [
            ':term_like' => '%' . $term . '%',
        ];

        if (filter_var($term, FILTER_VALIDATE_EMAIL)) {
            $conditions[] = 'i.email = :email_exact';
            $params[':email_exact'] = $term;
        }

        $digits = preg_replace('/\D+/', '', $term);
        if ($digits !== '' && strlen($digits) >= 4) {
            $conditions[] = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(i.celular, ' ', ''), '-', ''), '+', ''), '(', ''), ')', '') LIKE :phone_like";
            $params[':phone_like'] = '%' . $digits . '%';
        }

        if (ctype_digit($term)) {
            $conditions[] = 'i.id = :id_exact';
            $params[':id_exact'] = (int) $term;
        }

        $where = implode(' OR ', array_unique($conditions));

        $sql = "SELECT
                    i.id,
                    TRIM(CONCAT_WS(' ', i.nombre_inquilino, i.apellidop_inquilino, i.apellidom_inquilino)) AS nombre,
                    i.email,
                    COALESCE(i.celular, '') AS celular,
                    COALESCE(NULLIF(i.tipo, ''), 'inquilino') AS tipo
                FROM inquilinos i
                WHERE i.status = 1
                  AND ({$where})
                ORDER BY i.updated_at DESC
                LIMIT :lim";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            if ($key === ':id_exact') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }

        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(static function (array $row): array {
            return [
                'id'      => (int) $row['id'],
                'nombre'  => (string) ($row['nombre'] ?? ''),
                'email'   => (string) ($row['email'] ?? ''),
                'celular' => (string) ($row['celular'] ?? ''),
                'tipo'    => (string) ($row['tipo'] ?? 'inquilino'),
            ];
        }, $rows);
    }

    /**
     * Obtiene las pólizas activas de un inquilino con la información relevante del inmueble.
     */
    public function obtenerPolizasActivasPorInquilino(int $inquilinoId): array
    {
        $sql = "SELECT
                    p.numero_poliza,
                    p.monto_poliza,
                    p.vigencia,
                    inm.direccion_inmueble,
                    inm.renta,
                    arr.nombre_arrendador AS arrendador
                FROM polizas p
                INNER JOIN inmuebles inm ON p.id_inmueble = inm.id
                INNER JOIN arrendadores arr ON inm.id_arrendador = arr.id
                WHERE p.id_inquilino = :id
                  AND p.estado = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $inquilinoId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
