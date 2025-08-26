<?php
declare(strict_types=1);

namespace App\Models;

require_once __DIR__ . '/../Core/Database.php';
use App\Core\Database;
use PDO;

class ProspectAccessModel extends Database
{
    public function validateJti(string $jti, string $actorType = 'inquilino'): ?array
    {
        $sql = "SELECT actor_type, actor_id, email, expires_at, used_at
                FROM prospect_update_tokens
                WHERE jti = :jti
                AND actor_type = :actorType
                AND expires_at >= NOW()
                AND used_at IS NULL
                LIMIT 1";
        $st = $this->db->prepare($sql);
        $st->execute([':jti' => $jti, ':actorType' => $actorType]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** Busca actor por email. Retorna [actor_type, actor_id, nombre] o null */
    public function resolveActorByEmail(string $email, ?string $hint = null): ?array
    {
        $email = strtolower(trim($email));

        if ($hint === 'inquilino' || $hint === 'arrendador') {
            $row = $this->findByEmail($email, $hint);
            if ($row) return [$hint, (int)$row['id'], $row['nombre']];
        } else {
            if ($row = $this->findByEmail($email, 'inquilino')) {
                return ['inquilino', (int)$row['id'], $row['nombre']];
            }
            if ($row = $this->findByEmail($email, 'arrendador')) {
                return ['arrendador', (int)$row['id'], $row['nombre']];
            }
        }
        return null;
    }

    /** Obtiene id + nombre completo por email */
    private function findByEmail(string $email, string $actorType): ?array
    {
        if ($actorType === 'inquilino') {
            $st = $this->db->prepare("
                SELECT id, 
                    CONCAT_WS(' ', nombre_inquilino, apellidop_inquilino, apellidom_inquilino) AS nombre
                FROM inquilinos
                WHERE email = ?
                LIMIT 1
            ");
        } else {
            $st = $this->db->prepare("
                SELECT id, nombre_arrendador AS nombre
                FROM arrendadores
                WHERE email = ?
                LIMIT 1
            ");
        }
        $st->execute([$email]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }


    /** Inserta registro del token (OTP+token) y devuelve el ID insertado */
    public function insertToken(array $row): int
    {
        $sql = "INSERT INTO prospect_update_tokens
                (actor_type, actor_id, email, jti, otp, otp_hash, token_hash, scope, expires_at)
                VALUES (:actor_type, :actor_id, :email, :jti, :otp, :otp_hash, :token_hash, :scope, :expires_at)";
        $st = $this->db->prepare($sql);
        $st->execute([
            ':actor_type' => $row['actor_type'],
            ':actor_id'   => $row['actor_id'],
            ':email'      => $row['email'],
            ':jti'        => $row['jti'],
            ':otp'        => $row['otp'],          // en prod podrías guardar NULL y usar solo otp_hash
            ':otp_hash'   => $row['otp_hash'],
            ':token_hash' => $row['token_hash'],
            ':scope'      => $row['scope'] ?? 'self:update',
            ':expires_at' => $row['expires_at'],
        ]);
        return (int)$this->db->lastInsertId();
    }
}
