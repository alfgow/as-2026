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

    private function ensurePk(array &$item): void
    {
        if (!isset($item['pk']) && isset($item['id'])) {
            $item['pk'] = $this->buildPk((int) $item['id']);
        }
    }

    /**
     * Normaliza un registro de asesor proveniente de Dynamo.
     *
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function normalizeAsesor(array $item): array
    {
        if (isset($item['id'])) {
            $item['id'] = (int) $item['id'];
        } elseif (!empty($item['pk']) && preg_match('/^ase#(\d+)$/i', (string) $item['pk'], $matches)) {
            $item['id'] = (int) $matches[1];
        }

        $this->ensurePk($item);

        if (!empty($item['inquilinos_id']) && is_array($item['inquilinos_id'])) {
            $item['inquilinos_id'] = array_values(array_map('strval', $item['inquilinos_id']));
        } else {
            $item['inquilinos_id'] = [];
        }

        $item['inquilinos_total']    = count($item['inquilinos_id']);
        $item['arrendadores_total'] = (int) ($item['arrendadores_total'] ?? 0);

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
                'FilterExpression'          => 'begins_with(pk, :pk) AND sk = :sk',
                'ExpressionAttributeValues' => [
                    ':pk' => ['S' => self::PK_PREFIX],
                    ':sk' => ['S' => self::PROFILE_SK],
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

        $asesores = $this->attachAssignmentsSummary($asesores);

        usort(
            $asesores,
            static fn(array $a, array $b): int => strcasecmp((string) ($a['nombre_asesor'] ?? ''), (string) ($b['nombre_asesor'] ?? ''))
        );

        return $asesores;
    }

    public function find(int $id): ?array
    {
        $result = $this->client->getItem([
            'TableName' => $this->table,
            'Key'       => [
                'pk' => ['S' => $this->buildPk($id)],
                'sk' => ['S' => self::PROFILE_SK],
            ],
        ]);

        if (empty($result['Item'])) {
            return null;
        }

        $asesor = $this->normalizeAsesor($this->marshaler->unmarshalItem($result['Item']));

        $withAssignments = $this->attachAssignmentsSummary([$asesor]);

        return $withAssignments[0] ?? $asesor;
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

    public function existsByEmailOrPhone(string $email, ?string $celular = null): bool
    {
        $email = mb_strtolower(trim($email), 'UTF-8');
        $celular = $celular ? mb_strtolower(trim($celular), 'UTF-8') : null;

        $expr = 'email = :email';
        $eav = [':email' => ['S' => $email]];

        if ($celular) {
            $expr .= ' OR celular = :celular';
            $eav[':celular'] = ['S' => $celular];
        }

        $result = $this->client->scan([
            'TableName' => $this->table,
            'FilterExpression' => $expr,
            'ExpressionAttributeValues' => $eav,
            'Limit' => 1,
        ]);

        return !empty($result['Items']);
    }


    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $nombre    = trim((string) ($data['nombre_asesor'] ?? ''));
        $emailRaw  = trim((string) ($data['email'] ?? ''));
        $email     = mb_strtolower($emailRaw, 'UTF-8');
        $cel    = trim((string) ($data['celular'] ?? ''));
        if ($nombre === '' || $email === '') {
            throw new RuntimeException('Nombre y email son obligatorios.');
        }

        if ($this->existsByName($nombre)) {
            throw new RuntimeException('El nombre del asesor ya existe.');
        }

        if ($this->existsByEmail($email)) {
            throw new RuntimeException('El correo electrónico del asesor ya existe.');
        }

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $id = $this->nextId();

            if ($this->find($id) !== null) {
                continue;
            }

            $pk = $this->buildPk($id);
            $item = [
                'pk'             => ['S' => $pk],
                'sk'             => ['S' => self::PROFILE_SK],
                'id'             => ['N' => (string) $id],
                'nombre_asesor'  => ['S' => $nombre],
                'email'          => ['S' => $email],
                'fecha_registro' => ['S' => date('c')],
            ];

            if ($cel !== '') {
                $item['celular'] = ['S' => $cel];
            }

            try {
                $this->client->putItem([
                    'TableName'           => $this->table,
                    'Item'                => $item,
                    'ConditionExpression' => 'attribute_not_exists(pk) AND attribute_not_exists(sk)',
                ]);

                return $id;
            } catch (DynamoDbException $e) {
                if ($e->getAwsErrorCode() !== 'ConditionalCheckFailedException') {
                    throw new RuntimeException('No se pudo guardar el asesor: ' . $e->getMessage(), 0, $e);
                }
            }
        }

        throw new RuntimeException('No se pudo generar un identificador único para el asesor.');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        $nombre    = trim((string) ($data['nombre_asesor'] ?? ''));
        $emailRaw  = trim((string) ($data['email'] ?? ''));
        $email     = mb_strtolower($emailRaw, 'UTF-8');
        $cel    = trim((string) ($data['celular'] ?? ''));
        if ($nombre === '' || $email === '') {
            throw new RuntimeException('Nombre y email son obligatorios.');
        }

        if ($this->existsByName($nombre, $id)) {
            throw new RuntimeException('El nombre del asesor ya existe.');
        }

        if ($this->existsByEmail($email, $id)) {
            throw new RuntimeException('El correo electrónico del asesor ya existe.');
        }

        $setParts = ['#nombre = :nombre', '#email = :email'];
        $remove   = ['#telefono'];
        $values   = [
            ':nombre' => ['S' => $nombre],
            ':email'  => ['S' => $email],
        ];
        $names    = [
            '#nombre' => 'nombre_asesor',
            '#email'  => 'email',
            '#telefono' => 'telefono',
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

        try {
            $this->client->updateItem([
                'TableName'                 => $this->table,
                'Key'                       => [
                    'pk' => ['S' => $this->buildPk($id)],
                    'sk' => ['S' => self::PROFILE_SK],
                ],
                'UpdateExpression'          => implode(' ', $expressions),
                'ExpressionAttributeNames'  => $names,
                'ExpressionAttributeValues' => $values,
            ]);
        } catch (DynamoDbException $e) {
            throw new RuntimeException('No se pudo actualizar el asesor: ' . $e->getMessage(), 0, $e);
        }

        return true;
    }

    public function delete(int $id): bool
    {
        if ($this->hasUsage($id)) {
            return false;
        }

        try {
            $this->client->deleteItem([
                'TableName' => $this->table,
                'Key'       => [
                    'pk' => ['S' => $this->buildPk($id)],
                    'sk' => ['S' => self::PROFILE_SK],
                ],
            ]);
        } catch (DynamoDbException $e) {
            throw new RuntimeException('No se pudo eliminar el asesor: ' . $e->getMessage(), 0, $e);
        }

        return true;
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
                'FilterExpression'          => 'begins_with(pk, :pk) AND sk = :sk AND nombre_asesor = :nombre',
                'ExpressionAttributeValues' => [
                    ':pk'     => ['S' => self::PK_PREFIX],
                    ':sk'     => ['S' => self::PROFILE_SK],
                    ':nombre' => ['S' => $nombre_asesor],
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

    public function existsByEmail(string $email, ?int $excludeId = null): bool
    {
        $emailLookup = mb_strtolower(trim($email), 'UTF-8');
        $lastKey = null;

        do {
            $params = [
                'TableName'                 => $this->table,
                'FilterExpression'          => 'begins_with(pk, :pk) AND sk = :sk',
                'ExpressionAttributeValues' => [
                    ':pk'    => ['S' => self::PK_PREFIX],
                    ':sk'    => ['S' => self::PROFILE_SK],
                ],
            ];

            if ($lastKey) {
                $params['ExclusiveStartKey'] = $lastKey;
            }

            $result = $this->client->scan($params);
            foreach ($result['Items'] ?? [] as $item) {
                $asesor = $this->normalizeAsesor($this->marshaler->unmarshalItem($item));
                $emailActual = mb_strtolower((string) ($asesor['email'] ?? ''), 'UTF-8');
                if ($emailActual === $emailLookup && ($excludeId === null || (int) ($asesor['id'] ?? 0) !== $excludeId)) {
                    return true;
                }
            }

            $lastKey = $result['LastEvaluatedKey'] ?? null;
        } while ($lastKey);

        return false;
    }

    /**
     * @param array<int, array<string, mixed>> $asesores
     * @return array<int, array<string, mixed>>
     */
    private function attachAssignmentsSummary(array $asesores): array
    {
        if (empty($asesores)) {
            return $asesores;
        }

        $pks = [];
        foreach ($asesores as $asesor) {
            $id = (int) ($asesor['id'] ?? 0);
            $pk = (string) ($asesor['pk'] ?? '');
            if ($pk === '' && $id > 0) {
                $pk = $this->buildPk($id);
            }
            if ($pk !== '') {
                $pks[$pk] = true;
            }
        }

        $uniquePks = array_keys($pks);

        if (count($uniquePks) === 1) {
            $arrendadores = $this->countArrendadores($uniquePks[0]);
            $inquilinos   = $this->countInquilinos($uniquePks[0]);
        } else {
            $arrendadores = $this->countArrendadores();
            $inquilinos   = $this->countInquilinos();
        }

        foreach ($asesores as &$asesor) {
            $id = (int) ($asesor['id'] ?? 0);
            $pk = (string) ($asesor['pk'] ?? '');

            if ($pk === '' && $id > 0) {
                $pk          = $this->buildPk($id);
                $asesor['pk'] = $pk;
            }

            $asesor['arrendadores_total'] = $arrendadores[$pk] ?? (int) ($asesor['arrendadores_total'] ?? 0);
            $asesor['inquilinos_total']   = $inquilinos[$pk] ?? (is_array($asesor['inquilinos_id']) ? count($asesor['inquilinos_id']) : 0);
        }

        unset($asesor);

        return $asesores;
    }

    /**
     * @return array<string, int>
     */
    private function countArrendadores(?string $targetPk = null): array
    {
        $counts = [];
        $lastKey = null;

        $values = [
            ':pk' => ['S' => 'arr#'],
            ':sk' => ['S' => self::PROFILE_SK],
        ];

        do {
            $params = [
                'TableName'                 => $this->table,
                'FilterExpression'          => 'begins_with(pk, :pk) AND sk = :sk',
                'ExpressionAttributeValues' => $values,
                'ProjectionExpression'      => '#asesor, asesor_pk, asesor_id',
                'ExpressionAttributeNames'  => ['#asesor' => 'asesor'],
            ];

            if ($lastKey !== null) {
                $params['ExclusiveStartKey'] = $lastKey;
            }

            $result = $this->client->scan($params);
            foreach ($result['Items'] ?? [] as $item) {
                $arrendador = $this->marshaler->unmarshalItem($item);

                $asesorPk = '';
                if (!empty($arrendador['asesor'])) {
                    $asesorPk = (string) $arrendador['asesor'];
                } elseif (!empty($arrendador['asesor_pk'])) {
                    $asesorPk = (string) $arrendador['asesor_pk'];
                } elseif (!empty($arrendador['asesor_id'])) {
                    $asesorPk = $this->buildPk((int) $arrendador['asesor_id']);
                }

                if ($asesorPk === '') {
                    continue;
                }

                if ($targetPk !== null && $asesorPk !== $targetPk) {
                    continue;
                }

                $counts[$asesorPk] = ($counts[$asesorPk] ?? 0) + 1;
            }

            $lastKey = $result['LastEvaluatedKey'] ?? null;
        } while ($lastKey);

        return $counts;
    }

    /**
     * @return array<string, int>
     */
    private function countInquilinos(?string $targetPk = null): array
    {
        $counts = [];
        $lastKey = null;

        $values = [
            ':pk' => ['S' => 'inq#'],
            ':sk' => ['S' => self::PROFILE_SK],
        ];

        do {
            $params = [
                'TableName'                 => $this->table,
                'FilterExpression'          => 'begins_with(pk, :pk) AND sk = :sk',
                'ExpressionAttributeValues' => $values,
                'ProjectionExpression'      => 'asesor_pk, asesor_id, #asesor',
                'ExpressionAttributeNames'  => ['#asesor' => 'asesor'],
            ];

            if ($lastKey !== null) {
                $params['ExclusiveStartKey'] = $lastKey;
            }

            $result = $this->client->scan($params);
            foreach ($result['Items'] ?? [] as $item) {
                $inquilino = $this->marshaler->unmarshalItem($item);

                $asesorPk = '';
                if (!empty($inquilino['asesor_pk'])) {
                    $asesorPk = (string) $inquilino['asesor_pk'];
                } elseif (!empty($inquilino['asesor']['pk'])) {
                    $asesorPk = (string) $inquilino['asesor']['pk'];
                } elseif (!empty($inquilino['asesor_id'])) {
                    $asesorPk = $this->buildPk((int) $inquilino['asesor_id']);
                }

                if ($asesorPk === '') {
                    continue;
                }

                if ($targetPk !== null && $asesorPk !== $targetPk) {
                    continue;
                }

                $counts[$asesorPk] = ($counts[$asesorPk] ?? 0) + 1;
            }

            $lastKey = $result['LastEvaluatedKey'] ?? null;
        } while ($lastKey);

        return $counts;
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
        $this->client->updateItem([
            'TableName' => $this->table,
            'Key'       => [
                'pk' => ['S' => $this->buildPk($idAsesor)],
                'sk' => ['S' => self::PROFILE_SK],
            ],
            'UpdateExpression'          => 'ADD inquilinos_id :nuevo',
            'ExpressionAttributeValues' => [
                ':nuevo' => ['SS' => [$inquilinoPk]],
            ],
        ]);
    }

    public function removerInquilino(int $idAsesor, string $inquilinoPk): void
    {
        $this->client->updateItem([
            'TableName' => $this->table,
            'Key'       => [
                'pk' => ['S' => $this->buildPk($idAsesor)],
                'sk' => ['S' => self::PROFILE_SK],
            ],
            'UpdateExpression'          => 'DELETE inquilinos_id :quitar',
            'ExpressionAttributeValues' => [
                ':quitar' => ['SS' => [$inquilinoPk]],
            ],
        ]);
    }
}
