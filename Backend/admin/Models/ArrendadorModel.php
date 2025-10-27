<?php

declare(strict_types=1);

namespace App\Models;

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Helpers/NormalizadoHelper.php';
require_once __DIR__ . '/../Helpers/SlugHelper.php';
require_once __DIR__ . '/../Helpers/TextHelper.php';
require_once __DIR__ . '/AsesorModel.php';

use App\Core\Database;
use App\Helpers\NormalizadoHelper;
use App\Helpers\SlugHelper;
use App\Helpers\TextHelper;
use RuntimeException;

class ArrendadorModel extends Database
{
    public function __construct()
    {
        parent::__construct();
    }

    private function buildPk(int $id): string
    {
        return 'arr#' . $id;
    }

    private function buildSlug(int $id, ?string $nombre): string
    {
        $nombre = trim((string) $nombre);
        $slugBase = $nombre !== '' ? SlugHelper::fromName($nombre) : 'arrendador';

        return $id . '-' . $slugBase;
    }

    private function buildAsesorPk(?int $idAsesor): ?string
    {
        if ($idAsesor === null || $idAsesor <= 0) {
            return null;
        }

        return 'ase#' . $idAsesor;
    }

    private function mapProfile(array $row): array
    {
        $id = (int)($row['id'] ?? 0);
        if ($id <= 0) {
            throw new RuntimeException('Registro de arrendador inválido.');
        }

        $profile = $row;
        $profile['id'] = $id;
        $profile['pk'] = $this->buildPk($id);
        $profile['sk'] = 'profile';

        $idAsesor = isset($row['id_asesor']) && $row['id_asesor'] !== null
            ? (int)$row['id_asesor']
            : null;

        if ($idAsesor !== null) {
            $profile['id_asesor'] = $idAsesor;
            $profile['asesor']    = $this->buildAsesorPk($idAsesor);
            $profile['asesor_pk'] = $profile['asesor'];
        } else {
            $profile['id_asesor'] = null;
            unset($profile['asesor'], $profile['asesor_pk']);
        }

        if (empty($profile['slug'])) {
            $profile['slug'] = $this->buildSlug($id, $profile['nombre_arrendador'] ?? '');
        }

        return $profile;
    }

    private function hydrateArrendador(array $row): array
    {
        $id = (int)$row['id'];

        return [
            'profile'   => $this->mapProfile($row),
            'archivos'  => $this->obtenerArchivos($id),
            'inmuebles' => $this->obtenerInmuebles($id),
            'polizas'   => $this->obtenerPolizas($id),
        ];
    }

    public function obtenerTodos(): array
    {
        $sql = 'SELECT * FROM arrendadores ORDER BY fecha_registro DESC';
        $rows = $this->fetchAll($sql);

        return array_map(fn(array $row): array => $this->mapProfile($row), $rows);
    }

    public function buscar(string $q): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }

        $needle = '%' . NormalizadoHelper::lower($q) . '%';

        $sql = 'SELECT * FROM arrendadores
                WHERE LOWER(nombre_arrendador) LIKE :needle_nombre
                   OR LOWER(email) LIKE :needle_email
                   OR LOWER(celular) LIKE :needle_celular
                ORDER BY fecha_registro DESC';

        $rows = $this->fetchAll($sql, [
            ':needle_nombre'  => $needle,
            ':needle_email'   => $needle,
            ':needle_celular' => $needle,
        ]);

        return array_map(fn(array $row): array => $this->hydrateArrendador($row), $rows);
    }

    public function obtenerPorId(int $id): ?array
    {
        $sql = 'SELECT * FROM arrendadores WHERE id = :id LIMIT 1';
        $row = $this->fetch($sql, [':id' => $id]);

        return $row ? $this->hydrateArrendador($row) : null;
    }

    public function obtenerPorSlug(string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        $sql = 'SELECT * FROM arrendadores WHERE slug = :slug LIMIT 1';
        $row = $this->fetch($sql, [':slug' => $slug]);

        return $row ? $this->hydrateArrendador($row) : null;
    }

    public function obtenerProfilePorPk(string $pk): ?array
    {
        if (!preg_match('/^arr#(\d+)$/', $pk, $matches)) {
            return null;
        }

        $id = (int)$matches[1];
        $sql = 'SELECT * FROM arrendadores WHERE id = :id LIMIT 1';
        $row = $this->fetch($sql, [':id' => $id]);

        return $row ? $this->mapProfile($row) : null;
    }

    public function obtenerPorAsesor(int $asesorId): array
    {
        $sql = 'SELECT * FROM arrendadores WHERE id_asesor = :asesor ORDER BY nombre_arrendador ASC';
        $rows = $this->fetchAll($sql, [':asesor' => $asesorId]);

        return array_map(fn(array $row): array => $this->mapProfile($row), $rows);
    }

    public function obtenerArchivos(int $idArrendador): array
    {
        $sql = 'SELECT id_archivo, id_arrendador, s3_key, tipo, fecha_subida
                FROM arrendadores_archivos
                WHERE id_arrendador = :id
                ORDER BY fecha_subida DESC';

        $rows = $this->fetchAll($sql, [':id' => $idArrendador]);

        return array_map(function (array $row): array {
            $idArrendador = (int)$row['id_arrendador'];
            $idArchivo    = (int)$row['id_archivo'];

            return [
                'id_archivo'    => $idArchivo,
                'id_arrendador' => $idArrendador,
                's3_key'        => (string)$row['s3_key'],
                'tipo'          => (string)$row['tipo'],
                'fecha_subida'  => $row['fecha_subida'],
                'pk'            => $this->buildPk($idArrendador),
                'sk'            => 'arrfile#' . $idArchivo,
            ];
        }, $rows);
    }

    public function obtenerArchivoPorTipo(int $idArrendador, string $tipo): ?array
    {
        $sql = 'SELECT id_archivo, id_arrendador, s3_key, tipo, fecha_subida
                FROM arrendadores_archivos
                WHERE id_arrendador = :id AND tipo = :tipo
                ORDER BY fecha_subida DESC
                LIMIT 1';

        $row = $this->fetch($sql, [
            ':id'   => $idArrendador,
            ':tipo' => $tipo,
        ]);

        if (!$row) {
            return null;
        }

        $row['id_archivo']    = (int)$row['id_archivo'];
        $row['id_arrendador'] = (int)$row['id_arrendador'];
        $row['pk']            = $this->buildPk($row['id_arrendador']);
        $row['sk']            = 'arrfile#' . $row['id_archivo'];

        return $row;
    }

    public function guardarArchivo(int $idArrendador, string $tipo, string $s3Key): bool
    {
        $tipo = trim($tipo);
        if ($tipo === '') {
            return false;
        }

        $existing = $this->obtenerArchivoPorTipo($idArrendador, $tipo);
        if ($existing) {
            $sql = 'UPDATE arrendadores_archivos
                    SET s3_key = :s3_key, fecha_subida = NOW()
                    WHERE id_archivo = :id';

            $this->execute($sql, [
                ':s3_key' => $s3Key,
                ':id'     => $existing['id_archivo'],
            ]);

            return true;
        }

        $sql = 'INSERT INTO arrendadores_archivos (id_arrendador, s3_key, tipo)
                VALUES (:id_arrendador, :s3_key, :tipo)';

        $this->execute($sql, [
            ':id_arrendador' => $idArrendador,
            ':s3_key'        => $s3Key,
            ':tipo'          => $tipo,
        ]);

        return true;
    }

    public function eliminarArchivo(int $idArrendador, string $tipo): bool
    {
        $sql = 'DELETE FROM arrendadores_archivos WHERE id_arrendador = :id AND tipo = :tipo';
        $this->execute($sql, [
            ':id'   => $idArrendador,
            ':tipo' => $tipo,
        ]);

        return true;
    }

    public function actualizarDatosPersonales(string $pk, array $data): ?string
    {
        if (!preg_match('/^arr#(\d+)$/', $pk, $matches)) {
            return null;
        }

        $id = (int)$matches[1];

        $nombreArrendador    = TextHelper::titleCase((string)($data['nombre_arrendador'] ?? ''));
        $direccionArrendador = TextHelper::titleCase((string)($data['direccion_arrendador'] ?? ''));

        $slug = $this->buildSlug($id, $nombreArrendador);

        $sql = 'UPDATE arrendadores SET
                    nombre_arrendador    = :nombre_arrendador,
                    email                = :email,
                    celular              = :celular,
                    direccion_arrendador = :direccion,
                    estadocivil          = :estadocivil,
                    nacionalidad         = :nacionalidad,
                    rfc                  = :rfc,
                    tipo_id              = :tipo_id,
                    num_id               = :num_id,
                    slug                 = :slug
                WHERE id = :id';

        $this->execute($sql, [
            ':nombre_arrendador' => $nombreArrendador,
            ':email'             => $data['email'] ?? '',
            ':celular'           => $data['celular'] ?? '',
            ':direccion'         => $direccionArrendador,
            ':estadocivil'       => $data['estadocivil'] ?? '',
            ':nacionalidad'      => $data['nacionalidad'] ?? '',
            ':rfc'               => $data['rfc'] ?? '',
            ':tipo_id'           => $data['tipo_id'] ?? '',
            ':num_id'            => $data['num_id'] ?? '',
            ':slug'              => $slug,
            ':id'                => $id,
        ]);

        return $slug;
    }

    public function actualizarInfoBancaria(int $id, array $data): bool
    {
        $sql = 'UPDATE arrendadores SET banco = :banco, cuenta = :cuenta, clabe = :clabe WHERE id = :id';

        $this->execute($sql, [
            ':banco'  => $data['banco'] ?? '',
            ':cuenta' => $data['cuenta'] ?? '',
            ':clabe'  => $data['clabe'] ?? '',
            ':id'     => $id,
        ]);

        return true;
    }

    public function actualizarComentarios(string $pk, string $comentarios): bool
    {
        if (!preg_match('/^arr#(\d+)$/', $pk, $matches)) {
            return false;
        }

        $id = (int)$matches[1];
        $sql = 'UPDATE arrendadores SET comentarios = :comentarios WHERE id = :id';
        $this->execute($sql, [
            ':comentarios' => $comentarios,
            ':id'          => $id,
        ]);

        return true;
    }

    /**
     * @param array<string, mixed> $asesorData
     */
    public function cambiarAsesor(int $idArrendador, array $asesorData): array
    {
        $nuevoId = (int) ($asesorData['id'] ?? 0);
        if ($nuevoId <= 0) {
            throw new RuntimeException('ID de asesor inválido.');
        }

        $stmt = $this->db->prepare('UPDATE arrendadores SET id_asesor = :asesor WHERE id = :id');
        $stmt->execute([
            ':asesor' => $nuevoId,
            ':id'     => $idArrendador,
        ]);

        $asesorModel = new AsesorModel();
        $asesor      = $asesorModel->find($nuevoId);

        if ($asesor === null) {
            $asesor = [
                'id'            => $nuevoId,
                'nombre_asesor' => (string) ($asesorData['nombre_asesor'] ?? ''),
                'email'         => (string) ($asesorData['email'] ?? ''),
                'celular'       => (string) ($asesorData['celular'] ?? ''),
            ];
        }

        if (!isset($asesor['pk'])) {
            $asesor['pk'] = sprintf('ase#%d', (int) ($asesor['id'] ?? $nuevoId));
        }

        return $asesor;
    }

    private function obtenerInmuebles(int $idArrendador): array
    {
        $sql = 'SELECT id, id_arrendador, direccion_inmueble, tipo, renta, mantenimiento,
                       monto_mantenimiento, deposito, estacionamiento, mascotas,
                       comentarios, fecha_registro, id_asesor
                FROM inmuebles
                WHERE id_arrendador = :id
                ORDER BY fecha_registro DESC';

        $rows = $this->fetchAll($sql, [':id' => $idArrendador]);

        return array_map(function (array $row): array {
            $idInmueble   = (int)$row['id'];
            $idArrendador = (int)$row['id_arrendador'];
            $idAsesor     = isset($row['id_asesor']) ? (int)$row['id_asesor'] : null;

            $row['id']            = $idInmueble;
            $row['id_arrendador'] = $idArrendador;
            $row['pk']            = $this->buildPk($idArrendador);
            $row['sk']            = 'inm#' . $idInmueble;
            if ($idAsesor !== null) {
                $row['asesor']    = $this->buildAsesorPk($idAsesor);
                $row['asesor_pk'] = $row['asesor'];
            }

            return $row;
        }, $rows);
    }

    private function obtenerPolizas(int $idArrendador): array
    {
        $sql = 'SELECT id_poliza, numero_poliza, tipo_poliza, vigencia, fecha_poliza
                FROM polizas
                WHERE id_arrendador = :id
                ORDER BY fecha_poliza DESC, id_poliza DESC';

        $rows = $this->fetchAll($sql, [':id' => $idArrendador]);

        return array_map(function (array $row): array {
            $row['id_poliza'] = (int)$row['id_poliza'];
            $row['pk']        = 'pol#' . $row['id_poliza'];
            $row['sk']        = 'profile';
            return $row;
        }, $rows);
    }
}
