<?php
declare(strict_types=1);

namespace App\Models;
require_once __DIR__ . '/../Core/Database.php';
use App\Core\Database;
use PDO;
use PDOException;

/**
 * Modelo: Asesores
 *
 * Tabla: asesores
 *  - id (PK, AI)
 *  - nombre_asesor (UNIQUE)
 *  - email
 *  - celular
 *  - telefono
 *
 * Relación lógica (sin FK explícita en BD):
 *  - arrendadores.id_asesor
 *  - inmuebles.id_asesor
 *  - polizas.id_asesor
 *
 * Notas:
 *  - Se valida unicidad de nombre_asesor antes de insertar/actualizar para
 *    evitar excepciones por la restricción UNIQUE.
 */
class AsesorModel extends Database
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Listar todos (ordenados por nombre).
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM asesores ORDER BY nombre_asesor ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener por ID.
     * @param int $id
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM asesores WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /**
     * Buscar con paginación (por nombre/email/celular/telefono).
     * @param string $q
     * @param int $offset
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    public function search(string $q, int $offset = 0, int $limit = 20): array
    {
        $sql = "SELECT *
                FROM asesores
                WHERE nombre_asesor LIKE :q
                   OR email         LIKE :q
                   OR celular       LIKE :q
                   OR telefono      LIKE :q
                ORDER BY nombre_asesor ASC
                LIMIT :offset, :limit";
        $stmt = $this->db->prepare($sql);
        $like = '%' . $q . '%';
        $stmt->bindValue(':q', $like, PDO::PARAM_STR);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return (array) $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Total de resultados para la misma búsqueda de search().
     */
    public function searchCount(string $q): int
    {
        $sql = "SELECT COUNT(*)
                FROM asesores
                WHERE nombre_asesor LIKE :q
                   OR email         LIKE :q
                   OR celular       LIKE :q
                   OR telefono      LIKE :q";
        $stmt = $this->db->prepare($sql);
        $like = '%' . $q . '%';
        $stmt->bindValue(':q', $like, PDO::PARAM_STR);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Crear asesor. Devuelve el ID insertado.
     * Valida nombre_asesor único.
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $nombre = (string) ($data['nombre_asesor'] ?? '');
        $email  = (string) ($data['email'] ?? '');
        $cel    = (string) ($data['celular'] ?? '');
        $tel    = (string) ($data['telefono'] ?? '');

        if ($this->existsByName($nombre)) {
            throw new PDOException("El nombre del asesor ya existe.");
        }

        $sql = "INSERT INTO asesores (nombre_asesor, email, celular, telefono)
                VALUES (:nombre_asesor, :email, :celular, :telefono)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre_asesor' => $nombre,
            ':email'         => $email,
            ':celular'       => $cel,
            ':telefono'      => $tel,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualizar asesor por ID.
     * Valida nombre_asesor único (excluyendo el propio ID).
     * @param int $id
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        $nombre = (string) ($data['nombre_asesor'] ?? '');
        $email  = (string) ($data['email'] ?? '');
        $cel    = (string) ($data['celular'] ?? '');
        $tel    = (string) ($data['telefono'] ?? '');

        if ($this->existsByName($nombre, $id)) {
            throw new PDOException("El nombre del asesor ya existe.");
        }

        $sql = "UPDATE asesores
                SET nombre_asesor = :nombre_asesor,
                    email         = :email,
                    celular       = :celular,
                    telefono      = :telefono
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nombre_asesor' => $nombre,
            ':email'         => $email,
            ':celular'       => $cel,
            ':telefono'      => $tel,
            ':id'            => $id,
        ]);
    }

    /**
     * Eliminar asesor (opción segura: valida que no tenga uso en otras tablas).
     * Retorna false si está referenciado.
     */
    public function delete(int $id): bool
    {
        if ($this->hasUsage($id)) {
            // Si prefieres borrar “forzado”, puedes implementar reasignación o borrado en cascada lógico.
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM asesores WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Lista básica para selects (id, nombre).
     * @return array<int, array{id:int, nombre_asesor:string}>
     */
    public function forSelect(): array
    {
        $stmt = $this->db->query("SELECT id, nombre_asesor FROM asesores ORDER BY nombre_asesor ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ======================
       Métodos auxiliares
       ====================== */

    /**
     * ¿Existe un asesor con ese nombre? (opcionalmente excluye un ID)
     */
    public function existsByName(string $nombre_asesor, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM asesores WHERE nombre_asesor = :n";
        $params = [':n' => $nombre_asesor];

        if ($excludeId !== null) {
            $sql .= " AND id <> :id";
            $params[':id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return ((int) $stmt->fetchColumn()) > 0;
    }

    /**
     * ¿Está siendo usado por arrendadores, inmuebles o polizas?
     * (No hay FK en BD para bloquearlo; lo hacemos por app)
     */
    public function hasUsage(int $id): bool
    {
        // Cuenta referencias en arrendadores
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM arrendadores WHERE id_asesor = ?");
        $stmt->execute([$id]);
        $enArrendadores = (int) $stmt->fetchColumn();

        // Cuenta referencias en inmuebles
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM inmuebles WHERE id_asesor = ?");
        $stmt->execute([$id]);
        $enInmuebles = (int) $stmt->fetchColumn();

        // Cuenta referencias en polizas
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM polizas WHERE id_asesor = ?");
        $stmt->execute([$id]);
        $enPolizas = (int) $stmt->fetchColumn();

        return ($enArrendadores + $enInmuebles + $enPolizas) > 0;
    }

    /**
     * Indicadores rápidos del asesor: cuántos arrendadores/inmuebles/pólizas tiene.
     * @return array<string,int>
     */
    public function indicadores(int $id): array
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM arrendadores WHERE id_asesor = ?");
        $stmt->execute([$id]);
        $arr = (int) $stmt->fetchColumn();

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM inmuebles WHERE id_asesor = ?");
        $stmt->execute([$id]);
        $inm = (int) $stmt->fetchColumn();

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM polizas WHERE id_asesor = ?");
        $stmt->execute([$id]);
        $pol = (int) $stmt->fetchColumn();

        return [
            'arrendadores' => $arr,
            'inmuebles'    => $inm,
            'polizas'      => $pol,
        ];
        }
}