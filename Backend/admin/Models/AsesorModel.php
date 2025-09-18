<?php
declare(strict_types=1);

namespace App\Models;

require_once __DIR__ . '/../Core/Dynamo.php';

use App\Core\Dynamo;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;
use RuntimeException;

/**
 * Modelo de Asesores basado en DynamoDB.
 *
 * pk: ase#<id>
 * sk: profile
 *
 * Atributos principales
 * - id (N)
 * - nombre_asesor (S)
 * - email (S)
 * - celular (S, opcional)
 * - fecha_registro (S, ISO8601)
 * - inquilinos_id (SS) → lista de PKs de inquilinos asociados
 */
class AsesorModel
{
    private const PK_PREFIX  = 'ase#';
    private const COUNTER_PK = 'meta#asesor';
    private const COUNTER_SK = 'counter';
    private const PROFILE_SK = 'profile';

    private DynamoDbClient $client;
    private Marshaler $marshaler;
    private string $table;

    public function __construct()
    {
        $this->client    = Dynamo::client();
        $this->marshaler = Dynamo::marshaler();
        $this->table     = Dynamo::table();
    }

    private function buildPk(int $id): string
    {
        return self::PK_PREFIX . $id;
    }

    /**
     * Devuelve las variantes de PK soportadas (nueva y legacy).
     *
     * @return array<int, string>
     */
    private function pkCandidates(int $id): array
    {
        $pk        = $this->buildPk($id);
        $candidates = [$pk];
        $legacy    = strtoupper($pk);

        if ($legacy !== $pk) {
            $candidates[] = $legacy;
        }

        return $candidates;
    }

    /**
     * Normaliza un registro de asesor proveniente de Dynamo.
     *
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function normalizeAsesor(array $item): array
    {
        $pkValue = isset($item['pk']) ? strtolower((string) $item['pk']) : null;

        if (isset($item['id'])) {
            $item['id'] = (int) $item['id'];
        } elseif (!empty($item['pk']) && preg_match('/^ase#(\d+)$/i', (string) $item['pk'], $matches)) {
            $item['id'] = (int) $matches[1];
        }

        if ($pkValue === null && isset($item['id'])) {
            $pkValue = $this->buildPk((int) $item['id']);
        }

        if ($pkValue !== null) {
            $item['pk'] = strtolower((string) $pkValue);
        }

        if (!empty($item['inquilinos_id']) && is_array($item['inquilinos_id'])) {
            $item['inquilinos_id'] = array_values(array_map('strval', $item['inquilinos_id']));
        } else {
            $item['inquilinos_id'] = [];
        }

        unset($item['telefono']);

        return $item;
    }

    private function nextId(): int
    {
        $result = $this->client->updateItem([
            'TableName' => $this->table,
            'Key'       => [
                'pk' => ['S' => self::COUNTER_PK],
                'sk' => ['S' => self::COUNTER_SK],
            ],
            'UpdateExpression'          => 'ADD last_id :inc',
            'ExpressionAttributeValues' => [
                ':inc' => ['N' => '1'],
            ],
            'ReturnValues' => 'UPDATED_NEW',
        ]);

        $value = $result['Attributes']['last_id']['N'] ?? null;
        if ($value === null) {
            throw new RuntimeException('No fue posible generar el ID del asesor.');
        }

        return (int) $value;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $asesores = [];
        $lastKey  = null;

        do {
            $params = [
                'TableName'                 => $this->table,
                'FilterExpression'          => 'sk = :sk AND (begins_with(pk, :pkLower) OR begins_with(pk, :pkUpper))',
                'ExpressionAttributeValues' => [
                    ':sk'      => ['S' => self::PROFILE_SK],
                    ':pkLower' => ['S' => self::PK_PREFIX],
                    ':pkUpper' => ['S' => strtoupper(self::PK_PREFIX)],
                ],
            ];

            if ($lastKey) {
                $params['ExclusiveStartKey'] = $lastKey;
            }

            $result = $this->client->scan($params);
            foreach ($result['Items'] ?? [] as $item) {
                $asesores[] = $this->normalizeAsesor($this->marshaler->unmarshalItem($item));
            }

            $lastKey = $result['LastEvaluatedKey'] ?? null;
        } while ($lastKey);

        usort(
            $asesores,
            static fn(array $a, array $b): int => strcasecmp((string) ($a['nombre_asesor'] ?? ''), (string) ($b['nombre_asesor'] ?? ''))
        );

        return $asesores;
    }

    public function find(int $id): ?array
    {
        foreach ($this->pkCandidates($id) as $pk) {
            $result = $this->client->getItem([
                'TableName' => $this->table,
                'Key'       => [
                    'pk' => ['S' => $pk],
                    'sk' => ['S' => self::PROFILE_SK],
                ],
            ]);

            if (!empty($result['Item'])) {
                return $this->normalizeAsesor($this->marshaler->unmarshalItem($result['Item']));
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(string $q, int $offset = 0, int $limit = 20): array
    {
        $query    = trim($q);
        $asesores = $this->all();

        if ($query === '') {
            return array_slice($asesores, $offset, $limit);
        }

        $needle = mb_strtolower($query, 'UTF-8');
        $filtered = array_values(array_filter(
            $asesores,
            static function (array $asesor) use ($needle): bool {
                foreach (['nombre_asesor', 'email', 'celular'] as $field) {
                    $value = mb_strtolower((string) ($asesor[$field] ?? ''), 'UTF-8');
                    if ($value !== '' && mb_strpos($value, $needle, 0, 'UTF-8') !== false) {
                        return true;
                    }
                }
                return false;
            }
        ));

        return array_slice($filtered, $offset, $limit);
    }

    public function searchCount(string $q): int
    {
        return count($this->search($q, 0, PHP_INT_MAX));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $nombre = trim((string) ($data['nombre_asesor'] ?? ''));
        $email  = trim((string) ($data['email'] ?? ''));
        $cel    = trim((string) ($data['celular'] ?? ''));

        if ($nombre === '' || $email === '') {
            throw new RuntimeException('Nombre y email son obligatorios.');
        }

        if ($this->existsByName($nombre)) {
            throw new RuntimeException('El nombre del asesor ya existe.');
        }

        $attempts = 0;
        $fechaRegistro = date('c');

        while ($attempts < 25) {
            $id = $this->nextId();
            $pk = $this->buildPk($id);

            $item = [
                'pk'             => ['S' => $pk],
                'sk'             => ['S' => self::PROFILE_SK],
                'id'             => ['N' => (string) $id],
                'nombre_asesor'  => ['S' => $nombre],
                'email'          => ['S' => $email],
                'fecha_registro' => ['S' => $fechaRegistro],
            ];

            if ($cel !== '') {
                $item['celular'] = ['S' => $cel];
            }

            try {
                $this->client->putItem([
                    'TableName'           => $this->table,
                    'Item'                => $item,
                    'ConditionExpression' => 'attribute_not_exists(pk)',
                ]);

                return $id;
            } catch (DynamoDbException $e) {
                if ($e->getAwsErrorCode() !== 'ConditionalCheckFailedException') {
                    throw new RuntimeException('No se pudo guardar el asesor: ' . $e->getMessage(), 0, $e);
                }

                $attempts++;
            }
        }

        throw new RuntimeException('No se pudo guardar el asesor: no se encontró un identificador disponible.');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        $nombre = trim((string) ($data['nombre_asesor'] ?? ''));
        $email  = trim((string) ($data['email'] ?? ''));
        $cel    = trim((string) ($data['celular'] ?? ''));

        if ($nombre === '' || $email === '') {
            throw new RuntimeException('Nombre y email son obligatorios.');
        }

        if ($this->existsByName($nombre, $id)) {
            throw new RuntimeException('El nombre del asesor ya existe.');
        }

        $setParts = ['#nombre = :nombre', '#email = :email'];
        $remove   = [];
        $values   = [
            ':nombre' => ['S' => $nombre],
            ':email'  => ['S' => $email],
        ];
        $names    = [
            '#nombre' => 'nombre_asesor',
            '#email'  => 'email',
        ];

        if ($cel !== '') {
            $setParts[]         = '#celular = :celular';
            $values[':celular'] = ['S' => $cel];
            $names['#celular']  = 'celular';
        } else {
            $remove[]           = '#celular';
            $names['#celular']  = 'celular';
        }

        $expressions = [];
        if (!empty($setParts)) {
            $expressions[] = 'SET ' . implode(', ', $setParts);
        }
        if (!empty($remove)) {
            $expressions[] = 'REMOVE ' . implode(', ', $remove);
        }

        $lastException = null;
        foreach ($this->pkCandidates($id) as $pkValue) {
            try {
                $this->client->updateItem([
                    'TableName'                 => $this->table,
                    'Key'                       => [
                        'pk' => ['S' => $pkValue],
                        'sk' => ['S' => self::PROFILE_SK],
                    ],
                    'UpdateExpression'          => implode(' ', $expressions),
                    'ExpressionAttributeNames'  => $names,
                    'ExpressionAttributeValues' => $values,
                    'ConditionExpression'       => 'attribute_exists(pk) AND attribute_exists(sk)',
                ]);

                return true;
            } catch (DynamoDbException $e) {
                if ($e->getAwsErrorCode() !== 'ConditionalCheckFailedException') {
                    throw new RuntimeException('No se pudo actualizar el asesor: ' . $e->getMessage(), 0, $e);
                }
                $lastException = $e;
            }
        }

        throw new RuntimeException('No se pudo actualizar el asesor: ' . ($lastException?->getMessage() ?? 'registro inexistente.'));
    }

    public function delete(int $id): bool
    {
        if ($this->hasUsage($id)) {
            return false;
        }

        $lastException = null;
        foreach ($this->pkCandidates($id) as $pkValue) {
            try {
                $this->client->deleteItem([
                    'TableName'             => $this->table,
                    'Key'                   => [
                        'pk' => ['S' => $pkValue],
                        'sk' => ['S' => self::PROFILE_SK],
                    ],
                    'ConditionExpression' => 'attribute_exists(pk) AND attribute_exists(sk)',
                ]);

                return true;
            } catch (DynamoDbException $e) {
                if ($e->getAwsErrorCode() !== 'ConditionalCheckFailedException') {
                    throw new RuntimeException('No se pudo eliminar el asesor: ' . $e->getMessage(), 0, $e);
                }
                $lastException = $e;
            }
        }

        throw new RuntimeException('No se pudo eliminar el asesor: ' . ($lastException?->getMessage() ?? 'registro inexistente.'));
    }

    /**
     * @return array<int, array{id:int, nombre_asesor:string}>
     */
    public function forSelect(): array
    {
        return array_map(
            static fn(array $asesor): array => [
                'id'            => (int) ($asesor['id'] ?? 0),
                'nombre_asesor' => (string) ($asesor['nombre_asesor'] ?? ''),
            ],
            $this->all()
        );
    }

    public function existsByName(string $nombre_asesor, ?int $excludeId = null): bool
    {
        $lastKey = null;

        do {
            $params = [
                'TableName'                 => $this->table,
                'FilterExpression'          => 'sk = :sk AND nombre_asesor = :nombre AND (begins_with(pk, :pkLower) OR begins_with(pk, :pkUpper))',
                'ExpressionAttributeValues' => [
                    ':sk'      => ['S' => self::PROFILE_SK],
                    ':nombre'  => ['S' => $nombre_asesor],
                    ':pkLower' => ['S' => self::PK_PREFIX],
                    ':pkUpper' => ['S' => strtoupper(self::PK_PREFIX)],
                ],
            ];

            if ($lastKey) {
                $params['ExclusiveStartKey'] = $lastKey;
            }

            $result = $this->client->scan($params);
            foreach ($result['Items'] ?? [] as $item) {
                $asesor = $this->normalizeAsesor($this->marshaler->unmarshalItem($item));
                if ($excludeId === null || (int) ($asesor['id'] ?? 0) !== $excludeId) {
                    return true;
                }
            }

            $lastKey = $result['LastEvaluatedKey'] ?? null;
        } while ($lastKey);

        return false;
    }

    public function hasUsage(int $id): bool
    {
        $asesor = $this->find($id);
        if (!$asesor) {
            return false;
        }

        return !empty($asesor['inquilinos_id']);
    }

    /**
     * Intenta ejecutar una operación de actualización sobre el set de inquilinos
     * del asesor manejando posibles claves históricas en mayúsculas.
     */
    private function updateInquilinosSet(int $idAsesor, string $updateExpression, array $values, string $actionDescription): void
    {
        foreach ($this->pkCandidates($idAsesor) as $pkValue) {
            try {
                $this->client->updateItem([
                    'TableName'                 => $this->table,
                    'Key'                       => [
                        'pk' => ['S' => $pkValue],
                        'sk' => ['S' => self::PROFILE_SK],
                    ],
                    'UpdateExpression'          => $updateExpression,
                    'ExpressionAttributeValues' => $values,
                    'ConditionExpression'       => 'attribute_exists(pk) AND attribute_exists(sk)',
                ]);

                return;
            } catch (DynamoDbException $e) {
                if ($e->getAwsErrorCode() !== 'ConditionalCheckFailedException') {
                    throw new RuntimeException('No se pudo ' . $actionDescription . ' el inquilino: ' . $e->getMessage(), 0, $e);
                }
            }
        }

        throw new RuntimeException('No se pudo ' . $actionDescription . ' el inquilino porque el asesor no existe.');
    }

    /**
     * @return array{arrendadores:int,inmuebles:int,polizas:int}
     */
    public function indicadores(int $id): array
    {
        return [
            'arrendadores' => 0,
            'inmuebles'    => 0,
            'polizas'      => 0,
        ];
    }

    public function agregarInquilino(int $idAsesor, string $inquilinoPk): void
    {
        $this->updateInquilinosSet(
            $idAsesor,
            'ADD inquilinos_id :nuevo',
            [':nuevo' => ['SS' => [$inquilinoPk]]],
            'agregar'
        );
    }

    public function removerInquilino(int $idAsesor, string $inquilinoPk): void
    {
        $this->updateInquilinosSet(
            $idAsesor,
            'DELETE inquilinos_id :quitar',
            [':quitar' => ['SS' => [$inquilinoPk]]],
            'remover'
        );
    }
}
