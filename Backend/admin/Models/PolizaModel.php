<?php

namespace App\Models;

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Helpers/TextHelper.php';

use App\Core\Database;
use App\Helpers\NormalizadoHelper;
use App\Helpers\TextHelper;
use PDO;

/**
 * PolizaModel
 *
 * - Todas las personas (inquilino, fiador, obligado) se obtienen de la MISMA tabla `inquilinos`.
 * - Las direcciones de cada rol se obtienen de `inquilinos_direccion` por su respectivo id.
 * - No se usan tablas o columnas *_2025 ni la tabla `fiadores`.
 * - Consulta base reutilizable con WHERE dinámico y extras (ORDER/LIMIT).
 */
class PolizaModel extends Database
{
    private const ARRENDADOR_PK_PREFIX = 'arr#';
    private const INMUEBLE_SK_PREFIX   = 'INM#';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param array<int, array<string, mixed>> $polizas
     * @return array<int, array<string, mixed>>
     */
    private function adjuntarInmueblesDesdeMysql(array $polizas): array
    {
        if ($polizas === []) {
            return [];
        }

        $ids = [];
        foreach ($polizas as $poliza) {
            $id = isset($poliza['id_inmueble']) ? (int) $poliza['id_inmueble'] : 0;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        $inmuebles = $ids === []
            ? []
            : $this->obtenerInmueblesPorIds(array_values($ids));

        $defaults = [
            'direccion_inmueble'       => 'SIN DIRECCIÓN',
            'estacionamiento_inmueble' => 0,
            'monto_mantenimiento'      => '0.00',
            'mascotas_inmueble'        => 'NO',
            'mantenimiento_inmueble'   => '',
        ];

        foreach ($polizas as &$poliza) {
            foreach ($defaults as $campo => $valor) {
                $poliza[$campo] = $valor;
            }

            $poliza['inmueble_pk'] = '';
            $poliza['inmueble_sk'] = '';

            $idInmueble = isset($poliza['id_inmueble']) ? (int) $poliza['id_inmueble'] : 0;

            if ($idInmueble > 0 && isset($inmuebles[$idInmueble])) {
                $info = $inmuebles[$idInmueble];

                $poliza['direccion_inmueble'] = trim((string)($info['direccion_inmueble'] ?? '')) ?: $defaults['direccion_inmueble'];
                $poliza['estacionamiento_inmueble'] = (int)($info['estacionamiento'] ?? $defaults['estacionamiento_inmueble']);
                $poliza['monto_mantenimiento'] = $this->formatearMontoInmueble($info['monto_mantenimiento'] ?? $defaults['monto_mantenimiento']);

                $mascotas = strtoupper((string)($info['mascotas'] ?? ''));
                $poliza['mascotas_inmueble'] = $mascotas !== '' ? $mascotas : $defaults['mascotas_inmueble'];

                $poliza['mantenimiento_inmueble'] = (string)($info['mantenimiento'] ?? $defaults['mantenimiento_inmueble']);

                $idArrendador = isset($poliza['id_arrendador']) ? (int)$poliza['id_arrendador'] : 0;
                if ($idArrendador <= 0) {
                    $idArrendador = isset($info['id_arrendador']) ? (int)$info['id_arrendador'] : 0;
                }

                if ($idArrendador > 0) {
                    $poliza['inmueble_pk'] = self::ARRENDADOR_PK_PREFIX . $idArrendador;
                }

                $poliza['inmueble_sk'] = self::INMUEBLE_SK_PREFIX . $idInmueble;
            }
        }
        unset($poliza);

        return $polizas;
    }

    /**
     * @param array<int, array<string, mixed>> $polizas
     * @return array<int, array<string, mixed>>
     */
    private function inicializarDatosGarantiaFiador(array $polizas): array
    {
        if ($polizas === []) {
            return [];
        }

        $defaults = [
            'fiador_calle_garantia'       => '',
            'fiador_num_ext_garantia'     => '',
            'fiador_num_int_garantia'     => '',
            'fiador_colonia_garantia'     => '',
            'fiador_alcaldia_garantia'    => '',
            'fiador_estado_garantia'      => '',
            'fiador_cp_garantia'          => '',
            'fiador_numero_escritura'     => '',
            'fiador_fecha_escritura'      => '',
            'fiador_numero_notario'       => '',
            'fiador_nombre_notario'       => '',
            'fiador_estado_notario'       => '',
            'fiador_folio_real'           => '',
        ];

        foreach ($polizas as &$poliza) {
            foreach ($defaults as $campo => $valor) {
                if (!array_key_exists($campo, $poliza) || $poliza[$campo] === null) {
                    $poliza[$campo] = $valor;
                }
            }
        }
        unset($poliza);

        return $polizas;
    }

    /**
     * @param array<int, int> $ids
     * @return array<int, array<string, mixed>>
     */
    private function obtenerInmueblesPorIds(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if ($ids === []) {
            return [];
        }

        $placeholders = [];
        $params = [];

        foreach ($ids as $idx => $id) {
            $placeholder = ':id' . $idx;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $id;
        }

        $sql = 'SELECT
                    i.id,
                    i.id_arrendador,
                    i.direccion_inmueble,
                    i.estacionamiento,
                    i.monto_mantenimiento,
                    i.mantenimiento,
                    i.mascotas
                FROM inmuebles i
                WHERE i.id IN (' . implode(', ', $placeholders) . ')';

        $rows = $this->fetchAll($sql, $params);

        $map = [];

        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $map[$id] = [
                'id'                 => $id,
                'id_arrendador'      => isset($row['id_arrendador']) ? (int)$row['id_arrendador'] : 0,
                'direccion_inmueble' => $row['direccion_inmueble'] ?? '',
                'estacionamiento'    => isset($row['estacionamiento']) ? (int)$row['estacionamiento'] : 0,
                'monto_mantenimiento' => $row['monto_mantenimiento'] ?? null,
                'mantenimiento'      => $row['mantenimiento'] ?? '',
                'mascotas'           => $row['mascotas'] ?? '',
            ];
        }

        return $map;
    }

    private function formatearMontoInmueble(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '0.00';
        }

        if (is_numeric($valor)) {
            return number_format((float) $valor, 2, '.', '');
        }

        return (string) $valor;
    }

    /**
     * @param array<int, array<string, mixed>> $polizas
     * @return array<int, array<string, mixed>>
     */
    private function filtrarPolizasPorBusqueda(array $polizas, string $termino): array
    {
        $termino = trim($termino);
        if ($termino === '') {
            return $polizas;
        }

        $needle    = NormalizadoHelper::normalizarBusqueda($termino);
        $esNumero  = ctype_digit($termino);

        return array_values(array_filter($polizas, static function (array $poliza) use ($needle, $esNumero, $termino): bool {
            if ($esNumero && (string)($poliza['numero_poliza'] ?? '') === $termino) {
                return true;
            }

            $campos = [
                'nombre_inquilino',
                'apellidop_inquilino',
                'apellidom_inquilino',
                'nombre_inquilino_completo',
                'nombre_arrendador',
                'direccion_inmueble',
            ];

            foreach ($campos as $campo) {
                $valor = NormalizadoHelper::normalizarBusqueda((string)($poliza[$campo] ?? ''));
                if ($valor !== '' && mb_strpos($valor, $needle) !== false) {
                    return true;
                }
            }

            return false;
        }));
    }

    /**
     * Arma y ejecuta una consulta completa de pólizas con sus relaciones,
     * aplicando condiciones dinámicas.
     *
     * @param string $condiciones  Texto del WHERE (sin incluir la palabra WHERE si pones '1', por ejemplo).
     * @param array  $parametros   [':placeholder' => valor] que aparecen en $condiciones o $extras.
     * @param bool   $soloUna      true para devolver solo una fila (o null), false para todas.
     * @param string $extras       Sufijo de SQL (ORDER BY / LIMIT / OFFSET, etc.).
     *
     * @return array|null          array asociativo (una fila) si $soloUna, array de filas si no, o null si no hay.
     */
    private function obtenerPolizasConFiltros(
        string $condiciones,
        array $parametros = [],
        bool $soloUna = false,
        string $extras = ''
    ): array|null {
        // Consulta base (TODOS los roles salen de `inquilinos`; direcciones de `inquilinos_direccion`)
        $sqlBase = '
        SELECT
            p.id_poliza,
            p.tipo_poliza,
            p.id_asesor,
            p.id_arrendador,
            p.id_inquilino,
            p.id_obligado,
            p.id_fiador,
            p.id_inmueble,
            p.tipo_inmueble,
            p.monto_renta,
            p.monto_poliza,
            p.estado,
            p.vigencia,
            p.mes_vencimiento,
            p.year_vencimiento,
            p.usuario,
            p.serie_poliza,
            p.numero_poliza,
            p.fecha_poliza,
            p.fecha_fin,
            p.periodo,
            p.comentarios,
            \'SIN DIRECCIÓN\' AS direccion_inmueble,
            0 AS estacionamiento_inmueble,
            \'0.00\' AS monto_mantenimiento,
            \'NO\' AS mascotas_inmueble,
            \'\' AS mantenimiento_inmueble,
            a.nombre_asesor AS nombre_asesor,
            a.celular AS celular_asesor,
            arr.nombre_arrendador AS nombre_arrendador,
            arr.slug AS slug_arrendador,
            arr.celular AS celular_arrendador,
            arr.direccion_arrendador,
            arr.banco AS banco_arrendador,
            arr.cuenta AS cuenta_arrendador,
            arr.clabe AS clabe_arrendador,
            arr.tipo_id AS tipo_id_arrendador,
            arr.num_id AS num_id_arrendador,
            arr.nacionalidad AS nacionalidad_arrendador,
            i.nombre_inquilino AS nombre_inquilino,
            i.apellidop_inquilino AS apellidop_inquilino,
            i.apellidom_inquilino AS apellidom_inquilino,
            i.celular AS celular_inquilino,
            i.tipo_id AS tipo_id_inquilino,
            i.num_id AS num_id_inquilino,
            i.slug AS slug_inquilino,
            i.nacionalidad AS nacionalidad_inquilino,
            CONCAT_WS(" ", i.nombre_inquilino, i.apellidop_inquilino, i.apellidom_inquilino) AS nombre_inquilino_completo,
            CONCAT_WS("",
              TRIM(NULLIF(di.calle, "")),
              TRIM(NULLIF(di.num_exterior, "")),
              IF(di.num_interior IS NOT NULL AND di.num_interior <> "", CONCAT(" Int. ", TRIM(di.num_interior)), NULL),
              IFNULL(CONCAT(" Col. ", TRIM(di.colonia)), ""),
              IFNULL(CONCAT(" Alcaldía ", TRIM(di.alcaldia)), ""),
              IFNULL(CONCAT(" ", TRIM(di.ciudad)), ""),
              IFNULL(CONCAT(" C.P. ", TRIM(di.codigo_postal)), "")
            ) AS direccion_inquilino,
            f.nombre_inquilino     AS nombre_fiador,
            f.apellidop_inquilino  AS apellidop_fiador,
            f.apellidom_inquilino  AS apellidom_fiador,
            f.tipo_id AS tipo_id_fiador,
            f.num_id AS num_id_fiador,
            f.slug AS slug_fiador,
            f.nacionalidad AS nacionalidad_fiador,
            CONCAT_WS(" ", f.nombre_inquilino, f.apellidop_inquilino, f.apellidom_inquilino) AS nombre_fiador_completo,
            CONCAT_WS("",
              TRIM(NULLIF(df.calle, "")),
              TRIM(NULLIF(df.num_exterior, "")),
              IF(df.num_interior IS NOT NULL AND df.num_interior <> "", CONCAT(" Int. ", TRIM(df.num_interior)), NULL),
              IFNULL(CONCAT(" Col. ", TRIM(df.colonia)), ""),
              IFNULL(CONCAT(" Alcaldía ", TRIM(df.alcaldia)), ""),
              IFNULL(CONCAT(" ", TRIM(df.ciudad)), ""),
              IFNULL(CONCAT(" C.P. ", TRIM(df.codigo_postal)), "")
            ) AS direccion_fiador,
            fg.calle_inmueble      AS fiador_calle_garantia,
            fg.num_ext_inmueble    AS fiador_num_ext_garantia,
            fg.num_int_inmueble    AS fiador_num_int_garantia,
            fg.colonia_inmueble    AS fiador_colonia_garantia,
            fg.alcaldia_inmueble   AS fiador_alcaldia_garantia,
            fg.estado_inmueble     AS fiador_estado_garantia,
            fg.cp_inmueble         AS fiador_cp_garantia,
            fg.numero_escritura    AS fiador_numero_escritura,
            fg.fecha_escritura     AS fiador_fecha_escritura,
            fg.numero_notario      AS fiador_numero_notario,
            fg.nombre_notario      AS fiador_nombre_notario,
            fg.estado_notario      AS fiador_estado_notario,
            fg.folio_real          AS fiador_folio_real,
            o.nombre_inquilino     AS nombre_obligado,
            o.apellidop_inquilino  AS apellidop_obligado,
            o.apellidom_inquilino  AS apellidom_obligado,
            o.tipo_id AS tipo_id_obligado,
            o.num_id AS num_id_obligado,
            o.slug AS slug_obligado,
            o.nacionalidad AS nacionalidad_obligado,
            CONCAT_WS(" ", o.nombre_inquilino, o.apellidop_inquilino, o.apellidom_inquilino) AS nombre_obligado_completo,
            CONCAT_WS("",
              TRIM(NULLIF(do2.calle, "")),
              TRIM(NULLIF(do2.num_exterior, "")),
              IF(do2.num_interior IS NOT NULL AND do2.num_interior <> "", CONCAT(" Int. ", TRIM(do2.num_interior)), NULL),
              IFNULL(CONCAT(" Col. ", TRIM(do2.colonia)), ""),
              IFNULL(CONCAT(" Alcaldía ", TRIM(do2.alcaldia)), ""),
              IFNULL(CONCAT(" ", TRIM(do2.ciudad)), ""),
              IFNULL(CONCAT(" C.P. ", TRIM(do2.codigo_postal)), "")
            ) AS direccion_obligado

        FROM polizas p
        LEFT JOIN asesores a        ON p.id_asesor     = a.id
        LEFT JOIN arrendadores arr  ON p.id_arrendador = arr.id
        LEFT JOIN inquilinos i      ON p.id_inquilino  = i.id
        LEFT JOIN inquilinos f      ON p.id_fiador     = f.id
        LEFT JOIN inquilinos o      ON p.id_obligado   = o.id
        LEFT JOIN inquilinos_direccion di  ON di.id_inquilino  = i.id
        LEFT JOIN inquilinos_direccion df  ON df.id_inquilino  = f.id
        LEFT JOIN inquilinos_direccion do2 ON do2.id_inquilino = o.id
        LEFT JOIN inquilinos_fiador fg     ON fg.id_inquilino  = f.id
        ';

        // SQL final
        $sql = $sqlBase . (trim($condiciones) !== '' ? ' WHERE ' . $condiciones : '') . ' ' . $extras;

        $stmt = $this->db->prepare($sql);

        foreach ($parametros as $key => $value) {
            // Normaliza: acepta 't1' o ':t1'
            $name = $key[0] === ':' ? $key : (':' . $key);

            // Solo bindea si el placeholder existe en el SQL final
            if (strpos($sql, $name) !== false) {
                $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($name, $value, $type);
            }
        }

        $stmt->execute();

        if ($soloUna) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

            if ($row === null) {
                return null;
            }

            $conGarantia = $this->inicializarDatosGarantiaFiador([$row]);
            $conInmueble = $this->adjuntarInmueblesDesdeMysql($conGarantia);

            return $conInmueble[0] ?? $row;
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $rows = $this->inicializarDatosGarantiaFiador($rows);

        return $this->adjuntarInmueblesDesdeMysql($rows);
    }

    /* =========================================================
     *           VENCIMIENTOS / CONSULTAS CLAVE
     * ========================================================= */

    /**
     * Pólizas que vencen el próximo mes (estado = "1").
     */
    public function obtenerVencimientosProximos(): array
    {
        $mesActual  = (int) date('n');
        $anioActual = (int) date('Y');

        $mesSiguiente  = $mesActual + 1;
        $anioSiguiente = $anioActual;
        if ($mesSiguiente > 12) {
            $mesSiguiente = 1;
            $anioSiguiente++;
        }

        return $this->obtenerVencimientosPorMesAnio($mesSiguiente, $anioSiguiente);
    }

    public function obtenerVencimientosPorMesAnio(int $mes, int $anio): array
    {
        $condiciones = 'p.estado = :estado AND p.mes_vencimiento = :mes AND p.year_vencimiento = :anio';
        $parametros  = [
            ':estado' => '1',
            ':mes'    => $mes,
            ':anio'   => $anio,
        ];

        return $this->obtenerPolizasConFiltros($condiciones, $parametros, false, ' ORDER BY p.fecha_poliza ASC');
    }

    public function obtenerPorNumero(int|string $numero): ?array
    {
        return $this->obtenerPolizasConFiltros(
            'p.numero_poliza = :numero',
            [':numero' => $numero],
            true
        );
    }

    public function obtenerPorId(int $id): ?array
    {
        return $this->obtenerPolizasConFiltros(
            'p.id_poliza = :id',
            [':id' => $id],
            true
        );
    }

    public function obtenerUltimaPolizaEmitida(): string
    {
        $sql = "SELECT numero_poliza FROM polizas ORDER BY id_poliza DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return (string)($resultado['numero_poliza'] ?? '0');
    }

    public function obtenerTodas(): array
    {
        return $this->obtenerPolizasConFiltros('1');
    }

    public function contarTodas(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM polizas');
        return (int) $stmt->fetchColumn();
    }

    public function contarPorEstado(string $estado): int
    {
        $sql = 'SELECT COUNT(*) FROM polizas WHERE estado = :estado';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':estado' => $estado]);
        return (int) $stmt->fetchColumn();
    }

    /* =========================================================
     *                    PAGINACIÓN / LISTAS
     * ========================================================= */

    public function obtenerPaginadas(int $limite, int $offset): array
    {
        $condicion  = '1';
        $parametros = [
            ':limite' => $limite,
            ':offset' => $offset,
        ];
        $extras = 'ORDER BY p.fecha_poliza DESC LIMIT :limite OFFSET :offset';

        return $this->obtenerPolizasConFiltros($condicion, $parametros, false, $extras);
    }

    /**
     * Listado paginado con filtros por estado/tipo y búsqueda libre.
     * La búsqueda toca número de póliza, arrendador, cualquier persona (i/f/o) y dirección de inmueble.
     */
    public function obtenerPaginadasFiltradas(int $limit, int $offset, ?string $estado, ?string $tipo, ?string $buscar): array
    {
        // 1) Armar condiciones y parámetros nombrados
        $bloques = [];
        $params  = [];

        $buscarTerm = null;
        $buscarEsNumero = false;
        if ($buscar !== null && trim($buscar) !== '') {
            $buscarTerm = trim($buscar);
            $buscarEsNumero = ctype_digit($buscarTerm);

            if ($buscarEsNumero) {
                $bloques[] = 'p.numero_poliza = :num_poliza';
                $params[':num_poliza'] = (int) $buscarTerm;
            } else {
                // 🔎 Búsqueda textual insensible a acentos con parámetros únicos
                $like = '%' . $buscarTerm . '%';
                $bloques[] = '(
                CAST(i.nombre_inquilino AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci LIKE :like1
                OR CAST(i.apellidop_inquilino AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci LIKE :like2
                OR CAST(i.apellidom_inquilino AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci LIKE :like3
                OR CAST(arr.nombre_arrendador AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci LIKE :like4
                OR CAST(di.calle AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci LIKE :like5
                OR CAST(di.colonia AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci LIKE :like6
            )';
                $params[':like1'] = $like;
                $params[':like2'] = $like;
                $params[':like3'] = $like;
                $params[':like4'] = $like;
                $params[':like5'] = $like;
                $params[':like6'] = $like;
            }
        }

        if (!empty($estado)) {
            $bloques[] = 'p.estado = :estado';
            $params[':estado'] = $estado;
        }

        if (!empty($tipo)) {
            $bloques[] = 'p.tipo_poliza = :tipo';
            $params[':tipo'] = $tipo;
        }

        $where = $bloques ? implode(' AND ', $bloques) : '1';

        // 2) Orden y paginación (interpolación segura de enteros)
        $extras = ' ORDER BY p.numero_poliza DESC LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;

        // 3) Delegar al método común
        return $this->obtenerPolizasConFiltros($where, $params, false, $extras);
    }




    /**
     * Cuenta total de pólizas aplicando los mismos filtros que en obtenerPaginadasFiltradas().
     * Para mantener consistencia con los mismos JOINs, hacemos el COUNT con el mismo FROM.
     */
    public function contarFiltradas(?string $estado, ?string $tipo, ?string $buscar): int
    {
        // Construir MISMAS condiciones que arriba
        $bloques = [];
        $params  = [];

        $buscarTerm = null;
        $buscarEsNumero = false;
        if ($buscar !== null && trim($buscar) !== '') {
            $buscarTerm = trim($buscar);
            $buscarEsNumero = ctype_digit($buscarTerm);

            if ($buscarEsNumero) {
                $bloques[] = 'p.numero_poliza = :num_poliza';
                $params[':num_poliza'] = (int) $buscarTerm;
            }
        }

        if (!empty($estado)) {
            $bloques[] = 'p.estado = :estado';
            $params[':estado'] = $estado;
        }

        if (!empty($tipo)) {
            $bloques[] = 'p.tipo_poliza = :tipo';
            $params[':tipo'] = $tipo;
        }

        $where = $bloques ? implode(' AND ', $bloques) : '1';

        if ($buscarTerm !== null && !$buscarEsNumero) {
            $polizas = $this->obtenerPolizasConFiltros($where, $params);
            $polizas = $this->filtrarPolizasPorBusqueda($polizas, $buscarTerm);

            return count($polizas);
        }

        $polizas = $this->obtenerPolizasConFiltros($where, $params);

        return count($polizas);
    }

    /* =========================================================
     *                 CREAR / ACTUALIZAR
     * ========================================================= */

    /**
     * Actualiza campos permitidos de una póliza, referida por su número.
     */
    public function update(int|string $numero, array $data): bool
    {
        $campos = [];
        $params = [];

        // Campos permitidos a actualizar
        $permitidos = [
            'tipo_poliza',
            'id_asesor',
            'id_arrendador',
            'id_inquilino',
            'id_obligado',
            'id_fiador',
            'id_inmueble',
            'tipo_inmueble',
            'monto_renta',
            'monto_poliza',
            'estado',
            'vigencia',
            'mes_vencimiento',
            'year_vencimiento',
            'fecha_poliza',
            'fecha_fin',
            'periodo',
            'comentarios',
            // Campos “nombre_*” NO existen en `polizas` y se omiten
        ];

        foreach ($permitidos as $campo) {
            if (array_key_exists($campo, $data)) {
                $campos[] = "$campo = :$campo";
                $valor = $data[$campo];
                if ($campo === 'direccion_inmueble') {
                    $valor = TextHelper::titleCase((string) $valor);
                }
                $params[":$campo"] = $valor;
            }
        }

        if (empty($campos)) {
            return false;
        }

        $params[':numero'] = $numero;
        $sql = 'UPDATE polizas SET ' . implode(', ', $campos) . ' WHERE numero_poliza = :numero';

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Actualiza la dirección del inmueble (tabla `inmuebles`).
     */
    public function updateInmueble(int $idInmueble, array $data): bool
    {
        $campos = [];
        $params = [];
        $permitidos = ['direccion_inmueble'];

        foreach ($permitidos as $campo) {
            if (array_key_exists($campo, $data)) {
                $campos[] = "$campo = :$campo";
                $valor = $data[$campo];
                if ($campo === 'direccion_inmueble') {
                    $valor = TextHelper::titleCase((string) $valor);
                }
                $params[":$campo"] = $valor;
            }
        }

        if (empty($campos)) {
            return false;
        }

        $params[':id'] = $idInmueble;
        $sql = 'UPDATE inmuebles SET ' . implode(', ', $campos) . ' WHERE id = :id';

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Crea una póliza.
     * NOTA: ya NO existen campos *_2025 ni se insertan registros en otras tablas aquí.
     */
    public function crear(array $data): bool
    {


        $sql = "INSERT INTO polizas (
                    tipo_poliza, id_asesor, id_arrendador,
                    id_inquilino, id_obligado, id_fiador,
                    id_inmueble, tipo_inmueble,
                    monto_renta, monto_poliza,
                    estado, vigencia,
                    mes_vencimiento, year_vencimiento,
                    usuario, serie_poliza, numero_poliza,
                    fecha_poliza, fecha_fin,
                    periodo, comentarios
                ) VALUES (
                    :tipo_poliza, :id_asesor, :id_arrendador,
                    :id_inquilino, :id_obligado, :id_fiador,
                    :id_inmueble, :tipo_inmueble,
                    :monto_renta, :monto_poliza,
                    :estado, :vigencia,
                    :mes_vencimiento, :year_vencimiento,
                    :usuario, :serie_poliza, :numero_poliza,
                    :fecha_poliza, :fecha_fin,
                    :periodo, :comentarios
                )";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':tipo_poliza'      => $data['tipo_poliza'],
            ':id_asesor'        => $data['id_asesor'],
            ':id_arrendador'    => $data['id_arrendador'],
            ':id_inquilino'     => $data['id_inquilino']   ?? null,
            ':id_obligado'      => $data['id_obligado']    ?? null,
            ':id_fiador'        => $data['id_fiador']      ?? null,
            ':id_inmueble'      => $data['id_inmueble'],
            ':tipo_inmueble'    => $data['tipo_inmueble'],
            ':monto_renta'      => $data['monto_renta'],
            ':monto_poliza'     => $data['monto_poliza'],
            ':estado'           => $data['estado'],
            ':vigencia'         => $data['vigencia'],
            ':mes_vencimiento'  => $data['mes_vencimiento']  ?? null,
            ':year_vencimiento' => $data['year_vencimiento'] ?? null,
            ':usuario'          => $data['usuario'],
            ':serie_poliza'     => $data['serie_poliza']     ?? 1,
            ':numero_poliza'    => $data['numero_poliza'],
            ':fecha_poliza'     => $data['fecha_poliza'],
            ':fecha_fin'        => $data['fecha_fin']        ?? null,
            ':periodo'          => $data['periodo']          ?? '',
            ':comentarios'      => $data['comentarios']      ?? null,
        ]);
    }

    public function guardarArchivoPoliza(int $idArrendador, string $s3Key): bool
    {
        $sql = "INSERT INTO arrendadores_archivos (id_arrendador, s3_key, tipo)
            VALUES (:id_arrendador, :s3_key, 'poliza')";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_arrendador' => $idArrendador,
            ':s3_key'        => $s3Key,
        ]);
    }



    /**
     * Update + (opcionalmente) actualización de dirección del inmueble si viene `direccion` en $data.
     */
    public function actualizarCompleta(int|string $numero, array $data): bool
    {
        $ok = $this->update($numero, $data);

        if ($ok && isset($data['direccion'])) {
            $direccion = TextHelper::titleCase((string) $data['direccion']);
            $poliza = $this->obtenerPorNumero($numero);
            if ($poliza && !empty($poliza['id_inmueble'])) {
                $this->updateInmueble((int)$poliza['id_inmueble'], [
                    'direccion_inmueble' => $direccion
                ]);
            }
        }

        return $ok;
    }

    public function eliminarPorNumero(int $numero): bool
    {
        $stmt = $this->db->prepare('DELETE FROM polizas WHERE numero_poliza = :numero LIMIT 1');
        $stmt->execute([
            ':numero' => $numero,
        ]);

        return $stmt->rowCount() > 0;
    }
}
