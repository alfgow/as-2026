<?php

declare(strict_types=1);

namespace App\Models;

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Helpers/TextHelper.php';

use App\Core\Database;
use App\Helpers\TextHelper;
use InvalidArgumentException;
use PDO;

class InmuebleModel extends Database
{
    private const INMUEBLE_SK_PREFIX = 'INM#';
    private const ARRENDADOR_PK_PREFIX = 'arr#';

    /** @var array<int, array<string, mixed>>|null */
    private ?array $inmueblesCache = null;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Obtiene todos los inmuebles ordenados por fecha de registro descendente.
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodos(): array
    {
        return $this->getInmuebles();
    }

    /**
     * Busca inmuebles utilizando coincidencias parciales en dirección, arrendador, asesor o tipo.
     *
     * @param string $query
     * @param int    $limite
     *
     * @return array{items: array<int, array<string, mixed>>, consumedCapacity: float}
     */
    public function buscarPorDireccion(string $query, int $limite = 30): array
    {
        $limite = max(1, $limite);
        $tokens = $this->tokenizarBusqueda($query);

        if ($tokens === []) {
            return ['items' => [], 'consumedCapacity' => 0.0];
        }

        $items = [];
        foreach ($this->getInmuebles() as $inmueble) {
            if (!$this->coincideBusquedaTokens($inmueble, $tokens)) {
                continue;
            }

            $items[] = $inmueble;
            if (count($items) >= $limite) {
                break;
            }
        }

        return [
            'items'            => $items,
            'consumedCapacity' => 0.0,
        ];
    }

    public function contarPorArrendador(int|string $idArrendador): int
    {
        $id = $this->parseArrendadorId($idArrendador);
        if ($id === null) {
            return 0;
        }

        $sql  = 'SELECT COUNT(*) AS total FROM inmuebles WHERE id_arrendador = :id';
        $row  = $this->fetch($sql, [':id' => $id]);

        return (int) ($row['total'] ?? 0);
    }

    public function obtenerPorId(int|string $pk, ?string $sk = null): ?array
    {
        if ($sk === null && is_string($pk) && str_contains($pk, '|')) {
            [$pk, $sk] = explode('|', $pk, 2);
        }

        if ($sk === null) {
            if (is_numeric($pk)) {
                return $this->obtenerPorLegacyId((int) $pk);
            }

            throw new InvalidArgumentException('Se requieren pk y sk del inmueble.');
        }

        $arrendadorId = $this->parseArrendadorId($pk);
        $inmuebleId   = $this->parseInmuebleSk($sk);

        if ($inmuebleId === null) {
            throw new InvalidArgumentException('Identificador de inmueble inválido.');
        }

        $inmueble = $this->obtenerPorLegacyId($inmuebleId);
        if ($inmueble === null) {
            return null;
        }

        if ($arrendadorId !== null && (int) ($inmueble['id_arrendador'] ?? 0) !== $arrendadorId) {
            return null;
        }

        return $inmueble;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getInmuebles(): array
    {
        if ($this->inmueblesCache !== null) {
            return $this->inmueblesCache;
        }

        $sql  = $this->baseSelect() . ' ORDER BY i.fecha_registro DESC';
        $rows = $this->fetchAll($sql);

        $items = array_map(fn(array $row): array => $this->mapInmueble($row), $rows);

        return $this->inmueblesCache = array_values($items);
    }

    /**
     * @param array<string, mixed> $item
     *
     * @return array<string, mixed>
     */
    private function mapInmueble(array $item): array
    {
        $id            = (int) ($item['id'] ?? 0);
        $idArrendador  = (int) ($item['id_arrendador'] ?? 0);
        $idAsesor      = isset($item['id_asesor']) ? (int) $item['id_asesor'] : null;
        $pk            = $idArrendador > 0 ? self::ARRENDADOR_PK_PREFIX . $idArrendador : '';
        $sk            = $id > 0 ? self::INMUEBLE_SK_PREFIX . $id : '';
        $mantenimiento = $this->normalizarMantenimiento((string) ($item['mantenimiento'] ?? 'NO'));
        $mascotas      = strtoupper((string) ($item['mascotas'] ?? 'NO'));

        $inmueble = [
            'id'                  => $id,
            'pk'                  => $pk,
            'sk'                  => $sk,
            'id_virtual'          => ($pk !== '' && $sk !== '') ? $pk . '|' . $sk : null,
            'id_arrendador'       => $idArrendador,
            'id_asesor'           => $idAsesor,
            'tipo'                => (string) ($item['tipo'] ?? ''),
            'direccion_inmueble'  => (string) ($item['direccion_inmueble'] ?? ''),
            'renta'               => $this->formatearMonto($item['renta'] ?? null),
            'mantenimiento'       => $mantenimiento,
            'monto_mantenimiento' => $this->formatearMonto($item['monto_mantenimiento'] ?? null),
            'deposito'            => $this->formatearMonto($item['deposito'] ?? null),
            'estacionamiento'     => (int) ($item['estacionamiento'] ?? 0),
            'mascotas'            => $mascotas === 'SI' ? 'SI' : 'NO',
            'comentarios'         => (string) ($item['comentarios'] ?? ''),
            'fecha_registro'      => $item['fecha_registro'] ?? null,
            'nombre_arrendador'   => (string) ($item['nombre_arrendador'] ?? ''),
            'nombre_asesor'       => (string) ($item['nombre_asesor'] ?? ''),
            'asesor_pk'           => $idAsesor ? ('ase#' . $idAsesor) : null,
        ];

        return $inmueble;
    }

    private function normalizarMantenimiento(string $valor): string
    {
        $normalizado = strtoupper(str_replace(' ', '_', trim($valor)));

        switch ($normalizado) {
            case 'SI':
                return 'Si';
            case 'NO':
                return 'No';
            case 'NO_APLICA':
            case 'NA':
                return 'na';
            default:
                return 'No';
        }
    }

    /**
     * @param array<string, mixed> $inmueble
     * @param array<int, string>   $tokens
     */
    private function coincideBusquedaTokens(array $inmueble, array $tokens): bool
    {
        if ($tokens === []) {
            return true;
        }

        $campos = [
            $this->normalizarCampoBusqueda($inmueble['direccion_inmueble'] ?? ''),
            $this->normalizarCampoBusqueda($inmueble['nombre_arrendador'] ?? ''),
            $this->normalizarCampoBusqueda($inmueble['nombre_asesor'] ?? ''),
            $this->normalizarCampoBusqueda($inmueble['tipo'] ?? ''),
        ];

        $haystack = trim(implode(' ', array_filter($campos)));
        if ($haystack === '') {
            return false;
        }

        foreach ($tokens as $token) {
            if ($token === '') {
                continue;
            }

            if (mb_strpos($haystack, $token, 0, 'UTF-8') === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, string>
     */
    private function tokenizarBusqueda(string $query): array
    {
        $normalizado = $this->normalizarBusqueda($query);
        if ($normalizado === '') {
            return [];
        }

        $tokens = preg_split('/\s+/u', $normalizado) ?: [];

        return array_values(array_filter(array_map('trim', $tokens), static function ($token): bool {
            return $token !== '';
        }));
    }

    private function normalizarCampoBusqueda(mixed $valor): string
    {
        if ($valor === null) {
            return '';
        }

        return $this->normalizarBusqueda((string) $valor);
    }

    private function normalizarBusqueda(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');

        $replacements = [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n',
        ];

        $texto = strtr($texto, $replacements);
        $texto = str_replace(['col.', 'colonia'], 'colonia', $texto);
        $texto = preg_replace('/\bcol\b/u', 'colonia', $texto) ?? $texto;
        $texto = preg_replace('/[^a-z0-9\s]/u', ' ', $texto) ?? $texto;
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;

        return trim($texto);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function obtenerPorArrendador(int|string $idArrendador): array
    {
        $id = $this->parseArrendadorId($idArrendador);
        if ($id === null) {
            return [];
        }

        $sql  = $this->baseSelect() . ' WHERE i.id_arrendador = :id ORDER BY LOWER(i.direccion_inmueble) ASC';
        $rows = $this->fetchAll($sql, [':id' => $id]);

        return array_map(fn(array $row): array => $this->mapInmueble($row), $rows);
    }

    public function obtenerIdPorLlaves(string $pk, string $sk): ?int
    {
        $arrendadorId = $this->parseArrendadorId($pk);
        $inmuebleId   = $this->parseInmuebleSk($sk);

        if ($inmuebleId === null) {
            return null;
        }

        $inmueble = $this->obtenerPorLegacyId($inmuebleId);
        if ($inmueble === null) {
            return null;
        }

        if ($arrendadorId !== null && (int) ($inmueble['id_arrendador'] ?? 0) !== $arrendadorId) {
            return null;
        }

        return $inmueble['id'] ?? null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function crear(array $data): bool
    {
        $sql = 'INSERT INTO inmuebles (
                    id_arrendador,
                    id_asesor,
                    direccion_inmueble,
                    tipo,
                    renta,
                    mantenimiento,
                    monto_mantenimiento,
                    deposito,
                    estacionamiento,
                    mascotas,
                    comentarios
                ) VALUES (
                    :id_arrendador,
                    :id_asesor,
                    :direccion,
                    :tipo,
                    :renta,
                    :mantenimiento,
                    :monto_mantenimiento,
                    :deposito,
                    :estacionamiento,
                    :mascotas,
                    :comentarios
                )';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_arrendador', $data['id_arrendador'], PDO::PARAM_INT);
        if (isset($data['id_asesor']) && $data['id_asesor'] !== null) {
            $stmt->bindValue(':id_asesor', $data['id_asesor'], PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':id_asesor', null, PDO::PARAM_NULL);
        }
        $direccion = TextHelper::titleCase((string) ($data['direccion_inmueble'] ?? ''));
        $stmt->bindValue(':direccion', $direccion);
        $stmt->bindValue(':tipo', (string) $data['tipo']);
        $stmt->bindValue(':renta', (string) $data['renta']);
        $stmt->bindValue(':mantenimiento', (string) $data['mantenimiento']);
        $stmt->bindValue(':monto_mantenimiento', (string) $data['monto_mantenimiento']);
        $stmt->bindValue(':deposito', (string) $data['deposito']);
        $stmt->bindValue(':estacionamiento', (int) $data['estacionamiento'], PDO::PARAM_INT);
        $stmt->bindValue(':mascotas', (string) $data['mascotas']);
        $stmt->bindValue(':comentarios', (string) ($data['comentarios'] ?? ''));

        $ok = $stmt->execute();

        if ($ok) {
            $this->inmueblesCache = null;
        }

        return (bool) $ok;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function actualizarPorId(int $id, array $data): bool
    {
        $sql = 'UPDATE inmuebles SET
                    id_arrendador = :id_arrendador,
                    id_asesor = :id_asesor,
                    direccion_inmueble = :direccion,
                    tipo = :tipo,
                    renta = :renta,
                    mantenimiento = :mantenimiento,
                    monto_mantenimiento = :monto_mantenimiento,
                    deposito = :deposito,
                    estacionamiento = :estacionamiento,
                    mascotas = :mascotas,
                    comentarios = :comentarios
                WHERE id = :id';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_arrendador', $data['id_arrendador'], PDO::PARAM_INT);
        if (isset($data['id_asesor']) && $data['id_asesor'] !== null) {
            $stmt->bindValue(':id_asesor', $data['id_asesor'], PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':id_asesor', null, PDO::PARAM_NULL);
        }
        $direccion = TextHelper::titleCase((string) ($data['direccion_inmueble'] ?? ''));
        $stmt->bindValue(':direccion', $direccion);
        $stmt->bindValue(':tipo', (string) $data['tipo']);
        $stmt->bindValue(':renta', (string) $data['renta']);
        $stmt->bindValue(':mantenimiento', (string) $data['mantenimiento']);
        $stmt->bindValue(':monto_mantenimiento', (string) $data['monto_mantenimiento']);
        $stmt->bindValue(':deposito', (string) $data['deposito']);
        $stmt->bindValue(':estacionamiento', (int) $data['estacionamiento'], PDO::PARAM_INT);
        $stmt->bindValue(':mascotas', (string) $data['mascotas']);
        $stmt->bindValue(':comentarios', (string) ($data['comentarios'] ?? ''));
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $ok = $stmt->execute();

        if ($ok) {
            $this->inmueblesCache = null;
        }

        return (bool) $ok;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function actualizarPorPkSk(string $pk, string $sk, array $data): bool
    {
        $id = $this->obtenerIdPorLlaves($pk, $sk);
        if ($id === null) {
            return false;
        }

        return $this->actualizarPorId($id, $data);
    }

    public function eliminar(string $pk, string $sk): bool
    {
        $id = $this->obtenerIdPorLlaves($pk, $sk);
        if ($id === null) {
            return false;
        }

        $stmt = $this->db->prepare('DELETE FROM inmuebles WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $ok = $stmt->execute();

        if ($ok) {
            $this->inmueblesCache = null;
        }

        return (bool) $ok;
    }

    /* Helpers opcionales de filtrado */
    public function filtrarPorTipo(string $tipo, int $limite = 50, int $offset = 0): array
    {
        $needle = mb_strtolower(trim($tipo), 'UTF-8');

        if ($needle === '') {
            return [];
        }

        $filtered = array_values(array_filter(
            $this->getInmuebles(),
            static function (array $inmueble) use ($needle): bool {
                $valor = mb_strtolower((string)($inmueble['tipo'] ?? ''), 'UTF-8');

                return $valor === $needle;
            }
        ));

        return array_slice($filtered, $offset, $limite);
    }

    public function filtrarPorAsesor(int $idAsesor, int $limite = 50, int $offset = 0): array
    {
        $needleInt = (int) $idAsesor;
        $needlePk  = 'ase#' . $needleInt;

        $filtered = array_values(array_filter(
            $this->getInmuebles(),
            static function (array $inmueble) use ($needleInt, $needlePk): bool {
                if (isset($inmueble['id_asesor']) && (int) $inmueble['id_asesor'] === $needleInt) {
                    return true;
                }

                $asesorPk = (string)($inmueble['asesor_pk'] ?? '');

                return $asesorPk !== '' && strcasecmp($asesorPk, $needlePk) === 0;
            }
        ));

        return array_slice($filtered, $offset, $limite);
    }

    private function formatearMonto(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '0.00';
        }

        if (is_numeric($valor)) {
            return number_format((float) $valor, 2, '.', '');
        }

        $texto = trim((string) $valor);
        $texto = str_replace(['$', ' '], '', $texto);
        if (preg_match('/^\d{1,3}(\.\d{3})+,\d{2}$/', $texto)) {
            $texto = str_replace('.', '', $texto);
            $texto = str_replace(',', '.', $texto);
        } else {
            $texto = str_replace(',', '', $texto);
        }

        if (!str_contains($texto, '.')) {
            $texto .= '.00';
        } else {
            $parts = explode('.', $texto, 2);
            $dec   = substr($parts[1] . '00', 0, 2);
            $texto = $parts[0] . '.' . $dec;
        }

        if (!preg_match('/^\d+(\.\d{2})$/', $texto)) {
            return '0.00';
        }

        return $texto;
    }

    private function obtenerPorLegacyId(int $legacyId): ?array
    {
        $sql = $this->baseSelect() . ' WHERE i.id = :id LIMIT 1';
        $row = $this->fetch($sql, [':id' => $legacyId]);

        return $row ? $this->mapInmueble($row) : null;
    }

    private function baseSelect(): string
    {
        return 'SELECT
                    i.id,
                    i.id_arrendador,
                    i.id_asesor,
                    i.direccion_inmueble,
                    i.tipo,
                    i.renta,
                    i.mantenimiento,
                    i.monto_mantenimiento,
                    i.deposito,
                    i.estacionamiento,
                    i.mascotas,
                    i.comentarios,
                    i.fecha_registro,
                    arr.nombre_arrendador,
                    ase.nombre_asesor
                FROM inmuebles i
                INNER JOIN arrendadores arr ON arr.id = i.id_arrendador
                LEFT JOIN asesores ase ON ase.id = i.id_asesor';
    }

    private function parseArrendadorId(int|string|null $valor): ?int
    {
        if ($valor === null) {
            return null;
        }

        if (is_int($valor)) {
            return $valor > 0 ? $valor : null;
        }

        $trimmed = trim((string) $valor);
        if ($trimmed === '') {
            return null;
        }

        if (ctype_digit($trimmed)) {
            $int = (int) $trimmed;

            return $int > 0 ? $int : null;
        }

        if (preg_match('/^arr#(\d+)$/i', $trimmed, $matches)) {
            $int = (int) $matches[1];

            return $int > 0 ? $int : null;
        }

        return null;
    }

    private function parseInmuebleSk(?string $sk): ?int
    {
        if ($sk === null) {
            return null;
        }

        $trimmed = trim($sk);
        if ($trimmed === '') {
            return null;
        }

        if (ctype_digit($trimmed)) {
            $int = (int) $trimmed;

            return $int > 0 ? $int : null;
        }

        if (preg_match('/^INM#(\d+)$/i', $trimmed, $matches)) {
            $int = (int) $matches[1];

            return $int > 0 ? $int : null;
        }

        return null;
    }

}
