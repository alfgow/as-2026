<?php

declare(strict_types=1);

namespace App\Models;

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Helpers/TextHelper.php';

use App\Core\Database;
use App\Helpers\TextHelper;
use PDO;
use RuntimeException;

class UserModel extends Database
{
    private string $table = 'usuarios2';

    public function __construct()
    {
        parent::__construct();
    }

    private function normalizeUsername(string $username): string
    {
        return trim(mb_strtolower($username, 'UTF-8'));
    }

    private function normalizeEmail(string $email): string
    {
        return trim(mb_strtolower($email, 'UTF-8'));
    }

    private function formatUser(array $row): array
    {
        return [
            'id'                => isset($row['id']) ? (string)$row['id'] : null,
            'nombre_usuario'    => $row['nombre_usuario'] ?? '',
            'apellidos_usuario' => $row['apellidos_usuario'] ?? '',
            'usuario'           => $row['usuario'] ?? '',
            'corto_usuario'     => $row['corto_usuario'] ?? '',
            'mail_usuario'      => $row['mail_usuario'] ?? '',
            'password'          => $row['password'] ?? '',
            'tipo_usuario'      => isset($row['tipo_usuario']) ? (int)$row['tipo_usuario'] : 0,
        ];
    }

    public function findByUser(string $user): ?array
    {
        $username = $this->normalizeUsername($user);

        if ($username === '') {
            return null;
        }

        $sql = "SELECT id, nombre_usuario, apellidos_usuario, usuario, corto_usuario, mail_usuario, password, tipo_usuario
                FROM {$this->table}
                WHERE LOWER(usuario) = :usuario
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':usuario' => $username]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->formatUser($row) : null;
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

        $email = $this->normalizeEmail((string)($data['mail_usuario'] ?? ''));
        if ($email !== '' && $this->existsByUsernameOrEmail('', $email)) {
            throw new RuntimeException('El correo ya está registrado');
        }

        $password = password_hash((string)($data['password'] ?? ''), PASSWORD_DEFAULT);

        $sql = "INSERT INTO {$this->table}
                (nombre_usuario, apellidos_usuario, usuario, corto_usuario, mail_usuario, password, tipo_usuario)
                VALUES (:nombre, :apellidos, :usuario, :corto, :mail, :password, :tipo)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre'    => TextHelper::titleCase((string)($data['nombre_usuario'] ?? '')),
            ':apellidos' => TextHelper::titleCase((string)($data['apellidos_usuario'] ?? '')),
            ':usuario'   => $username,
            ':corto'     => TextHelper::titleCase((string)($data['corto_usuario'] ?? '')),
            ':mail'      => $email,
            ':password'  => $password,
            ':tipo'      => (int)($data['tipo_usuario'] ?? 0),
        ]);

        return $this->lastInsertId();
    }

    public function updatePassword(int|string $id, string $newPassword): bool
    {
        if ($newPassword === '') {
            return false;
        }

        $user = $this->findById($id);
        if ($user === null || empty($user['id'])) {
            return false;
        }

        $sql = "UPDATE {$this->table}
                SET password = :password
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':password' => password_hash($newPassword, PASSWORD_DEFAULT),
            ':id'       => (int)$user['id'],
        ]);
    }

    public function existsByUsernameOrEmail(string $usuario, string $mail): bool
    {
        $conditions = [];
        $params     = [];

        $username = $this->normalizeUsername($usuario);
        if ($username !== '') {
            $conditions[]      = 'LOWER(usuario) = :usuario';
            $params[':usuario'] = $username;
        }

        $email = $this->normalizeEmail($mail);
        if ($email !== '') {
            $conditions[]   = 'LOWER(mail_usuario) = :mail';
            $params[':mail'] = $email;
        }

        if (!$conditions) {
            return false;
        }

        $sql = "SELECT 1 FROM {$this->table} WHERE " . implode(' OR ', $conditions) . " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool)$stmt->fetchColumn();
    }

    public function findByIdAsArray(int|string $id): ?array
    {
        return $this->findById($id);
    }

    private function findById(int|string $id): ?array
    {
        if (!is_numeric($id)) {
            return null;
        }

        $sql = "SELECT id, nombre_usuario, apellidos_usuario, usuario, corto_usuario, mail_usuario, password, tipo_usuario
                FROM {$this->table}
                WHERE id = :id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => (int)$id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->formatUser($row) : null;
    }
}
