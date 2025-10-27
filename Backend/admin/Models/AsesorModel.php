<?php

declare(strict_types=1);

namespace App\Models;

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Helpers/TextHelper.php';

use App\Core\Database;
use App\Helpers\TextHelper;
use PDO;
use RuntimeException;

class AsesorModel extends Database
{
    private const PK_PREFIX = 'ase#';

    public function __construct()
    {
        parent::__construct();
    }

    private function buildPk(int $id): string
    {
        return self::PK_PREFIX . $id;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function mapAsesor(array $row): array
    {
        $id = (int) ($row['id'] ?? 0);
        if ($id <= 0) {
            throw new RuntimeException('Registro de asesor inválido.');
        }

        return [
            'id'                  => $id,
            'pk'                  => $this->buildPk($id),
            'sk'                  => 'profile',
            'nombre_asesor'       => (string) ($row['nombre_asesor'] ?? ''),
            'email'               => (string) ($row['email'] ?? ''),
            'celular'             => (string) ($row['celular'] ?? ''),
            'arrendadores_total'  => (int) ($row['arrendadores_total'] ?? 0),
            'inquilinos_total'    => (int) ($row['inquilinos_total'] ?? 0),
        ];
    }

    private function baseSelect(): string
    {
        return 'SELECT a.id, a.nombre_asesor, a.email, a.celular,
                       COALESCE(arr.total_arrendadores, 0) AS arrendadores_total,
                       COALESCE(inq.total_inquilinos, 0) AS inquilinos_total
                FROM asesores a
                LEFT JOIN (
                    SELECT id_asesor, COUNT(*) AS total_arrendadores
                    FROM arrendadores
                    WHERE id_asesor IS NOT NULL
                    GROUP BY id_asesor
                ) arr ON arr.id_asesor = a.id
                LEFT JOIN (
                    SELECT id_asesor, COUNT(*) AS total_inquilinos
                    FROM inquilinos
                    GROUP BY id_asesor
                ) inq ON inq.id_asesor = a.id';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $sql  = $this->baseSelect() . ' ORDER BY LOWER(a.nombre_asesor) ASC';
        $rows = $this->fetchAll($sql);

        return array_map(fn(array $row): array => $this->mapAsesor($row), $rows);
    }

    public function find(int $id): ?array
    {
        $sql = $this->baseSelect() . ' WHERE a.id = :id LIMIT 1';
        $row = $this->fetch($sql, [':id' => $id]);

        return $row ? $this->mapAsesor($row) : null;
    }

    /**
     * @param array<int, string> $pks
     * @return array<string, array<string, mixed>>
     */
    public function batchGetByPk(array $pks): array
    {
        $ids = [];
        foreach ($pks as $pk) {
            if (preg_match('/^ase#(\d+)$/i', (string) $pk, $matches)) {
                $ids[(int) $matches[1]] = (int) $matches[1];
            }
        }

        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql          = $this->baseSelect() . " WHERE a.id IN ($placeholders)";
        $stmt         = $this->db->prepare($sql);
        $stmt->execute(array_values($ids));

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $asesor = $this->mapAsesor($row);
            $result[$asesor['pk']] = $asesor;
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(string $q, int $offset = 0, int $limit = 20): array
    {
        $query  = trim($q);
        $offset = max(0, $offset);
        $limit  = max(1, $limit);

        if ($query === '') {
            return array_slice($this->all(), $offset, $limit);
        }

        $needle = '%' . mb_strtolower($query, 'UTF-8') . '%';
        $sql    = $this->baseSelect() . '
                WHERE LOWER(a.nombre_asesor) LIKE :needle
                   OR LOWER(a.email) LIKE :needle
                   OR LOWER(a.celular) LIKE :needle
                ORDER BY LOWER(a.nombre_asesor) ASC
                LIMIT ' . $limit . ' OFFSET ' . $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':needle', $needle, PDO::PARAM_STR);
        $stmt->execute();

        return array_map(fn(array $row): array => $this->mapAsesor($row), (array) $stmt->fetchAll());
    }

    public function searchCount(string $q): int
    {
        $query = trim($q);
        if ($query === '') {
            $row = $this->fetch('SELECT COUNT(*) AS total FROM asesores');
            return (int) ($row['total'] ?? 0);
        }

        $needle = '%' . mb_strtolower($query, 'UTF-8') . '%';
        $sql    = 'SELECT COUNT(*) AS total
                   FROM asesores
                   WHERE LOWER(nombre_asesor) LIKE :needle
                      OR LOWER(email) LIKE :needle
                      OR LOWER(celular) LIKE :needle';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':needle', $needle, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();

        return (int) ($row['total'] ?? 0);
    }

    public function existsByEmailOrPhone(string $email, ?string $celular = null): bool
    {
        $email = mb_strtolower(trim($email), 'UTF-8');
        $cel   = $celular !== null ? trim($celular) : null;

        $conditions = ['LOWER(email) = :email'];
        $params     = [':email' => $email];

        if ($cel !== null && $cel !== '') {
            $conditions[]       = 'celular = :celular';
            $params[':celular'] = $cel;
        }

        $sql = 'SELECT 1 FROM asesores WHERE ' . implode(' OR ', $conditions) . ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchColumn() !== false;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $nombre = TextHelper::titleCase(trim((string) ($data['nombre_asesor'] ?? '')));
        $email  = mb_strtolower(trim((string) ($data['email'] ?? '')), 'UTF-8');
        $cel    = trim((string) ($data['celular'] ?? ''));

        if ($nombre === '' || $email === '') {
            throw new RuntimeException('Nombre y email son obligatorios.');
        }

        if ($this->existsByName($nombre)) {
            throw new RuntimeException('El nombre del asesor ya existe.');
        }

        if ($this->existsByEmail($email)) {
            throw new RuntimeException('El correo electrónico del asesor ya existe.');
        }

        $stmt = $this->db->prepare('INSERT INTO asesores (nombre_asesor, email, celular)
            VALUES (:nombre, :email, :celular)');
        $stmt->bindValue(':nombre', $nombre, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':celular', $cel, PDO::PARAM_STR);
        $stmt->execute();

        return (int) $this->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        $nombre = TextHelper::titleCase(trim((string) ($data['nombre_asesor'] ?? '')));
        $email  = mb_strtolower(trim((string) ($data['email'] ?? '')), 'UTF-8');
        $cel    = trim((string) ($data['celular'] ?? ''));

        if ($nombre === '' || $email === '') {
            throw new RuntimeException('Nombre y email son obligatorios.');
        }

        if ($this->existsByName($nombre, $id)) {
            throw new RuntimeException('El nombre del asesor ya existe.');
        }

        if ($this->existsByEmail($email, $id)) {
            throw new RuntimeException('El correo electrónico del asesor ya existe.');
        }

        $stmt = $this->db->prepare('UPDATE asesores
            SET nombre_asesor = :nombre,
                email         = :email,
                celular       = :celular
            WHERE id = :id');
        $stmt->bindValue(':nombre', $nombre, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':celular', $cel, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        if ($this->hasUsage($id)) {
            return false;
        }

        $stmt = $this->db->prepare('DELETE FROM asesores WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * @return array<int, array{id:int,nombre_asesor:string}>
     */
    public function forSelect(): array
    {
        $rows = $this->fetchAll('SELECT id, nombre_asesor FROM asesores ORDER BY LOWER(nombre_asesor) ASC');

        return array_map(static function (array $row): array {
            return [
                'id'            => (int) ($row['id'] ?? 0),
                'nombre_asesor' => (string) ($row['nombre_asesor'] ?? ''),
            ];
        }, $rows);
    }

    public function existsByName(string $nombre_asesor, ?int $excludeId = null): bool
    {
        $nombre = mb_strtolower(trim($nombre_asesor), 'UTF-8');
        if ($nombre === '') {
            return false;
        }

        $sql    = 'SELECT 1 FROM asesores WHERE LOWER(nombre_asesor) = :nombre';
        $params = [':nombre' => $nombre];

        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude';
            $params[':exclude'] = $excludeId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $type = $key === ':exclude' ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $type);
        }
        $stmt->execute();

        return $stmt->fetchColumn() !== false;
    }

    public function existsByEmail(string $email, ?int $excludeId = null): bool
    {
        $normalized = mb_strtolower(trim($email), 'UTF-8');
        if ($normalized === '') {
            return false;
        }

        $sql    = 'SELECT 1 FROM asesores WHERE LOWER(email) = :email';
        $params = [':email' => $normalized];

        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude';
            $params[':exclude'] = $excludeId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $type = $key === ':exclude' ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $type);
        }
        $stmt->execute();

        return $stmt->fetchColumn() !== false;
    }

    public function hasUsage(int $id): bool
    {
        $checks = [
            'SELECT 1 FROM inquilinos WHERE id_asesor = :id LIMIT 1',
            'SELECT 1 FROM arrendadores WHERE id_asesor = :id LIMIT 1',
            'SELECT 1 FROM inmuebles WHERE id_asesor = :id LIMIT 1',
            'SELECT 1 FROM polizas WHERE id_asesor = :id LIMIT 1',
        ];

        foreach ($checks as $sql) {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->fetchColumn() !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{arrendadores:int,inmuebles:int,polizas:int}
     */
    public function indicadores(int $id): array
    {
        $arrendadores = $this->fetch('SELECT COUNT(*) AS total FROM arrendadores WHERE id_asesor = :id', [':id' => $id]);
        $inmuebles    = $this->fetch('SELECT COUNT(*) AS total FROM inmuebles WHERE id_asesor = :id', [':id' => $id]);
        $polizas      = $this->fetch('SELECT COUNT(*) AS total FROM polizas WHERE id_asesor = :id', [':id' => $id]);

        return [
            'arrendadores' => (int) ($arrendadores['total'] ?? 0),
            'inmuebles'    => (int) ($inmuebles['total'] ?? 0),
            'polizas'      => (int) ($polizas['total'] ?? 0),
        ];
    }

    public function agregarInquilino(int $idAsesor, string $inquilinoPk): void
    {
        // La relación se mantiene en la tabla `inquilinos`, no se requiere acción adicional aquí.
    }

    public function removerInquilino(int $idAsesor, string $inquilinoPk): void
    {
        // La relación se mantiene en la tabla `inquilinos`, no se requiere acción adicional aquí.
    }
}
