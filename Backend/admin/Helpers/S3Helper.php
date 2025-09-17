<?php

namespace App\Helpers;

require_once __DIR__ . '/../aws-sdk-php/aws-autoloader.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

/**
 * Helper para subir/leer archivos en S3
 */
class S3Helper
{
    protected $s3;
    protected $bucket;

    public function __construct($bucketKey = 'blog')
    {
        $config       = require __DIR__ . '/../config/s3config.php';
        $config       = $config[$bucketKey];
        $this->bucket = $config['bucket'];
        $this->s3     = new S3Client([
            'version'     => 'latest',
            'region'      => $config['region'],
            'credentials' => $config['credentials'],
        ]);
    }

    /**
     * Subida simple estilo blog (aleatorio + prefijo opcional).
     * ❌ Sólo para compatibilidad con vistas viejas.
     */
    public function uploadImage($file, $prefix = 'blog')
    {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            error_log("Error en el archivo de subida S3: " . print_r($file, true));
            return false;
        }

        $filename = uniqid() . "_" . basename($file['name']);
        $s3Key    = $prefix ? "{$prefix}/{$filename}" : $filename;
        $mimeType = mime_content_type($file['tmp_name']);

        try {
            $this->s3->putObject([
                'Bucket'      => $this->bucket,
                'Key'         => $s3Key,
                'SourceFile'  => $file['tmp_name'],
                'ContentType' => $mimeType,
            ]);
            return $s3Key;
        } catch (AwsException $e) {
            error_log('Error al subir imagen a S3: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Subir archivo con un Key exacto (cuando ya lo generaste tú).
     */
    public function uploadFileWithKey(array $file, string $s3Key): bool
    {
        try {
            $this->s3->putObject([
                'Bucket'      => $this->bucket,
                'Key'         => $s3Key,
                'SourceFile'  => $file['tmp_name'],
                'ACL'         => 'private',
                'ContentType' => mime_content_type($file['tmp_name'])
            ]);
            return true;
        } catch (\Exception $e) {
            error_log("S3 upload error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Subir archivo de un inquilino usando convención oficial:
     * {nombreNormalizado}/{tipo}_{nombreNormalizado}.ext
     */
    public function uploadInquilinoFile(array $file, string $nombreNormalizado, string $tipo = 'otro'): ?string
    {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            error_log("Error en el archivo de subida S3: " . print_r($file, true));
            return null;
        }

        $ext   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $s3Key = $nombreNormalizado . '/' . $tipo . '_' . $nombreNormalizado . '.' . $ext;

        return $this->uploadFileWithKey($file, $s3Key) ? $s3Key : null;
    }

    /**
     * Devuelve la URL pública directa (útil si el objeto es público).
     */
    public function getS3Url($key): string
    {
        if (!$key) return '';
        return "https://{$this->bucket}.s3.{$this->s3->getRegion()}.amazonaws.com/{$key}";
    }

    /**
     * Genera una URL presignada (temporal) para leer un objeto privado en S3.
     */
    public function getPresignedUrl(string $key, $expires = '+5 minutes', array $responseHeaders = []): string
    {
        if (!$key) return '';

        try {
            $commandArgs = array_filter([
                'Bucket' => $this->bucket,
                'Key'    => $key,
                'ResponseContentType'        => $responseHeaders['ContentType']        ?? null,
                'ResponseContentDisposition' => $responseHeaders['ContentDisposition'] ?? null,
                'ResponseCacheControl'       => $responseHeaders['CacheControl']       ?? null,
            ]);

            $cmd     = $this->s3->getCommand('GetObject', $commandArgs);
            $request = $this->s3->createPresignedRequest($cmd, $expires);

            return (string) $request->getUri();
        } catch (\Throwable $e) {
            error_log('Error al generar URL presignada: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Normaliza un nombre para usar como "folder" en S3.
     * Ej: "Alfonso Villanueva Quiroz" -> "alfonsovillanuevaquiroz"
     */
    public static function buildPersonKeyFromParts(string $nombre, string $apellidoP, ?string $apellidoM = null): string
    {
        $base = trim($nombre . ' ' . $apellidoP . ' ' . ($apellidoM ?? ''));
        $trans = [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n',
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N'
        ];
        $base = strtr($base, $trans);
        $key  = strtolower(preg_replace('/[^a-z0-9]/i', '', $base));
        return $key;
    }

    /**
     * Exponer bucket y región
     */
    public function getBucketAndRegion(): array
    {
        return [
            'bucket' => $this->bucket,
            'region' => $this->s3->getRegion(),
        ];
    }

    /**
     * Presign alternativo directo con env vars (para pruebas rápidas).
     */
    public function presignS3(string $key, int $ttl = 300): string
    {
        $bucket = getenv('S3_BUCKET_INQUILINOS') ?: 'as-s3-inquilinos';
        $s3 = new S3Client(['version' => 'latest', 'region' => getenv('AWS_REGION') ?: 'us-east-1']);
        $cmd = $s3->getCommand('GetObject', ['Bucket' => $bucket, 'Key' => $key]);
        $req = $s3->createPresignedRequest($cmd, "+{$ttl} seconds");
        return (string)$req->getUri();
    }

    /**
     * Descarga un archivo desde S3 y lo devuelve como base64.
     */
    public function getFileBase64(string $s3Key): ?string
    {
        if (!$s3Key) return null;

        try {
            $result  = $this->s3->getObject([
                'Bucket' => $this->bucket,
                'Key'    => $s3Key,
            ]);
            $content = (string) $result['Body'];
            $mime    = $result['ContentType'] ?? null;

            if (!in_array($mime, ['image/jpeg', 'image/png'])) {
                error_log("Archivo no válido para VerificaMex: {$s3Key} ({$mime})");
                return null;
            }

            return "data:{$mime};base64," . base64_encode($content);
        } catch (\Throwable $e) {
            error_log('Error al obtener archivo de S3 como base64: ' . $e->getMessage());
            return null;
        }
    }

    public function deleteFile(string $s3Key): bool
    {
        try {
            $this->s3->deleteObject([
                'Bucket' => $this->bucket,
                'Key'    => $s3Key,
            ]);
            return true;
        } catch (\Exception $e) {
            error_log("Error al borrar archivo de S3: " . $e->getMessage());
            return false;
        }
    }

    public function uploadJson($jsonData, $prefix = 'validaciones')
    {
        if (empty($jsonData)) {
            error_log("⚠️ JSON vacío, no se sube a S3");
            return false;
        }

        // Usamos un nombre único
        $filename = uniqid() . ".json";
        $s3Key    = $prefix ? "{$prefix}/{$filename}" : $filename;

        // Convertir array/objeto en string JSON si hace falta
        if (is_array($jsonData) || is_object($jsonData)) {
            $body = json_encode($jsonData, JSON_UNESCAPED_UNICODE);
        } else {
            $body = (string)$jsonData;
        }

        try {
            $this->s3->putObject([
                'Bucket'      => $this->bucket,
                'Key'         => $s3Key,
                'Body'        => $body,
                'ContentType' => 'application/json',
            ]);
            return $s3Key;
        } catch (AwsException $e) {
            error_log('❌ Error al subir JSON a S3: ' . $e->getMessage());
            return false;
        }
    }
}
