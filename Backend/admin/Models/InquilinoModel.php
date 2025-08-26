<?php

namespace App\Models;

require_once __DIR__ . '/../Core/Database.php';

use App\Core\Database;
use PDO;


/**
 * InquilinoModel
 *
 * Capa de acceso a datos para inquilinos y sus entidades relacionadas.
 * Basado en el esquema actual (tabla principal: `inquilinos`).
 *
 * Consideraciones:
 * - Usa PDO con prepares nativos (ver App\Core\Database).
 * - Este modelo NO incluye lógica de migración.
 * - Campos sensibles a longitudes (según tu esquema):
 *   - inquilinos.nacionalidad: VARCHAR(20)
 *   - inquilinos.tipo_id: VARCHAR(20)
 *   - inquilinos.num_id: VARCHAR(255)
 *   - inquilinos.email: VARCHAR(50)
 *   - inquilinos.celular: VARCHAR(20)
 */
class InquilinoModel extends Database
{
    public function __construct()
    {
        parent::__construct(); // inicializa $this->db (PDO)
    }

    /* =========================================================
     *  UTILIDADES (limpieza, slug, S3, archivos)
     * ========================================================= */
    private ?string $lastSql    = null;
    private ?array  $lastParams = null;
    private ?string $lastError  = null;

public function getLastSql(): ?string   { return $this->lastSql; }
public function getLastParams(): ?array { return $this->lastParams; }
public function getLastError(): ?string { return $this->lastError; }
    /**
     * Genera un slug básico a partir de nombre y apellidos.
     */
    private function generarSlug(string $nombre, string $apellidoP, string $apellidoM = ''): string
    {
        $texto = trim($nombre . ' ' . $apellidoP . ' ' . $apellidoM);
        $reemplazos = [
            'ñ' => 'n', 'Ñ' => 'n',
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u',
            'ü' => 'u', 'Ü' => 'u'
        ];
        $texto = strtr($texto, $reemplazos);
        $slug  = strtolower($texto);
        $slug  = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-');
    }

    private function cleanMoney($v): ?int
    {
        if ($v === null || $v === '') return null;
        return (int)preg_replace('/[^\d]/', '', (string)$v);
    }

    private function cleanPhone($v): ?string
    {
        if ($v === null || $v === '') return null;
        $digits = preg_replace('/\D+/', '', (string)$v);
        return $digits ?: null;
    }

    private function cleanText($v): ?string
    {
        if ($v === null) return null;
        return is_string($v) ? trim($v) : (string)$v;
    }

    /**
     * Genera URL firmada temporal (15 min) para un objeto S3 en el bucket de inquilinos.
     * Requiere AWS SDK y credenciales configuradas en config/s3config.php
     */
    public static function getS3Url(string $s3_key): string
    {
        static $s3config = null;
        if ($s3config === null) {
            $s3config = require(__DIR__ . '/../config/s3config.php');
        }
        $cfg = $s3config['inquilinos'];

        $s3 = new \Aws\S3\S3Client([
            'version'     => 'latest',
            'region'      => $cfg['region'],
            'credentials' => $cfg['credentials'],
        ]);

        $cmd = $s3->getCommand('GetObject', [
            'Bucket' => $cfg['bucket'],
            'Key'    => $s3_key,
        ]);
        $request = $s3->createPresignedRequest($cmd, '+15 minutes');
        return (string) $request->getUri();
    }

    /**
     * Devuelve s3_key de archivos de identidad (selfie/INE) por inquilino.
     *
     * @return array<string,string> Mapa tipo => s3_key
     */
    public function obtenerArchivosIdentidad(int $id_inquilino): array
    {
        $stmt = $this->db->prepare(
            "SELECT tipo, s3_key 
             FROM inquilinos_archivos 
             WHERE id_inquilino = ? 
               AND tipo IN ('selfie','ine_frontal','ine_reverso')"
        );
        $stmt->execute([$id_inquilino]);

        $out = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out[$row['tipo']] = $row['s3_key'];
        }
        return $out;
    }

    // aws validation
    public function archivosPorInquilinoId($idInquilino)
    {
        $sql = "SELECT tipo, s3_key, mime_type, size
                FROM inquilinos_archivos
                WHERE id_inquilino = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idInquilino]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function crearFilaValidacionesSiNoExiste(int $id_inquilino): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM inquilinos_validaciones WHERE id_inquilino = ? LIMIT 1");
        $stmt->execute([$id_inquilino]);
        if ($stmt->fetch(PDO::FETCH_NUM)) {
            return true;
        }
        $ins = $this->db->prepare("INSERT INTO inquilinos_validaciones (id_inquilino) VALUES (?)");
        return $ins->execute([$id_inquilino]);
    }

    /**
     * Guarda o actualiza la validación de identidad
     *
     * @param int    $id_inquilino
     * @param int    $estatus             Estado del proceso (ej. 1=ok, 0=fallo)
     * @param string $resumen             Texto legible de la validación
     * @param array  $datos               JSON completo de la validación
     * @return bool
     */
    public function guardarValidacionIdentidad(int $id_inquilino, int $estatus, string $resumen = '', array $datos = []): bool
    {
        $json = !empty($datos) ? json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

        // Primero intentamos actualizar
        $stmt = $this->db->prepare("
            UPDATE inquilinos_validaciones
            SET proceso_validacion_id = :estatus,
                validacion_id_resumen  = :resumen,
                validacion_id_json     = :json,
                updated_at = CURRENT_TIMESTAMP
            WHERE id_inquilino = :id
        ");
        $stmt->execute([
            ':estatus' => $estatus,
            ':resumen' => $resumen,
            ':json'    => $json,
            ':id'      => $id_inquilino
        ]);

        // Si no existía registro, insertamos uno nuevo
        if ($stmt->rowCount() === 0) {
            $stmt = $this->db->prepare("
                INSERT INTO inquilinos_validaciones
                    (id_inquilino, proceso_validacion_id, validacion_id_resumen, validacion_id_json)
                VALUES
                    (:id, :estatus, :resumen, :json)
            ");
            return $stmt->execute([
                ':id'      => $id_inquilino,
                ':estatus' => $estatus,
                ':resumen' => $resumen,
                ':json'    => $json
            ]);
        }

        return true;
    }


    /**
     * Lee el estado de todas las validaciones para un inquilino y devuelve
     * un arreglo estructurado con proceso (0/1/2), resumen (texto) y json (array).
     */
    public function obtenerValidaciones(int $id_inquilino): array
    {
        $sql = "SELECT
                    -- Archivos
                    proceso_validacion_archivos,
                    validacion_archivos_resumen,
                    validacion_archivos_json,
                    -- Rostro
                    proceso_validacion_rostro,
                    validacion_rostro_resumen,
                    validacion_rostro_json,
                    -- Identidad (nombres / curp-cic)
                    proceso_validacion_id,
                    validacion_id_resumen,
                    validacion_id_json,
                    -- Documentos (OCR INE líneas)
                    proceso_validacion_documentos,
                    validacion_documentos_resumen,
                    validacion_documentos_json,
                    -- Ingresos (simple/ocr/list)
                    proceso_validacion_ingresos,
                    validacion_ingresos_resumen,
                    validacion_ingresos_json,
                    -- Pago inicial
                    proceso_pago_inicial,
                    pago_inicial_resumen,
                    pago_inicial_json,
                    -- Investigación de demandas
                    proceso_inv_demandas,
                    inv_demandas_resumen,
                    inv_demandas_json,
                    -- Timestamps
                    created_at,
                    updated_at
                FROM inquilinos_validaciones
                WHERE id_inquilino = ?
                LIMIT 1";

        $st = $this->db->prepare($sql);
        $st->execute([$id_inquilino]);
        $row = $st->fetch(\PDO::FETCH_ASSOC);

        // Si no hay fila, devolver estructura por defecto (todo PENDIENTE)
        if (!$row) {
            $def = function() {
                return ['proceso' => 2, 'resumen' => null, 'json' => null];
            };
            return [
                'archivos'     => $def(),
                'rostro'       => $def(),
                'identidad'    => $def(),
                'documentos'   => $def(),
                'ingresos'     => $def(),
                'pago_inicial' => $def(),
                'demandas'     => $def(),
                'meta'         => ['created_at' => null, 'updated_at' => null],
            ];
        }

        // Helper para decodificar JSON seguro
        $j = function($s) {
            if ($s === null || $s === '') return null;
            $d = json_decode($s, true);
            return (json_last_error() === JSON_ERROR_NONE) ? $d : ['_error' => 'json_decode_failed'];
        };

        return [
            'archivos' => [
                'proceso' => (int)($row['proceso_validacion_archivos'] ?? 2),
                'resumen' => $row['validacion_archivos_resumen'] ?? null,
                'json'    => $j($row['validacion_archivos_json'] ?? null),
            ],
            'rostro' => [
                'proceso' => (int)($row['proceso_validacion_rostro'] ?? 2),
                'resumen' => $row['validacion_rostro_resumen'] ?? null,
                'json'    => $j($row['validacion_rostro_json'] ?? null),
            ],
            'identidad' => [
                'proceso' => (int)($row['proceso_validacion_id'] ?? 2),
                'resumen' => $row['validacion_id_resumen'] ?? null,
                'json'    => $j($row['validacion_id_json'] ?? null),
            ],
            'documentos' => [
                'proceso' => (int)($row['proceso_validacion_documentos'] ?? 2),
                'resumen' => $row['validacion_documentos_resumen'] ?? null,
                'json'    => $j($row['validacion_documentos_json'] ?? null),
            ],
            'ingresos' => [
                'proceso' => (int)($row['proceso_validacion_ingresos'] ?? 2),
                'resumen' => $row['validacion_ingresos_resumen'] ?? null,
                'json'    => $j($row['validacion_ingresos_json'] ?? null),
            ],
            'pago_inicial' => [
                'proceso' => (int)($row['proceso_pago_inicial'] ?? 2),
                'resumen' => $row['pago_inicial_resumen'] ?? null,
                'json'    => $j($row['pago_inicial_json'] ?? null),
            ],
            'demandas' => [
                'proceso' => (int)($row['proceso_inv_demandas'] ?? 2),
                'resumen' => $row['inv_demandas_resumen'] ?? null,
                'json'    => $j($row['inv_demandas_json'] ?? null),
            ],
            'meta' => [
                'created_at' => $row['created_at'] ?? null,
                'updated_at' => $row['updated_at'] ?? null,
            ],
        ];
    }


/**
 * Devuelve los comprobantes de ingreso del inquilino (solo PDFs).
 * Estructura similar a la que ya usas en otros métodos.
 */
public function obtenerComprobantesIngreso(int $id_inquilino): array
{
    $stmt = $this->db->prepare(
        "SELECT id, s3_key, mime_type, size
           FROM inquilinos_archivos
          WHERE id_inquilino = ?
            AND tipo = 'comprobante_ingreso'"
    );
    $stmt->execute([$id_inquilino]);

    $items = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $ext = strtolower(pathinfo($row['s3_key'] ?? '', PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            // Si en el front sólo permites PDF, saltamos cualquier otro tipo.
            continue;
        }
        $items[] = [
            'id'     => (int)$row['id'],
            's3_key' => $row['s3_key'],
            'ext'    => $ext,
            'mime'   => $row['mime_type'] ?: null,
            'size'   => isset($row['size']) ? (int)$row['size'] : null,
        ];
    }
    return $items;
}

/**
 * Asegura que exista la fila de validaciones del inquilino.
 * (Útil si alguna vez no se ha creado todavía.)
 */
private function ensureFilaValidaciones(int $id_inquilino): void
{
    $q = $this->db->prepare("SELECT id FROM inquilinos_validaciones WHERE id_inquilino = ? LIMIT 1");
    $q->execute([$id_inquilino]);
    if (!$q->fetch(PDO::FETCH_ASSOC)) {
        $ins = $this->db->prepare("INSERT INTO inquilinos_validaciones (id_inquilino) VALUES (?)");
        $ins->execute([$id_inquilino]);
    }
}

    /**
     * Guarda la validación de INGRESOS (simple por conteo de PDFs):
     * proceso (0/1/2), resumen humano y payload JSON.
     *
     * @param int      $id_inquilino
     * @param int      $proceso      0 = NO_OK, 1 = OK, 2 = PENDIENTE
     * @param array    $payload      Payload completo (se serializa a JSON)
     * @param string   $resumen      Resumen humano legible
     */
    public function guardarValidacionIngresosSimple(int $id_inquilino, int $proceso, array $payload, string $resumen): bool
    {
        // 1) Asegurar fila base
        $stmt = $this->db->prepare("SELECT 1 FROM inquilinos_validaciones WHERE id_inquilino = ? LIMIT 1");
        $stmt->execute([$id_inquilino]);
        if (!$stmt->fetch(\PDO::FETCH_NUM)) {
            $ins = $this->db->prepare("
                INSERT INTO inquilinos_validaciones (id_inquilino, created_at, updated_at)
                VALUES (?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            $ins->execute([$id_inquilino]);
        }

        // 2) Serializar JSON
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $json = json_encode(['_error' => 'json_encode_failed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // 3) UPDATE de proceso + resumen + json
        $sql = "UPDATE inquilinos_validaciones
                SET  proceso_validacion_ingresos = :p,
                    validacion_ingresos_resumen = :r,
                    validacion_ingresos_json    = :j,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id_inquilino = :id";
        $up = $this->db->prepare($sql);
        return $up->execute([
            ':p'  => $proceso,
            ':r'  => $resumen,
            ':j'  => $json,
            ':id' => $id_inquilino,
        ]);
    }

    /* ============================================================================
    * Cambios: Validaciones de Ingresos (simple) y OCR de INE como "Documentos"
    * Fecha: 2025-08-13
    * Autor: alfgow
    * ----------------------------------------------------------------------------
    * 1) NUEVO/ACTUALIZADO: guardarValidacionIngresosSimple(int $id_inquilino,
    *    int $proceso, array $payload, string $resumen): bool
    *
    *    - Antes: existía una versión que recibía 2 parámetros (id, payload) y 
    *      guardaba JSON en la columna `validacion_ingresos`. Esto generó error
    *      al migrar el esquema a columnas *_resumen / *_json.
    *    - Ahora: recibe 4 parámetros y guarda:
    *        - proceso_validacion_ingresos (0=NO_OK, 1=OK, 2=PENDIENTE)
    *        - validacion_ingresos_resumen (TEXTO legible)
    *        - validacion_ingresos_json    (JSON con todo el detalle)
    *        - updated_at (marca de tiempo)
    *
    *    Columnas usadas:
    *      - proceso_validacion_ingresos INT NOT NULL DEFAULT 2
    *      - validacion_ingresos_resumen TEXT NULL
    *      - validacion_ingresos_json    JSON NULL
    *      - updated_at TIMESTAMP
    *
    *    Contrato de $payload (ejemplo):
    *      [
    *        'tipo'     => 'ingresos_pdf_simple',
    *        'conteo'   => 3,
    *        'archivos' => [ ['s3_key'=>..., 'ext'=>'pdf', 'size'=>...], ... ],
    *        'reglas'   => ['min_recomendado'=>3, 'criterio'=>'...'],
    *        'status'   => 'OK|REVIEW|FAIL',
    *        'ts'       => '2025-08-13T..'
    *      ]
    *
    *    Ejemplo de $resumen generado por Helper:
    *      "✔️ Ingresos (simple): 3 PDF(s) → OK."
    *
    *    Compatibilidad:
    *      - Si hay código antiguo que llame con 2 parámetros, debe actualizarse
    *        a la nueva firma (ver cambios en el controlador).
    *
    * ----------------------------------------------------------------------------
    * 2) NUEVO: guardarValidacionDocumentos(int $id_inquilino, int $proceso,
    *    array $payload, string $resumen): bool
    *
    *    - Propósito: persistir resultados de OCR genéricos de INE (conteo de
    *      líneas frente/reverso) como parte de "documentos".
    *    - Guarda:
    *        - proceso_validacion_documentos (0/1/2)
    *        - validacion_documentos_resumen (TEXTO)
    *        - validacion_documentos_json    (JSON)
    *        - updated_at
    *
    *    Columnas usadas:
    *      - proceso_validacion_documentos INT NOT NULL DEFAULT 2
    *      - validacion_documentos_resumen TEXT NULL
    *      - validacion_documentos_json    JSON NULL
    *      - updated_at TIMESTAMP
    *
    *    Contrato de $payload (ejemplo):
    *      [
    *        'tipo'    => 'ine_ocr_detect',
    *        'frontal' => ['lineas'=>12, 'texto'=>[...]],
    *        'reverso' => ['lineas'=>10, 'texto'=>[...]],
    *        'archivos'=> ['ine_frontal_key'=>'...', 'ine_reverso_key'=>'...'],
    *        'ts'      => '2025-08-13T..'
    *      ]
    *
    *    Ejemplo de $resumen:
    *      "✔️ INE OCR: 12 líneas frente, 10 reverso."
    *
    * ----------------------------------------------------------------------------
    * Notas de implementación:
    *  - Ambas funciones aseguran la fila base con INSERT si no existe el
    *    `id_inquilino` (patrón upsert simple: SELECT → INSERT → UPDATE).
    *  - JSON se serializa con JSON_UNESCAPED_UNICODE/SLASHES y hace fallback
    *    seguro si json_encode falla.
    *  - No realizan validación de negocio; asumen que el controlador les
    *    entrega $proceso, $payload y $resumen ya calculados.
    * ========================================================================== */

    public function guardarValidacionDocumentos(int $id_inquilino, int $proceso, array $payload, string $resumen): bool
    {
        // Asegurar fila
        $stmt = $this->db->prepare("SELECT 1 FROM inquilinos_validaciones WHERE id_inquilino = ? LIMIT 1");
        $stmt->execute([$id_inquilino]);
        if (!$stmt->fetch(\PDO::FETCH_NUM)) {
            $ins = $this->db->prepare("
                INSERT INTO inquilinos_validaciones (id_inquilino, created_at, updated_at)
                VALUES (?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            $ins->execute([$id_inquilino]);
        }

        // JSON seguro
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $json = json_encode(['_error' => 'json_encode_failed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // Update
        $sql = "UPDATE inquilinos_validaciones
                SET  proceso_validacion_documentos = :p,
                    validacion_documentos_resumen = :r,
                    validacion_documentos_json    = :j,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id_inquilino = :id";
        $up = $this->db->prepare($sql);
        return $up->execute([
            ':p'  => $proceso,
            ':r'  => $resumen,
            ':j'  => $json,
            ':id' => $id_inquilino,
        ]);
    }
    
    /**
     * Guarda la validación de INGRESOS (listado sin OCR): proceso + resumen + JSON.
     * Convención de proceso:
     *   - 2 (PENDIENTE) si hay ≥1 documento listado
     *   - 0 (NO_OK)     si no hay ninguno
     */
    public function guardarValidacionIngresosList(int $id_inquilino, int $proceso, array $payload, string $resumen): bool
    {
        // Asegurar fila base
        $stmt = $this->db->prepare("SELECT 1 FROM inquilinos_validaciones WHERE id_inquilino = ? LIMIT 1");
        $stmt->execute([$id_inquilino]);
        if (!$stmt->fetch(\PDO::FETCH_NUM)) {
            $ins = $this->db->prepare("
                INSERT INTO inquilinos_validaciones (id_inquilino, created_at, updated_at)
                VALUES (?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            $ins->execute([$id_inquilino]);
        }

        // JSON
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $json = json_encode(['_error' => 'json_encode_failed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // UPDATE
        $sql = "UPDATE inquilinos_validaciones
                SET  proceso_validacion_ingresos = :p,
                    validacion_ingresos_resumen = :r,
                    validacion_ingresos_json    = :j,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id_inquilino = :id";
        $up = $this->db->prepare($sql);
        return $up->execute([
            ':p'  => $proceso,
            ':r'  => $resumen,
            ':j'  => $json,
            ':id' => $id_inquilino,
        ]);
    }

        /**
     * Guarda el PAGO INICIAL: proceso (0/1/2), resumen humano y payload JSON.
     * Convención:
     *  - OK (1)     si hay monto > 0 y fecha válida (YYYY-MM-DD)
     *  - PEND (2)   si hay algún dato parcial (monto o fecha o referencia)
     *  - NO_OK (0)  si no hay datos
     */
        public function guardarPagoInicial(int $id_inquilino, int $proceso, array $payload, string $resumen): bool
        {
            // Asegurar fila base
            $stmt = $this->db->prepare("SELECT 1 FROM inquilinos_validaciones WHERE id_inquilino = ? LIMIT 1");
            $stmt->execute([$id_inquilino]);
            if (!$stmt->fetch(\PDO::FETCH_NUM)) {
                $ins = $this->db->prepare("
                    INSERT INTO inquilinos_validaciones (id_inquilino, created_at, updated_at)
                    VALUES (?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ");
                $ins->execute([$id_inquilino]);
            }

            // JSON
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                $json = json_encode(['_error' => 'json_encode_failed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            // UPDATE
            $sql = "UPDATE inquilinos_validaciones
                    SET  proceso_pago_inicial = :p,
                        pago_inicial_resumen = :r,
                        pago_inicial_json    = :j,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id_inquilino = :id";
            $up = $this->db->prepare($sql);
            return $up->execute([
                ':p'  => $proceso,
                ':r'  => $resumen,
                ':j'  => $json,
                ':id' => $id_inquilino,
            ]);
        }

        /**
         * Guarda la INVESTIGACIÓN DE DEMANDAS: proceso (0/1/2), resumen humano y payload JSON.
         * Convención:
         *  - OK (1)     → sin antecedentes (hit=false)
         *  - NO_OK (0)  → se hallaron antecedentes (hit=true)
         *  - PEND (2)   → sin datos suficientes para concluir
         */
        public function guardarInvestigacionDemandas(int $id_inquilino, int $proceso, array $payload, string $resumen): bool
        {
            // Asegurar fila base
            $stmt = $this->db->prepare("SELECT 1 FROM inquilinos_validaciones WHERE id_inquilino = ? LIMIT 1");
            $stmt->execute([$id_inquilino]);
            if (!$stmt->fetch(\PDO::FETCH_NUM)) {
                $ins = $this->db->prepare("
                    INSERT INTO inquilinos_validaciones (id_inquilino, created_at, updated_at)
                    VALUES (?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ");
                $ins->execute([$id_inquilino]);
            }

            // JSON
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                $json = json_encode(['_error' => 'json_encode_failed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            // UPDATE
            $sql = "UPDATE inquilinos_validaciones
                    SET  proceso_inv_demandas = :p,
                        inv_demandas_resumen = :r,
                        inv_demandas_json    = :j,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id_inquilino = :id";
            $up = $this->db->prepare($sql);
            return $up->execute([
                ':p'  => $proceso,
                ':r'  => $resumen,
                ':j'  => $json,
                ':id' => $id_inquilino,
            ]);
        }






    /**
     * Guarda la validación de ingresos (OCR): proceso (0/1/2), resumen humano y payload JSON.
     *
     * @param int      $id_inquilino
     * @param int      $proceso      0 = NO_OK, 1 = OK, 2 = PENDIENTE (REVIEW)
     * @param array    $payload      Payload completo (se serializa a JSON)
     * @param string   $resumen      Resumen humano legible
     */
    public function guardarValidacionIngresosOCR(int $id_inquilino, int $proceso, array $payload, string $resumen): bool
    {
        // 1) Asegurar fila base
        $stmt = $this->db->prepare("SELECT 1 FROM inquilinos_validaciones WHERE id_inquilino = ? LIMIT 1");
        $stmt->execute([$id_inquilino]);
        if (!$stmt->fetch(\PDO::FETCH_NUM)) {
            $ins = $this->db->prepare("
                INSERT INTO inquilinos_validaciones (id_inquilino, created_at, updated_at)
                VALUES (?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            $ins->execute([$id_inquilino]);
        }

        // 2) Serializar JSON
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $json = json_encode(['_error' => 'json_encode_failed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // 3) UPDATE de proceso + resumen + json
        $sql = "UPDATE inquilinos_validaciones
                SET  proceso_validacion_ingresos = :p,
                    validacion_ingresos_resumen = :r,
                    validacion_ingresos_json    = :j,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id_inquilino = :id";
        $up = $this->db->prepare($sql);
        return $up->execute([
            ':p'  => $proceso,
            ':r'  => $resumen,
            ':j'  => $json,
            ':id' => $id_inquilino,
        ]);
    }

    /**
     * Guarda la validación de ARCHIVOS: proceso (0/1/2), resumen humano y payload JSON.
     *
     * Convención:
     *  - proceso = 1 (OK)     si existen selfie e ine_frontal
     *  - proceso = 2 (PEND.)  si falta uno de los dos pero hay algo de evidencia
     *  - proceso = 0 (NO_OK)  si no hay selfie ni ine_frontal
     *
     * @param int      $id_inquilino
     * @param int      $proceso      0 = NO_OK, 1 = OK, 2 = PENDIENTE
     * @param array    $payload      Payload completo (se serializa a JSON)
     * @param string   $resumen      Resumen humano legible
     */
    public function guardarValidacionArchivos(int $id_inquilino, int $proceso, array $payload, string $resumen): bool
    {
        // 1) Asegurar fila base
        $stmt = $this->db->prepare("SELECT 1 FROM inquilinos_validaciones WHERE id_inquilino = ? LIMIT 1");
        $stmt->execute([$id_inquilino]);
        if (!$stmt->fetch(\PDO::FETCH_NUM)) {
            $ins = $this->db->prepare("
                INSERT INTO inquilinos_validaciones (id_inquilino, created_at, updated_at)
                VALUES (?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            $ins->execute([$id_inquilino]);
        }

        // 2) Serializar JSON
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $json = json_encode(['_error' => 'json_encode_failed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // 3) UPDATE de proceso + resumen + json
        $sql = "UPDATE inquilinos_validaciones
                SET  proceso_validacion_archivos = :p,
                    validacion_archivos_resumen = :r,
                    validacion_archivos_json    = :j,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id_inquilino = :id";
        $up = $this->db->prepare($sql);
        return $up->execute([
            ':p'  => $proceso,
            ':r'  => $resumen,
            ':j'  => $json,
            ':id' => $id_inquilino,
        ]);
    }




public function obtenerSueldoDeclarado(int $idInquilino): ?float
{
    // Tabla inquilinos_trabajo.sueldo (varchar) -> devolvemos float limpio o null
    $stmt = $this->db->prepare("SELECT sueldo FROM inquilinos_trabajo WHERE id_inquilino = ? LIMIT 1");
    $stmt->execute([$idInquilino]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || $row['sueldo'] === null || $row['sueldo'] === '') return null;

    $raw = (string)$row['sueldo'];
    // limpiar "$18,500.00" -> 18500.00
    $num = (float) str_replace([',','$',' '], '', $raw);
    return ($num > 0) ? $num : null;
}

/**
 * Guarda la validación de rostro: proceso (0/1/2), resumen humano y payload JSON.
 *
 * @param int   $id_inquilino
 * @param int   $estatus       0 = NO_OK, 1 = OK, 2 = PENDIENTE
 * @param array $datos         Payload completo (se serializa a JSON)
 * @param ?string $resumen     Resumen humano; si es null se genera uno mínimo.
 */
public function guardarValidacionRostro(int $id_inquilino, int $estatus, array $datos, ?string $resumen = null): bool
{
    // 1) Asegurar fila para el inquilino (si no existe, la creamos)
    $stmt = $this->db->prepare("SELECT 1 FROM inquilinos_validaciones WHERE id_inquilino = ? LIMIT 1");
    $stmt->execute([$id_inquilino]);
    if (!$stmt->fetch(\PDO::FETCH_NUM)) {
        $ins = $this->db->prepare("
            INSERT INTO inquilinos_validaciones (id_inquilino, created_at, updated_at)
            VALUES (?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ");
        $ins->execute([$id_inquilino]);
    }

    // 2) Serializar JSON de forma segura
    $json = json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        // Fallback por si algo no serializa
        $json = json_encode(['_error' => 'json_encode_failed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    // 3) Si no recibimos resumen, generamos uno mínimo (no depende del helper)
    if ($resumen === null) {
        $thr = isset($datos['threshold']) ? (int)$datos['threshold'] : 90;
        $sim = isset($datos['best']['similarity']) ? (float)$datos['best']['similarity'] : 0.0;
        $conf = isset($datos['best']['confidence']) ? (float)$datos['best']['confidence'] : 0.0;
        // ✔️/✖️/⏳ según estatus
        $emoji = ($estatus === 1 ? '✔️' : ($estatus === 0 ? '✖️' : '⏳'));
        $resumen = sprintf(
            '%s Rostro: similitud %.1f%% (umbral %d%%), confianza %.1f%%.',
            $emoji, $sim, $thr, $conf
        );
    }

    // 4) Guardar proceso + resumen + JSON
    $sql = "UPDATE inquilinos_validaciones
            SET  proceso_validacion_rostro = :p,
                 validacion_rostro_resumen = :r,
                 validacion_rostro_json    = :j,
                 updated_at = CURRENT_TIMESTAMP
            WHERE id_inquilino = :id";
    $up = $this->db->prepare($sql);
    return $up->execute([
        ':p'  => $estatus,        // 1=OK, 0=NO_OK, 2=PENDIENTE
        ':r'  => $resumen,        // TEXTO legible
        ':j'  => $json,           // JSON detallado
        ':id' => $id_inquilino,
    ]);
}






    /* =========================================================
     *  DETALLE POR SLUG
     * ========================================================= */

    /**
     * Recupera un inquilino (inquilino) por slug con relaciones y archivos.
     *
     * Relaciones incluidas:
     * - direccion (inquilinos_direccion, 1 fila)
     * - fiador (inquilinos_fiador, 1 fila de datos del inmueble en garantía si aplica)
     * - trabajo (inquilinos_trabajo, 1 fila)
     * - validaciones (inquilinos_validaciones, 1 fila)
     * - historial_vivienda (inquilinos_historial_vivienda, N filas)
     * - asesor (asesores, 1 fila)
     */
    public function obtenerPorSlug(string $slug): ?array
    {
        // Base pública S3 (para URLs directas, no firmadas)
        $s3config  = require __DIR__ . '/../config/s3config.php';
        $bucket    = $s3config['inquilinos']['bucket'];
        $region    = $s3config['inquilinos']['region'];
        $s3BaseUrl = "https://{$bucket}.s3.{$region}.amazonaws.com/";

        // Registro base
        $stmt = $this->db->prepare("SELECT * FROM inquilinos WHERE slug = :slug LIMIT 1");
        $stmt->execute([':slug' => $slug]);
        $inquilino = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$inquilino) return null;

        $id = (int)$inquilino['id'];

        // Selfie (si existe)
        $stmtSelfie = $this->db->prepare(
            "SELECT s3_key 
               FROM inquilinos_archivos 
              WHERE id_inquilino = :id AND tipo = 'selfie'
              LIMIT 1"
        );
        $stmtSelfie->execute([':id' => $id]);
        $selfie = $stmtSelfie->fetch(PDO::FETCH_ASSOC);
        $inquilino['selfie_url'] = ($selfie && !empty($selfie['s3_key']))
            ? $s3BaseUrl . $selfie['s3_key']
            : null;

        // Todos los archivos
        $stmtArchivos = $this->db->prepare("SELECT * FROM inquilinos_archivos WHERE id_inquilino = :id");
        $stmtArchivos->execute([':id' => $id]);
        $inquilino['archivos'] = $stmtArchivos->fetchAll(PDO::FETCH_ASSOC);

        // Relaciones
        $inquilino['direccion']          = $this->fetchOne("inquilinos_direccion", $id);
        $inquilino['fiador']             = $this->fetchOne("inquilinos_fiador", $id);
        $inquilino['trabajo']            = $this->fetchOne("inquilinos_trabajo", $id);
        $inquilino['validaciones']       = $this->fetchOne("inquilinos_validaciones", $id);
        $inquilino['historial_vivienda'] = $this->fetchMany("inquilinos_historial_vivienda", $id);

        // Asesor
        $stmtAsesor = $this->db->prepare("SELECT * FROM asesores WHERE id = :id_asesor LIMIT 1");
        $stmtAsesor->execute([':id_asesor' => $inquilino['id_asesor'] ?? null]);
        $inquilino['asesor'] = $stmtAsesor->fetch(PDO::FETCH_ASSOC) ?: null;

        // Base URL S3 (por si la UI quiere construir URLs públicas)
        $inquilino['s3_base_url'] = $s3BaseUrl;

        return $inquilino;
    }

    /** Helper: trae una sola fila relacionada por id_inquilino */
    private function fetchOne(string $table, int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$table} WHERE id_inquilino = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Helper: trae N filas relacionadas por id_inquilino */
    private function fetchMany(string $table, int $id): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$table} WHERE id_inquilino = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /* =========================================================
     *  LISTADOS / KPIs / BUSCADOR / FILTROS
     * ========================================================= */

    /**
     * Cuenta inquilinos nuevos (status=1) en `inquilinos`.
     */
    public function contarInquilinosNuevos(): int
    {
        $sql = "SELECT COUNT(*) as total FROM inquilinos WHERE status = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    /**
     * Lista inquilinos nuevos (status=1) con campos básicos.
     */
    public function getInquilinosNuevos(): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, nombre_inquilino, email, celular, status 
               FROM inquilinos 
              WHERE status = 1 
              ORDER BY id DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getInquilinosAll(): array
    {
        $stmt = $this->db->prepare(
            "SELECT *
               FROM inquilinos 
              WHERE tipo = 'Arrendatario' 
              ORDER BY id DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getFiadoresAll(): array
    {
        $stmt = $this->db->prepare(
            "SELECT *
               FROM inquilinos 
              WHERE tipo = 'Fiador' 
              ORDER BY id DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getObligadosAll(): array
    {
        $stmt = $this->db->prepare(
            "SELECT *
               FROM inquilinos 
              WHERE tipo = 'Obligado Solidario' 
              ORDER BY id DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }



    /**
     * Lista inquilinos nuevos (status=1) y agrega URL de selfie si existe.
     * Usa URL pública (no firmada). Para URL firmada, usa getS3Url().
     */
    public function getInquilinosNuevosConSelfie(): array
    {
        $s3config = require __DIR__ . '/../config/s3config.php';
        $bucket   = $s3config['inquilinos']['bucket'];
        $region   = $s3config['inquilinos']['region'];

        $stmt = $this->db->prepare(
            "SELECT * FROM inquilinos 
              WHERE status = 1 
              ORDER BY id DESC"
        );
        $stmt->execute();
        $inquilinos = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $stmtSelfie = $this->db->prepare(
            "SELECT s3_key FROM inquilinos_archivos 
              WHERE id_inquilino = :id AND tipo = 'selfie' 
              LIMIT 1"
        );

        foreach ($inquilinos as &$p) {
            $stmtSelfie->execute([':id' => $p['id']]);
            $selfie = $stmtSelfie->fetch(PDO::FETCH_ASSOC);

            $p['selfie_url'] = ($selfie && !empty($selfie['s3_key']))
                ? "https://{$bucket}.s3.{$region}.amazonaws.com/{$selfie['s3_key']}"
                : null;
        }
        return $inquilinos;
    }

    /**
     * Búsqueda de inquilinos por texto (nombre/email/celular).
     *
     * @param string $q     Texto a buscar (mín. 2 chars)
     * @param int    $limit Máximo 50
     */
    public function buscarPorTexto(string $q, int $limit = 10): array
    {
        $q = trim($q);
        if ($q === '' || mb_strlen($q, 'UTF-8') < 2) return [];

        $like  = '%' . $q . '%';
        $limit = max(1, min(50, (int)$limit));

        $sql = "
            SELECT 
                CONCAT_WS(' ', nombre_inquilino, apellidop_inquilino, apellidom_inquilino) AS nombre,
                email,
                celular AS telefono,
                id,
                'inquilinos' AS fuente
            FROM inquilinos
            WHERE 
                CONCAT_WS(' ', nombre_inquilino, apellidop_inquilino, apellidom_inquilino) LIKE :q1
                OR email LIKE :q2
                OR celular LIKE :q3
            ORDER BY id DESC
            LIMIT {$limit}
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':q1', $like, PDO::PARAM_STR);
        $stmt->bindValue(':q2', $like, PDO::PARAM_STR);
        $stmt->bindValue(':q3', $like, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Listado con filtros y paginación.
     * - $q busca en nombre completo/email/celular.
     * - $tipo filtra por rol (Arrendatario/Fiador/Obligado Solidario, etc.).
     * - $estatus filtra por `status` (string para flexibilidad en UI).
     */
    public function buscarConFiltros(string $q, string $tipo, string $estatus, int $limit, int $offset): array
    {
        $db = self::getDb();
        $where  = [];
        $params = [];

        if ($q !== '') {
            $where[] = "(CONCAT_WS(' ', nombre_inquilino, apellidop_inquilino, apellidom_inquilino) LIKE :q1
                        OR email LIKE :q2
                        OR celular LIKE :q3)";
            $like = "%{$q}%";
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
        }

        if ($tipo !== '') {
            // Valores válidos según tu BD: Arrendatario | Fiador | Obligado Solidario
            $where[] = "tipo = :tipo";
            $params[':tipo'] = $tipo;
        }

        // Si vas a usar el filtro estatus del select (completo/pendiente/incompleto),
        // mapea aquí a tu columna 'status' (1=Nuevo, 2=Aprobado, 3=Rechazado, 4=Problemático) o comenta esta sección.
        if ($estatus !== '') {
            $map = [
                'pendiente'  => 1, // Nuevo
                'completo'   => 2, // Aprobado
                'incompleto' => 3, // Rechazado (ajústalo si usas otro significado)
                // 'problematico' => 4,
            ];
            if (isset($map[$estatus])) {
                $where[] = "status = :status";
                $params[':status'] = $map[$estatus];
            }
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "SELECT *
                FROM inquilinos
                {$whereSql}
                ORDER BY id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);

        // Bind de filtros
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }

        // Bind de paginación SIEMPRE como enteros
        $stmt->bindValue(':limit',  $limit,  \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }


    /**
     * Total para paginación del listado con filtros (misma lógica que buscarConFiltros()).
     */
    public function contarTotalConFiltros(string $q, string $tipo, string $estatus): int
    {
        $db = self::getDb();
        $where  = [];
        $params = [];

        if ($q !== '') {
            $where[] = "(CONCAT_WS(' ', nombre_inquilino, apellidop_inquilino, apellidom_inquilino) LIKE :q1
                        OR email LIKE :q2
                        OR celular LIKE :q3)";
            $like = "%{$q}%";
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
        }

        if ($tipo !== '') {
            $where[] = "tipo = :tipo";
            $params[':tipo'] = $tipo;
        }

        if ($estatus !== '') {
            $map = [
                'pendiente'  => 1,
                'completo'   => 2,
                'incompleto' => 3,
                // 'problematico' => 4,
            ];
            if (isset($map[$estatus])) {
                $where[] = "status = :status";
                $params[':status'] = $map[$estatus];
            }
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "SELECT COUNT(*) FROM inquilinos {$whereSql}";
        $stmt = $db->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }

        $stmt->execute();
        return (int)$stmt->fetchColumn();
}



    /* =========================================================
     *  ACTUALIZACIONES (CRUD por bloques)
     * ========================================================= */

    /**
     * Actualiza datos personales en `inquilinos`.
     * OJO con longitudes: email(50), celular(20), nacionalidad(20), tipo_id(20), num_id(255).
     */
    public function actualizarDatosPersonales(int $id, array $data): bool
    {
        $sql = "UPDATE inquilinos SET
            nombre_inquilino    = :nombre_inquilino,
            apellidop_inquilino = :apellidop_inquilino,
            apellidom_inquilino = :apellidom_inquilino,
            email               = :email,
            celular             = :celular,
            rfc                 = :rfc,
            curp                = :curp,
            nacionalidad        = :nacionalidad,
            estadocivil         = :estadocivil,
            conyuge             = :conyuge,
            tipo_id             = :tipo_id,
            num_id              = :num_id
            WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nombre_inquilino'    => $data['nombre_inquilino'],
            ':apellidop_inquilino' => $data['apellidop_inquilino'],
            ':apellidom_inquilino' => $data['apellidom_inquilino'],
            ':email'               => $data['email'],
            ':celular'             => $data['celular'],
            ':rfc'                 => $data['rfc'],
            ':curp'                => $data['curp'],
            ':nacionalidad'        => $data['nacionalidad'],
            ':estadocivil'         => $data['estadocivil'],
            ':conyuge'             => $data['conyuge'],
            ':tipo_id'             => $data['tipo_id'],
            ':num_id'              => $data['num_id'],
            ':id'                  => $id,
        ]);
    }

    /**
     * Actualiza domicilio en `inquilinos_direccion` (1 fila por inquilino).
     */
    public function actualizarDomicilio(int $id_inquilino, array $data): bool
    {
        $sql = "UPDATE inquilinos_direccion
                   SET calle = :calle,
                       num_exterior = :num_exterior,
                       num_interior = :num_interior,
                       colonia = :colonia,
                       alcaldia = :alcaldia,
                       ciudad = :ciudad,
                       codigo_postal = :codigo_postal
                 WHERE id_inquilino = :id_inquilino
                 LIMIT 1";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':calle'         => $data['calle'],
            ':num_exterior'  => $data['num_exterior'],
            ':num_interior'  => $data['num_interior'],
            ':colonia'       => $data['colonia'],
            ':alcaldia'      => $data['alcaldia'],
            ':ciudad'        => $data['ciudad'],
            ':codigo_postal' => $data['codigo_postal'],
            ':id_inquilino'  => $id_inquilino,
        ]);
    }

    /**
     * Actualiza datos laborales en `inquilinos_trabajo`.
     */
    public function actualizarTrabajo(int $id_inquilino, array $data): bool
    {
        $sql = "UPDATE inquilinos_trabajo
                SET empresa = :empresa,
                    puesto = :puesto,
                    direccion_empresa = :direccion_empresa,
                    antiguedad = :antiguedad,
                    sueldo = :sueldo,
                    otrosingresos = :otrosingresos,
                    nombre_jefe = :nombre_jefe,
                    tel_jefe = :tel_jefe,
                    web_empresa = :web_empresa,
                    telefono_empresa = :telefono_empresa, -- 👈 agregado
                    updated_at = CURRENT_TIMESTAMP
                WHERE id_inquilino = :id_inquilino";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':empresa'          => $data['empresa'],
            ':puesto'           => $data['puesto'],
            ':direccion_empresa'=> $data['direccion_empresa'],
            ':antiguedad'       => $data['antiguedad'],
            ':sueldo'           => $data['sueldo'],
            ':otrosingresos'    => $data['otrosingresos'],
            ':nombre_jefe'      => $data['nombre_jefe'],
            ':tel_jefe'         => $data['tel_jefe'],
            ':web_empresa'      => $data['web_empresa'],
            ':telefono_empresa' => $data['telefono_empresa'], // 👈 agregado
            ':id_inquilino'     => $id_inquilino,
        ]);
    }


    /**
     * Actualiza datos de garantía del fiador en `inquilinos_fiador`.
     * (Esta tabla cuelga del inquilino que tiene rol de fiador en `inquilinos`.)reemplazarArchivo
     */
    public function actualizarFiador(array $data): bool
    {
        if (empty($data['id_inquilino'])) return false;

        $stmt = $this->db->prepare("
            UPDATE inquilinos_fiador SET
                calle_inmueble    = :calle_inmueble,
                num_ext_inmueble  = :num_ext_inmueble,
                num_int_inmueble  = :num_int_inmueble,
                colonia_inmueble  = :colonia_inmueble,
                alcaldia_inmueble = :alcaldia_inmueble,
                estado_inmueble   = :estado_inmueble,
                numero_escritura  = :numero_escritura,
                numero_notario    = :numero_notario,
                estado_notario    = :estado_notario,
                folio_real        = :folio_real
            WHERE id_inquilino = :id_inquilino
            LIMIT 1
        ");

        return $stmt->execute([
            ':calle_inmueble'    => $data['calle_inmueble'] ?? '',
            ':num_ext_inmueble'  => $data['num_ext_inmueble'] ?? '',
            ':num_int_inmueble'  => $data['num_int_inmueble'] ?? '',
            ':colonia_inmueble'  => $data['colonia_inmueble'] ?? '',
            ':alcaldia_inmueble' => $data['alcaldia_inmueble'] ?? '',
            ':estado_inmueble'   => $data['estado_inmueble'] ?? '',
            ':numero_escritura'  => $data['numero_escritura'] ?? '',
            ':numero_notario'    => $data['numero_notario'] ?? '',
            ':estado_notario'    => $data['estado_notario'] ?? '',
            ':folio_real'        => $data['folio_real'] ?? '',
            ':id_inquilino'      => $data['id_inquilino'],
        ]);
    }

    /**
     * Actualiza una fila de historial de vivienda por su ID.
     */
    public function actualizarHistorialVivienda(array $data): bool
    {
        if (empty($data['id'])) return false;

        $stmt = $this->db->prepare("
            UPDATE inquilinos_historial_vivienda
               SET vive_actualmente         = :vive_actualmente,
                   renta_actualmente        = :renta_actualmente,
                   arrendador_actual        = :arrendador_actual,
                   cel_arrendador_actual    = :cel_arrendador_actual,
                   monto_renta_actual       = :monto_renta_actual,
                   tiempo_habitacion_actual = :tiempo_habitacion_actual,
                   motivo_arrendamiento     = :motivo_arrendamiento
             WHERE id = :id
             LIMIT 1
        ");

        return $stmt->execute([
            ':vive_actualmente'         => $data['vive_actualmente'] ?? null,
            ':renta_actualmente'        => $data['renta_actualmente'] ?? null,
            ':arrendador_actual'        => $data['arrendador_actual'] ?? null,
            ':cel_arrendador_actual'    => $data['cel_arrendador_actual'] ?? null,
            ':monto_renta_actual'       => $data['monto_renta_actual'] ?? null,
            ':tiempo_habitacion_actual' => $data['tiempo_habitacion_actual'] ?? null,
            ':motivo_arrendamiento'     => $data['motivo_arrendamiento'] ?? null,
            ':id'                       => $data['id'],
        ]);
    }


    /**
     * Actualiza varios campos de validaciones en lote
     * @param int   $id_inquilino
     * @param array $campos
     * @return bool
     */
    public function actualizarValidaciones(int $id_inquilino, array $campos): bool
    {
        if (empty($campos)) {
            return false;
        }

        // Mapeo de alias → columnas reales
        $map = [
            // Documentos
            'validacion_documentos' => 'validacion_documentos_resumen',
            // ID
            'validacion_id' => [
                'resumen' => 'validacion_id_resumen',
                'json'    => 'validacion_id_json'
            ],
            // Rostro
            'validacion_rostro' => 'validacion_rostro_resumen',
            // Archivos
            'validacion_archivos' => 'validacion_archivos_resumen',
            // Ingresos
            'validacion_ingresos' => 'validacion_ingresos_resumen',
            // Pago inicial
            'pago_inicial' => 'pago_inicial_resumen',
            // Demandas
            'inv_demandas' => 'inv_demandas_resumen',
        ];

        $sets = [];
        $params = [':id' => $id_inquilino];

        foreach ($campos as $k => $v) {
            if (isset($map[$k])) {
                // Caso especial validacion_id → puede ser string o array
                if ($k === 'validacion_id') {
                    if (is_array($v)) {
                        $sets[] = $map[$k]['json'] . ' = :jsonVal';
                        $params[':jsonVal'] = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    } else {
                        $sets[] = $map[$k]['resumen'] . ' = :resumenVal';
                        $params[':resumenVal'] = $v;
                    }
                } else {
                    $sets[] = $map[$k] . ' = :' . $k;
                    $params[":$k"] = is_array($v)
                        ? json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        : $v;
                }
            } else {
                // Si no hay alias, usar directamente
                $sets[] = $k . ' = :' . $k;
                $params[":$k"] = is_array($v)
                    ? json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : $v;
            }
        }

        if (empty($sets)) {
            return false;
        }

        $sql = "UPDATE inquilinos_validaciones SET " . implode(', ', $sets) . ", updated_at = CURRENT_TIMESTAMP WHERE id_inquilino = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }


    /**
     * Cambia el asesor asignado en `inquilinos`.
     */
    public function actualizarAsesor(int $idInquilino, int $idAsesor): bool
    {
        $stmt = $this->db->prepare("UPDATE inquilinos SET id_asesor = ? WHERE id = ?");
        return $stmt->execute([$idAsesor, $idInquilino]);
    }

    /**
     * Reemplaza un archivo (s3_key y opcionalmente su tipo) en `inquilinos_archivos`.
     *
     * @return array{ok:bool, s3_key?:string, error?:string}
     */
    public function reemplazarArchivo(int $archivoId, string $s3_key, ?string $tipo = null): array
    {
        $stmt = $this->db->prepare("SELECT * FROM inquilinos_archivos WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $archivoId]);
        $oldFile = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$oldFile) {
            return ['ok' => false, 'error' => 'Archivo no encontrado'];
        }

        if ($tipo !== null) {
            $stmt = $this->db->prepare("UPDATE inquilinos_archivos SET s3_key = :s3_key, tipo = :tipo WHERE id = :id");
            $stmt->execute([':s3_key' => $s3_key, ':tipo' => $tipo, ':id' => $archivoId]);
        } else {
            $stmt = $this->db->prepare("UPDATE inquilinos_archivos SET s3_key = :s3_key WHERE id = :id");
            $stmt->execute([':s3_key' => $s3_key, ':id' => $archivoId]);
        }

        return ['ok' => true, 's3_key' => $s3_key];
    }

    /**
     * Inserta un registro en inquilinos_archivos.
     * @return int|false  ID del nuevo archivo o false en error
     */
    public function crearArchivo(int $id_inquilino, string $tipo, string $s3_key, ?string $mime_type, ?int $size)
    {
        try {
            $sql = "INSERT INTO inquilinos_archivos (id_inquilino, tipo, s3_key, mime_type, size, created_at)
                    VALUES (:id_inquilino, :tipo, :s3_key, :mime_type, :size, CURRENT_TIMESTAMP)";
            $stmt = $this->db->prepare($sql);
            $ok = $stmt->execute([
                ':id_inquilino' => $id_inquilino,
                ':tipo'         => $tipo,
                ':s3_key'       => $s3_key,
                ':mime_type'    => $mime_type,
                ':size'         => $size,
            ]);
            if (!$ok) return false;
            return (int)$this->db->lastInsertId();
        } catch (\Throwable $e) {
            error_log('[InquilinoModel::crearArchivo] ' . $e->getMessage());
            return false;
        }
    }



    /**
     * Catálogo de asesores, ordenado alfabéticamente.
     */
    public function obtenerTodosAsesores(): array
    {
        $stmt = $this->db->query("SELECT * FROM asesores ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
 * Devuelve los archivos de un inquilino por su slug (SIN presignar).
 * @return array<int, array{id:int,tipo:string,s3_key:string,mime_type:?string,created_at:?string}>
 */
public function obtenerArchivosPorSlug(string $slug): array
{
    try {
        // Usa la conexión del modelo; si no existe, fallback seguro
        $db = $this->db ?? (new Database())->getDb();

        $sql = "
            SELECT a.id, a.tipo, a.s3_key, a.mime_type, a.created_at
            FROM inquilinos i
            INNER JOIN inquilinos_archivos a ON a.id_inquilino = i.id
            WHERE i.slug = ?
            ORDER BY a.created_at DESC, a.id DESC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$slug]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (\Throwable $e) {
        error_log('[InquilinoModel] obtenerArchivosPorSlug: ' . $e->getMessage());
        return [];
    }
}



}