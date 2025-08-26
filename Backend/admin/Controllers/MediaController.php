<?php
declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . '/../Helpers/S3Helper.php';

use App\Helpers\S3Helper;

class MediaController
{
    /**
     * GET /media/presign?k=<base64url(s3_key)>&bucket=blog
     * Presigna un solo key.
     */
    public function presign(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $k64   = $_GET['k'] ?? '';
        $bkKey = $_GET['bucket'] ?? 'blog';
        $key   = $k64 ? base64_decode(strtr($k64, '-_', '+/')) : '';

        if (!$key) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'key requerido']);
            return;
        }

        // TODO: autorizar que el usuario puede leer ese key

        $s3   = new S3Helper($bkKey);
        $url  = $s3->getPresignedUrl($key, '+10 minutes', [
            'ContentDisposition' => 'inline',
        ]);

        if (!$url) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'No se pudo presignar']);
            return;
        }

        echo json_encode(['ok' => true, 'url' => $url]);
    }

    /**
     * POST /media/presign-many  (body JSON: { "keys": ["a/b.jpg","c/d.pdf"], "bucket":"blog" })
     * Devuelve arreglo de { key, url }.
     */
    public function presignMany(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $raw  = file_get_contents('php://input') ?: '{}';
        $body = json_decode($raw, true) ?: [];
        $keys = $body['keys'] ?? [];
        $bk   = $body['bucket'] ?? 'blog';

        if (!is_array($keys) || empty($keys)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'keys requerido']);
            return;
        }

        // TODO: autorizar lectura de estos keys
        $s3   = new S3Helper($bk);
        $out  = [];
        foreach ($keys as $k) {
            $k   = trim((string)$k);
            if (!$k) continue;
            $url = $s3->getPresignedUrl($k, '+10 minutes', ['ContentDisposition' => 'inline']);
            if ($url) $out[] = ['key' => $k, 'url' => $url];
        }

        echo json_encode(['ok' => true, 'items' => $out]);
    }
}
