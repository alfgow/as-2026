<?php

declare(strict_types=1);

namespace App\Models;

require_once __DIR__ . '/../Core/Database.php';

use App\Core\Database;
use PDO;

/**
 * Modelo auxiliar para validar claves de archivos en buckets conocidos.
 */
class MediaModel extends Database
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Verifica si una clave existe para el bucket solicitado.
     */
    public function keyExists(string $bucket, string $key): bool
    {
        $key = trim($key);
        if ($key === '') {
            return false;
        }

        $query = $this->getBucketQuery($bucket, $key);
        if (!$query) {
            return false;
        }

        [$sql, $params] = $query;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool)$stmt->fetchColumn();
    }

    /**
     * Devuelve únicamente las claves válidas que existen en el bucket indicado.
     * Mantiene el orden original recibido.
     *
     * @param array<int, string> $keys
     * @return array<int, string>
     */
    public function filterValidKeys(array $keys, string $bucket): array
    {
        $cleanKeys = [];
        foreach ($keys as $k) {
            $k = trim((string) $k);
            if ($k !== '') {
                $cleanKeys[$k] = true; // usar arreglo asociativo para deduplicar
            }
        }

        if (empty($cleanKeys)) {
            return [];
        }

        $query = $this->getBucketQuery($bucket, array_keys($cleanKeys));
        if (!$query) {
            return [];
        }

        [$sql, $params] = $query;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $found = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        if (empty($found)) {
            return [];
        }

        // Mantener el orden de entrada usando intersección manual.
        $foundMap = array_flip($found);
        $ordered = [];
        foreach ($keys as $original) {
            $original = trim((string) $original);
            if ($original !== '' && isset($foundMap[$original])) {
                $ordered[] = $original;
            }
        }

        return $ordered;
    }

    /**
     * Construye la consulta SQL para el bucket solicitado.
     *
     * @param string $bucket
     * @param string|array<int,string> $key
     * @return array{0:string,1:array<string,mixed>}|null
     */
    private function getBucketQuery(string $bucket, $key): ?array
    {
        switch ($bucket) {
            case 'inquilinos':
                $column = 's3_key';
                $table  = 'inquilinos_archivos';
                break;
            case 'arrendadores':
                $column = 's3_key';
                $table  = 'arrendadores_archivos';
                break;
            case 'blog':
                $column = 'imagen_key';
                $table  = 'blog_posts';
                break;
            default:
                return null;
        }

        if (is_array($key)) {
            $placeholders = [];
            $params       = [];
            foreach ($key as $idx => $value) {
                $placeholder = ':k' . $idx;
                $placeholders[]      = $placeholder;
                $params[$placeholder] = $value;
            }

            if (empty($placeholders)) {
                return null;
            }

            $sql = sprintf(
                'SELECT DISTINCT %s FROM %s WHERE %s IN (%s)',
                $column,
                $table,
                $column,
                implode(', ', $placeholders)
            );

            return [$sql, $params];
        }

        $sql = sprintf(
            'SELECT %s FROM %s WHERE %s = :key LIMIT 1',
            $column,
            $table,
            $column
        );

        return [$sql, [':key' => $key]];
    }
}
