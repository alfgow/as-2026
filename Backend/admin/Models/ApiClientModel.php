<?php

declare(strict_types=1);

namespace App\Models;

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Helpers/ApiScopeHelper.php';

use App\Core\Database;
use App\Helpers\ApiScopeHelper;
use PDO;
use RuntimeException;

class ApiClientModel extends Database
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_REVOKED = 'revoked';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $sql = 'SELECT id, client_id, name, secret_hash, allowed_scopes, status, rate_limit_per_minute, last_used_at, created_at, updated_at'
            . ' FROM api_clients'
            . ' ORDER BY created_at DESC';

        $rows = $this->fetchAll($sql);

        return array_map(function (array $row): array {
            $mapped = $this->mapRow($row);
            unset($mapped['secret_hash']);

            return $mapped;
        }, $rows);
    }

    public function findByClientId(string $clientId): ?array
    {
        $sql  = 'SELECT * FROM api_clients WHERE client_id = :client_id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':client_id' => $clientId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapRow($row) : null;
    }

    public function findById(int $id): ?array
    {
        $sql  = 'SELECT * FROM api_clients WHERE id = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapRow($row) : null;
    }

    /**
     * @param array<int, string> $scopes
     * @return array{client_id: string, client_secret: string, name: string}
     */
    public function createClient(string $name, array $scopes, int $rateLimitPerMinute = 60, string $status = self::STATUS_ACTIVE): array
    {
        $name = trim($name);
        if ($name === '') {
            throw new RuntimeException('El nombre del cliente es obligatorio.');
        }

        $scopes = ApiScopeHelper::filter($scopes);
        if ($scopes === []) {
            throw new RuntimeException('Selecciona al menos un scope.');
        }

        if (!in_array($status, [self::STATUS_ACTIVE, self::STATUS_SUSPENDED, self::STATUS_REVOKED], true)) {
            throw new RuntimeException('Estado inválido.');
        }

        $clientId     = $this->generateUniqueClientId();
        $clientSecret = $this->generateSecret();
        $secretHash   = password_hash($clientSecret, PASSWORD_DEFAULT);

        $sql = 'INSERT INTO api_clients (client_id, name, secret_hash, allowed_scopes, status, rate_limit_per_minute, created_at, updated_at)'
            . ' VALUES (:client_id, :name, :secret_hash, :scopes, :status, :rate_limit, NOW(), NOW())';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':client_id'  => $clientId,
            ':name'       => $name,
            ':secret_hash'=> $secretHash,
            ':scopes'     => json_encode($scopes, JSON_UNESCAPED_SLASHES),
            ':status'     => $status,
            ':rate_limit' => max(1, $rateLimitPerMinute),
        ]);

        return [
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'name'          => $name,
        ];
    }

    /**
     * @return array{client_id: string, client_secret: string}
     */
    public function rotateSecret(int $id): array
    {
        $client = $this->findById($id);
        if (!$client) {
            throw new RuntimeException('Cliente no encontrado.');
        }

        $secret      = $this->generateSecret();
        $secretHash  = password_hash($secret, PASSWORD_DEFAULT);
        $sql         = 'UPDATE api_clients SET secret_hash = :secret, updated_at = NOW() WHERE id = :id LIMIT 1';
        $stmt        = $this->db->prepare($sql);
        $stmt->execute([':secret' => $secretHash, ':id' => $id]);

        return [
            'client_id'     => $client['client_id'],
            'client_secret' => $secret,
        ];
    }

    public function updateStatus(int $id, string $status): void
    {
        if (!in_array($status, [self::STATUS_ACTIVE, self::STATUS_SUSPENDED, self::STATUS_REVOKED], true)) {
            throw new RuntimeException('Estado inválido.');
        }

        $sql  = 'UPDATE api_clients SET status = :status, updated_at = NOW() WHERE id = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':status' => $status, ':id' => $id]);
    }

    public function touchLastUsed(int $id): void
    {
        $sql  = 'UPDATE api_clients SET last_used_at = NOW(), updated_at = NOW() WHERE id = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function mapRow(array $row): array
    {
        $row['allowed_scopes'] = $this->decodeScopes($row['allowed_scopes'] ?? '[]');

        return $row;
    }

    /**
     * @return array<int, string>
     */
    private function decodeScopes(string $payload): array
    {
        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            return [];
        }

        return ApiScopeHelper::filter($decoded);
    }

    private function generateUniqueClientId(): string
    {
        do {
            $candidate = 'cli_' . bin2hex(random_bytes(8));
            $exists    = $this->findByClientId($candidate);
        } while ($exists !== null);

        return $candidate;
    }

    private function generateSecret(): string
    {
        return bin2hex(random_bytes(32));
    }
}
