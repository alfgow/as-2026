<?php
namespace App\Models;

require_once __DIR__ . '/../Core/Database.php';
use App\Core\Database;
use PDO;

class InmuebleModel extends Database
{
    public function __construct()
    {
        parent::__construct();
    }

    /** Listado completo (ojo: sin paginar) */
    public function obtenerTodos(): array
    {
        $sql = "SELECT i.*, a.nombre_arrendador, s.nombre_asesor
                FROM inmuebles i
                JOIN arrendadores a ON i.id_arrendador = a.id
                JOIN asesores s ON i.id_asesor = s.id
                ORDER BY i.fecha_registro DESC";
        return $this->getConnection()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPaginados(int $limite, int $offset): array
    {
        $sql = "SELECT i.*, a.nombre_arrendador, s.nombre_asesor
                FROM inmuebles i
                JOIN arrendadores a ON i.id_arrendador = a.id
                JOIN asesores s ON i.id_asesor = s.id
                ORDER BY i.fecha_registro DESC
                LIMIT :limite OFFSET :offset";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPaginados(string $query, int $limite, int $offset): array
    {
        $sql = "SELECT i.*, a.nombre_arrendador, s.nombre_asesor
                FROM inmuebles i
                JOIN arrendadores a ON i.id_arrendador = a.id
                JOIN asesores s ON i.id_asesor = s.id
                WHERE i.direccion_inmueble LIKE :q1
                OR a.nombre_arrendador   LIKE :q2
                OR s.nombre_asesor       LIKE :q3
                ORDER BY i.fecha_registro DESC
                LIMIT :limite OFFSET :offset";

        $stmt  = $this->getConnection()->prepare($sql);
        $param = '%' . $query . '%';

        $stmt->bindValue(':q1', $param, PDO::PARAM_STR);
        $stmt->bindValue(':q2', $param, PDO::PARAM_STR);
        $stmt->bindValue(':q3', $param, PDO::PARAM_STR);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarBusqueda(string $query): int
    {
        $sql = "SELECT COUNT(*)
                FROM inmuebles i
                JOIN arrendadores a ON i.id_arrendador = a.id
                JOIN asesores s ON i.id_asesor = s.id
                WHERE i.direccion_inmueble LIKE :q1
                OR a.nombre_arrendador   LIKE :q2
                OR s.nombre_asesor       LIKE :q3";

        $stmt  = $this->getConnection()->prepare($sql);
        $param = '%' . $query . '%';

        $stmt->bindValue(':q1', $param, PDO::PARAM_STR);
        $stmt->bindValue(':q2', $param, PDO::PARAM_STR);
        $stmt->bindValue(':q3', $param, PDO::PARAM_STR);

        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function contarTodos(): int
    {
        return (int) $this->getConnection()->query('SELECT COUNT(*) FROM inmuebles')->fetchColumn();
    }

    public function contarPorArrendador(int $idArrendador): int
    {
        $stmt = $this->getConnection()->prepare('SELECT COUNT(*) FROM inmuebles WHERE id_arrendador = :id');
        $stmt->execute([':id' => $idArrendador]);
        return (int) $stmt->fetchColumn();
    }

    public function obtenerPorId(int $id): ?array
    {
        $sql = "SELECT i.*, a.nombre_arrendador, s.nombre_asesor
                FROM inmuebles i
                JOIN arrendadores a ON i.id_arrendador = a.id
                JOIN asesores s ON i.id_asesor = s.id
                WHERE i.id = :id
                LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function crear(array $data): bool
    {
        $sql = "INSERT INTO inmuebles
                (id_arrendador, id_asesor, direccion_inmueble, tipo, renta, mantenimiento,
                 monto_mantenimiento, deposito, estacionamiento, mascotas, comentarios)
                VALUES
                (:id_arrendador, :id_asesor, :direccion_inmueble, :tipo, :renta, :mantenimiento,
                 :monto_mantenimiento, :deposito, :estacionamiento, :mascotas, :comentarios)";
        $stmt = $this->getConnection()->prepare($sql);
        return $stmt->execute([
            ':id_arrendador'       => $data['id_arrendador'],
            ':id_asesor'           => $data['id_asesor'],
            ':direccion_inmueble'  => $data['direccion_inmueble'],
            ':tipo'                => $data['tipo'],
            ':renta'               => $data['renta'],
            ':mantenimiento'       => $data['mantenimiento'],
            ':monto_mantenimiento' => $data['monto_mantenimiento'],
            ':deposito'            => $data['deposito'],
            ':estacionamiento'     => (int) !empty($data['estacionamiento']),
            ':mascotas'            => $data['mascotas'],
            ':comentarios'         => $data['comentarios'] ?? null,
        ]);
    }

    public function actualizar(int $id, array $data): bool
    {
        $sql = "UPDATE inmuebles SET
                    id_arrendador = :id_arrendador,
                    id_asesor = :id_asesor,
                    direccion_inmueble = :direccion_inmueble,
                    tipo = :tipo,
                    renta = :renta,
                    mantenimiento = :mantenimiento,
                    monto_mantenimiento = :monto_mantenimiento,
                    deposito = :deposito,
                    estacionamiento = :estacionamiento,
                    mascotas = :mascotas,
                    comentarios = :comentarios
                WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        return $stmt->execute([
            ':id_arrendador'       => $data['id_arrendador'],
            ':id_asesor'           => $data['id_asesor'],
            ':direccion_inmueble'  => $data['direccion_inmueble'],
            ':tipo'                => $data['tipo'],
            ':renta'               => $data['renta'],
            ':mantenimiento'       => $data['mantenimiento'],
            ':monto_mantenimiento' => $data['monto_mantenimiento'],
            ':deposito'            => $data['deposito'],
            ':estacionamiento'     => (int) !empty($data['estacionamiento']),
            ':mascotas'            => $data['mascotas'],
            ':comentarios'         => $data['comentarios'] ?? null,
            ':id'                  => $id,
        ]);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->getConnection()->prepare("DELETE FROM inmuebles WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function obtenerPorArrendador(int $idArrendador): array
    {
        $stmt = $this->getConnection()->prepare(
            "SELECT id, direccion_inmueble, renta
             FROM inmuebles
             WHERE id_arrendador = :id
             ORDER BY direccion_inmueble"
        );
        $stmt->execute([':id' => $idArrendador]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* Helpers opcionales de filtrado */
    public function filtrarPorTipo(string $tipo, int $limite = 50, int $offset = 0): array
    {
        $sql = "SELECT i.*, a.nombre_arrendador, s.nombre_asesor
                FROM inmuebles i
                JOIN arrendadores a ON i.id_arrendador = a.id
                JOIN asesores s ON i.id_asesor = s.id
                WHERE i.tipo = :tipo
                ORDER BY i.fecha_registro DESC
                LIMIT :limite OFFSET :offset";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function filtrarPorAsesor(int $idAsesor, int $limite = 50, int $offset = 0): array
    {
        $sql = "SELECT i.*, a.nombre_arrendador, s.nombre_asesor
                FROM inmuebles i
                JOIN arrendadores a ON i.id_arrendador = a.id
                JOIN asesores s ON i.id_asesor = s.id
                WHERE i.id_asesor = :idAsesor
                ORDER BY i.fecha_registro DESC
                LIMIT :limite OFFSET :offset";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':idAsesor', $idAsesor, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}