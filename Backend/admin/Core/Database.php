<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Clase base de conexión a MySQL usando PDO.
 *
 * Características:
 * - Lee configuración desde admin/config/credentials.php (auto fallback a credentials.local.php o variables de entorno)
 * - Charset utf8mb4.
 * - Prepares nativos (ATTR_EMULATE_PREPARES = false).
 * - Helpers para consultas, transacciones y lastInsertId.
 *
 * Uso en un modelo:
 *   class MiModelo extends Database {
 *       public function listar(): array {
 *           $sql = "SELECT * FROM tabla";
 *           return $this->fetchAll($sql);
 *       }
 *   }
 */
class Database
{
    /** @var PDO */
    protected PDO $db;

    public function __construct()
    {
        $this->db = $this->getConnection();
    }

    /**
     * Devuelve la conexión PDO (por compatibilidad con tus modelos).
     */
    public function getDb(): PDO
    {
        return $this->db;
    }

    /**
     * Crea y configura la conexión PDO.
     */
    protected function getConnection(): PDO
    {
        $credentials = require __DIR__ . '/../config/credentials.php';

        if (!is_array($credentials)) {
            throw new PDOException('El archivo de credenciales debe retornar un arreglo PHP.');
        }

        $dbConfig = $credentials['database'] ?? [];

        $host       = (string) ($dbConfig['host'] ?? 'localhost');
        $port       = (string) ($dbConfig['port'] ?? '3306');
        $db         = (string) ($dbConfig['name'] ?? 'as-db');
        $user       = (string) ($dbConfig['user'] ?? 'root');
        $pass       = (string) ($dbConfig['password'] ?? '');
        $charsetRaw = (string) ($dbConfig['charset'] ?? 'utf8mb4');
        $charset    = $this->sanitizeCharset($charsetRaw);
        $sslCa   = $dbConfig['ssl_ca'] ?? null; // Ruta al CA (ej. /etc/ssl/certs/rds-ca-2019-root.pem)

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanza excepciones
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Arrays asociativos
            PDO::ATTR_EMULATE_PREPARES   => false,                  // Prepares nativos
        ];

        if (!empty($sslCa)) {
            // @phpstan-ignore-next-line (constante específica de PDO MySQL)
            $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
        }

        try {
            $pdo = new PDO($dsn, $user, $pass, $options);

            // Configuración de sesión recomendada
            $pdo->exec("SET NAMES {$charset}");
            // $pdo->exec("SET time_zone = 'America/Mexico_City'");

            return $pdo;
        } catch (PDOException $e) {
            throw new PDOException(
                'Error de conexión a la base de datos: ' . $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }

    /* =======================
       Helpers de conveniencia
       ======================= */

    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (array) $stmt->fetchAll();
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->rowCount();
    }

    public function lastInsertId(): string
    {
        return $this->db->lastInsertId();
    }

    public function beginTransaction(): void
    {
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
        }
    }

    public function commit(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->commit();
        }
    }

    public function rollBack(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }

    private function sanitizeCharset(string $value): string
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return 'utf8mb4';
        }

        $normalized = $trimmed;
        $underscorePosition = strpos($normalized, '_');

        if ($underscorePosition !== false) {
            $normalized = substr($normalized, 0, $underscorePosition);
        }

        if ($normalized === '' || !ctype_alnum($normalized)) {
            return 'utf8mb4';
        }

        return $normalized;
    }
}
