<?php

namespace App\Models;

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/Dynamo.php';

use App\Core\Database;
use App\Core\Dynamo;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
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
    private DynamoDbClient $client;
    private Marshaler $marshaler;
    private string $table;

    public function __construct()
    {
        parent::__construct();

        $this->client    = Dynamo::client();
        $this->marshaler = Dynamo::marshaler();
        $this->table     = Dynamo::table();
    }

    /**
     * @param array<int, array<string, mixed>> $polizas
     * @return array<int, array<string, mixed>>
     */
    private function adjuntarInmueblesDesdeDynamo(array $polizas): array
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

        $llaves = $ids === []
            ? []
            : $this->obtenerLlavesInmueblesPorIds(array_values($ids));

        $keyToId = [];
        $keys = [];

        foreach ($llaves as $id => $llave) {
            $pk = trim((string)($llave['pk'] ?? ''));
            $sk = trim((string)($llave['sk'] ?? ''));

            if ($pk === '' || $sk === '') {
                continue;
            }

            $keys[] = ['pk' => $pk, 'sk' => $sk];
            $keyToId[mb_strtolower($pk . '|' . $sk, 'UTF-8')] = $id;
        }

        $items = $keys === [] ? [] : $this->batchGetInmuebles($keys);

        $datos = [];
        foreach ($items as $item) {
            $normalizado = $this->normalizarInmuebleDynamo($item);
            $pk = (string)($normalizado['pk'] ?? '');
            $sk = (string)($normalizado['sk'] ?? '');

            if ($pk === '' || $sk === '') {
                continue;
            }

            $clave = mb_strtolower($pk . '|' . $sk, 'UTF-8');
            if (isset($keyToId[$clave])) {
                $datos[$keyToId[$clave]] = $normalizado;
            }
        }

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

            $id = isset($poliza['id_inmueble']) ? (int) $poliza['id_inmueble'] : 0;

            if ($id > 0) {
                if (isset($llaves[$id])) {
                    $poliza['inmueble_pk'] = (string)($llaves[$id]['pk'] ?? '');
                    $poliza['inmueble_sk'] = (string)($llaves[$id]['sk'] ?? '');
                }

                if (isset($datos[$id])) {
                    $info = $datos[$id];
                    $poliza['direccion_inmueble'] = (string)($info['direccion_inmueble'] ?? $defaults['direccion_inmueble']);
                    $poliza['estacionamiento_inmueble'] = (int)($info['estacionamiento_inmueble'] ?? $defaults['estacionamiento_inmueble']);
                    $poliza['monto_mantenimiento'] = (string)($info['monto_mantenimiento'] ?? $defaults['monto_mantenimiento']);
                    $poliza['mascotas_inmueble'] = (string)($info['mascotas_inmueble'] ?? $defaults['mascotas_inmueble']);
                    $poliza['mantenimiento_inmueble'] = (string)($info['mantenimiento_inmueble'] ?? $defaults['mantenimiento_inmueble']);
                }
            }
        }
        unset($poliza);

        return $polizas;
    }

    /**
     * @param array<int, int> $ids
     * @return array<int, array<string, string>>
     */
    private function obtenerLlavesInmueblesPorIds(array $ids): array
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

        $sql = 'SELECT id, pk, sk FROM inmuebles WHERE id IN (' . implode(', ', $placeholders) . ')';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $map = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = (int)($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $map[$id] = [
                'pk' => isset($row['pk']) ? (string)$row['pk'] : '',
                'sk' => isset($row['sk']) ? (string)$row['sk'] : '',
            ];
        }

        return $map;
    }

    /**
     * @param array<int, array{pk: string, sk: string}> $keys
     * @return array<int, array<string, mixed>>
     */
    private function batchGetInmuebles(array $keys): array
    {
        if ($keys === []) {
            return [];
        }

        $items = [];

        foreach (array_chunk($keys, 25) as $chunk) {
            $request = ['RequestItems' => [$this->table => ['Keys' => []]]];

            foreach ($chunk as $key) {
                $request['RequestItems'][$this->table]['Keys'][] = [
                    'pk' => ['S' => $key['pk']],
                    'sk' => ['S' => $key['sk']],
                ];
            }

            do {
                try {
                    $response = $this->client->batchGetItem($request);
                } catch (\Throwable $e) {
                    error_log('❌ Error consultando inmuebles en DynamoDB: ' . $e->getMessage());
                    break;
                }

                foreach ($response['Responses'][$this->table] ?? [] as $item) {
                    $items[] = $item;
                }

                if (!empty($response['UnprocessedKeys'][$this->table]['Keys'])) {
                    $request = ['RequestItems' => [$this->table => ['Keys' => $response['UnprocessedKeys'][$this->table]['Keys']]]];
                } else {
                    $request = null;
                }
            } while ($request !== null);

            usleep(250000);
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function normalizarInmuebleDynamo(array $item): array
    {
        $data = $this->marshaler->unmarshalItem($item);

        $pk = (string)($data['pk'] ?? '');
        $sk = (string)($data['sk'] ?? '');

        $direccion = trim((string)($data['direccion_inmueble'] ?? ''));
        if ($direccion === '') {
            $direccion = 'SIN DIRECCIÓN';
        }

        $mascotas = strtoupper((string)($data['mascotas'] ?? 'NO'));
        if ($mascotas === '') {
            $mascotas = 'NO';
        }

        return [
            'pk'                       => $pk,
            'sk'                       => $sk,
            'direccion_inmueble'       => $direccion,
            'estacionamiento_inmueble' => isset($data['estacionamiento']) ? (int) $data['estacionamiento'] : 0,
            'monto_mantenimiento'      => $this->formatearMontoInmueble($data['monto_mantenimiento'] ?? null),
            'mascotas_inmueble'        => $mascotas,
            'mantenimiento_inmueble'   => (string)($data['mantenimiento'] ?? ''),
        ];
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

        $needle    = mb_strtolower($termino, 'UTF-8');
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
                $valor = mb_strtolower((string)($poliza[$campo] ?? ''), 'UTF-8');
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
            arr.celular AS celular_arrendador,
            arr.direccion_arrendador,
            arr.banco AS banco_arrendador,
            arr.cuenta AS cuenta_arrendador,
            arr.clabe AS clabe_arrendador,
            arr.tipo_id AS tipo_id_arrendador,
            arr.num_id AS num_id_arrendador,
            i.nombre_inquilino AS nombre_inquilino,
            i.apellidop_inquilino AS apellidop_inquilino,
            i.apellidom_inquilino AS apellidom_inquilino,
            i.celular AS celular_inquilino,
            i.tipo_id AS tipo_id_inquilino,
            i.num_id AS num_id_inquilino,
            i.slug AS slug_inquilino,
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
            o.nombre_inquilino     AS nombre_obligado,
            o.apellidop_inquilino  AS apellidop_obligado,
            o.apellidom_inquilino  AS apellidom_obligado,
            o.tipo_id AS tipo_id_obligado,
            o.num_id AS num_id_obligado,
            o.slug AS slug_obligado,
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

            $conInmueble = $this->adjuntarInmueblesDesdeDynamo([$row]);

            return $conInmueble[0] ?? $row;
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return $this->adjuntarInmueblesDesdeDynamo($rows);
    }

    /**
     * Consulta DynamoDB para obtener pólizas activas filtradas por mes/año.
     *
     * Se utiliza scan paginado porque los perfiles POL# comparten partición; si el
     * volumen crece será recomendable un GSI dedicado a mes/year para evitar escaneos.
     *
     * @return array<int, array<string, mixed>>
     */
    private function obtenerVencimientosDesdeDynamo(int $mes, int $anio): array
    {
        $items   = [];
        $lastKey = null;

        do {
            $params = [
                'TableName'                 => $this->table,
                'FilterExpression'          => 'begins_with(pk, :pk) AND sk = :sk AND estado = :estado AND mes_vencimiento = :mes AND year_vencimiento = :anio',
                'ExpressionAttributeValues' => [
                    ':pk'     => ['S' => 'pol#'],
                    ':sk'     => ['S' => 'profile'],
                    ':estado' => ['S' => '1'],
                    ':mes'    => ['N' => (string) $mes],
                    ':anio'   => ['N' => (string) $anio],
                ],
            ];

            if ($lastKey !== null) {
                $params['ExclusiveStartKey'] = $lastKey;
            }

            try {
                $result = $this->client->scan($params);
            } catch (\Throwable $e) {
                error_log('❌ Error consultando vencimientos en DynamoDB: ' . $e->getMessage());
                break;
            }

            if (!empty($result['Items'])) {
                foreach ($result['Items'] as $item) {
                    $profile   = $this->marshaler->unmarshalItem($item);
                    $items[] = $this->normalizarVencimientoDynamo($profile);
                }
            }

            $lastKey = $result['LastEvaluatedKey'] ?? null;
        } while ($lastKey !== null);

        return $items;
    }

    /**
     * Normaliza los campos retornados por Dynamo para que coincidan con las vistas legacy.
     *
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function normalizarVencimientoDynamo(array $item): array
    {
        $normalizado = $item;

        // Identificadores
        $normalizado['numero_poliza'] = (string)($item['numero_poliza'] ?? '');
        $normalizado['serie_poliza']  = (string)($item['serie_poliza'] ?? '');
        $normalizado['tipo_poliza']   = (string)($item['tipo_poliza'] ?? '');
        $normalizado['estado']        = (string)($item['estado'] ?? '');
        $normalizado['vigencia']      = (string)($item['vigencia'] ?? '');
        $normalizado['fecha_poliza']  = (string)($item['fecha_poliza'] ?? '');
        $normalizado['fecha_fin']     = (string)($item['fecha_fin'] ?? '');

        // Fechas de vencimiento
        $normalizado['mes_vencimiento']  = isset($item['mes_vencimiento']) ? (int) $item['mes_vencimiento'] : 0;
        $normalizado['year_vencimiento'] = isset($item['year_vencimiento']) ? (int) $item['year_vencimiento'] : 0;

        // Montos
        $normalizado['monto_renta']  = isset($item['monto_renta']) ? (float) $item['monto_renta'] : 0.0;
        $normalizado['monto_poliza'] = isset($item['monto_poliza']) ? (float) $item['monto_poliza'] : 0.0;

        // 🔥 Nombres de personas
        $normalizado['nombre_inquilino_completo'] = (string)($item['nombre_inquilino_completo'] ?? $item['inquilino'] ?? '—');
        $normalizado['nombre_arrendador']         = (string)($item['nombre_arrendador'] ?? $item['arrendador'] ?? '—');
        $normalizado['nombre_fiador']             = (string)($item['nombre_fiador'] ?? $item['fiador'] ?? '—');
        $normalizado['nombre_asesor']             = (string)($item['nombre_asesor'] ?? $item['asesor'] ?? '—');

        // 🔥 Dirección
        $normalizado['direccion_inmueble'] = (string)($item['direccion_inmueble'] ?? $item['inmueble'] ?? '—');

        return $normalizado;
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
        $items = $this->obtenerVencimientosDesdeDynamo($mes, $anio);

        usort($items, static function (array $a, array $b): int {
            $fechaA = (string)($a['fecha_poliza'] ?? '');
            $fechaB = (string)($b['fecha_poliza'] ?? '');

            return strcmp($fechaA, $fechaB);
        });

        return $items;
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
        // 1) Armar condiciones y parámetros nombrados (con dos puntos)
        $bloques = [];
        $params  = [];

        $buscarTerm = null;
        if ($buscar !== null && trim($buscar) !== '') {
            $buscarTerm = trim($buscar);
            $like = "%{$buscarTerm}%";

            $sub = [
                'i.nombre_inquilino LIKE :t1',
                'i.apellidop_inquilino LIKE :t2',
                'i.apellidom_inquilino LIKE :t3',
                'arr.nombre_arrendador LIKE :t4',
            ];
            $params[':t1'] = $like;
            $params[':t2'] = $like;
            $params[':t3'] = $like;
            $params[':t4'] = $like;

            if (ctype_digit($buscarTerm)) {
                $sub[] = 'p.numero_poliza = :num_poliza';
                $params[':num_poliza'] = (int)$buscarTerm;
            }

            $bloques[] = '(' . implode(' OR ', $sub) . ')';
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

        if ($buscarTerm !== null) {
            $polizas = $this->obtenerPolizasConFiltros($where, $params);
            $filtradas = $this->filtrarPolizasPorBusqueda($polizas, $buscarTerm);

            return array_slice($filtradas, $offset, $limit);
        }

        // 2) Orden y paginación
        $extras = ' ORDER BY p.numero_poliza DESC LIMIT :limit OFFSET :offset';
        $params[':limit']  = (int)$limit;
        $params[':offset'] = (int)$offset;

        // 3) Delegar al método común (el tuyo)
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
        if ($buscar !== null && trim($buscar) !== '') {
            $buscarTerm = trim($buscar);
            $like = "%{$buscarTerm}%";

            $sub = [
                'i.nombre_inquilino LIKE :t1',
                'i.apellidop_inquilino LIKE :t2',
                'i.apellidom_inquilino LIKE :t3',
                'arr.nombre_arrendador LIKE :t4',
            ];
            $params[':t1'] = $like;
            $params[':t2'] = $like;
            $params[':t3'] = $like;
            $params[':t4'] = $like;

            if (ctype_digit($buscarTerm)) {
                $sub[] = 'p.numero_poliza = :num_poliza';
                $params[':num_poliza'] = (int)$buscarTerm;
            }

            $bloques[] = '(' . implode(' OR ', $sub) . ')';
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

        $polizas = $this->obtenerPolizasConFiltros($where, $params);

        if ($buscarTerm !== null) {
            $polizas = $this->filtrarPolizasPorBusqueda($polizas, $buscarTerm);
        }

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
                $params[":$campo"] = $data[$campo];
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
                $params[":$campo"] = $data[$campo];
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
            $poliza = $this->obtenerPorNumero($numero);
            if ($poliza && !empty($poliza['id_inmueble'])) {
                $this->updateInmueble((int)$poliza['id_inmueble'], [
                    'direccion_inmueble' => $data['direccion']
                ]);
            }
        }

        return $ok;
    }
}
