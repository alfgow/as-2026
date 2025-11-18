<?php

declare(strict_types=1);

namespace App\Models;

require_once __DIR__ . '/../Core/Database.php';

use App\Core\Database;
use DateTimeInterface;

class ApiTokenRevocationModel extends Database
{
    public function isRevoked(string $jti): bool
    {
        $sql  = 'SELECT 1 FROM api_token_revocations WHERE jti = :jti AND expires_at > NOW() LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':jti' => $jti]);

        return (bool)$stmt->fetchColumn();
    }

    public function revoke(string $jti, string $tokenType, ?string $reason, ?string $revokedBy, DateTimeInterface $expiresAt): void
    {
        $sql = 'INSERT INTO api_token_revocations (jti, token_type, reason, revoked_by, revoked_at, expires_at)'
            . ' VALUES (:jti, :token_type, :reason, :revoked_by, NOW(), :expires_at)'
            . ' ON DUPLICATE KEY UPDATE reason = VALUES(reason), revoked_by = VALUES(revoked_by), revoked_at = NOW(), expires_at = VALUES(expires_at)';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':jti'        => $jti,
            ':token_type' => $tokenType,
            ':reason'     => $reason,
            ':revoked_by' => $revokedBy,
            ':expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);
    }
}
