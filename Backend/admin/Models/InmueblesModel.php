<?php

namespace App\Models;

require_once __DIR__ . '/AsesorModel.php';

require_once __DIR__ . '/../Core/Dynamo.php';

use App\Core\Dynamo;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use InvalidArgumentException;

class InmuebleModel
{
    private const INMUEBLE_SK_PREFIX = 'INM#';
    private const ARRENDADOR_PROFILE_SK = 'PROFILE';

    private DynamoDbClient $client;
    private Marshaler $marshaler;
    private string $table;
    /** @var array<int, array<string, mixed>>|null */
    private ?array $inmueblesCache = null;
    private ?AsesorModel $asesorModel = null;

    public function __construct()
    {
        $this->client    = Dynamo::client();
        $this->marshaler = Dynamo::marshaler();
        $this->table     = Dynamo::table();
    }

    /** Listado completo (ojo: sin paginar) */
    public function obtenerTodos(): array
    {
        return $this->getInmuebles();
    }

    public function obtenerPaginados(int $limite, int $offset): array
    {
        return array_slice($this->getInmuebles(), $offset, $limite);
    }

    public function buscarPaginados(string $query, int $limite, int $offset): array
    {
        $filtrados = $this->filtrarInmuebles($query);

        echo "<pre>";
        var_dump($filtrados);
        echo "</pre>";
        exit;

        return array_slice($filtrados, $offset, $limite);
    }

    public function contarBusqueda(string $query): int
    {
        return count($this->filtrarInmuebles($query));
    }

    public function contarTodos(): int
    {
        return count($this->getInmuebles());
    }

    /**
     * Obtiene una página de inmuebles directamente desde DynamoDB.
     *
     * @param int $limite
     * @param string|null $token
     * @return array{items: array<int, array<string, mixed>>, nextToken: ?string, hasMore: bool, consumedCapacity: float}
     */
    public function obtenerPaginaDynamo(int $limite, ?string $token = null): array
    {
        return $this->consultarPaginaDynamo($limite, $token, null);
    }

    /**
     * Busca inmuebles utilizando paginación basada en tokens.
     *
     * @param string $query
     * @param int $limite
     * @param string|null $token
     * @return array{items: array<int, array<string, mixed>>, nextToken: ?string, hasMore: bool, consumedCapacity: float}
     */
    public function buscarPaginaDynamo(string $query, int $limite, ?string $token = null): array
    {
        $query = trim($query);
        if ($query === '') {
            return $this->obtenerPaginaDynamo($limite, $token);
        }

        return $this->consultarPaginaDynamo($limite, $token, mb_strtolower($query, 'UTF-8'));
    }

    public function contarPorArrendador(int|string $idArrendador): int
    {
        $pk = is_numeric($idArrendador)
            ? 'arr#' . (int) $idArrendador
            : (string) $idArrendador;
        $needle = mb_strtolower($pk, 'UTF-8');

        return count(array_filter(
            $this->getInmuebles(),
            static fn(array $inmueble): bool => mb_strtolower((string)($inmueble['pk'] ?? ''), 'UTF-8') === $needle
        ));
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

            throw new InvalidArgumentException('Se requieren pk y sk del inmueble para DynamoDB.');
        }

        $pk = (string) $pk;
        $sk = (string) $sk;

        $result = $this->client->getItem([
            'TableName' => $this->table,
            'Key'       => [
                'pk' => ['S' => $pk],
                'sk' => ['S' => $sk],
            ],
        ]);

        if (empty($result['Item'])) {
            return null;
        }

        $inmueble = $this->normalizarItem($result['Item']);
        $conDatos = $this->adjuntarArrendadores([$inmueble]);

        return $conDatos[0] ?? $inmueble;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getInmuebles(): array
    {
        if ($this->inmueblesCache !== null) {
            return $this->inmueblesCache;
        }

        $items = [];
        $lastEvaluatedKey = null;

        do {
            $params = [
                'TableName'                 => $this->table,
                'FilterExpression'          => 'begins_with(sk, :prefix)',
                'ExpressionAttributeValues' => [
                    ':prefix' => ['S' => self::INMUEBLE_SK_PREFIX],
                ],
            ];

            if ($lastEvaluatedKey) {
                $params['ExclusiveStartKey'] = $lastEvaluatedKey;
            }

            $result = $this->client->scan($params);

            foreach ($result['Items'] ?? [] as $item) {
                $items[] = $this->normalizarItem($item);
            }

            $lastEvaluatedKey = $result['LastEvaluatedKey'] ?? null;
        } while ($lastEvaluatedKey);

        $items = $this->adjuntarArrendadores($items);

        usort(
            $items,
            static function (array $a, array $b): int {
                $fechaA = strtotime((string)($a['fecha_registro'] ?? '')) ?: 0;
                $fechaB = strtotime((string)($b['fecha_registro'] ?? '')) ?: 0;

                return $fechaB <=> $fechaA;
            }
        );

        return $this->inmueblesCache = array_values($items);
    }

    /**
     * @param string $texto
     * @return array<int, array<string, mixed>>
     */
    private function filtrarInmuebles(string $texto): array
    {
        $needle = mb_strtolower(trim($texto), 'UTF-8');
        if ($needle === '') {
            return $this->getInmuebles();
        }

        return array_values(array_filter(
            $this->getInmuebles(),
            function (array $inmueble) use ($needle): bool {
                return $this->coincideBusqueda($inmueble, $needle);
            }
        ));
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function normalizarItem(array $item): array
    {
        $data = $this->marshaler->unmarshalItem($item);

        $data['pk'] = (string) ($data['pk'] ?? '');
        $data['sk'] = (string) ($data['sk'] ?? '');
        $data['tipo'] = (string) ($data['tipo'] ?? '');
        $data['direccion_inmueble'] = (string) ($data['direccion_inmueble'] ?? '');
        $data['mantenimiento'] = (string) ($data['mantenimiento'] ?? '');
        $data['mascotas'] = strtoupper((string) ($data['mascotas'] ?? 'NO'));
        $data['deposito'] = (string) ($data['deposito'] ?? '');
        $data['comentarios'] = (string) ($data['comentarios'] ?? '');
        $data['fecha_registro'] = (string) ($data['fecha_registro'] ?? '');
        $data['nombre_arrendador'] = (string) ($data['nombre_arrendador'] ?? '');
        $data['nombre_asesor'] = (string) ($data['nombre_asesor'] ?? '');
        $data['renta'] = $this->formatearMonto($data['renta'] ?? null);
        $data['monto_mantenimiento'] = $this->formatearMonto($data['monto_mantenimiento'] ?? null);
        $data['estacionamiento'] = (int) ($data['estacionamiento'] ?? 0);

        $legacyId = $this->extraerLegacyId($data);
        if ($legacyId !== null) {
            $data['legacy_id'] = $legacyId;
            $data['id']        = $legacyId;
        }

        return $data;
    }

    /**
     * @param int $limite
     * @param string|null $token
     * @param string|null $needle
     * @return array{items: array<int, array<string, mixed>>, nextToken: ?string, hasMore: bool, consumedCapacity: float}
     */
    private function consultarPaginaDynamo(int $limite, ?string $token, ?string $needle): array
    {
        $limite = max(1, $limite);
        $exclusiveStartKey = $this->decodeToken($token);
        $items = [];
        $nextToken = null;
        $consumed = 0.0;

        $startKey = $exclusiveStartKey;

        do {
            $params = [
                'TableName'                 => $this->table,
                'FilterExpression'          => 'begins_with(sk, :prefix)',
                'ExpressionAttributeValues' => [
                    ':prefix' => ['S' => self::INMUEBLE_SK_PREFIX],
                ],
                'Limit'                     => 25,
                'ReturnConsumedCapacity'    => 'TOTAL',
            ];

            if ($startKey !== null) {
                $params['ExclusiveStartKey'] = $startKey;
                $startKey = null;
            }

            $result = $this->client->scan($params);
            $consumed += (float) ($result['ConsumedCapacity']['CapacityUnits'] ?? 0);

            foreach ($result['Items'] ?? [] as $rawItem) {
                $inmueble = $this->normalizarItem($rawItem);

                if ($needle !== null && $needle !== '' && !$this->coincideBusqueda($inmueble, $needle)) {
                    continue;
                }

                $items[] = $inmueble;

                if (count($items) === $limite) {
                    $nextToken = $this->encodeKey($rawItem);
                    break 2;
                }
            }

            $startKey = $result['LastEvaluatedKey'] ?? null;

            if ($startKey !== null) {
                usleep(250000);
            }
        } while ($startKey !== null);

        $items = $this->adjuntarArrendadores($items);

        return [
            'items'            => $items,
            'nextToken'        => $nextToken,
            'hasMore'          => $nextToken !== null,
            'consumedCapacity' => $consumed,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $inmuebles
     * @return array<int, array<string, mixed>>
     */
    private function adjuntarArrendadores(array $inmuebles): array
    {
        if ($inmuebles === []) {
            return [];
        }

        $pks = [];
        foreach ($inmuebles as $inmueble) {
            $pk = (string) ($inmueble['pk'] ?? '');
            if ($pk !== '') {
                $pks[] = $pk;
            }
        }

        $arrendadores = $this->obtenerPerfilesArrendador($pks);

        foreach ($inmuebles as &$inmueble) {
            $pkLower = mb_strtolower((string)($inmueble['pk'] ?? ''), 'UTF-8');
            $datos = $arrendadores[$pkLower] ?? null;

            $inmueble['nombre_arrendador'] = $datos['nombre_arrendador'] ?? '';
            if (isset($datos['id_arrendador'])) {
                $inmueble['id_arrendador'] = $datos['id_arrendador'];
            }
            if (isset($datos['nombre_asesor'])) {
                $inmueble['nombre_asesor'] = $datos['nombre_asesor'];
            }
            if (isset($datos['id_asesor'])) {
                $inmueble['id_asesor'] = $datos['id_asesor'];
            }
            if (isset($datos['asesor_pk'])) {
                $inmueble['asesor_pk'] = $datos['asesor_pk'];
            }
        }
        unset($inmueble);

        return $inmuebles;
    }

    /**
     * @param array<int, string> $arrendadorPks
     * @return array<string, array<string, mixed>>
     */
    private function obtenerPerfilesArrendador(array $arrendadorPks): array
    {
        $unique = [];
        foreach ($arrendadorPks as $pk) {
            $original = trim((string) $pk);
            if ($original === '') {
                continue;
            }
            $unique[mb_strtolower($original, 'UTF-8')] = $original;
        }

        if ($unique === []) {
            return [];
        }

        $perfiles = [];
        $asesorPks = [];

        foreach (array_chunk(array_values($unique), 25) as $chunk) {
            $keys = [];
            foreach ($chunk as $pk) {
                $keys[] = [
                    'pk' => ['S' => $pk],
                    'sk' => ['S' => self::ARRENDADOR_PROFILE_SK],
                ];
            }

            foreach ($this->batchGet($keys) as $item) {
                $profile = $this->marshaler->unmarshalItem($item);
                $pkValue = (string) ($profile['pk'] ?? '');
                if ($pkValue === '') {
                    continue;
                }

                $pkLower = mb_strtolower($pkValue, 'UTF-8');
                if (isset($perfiles[$pkLower])) {
                    continue;
                }

                $asesorPk = $this->resolverAsesorPk($profile['asesor_pk'] ?? null, $profile['asesor'] ?? null);
                if ($asesorPk !== null) {
                    $asesorPks[] = $asesorPk;
                }

                $perfiles[$pkLower] = [
                    'pk'                => $pkValue,
                    'id_arrendador'     => isset($profile['id']) ? (int) $profile['id'] : null,
                    'nombre_arrendador' => (string) ($profile['nombre_arrendador'] ?? ''),
                    'asesor_pk'         => $asesorPk,
                ];
            }

            usleep(250000);
        }

        $asesorPks = array_values(array_unique(array_filter($asesorPks)));
        $asesores = $asesorPks === [] ? [] : $this->getAsesorModel()->batchGetByPk($asesorPks);

        foreach ($perfiles as $pkLower => &$perfil) {
            $asesorPk = $perfil['asesor_pk'] ?? null;
            if ($asesorPk && isset($asesores[$asesorPk])) {
                $asesor = $asesores[$asesorPk];
                $perfil['nombre_asesor'] = (string) ($asesor['nombre_asesor'] ?? '');
                if (isset($asesor['id'])) {
                    $perfil['id_asesor'] = (int) $asesor['id'];
                }
            } else {
                $perfil['nombre_asesor'] = '';
            }
        }
        unset($perfil);

        return $perfiles;
    }

    private function coincideBusqueda(array $inmueble, string $needle): bool
    {
        foreach (['direccion_inmueble', 'nombre_arrendador', 'nombre_asesor', 'tipo'] as $campo) {
            $valor = mb_strtolower((string) ($inmueble[$campo] ?? ''), 'UTF-8');
            if ($valor !== '' && mb_strpos($valor, $needle, 0, 'UTF-8') !== false) {
                return true;
            }
        }

        return false;
    }

    private function encodeKey(array $rawItem): ?string
    {
        $pk = $rawItem['pk']['S'] ?? null;
        $sk = $rawItem['sk']['S'] ?? null;

        if (!is_string($pk) || $pk === '' || !is_string($sk) || $sk === '') {
            return null;
        }

        $payload = json_encode(['pk' => $pk, 'sk' => $sk]);

        if ($payload === false) {
            return null;
        }

        return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    }

    private function decodeToken(?string $token): ?array
    {
        if ($token === null || $token === '') {
            return null;
        }

        $normalized = strtr($token, '-_', '+/');
        $padding = strlen($normalized) % 4;
        if ($padding > 0) {
            $normalized .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($normalized, true);
        if ($decoded === false) {
            return null;
        }

        $data = json_decode($decoded, true);
        if (!is_array($data)) {
            return null;
        }

        $pk = $data['pk'] ?? null;
        $sk = $data['sk'] ?? null;

        if (!is_string($pk) || $pk === '' || !is_string($sk) || $sk === '') {
            return null;
        }

        return [
            'pk' => ['S' => $pk],
            'sk' => ['S' => $sk],
        ];
    }

    /**
     * @param array<int, array<string, array<string, string>>> $keys
     * @return array<int, array<string, mixed>>
     */
    private function batchGet(array $keys): array
    {
        if ($keys === []) {
            return [];
        }

        $items = [];
        $request = ['RequestItems' => [$this->table => ['Keys' => $keys]]];

        do {
            $response = $this->client->batchGetItem($request);

            foreach ($response['Responses'][$this->table] ?? [] as $item) {
                $items[] = $item;
            }

            if (!empty($response['UnprocessedKeys'][$this->table]['Keys'])) {
                $request = ['RequestItems' => [$this->table => ['Keys' => $response['UnprocessedKeys'][$this->table]['Keys']]]];
            } else {
                break;
            }
        } while (true);

        return $items;
    }

    private function resolverAsesorPk(mixed $asesorPk, mixed $asesor): ?string
    {
        if (is_string($asesorPk) && $asesorPk !== '') {
            return $asesorPk;
        }

        if (is_string($asesor) && $asesor !== '') {
            return $asesor;
        }

        if (is_array($asesor)) {
            if (!empty($asesor['pk']) && is_string($asesor['pk'])) {
                return $asesor['pk'];
            }

            if (!empty($asesor['id']) && is_scalar($asesor['id'])) {
                return 'ase#' . (string) $asesor['id'];
            }
        }

        return null;
    }

    private function formatearMonto(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '0.00';
        }

        if (is_numeric($valor)) {
            return number_format((float) $valor, 2, '.', '');
        }

        return (string) $valor;
    }

    private function extraerLegacyId(array $data): ?int
    {
        foreach (['legacy_id', 'id'] as $field) {
            if (!isset($data[$field])) {
                continue;
            }

            $value = $data[$field];
            if (is_numeric($value)) {
                return (int) $value;
            }

            if (is_array($value) && isset($value['N']) && is_numeric($value['N'])) {
                return (int) $value['N'];
            }
        }

        return null;
    }

    private function getAsesorModel(): AsesorModel
    {
        if ($this->asesorModel === null) {
            $this->asesorModel = new AsesorModel();
        }

        return $this->asesorModel;
    }

    private function obtenerPorLegacyId(int $legacyId): ?array
    {
        $lastKey = null;

        do {
            $params = [
                'TableName'                 => $this->table,
                'FilterExpression'          => 'begins_with(sk, :prefix) AND (#legacy = :legacy OR #id = :legacy)',
                'ExpressionAttributeValues' => [
                    ':prefix' => ['S' => self::INMUEBLE_SK_PREFIX],
                    ':legacy' => ['N' => (string) $legacyId],
                ],
                'ExpressionAttributeNames'  => [
                    '#legacy' => 'legacy_id',
                    '#id'     => 'id',
                ],
                'Limit' => 25,
            ];

            if ($lastKey) {
                $params['ExclusiveStartKey'] = $lastKey;
            }

            $result = $this->client->scan($params);

            if (!empty($result['Items'])) {
                $inmueble  = $this->normalizarItem($result['Items'][0]);
                $withOwner = $this->adjuntarArrendadores([$inmueble]);

                return $withOwner[0] ?? $inmueble;
            }

            $lastKey = $result['LastEvaluatedKey'] ?? null;

            if ($lastKey) {
                usleep(250000);
            }
        } while ($lastKey);

        return null;
    }

    public function obtenerIdPorLlaves(string $pk, string $sk): ?int
    {
        $result = $this->client->getItem([
            'TableName' => $this->table,
            'Key'       => [
                'pk' => ['S' => $pk],
                'sk' => ['S' => $sk],
            ],
            'ProjectionExpression'     => '#legacy, #id',
            'ExpressionAttributeNames' => [
                '#legacy' => 'legacy_id',
                '#id'     => 'id',
            ],
        ]);

        if (empty($result['Item'])) {
            return null;
        }

        $data = $this->marshaler->unmarshalItem($result['Item']);

        return $this->extraerLegacyId($data);
    }

    public function crear(array $data): bool
    {
        /**
         * 🚩 ATENCIÓN:
         * Esta función ya fue actualizada a DynamoDB.
         * NO volver a modificar para MySQL.
         */
        $sk = 'INM#' . uniqid();

        // 1️⃣ Construir item de inmueble
        $item = [
            'pk'                 => ['S' => $data['pk']], // ej: ARR#557
            'sk'                 => ['S' => $sk],
            'tipo'               => ['S' => $data['tipo']],
            'direccion_inmueble' => ['S' => $data['direccion_inmueble']],
            'renta'              => ['N' => (string) $data['renta']],
            'mantenimiento'      => ['S' => $data['mantenimiento']],
            'estacionamiento'    => ['N' => (string) $data['estacionamiento']],
            'mascotas'           => ['S' => strtoupper((string) $data['mascotas'])],
            'fecha_registro'     => ['S' => date('Y-m-d H:i:s')],
        ];

        if (isset($data['monto_mantenimiento']) && $data['monto_mantenimiento'] !== null && $data['monto_mantenimiento'] !== '') {
            $item['monto_mantenimiento'] = ['N' => (string) $data['monto_mantenimiento']];
        }

        if (isset($data['deposito']) && $data['deposito'] !== null && $data['deposito'] !== '') {
            $item['deposito'] = ['N' => (string) $data['deposito']];
        }

        if (!empty($data['comentarios'])) {
            $item['comentarios'] = ['S' => $data['comentarios']];
        }

        if (!empty($data['asesor'])) {
            $item['asesor'] = ['S' => $data['asesor']];
        }

        try {
            // 2️⃣ Guardar inmueble
            $this->client->putItem([
                'TableName' => $this->table,
                'Item'      => $item,
            ]);

            // 3️⃣ Actualizar arrendador → agregar inmueble a inmuebles_ids
            $this->client->updateItem([
                'TableName' => $this->table,
                'Key' => [
                    'pk' => ['S' => $data['pk']],      // ARR#557
                    'sk' => ['S' => 'PROFILE']
                ],
                'UpdateExpression' => 'SET inmuebles_ids = list_append(if_not_exists(inmuebles_ids, :empty_list), :new_inm)',
                'ExpressionAttributeValues' => [
                    ':new_inm'    => ['L' => [['S' => $sk]]],
                    ':empty_list' => ['L' => []]
                ]
            ]);

            $this->inmueblesCache = null;

            return true;
        } catch (\Throwable $e) {
            error_log("❌ Error guardando inmueble en Dynamo: " . $e->getMessage());
            return false;
        }
    }



    public function actualizarPorPkSk(string $pk, string $sk, array $data): bool
    {
        try {
            $setParts = [
                'tipo = :tipo',
                'direccion_inmueble = :direccion',
                'renta = :renta',
                'mantenimiento = :mantenimiento',
                'estacionamiento = :estacionamiento',
                'mascotas = :mascotas',
            ];

            $values = [
                ':tipo'           => ['S' => (string) $data['tipo']],
                ':direccion'      => ['S' => (string) $data['direccion_inmueble']],
                ':renta'          => ['N' => (string) $data['renta']],
                ':mantenimiento'  => ['S' => (string) $data['mantenimiento']],
                ':estacionamiento' => ['N' => (string) (int) $data['estacionamiento']],
                ':mascotas'       => ['S' => strtoupper((string) $data['mascotas'])],
            ];

            $remove = [];

            if (isset($data['monto_mantenimiento']) && $data['monto_mantenimiento'] !== null && $data['monto_mantenimiento'] !== '') {
                $setParts[] = 'monto_mantenimiento = :monto_mantenimiento';
                $values[':monto_mantenimiento'] = ['N' => (string) $data['monto_mantenimiento']];
            } else {
                $remove[] = 'monto_mantenimiento';
            }

            if (isset($data['deposito']) && $data['deposito'] !== null && $data['deposito'] !== '') {
                $setParts[] = 'deposito = :deposito';
                $values[':deposito'] = ['N' => (string) $data['deposito']];
            } else {
                $remove[] = 'deposito';
            }

            if (!empty($data['comentarios'])) {
                $setParts[] = 'comentarios = :comentarios';
                $values[':comentarios'] = ['S' => (string) $data['comentarios']];
            } else {
                $remove[] = 'comentarios';
            }

            if (!empty($data['asesor'])) {
                $setParts[] = 'asesor = :asesor';
                $values[':asesor'] = ['S' => (string) $data['asesor']];
            } else {
                $remove[] = 'asesor';
            }

            $updateExpression = 'SET ' . implode(', ', $setParts);
            $remove = array_unique(array_filter($remove));
            if (!empty($remove)) {
                $updateExpression .= ' REMOVE ' . implode(', ', $remove);
            }

            $params = [
                'TableName' => $this->table,
                'Key'       => [
                    'pk' => ['S' => $pk],
                    'sk' => ['S' => $sk],
                ],
                'UpdateExpression'          => $updateExpression,
                'ExpressionAttributeValues' => $values,
            ];

            $this->client->updateItem($params);

            return true;
        } catch (\Throwable $e) {
            error_log('❌ Error actualizando inmueble en Dynamo: ' . $e->getMessage());
            return false;
        }
    }

    public function eliminar(string $pk, string $sk): bool
    {
        try {
            // 1️⃣ Eliminar el item del inmueble
            $this->client->deleteItem([
                'TableName' => $this->table,
                'Key' => [
                    'pk' => ['S' => $pk],
                    'sk' => ['S' => $sk],
                ],
            ]);

            // 2️⃣ Obtener el PROFILE del arrendador para localizar el índice del inmueble en inmuebles_ids
            $result = $this->client->getItem([
                'TableName' => $this->table,
                'Key' => [
                    'pk' => ['S' => $pk],
                    'sk' => ['S' => 'PROFILE'],
                ],
                'ProjectionExpression' => 'inmuebles_ids'
            ]);

            if (!isset($result['Item']['inmuebles_ids']['L'])) {
                // No hay lista de inmuebles_ids, nada más que hacer
                return true;
            }

            $lista = $result['Item']['inmuebles_ids']['L'];
            $index = null;

            foreach ($lista as $i => $val) {
                if (isset($val['S']) && $val['S'] === $sk) {
                    $index = $i;
                    break;
                }
            }

            if ($index === null) {
                // El inmueble no estaba en la lista
                return true;
            }

            // 3️⃣ Eliminar el inmueble del array inmuebles_ids por índice
            $this->client->updateItem([
                'TableName' => $this->table,
                'Key' => [
                    'pk' => ['S' => $pk],
                    'sk' => ['S' => 'PROFILE'],
                ],
                'UpdateExpression' => "REMOVE inmuebles_ids[$index]"
            ]);

            $this->inmueblesCache = null;

            return true;
        } catch (\Throwable $e) {
            error_log("❌ Error al eliminar inmueble: " . $e->getMessage());
            return false;
        }
    }


    public function obtenerPorArrendador(int|string $idArrendador): array
    {
        $pk = is_numeric($idArrendador)
            ? 'arr#' . (int) $idArrendador
            : (string) $idArrendador;

        $pk = mb_strtolower(trim($pk), 'UTF-8');
        if ($pk === '') {
            return [];
        }

        $profileResult = $this->client->getItem([
            'TableName'            => $this->table,
            'Key'                  => [
                'pk' => ['S' => $pk],
                'sk' => ['S' => self::ARRENDADOR_PROFILE_SK],
            ],
            'ProjectionExpression' => 'inmuebles_ids',
        ]);

        $inmuebleSks = [];
        foreach (($profileResult['Item']['inmuebles_ids']['L'] ?? []) as $value) {
            if (isset($value['S'])) {
                $sk = trim((string) $value['S']);
                if ($sk !== '') {
                    $inmuebleSks[$sk] = $sk;
                }
            }
        }

        if ($inmuebleSks === []) {
            return [];
        }

        $items = [];

        foreach (array_chunk(array_values($inmuebleSks), 25) as $chunk) {
            $keys = [];
            foreach ($chunk as $sk) {
                $keys[] = [
                    'pk' => ['S' => $pk],
                    'sk' => ['S' => $sk],
                ];
            }

            foreach ($this->batchGet($keys) as $item) {
                $inmueble = $this->normalizarItem($item);
                $skValue = (string) ($inmueble['sk'] ?? '');
                if ($skValue === '') {
                    continue;
                }

                $items[$skValue] = $inmueble;
            }

            usleep(250000);
        }

        $items = array_filter(
            $items,
            static fn(array $inmueble): bool => mb_strtolower((string)($inmueble['pk'] ?? ''), 'UTF-8') === $pk
        );

        $items = array_values($items);

        usort(
            $items,
            static function (array $a, array $b): int {
                return strcasecmp((string)($a['direccion_inmueble'] ?? ''), (string)($b['direccion_inmueble'] ?? ''));
            }
        );

        foreach ($items as &$inmueble) {
            $pkValue = (string)($inmueble['pk'] ?? '');
            $skValue = (string)($inmueble['sk'] ?? '');
            if ($pkValue !== '' && $skValue !== '') {
                $inmueble['id_virtual'] = $pkValue . '|' . $skValue;
            }

            $legacyId = $inmueble['legacy_id'] ?? $inmueble['id'] ?? null;
            if (is_numeric($legacyId)) {
                $inmueble['id'] = (int) $legacyId;
            }
        }
        unset($inmueble);

        return $items;
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
}
