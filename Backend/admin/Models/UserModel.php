<?php
namespace App\Models;

require_once __DIR__ . '/../Core/Database.php';

use App\Core\Database;
use PDO;
use PDOException;

class UserModel extends Database
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Busca un usuario por su username exacto (columna `usuario` en `usuarios2`)
     * Retorna array asociativo o null si no existe.
     */
    public function findByUser(string $user): ?array
    {
        $sql = "SELECT id, nombre_usuario, apellidos_usuario, usuario, corto_usuario, mail_usuario, password, tipo_usuario
                FROM usuarios2
                WHERE usuario = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Crea un usuario nuevo (password hasheado con password_hash).
     * Devuelve el ID insertado.
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO usuarios2
                (nombre_usuario, apellidos_usuario, usuario, corto_usuario, mail_usuario, password, tipo_usuario)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $hashed = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['nombre_usuario'],
            $data['apellidos_usuario'],
            $data['usuario'],
            $data['corto_usuario'],
            $data['mail_usuario'],
            $hashed,
            (int)$data['tipo_usuario']
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Actualiza el password (re-hash).
     */
    public function updatePassword(int $id, string $newPassword): bool
    {
        $sql = "UPDATE usuarios2 SET password = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $id]);
    }

    /**
     * Verifica si ya existe un usuario o correo.
     */
    public function existsByUsernameOrEmail(string $usuario, string $mail): bool
    {
        $sql = "SELECT 1
                FROM usuarios2
                WHERE usuario = ? OR mail_usuario = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$usuario, $mail]);
        return (bool)$stmt->fetchColumn();
    }
}
