<?php

declare(strict_types=1);

namespace App\Models;

require_once __DIR__ . '/../Core/Dynamo.php';
require_once __DIR__ . '/../Helpers/NormalizadoHelper.php';


use App\Helpers\NormalizadoHelper;
use App\Core\Dynamo;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;

class ArrendadorModel
{
    private DynamoDbClient $client;
    private Marshaler $marshaler;
    private string $table;



    public function __construct()
    {
        $this->client    = Dynamo::client();
        $this->marshaler = Dynamo::marshaler();
        $this->table     = Dynamo::table();
    }

    private function obtenerPolizasPorIds(array $ids): array
    {
        if (empty($ids)) return [];

        $keys = [];
        foreach ($ids as $pk) {
            $keys[] = [
                'pk' => ['S' => $pk],       // ej: POL#960
                'sk' => ['S' => 'profile']
            ];
        }

        $response = $this->client->batchGetItem([
            'RequestItems' => [
                $this->table => ['Keys' => $keys]
            ]
        ]);

        $items = [];
        if (!empty($response['Responses'][$this->table])) {
            foreach ($response['Responses'][$this->table] as $item) {
                $items[] = $this->marshaler->unmarshalItem($item);
            }
        }

        return $items;
    }

    /**
     * Obtiene todos los arrendadores ordenados por fecha_registro DESC
     */
    public function obtenerTodos(): array
    {
        $arrendadores = [];
        $lastKey = null;

        do {
            $params = [
                'TableName'                 => $this->table,
                'FilterExpression'          => 'begins_with(pk, :pk) AND sk = :sk',
                'ExpressionAttributeValues' => [
                    ':pk' => ['S' => 'arr#'],
                    ':sk' => ['S' => 'profile']
                ]
            ];
            if ($lastKey) {
                $params['ExclusiveStartKey'] = $lastKey;
            }

            $result = $this->client->scan($params);

            foreach ($result['Items'] as $item) {
                $arrendadores[] = $this->marshaler->unmarshalItem($item);
            }

            $lastKey = $result['LastEvaluatedKey'] ?? null;
        } while ($lastKey);

        usort(
            $arrendadores,
            fn($a, $b) =>
            strtotime($b['fecha_registro']) <=> strtotime($a['fecha_registro'])
        );

        return $arrendadores;
    }

    /**
     * Obtiene detalles de varios items por sus IDs (batchGet)
     */
    private function obtenerItemsPorIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $keys = [];
        foreach ($ids as $id) {
            // Ej: arrfile#..., INM#..., POL#...
            $keys[] = ['pk' => ['S' => $id], 'sk' => ['S' => 'profile']];
        }

        $response = $this->client->batchGetItem([
            'RequestItems' => [
                $this->table => ['Keys' => $keys]
            ]
        ]);

        $items = [];
        foreach ($response['Responses'][$this->table] as $item) {
            $items[] = $this->marshaler->unmarshalItem($item);
        }

        return $items;
    }

    /**
     * Devuelve el archivo único de un arrendador para el tipo indicado
     */
    public function obtenerArchivoPorTipo(int $idArrendador, string $tipo): ?array
    {
        $archivos = $this->obtenerArchivos($idArrendador);
        foreach ($archivos as $archivo) {
            if (($archivo['tipo'] ?? '') === $tipo) {
                return $archivo;
            }
        }
        return null;
    }

    /**
     * Elimina de DynamoDB el archivo de un arrendador para el tipo indicado
     */
    public function eliminarArchivo(int $idArrendador, string $tipo): bool
    {
        $archivos = $this->obtenerArchivos($idArrendador);
        foreach ($archivos as $archivo) {
            if (($archivo['tipo'] ?? '') === $tipo) {
                $this->client->deleteItem([
                    'TableName' => $this->table,
                    'Key'       => [
                        'pk' => ['S' => $archivo['pk']],
                        'sk' => ['S' => $archivo['sk']]
                    ]
                ]);
                return true;
            }
        }
        return false;
    }

    /**
     * Lista todos los archivos de un arrendador
     */
    public function obtenerArchivos(int $idArrendador): array
    {
        $arr = $this->obtenerPorId($idArrendador);
        return $arr && !empty($arr['archivos_ids'])
            ? $this->obtenerItemsPorIds($arr['archivos_ids'])
            : [];
    }

    /**
     * Obtiene varios ítems asociados a un arrendador usando pk + lista de sks
     */
    private function obtenerItemsPorArrendador(string $pkArr, array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $keys = [];
        foreach ($ids as $sk) {
            $keys[] = [
                'pk' => ['S' => $pkArr], // siempre el arrendador
                'sk' => ['S' => $sk]     // ej: arrfile#58, INM#539, POL#960
            ];
        }

        $response = $this->client->batchGetItem([
            'RequestItems' => [
                $this->table => ['Keys' => $keys]
            ]
        ]);

        $items = [];
        if (!empty($response['Responses'][$this->table])) {
            foreach ($response['Responses'][$this->table] as $item) {
                $items[] = $this->marshaler->unmarshalItem($item);
            }
        }

        return $items;
    }


    /**
     * Actualiza datos personales ampliados
     */
    public function actualizarDatosPersonales(string $pk, array $data): bool
    {
        try {
            $updateExpr = "SET 
            nombre_arrendador = :n,
            email = :e,
            celular = :c,
            direccion_arrendador = :d,
            estadocivil = :ec,
            nacionalidad = :na,
            rfc = :r,
            tipo_id = :ti,
            num_id = :ni";

            $params = [
                'TableName' => $this->table,
                'Key' => [
                    'pk' => ['S' => $pk],
                    'sk' => ['S' => 'profile'],
                ],
                'UpdateExpression' => $updateExpr,
                'ExpressionAttributeValues' => [
                    ':n'  => ['S' => $data['nombre_arrendador']],
                    ':e'  => ['S' => $data['email']],
                    ':c'  => ['S' => $data['celular']],
                    ':d'  => ['S' => $data['direccion_arrendador']],
                    ':ec' => ['S' => $data['estadocivil']],
                    ':na' => ['S' => $data['nacionalidad']],
                    ':r'  => ['S' => $data['rfc']],
                    ':ti' => ['S' => $data['tipo_id']],
                    ':ni' => ['S' => $data['num_id']],
                ]
            ];

            $this->client->updateItem($params);
            return true;
        } catch (\Throwable $e) {
            error_log("Error update arrendador: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Actualiza información bancaria
     */
    public function actualizarInfoBancaria(int $id, array $data): bool
    {
        $this->client->updateItem([
            'TableName' => $this->table,
            'Key'       => [
                'pk' => ['S' => "arr#{$id}"],
                'sk' => ['S' => 'profile']
            ],
            'UpdateExpression'          => 'SET banco = :banco, cuenta = :cuenta, clabe = :clabe',
            'ExpressionAttributeValues' => [
                ':banco'  => ['S' => (string)($data['banco'] ?? '')],
                ':cuenta' => ['S' => (string)($data['cuenta'] ?? '')],
                ':clabe'  => ['S' => (string)($data['clabe'] ?? '')],
            ]
        ]);

        return true;
    }

    /**
     * Actualiza comentarios en Dynamo
     */
    public function actualizarComentarios(string $pk, string $comentarios): bool
    {
        try {
            $this->client->updateItem([
                'TableName' => $this->table,
                'Key'       => [
                    'pk' => ['S' => $pk],
                    'sk' => ['S' => 'profile']
                ],
                'UpdateExpression'          => 'SET comentarios = :comentarios',
                'ExpressionAttributeValues' => [
                    ':comentarios' => ['S' => $comentarios],
                ]
            ]);
            return true;
        } catch (\Throwable $e) {
            error_log("❌ Error al actualizar comentarios en Dynamo: " . $e->getMessage());
            return false;
        }
    }


    public function obtenerPorAsesor(string $asesorId): array
    {
        $result = $this->client->scan([
            'TableName'                 => $this->table,
            'FilterExpression'          => 'begins_with(pk, :pk) AND sk = :sk AND asesor = :asesor',
            'ExpressionAttributeValues' => [
                ':pk'     => ['S' => 'arr#'],
                ':sk'     => ['S' => 'profile'],
                ':asesor' => ['S' => $asesorId] // Ej: ase#1
            ]
        ]);

        $arrendadores = [];
        foreach ($result['Items'] as $item) {
            $arrendadores[] = $this->marshaler->unmarshalItem($item);
        }

        usort(
            $arrendadores,
            fn($a, $b) =>
            strcmp($a['nombre_arrendador'], $b['nombre_arrendador'])
        );

        return $arrendadores;
    }

    /**
     * Buscar arrendadores por nombre, email o celular
     * Incluye archivos, inmuebles, pólizas y selfie_url
     */
    public function buscar(string $q): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }

        $arrendadores = [];
        $lastKey = null;
        $qLower = NormalizadoHelper::lower($q);

        do {
            $params = [
                'TableName'                 => $this->table,
                'FilterExpression'          => 'begins_with(pk, :pk) AND sk = :sk',
                'ExpressionAttributeValues' => [
                    ':pk' => ['S' => 'arr#'],
                    ':sk' => ['S' => 'profile']
                ]
            ];
            if ($lastKey) {
                $params['ExclusiveStartKey'] = $lastKey;
            }

            $result = $this->client->scan($params);

            foreach ($result['Items'] as $item) {
                $profile = $this->marshaler->unmarshalItem($item);

                $nombre   = NormalizadoHelper::lower($profile['nombre_arrendador'] ?? '');
                $email    = NormalizadoHelper::lower($profile['email'] ?? '');
                $celular  = NormalizadoHelper::lower($profile['celular'] ?? '');

                if (
                    str_contains($nombre, $qLower) ||
                    str_contains($email, $qLower) ||
                    str_contains($celular, $qLower)
                ) {
                    // Resolver archivos e inmuebles usando pk del arrendador
                    $archivos  = !empty($profile['archivos_ids'])
                        ? $this->obtenerItemsPorArrendador($profile['pk'], $profile['archivos_ids'])
                        : [];

                    $inmuebles = !empty($profile['inmuebles_ids'])
                        ? $this->obtenerItemsPorArrendador($profile['pk'], $profile['inmuebles_ids'])
                        : [];

                    // Resolver polizas (items independientes)
                    $polizas   = !empty($profile['polizas_ids'])
                        ? $this->obtenerPolizasPorIds($profile['polizas_ids'])
                        : [];

                    // Armar estructura final
                    $arrendadores[] = [
                        'profile'    => $profile,
                        'archivos'   => $archivos,
                        'inmuebles'  => $inmuebles,
                        'polizas'    => $polizas,
                    ];
                }
            }

            $lastKey = $result['LastEvaluatedKey'] ?? null;
        } while ($lastKey);

        return $arrendadores;
    }

    /**
     * Obtiene un arrendador por ID con sus archivos, inmuebles y pólizas
     */
    public function obtenerPorId(int $id): ?array
    {
        $pk = "arr#{$id}";

        // 1. Traer el perfil
        $result = $this->client->getItem([
            'TableName' => $this->table,
            'Key'       => [
                'pk' => ['S' => $pk],
                'sk' => ['S' => 'profile']
            ]
        ]);

        if (empty($result['Item'])) {
            return null; // no existe
        }

        $profile = $this->marshaler->unmarshalItem($result['Item']);

        // 2. Resolver archivos, inmuebles y pólizas 
        $archivos = !empty($profile['archivos_ids'])
            ? $this->obtenerItemsPorArrendador($profile['pk'], $profile['archivos_ids'])
            : [];

        $inmuebles = !empty($profile['inmuebles_ids'])
            ? $this->obtenerItemsPorArrendador($profile['pk'], $profile['inmuebles_ids'])
            : [];

        $polizas = !empty($profile['polizas_ids'])
            ? $this->obtenerPolizasPorIds($profile['polizas_ids'])
            : [];

        // 3. Retornar estructura final
        return [
            'profile'    => $profile,
            'archivos'   => $archivos,
            'inmuebles'  => $inmuebles,
            'polizas'    => $polizas,
        ];
    }

}
