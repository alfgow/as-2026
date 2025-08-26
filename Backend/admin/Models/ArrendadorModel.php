<?php
declare(strict_types=1);

namespace App\Models;
require_once __DIR__ . '/../Core/Database.php';
use App\Core\Database;
use PDO;

/**
 * Modelo de Arrendadores.
 *
 * Tablas involucradas:
 *  - arrendadores
 *  - inmuebles (FK lógica id_arrendador)
 *  - polizas (FK lógica id_arrendador)
 *  - arrendadores_archivos (FK explícita a arrendadores.id)
 *
 * Notas:
 *  - El esquema actual NO tiene columna `slug` en arrendadores.
 *  - Este modelo extiende Database y usa $this->db (PDO) con prepares nativos.
 */
class ArrendadorModel extends Database
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Listado de arrendadores (recientes primero).
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodos(): array
    {
        $sql = "SELECT *
                FROM arrendadores
                ORDER BY fecha_registro DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        /** @var array<int, array<string, mixed>> */
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Búsqueda con paginación + métricas agregadas.
     *
     * - num_inmuebles: total distinto de inmuebles vinculados
     * - polizas_activas: cuántas pólizas VIGENTES del arrendador
     * - ultima_poliza: fecha más reciente de póliza
     *
     * @param string $q      Texto a buscar (nombre/email/celular)
     * @param int    $offset Desplazamiento
     * @param int    $limite Límite de filas
     * @return array<int, array<string, mixed>>
     */
    public function buscarConPaginacion(string $q, int $offset, int $limite): array
    {
        $sql = "SELECT a.*,
                       COUNT(DISTINCT i.id)                                            AS num_inmuebles,
                       SUM(CASE WHEN p.estado = 'VIGENTE' THEN 1 ELSE 0 END)          AS polizas_activas,
                       MAX(p.fecha_poliza)                                            AS ultima_poliza
                FROM arrendadores a
                LEFT JOIN inmuebles i ON i.id_arrendador = a.id
                LEFT JOIN polizas   p ON p.id_arrendador = a.id
                WHERE a.nombre_arrendador LIKE ?
                   OR a.email LIKE ?
                   OR a.celular LIKE ?
                GROUP BY a.id
                ORDER BY a.id DESC
                LIMIT ?, ?";

        $stmt   = $this->db->prepare($sql);
        $qParam = '%' . $q . '%';

        $stmt->bindValue(1, $qParam, PDO::PARAM_STR);
        $stmt->bindValue(2, $qParam, PDO::PARAM_STR);
        $stmt->bindValue(3, $qParam, PDO::PARAM_STR);
        $stmt->bindValue(4, $offset, PDO::PARAM_INT);
        $stmt->bindValue(5, $limite, PDO::PARAM_INT);

        $stmt->execute();
        /** @var array<int, array<string, mixed>> */
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cuenta de resultados para la misma búsqueda de buscarConPaginacion (sin agregados).
     */
    public function contarTotalResultados(string $q): int
    {
        $sql = "SELECT COUNT(*)
                FROM arrendadores
                WHERE nombre_arrendador LIKE ?
                   OR email LIKE ?
                   OR celular LIKE ?";

        $stmt  = $this->db->prepare($sql);
        $param = '%' . $q . '%';
        $stmt->execute([$param, $param, $param]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Indicadores generales de arrendadores.
     * - total
     * - con_poliza (al menos 1 póliza vigente)
     * - sin_documentacion (sin archivos en arrendadores_archivos)
     * - nuevos_mes (creados en el mes/año actual)
     *
     * @return array<string, int>
     */
    public function obtenerIndicadores(): array
    {
        $total = (int) $this->db->query("SELECT COUNT(*) FROM arrendadores")->fetchColumn();

        $conPoliza = (int) $this->db->query(
            "SELECT COUNT(DISTINCT id_arrendador)
             FROM polizas
             WHERE estado = 'VIGENTE'"
        )->fetchColumn();

        $sinDocs = (int) $this->db->query(
            "SELECT COUNT(*)
             FROM arrendadores a
             WHERE NOT EXISTS (
               SELECT 1 FROM arrendadores_archivos d WHERE d.id_arrendador = a.id
             )"
        )->fetchColumn();

        $nuevos = (int) $this->db->query(
            "SELECT COUNT(*)
             FROM arrendadores
             WHERE MONTH(fecha_registro) = MONTH(NOW())
               AND YEAR(fecha_registro)  = YEAR(NOW())"
        )->fetchColumn();

        return [
            'total'              => $total,
            'con_poliza'         => $conPoliza,
            'sin_documentacion'  => $sinDocs,
            'nuevos_mes'         => $nuevos,
        ];
    }

    /**
     * Obtiene un arrendador por ID con relaciones básicas.
     *
     * @param int $id
     * @return array<string, mixed>|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM arrendadores WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $arrendador = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($arrendador) {
            $arrendador['inmuebles'] = $this->obtenerInmuebles((int)$arrendador['id']);
            $arrendador['polizas']   = $this->obtenerPolizas((int)$arrendador['id']);
            $arrendador['archivos']  = $this->obtenerArchivos((int)$arrendador['id']);
        }
        return $arrendador;
    }

    /**
     * obtenerPorSlug($slug)
     */
    public function obtenerPorSlug(string $slug): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM arrendadores WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $arrendador = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($arrendador) {
            $arrendador['inmuebles'] = $this->obtenerInmuebles((int)$arrendador['id']);
            $arrendador['polizas']   = $this->obtenerPolizas((int)$arrendador['id']);
            $arrendador['archivos']  = $this->obtenerArchivos((int)$arrendador['id']);
        }
        return $arrendador;
    }

    /**
     * Inmuebles del arrendador.
     * @param int $idArrendador
     * @return array<int, array<string, mixed>>
     */
    private function obtenerInmuebles(int $idArrendador): array
    {
        $stmt = $this->db->prepare("SELECT * FROM inmuebles WHERE id_arrendador = ?");
        $stmt->execute([$idArrendador]);
        /** @var array<int, array<string, mixed>> */
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Pólizas del arrendador.
     * @param int $idArrendador
     * @return array<int, array<string, mixed>>
     */
    private function obtenerPolizas(int $idArrendador): array
    {
        $stmt = $this->db->prepare("SELECT * FROM polizas WHERE id_arrendador = ?");
        $stmt->execute([$idArrendador]);
        /** @var array<int, array<string, mixed>> */
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Archivos del arrendador.
     * @param int $idArrendador
     * @return array<int, array<string, mixed>>
     */
    private function obtenerArchivos(int $idArrendador): array
    {
        $stmt = $this->db->prepare("SELECT * FROM arrendadores_archivos WHERE id_arrendador = ?");
        $stmt->execute([$idArrendador]);
        /** @var array<int, array<string, mixed>> */
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Actualiza campos básicos.
     * @param int   $id
     * @param array<string, mixed> $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE arrendadores SET
                    nombre_arrendador = :nombre_arrendador,
                    email             = :email,
                    celular           = :celular,
                    telefono          = :telefono,
                    rfc               = :rfc
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nombre_arrendador' => (string)($data['nombre_arrendador'] ?? ''),
            ':email'             => (string)($data['email'] ?? ''),
            ':celular'           => (string)($data['celular'] ?? ''),
            ':telefono'          => (string)($data['telefono'] ?? ''),
            ':rfc'               => (string)($data['rfc'] ?? ''),
            ':id'                => $id,
        ]);
    }

    /**
     * Actualiza datos personales ampliados.
     * @param int   $id
     * @param array<string, mixed> $data
     * @return bool
     */
    public function actualizarDatosPersonales(int $id, array $data): bool
    {
        $sql = "UPDATE arrendadores SET
                    nombre_arrendador    = :nombre_arrendador,
                    email                = :email,
                    celular              = :celular,
                    telefono             = :telefono,
                    direccion_arrendador = :direccion_arrendador,
                    estadocivil          = :estadocivil,
                    nacionalidad         = :nacionalidad,
                    rfc                  = :rfc,
                    tipo_id              = :tipo_id,
                    num_id               = :num_id
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nombre_arrendador'    => (string)($data['nombre_arrendador'] ?? ''),
            ':email'                => (string)($data['email'] ?? ''),
            ':celular'              => (string)($data['celular'] ?? ''),
            ':telefono'             => (string)($data['telefono'] ?? ''),
            ':direccion_arrendador' => (string)($data['direccion_arrendador'] ?? ''),
            ':estadocivil'          => (string)($data['estadocivil'] ?? ''),
            ':nacionalidad'         => (string)($data['nacionalidad'] ?? ''),
            ':rfc'                  => (string)($data['rfc'] ?? ''),
            ':tipo_id'              => (string)($data['tipo_id'] ?? ''),
            ':num_id'               => (string)($data['num_id'] ?? ''),
            ':id'                   => $id,
        ]);
    }

    /**
     * Actualiza información bancaria.
     */
    public function actualizarInfoBancaria(int $id, array $data): bool
    {
        $sql = "UPDATE arrendadores
                SET banco  = :banco,
                    cuenta = :cuenta,
                    clabe  = :clabe
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':banco'  => (string)($data['banco'] ?? ''),
            ':cuenta' => (string)($data['cuenta'] ?? ''),
            ':clabe'  => (string)($data['clabe'] ?? ''),
            ':id'     => $id,
        ]);
    }

    /**
     * Actualiza comentarios libres.
     */
    public function actualizarComentarios(int $id, string $comentarios): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE arrendadores SET comentarios = :comentarios WHERE id = :id"
        );
        return $stmt->execute([
            ':comentarios' => $comentarios,
            ':id'          => $id,
        ]);
    }

    /**
     * ¿Tiene al menos una póliza activa?
     */
    public function tienePolizasActivas(int $id): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM polizas
             WHERE id_arrendador = ? AND estado = 'VIGENTE'"
        );
        $stmt->execute([$id]);
        return ((int)$stmt->fetchColumn()) > 0;
    }

    /**
     * Arrendadores por asesor.
     * @param int $idAsesor
     * @return array<int, array{id:int, nombre_arrendador:string}>
     */
    public function obtenerPorAsesor(int $idAsesor): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, nombre_arrendador
             FROM arrendadores
             WHERE id_asesor = ?
             ORDER BY nombre_arrendador"
        );
        $stmt->execute([$idAsesor]);
        /** @var array<int, array{id:int, nombre_arrendador:string}> */
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =================
       Métodos opcionales
       ================= */

    /**
     * Crea un arrendador mínimo.
     * @param array<string, mixed> $data
     * @return int ID insertado
     */
    public function crear(array $data): int
    {
        $sql = "INSERT INTO arrendadores
                   (nombre_arrendador, email, celular, telefono, id_asesor, fecha_registro)
                VALUES
                   (:nombre_arrendador, :email, :celular, :telefono, :id_asesor, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre_arrendador' => (string)($data['nombre_arrendador'] ?? ''),
            ':email'             => (string)($data['email'] ?? ''),
            ':celular'           => (string)($data['celular'] ?? ''),
            ':telefono'          => (string)($data['telefono'] ?? ''),
            ':id_asesor'         => (int)($data['id_asesor'] ?? 0),
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Elimina un arrendador por ID.
     * (En cascada se borran sus archivos si existen FK, y lógicamente
     * quedarán inmuebles/pólizas si no hay FK; maneja esto en tu capa de servicio.)
     */
    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM arrendadores WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
