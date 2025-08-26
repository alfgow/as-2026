<?php
/* =============================================================
 * InquilinoValidacionAWSController.php
 * Proyecto: Backend - Arrendamiento Seguro
 * Revisión/Corrección/Actualización/Documentación: 2025-08-13T05:44:25
 * ============================================================= */
declare(strict_types=1);

namespace App\Controllers;
require_once __DIR__ . '/../Models/InquilinoModel.php';
require_once __DIR__ . '/../Helpers/ValidacionResumenHelper.php';
use App\Models\InquilinoModel;
use App\Helpers\S3Helper;
use Aws\Rekognition\RekognitionClient;
use Aws\Textract\TextractClient;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use App\Helpers\ValidacionResumenHelper as VRH;


/**
 * Controlador de Validaciones AWS para Inquilinos
 *
 * - Aísla la lógica de validación para poder eliminarse sin afectar el resto del sistema.
 * - Ruta principal sugerida: GET /inquilino/{slug}/validar  -> validar($slug)
 * - Endpoints/debug opcionales (?check=archivos|faces|ocr|parse|kv|match)
 * - Utiliza AWS S3, Textract y Rekognition (us-east-1 para evitar restricciones regionales).
 *
 * Tareas realizadas: Revisión, Corrección, Actualización y Documentación (2025-08-13).
 */
class InquilinoValidacionAWSController
{

    private $model;
    private $rekognition;
    private $textract;
    private $aws;       
    private $s3Bucket;
    private $s3;
    private $s3Us; 
    private $textractUS;
    private $s3BucketUs = 'copia-inquilinos-us';
    
/**
 * Constructor: inicializa modelo y clientes AWS.
 * - Carga configuración desde config/s3config.php, perfil 'inquilinos'.
 * - S3 en región configurada.
 * - Textract/Rekognition en us-east-1 por disponibilidad.
 */
public function __construct()
    {
        $this->model = new InquilinoModel();
        $this->aws = require __DIR__ . '/../config/s3config.php'; // ajusta ruta si difiere
        $conf = $this->aws['inquilinos'];                     // usamos el perfil "inquilinos"
        $this->s3Bucket = $conf['bucket'];

        $this->s3 = new S3Client([
            'version'     => 'latest',
            'region'      => $conf['region'],        // p.ej. mx-central-1
            'credentials' => $conf['credentials'],
        ]);

        $this->s3Us = new S3Client([
        'version' => 'latest',
        'region'  => 'us-east-1',   // 👈 importante
        'credentials' => $conf['credentials']
        ]);

        // Rekognition no está disponible en todas las regiones.
        // Usamos us-east-1 para evitar errores regionales.
        $this->rekognition = new RekognitionClient([
            'version'     => 'latest',
            'region'      => 'us-east-1',
            'credentials' => $conf['credentials'],
        ]);

        $this->textract = new TextractClient([
            'version'     => 'latest',
            'region'      => 'us-east-1',
            'credentials' => $this->aws['inquilinos']['credentials'],
        ]);


        
      
    }

    /**
 * Copia un objeto desde el bucket MX ($this->s3Bucket) hacia el bucket US ($this->s3BucketUs)
 * y devuelve la key de destino. Usa un prefijo "textract-cache/" para no ensuciar la raíz.
 */
private function ensureCopyToUsBucket(string $srcKey): string
{
    $dstKey = 'textract-cache/' . ltrim($srcKey, '/'); // puedes personalizar el prefijo

    // Hacemos el copy S3 -> S3 (misma cuenta, distinta región)
    $this->s3Us->copyObject([
        'Bucket'     => $this->s3BucketUs,
        'Key'        => $dstKey,
        'CopySource' => $this->s3Bucket . '/' . $srcKey, // importante: "bucket/key" sin URL
        'ACL'        => 'private',
    ]);

    return $dstKey;
}
    
    /**
     * Valida comprobantes de ingreso por conteo simple de PDFs.
     * Regla: OK (>=3), REVIEW (1-2), FAIL (0).
     * Guarda payload + resumen humano en inquilinos_validaciones.
     */
    public function validarIngresosPDFSimple(array $inquilino)
    {
        // 1) Obtener los comprobantes de ingresos (filtra solo PDF)
        $items = $this->model->obtenerComprobantesIngreso((int)$inquilino['id']);
        $pdfs = [];
        foreach ($items as $r) {
            $key = $r['s3_key'] ?? '';
            $ext = strtolower(pathinfo($key, PATHINFO_EXTENSION));
            if ($ext === 'pdf') {
                $pdfs[] = [
                    's3_key' => $key,
                    'ext'    => $ext,
                    'size'   => $r['size'] ?? null,
                    'mime'   => $r['mime_type'] ?? null,
                ];
            }
        }

        $n = count($pdfs);

        // 2) Determinar status texto
        //    - OK:     3 o más PDFs
        //    - REVIEW: 1 o 2 PDFs
        //    - FAIL:   0 PDFs
        $status = ($n >= 3) ? 'OK' : (($n >= 1) ? 'REVIEW' : 'FAIL');

        // 3) Payload con reglas y resultados
        $payload = [
            'tipo'      => 'ingresos_pdf_simple',
            'conteo'    => $n,
            'archivos'  => $pdfs,
            'reglas'    => [
                'min_recomendado' => 3,
                'criterio'        => 'OK si hay >= 3 PDFs; REVIEW si 1-2; FAIL si 0.',
            ],
            'status'    => $status,
            'ts'        => date('c'),
        ];

        // 4) Mapear status -> proceso (0/1/2)
        $proceso = ($status === 'OK') ? 1 : (($status === 'FAIL') ? 0 : 2);

        // 5) Resumen humano
        $resumen = \App\Helpers\ValidacionResumenHelper::ingresosSimple($proceso, $payload);

        // 6) Guardar en la tabla inquilinos_validaciones (columnas *_resumen/*_json)
        return $this->model->guardarValidacionIngresosSimple(
            (int)$inquilino['id'],
            (int)$proceso,
            $payload,
            $resumen
        );
    }



    public 
/**
 * Orquestador de validaciones para un inquilino por slug.
 * Debugs opcionales vía ?check=archivos|faces|ocr|parse|kv|match
 * Devuelve JSON para pruebas rápidas.
 */
function validar($slug)
    {
        // 1. Obtener datos del inquilino
        $inquilino = $this->model->obtenerPorSlug($slug);            

        // --- VALIDACIÓN / RESUMEN DE ARCHIVOS ---------------------------------------
        if (isset($_GET['check']) && $_GET['check'] === 'archivos') {
            // 1) Leer archivos desde BD
            $rows = $this->model->archivosPorInquilinoId((int)$inquilino['id']);

            // 2) Alias aceptados -> clave canónica
            $alias = [
                'selfie'            => ['selfie','foto_selfie','face','rostro'],
                'ine_frontal'       => ['ine_frontal','identificacion_frontal','ine_frente','ine_front'],
                'ine_reverso'       => ['ine_reverso','identificacion_reverso','ine_atras','ine_back','ine_rear'],
                'pasaporte'         => ['pasaporte','passport'],
                'forma_migratoria'  => ['forma_migratoria','fm2','fm3','residencia_migratoria'],
                'comprobante_ingreso' => ['comprobante_ingreso','recibo_nomina','estado_cuenta','pdf','ingresos_pdf'],
            ];
            $toCanonical = function (string $tipo) use ($alias): ?string {
                $t = strtolower(trim($tipo));
                foreach ($alias as $canon => $list) {
                    if (in_array($t, $list, true)) return $canon;
                }
                return null;
            };
            $inferMime = function (string $key): ?string {
                $ext = strtolower(pathinfo($key, PATHINFO_EXTENSION));
                return match($ext) {
                    'jpg','jpeg' => 'image/jpeg',
                    'png'        => 'image/png',
                    'pdf'        => 'application/pdf',
                    'webp'       => 'image/webp',
                    default      => null,
                };
            };

            // 3) Construir mapa y contadores
            $map = [
                'selfie'           => null,
                'ine_frontal'      => null,
                'ine_reverso'      => null,
                'pasaporte'        => null,
                'forma_migratoria' => null,
                'comprobantes'     => [],
            ];
            $tiposDetectados = [];

            foreach ($rows as $r) {
                $canon = $toCanonical((string)$r['tipo']);
                $item = [
                    's3_key' => $r['s3_key'],
                    'mime'   => $r['mime_type'] ?? null,
                    'size'   => isset($r['size']) ? (int)$r['size'] : null,
                ];
                if (!$item['mime']) $item['mime'] = $inferMime($item['s3_key']);
                if ($item['size'] === null) $item['size'] = 0;

                if ($canon === 'comprobante_ingreso') {
                    $map['comprobantes'][] = $item;
                    $tiposDetectados[] = 'comprobante_ingreso';
                    continue;
                }
                if ($canon && array_key_exists($canon, $map) && $canon !== 'comprobantes') {
                    if ($map[$canon] === null) { // conserva el primero o el más reciente si ajustas por fecha
                        $map[$canon] = $item;
                        $tiposDetectados[] = $canon;
                    }
                }
            }

            $hasSelfie  = !empty($map['selfie']['s3_key'] ?? null);
            $hasFrente  = !empty($map['ine_frontal']['s3_key'] ?? null);
            $evidencia  = $hasSelfie || $hasFrente || !empty($map['ine_reverso']) || !empty($map['pasaporte']) || !empty($map['forma_migratoria']) || count($map['comprobantes']) > 0;

            // 4) Regla de proceso (0/1/2)
            $proceso = 0; // NO_OK
            if ($hasSelfie && $hasFrente) {
                $proceso = 1; // OK
            } elseif ($evidencia) {
                $proceso = 2; // PENDIENTE
            }

            // 5) Payload para guardar + Resumen humano
            $payload = [
                'tipo'       => 'archivos',
                'archivos'   => $map,
                'contadores' => [
                    'total_tipos'       => count($tiposDetectados),
                    'comprobantes'      => count($map['comprobantes']),
                    'tipos_detectados'  => $tiposDetectados,
                ],
                'requeridos' => [
                    'selfie'      => $hasSelfie,
                    'ine_frontal' => $hasFrente,
                ],
                'ts' => date('c'),
            ];
            $resumen = \App\Helpers\ValidacionResumenHelper::archivos($proceso, $payload);

            // 6) Guardar en BD (proceso + resumen + JSON)
            $ok = $this->model->guardarValidacionArchivos((int)$inquilino['id'], $proceso, $payload, $resumen);

            // 7) Respuesta
            echo json_encode([
                'ok'      => (bool)$ok,
                'mensaje' => $ok ? 'Validación de archivos guardada.' : 'No se pudo guardar la validación de archivos.',
                'resultado' => [
                    'proceso'   => $proceso,
                    'resumen'   => $resumen,
                    'requeridos'=> ['selfie'=>$hasSelfie, 'ine_frontal'=>$hasFrente],
                    'detalles'  => $map,
                ],
            ], JSON_UNESCAPED_UNICODE);
            return;
        }


        // --- DEBUG: comparar Selfie vs INE frontal ---
        if (isset($_GET['check']) && $_GET['check'] === 'faces') {
            // 1) Leer archivos desde BD
            $rows = $this->model->archivosPorInquilinoId($inquilino['id']);
            $selfieKey = null;
            $frontalKey = null;

            foreach ($rows as $r) {
                $tipo = strtolower(trim($r['tipo']));
                if ($tipo === 'selfie')       $selfieKey  = $r['s3_key'];
                if ($tipo === 'ine_frontal')  $frontalKey = $r['s3_key'];
            }

            if (!$selfieKey || !$frontalKey) {
                echo json_encode(['ok' => false, 'mensaje' => 'Faltan archivos: se requiere selfie e INE frontal.']);
                return;
            }

            try {
                // 2) Descargar bytes de S3
                $o1 = $this->s3->getObject(['Bucket' => $this->s3Bucket, 'Key' => $selfieKey]);
                $o2 = $this->s3->getObject(['Bucket' => $this->s3Bucket, 'Key' => $frontalKey]);
                $bytesSelfie = (string)$o1['Body'];
                $bytesFrente = (string)$o2['Body'];

                // 3) CompareFaces en Rekognition
                $res = $this->rekognition->compareFaces([
                    'SourceImage'        => ['Bytes' => $bytesSelfie],
                    'TargetImage'        => ['Bytes' => $bytesFrente],
                    'SimilarityThreshold'=> 80, // ajustable
                ]);

                $matches = $res['FaceMatches'] ?? [];
                $best = null;
                if (!empty($matches)) {
                    // tomar el match de mayor similitud
                    usort($matches, function($a,$b){
                        return ($b['Similarity'] <=> $a['Similarity']);
                    });
                    $best = [
                        'similarity' => $matches[0]['Similarity'] ?? null,
                        'confidence' => $matches[0]['Face']['Confidence'] ?? null,
                        'boundingBox'=> $matches[0]['Face']['BoundingBox'] ?? null,
                    ];
                }

                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'CompareFaces ejecutado.',
                    'bucket' => $this->s3Bucket,
                    'selfie_key' => $selfieKey,
                    'ine_frontal_key' => $frontalKey,
                    'faceMatches' => count($matches),
                    'bestMatch' => $best
                ]);
            } catch (AwsException $e) {
                echo json_encode([
                    'ok' => false,
                    'mensaje' => 'Error en AWS: ' . $e->getAwsErrorMessage(),
                    'codigo'  => $e->getAwsErrorCode()
                ]);
            }
            return;
        }
        // --- FIN DEBUG faces ---

        /* ============================================================================
        * Cambios: Resumen Humano + Persistencia extendida (ingresos_simple y OCR INE)
        * Fecha: 2025-08-13
        * Autor: alfgow
        * ----------------------------------------------------------------------------
        * 1) ACTUALIZADO: validarIngresosPDFSimple(array $inquilino)
        *
        *    - Antes: guardaba JSON en `validacion_ingresos` con 2 params al modelo.
        *      Tras la migración de esquema, la firma del modelo cambió (4 params),
        *      lo que provocó el error "Expected 4 arguments. Found 2".
        *
        *    - Ahora: el controlador:
        *        (a) arma $payload con conteo de PDFs,
        *        (b) decide $status ('OK'|'REVIEW'|'FAIL') y mapea a $proceso (1/2/0),
        *        (c) genera $resumen con ValidacionResumenHelper::ingresosSimple(...),
        *        (d) llama al modelo con 4 parámetros:
        *            guardarValidacionIngresosSimple($id, $proceso, $payload, $resumen)
        *
        *    - Resultado:
        *        - proceso_validacion_ingresos ← 1/2/0
        *        - validacion_ingresos_resumen ← TEXTO legible
        *        - validacion_ingresos_json    ← JSON completo
        *
        *    - Ejemplo de respuesta JSON del endpoint:
        *        {
        *          "ok": true,
        *          "mensaje": "Validación de comprobantes (simple) guardada.",
        *          "resultado": {
        *            "status": "REVIEW",
        *            "proceso": 2,
        *            "resumen": "⏳ Ingresos (simple): 1 PDF(s) → REVIEW.",
        *            "conteo": 1
        *          }
        *        }
        *
        * ----------------------------------------------------------------------------
        * 2) ACTUALIZADO: ?check=ocr (OCR de INE con Textract y guardado como Documentos)
        *
        *    - Antes: solo hacía OCR y devolvía las líneas por pantalla (uso debug).
        *    - Ahora: además de hacer OCR (DetectDocumentText) y contar líneas:
        *        (a) decide $proceso: OK si ambos lados tienen líneas; PEND si uno;
        *            NO_OK si ninguno,
        *        (b) arma $payload con conteos, claves S3 y extractos,
        *        (c) genera $resumen con ValidacionResumenHelper::ineOcr(...),
        *        (d) guarda en BD con guardarValidacionDocumentos($id, $proceso, $payload, $resumen).
        *
        *    - Columnas afectadas:
        *        - proceso_validacion_documentos
        *        - validacion_documentos_resumen
        *        - validacion_documentos_json
        *
        *    - Ejemplo de $resumen:
        *        "✔️ INE OCR: 14 líneas frente, 9 reverso."
        *
        * ----------------------------------------------------------------------------
        * Dependencias y convenciones:
        *  - Se importa el helper:
        *      require_once __DIR__ . '/../Helpers/ValidacionResumenHelper.php';
        *      use App\Helpers\ValidacionResumenHelper as VRH;
        *  - Semáforo (proceso): 0 = NO_OK ✖️, 1 = OK ✔️, 2 = PENDIENTE ⏳.
        *  - Los payloads incluyen siempre 'tipo' y 'ts' (ISO 8601).
        *
        * Pruebas rápidas (GET):
        *  - /inquilino/{slug}/validar?check=ingresos_simple
        *  - /inquilino/{slug}/validar?check=ocr
        *
        * Consideraciones:
        *  - Si no se encuentran archivos requeridos (INE frente/reverso), se retorna
        *    error JSON y NO se intenta guardar.
        *  - Controlador no atrapa errores de BD (se recomienda tener try/catch en el
        *    modelo o un middleware general).
        * ========================================================================== */

        if (isset($_GET['check']) && $_GET['check'] === 'ocr') {
            // 1) Localizar las s3_key
            $rows = $this->model->archivosPorInquilinoId((int)$inquilino['id']);
            $frontalKey = null;
            $reversoKey = null;

            foreach ($rows as $r) {
                $tipo = strtolower(trim($r['tipo']));
                if ($tipo === 'ine_frontal') $frontalKey = $r['s3_key'];
                if ($tipo === 'ine_reverso') $reversoKey = $r['s3_key'];
            }

            if (!$frontalKey || !$reversoKey) {
                echo json_encode(['ok' => false, 'mensaje' => 'Faltan imágenes: se requiere INE frontal y reverso.']);
                return;
            }

            try {
                // 2) Descargar bytes desde S3
                $fr = $this->s3->getObject(['Bucket' => $this->s3Bucket, 'Key' => $frontalKey]);
                $rv = $this->s3->getObject(['Bucket' => $this->s3Bucket, 'Key' => $reversoKey]);
                $bytesFrontal = (string)$fr['Body'];
                $bytesReverso = (string)$rv['Body'];

                // 3) Textract: DetectDocumentText (simple y rápido)
                $resF = $this->textract->detectDocumentText(['Document' => ['Bytes' => $bytesFrontal]]);
                $resR = $this->textract->detectDocumentText(['Document' => ['Bytes' => $bytesReverso]]);

                // 4) Nos quedamos con las líneas
                $linesF = [];
                foreach (($resF['Blocks'] ?? []) as $b) {
                    if (($b['BlockType'] ?? '') === 'LINE' && !empty($b['Text'])) $linesF[] = $b['Text'];
                }
                $linesR = [];
                foreach (($resR['Blocks'] ?? []) as $b) {
                    if (($b['BlockType'] ?? '') === 'LINE' && !empty($b['Text'])) $linesR[] = $b['Text'];
                }

                // 5) Proceso: OK si ambos lados tienen líneas; PEND si uno; NO_OK si ninguno
                $lf = count($linesF);
                $lr = count($linesR);
                $proceso = ($lf > 0 && $lr > 0) ? 1 : (($lf > 0 || $lr > 0) ? 2 : 0);

                // 6) Payload y resumen humano
                $payload = [
                    'tipo'    => 'ine_ocr_detect',
                    'frontal' => ['lineas' => $lf, 'texto' => $linesF],
                    'reverso' => ['lineas' => $lr, 'texto' => $linesR],
                    'archivos'=> ['ine_frontal_key' => $frontalKey, 'ine_reverso_key' => $reversoKey],
                    'ts'      => date('c'),
                ];
                $resumen = \App\Helpers\ValidacionResumenHelper::ineOcr($proceso, $payload);

                // 7) Guardar en BD (usa el bloque de DOCUMENTOS para OCR genérico)
                $ok = $this->model->guardarValidacionDocumentos((int)$inquilino['id'], (int)$proceso, $payload, $resumen);

                // 8) Respuesta
                echo json_encode([
                    'ok' => (bool)$ok,
                    'mensaje' => $ok ? 'OCR de INE guardado.' : 'No se pudo guardar.',
                    'resultado' => [
                        'proceso' => $proceso,
                        'resumen' => $resumen,
                        'ine_frontal_key' => $frontalKey,
                        'ine_reverso_key' => $reversoKey,
                        'frontal' => ['lineas' => $lf],
                        'reverso' => ['lineas' => $lr],
                    ],
                ]);
            } catch (\Aws\Exception\AwsException $e) {
                echo json_encode([
                    'ok' => false,
                    'mensaje' => 'Error en Textract: ' . $e->getAwsErrorMessage(),
                    'codigo'  => $e->getAwsErrorCode()
                ]);
            }
            return;
        }


        // --- DEBUG: parseo básico de CURP/CIC desde OCR ---
            if (isset($_GET['check']) && $_GET['check'] === 'parse') {
                // Reutilizamos la lógica de ocr para obtener líneas actuales
                $rows = $this->model->archivosPorInquilinoId($inquilino['id']);
                $frontalKey = $reversoKey = null;
                foreach ($rows as $r) {
                    $t = strtolower(trim($r['tipo']));
                    if ($t === 'ine_frontal') $frontalKey = $r['s3_key'];
                    if ($t === 'ine_reverso') $reversoKey = $r['s3_key'];
                }
                if (!$frontalKey || !$reversoKey) {
                    echo json_encode(['ok' => false, 'mensaje' => 'Faltan imágenes: frontal y reverso.']);
                    return;
                }

                try {
                    // 1) Descarga
                    $fr = $this->s3->getObject(['Bucket' => $this->s3Bucket, 'Key' => $frontalKey]);
                    $rv = $this->s3->getObject(['Bucket' => $this->s3Bucket, 'Key' => $reversoKey]);
                    $bytesF = (string)$fr['Body']; $bytesR = (string)$rv['Body'];

                    // 2) OCR (DetectDocumentText)
                    $resF = $this->textract->detectDocumentText(['Document' => ['Bytes' => $bytesF]]);
                    $resR = $this->textract->detectDocumentText(['Document' => ['Bytes' => $bytesR]]);

                    $linesF = []; foreach (($resF['Blocks'] ?? []) as $b) if (($b['BlockType'] ?? '') === 'LINE' && !empty($b['Text'])) $linesF[] = $b['Text'];
                    $linesR = []; foreach (($resR['Blocks'] ?? []) as $b) if (($b['BlockType'] ?? '') === 'LINE' && !empty($b['Text'])) $linesR[] = $b['Text'];

                    // 3) Unimos texto (frontal + reverso) y parseamos
                    $texto = strtoupper(implode("\n", array_merge($linesF, $linesR)));
                    $parsed = $this->ine_parse_curp_cic($texto);

                    echo json_encode([
                        'ok' => true,
                        'mensaje' => 'Parseo básico OK.',
                        'ine_frontal_key' => $frontalKey,
                        'ine_reverso_key' => $reversoKey,
                        'parsed' => $parsed,
                        'debug' => [
                            'lineas_frontal' => count($linesF),
                            'lineas_reverso' => count($linesR)
                        ]
                    ]);
                } catch (\Aws\Exception\AwsException $e) {
                    echo json_encode(['ok' => false, 'mensaje' => 'Error Textract: '.$e->getAwsErrorMessage(), 'codigo'=>$e->getAwsErrorCode()]);
                }
                return;
            }
        // --- FIN DEBUG parse ---    

            // --- DEBUG: nombres/apellidos vía AnalyzeDocument(FORMS) ---
        if (isset($_GET['check']) && $_GET['check'] === 'kv') {
            // localizar claves S3
            $rows = $this->model->archivosPorInquilinoId($inquilino['id']);
            $frontalKey = $reversoKey = null;
            foreach ($rows as $r) {
                $t = strtolower(trim($r['tipo']));
                if ($t === 'ine_frontal') $frontalKey = $r['s3_key'];
                if ($t === 'ine_reverso') $reversoKey = $r['s3_key'];
            }
            if (!$frontalKey || !$reversoKey) {
                echo json_encode(['ok'=>false,'mensaje'=>'Faltan imágenes: frontal y reverso.']); return;
            }

            try {
                // descarga
                $fr = $this->s3->getObject(['Bucket'=>$this->s3Bucket,'Key'=>$frontalKey]);
                $rv = $this->s3->getObject(['Bucket'=>$this->s3Bucket,'Key'=>$reversoKey]);
                $bytesF = (string)$fr['Body']; $bytesR = (string)$rv['Body'];

                // AnalyzeDocument (FORMS) en frontal y reverso
                $adF = $this->textract->analyzeDocument([
                    'Document' => ['Bytes' => $bytesF],
                    'FeatureTypes' => ['FORMS']
                ]);
                $adR = $this->textract->analyzeDocument([
                    'Document' => ['Bytes' => $bytesR],
                    'FeatureTypes' => ['FORMS']
                ]);

                $mapF = $this->textract_kv_map($adF['Blocks'] ?? []);
                $mapR = $this->textract_kv_map($adR['Blocks'] ?? []);
                $merged = $mapF + $mapR; // frontal tiene prioridad

                // sinónimos comunes que usa la INE en distintas versiones
                $synNombres = ['NOMBRE(S)','NOMBRES','NOMBRE'];
                $synApPat   = ['APELLIDO PATERNO','PRIMER APELLIDO','APELLIDO 1','APELLIDO1'];
                $synApMat   = ['APELLIDO MATERNO','SEGUNDO APELLIDO','APELLIDO 2','APELLIDO2'];

                $nombres = $this->kv_pick($merged, $synNombres);
                $apPat   = $this->kv_pick($merged, $synApPat);
                $apMat   = $this->kv_pick($merged, $synApMat);

                // Fallback: si solo tenemos NOMBRE con todo concatenado (APELLIDO P, APELLIDO M, NOMBRES)
            if (!$apPat && !$apMat && $nombres) {
            // 1) Cortar cualquier rastro de "DOMICILIO ..." que Textract pegó
            $clean = strtoupper($nombres);
            $clean = preg_replace('/\bDOMICILIO\b.*$/u', '', $clean);
            // Limpieza de espacios
            $clean = preg_replace('/\s+/', ' ', trim($clean));

            // 2) Partir en tokens
            $tokens = array_values(array_filter(explode(' ', $clean)));

            // 3) Heurística INE: [APELLIDO_P, APELLIDO_M, NOMBRES...]
            if (count($tokens) >= 3) {
                $apPat  = $tokens[0];
                $apMat  = $tokens[1];
                $nombres = implode(' ', array_slice($tokens, 2));
            }
            }


                echo json_encode([
                    'ok' => true,
                    'mensaje' => 'AnalyzeDocument(FORMS) OK.',
                    'resultado' => [
                        'apellidop' => $apPat,
                        'apellidom' => $apMat,
                        'nombres'   => $nombres,
                    ],
                    'debug' => [
                        'keys_detectadas' => array_slice(array_keys($merged), 0, 30), // primeras 30
                    ]
                ]);
            } catch (\Aws\Exception\AwsException $e) {
                echo json_encode(['ok'=>false,'mensaje'=>'Error Textract: '.$e->getAwsErrorMessage(),'codigo'=>$e->getAwsErrorCode()]);
            }
            return;
        }
        // --- FIN DEBUG kv ---

        // --- DEBUG: comparar nombres/apellidos OCR vs BD ---
        if (isset($_GET['check']) && $_GET['check'] === 'match') {
            // 1) Localiza keys
            $rows = $this->model->archivosPorInquilinoId($inquilino['id']);
            $frontalKey = $reversoKey = null;
            foreach ($rows as $r) {
                $t = strtolower(trim($r['tipo']));
                if ($t === 'ine_frontal') $frontalKey = $r['s3_key'];
                if ($t === 'ine_reverso') $reversoKey = $r['s3_key'];
            }
            if (!$frontalKey || !$reversoKey) {
                echo json_encode(['ok'=>false,'mensaje'=>'Faltan imágenes: frontal y reverso.']); return;
            }

            try {
                // 2) Descarga y AnalyzeDocument(FORMS)
                $fr = $this->s3->getObject(['Bucket'=>$this->s3Bucket,'Key'=>$frontalKey]);
                $rv = $this->s3->getObject(['Bucket'=>$this->s3Bucket,'Key'=>$reversoKey]);
                $bytesF = (string)$fr['Body']; $bytesR = (string)$rv['Body'];

                $adF = $this->textract->analyzeDocument(['Document'=>['Bytes'=>$bytesF],'FeatureTypes'=>['FORMS']]);
                $adR = $this->textract->analyzeDocument(['Document'=>['Bytes'=>$bytesR],'FeatureTypes'=>['FORMS']]);

                $mapF = $this->textract_kv_map($adF['Blocks'] ?? []);
                $mapR = $this->textract_kv_map($adR['Blocks'] ?? []);
                $merged = $mapF + $mapR;

                $synNombres = ['NOMBRE(S)','NOMBRES','NOMBRE'];
                $synApPat   = ['APELLIDO PATERNO','PRIMER APELLIDO','APELLIDO 1','APELLIDO1'];
                $synApMat   = ['APELLIDO MATERNO','SEGUNDO APELLIDO','APELLIDO 2','APELLIDO2'];

                $nombres = $this->kv_pick($merged, $synNombres);
                $apPat   = $this->kv_pick($merged, $synApPat);
                $apMat   = $this->kv_pick($merged, $synApMat);

                // Fallback cuando viene todo en "NOMBRE"
                if (!$apPat && !$apMat && $nombres) {
                    $clean = strtoupper($nombres);
                    $clean = preg_replace('/\bDOMICILIO\b.*$/u', '', $clean);
                    $clean = preg_replace('/\s+/', ' ', trim($clean));
                    $tokens = array_values(array_filter(explode(' ', $clean)));
                    if (count($tokens) >= 3) {
                        $apPat  = $tokens[0];
                        $apMat  = $tokens[1];
                        $nombres = implode(' ', array_slice($tokens, 2));
                    }
                }

                // 3) Normaliza OCR vs BD
                $ocr = [
                    'apellidop' => $this->_norm_txt($apPat),
                    'apellidom' => $this->_norm_txt($apMat),
                    'nombres'   => $this->_norm_txt($nombres),
                ];
                $bd  = [
                    'apellidop' => $this->_norm_txt($inquilino['apellidop_inquilino'] ?? ''),
                    'apellidom' => $this->_norm_txt($inquilino['apellidom_inquilino'] ?? ''),
                    'nombres'   => $this->_norm_txt($inquilino['nombre_inquilino'] ?? ''),
                ];

                $match = [
                    'apellidop' => ($ocr['apellidop'] !== '' && $ocr['apellidop'] === $bd['apellidop']),
                    'apellidom' => ($ocr['apellidom'] !== '' && $ocr['apellidom'] === $bd['apellidom']),
                    'nombres'   => ($ocr['nombres']   !== '' && $ocr['nombres']   === $bd['nombres']),
                ];
                $overall = ($match['apellidop'] && $match['apellidom'] && $match['nombres']);

                echo json_encode([
                    'ok'=>true,
                    'mensaje'=>'Comparación OCR vs BD',
                    'resultado'=>[
                        'overall'=>$overall,
                        'detalles'=>$match,
                        'ocr'=>$ocr,
                        'bd'=>$bd
                    ]
                ]);
            } catch (\Aws\Exception\AwsException $e) {
                echo json_encode(['ok'=>false,'mensaje'=>'Error Textract: '.$e->getAwsErrorMessage(),'codigo'=>$e->getAwsErrorCode()]);
            }
            return;
        }
        // --- FIN DEBUG match ---

        // --- LISTAR COMPROBANTES DE INGRESO (sin OCR) + GUARDAR RESUMEN ------------
        if (isset($_GET['check']) && $_GET['check'] === 'ingresos_list') {
            // 1) Obtener archivos del inquilino
            $rows = $this->model->archivosPorInquilinoId((int)$inquilino['id']);

            $items = [];
            foreach ($rows as $r) {
                if (strtolower(trim($r['tipo'])) !== 'comprobante_ingreso') continue;
                $key = $r['s3_key'] ?? '';
                if (!$key) continue;
                $ext = strtolower(pathinfo($key, PATHINFO_EXTENSION));
                $items[] = [
                    's3_key' => $key,
                    'ext'    => $ext ?: null,
                    'size'   => $r['size'] ?? null,
                    'mime'   => $r['mime_type'] ?? null,
                ];
            }

            $total = count($items);

            // 2) Convención de proceso: PENDIENTE si hay ≥1, NO_OK si 0
            $proceso = ($total > 0) ? 2 : 0;

            // 3) Payload y resumen humano
            $payload = [
                'tipo'   => 'ingresos_list',
                'total'  => $total,
                'items'  => $items,
                'ts'     => date('c'),
            ];
            $resumen = \App\Helpers\ValidacionResumenHelper::ingresosList($proceso, $payload);

            // 4) Guardar proceso + resumen + JSON
            $ok = $this->model->guardarValidacionIngresosList((int)$inquilino['id'], (int)$proceso, $payload, $resumen);

            // 5) Respuesta
            echo json_encode([
                'ok'      => (bool)$ok,
                'mensaje' => $ok ? 'Listado de comprobantes guardado.' : 'No se pudo guardar el listado.',
                'resultado' => [
                    'proceso' => $proceso,
                    'resumen' => $resumen,
                    'total'   => $total,
                    'items'   => $items,
                ],
            ], JSON_UNESCAPED_UNICODE);
            return;
        }


        // --- VALIDAR COMPROBANTES (PDF) - SIMPLE POR CONTEO ----------------------
    if (isset($_GET['check']) && $_GET['check'] === 'ingresos_simple') {
        // 1) Traer archivos del inquilino
        $rows = $this->model->archivosPorInquilinoId((int)$inquilino['id']);

        $pdfs = [];
        foreach ($rows as $r) {
            if (strtolower(trim($r['tipo'])) !== 'comprobante_ingreso') continue;
            $key = $r['s3_key'] ?? '';
            $ext = strtolower(pathinfo($key, PATHINFO_EXTENSION));
            if ($ext === 'pdf') {
                $pdfs[] = [
                    's3_key' => $key,
                    'ext'    => $ext,
                    'size'   => $r['size'] ?? null,
                    'mime'   => $r['mime_type'] ?? null,
                ];
            }
        }

        $n = count($pdfs);

        // Reglas simples:
        //  - OK      : 3 o más PDFs (recomendado: 3 meses)
        //  - REVIEW  : 1 o 2 PDFs (pedir más)
        //  - FAIL    : 0 PDFs
        $status = ($n >= 3) ? 'OK' : (($n >= 1) ? 'REVIEW' : 'FAIL');

        // 2) Payload con reglas y resultados
        $payload = [
            'tipo'      => 'ingresos_pdf_simple',
            'conteo'    => $n,
            'archivos'  => $pdfs,
            'reglas'    => [
                'min_recomendado' => 3,
                'criterio'        => 'OK si hay >= 3 PDFs; REVIEW si 1-2; FAIL si 0.',
            ],
            'status'    => $status,
            'ts'        => date('c'),
        ];

        // 3) Mapear a proceso (0/1/2)
        $proceso = ($status === 'OK') ? 1 : (($status === 'FAIL') ? 0 : 2); // REVIEW => 2

        // 4) Resumen humano
        $resumen = \App\Helpers\ValidacionResumenHelper::ingresosSimple($proceso, $payload);

        // 5) Guardar en la tabla inquilinos_validaciones (columnas *_resumen/*_json)
        //    Nota: requiere método del modelo guardarValidacionIngresosSimple($id, $proceso, $payload, $resumen)
        $ok = $this->model->guardarValidacionIngresosSimple((int)$inquilino['id'], (int)$proceso, $payload, $resumen);

        // 6) Respuesta
        echo json_encode([
            'ok'       => (bool)$ok,
            'mensaje'  => 'Validación de comprobantes (simple) guardada.',
            'resultado'=> [
                'status'  => $status,
                'proceso' => $proceso,
                'resumen' => $resumen,
                'conteo'  => $n
            ],
        ]);
        return;
    }

        // --- GUARDAR PAGO INICIAL ---------------------------------------------------
    if (isset($_GET['check']) && $_GET['check'] === 'pago_inicial') {
        // 1) Leer parámetros (pueden venir por GET) y normalizar
        $montoRaw = $_GET['monto']      ?? null;   // acepta "12500", "12,500.00", "$12,500.00"
        $fechaRaw = $_GET['fecha']      ?? null;   // preferente YYYY-MM-DD
        $ref      = trim((string)($_GET['referencia'] ?? ''));

        // Normalizar monto
        $monto = null;
        if ($montoRaw !== null && $montoRaw !== '') {
            $s = str_replace(['$', ',', ' '], '', (string)$montoRaw);
            if (is_numeric($s)) $monto = (float)$s;
        }

        // Validar/normalizar fecha
        $fecha = null;
        if ($fechaRaw) {
            // aceptar YYYY-MM-DD o intentar convertir d/m/Y
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaRaw)) {
                $fecha = $fechaRaw;
            } elseif (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $fechaRaw)) {
                [$d,$m,$y] = explode('/', $fechaRaw);
                $fecha = sprintf('%04d-%02d-%02d', (int)$y, (int)$m, (int)$d);
            }
        }

        // 2) Decidir proceso
        $hasMonto = ($monto !== null && $monto > 0);
        $hasFecha = ($fecha !== null);
        $hasRef   = ($ref !== '');

        $proceso = 0; // NO_OK
        if ($hasMonto && $hasFecha) {
            $proceso = 1; // OK
        } elseif ($hasMonto || $hasFecha || $hasRef) {
            $proceso = 2; // PENDIENTE
        }

        // 3) Payload y Resumen humano
        $payload = [
            'tipo'   => 'pago_inicial',
            'monto'  => $monto,
            'fecha'  => $fecha,
            'referencia' => $ref,
            'ts'     => date('c'),
            'origen' => 'manual_params', // o 'banco_webhook' si luego viene de webhook
        ];

        $resumen = \App\Helpers\ValidacionResumenHelper::pagoInicial($proceso, $payload);

        // 4) Guardar en BD
        $ok = $this->model->guardarPagoInicial(
            (int)$inquilino['id'],
            (int)$proceso,
            $payload,
            $resumen
        );

        // 5) Respuesta
        echo json_encode([
            'ok' => (bool)$ok,
            'mensaje' => $ok ? 'Pago inicial guardado.' : 'No se pudo guardar el pago inicial.',
            'resultado' => [
                'proceso' => $proceso,
                'resumen' => $resumen,
                'payload' => $payload,
            ]
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    // --- INVESTIGACIÓN DE DEMANDAS ---------------------------------------------
    if (isset($_GET['check']) && $_GET['check'] === 'demandas') {
        // 1) Leer params: hit (bool), fuentes (csv o arreglo), folio (string)
        $hitRaw = strtolower(trim((string)($_GET['hit'] ?? '')));
        $hit = in_array($hitRaw, ['1','true','sí','si','yes'], true) ? true
            : (in_array($hitRaw, ['0','false','no'], true) ? false : null);

        // fuentes: aceptar ?fuentes=TSJCDMX,RENADE o múltiples ?fuentes[]=...
        $fuentes = [];
        if (isset($_GET['fuentes'])) {
            if (is_array($_GET['fuentes'])) {
                foreach ($_GET['fuentes'] as $f) {
                    $f = trim((string)$f);
                    if ($f !== '') $fuentes[] = $f;
                }
            } else {
                foreach (explode(',', (string)$_GET['fuentes']) as $f) {
                    $f = trim($f);
                    if ($f !== '') $fuentes[] = $f;
                }
            }
        }
        $folio = trim((string)($_GET['folio'] ?? ''));

        // 2) Decidir proceso
        //    - hit === true   → 0 (NO_OK)
        //    - hit === false  → 1 (OK)
        //    - hit === null   → 2 (PEND), salvo que haya señales claras
        if ($hit === true) {
            $proceso = 0;
        } elseif ($hit === false) {
            $proceso = 1;
        } else {
            $proceso = (!empty($fuentes) || $folio !== '') ? 2 : 2;
        }

        // 3) Payload y resumen humano
        $payload = [
            'tipo'    => 'inv_demandas',
            'hit'     => $hit,
            'fuentes' => $fuentes,
            'folio'   => $folio,
            'ts'      => date('c'),
            'nota'    => 'Resultado cargado manualmente vía querystring',
        ];
        $resumen = \App\Helpers\ValidacionResumenHelper::invDemandas($proceso, $payload);

        // 4) Guardar
        $ok = $this->model->guardarInvestigacionDemandas(
            (int)$inquilino['id'],
            (int)$proceso,
            $payload,
            $resumen
        );

        // 5) Respuesta
        echo json_encode([
            'ok'      => (bool)$ok,
            'mensaje' => $ok ? 'Investigación de demandas guardada.' : 'No se pudo guardar.',
            'resultado' => [
                'proceso' => $proceso,
                'resumen' => $resumen,
                'payload' => $payload,
            ],
        ], JSON_UNESCAPED_UNICODE);
        return;
    }




        
    /**
     * Extrae señales de un estado de cuenta (BBVA y similares) desde texto OCR.
     * Devuelve:
     *   - monto    : número (float) — usa "Total Abonos" / "Depósitos / Abonos" si existe; si no, el monto más grande con $ o MXN
     *   - mes_anno : string "MM-YYYY" — intenta múltiples formatos: "11/JUL", "08/2025", "agosto 2025", "14/08/2025", "2025-08-14"
     *   - origen   : palabra clave detectada (bbva, abonos, depósitos, nómina, etc.)
     */
    function parseComprobanteIngresoBBVA(string $text): array
    {
        $out = ['monto' => null, 'mes_anno' => null, 'origen' => null];

        // Normalizar: una línea y espacios simples
        $t = preg_replace('/[ \t]+/u', ' ', str_replace(["\r", "\n"], ' ', $text));

        // =========================
        // 1) MONTO (ABONOS BBVA)
        // =========================
        $monto = null;

        // Patrones típicos en estados BBVA (pueden variar por plantilla):
        //   "Abonos", "Total Abonos", "Depósitos / Abonos", "Depósitos y abonos"
        if (preg_match_all(
            '/(?:Total\s+Abonos|Abonos(?:\s+del\s+Per[ií]odo)?|Dep[óo]sitos(?:\s*(?:\/|y)\s*Abonos)?)\D{0,20}\$?\s*([0-9]{1,3}(?:[.,\s][0-9]{3})*(?:[.,][0-9]{2})?)/iu',
            $t,
            $mm
        )) {
            foreach ($mm[1] as $raw) {
                // Normalizar separadores: quitar miles (coma/espacio) y usar punto para decimales
                $num = (float) str_replace([',', ' '], ['', ''], str_replace(',', '.', $raw));
                if ($num > 0) $monto = max($monto ?? 0, $num);
            }
        }

        // Fallback: mayor cantidad que aparezca con $ o MXN
        if ($monto === null && preg_match_all('/(?:\$|\bMXN\b)\s*([0-9]{1,3}(?:[.,\s][0-9]{3})*(?:[.,][0-9]{2})?)/iu', $t, $mm2)) {
            foreach ($mm2[1] as $raw) {
                $num = (float) str_replace([',', ' '], ['', ''], str_replace(',', '.', $raw));
                if ($num > 0) $monto = max($monto ?? 0, $num);
            }
        }
        $out['monto'] = $monto;

        // =========================
        // 2) FECHAS → "MM-YYYY"
        // =========================
        $candidatos = [];

        // a) Texto "agosto 2025" o "ago 2025"
        $mesesMap = [
            'enero'=>1,'febrero'=>2,'marzo'=>3,'abril'=>4,'mayo'=>5,'junio'=>6,
            'julio'=>7,'agosto'=>8,'septiembre'=>9,'setiembre'=>9,'octubre'=>10,'noviembre'=>11,'diciembre'=>12
        ];
        $abrMap = ['ene'=>'enero','feb'=>'febrero','mar'=>'marzo','abr'=>'abril','may'=>'mayo','jun'=>'junio','jul'=>'julio','ago'=>'agosto','sep'=>'septiembre','set'=>'septiembre','oct'=>'octubre','nov'=>'noviembre','dic'=>'diciembre'];

        if (preg_match_all('/\b(ene|feb|mar|abr|may|jun|jul|ago|sep|set|oct|nov|dic|enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|setiembre|octubre|noviembre|diciembre)\b[^\d]{0,6}(\d{4})/iu', $t, $m, PREG_SET_ORDER)) {
            foreach ($m as $hit) {
                $mesTxt = mb_strtolower($hit[1], 'UTF-8');
                $year   = (int) $hit[2];
                $mesFull = $abrMap[$mesTxt] ?? $mesTxt;
                $mes = $mesesMap[$mesFull] ?? null;
                if ($mes) $candidatos[] = sprintf('%02d-%04d', $mes, $year);
            }
        }

        // b) "11/JUL" (sin año) → estimar año (si hay 20xx en texto, usamos ese; si no, año actual)
        $mapAbr = ['ENE'=>1,'FEB'=>2,'MAR'=>3,'ABR'=>4,'MAY'=>5,'JUN'=>6,'JUL'=>7,'AGO'=>8,'SEP'=>9,'SET'=>9,'OCT'=>10,'NOV'=>11,'DIC'=>12];
        if (preg_match_all('/\b([0-3]?\d)[\/\-](ENE|FEB|MAR|ABR|MAY|JUN|JUL|AGO|SEP|SET|OCT|NOV|DIC)\b/u', strtoupper($t), $m, PREG_SET_ORDER)) {
            $year = null;
            if (preg_match('/\b(20\d{2})\b/u', $t, $y)) $year = (int) $y[1];
            if ($year === null) $year = (int) date('Y');
            foreach ($m as $hit) {
                $mes = $mapAbr[$hit[2]] ?? null;
                if ($mes) $candidatos[] = sprintf('%02d-%04d', $mes, $year);
            }
        }

        // c) "08/2025" o "08-2025"
        if (preg_match_all('/\b(0?[1-9]|1[0-2])[\/\-](\d{4})\b/u', $t, $m, PREG_SET_ORDER)) {
            foreach ($m as $hit) $candidatos[] = sprintf('%02d-%04d', (int) $hit[1], (int) $hit[2]);
        }

        // d) "14/08/2025" o "14-08-2025" → usar MES/AÑO
        if (preg_match_all('/\b(0?[1-9]|[12][0-9]|3[01])[\/\-](0?[1-9]|1[0-2])[\/\-](\d{4})\b/u', $t, $m, PREG_SET_ORDER)) {
            foreach ($m as $hit) $candidatos[] = sprintf('%02d-%04d', (int) $hit[2], (int) $hit[3]);
        }

        // e) "2025-08-14" → usar 08/2025
        if (preg_match_all('/\b(\d{4})-(0?[1-9]|1[0-2])-(0?[1-9]|[12][0-9]|3[01])\b/u', $t, $m, PREG_SET_ORDER)) {
            foreach ($m as $hit) $candidatos[] = sprintf('%02d-%04d', (int) $hit[2], (int) $hit[1]);
        }

        // Elegir el más reciente (YYYYMM descendente)
        if (!empty($candidatos)) {
            usort($candidatos, function ($a, $b) {
                [$ma, $ya] = explode('-', $a);
                [$mb, $yb] = explode('-', $b);
                return (int)($yb . $mb) <=> (int)($ya . $ma);
            });
            $out['mes_anno'] = $candidatos[0];
        }

        // =========================
        // 3) ORIGEN (heurístico)
        // =========================
        if (preg_match('/(bbva|abonos|dep[óo]sitos|n[óo]mina|recibo|estado\s+de\s+cuenta|saldo|movimientos)/iu', $t, $m)) {
            $out['origen'] = $m[1];
        }

        return $out;
    }

    // --- VALIDAR COMPROBANTES (OCR con Textract, copia a us-east-1) --------------
    if (isset($_GET['check']) && $_GET['check'] === 'ingresos_ocr') {
        if (empty($this->s3Bucket)) {
            echo json_encode(['ok'=>false,'mensaje'=>'No hay bucket (MX) en $this->s3Bucket.']);
            return;
        }

        // Bucket destino (us-east-1) para Textract
        $usBucket = $this->s3BucketUs ?: 'copia-inquilinos-us';

        // Id del inquilino (GET o del contexto)
        $idInq = isset($_GET['id_inquilino']) ? (int)$_GET['id_inquilino'] : (int)($inquilino['id'] ?? 0);
        if (!$idInq) {
            echo json_encode(['ok'=>false,'mensaje'=>'Falta id_inquilino para validar ingresos.']);
            return;
        }

        // 1) Traer archivos del inquilino
        $rows = $this->model->archivosPorInquilinoId($idInq);

        // 2) Filtrar comprobantes (pdf/jpg/png/jpeg)
        $docs = [];
        foreach ($rows as $r) {
            if (strtolower(trim($r['tipo'])) !== 'comprobante_ingreso') continue;
            $key = $r['s3_key'] ?? '';
            if (!$key) continue;
            $ext = strtolower(pathinfo($key, PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf','jpg','jpeg','png'])) continue;
            $docs[] = [
                's3_key' => $key,
                'ext'    => $ext,
                'size'   => $r['size'] ?? null,
                'mime'   => $r['mime_type'] ?? null,
            ];
        }

        // Si no hay documentos, guardar FAIL y salir
        if (empty($docs)) {
            $payload = [
                'tipo'     => 'ingresos_ocr',
                'status'   => 'FAIL',
                'motivo'   => 'No hay comprobantes de ingreso (pdf/jpg/png) para analizar.',
                'archivos' => [],
                'ts'       => date('c'),
                'metricas' => [
                    'docs_total'        => 0,
                    'docs_analizados'   => 0,
                    'meses_unicos'      => [],
                    'meses_en_rango6'   => 0,
                    'montos_detectados' => [],
                    'sueldo_declarado'  => null,
                    'consistencia_monto'=> null,
                    'texto_ocr_total'   => 0,
                ],
                'detalles' => [],
                'reglas'   => [
                    'min_meses'  => 1,
                    'ventana'    => '6 meses',
                    'consistencia_monto' => '±20% contra sueldo declarado (si existe)',
                ],
            ];

            $proceso = 0; // FAIL
            $resumen = \App\Helpers\ValidacionResumenHelper::ingresosOcr($proceso, $payload);
            $ok = $this->model->guardarValidacionIngresosOCR($idInq, $proceso, $payload, $resumen);

            echo json_encode([
                'ok'=> (bool)$ok,
                'mensaje'=>'Sin comprobantes',
                'resultado'=>[
                    'status'=>'FAIL',
                    'proceso'=>$proceso,
                    'resumen'=>$resumen,
                    'meses_en_rango6'=>0,
                    'docs'=>0,
                    'motivo'=>$payload['motivo']
                ]
            ]);
            return;
        }

        // 3) Analizar cada documento con Textract (copiando a us-east-1)
        $analizados = [];
        foreach ($docs as $doc) {
            $textContent = '';
            $perDocMotivo = [];
            $debug = ['pipeline' => []];
            $dstKey = null;

            try {
                // Copiar SIEMPRE el objeto al bucket de us-east-1 (Textract exige misma región)
                $dstKey = 'textract-cache/' . ltrim($doc['s3_key'], '/');
                $this->s3Us->copyObject([
                    'Bucket'     => $usBucket,
                    'Key'        => $dstKey,
                    'CopySource' => $this->s3Bucket . '/' . $doc['s3_key'],
                    'ACL'        => 'private',
                ]);
                $debug['pipeline'][] = 'S3CopyToUSE1:OK';

                if ($doc['ext'] === 'pdf') {
                    // ====== PDF: Start + polling con timeout largo ======
                    $start = $this->textract->startDocumentTextDetection([
                        'DocumentLocation' => [
                            'S3Object' => ['Bucket' => $usBucket, 'Name' => $dstKey]
                        ]
                    ]);

                    $jobId = $start->get('JobId');
                    $maxWait = 120; // segundos
                    $elapsed = 0;
                    $sleep  = 3;

                    $blocksAll = [];
                    while (true) {
                        $res = $this->textract->getDocumentTextDetection(['JobId' => $jobId]);
                        $statusJob = $res->get('JobStatus');
                        if ($statusJob === 'SUCCEEDED') {
                            $blocksAll = array_merge($blocksAll, $res->get('Blocks') ?? []);
                            $nextToken = $res->get('NextToken') ?? null;
                            while ($nextToken) {
                                $res = $this->textract->getDocumentTextDetection(['JobId'=>$jobId,'NextToken'=>$nextToken]);
                                $blocksAll = array_merge($blocksAll, $res->get('Blocks') ?? []);
                                $nextToken = $res->get('NextToken') ?? null;
                            }
                            $debug['pipeline'][] = 'TextDetection:SUCCEEDED';
                            break;
                        } elseif ($statusJob === 'FAILED') {
                            $perDocMotivo[] = 'TextDetection FAILED';
                            $debug['pipeline'][] = 'TextDetection:FAILED';
                            break;
                        }
                        if ($elapsed >= $maxWait) {
                            $perDocMotivo[] = 'Timeout TextDetection';
                            $debug['pipeline'][] = 'TextDetection:TIMEOUT';
                            break;
                        }
                        sleep($sleep);
                        $elapsed += $sleep;
                    }

                    // 1) Intento con LINE
                    if ($textContent === '' && !empty($blocksAll)) {
                        $buf = [];
                        foreach ($blocksAll as $b) {
                            if (($b['BlockType'] ?? '') === 'LINE' && isset($b['Text'])) $buf[] = $b['Text'];
                        }
                        if (!empty($buf)) {
                            $textContent = implode("\n", $buf);
                            $debug['pipeline'][] = 'LinesOK';
                        }
                    }

                    // 2) Fallback con WORD
                    if ($textContent === '' && !empty($blocksAll)) {
                        $buf = [];
                        foreach ($blocksAll as $b) {
                            if (($b['BlockType'] ?? '') === 'WORD' && isset($b['Text'])) $buf[] = $b['Text'];
                        }
                        if (!empty($buf)) {
                            $textContent = trim(implode(' ', $buf));
                            $perDocMotivo[] = 'Texto vía WORD';
                            $debug['pipeline'][] = 'WordsFallbackOK';
                        }
                    }

                    // 3) Segundo intento con AnalyzeDocument si sigue vacío
                    if ($textContent === '') {
                        $startA = $this->textract->startDocumentAnalysis([
                            'DocumentLocation' => [
                                'S3Object' => ['Bucket' =>  $usBucket, 'Name' => $dstKey]
                            ],
                            'FeatureTypes' => ['TABLES','FORMS'],
                        ]);
                        $jobA = $startA->get('JobId');
                        $elapsed = 0; $blocksAll = [];
                        while (true) {
                            $resA = $this->textract->getDocumentAnalysis(['JobId' => $jobA]);
                            $statusA = $resA->get('JobStatus');
                            if ($statusA === 'SUCCEEDED') {
                                $blocksAll = array_merge($blocksAll, $resA->get('Blocks') ?? []);
                                $nextToken = $resA->get('NextToken') ?? null;
                                while ($nextToken) {
                                    $resA = $this->textract->getDocumentAnalysis(['JobId'=>$jobA,'NextToken'=>$nextToken]);
                                    $blocksAll = array_merge($blocksAll, $resA->get('Blocks') ?? []);
                                    $nextToken = $resA->get('NextToken') ?? null;
                                }
                                $debug['pipeline'][] = 'AnalyzeDocument:SUCCEEDED';
                                break;
                            } elseif ($statusA === 'FAILED') {
                                $perDocMotivo[] = 'AnalyzeDocument FAILED';
                                $debug['pipeline'][] = 'AnalyzeDocument:FAILED';
                                break;
                            }
                            if ($elapsed >= $maxWait) { $perDocMotivo[]='Timeout AnalyzeDocument'; $debug['pipeline'][]='AnalyzeDocument:TIMEOUT'; break; }
                            sleep($sleep);
                            $elapsed += $sleep;
                        }
                        if ($textContent === '' && !empty($blocksAll)) {
                            $buf = [];
                            foreach ($blocksAll as $b) if (isset($b['Text'])) $buf[] = $b['Text'];
                            if (!empty($buf)) {
                                $textContent = trim(implode("\n", $buf));
                                $perDocMotivo[] = 'Texto desde AnalyzeDocument';
                                $debug['pipeline'][] = 'AnalyzeExtractOK';
                            }
                        }
                    }

                } else {
                    // ====== Imagen: DetectDocumentText (sobre el objeto copiado en us-east-1) ======
                    $res = $this->textract->detectDocumentText([
                        'Document' => ['S3Object' => ['Bucket' => $usBucket, 'Name' => $dstKey]]
                    ]);
                    $lines = [];
                    foreach (($res->get('Blocks') ?? []) as $b) {
                        if (($b['BlockType'] ?? '') === 'LINE' && isset($b['Text'])) $lines[] = $b['Text'];
                    }
                    if (empty($lines)) {
                        foreach (($res->get('Blocks') ?? []) as $b) {
                            if (($b['BlockType'] ?? '') === 'WORD' && isset($b['Text'])) $lines[] = $b['Text'];
                        }
                    }
                    $textContent = implode("\n", $lines);
                    $debug['pipeline'][] = 'ImageDetectText';
                }
            } catch (\Throwable $e) {
                $analizados[] = [
                    's3_key' => $doc['s3_key'],
                    'ext'    => $doc['ext'],
                    'ok'     => false,
                    'error'  => $e->getMessage(),
                    'bucket_in' => $this->s3Bucket,
                    'bucket_textract' => $usBucket,
                    'dst_key' => $dstKey,
                ];
                continue;
            }

            // Parse (monto / mes-año / origen) — helper BBVA
            $parsed = parseComprobanteIngresoBBVA($textContent);

            $analizados[] = [
                's3_key'     => $doc['s3_key'],
                'ext'        => $doc['ext'],
                'ok'         => true,
                'texto_len'  => strlen($textContent),
                'extracto'   => mb_substr($textContent, 0, 500),
                'parsed'     => $parsed,
                'motivo_doc' => implode(' | ', $perDocMotivo),
                'ocr_debug'  => $debug,
                'bucket_in'  => $this->s3Bucket,
                'bucket_textract' => $usBucket,
                'dst_key'    => $dstKey,
            ];
        }

        // 4) Reglas de negocio (>=1 mes dentro de 6 meses y consistencia de monto)
        $mesesUnicos = [];
        $montos      = [];
        $textoTotal  = 0;
        foreach ($analizados as $a) {
            if (!empty($a['ok'])) $textoTotal += (int)($a['texto_len'] ?? 0);
            if (!($a['ok'] ?? false)) continue;
            $p = $a['parsed'] ?? [];
            if (!empty($p['mes_anno'])) $mesesUnicos[$p['mes_anno']] = true;
            if (!empty($p['monto']))    $montos[] = (float)$p['monto'];
        }

        $hoy = new \DateTime('now');
        $hace6 = (clone $hoy)->modify('-6 months');

        $mesesEnRango = 0;
        foreach (array_keys($mesesUnicos) as $mmYYYY) {
            [$mm,$yyyy] = explode('-', $mmYYYY);
            $d = \DateTime::createFromFormat('!m-Y', $mm.'-'.$yyyy);
            if ($d && $d >= $hace6 && $d <= $hoy) $mesesEnRango++;
        }

        $sueldoDeclarado = $this->model->obtenerSueldoDeclarado($idInq); // float|null
        $consistenciaMonto = null;
        if ($sueldoDeclarado !== null && !empty($montos)) {
            $prom = array_sum($montos) / max(count($montos),1);
            $delta = abs($prom - $sueldoDeclarado);
            $consistenciaMonto = ($delta <= ($sueldoDeclarado * 0.20)); // ±20%
        }

        // === REGLAS Y MOTIVOS ====================================================
        $status = 'FAIL';
        $motivo = '';

        if ($mesesEnRango >= 1 && ($sueldoDeclarado === null || $consistenciaMonto !== false)) {
            $status = 'OK';
            $motivo = "Se detectó al menos 1 mes válido en los últimos 6 meses"
                    . ($sueldoDeclarado !== null ? " y el monto es consistente con el sueldo declarado." : " (sin sueldo declarado para comparar).");
        } elseif ($mesesEnRango >= 1) {
            $status = 'REVIEW';
            $motivo = "Se detectó(n) {$mesesEnRango} mes(es) válido(s), pero el monto no coincide con el sueldo declarado (±20%).";
        } else {
            if ($textoTotal > 0) {
                $status = 'REVIEW';
                $motivo = "Se leyó texto OCR ({$textoTotal} caracteres), pero no se detectaron fechas de mes/año válidas.";
            } else {
                $status = 'FAIL';
                $motivo = "No se pudo leer texto con OCR en ninguno de los comprobantes.";
            }
        }

        $payload = [
            'tipo'   => 'ingresos_ocr',
            'status' => $status,
            'motivo' => $motivo,
            'metricas' => [
                'docs_total'        => count($docs),
                'docs_analizados'   => count($analizados),
                'meses_unicos'      => array_keys($mesesUnicos),
                'meses_en_rango6'   => $mesesEnRango,
                'montos_detectados' => $montos,
                'sueldo_declarado'  => $sueldoDeclarado,
                'consistencia_monto'=> $consistenciaMonto,
                'texto_ocr_total'   => $textoTotal,
            ],
            'detalles' => $analizados,
            'reglas' => [
                'min_meses'  => 1,
                'ventana'    => '6 meses',
                'consistencia_monto' => '±20% contra sueldo declarado (si existe)',
            ],
            'ts' => date('c'),
        ];

        // Mapear status textual a proceso (0/1/2)
        $proceso = ($status === 'OK') ? 1 : (($status === 'FAIL') ? 0 : 2); // REVIEW => 2

        // Resumen humano
        $resumen = \App\Helpers\ValidacionResumenHelper::ingresosOcr($proceso, $payload);

        // Guardar proceso + resumen + JSON
        $ok = $this->model->guardarValidacionIngresosOCR(
            (int)$idInq,
            (int)$proceso,
            $payload,
            $resumen
        );

        echo json_encode([
            'ok'      => (bool)$ok,
            'mensaje' => 'Validación de comprobantes (OCR) guardada.',
            'resultado' => [
                'status'          => $status,
                'proceso'         => $proceso,
                'resumen'         => $resumen,
                'meses_en_rango6' => $mesesEnRango,
                'docs'            => count($docs),
                'motivo'          => $motivo,
                'detalles'        => $analizados,
            ],
        ]);
        return;
    }


    

/**
 * Helper: parsea el texto OCR y saca señales útiles.
 * - monto (ej: $18,500.00)
 * - mes y año (devuelve "MM-YYYY")
 * - empleador/banco (heurístico)
 */
function parseComprobanteIngreso(string $text): array {
    $out = [
        'monto'    => null,
        'mes_anno' => null, // "MM-YYYY"
        'origen'   => null,
    ];

    // Normaliza espacios
    $t = preg_replace('/[ \t]+/', ' ', str_replace(["\r","\n"], ' ', $text));

    // ======================
    // 1) MONTO (toma el mayor monto con prefijo $ o MXN)
    // ======================
    $monto = null;
    if (preg_match_all('/(?:\$|\bMXN\b)\s*([0-9]{1,3}(?:[,\s][0-9]{3})*(?:\.[0-9]{2})?)/i', $t, $mm)) {
        $nums = [];
        foreach ($mm[1] as $raw) {
            $num = (float) str_replace([',',' '], '', $raw);
            if ($num > 0) $nums[] = $num;
        }
        if (!empty($nums)) $monto = max($nums);
    }
    $out['monto'] = $monto;

    // ======================
    // 2) FECHAS → derivar MM-YYYY
    //    Cubrimos:
    //    - "agosto 2025", "ago 2025", "AGO-2025"
    //    - "08/2025", "08-2025"
    //    - "14/08/2025", "14-08-2025", "2025-08-14"
    // ======================
    $mesesMap = [
        'enero'=>1,'febrero'=>2,'marzo'=>3,'abril'=>4,'mayo'=>5,'junio'=>6,
        'julio'=>7,'agosto'=>8,'septiembre'=>9,'setiembre'=>9,'octubre'=>10,'noviembre'=>11,'diciembre'=>12
    ];
    $mapAbr = ['ene'=>'enero','feb'=>'febrero','mar'=>'marzo','abr'=>'abril','may'=>'mayo','jun'=>'junio','jul'=>'julio','ago'=>'agosto','sep'=>'septiembre','set'=>'septiembre','oct'=>'octubre','nov'=>'noviembre','dic'=>'diciembre'];

    $candidatos = [];

    // a) "ago 2025" / "agosto 2025" / "AGO-2025"
    if (preg_match_all('/\b(ene|feb|mar|abr|may|jun|jul|ago|sep|set|oct|nov|dic|enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|setiembre|octubre|noviembre|diciembre)\b[^\d]{0,4}(\d{4})/i', $t, $m, PREG_SET_ORDER)) {
        foreach ($m as $hit) {
            $mesTxt = mb_strtolower($hit[1], 'UTF-8');
            $year   = (int)$hit[2];
            $mesFull = $mapAbr[$mesTxt] ?? $mesTxt;
            $mes = $mesesMap[$mesFull] ?? null;
            if ($mes && $year >= 2000 && $year < 2100) {
                $candidatos[] = sprintf('%02d-%04d', $mes, $year);
            }
        }
    }

    // b) "08/2025" o "08-2025"
    if (preg_match_all('/\b(0?[1-9]|1[0-2])[\/\-](\d{4})\b/', $t, $m, PREG_SET_ORDER)) {
        foreach ($m as $hit) {
            $mes = (int)$hit[1];
            $year= (int)$hit[2];
            $candidatos[] = sprintf('%02d-%04d', $mes, $year);
        }
    }

    // c) "14/08/2025" o "14-08-2025" → usar el MES/AÑO
    if (preg_match_all('/\b(0?[1-9]|[12][0-9]|3[01])[\/\-](0?[1-9]|1[0-2])[\/\-](\d{4})\b/', $t, $m, PREG_SET_ORDER)) {
        foreach ($m as $hit) {
            $mes  = (int)$hit[2];
            $year = (int)$hit[3];
            $candidatos[] = sprintf('%02d-%04d', $mes, $year);
        }
    }

    // d) "2025-08-14" → tomar 08/2025
    if (preg_match_all('/\b(\d{4})-(0?[1-9]|1[0-2])-(0?[1-9]|[12][0-9]|3[01])\b/', $t, $m, PREG_SET_ORDER)) {
        foreach ($m as $hit) {
            $year = (int)$hit[1];
            $mes  = (int)$hit[2];
            $candidatos[] = sprintf('%02d-%04d', $mes, $year);
        }
    }

    // Quedarnos con el candidato "más reciente" si hay varios
    $seleccion = null;
    if (!empty($candidatos)) {
        usort($candidatos, function($a, $b) {
            [$ma,$ya] = explode('-', $a);
            [$mb,$yb] = explode('-', $b);
            $ia = (int)($ya.$ma);
            $ib = (int)($yb.$mb);
            return $ib <=> $ia; // desc
        });
        $seleccion = $candidatos[0];
    }
    $out['mes_anno'] = $seleccion;

    // ======================
    // 3) ORIGEN (heurístico)
    // ======================
    if (preg_match('/(n[oó]mina|recibo|pago|dep[oó]sito|transferencia|bancomer|bbva|santander|banamex|citibanamex|hsbc|scotiabank|banorte|payroll|empresa|raz[oó]n social|clabe|cuenta|folio|referencia)/i', $t, $m)) {
        $out['origen'] = $m[1];
    }

    return $out;
    }

    // --- GUARDAR identidad (Textract FORMS -> BD) ---
    if (isset($_GET['check']) && $_GET['check'] === 'save_match') {
        // 1) localizar keys
        $rows = $this->model->archivosPorInquilinoId($inquilino['id']);
        $frontalKey = $reversoKey = null;
        foreach ($rows as $r) {
            $t = strtolower(trim($r['tipo']));
            if ($t === 'ine_frontal') $frontalKey = $r['s3_key'];
            if ($t === 'ine_reverso') $reversoKey = $r['s3_key'];
        }
        if (!$frontalKey || !$reversoKey) {
            echo json_encode(['ok'=>false,'mensaje'=>'Faltan imágenes: INE frontal y reverso.']); 
            return;
        }

        try {
            // 2) Descargar desde S3 y AnalyzeDocument(FORMS)
            $fr = $this->s3->getObject(['Bucket'=>$this->s3Bucket, 'Key'=>$frontalKey]);
            $rv = $this->s3->getObject(['Bucket'=>$this->s3Bucket, 'Key'=>$reversoKey]);
            $bytesF = (string)$fr['Body'];
            $bytesR = (string)$rv['Body'];

            $adF = $this->textract->analyzeDocument([
                'Document'=>['Bytes'=>$bytesF],
                'FeatureTypes'=>['FORMS']
            ]);
            $adR = $this->textract->analyzeDocument([
                'Document'=>['Bytes'=>$bytesR],
                'FeatureTypes'=>['FORMS']
            ]);

            $mapF = $this->textract_kv_map($adF['Blocks'] ?? []);
            $mapR = $this->textract_kv_map($adR['Blocks'] ?? []);
            $merged = $mapF + $mapR;

            // 3) Tomar campos (y fallback si todo viene en "NOMBRE")
            $synNombres = ['NOMBRE(S)','NOMBRES','NOMBRE'];
            $synApPat   = ['APELLIDO PATERNO','PRIMER APELLIDO','APELLIDO 1','APELLIDO1'];
            $synApMat   = ['APELLIDO MATERNO','SEGUNDO APELLIDO','APELLIDO 2','APELLIDO2'];

            $nombres = $this->kv_pick($merged, $synNombres);
            $apPat   = $this->kv_pick($merged, $synApPat);
            $apMat   = $this->kv_pick($merged, $synApMat);

            if (!$apPat && !$apMat && $nombres) {
                $clean = strtoupper($nombres);
                $clean = preg_replace('/\bDOMICILIO\b.*$/u', '', $clean);
                $clean = preg_replace('/\s+/', ' ', trim($clean));
                $tokens = array_values(array_filter(explode(' ', $clean)));
                if (count($tokens) >= 3) {
                    $apPat  = $tokens[0];
                    $apMat  = $tokens[1];
                    $nombres = implode(' ', array_slice($tokens, 2));
                }
            }

            // 4) Normaliza y compara contra BD
            $ocr = [
                'apellidop' => $this->_norm_txt($apPat),
                'apellidom' => $this->_norm_txt($apMat),
                'nombres'   => $this->_norm_txt($nombres),
            ];
            $bd  = [
                'apellidop' => $this->_norm_txt($inquilino['apellidop_inquilino'] ?? ''),
                'apellidom' => $this->_norm_txt($inquilino['apellidom_inquilino'] ?? ''),
                'nombres'   => $this->_norm_txt($inquilino['nombre_inquilino'] ?? ''),
            ];

            $match = [
                'apellidop' => ($ocr['apellidop'] !== '' && $ocr['apellidop'] === $bd['apellidop']),
                'apellidom' => ($ocr['apellidom'] !== '' && $ocr['apellidom'] === $bd['apellidom']),
                'nombres'   => ($ocr['nombres']   !== '' && $ocr['nombres']   === $bd['nombres']),
            ];
            $overall = ($match['apellidop'] && $match['apellidom'] && $match['nombres']);

            // 5) Payload completo
            $payload = [
                'tipo'     => 'identidad_nombres',
                'fuente'   => 'textract_forms',
                'overall'  => $overall,
                'detalles' => $match,
                'ocr'      => $ocr,
                'bd'       => $bd,
                'ts'       => date('c'),
            ];

            // 6) Proceso (0/1/2) y resumen humano
            $proceso = $overall ? 1 : 0; // si quieres, agrega lógica para 2=PENDIENTE cuando no haya evidencia suficiente
            $resumen = \App\Helpers\ValidacionResumenHelper::identidadNombres($proceso, $payload);

            // 7) Guardar proceso + resumen + JSON
            $ok = $this->model->guardarValidacionIdentidad(
                (int)$inquilino['id'],
                (int)$proceso,
                $payload,
                $resumen
            );

            echo json_encode([
                'ok' => (bool)$ok,
                'mensaje' => $ok ? 'Validación de identidad (nombres) guardada.' : 'No se pudo guardar.',
                'resultado' => [
                    'proceso'  => $proceso,
                    'resumen'  => $resumen,
                    'overall'  => $overall,
                    'detalles' => $match,
                    'ocr'      => $ocr,
                    'bd'       => $bd
                ]
            ]);
        } catch (\Aws\Exception\AwsException $e) {
            echo json_encode(['ok'=>false,'mensaje'=>'Error Textract: '.$e->getAwsErrorMessage(),'codigo'=>$e->getAwsErrorCode()]);
        }
        return;
    }

    // --- GUARDAR identidad (CURP/CIC/VIGENCIA desde OCR) ------------------------
    if (isset($_GET['check']) && $_GET['check'] === 'save_curp_cic') {
        // 1) localizar keys
        $rows = $this->model->archivosPorInquilinoId((int)$inquilino['id']);
        $frontalKey = $reversoKey = null;
        foreach ($rows as $r) {
            $t = strtolower(trim($r['tipo']));
            if ($t === 'ine_frontal') $frontalKey = $r['s3_key'];
            if ($t === 'ine_reverso') $reversoKey = $r['s3_key'];
        }
        if (!$frontalKey || !$reversoKey) {
            echo json_encode(['ok'=>false,'mensaje'=>'Faltan imágenes: INE frontal y reverso.']);
            return;
        }

        try {
            // 2) Descargar bytes desde S3 (MX) y correr OCR con Textract (us-east-1) vía BYTES
            $fr = $this->s3->getObject(['Bucket' => $this->s3Bucket, 'Key' => $frontalKey]);
            $rv = $this->s3->getObject(['Bucket' => $this->s3Bucket, 'Key' => $reversoKey]);
            $bytesF = (string)$fr['Body'];
            $bytesR = (string)$rv['Body'];

            $resF = $this->textract->detectDocumentText(['Document' => ['Bytes' => $bytesF]]);
            $resR = $this->textract->detectDocumentText(['Document' => ['Bytes' => $bytesR]]);

            // 3) Extraer líneas y unir texto
            $linesF = [];
            foreach (($resF['Blocks'] ?? []) as $b) {
                if (($b['BlockType'] ?? '') === 'LINE' && !empty($b['Text'])) $linesF[] = $b['Text'];
            }
            $linesR = [];
            foreach (($resR['Blocks'] ?? []) as $b) {
                if (($b['BlockType'] ?? '') === 'LINE' && !empty($b['Text'])) $linesR[] = $b['Text'];
            }

            $texto = strtoupper(implode("\n", array_merge($linesF, $linesR)));

            // 4) Parsear CURP / CIC / Vigencia con helper del propio controlador
            $parsed = $this->ine_parse_curp_cic($texto);
            $tieneCurp = !empty($parsed['curp']);
            $tieneCic  = !empty($parsed['cic']);
            $tieneAlgo = $tieneCurp || $tieneCic || !empty($parsed['vigencia']);

            // 5) Decidir proceso (0/1/2)
            //    OK si hay CURP y CIC; PEND si hay alguno; NO_OK si nada.
            if ($tieneCurp && $tieneCic) {
                $proceso = 1; // OK
            } elseif ($tieneAlgo) {
                $proceso = 2; // PENDIENTE
            } else {
                $proceso = 0; // NO_OK
            }

            // 6) Armar payload y resumen humano
            $payload = [
                'tipo'    => 'identidad_curp_cic',
                'fuente'  => 'textract_detect',
                'parsed'  => $parsed,
                'ocr'     => [
                    'lineas_frontal' => count($linesF),
                    'lineas_reverso' => count($linesR),
                ],
                'archivos' => [
                    'ine_frontal_key' => $frontalKey,
                    'ine_reverso_key' => $reversoKey,
                ],
                'ts' => date('c'),
            ];

            $resumen = \App\Helpers\ValidacionResumenHelper::identidadCurpCic($proceso, $parsed);

            // 7) Guardar en BD reutilizando guardarValidacionIdentidad (misma columna id_json/resumen)
            $ok = $this->model->guardarValidacionIdentidad(
                (int)$inquilino['id'],
                (int)$proceso,
                $payload,
                $resumen
            );

            echo json_encode([
                'ok' => (bool)$ok,
                'mensaje' => $ok ? 'Validación de identidad (CURP/CIC) guardada.' : 'No se pudo guardar.',
                'resultado' => [
                    'proceso'  => $proceso,
                    'resumen'  => $resumen,
                    'parsed'   => $parsed,
                    'ocr'      => ['lineas_frontal'=>count($linesF), 'lineas_reverso'=>count($linesR)],
                    'archivos' => ['ine_frontal_key'=>$frontalKey, 'ine_reverso_key'=>$reversoKey],
                ]
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Aws\Exception\AwsException $e) {
            echo json_encode([
                'ok'=>false,
                'mensaje'=>'Error Textract: '.$e->getAwsErrorMessage(),
                'codigo'=>$e->getAwsErrorCode()
            ]);
        }
        return;
    }




        // --- STATUS: resumen de validaciones guardadas ---
                // --- STATUS: resumen global de validaciones guardadas -----------------------
        if (isset($_GET['check']) && $_GET['check'] === 'status') {
            try {
                $vals = $this->model->obtenerValidaciones((int)$inquilino['id']);

                // Semáforos (0=NO_OK, 1=OK, 2=PEND.)
                $semaforos = [
                    'documentos'   => (int)($vals['documentos']['proceso']   ?? 2),
                    'archivos'     => (int)($vals['archivos']['proceso']     ?? 2),
                    'rostro'       => (int)($vals['rostro']['proceso']       ?? 2),
                    'identidad'    => (int)($vals['identidad']['proceso']    ?? 2),
                    'ingresos'     => (int)($vals['ingresos']['proceso']     ?? 2),
                    'pago_inicial' => (int)($vals['pago_inicial']['proceso'] ?? 2),
                    'demandas'     => (int)($vals['demandas']['proceso']     ?? 2),
                ];

                // Resumen Humano global (ej. "✔️ Docs · ⏳ Arch · ✖️ Rostro · ...")
                $resumenGlobal = \App\Helpers\ValidacionResumenHelper::statusGlobal($semaforos);

                // Además, devolvemos cada resumen humano individual si existe
                $resumenes = [
                    'archivos'     => $vals['archivos']['resumen']     ?? null,
                    'rostro'       => $vals['rostro']['resumen']       ?? null,
                    'identidad'    => $vals['identidad']['resumen']    ?? null,
                    'documentos'   => $vals['documentos']['resumen']   ?? null,
                    'ingresos'     => $vals['ingresos']['resumen']     ?? null,
                    'pago_inicial' => $vals['pago_inicial']['resumen'] ?? null,
                    'demandas'     => $vals['demandas']['resumen']     ?? null,
                ];

                // Si el cliente quiere texto plano combinado (?plain=1)
                $plain = (isset($_GET['plain']) && $_GET['plain'] == '1');
                if ($plain) {
                    // Arma un párrafo multi-renglón listo para copiar/pegar
                    $lines = [$resumenGlobal];
                    foreach ($resumenes as $k => $txt) {
                        if ($txt) $lines[] = "• " . $txt;
                    }
                    echo json_encode([
                        'ok' => true,
                        'slug' => $inquilino['slug'] ?? null,
                        'plain' => implode("\n", $lines),
                        'meta' => $vals['meta'] ?? null,
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }

                echo json_encode([
                    'ok' => true,
                    'slug' => $inquilino['slug'] ?? null,
                    'resumen_global' => $resumenGlobal,
                    'semaforos' => $semaforos,
                    'resumenes' => $resumenes,
                    'detalles'  => $vals,  // incluye los JSON completos por cada validación
                ], JSON_UNESCAPED_UNICODE);
            } catch (\Throwable $e) {
                echo json_encode(['ok'=>false,'mensaje'=>'Error al consultar estado: '.$e->getMessage()]);
            }
            return;
        }

        // --- REGENERAR RESÚMENES HUMANOS DESDE JSON GUARDADOS ----------------------
        if (isset($_GET['check']) && $_GET['check'] === 'resumen_full') {
            try {
                $idInq = (int)($inquilino['id'] ?? 0);
                if (!$idInq) {
                    echo json_encode(['ok'=>false,'mensaje'=>'Falta id_inquilino en el contexto.']); 
                    return;
                }

                // Opciones
                $dry  = (isset($_GET['dry'])  && $_GET['dry']  == '1');   // dry run: no guarda, solo muestra
                $only = isset($_GET['only']) ? (string)$_GET['only'] : ''; // ej: only=archivos,rostro,ingresos

                // Normalizar filtro "only"
                $allow = null;
                if ($only !== '') {
                    $allow = array_filter(array_map('trim', explode(',', strtolower($only))));
                }
                $isAllowed = function(string $k) use ($allow): bool {
                    if ($allow === null) return true;
                    return in_array($k, $allow, true);
                };

                // 1) Leer todo el estado actual
                $vals = $this->model->obtenerValidaciones($idInq);

                $updated = [];
                $skipped = [];
                $errors  = [];

                // Helper de guardado seguro (wrap try/catch y soporta dry-run)
                $doSave = function(string $clave, callable $fn) use (&$updated, &$errors, $dry) {
                    try {
                        if ($dry) {
                            $updated[] = $clave.' (dry)';
                        } else {
                            $ok = $fn();
                            if ($ok) $updated[] = $clave;
                            else     $errors[]  = $clave.' (no guardó)';
                        }
                    } catch (\Throwable $e) {
                        $errors[] = $clave.' (error: '.$e->getMessage().')';
                    }
                };

                // =========================
                //  A) ARCHIVOS
                // =========================
                if ($isAllowed('archivos')) {
                    $p = (int)($vals['archivos']['proceso'] ?? 2);
                    $j = $vals['archivos']['json'] ?? null;
                    if (is_array($j)) {
                        $resumen = \App\Helpers\ValidacionResumenHelper::archivos($p, $j);
                        $doSave('archivos', function() use($idInq, $p, $j, $resumen) {
                            return $this->model->guardarValidacionArchivos($idInq, $p, $j, $resumen);
                        });
                    } else {
                        $skipped[] = 'archivos (sin JSON)';
                    }
                }

                // =========================
                //  B) ROSTRO
                // =========================
                if ($isAllowed('rostro')) {
                    $p = (int)($vals['rostro']['proceso'] ?? 2);
                    $j = $vals['rostro']['json'] ?? null;
                    if (is_array($j)) {
                        $resumen = \App\Helpers\ValidacionResumenHelper::rostroCompareFaces($p, $j);
                        $doSave('rostro', function() use($idInq, $p, $j, $resumen) {
                            return $this->model->guardarValidacionRostro($idInq, $p, $j, $resumen);
                        });
                    } else {
                        $skipped[] = 'rostro (sin JSON)';
                    }
                }

                // =========================
                //  C) IDENTIDAD (nombres / curp-cic)
                //     *Ambas comparten columnas; usamos el "tipo" del payload para el resumen*
                // =========================
                if ($isAllowed('identidad')) {
                    $p = (int)($vals['identidad']['proceso'] ?? 2);
                    $j = $vals['identidad']['json'] ?? null;
                    if (is_array($j)) {
                        $tipo = strtolower((string)($j['tipo'] ?? ''));
                        if ($tipo === 'identidad_nombres') {
                            $resumen = \App\Helpers\ValidacionResumenHelper::identidadNombres($p, $j);
                        } elseif ($tipo === 'identidad_curp_cic') {
                            $resumen = \App\Helpers\ValidacionResumenHelper::identidadCurpCic($p, $j['parsed'] ?? []);
                        } else {
                            // Fallback si no trae tipo
                            $resumen = \App\Helpers\ValidacionResumenHelper::identidadNombres($p, $j);
                        }
                        $doSave('identidad', function() use($idInq, $p, $j, $resumen) {
                            return $this->model->guardarValidacionIdentidad($idInq, $p, $j, $resumen);
                        });
                    } else {
                        $skipped[] = 'identidad (sin JSON)';
                    }
                }

                // =========================
                //  D) DOCUMENTOS (OCR INE líneas)
                // =========================
                if ($isAllowed('documentos')) {
                    $p = (int)($vals['documentos']['proceso'] ?? 2);
                    $j = $vals['documentos']['json'] ?? null;
                    if (is_array($j)) {
                        $resumen = \App\Helpers\ValidacionResumenHelper::ineOcr($p, $j);
                        $doSave('documentos', function() use($idInq, $p, $j, $resumen) {
                            return $this->model->guardarValidacionDocumentos($idInq, $p, $j, $resumen);
                        });
                    } else {
                        $skipped[] = 'documentos (sin JSON)';
                    }
                }

                // =========================
                //  E) INGRESOS (simple / ocr / list) — usan mismas columnas
                //     Decidimos helper por $payload['tipo']
                // =========================
                if ($isAllowed('ingresos')) {
                    $p = (int)($vals['ingresos']['proceso'] ?? 2);
                    $j = $vals['ingresos']['json'] ?? null;
                    if (is_array($j)) {
                        $tipo = strtolower((string)($j['tipo'] ?? ''));
                        if ($tipo === 'ingresos_pdf_simple') {
                            $resumen = \App\Helpers\ValidacionResumenHelper::ingresosSimple($p, $j);
                            $doSave('ingresos_simple', function() use($idInq, $p, $j, $resumen) {
                                return $this->model->guardarValidacionIngresosSimple($idInq, $p, $j, $resumen);
                            });
                        } elseif ($tipo === 'ingresos_ocr') {
                            $resumen = \App\Helpers\ValidacionResumenHelper::ingresosOcr($p, $j);
                            $doSave('ingresos_ocr', function() use($idInq, $p, $j, $resumen) {
                                return $this->model->guardarValidacionIngresosOCR($idInq, $p, $j, $resumen);
                            });
                        } elseif ($tipo === 'ingresos_list') {
                            $resumen = \App\Helpers\ValidacionResumenHelper::ingresosList($p, $j);
                            $doSave('ingresos_list', function() use($idInq, $p, $j, $resumen) {
                                return $this->model->guardarValidacionIngresosList($idInq, $p, $j, $resumen);
                            });
                        } else {
                            // Fallback genérico
                            $resumen = \App\Helpers\ValidacionResumenHelper::ingresosList($p, $j);
                            $doSave('ingresos', function() use($idInq, $p, $j, $resumen) {
                                return $this->model->guardarValidacionIngresosList($idInq, $p, $j, $resumen);
                            });
                        }
                    } else {
                        $skipped[] = 'ingresos (sin JSON)';
                    }
                }

                // =========================
                //  F) PAGO INICIAL
                // =========================
                if ($isAllowed('pago_inicial')) {
                    $p = (int)($vals['pago_inicial']['proceso'] ?? 2);
                    $j = $vals['pago_inicial']['json'] ?? null;
                    if (is_array($j)) {
                        $resumen = \App\Helpers\ValidacionResumenHelper::pagoInicial($p, $j);
                        $doSave('pago_inicial', function() use($idInq, $p, $j, $resumen) {
                            return $this->model->guardarPagoInicial($idInq, $p, $j, $resumen);
                        });
                    } else {
                        $skipped[] = 'pago_inicial (sin JSON)';
                    }
                }

                // =========================
                //  G) INVESTIGACIÓN DE DEMANDAS
                // =========================
                if ($isAllowed('demandas')) {
                    $p = (int)($vals['demandas']['proceso'] ?? 2);
                    $j = $vals['demandas']['json'] ?? null;
                    if (is_array($j)) {
                        $resumen = \App\Helpers\ValidacionResumenHelper::invDemandas($p, $j);
                        $doSave('demandas', function() use($idInq, $p, $j, $resumen) {
                            return $this->model->guardarInvestigacionDemandas($idInq, $p, $j, $resumen);
                        });
                    } else {
                        $skipped[] = 'demandas (sin JSON)';
                    }
                }

                // 2) Status global usando los procesos (antes y/o después; aquí usamos los actuales)
                $semaforos = [
                    'documentos'   => (int)($vals['documentos']['proceso']   ?? 2),
                    'archivos'     => (int)($vals['archivos']['proceso']     ?? 2),
                    'rostro'       => (int)($vals['rostro']['proceso']       ?? 2),
                    'identidad'    => (int)($vals['identidad']['proceso']    ?? 2),
                    'ingresos'     => (int)($vals['ingresos']['proceso']     ?? 2),
                    'pago_inicial' => (int)($vals['pago_inicial']['proceso'] ?? 2),
                    'demandas'     => (int)($vals['demandas']['proceso']     ?? 2),
                ];
                $resumenGlobal = \App\Helpers\ValidacionResumenHelper::statusGlobal($semaforos);

                echo json_encode([
                    'ok' => true,
                    'dry_run' => $dry,
                    'only'    => $allow ?? 'all',
                    'resumen_global' => $resumenGlobal,
                    'updated' => $updated,
                    'skipped' => $skipped,
                    'errors'  => $errors,
                ], JSON_UNESCAPED_UNICODE);
            } catch (\Throwable $e) {
                echo json_encode(['ok'=>false,'mensaje'=>'Error al regenerar resúmenes: '.$e->getMessage()]);
            }
            return;
        }



        // --- GUARDAR rostro (Rekognition -> BD) ---
    if (isset($_GET['check']) && $_GET['check'] === 'save_face') {
        // 1) localizar keys
        $rows = $this->model->archivosPorInquilinoId($inquilino['id']);
        $selfieKey = $frontalKey = null;
        foreach ($rows as $r) {
            $t = strtolower(trim($r['tipo']));
            if ($t === 'selfie')       $selfieKey  = $r['s3_key'];
            if ($t === 'ine_frontal')  $frontalKey = $r['s3_key'];
        }
        if (!$selfieKey || !$frontalKey) {
            echo json_encode(['ok'=>false,'mensaje'=>'Faltan imágenes: selfie y/o INE frontal.']); 
            return;
        }

        try {
            // 2) Descargar imágenes desde S3
            $selfieObj  = $this->s3->getObject(['Bucket'=>$this->s3Bucket, 'Key'=>$selfieKey]);
            $frontalObj = $this->s3->getObject(['Bucket'=>$this->s3Bucket, 'Key'=>$frontalKey]);

            $selfieBytes  = (string)$selfieObj['Body'];
            $frontalBytes = (string)$frontalObj['Body'];

            // 3) CompareFaces con BYTES
            $threshold = 90; // umbral base (ajustable)
            $res = $this->rekognition->compareFaces([
                'SimilarityThreshold' => $threshold,
                'SourceImage' => ['Bytes' => $selfieBytes],
                'TargetImage' => ['Bytes' => $frontalBytes],
            ]);

            $best = null; $count = 0;
            foreach (($res['FaceMatches'] ?? []) as $m) {
                $count++;
                if ($best === null || ($m['Similarity'] ?? 0) > ($best['Similarity'] ?? 0)) {
                    $best = $m;
                }
            }

            $similarity = $best['Similarity']            ?? 0.0;
            $confidence = $best['Face']['Confidence']    ?? 0.0;
            $bbox       = $best['Face']['BoundingBox']   ?? null;

            // 4) decidir estatus (0/1/2)
            $estatus = ($similarity >= $threshold) ? 1 : 0;

            // 5) payload para trazar todo
            $payload = [
                'tipo'        => 'rostro_comparefaces',
                'threshold'   => $threshold,
                'match_count' => $count,
                'best'        => [
                    'similarity' => $similarity,
                    'confidence' => $confidence,
                    'bbox'       => $bbox,
                ],
                'selfie_key'   => $selfieKey,
                'ine_frontal'  => $frontalKey,
                'ts'           => date('c'),
            ];

            // 6) NUEVO: resumen humano con el helper
            $resumen = \App\Helpers\ValidacionResumenHelper::rostroCompareFaces((int)$estatus, $payload);

            // 7) NUEVO: guardar proceso + resumen + JSON en BD
            $ok = $this->model->guardarValidacionRostro(
                (int)$inquilino['id'],
                (int)$estatus,
                $payload,
                $resumen
            );

            echo json_encode([
                'ok' => (bool)$ok,
                'mensaje' => $ok ? 'Validación de rostro guardada.' : 'No se pudo guardar.',
                'resultado' => [
                    'estatus'     => $estatus,
                    'resumen'     => $resumen,
                    'similarity'  => $similarity,
                    'confidence'  => $confidence
                ]
            ]);
        } catch (\Aws\Exception\AwsException $e) {
            echo json_encode([
                'ok'=>false,
                'mensaje'=>'Error Rekognition: '.$e->getAwsErrorMessage(),
                'codigo'=>$e->getAwsErrorCode()
            ]);
        }
        return;
    }


    }
    /**
     * Parsea CURP, CIC/OCR/IDMEX y vigencia a partir del texto OCR INE.
     */
    public function ine_parse_curp_cic(string $text): array
    {
        // CURP: 18 caracteres con patrón oficial
        $curp = null;
        if (preg_match('/\b([A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d)\b/i', $text, $m)) {
            $curp = strtoupper($m[1]);
        }

        // CIC / OCR / IDMEX: número largo de 9-20 dígitos tras etiqueta común
        $cic = null;
        $patronesCic = [
            '/\bCIC[:\s-]*([0-9]{8,20})\b/i',
            '/\bOCR[:\s-]*([0-9]{8,20})\b/i',
            '/\bIDMEX[:\s-]*([0-9]{8,20})\b/i',
        ];
        foreach ($patronesCic as $re) {
            if (preg_match($re, $text, $m)) { $cic = $m[1]; break; }
        }

        // Vigencia (opcional): 2024-2034, o solo un año
        $vigencia = null;
        if (preg_match('/VIGENCIA[:\s-]*([0-9]{4})(?:\s*[-a]\s*([0-9]{4}))?/i', $text, $m)) {
            $vigencia = isset($m[2]) && $m[2] ? "{$m[1]}-{$m[2]}" : $m[1];
        }

        return ['curp' => $curp, 'cic' => $cic, 'vigencia' => $vigencia];
    }

    /**
     * Extrae nombres y apellidos desde líneas OCR de INE (frente+reverso).
     * Combina heurísticas por etiquetas y por línea candidata principal.
     */
    public function ine_parse_nombres_apellidos(array $linesF, array $linesR): array
    {
        $lines = array_map(fn($s) => strtoupper(trim($s)), array_merge($linesF, $linesR));
        $texto = implode("\n", $lines);

        $out = ['nombres'=>null,'apellidop'=>null,'apellidom'=>null,'fuente'=>null,'linea_raw'=>null];

        // 1) Intento por etiquetas comunes
        if (preg_match('/APELLIDO\s+PATERNO[:\s]*([A-ZÁÉÍÓÚÑ ]{2,})/u', $texto, $m1)) $out['apellidop'] = trim($m1[1]);
        if (preg_match('/APELLIDO\s+MATERNO[:\s]*([A-ZÁÉÍÓÚÑ ]{2,})/u', $texto, $m2)) $out['apellidom'] = trim($m2[1]);
        if (preg_match('/NOMBRE(?:\(S\))?[:\s]*([A-ZÁÉÍÓÚÑ ]{2,})/u', $texto, $m3)) $out['nombres']   = trim($m3[1]);

        if ($out['nombres'] || $out['apellidop'] || $out['apellidom']) {
            $out['fuente'] = 'labels';
            return $out;
        }

        // 2) Heurística: detectar línea "larga" de nombre completo y partirla
        $stop = ['DOMICILIO','CLAVE','ELECTOR','CIC','OCR','SECCIÓN','CURP','EMISIÓN','VIGENCIA','ESTADO','MÉXICO','MEXICO','MUNICIPIO','ZONA','LOCALIDAD','AÑO','A\'O','FOLIO'];
        $candidatas = array_filter($lines, function($L) use ($stop){
            if (mb_strlen($L) < 10) return false;
            if (!preg_match('/^[A-ZÁÉÍÓÚÑ ]+$/u', $L)) return false;
            foreach ($stop as $w) if (str_contains($L, $w)) return false;
            return true;
        });

        if (!empty($candidatas)) {
            usort($candidatas, fn($a,$b)=>substr_count($b,' ')<=>substr_count($a,' '));
            $best = trim($candidatas[0]);
            $tokens = array_values(array_filter(explode(' ', $best)));
            // Heurística simple: [APELLIDO_P, APELLIDO_M, NOMBRES...]
            if (count($tokens) >= 3) {
                $out['apellidop'] = $tokens[0];
                $out['apellidom'] = $tokens[1];
                $out['nombres']   = implode(' ', array_slice($tokens, 2));
                $out['fuente']    = 'heuristica';
                $out['linea_raw'] = $best;
                return $out;
            }
        }

        return $out;
    }

    /**
     * Normaliza etiquetas extraídas por Textract (FORMS) para emparejar claves.
     */
    public function normalize_label(string $s): string
    {
        $s = strtoupper(trim($s));
        $s = strtr($s, ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N']);
        $s = preg_replace('/[^A-Z0-9 ]+/', '', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return $s;
    }

    /**
     * Helper: obtiene texto de un bloque Textract por ID desde el mapa.
     */
    public function textract_get_text_from_block(array $block, array $blockMap): string
    {
        $txt = '';
        if (!empty($block['Relationships'])) {
            foreach ($block['Relationships'] as $rel) {
                if ($rel['Type'] === 'CHILD') {
                    foreach ($rel['Ids'] as $cid) {
                        $child = $blockMap[$cid] ?? null;
                        if ($child && ($child['BlockType'] ?? '') === 'WORD' && !empty($child['Text'])) {
                            $txt .= $child['Text'].' ';
                        }
                    }
                }
            }
        }
        return trim($txt);
    }

    /**
     * Construye mapas clave->valor de Textract AnalyzeDocument(FORMS).
     */
    public function textract_kv_map(array $blocks): array
    {
        $blockMap = [];
        foreach ($blocks as $b) {
            if (!empty($b['Id'])) $blockMap[$b['Id']] = $b;
        }

        $kv = [];
        foreach ($blocks as $b) {
            if (($b['BlockType'] ?? '') === 'KEY_VALUE_SET' && in_array('KEY', $b['EntityTypes'] ?? [])) {
                $keyText = $this->textract_get_text_from_block($b, $blockMap);
                $valText = '';
                if (!empty($b['Relationships'])) {
                    foreach ($b['Relationships'] as $rel) {
                        if ($rel['Type'] === 'VALUE') {
                            foreach ($rel['Ids'] as $vid) {
                                $vblock = $blockMap[$vid] ?? null;
                                if ($vblock) {
                                    $valText = trim($valText.' '.$this->textract_get_text_from_block($vblock, $blockMap));
                                }
                            }
                        }
                    }
                }
                $k = $this->normalize_label($keyText);
                if ($k !== '') $kv[$k] = $valText;
            }
        }
        return $kv;
    }

    /**
     * Obtiene el valor para una de varias claves candidatas desde un mapa KV.
     */
    public function kv_pick(array $map, array $synonyms): ?string
    {
        foreach ($map as $k => $v) {
            $nk = $this->normalize_label((string)$k);
            foreach ($synonyms as $s) {
                $ns = $this->normalize_label($s);
                if (str_contains($nk, $ns)) return $v ?: null;
            }
        }
        return null;
    }

    /**
     * Normalización básica de texto para comparaciones (upper, sin tildes/espacios extra).
     */
    public function _norm_txt(?string $s): string
    {
        $s = strtoupper(trim((string)$s));
        // quita acentos/ñ
        $map = ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N'];
        $s = strtr($s, $map);
        // solo letras y espacios
        $s = preg_replace('/[^A-Z ]+/', '', $s);
        // colapsa espacios
        $s = preg_replace('/\s+/', ' ', $s);
        return $s;
    }
}