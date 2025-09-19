<?php

namespace App\Models;

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/AsesorModel.php';

use App\Core\Database;
use PDO;

require_once __DIR__ . '/../Core/Dynamo.php';

use App\Core\Dynamo;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use InvalidArgumentException;

class InmuebleModel extends Database
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
        parent::__construct();
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
                return $this->obtenerPorIdLegacy((int) $pk);
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

        foreach (array_chunk(array_values($unique), 100) as $chunk) {
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

    private function getAsesorModel(): AsesorModel
    {
        if ($this->asesorModel === null) {
            $this->asesorModel = new AsesorModel();
        }

        return $this->asesorModel;
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

        if (!$row) {
            return null;
        }

        $row['pk'] = (string)($row['pk'] ?? '');
        $row['sk'] = (string)($row['sk'] ?? '');

        return $row;
    }

    public function obtenerIdPorLlaves(string $pk, string $sk): ?int
    {
        $sql = 'SELECT id FROM inmuebles WHERE pk = :pk AND sk = :sk LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            ':pk' => $pk,
            ':sk' => $sk,
        ]);

        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
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
                ':estacionamiento'=> ['N' => (string) (int) $data['estacionamiento']],
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
