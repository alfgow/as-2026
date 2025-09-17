<?php

namespace App\Models;

require_once __DIR__ . '/../Core/Database.php';

use App\Core\Database;
use PDO;
use PDOException;

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
}
