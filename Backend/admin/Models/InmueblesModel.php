<?php

namespace App\Models;

require_once __DIR__ . '/../Core/Database.php';

use App\Core\Database;
use PDO;

require_once __DIR__ . '/../Core/Dynamo.php';
require_once __DIR__ . '/AsesorModel.php';

use App\Core\Dynamo;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use InvalidArgumentException;

class InmuebleModel extends Database
{
    private const INMUEBLE_PREFIX = 'INM#';
    private const PROFILE_SK      = 'profile';

    private DynamoDbClient $client;
    private Marshaler $marshaler;
    private string $table;
    private ?array $cachedInmuebles = null;
    private ?AsesorModel $asesorModel = null;

    public function __construct()
    {
        parent::__construct();
        $this->client    = Dynamo::client();
        $this->marshaler = Dynamo::marshaler();
        $this->table     = Dynamo::table();
    }

    /** Listado completo (ojo: sin paginar) */
    public function obtenerTodos(): array
    {
        return $this->allInmuebles();
    }

    public function obtenerPaginados(int $limite, int $offset): array
    {
        return array_slice($this->allInmuebles(), $offset, $limite);
    }

    public function buscarPaginados(string $query, int $limite, int $offset): array
    {
        $filtrados = $this->filtrarPorTexto($query);
        return array_slice($filtrados, $offset, $limite);
    }

    public function contarBusqueda(string $query): int
    {
        return count($this->filtrarPorTexto($query));
    }

    public function contarTodos(): int
    {
        return count($this->allInmuebles());
    }

    public function contarPorArrendador(int|string $idArrendador): int
    {
        $pk = is_numeric($idArrendador)
            ? 'arr#' . (int) $idArrendador
            : (string) $idArrendador;

        $pkLower = strtolower($pk);

        return count(array_filter(
            $this->allInmuebles(),
            static fn(array $inmueble): bool => strtolower((string)($inmueble['pk'] ?? '')) === $pkLower
        ));
    }

    public function obtenerPorId(string|int $pk, ?string $sk = null): ?array
    {
        if ($sk === null) {
            // Compatibilidad legacy: si recibimos un ID numérico, consultamos la versión anterior.
            if (is_numeric($pk)) {
                return $this->obtenerPorIdLegacy((int) $pk);
            }

            if (is_string($pk) && str_contains($pk, '|')) {
                [$pk, $sk] = explode('|', $pk, 2);
            }
        }

        if ($sk === null) {
            throw new InvalidArgumentException('Se requieren pk y sk del inmueble para consultar en DynamoDB.');
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

        $inmueble = $this->normalizeInmuebleItem($result['Item']);
        $conRelacion = $this->adjuntarArrendadores([$inmueble]);

        return $conRelacion[0] ?? $inmueble;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function allInmuebles(): array
    {
        if ($this->cachedInmuebles !== null) {
            return $this->cachedInmuebles;
        }

        $items   = [];
        $lastKey = null;

        do {
            $params = [
                'TableName'                 => $this->table,
                'FilterExpression'          => 'begins_with(sk, :inmPrefix)',
                'ExpressionAttributeValues' => [
                    ':inmPrefix' => ['S' => self::INMUEBLE_PREFIX],
                ],
            ];

            if ($lastKey !== null) {
                $params['ExclusiveStartKey'] = $lastKey;
            }

            $result = $this->client->scan($params);

            foreach ($result['Items'] ?? [] as $item) {
                $items[] = $this->normalizeInmuebleItem($item);
            }

            $lastKey = $result['LastEvaluatedKey'] ?? null;
        } while ($lastKey);

        $items = $this->adjuntarArrendadores($items);

        usort(
            $items,
            static function (array $a, array $b): int {
                $fechaA = strtotime((string)($a['fecha_registro'] ?? '')) ?: 0;
                $fechaB = strtotime((string)($b['fecha_registro'] ?? '')) ?: 0;
                return $fechaB <=> $fechaA;
            }
        );

        return $this->cachedInmuebles = array_values($items);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function filtrarPorTexto(string $query): array
    {
        $needle = trim($query);
        if ($needle === '') {
            return $this->allInmuebles();
        }

        $needle = mb_strtolower($needle, 'UTF-8');

        return array_values(array_filter(
            $this->allInmuebles(),
            static function (array $inmueble) use ($needle): bool {
                foreach (['direccion_inmueble', 'nombre_arrendador', 'nombre_asesor', 'tipo'] as $campo) {
                    $valor = mb_strtolower((string)($inmueble[$campo] ?? ''), 'UTF-8');
                    if ($valor !== '' && mb_strpos($valor, $needle, 0, 'UTF-8') !== false) {
                        return true;
                    }
                }

                return false;
            }
        ));
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function normalizeInmuebleItem(array $item): array
    {
        $data = $this->marshaler->unmarshalItem($item);

        $data['pk'] = (string)($data['pk'] ?? '');
        $data['sk'] = (string)($data['sk'] ?? '');

        $data['direccion_inmueble']  = (string)($data['direccion_inmueble'] ?? '');
        $data['tipo']                = (string)($data['tipo'] ?? '');
        $data['mantenimiento']       = strtoupper((string)($data['mantenimiento'] ?? 'NO'));
        $data['mascotas']            = strtoupper((string)($data['mascotas'] ?? 'NO'));
        $data['deposito']            = (string)($data['deposito'] ?? '');
        $data['comentarios']         = (string)($data['comentarios'] ?? '');
        $data['fecha_registro']      = (string)($data['fecha_registro'] ?? '');
        $data['renta']               = $this->formatMonto($data['renta'] ?? null);
        $data['monto_mantenimiento'] = $this->formatMonto($data['monto_mantenimiento'] ?? null);
        $data['estacionamiento']     = (int)($data['estacionamiento'] ?? 0);

        return $data;
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
            $pks[] = (string)($inmueble['pk'] ?? '');
        }

        $arrendadores = $this->fetchArrendadorProfiles($pks);

        foreach ($inmuebles as &$inmueble) {
            $pkLower = strtolower((string)($inmueble['pk'] ?? ''));
            if (!isset($arrendadores[$pkLower])) {
                continue;
            }

            $profile = $arrendadores[$pkLower];

            $inmueble['nombre_arrendador'] = $profile['nombre_arrendador'] ?? '';
            if (isset($profile['id_arrendador'])) {
                $inmueble['id_arrendador'] = $profile['id_arrendador'];
            }
            if (isset($profile['asesor_pk'])) {
                $inmueble['asesor_pk'] = $profile['asesor_pk'];
            }
            if (isset($profile['nombre_asesor'])) {
                $inmueble['nombre_asesor'] = $profile['nombre_asesor'];
            }
            if (isset($profile['id_asesor'])) {
                $inmueble['id_asesor'] = $profile['id_asesor'];
            }
        }
        unset($inmueble);

        return $inmuebles;
    }

    /**
     * @param array<int, string> $pks
     * @return array<string, array<string, mixed>> keyed by pk en minúsculas
     */
    private function fetchArrendadorProfiles(array $pks): array
    {
        $unique = [];
        foreach ($pks as $pk) {
            $pk = trim((string) $pk);
            if ($pk === '') {
                continue;
            }
            $unique[strtolower($pk)] = $pk;
        }

        if ($unique === []) {
            return [];
        }

        $profiles  = [];
        $asesorPks = [];

        $this->appendArrendadoresPorSk(array_values($unique), self::PROFILE_SK, $profiles, $asesorPks);

        if (count($profiles) < count($unique)) {
            $faltantes = [];
            foreach ($unique as $lower => $original) {
                if (!isset($profiles[$lower])) {
                    $faltantes[] = $original;
                }
            }

            if ($faltantes !== []) {
                $this->appendArrendadoresPorSk($faltantes, strtoupper(self::PROFILE_SK), $profiles, $asesorPks);
            }
        }

        if ($profiles === []) {
            return [];
        }

        $asesorPks = array_values(array_unique(array_filter($asesorPks)));
        $asesores  = $asesorPks === [] ? [] : $this->getAsesorModel()->batchGetByPk($asesorPks);

        foreach ($profiles as $pkLower => &$profile) {
            $asesorPk = $profile['asesor_pk'] ?? null;
            if ($asesorPk && isset($asesores[$asesorPk])) {
                $asesor = $asesores[$asesorPk];
                $profile['nombre_asesor'] = (string)($asesor['nombre_asesor'] ?? '');
                if (isset($asesor['id'])) {
                    $profile['id_asesor'] = (int) $asesor['id'];
                }
            } else {
                $profile['nombre_asesor'] = '';
            }
        }
        unset($profile);

        return $profiles;
    }

    /**
     * @param array<int, string> $pks
     * @param string $skValue
     * @param array<string, array<string, mixed>> $profiles
     * @param array<int, string> $asesorPks
     */
    private function appendArrendadoresPorSk(array $pks, string $skValue, array &$profiles, array &$asesorPks): void
    {
        if ($pks === []) {
            return;
        }

        foreach (array_chunk($pks, 100) as $chunk) {
            $keys = [];
            foreach ($chunk as $pk) {
                $keys[] = [
                    'pk' => ['S' => $pk],
                    'sk' => ['S' => $skValue],
                ];
            }

            foreach ($this->batchGetRawItems($keys) as $item) {
                $profile = $this->marshaler->unmarshalItem($item);
                $pkValue = (string)($profile['pk'] ?? '');

                if ($pkValue === '') {
                    continue;
                }

                $pkLower = strtolower($pkValue);
                if (isset($profiles[$pkLower])) {
                    continue;
                }

                $asesorPk = $this->extractAsesorPk($profile['asesor'] ?? null);
                if ($asesorPk !== null) {
                    $asesorPks[] = $asesorPk;
                }

                $profiles[$pkLower] = [
                    'profile'           => $profile,
                    'nombre_arrendador' => (string)($profile['nombre_arrendador'] ?? ''),
                    'asesor_pk'         => $asesorPk,
                    'id_arrendador'     => isset($profile['id']) ? (int) $profile['id'] : null,
                ];
            }
        }
    }

    /**
     * @param array<int, array<string, array<string, string>>> $keys
     * @return array<int, array<string, mixed>>
     */
    private function batchGetRawItems(array $keys): array
    {
        if ($keys === []) {
            return [];
        }

        $items   = [];
        $request = ['RequestItems' => [$this->table => ['Keys' => $keys]]];

        do {
            $response = $this->client->batchGetItem($request);

            if (!empty($response['Responses'][$this->table])) {
                foreach ($response['Responses'][$this->table] as $item) {
                    $items[] = $item;
                }
            }

            if (!empty($response['UnprocessedKeys'][$this->table]['Keys'])) {
                $request = ['RequestItems' => [$this->table => ['Keys' => $response['UnprocessedKeys'][$this->table]['Keys']]]];
            } else {
                break;
            }
        } while (true);

        return $items;
    }

    private function getAsesorModel(): AsesorModel
    {
        if ($this->asesorModel === null) {
            $this->asesorModel = new AsesorModel();
        }

        return $this->asesorModel;
    }

    private function extractAsesorPk(mixed $valor): ?string
    {
        if (is_string($valor) && $valor !== '') {
            return $valor;
        }

        if (is_array($valor)) {
            if (!empty($valor['pk']) && is_string($valor['pk'])) {
                return $valor['pk'];
            }

            if (!empty($valor['id']) && is_scalar($valor['id'])) {
                return 'ase#' . (string) $valor['id'];
            }
        }

        return null;
    }

    private function formatMonto(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '0.00';
        }

        if (is_numeric($valor)) {
            return number_format((float) $valor, 2, '.', '');
        }

        return (string) $valor;
    }

    private function obtenerPorIdLegacy(int $id): ?array
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
            'mascotas'           => ['S' => strtoupper($data['mascotas'])],
            'fecha_registro'     => ['S' => date('Y-m-d H:i:s')],
        ];

        if ($data['monto_mantenimiento'] !== '' && is_numeric($data['monto_mantenimiento'])) {
            $item['monto_mantenimiento'] = ['N' => (string) $data['monto_mantenimiento']];
        }

        if (!empty($data['deposito'])) {
            $item['deposito'] = ['S' => $data['deposito']];
        }

        if (!empty($data['comentarios'])) {
            $item['comentarios'] = ['S' => $data['comentarios']];
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

            return true;
        } catch (\Throwable $e) {
            error_log("❌ Error guardando inmueble en Dynamo: " . $e->getMessage());
            return false;
        }
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

            return true;
        } catch (\Throwable $e) {
            error_log("❌ Error al eliminar inmueble: " . $e->getMessage());
            return false;
        }
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
