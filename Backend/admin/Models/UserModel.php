<?php

declare(strict_types=1);

namespace App\Models;

require_once __DIR__ . '/../Core/Dynamo.php';

use App\Core\Dynamo;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use RuntimeException;

class UserModel
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

    private function normalizeUsername(string $username): string
    {
        return trim(mb_strtolower($username, 'UTF-8'));
    }

    private function buildPk(string $username): string
    {
        return 'usr#' . $username;
    }

    private function formatUser(array $item): array
    {
        return [
            'id'               => $item['id'] ?? ($item['usuario'] ?? null),
            'nombre_usuario'   => $item['nombre_usuario'] ?? '',
            'apellidos_usuario' => $item['apellidos_usuario'] ?? '',
            'usuario'          => $item['usuario'] ?? '',
            'corto_usuario'    => $item['corto_usuario'] ?? '',
            'mail_usuario'     => $item['mail_usuario'] ?? '',
            'password'         => $item['password'] ?? '',
            'tipo_usuario'     => (int)($item['tipo_usuario'] ?? 0),
        ];
    }

    public function findByUser(string $user): ?array
    {
        $username = $this->normalizeUsername($user);

        if ($username === '') {
            return null;
        }

        // Intento rápido: pk basado en username (nuevo esquema)
        $pk = $this->buildPk($username);
        $result = $this->client->getItem([
            'TableName' => $this->table,
            'Key'       => [
                'pk' => ['S' => $pk],
                'sk' => ['S' => 'profile'],
            ],
        ]);

        if (!empty($result['Item'])) {
            $item = $this->marshaler->unmarshalItem($result['Item']);
            return $this->formatUser($item);
        }

        // Compatibilidad con registros legacy (pk = usr#id). Escaneamos por usuario.
        $candidatos = array_unique([$username, trim($user)]);
        foreach ($candidatos as $cand) {
            if ($cand === '') {
                continue;
            }

            $scan = $this->client->scan([
                'TableName' => $this->table,
                'FilterExpression' => 'sk = :profile AND #usuario = :usuario',
                'ExpressionAttributeNames' => ['#usuario' => 'usuario'],
                'ExpressionAttributeValues' => [
                    ':profile' => ['S' => 'profile'],
                    ':usuario' => ['S' => $cand],
                ],
                'Limit' => 1,
            ]);

            if (!empty($scan['Items'])) {
                $item = $this->marshaler->unmarshalItem($scan['Items'][0]);
                return $this->formatUser($item);
            }
        }

        return null;
    }

    public function create(array $data): string
    {
        $username = $this->normalizeUsername($data['usuario'] ?? '');
        if ($username === '') {
            throw new RuntimeException('Usuario requerido');
        }

        if ($this->findByUser($username)) {
            throw new RuntimeException('El usuario ya existe');
        }

        $id    = isset($data['id']) ? (string)$data['id'] : strtoupper(bin2hex(random_bytes(6)));
        $email = trim(mb_strtolower((string)($data['mail_usuario'] ?? ''), 'UTF-8'));

        $item = [
            'pk'                => $this->buildPk($username),
            'sk'                => 'profile',
            'id'                => $id,
            'usuario'           => $username,
            'nombre_usuario'    => (string)($data['nombre_usuario'] ?? ''),
            'apellidos_usuario' => (string)($data['apellidos_usuario'] ?? ''),
            'corto_usuario'     => (string)($data['corto_usuario'] ?? ''),
            'mail_usuario'      => $email,
            'tipo_usuario'      => (int)($data['tipo_usuario'] ?? 0),
            'password'          => password_hash((string)($data['password'] ?? ''), PASSWORD_DEFAULT),
            'created_at'        => date('c'),
            'updated_at'        => date('c'),
        ];

        $this->client->putItem([
            'TableName' => $this->table,
            'Item'      => $this->marshaler->marshalItem($item),
            'ConditionExpression' => 'attribute_not_exists(pk)',
        ]);

        return $item['id'];
    }

    public function updatePassword(int|string $id, string $newPassword): bool
    {
        if ($newPassword === '') {
            return false;
        }

        $userItem = $this->findById($id);
        if (!$userItem) {
            return false;
        }

        $username = $this->normalizeUsername($userItem['usuario'] ?? '');
        if ($username === '') {
            return false;
        }

        $pk = $this->buildPk($username);

        $this->client->updateItem([
            'TableName' => $this->table,
            'Key'       => [
                'pk' => ['S' => $pk],
                'sk' => ['S' => 'profile'],
            ],
            'UpdateExpression'          => 'SET password = :password, updated_at = :updated',
            'ExpressionAttributeValues' => [
                ':password' => ['S' => password_hash($newPassword, PASSWORD_DEFAULT)],
                ':updated'  => ['S' => date('c')],
            ],
        ]);

        return true;
    }

    public function existsByUsernameOrEmail(string $usuario, string $mail): bool
    {
        $username = $this->normalizeUsername($usuario);
        if ($username !== '' && $this->findByUser($username)) {
            return true;
        }

        $mail = trim(mb_strtolower($mail, 'UTF-8'));
        if ($mail === '') {
            return false;
        }

        $result = $this->client->scan([
            'TableName' => $this->table,
            'FilterExpression' => 'sk = :profile AND mail_usuario = :mail',
            'ExpressionAttributeValues' => [
                ':profile' => ['S' => 'profile'],
                ':mail'    => ['S' => $mail],
            ],
            'ProjectionExpression' => 'pk',
            'Limit' => 1,
        ]);

        return !empty($result['Items']);
    }

    private function findById(int|string $id): ?array
    {
        $id = (string)$id;
        if ($id === '') {
            return null;
        }

        $result = $this->client->scan([
            'TableName' => $this->table,
            'FilterExpression' => 'sk = :profile AND id = :id',
            'ExpressionAttributeValues' => [
                ':profile' => ['S' => 'profile'],
                ':id'      => ['S' => $id],
            ],
            'Limit' => 1,
        ]);

        if (empty($result['Items'])) {
            return null;
        }

        $item = $this->marshaler->unmarshalItem($result['Items'][0]);
        return $this->formatUser($item);
    }
}
