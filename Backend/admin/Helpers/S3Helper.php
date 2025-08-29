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
     * Flujo tradicional del blog: nombre aleatorio, prefijo opcional
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
     * Permite definir un Key exacto para el archivo en S3
     */
    public function uploadWithCustomKey($file, $s3Key)
    {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            error_log("Error en el archivo de subida S3: " . print_r($file, true));
            return false;
        }

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
     * Devuelve la URL pública directa (útil si el objeto es público)
     */
    public function getS3Url($key)
    {
        if (!$key) {
            return '';
        }
        return "https://{$this->bucket}.s3.{$this->s3->getRegion()}.amazonaws.com/{$key}";
    }

    /**
     * Genera una URL presignada (temporal) para leer un objeto privado en S3.
     * $expires acepta formatos como '+5 minutes', '+10 minutes', 300, etc.
     * $responseHeaders permite forzar headers de respuesta (opcional).
     *
     * Ej:
     *   $this->getPresignedUrl('blog/img.png', '+5 minutes');
     *   $this->getPresignedUrl('blog/doc.pdf', '+5 minutes', [
     *       'ContentType'        => 'image/png',
     *       'ContentDisposition' => 'inline', // o 'attachment; filename="archivo.png"'
     *       'CacheControl'       => 'private, max-age=60'
     *   ]);
     */
    public function getPresignedUrl(string $key, $expires = '+5 minutes', array $responseHeaders = []): string
    {
        if (!$key) {
            return '';
        }

        try {
            // Construimos el comando GetObject con headers de respuesta opcionales
            $commandArgs = array_filter([
                'Bucket' => $this->bucket,
                'Key'    => $key,
                // Headers de respuesta (opcionales)
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
     * Sube un archivo para un prospecto, generando un prefijo con su nombre
     */
    public function uploadProspectoFile($file, $nombreProspecto)
    {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            error_log("Error en el archivo de subida S3: " . print_r($file, true));
            return false;
        }

        // Limpiar el nombre del prospecto: solo letras/números, sin espacios, en minúsculas
        $prefix = strtolower(preg_replace('/\s+/', '', $nombreProspecto));

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
            error_log('Error al subir archivo a S3: ' . $e->getMessage());
            return false;
        }
    }

    public function uploadInquilinoFile($file, $nombreInquilino)
    {
        return $this->uploadProspectoFile($file, $nombreInquilino);
    }

        /**
     * Normaliza el "folder" del prospecto en S3: todo junto, minúsculas, sin acentos ni signos.
     * Ej.: "Alfonso Villanueva Quiroz" -> "alfonsovillanuevaquiroz"
     */
    public static function buildPersonKeyFromParts(string $nombre, string $apellidoP, ?string $apellidoM = null): string
    {
        $base = trim($nombre . ' ' . $apellidoP . ' ' . ($apellidoM ?? ''));
        // Quitar acentos
        $trans = [
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',
            'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N'
        ];
        $base = strtr($base, $trans);
        // Dejar solo [a-z0-9], sin espacios, y a minúsculas
        $key = strtolower(preg_replace('/[^a-z0-9]/i', '', $base));
        return $key;
    }

    /**
     * Expone el bucket y la región cargados desde config para reutilizarlos en otros servicios.
     */
    public function getBucketAndRegion(): array
    {
        return [
            'bucket' => $this->bucket,
            'region' => $this->s3->getRegion(),
        ];
    }

    function presignS3(string $key, int $ttl = 300): string {
        $bucket = getenv('S3_BUCKET_INQUILINOS') ?: 'as-s3-inquilinos';
        $s3 = new S3Client(['version'=>'latest','region'=>getenv('AWS_REGION') ?: 'us-east-1']);
        $cmd = $s3->getCommand('GetObject', ['Bucket'=>$bucket, 'Key'=>$key]);
        $req = $s3->createPresignedRequest($cmd, "+{$ttl} seconds");
        return (string)$req->getUri();
    }

        /**
     * Descarga un archivo desde S3 y lo devuelve como base64.
     * Útil para enviar imágenes a APIs externas (ej. VerificaMex).
     *
     * @param string $s3Key Key exacto del objeto en el bucket.
     * @return string|null Cadena base64 o null si falla.
     */
    public function getFileBase64(string $s3Key): ?string
    {
        if (!$s3Key) {
            return null;
        }

        try {
            $result  = $this->s3->getObject([
                'Bucket' => $this->bucket,
                'Key'    => $s3Key,
            ]);
            $content = (string) $result['Body'];
            $mime    = $result['ContentType'] ?? null;

            // Verificamex espera imágenes (jpeg/png) con prefijo dataURL
            if (!in_array($mime, ['image/jpeg', 'image/png'])) {
                error_log("Archivo no válido para Verificamex: {$s3Key} ({$mime})");
                return null;
            }

            return "data:{$mime};base64," . base64_encode($content);
        } catch (\Throwable $e) {
            error_log('Error al obtener archivo de S3 como base64: ' . $e->getMessage());
            return null;
        }
    }





}
