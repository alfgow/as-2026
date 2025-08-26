<?php
namespace App\Controllers;

require_once __DIR__ . '/../aws-sdk-php/aws-autoloader.php';
require_once __DIR__ . '/../Models/InquilinoModel.php';
require_once __DIR__ . '/../Models/ValidacionLegalModel.php'; 

use App\Models\InquilinoModel;
use App\Models\ValidacionLegalModel;
use Aws\Sdk;
use Aws\Sfn\SfnClient;

class ValidacionLegalController
{
     // ⬇️ AJUSTA estos valores a los reales de tu cuenta
    private const AWS_REGION = 'mx-central-1';
    private const STATE_MACHINE_ARN_VALIDACION_LEGAL = 'arn:aws:states:mx-central-1:453899975331:stateMachine:ValidacionLegal';
    private const S3_BUCKET_INQUILINOS = 'as-s3-inquilinos';
    protected ValidacionLegalModel $model;
    
    public function __construct()
    {
        $this->model = new ValidacionLegalModel();
    }
    

    /**
     * POST /validaciones/demandas/run/{id}
     */
    public function run($idInquilino)
{
    header('Content-Type: application/json; charset=utf-8');
    try {
        $idInquilino = (int)$idInquilino;
        $nombre = trim($_POST['nombre'] ?? '');
        $apP    = trim($_POST['apellido_p'] ?? '');
        $apM    = isset($_POST['apellido_m']) ? trim($_POST['apellido_m']) : null;
        $curp   = $_POST['curp'] ?? null;
        $rfc    = $_POST['rfc'] ?? null;
        $slug   = trim($_POST['slug'] ?? '');

        if ($nombre === '' || $apP === '') {
            echo json_encode(['ok' => false, 'mensaje' => 'Nombre y apellido paterno son obligatorios']);
            return;
        }

        // === Carga tu config centralizada ===
        $aws = require __DIR__ . '/../config/s3config.php';         // <- ajusta ruta si es necesario
        $cfgInq = $aws['inquilinos'] ?? null;
        if (!$cfgInq || empty($cfgInq['region']) || empty($cfgInq['credentials']['key']) || empty($cfgInq['credentials']['secret'])) {
            echo json_encode(['ok' => false, 'mensaje' => 'Config AWS incompleta (inquilinos).']);
            return;
        }

        // Constantes + config
        $AWS_REGION        = $cfgInq['region'];                          // p.ej. mx-central-1
        $S3_BUCKET_INQ     = $cfgInq['bucket'] ?? self::S3_BUCKET_INQUILINOS;
        $STATE_MACHINE_ARN = self::STATE_MACHINE_ARN_VALIDACION_LEGAL;   // tu ARN de la máquina

        if ($STATE_MACHINE_ARN === '') {
            echo json_encode(['ok' => false, 'mensaje' => 'Falta STATE_MACHINE_ARN_VALIDACION_LEGAL (constante vacía)']);
            return;
        }

        // Person key para prefijos S3
        $personKey = $_POST['s3_person_key'] ?? $this->buildPersonKeyFromParts($nombre, $apP, $apM);

        // Portales a ejecutar
        $portales = ['cjf_lista_acuerdos','cjf_expedientes','pjcdmx_boletin','pjedomex_boletin'];

        // Paso A: Bitácora local
        $idsIntento = [];
        foreach ($portales as $portal) {
            $idsIntento[] = $this->model->registrarIntento([
                'id_inquilino' => $idInquilino,
                'nombre'       => $nombre,
                'apellido_p'   => $apP,
                'apellido_m'   => $apM,
                'curp'         => $curp,
                'rfc'          => $rfc,
                'portal'       => $portal,
                'query_usada'  => [
                    'variante' => mb_strtoupper(trim("$nombre $apP " . ($apM ?? '')), 'UTF-8'),
                    'fecha'    => date('Y-m-d'),
                ],
                'status'       => 'no_data',
            ]);
        }

        // Paso B: Disparar Step Functions con credenciales del config
        $sfn = new SfnClient([
            'region'      => $AWS_REGION,
            'version'     => '2016-11-23', // o 'latest'
            'credentials' => [
                'key'    => $cfgInq['credentials']['key'],
                'secret' => $cfgInq['credentials']['secret'],
            ],
        ]);

        $ejecuciones = [];
        foreach ($portales as $portal) {
            $input = [
                'portal'    => $portal,
                'inquilino' => [
                    'id'          => $idInquilino,
                    'nombre'      => $nombre,
                    'apellido_p'  => $apP,
                    'apellido_m'  => $apM,
                    'curp'        => $curp,
                    'rfc'         => $rfc,
                    'slug'        => $slug,
                ],
                's3' => [
                    'bucket'             => $S3_BUCKET_INQ,
                    'person_key'         => $personKey,
                    'prefix_base'        => "{$personKey}/validaciones_legal/",
                    'prefix_portal_date' => "{$personKey}/validaciones_legal/{$portal}/" . date('Y-m-d') . "/",
                ],
            ];

            try {
                $name = $this->buildExecutionName($portal, $idInquilino);
                $res = $sfn->startExecution([
                    'stateMachineArn' => $STATE_MACHINE_ARN,
                    'name'            => $name,
                    'input'           => json_encode($input, JSON_UNESCAPED_UNICODE),
                ]);
                $ejecuciones[$portal] = ['ok' => true, 'executionArn' => (string)$res->get('executionArn')];
            } catch (\Throwable $ex) {
                $ejecuciones[$portal] = ['ok' => false, 'error' => $ex->getMessage()];
            }
        }

        echo json_encode([
            'ok'          => true,
            'mensaje'     => 'Intentos registrados y ejecuciones iniciadas.',
            'intentos'    => $idsIntento,
            'ejecuciones' => $ejecuciones
        ]);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'mensaje' => 'Error interno','error' => $e->getMessage()]);
    }
}


    /**
     * GET /validaciones/demandas/ultimo/{id}
     */
    public function ultimo($idInquilino)
{
    header('Content-Type: application/json; charset=utf-8');
    try {
        $idInquilino = (int)$idInquilino;
        $portal      = $_GET['portal'] ?? null;
        $ttl         = max(60, (int)($_GET['ttl'] ?? 300));

        // Usa las constantes de la clase (coherencia con run)
        $BUCKET_INQ = self::S3_BUCKET_INQUILINOS; // as-s3-inquilinos
        $AWS_REGION = self::AWS_REGION;           // mx-central-1

        $presign = function (?string $key) use ($ttl, $BUCKET_INQ, $AWS_REGION) {
            if (empty($key)) return null;
            try {
                $s3  = new \Aws\S3\S3Client(['version' => 'latest', 'region' => $AWS_REGION]);
                $cmd = $s3->getCommand('GetObject', ['Bucket' => $BUCKET_INQ, 'Key' => $key]);
                $req = $s3->createPresignedRequest($cmd, "+{$ttl} seconds");
                return (string)$req->getUri();
            } catch (\Throwable $e) {
                return null;
            }
        };

        $reporte = $this->model->obtenerUltimoReportePorInquilino($idInquilino, $portal);
        if ($reporte) {
            if (!empty($reporte['evidencia_s3_key'])) {
                $reporte['evidencia_url'] = $presign($reporte['evidencia_s3_key']);
            }
            if (!empty($reporte['raw_json_s3_key'])) {
                $reporte['raw_json_url'] = $presign($reporte['raw_json_s3_key']);
            }
            echo json_encode(['ok' => true, 'reporte' => $reporte]);
            return;
        }

        $agg = method_exists($this->model, 'obtenerValidacionDemandas')
            ? $this->model->obtenerValidacionDemandas($idInquilino)
            : null;

        if (!$agg) {
            echo json_encode(['ok' => true, 'reporte' => null]);
            return;
        }

        $resultado = $agg['evidencias'] ?? [];
        $reporteSintetico = [
            'portal'           => 'juridico_agg',
            'status'           => $agg['status'] ?? 'no_data',
            'clasificacion'    => $agg['clasificacion'] ?? 'sin_evidencia',
            'score_max'        => (int)($agg['scoring'] ?? $agg['score'] ?? $agg['score_max'] ?? 0),
            'query_usada'      => json_encode(['fuente' => 'inquilinos_validaciones.inv_demandas_json', 'fecha' => date('Y-m-d')], JSON_UNESCAPED_UNICODE),
            'resultado'        => json_encode($resultado, JSON_UNESCAPED_UNICODE),
            'evidencia_s3_key' => null,
            'raw_json_s3_key'  => null,
            'evidencia_url'    => null,
            'raw_json_url'     => null,
            'error_message'    => null,
            'searched_at'      => date('Y-m-d H:i:s'),
        ];

        echo json_encode(['ok' => true, 'reporte' => $reporteSintetico]);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'mensaje' => 'Error interno', 'error' => $e->getMessage()]);
    }
}





    /**
     * GET /validaciones/demandas/resumen/{id}
     */
    public function resumen($idInquilino)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $id = (int)$idInquilino;
            $items = $this->model->obtenerResumenPorPortal($id);
            if (!$items || count($items) === 0) {
                $agg = method_exists($this->model,'obtenerValidacionDemandas')
                    ? $this->model->obtenerValidacionDemandas($id) : null;
                if ($agg) {
                    $items = [[
                        'portal'        => 'juridico_agg',
                        'status'        => $agg['status'] ?? 'no_data',
                        'clasificacion' => $agg['clasificacion'] ?? 'sin_evidencia',
                        'score_max'     => (int)($agg['scoring'] ?? $agg['score'] ?? $agg['score_max'] ?? 0),
                        'resultado'     => json_encode($agg['evidencias'] ?? [], JSON_UNESCAPED_UNICODE),
                        'query_usada'   => json_encode(['fuente'=>'inquilinos_validaciones.inv_demandas_json','fecha'=>date('Y-m-d')]),
                        'searched_at'   => date('Y-m-d H:i:s'),
                        'evidencia_s3_key'=>null,
                        'raw_json_s3_key'=>null,
                        'error_message'=>null,
                    ]];
                }
            }
            echo json_encode(['ok'=>true,'items'=>$items ?: []]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok'=>false,'mensaje'=>'Error interno','error'=>$e->getMessage()]);
        }
    }

    /**
     * GET /inquilino/{slug}/validaciones/juridico
     */
    public function mostrarReporte(string $slug)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $inquilinoModel = new \App\Models\InquilinoModel();
            $inquilino = $inquilinoModel->obtenerPorSlug($slug);
            if (!$inquilino) {
                http_response_code(404);
                echo json_encode(['ok'=>false,'mensaje'=>'Inquilino no encontrado']);
                return;
            }
            $reporte = $this->model->obtenerValidacionDemandas((int)$inquilino['id']);
            echo json_encode(['ok'=>true,'slug'=>$slug,'reporte'=>$reporte]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok'=>false,'mensaje'=>'Error interno','error'=>$e->getMessage()]);
        }
    }

    /**
     * GET /validaciones/demandas/historial/{id}
     * Renderiza vista HTML
     */
   public function historial(int $idInquilino): void
{
    try {
        // 1) Trae el historial
        $historial = $this->model->obtenerHistorialPorInquilino($idInquilino);

        // 2) (Opcional) también puedes traer chips/último reporte aquí si quieres precargar
        // $items = $this->model->obtenerResumenPorPortal($idInquilino);
        // $reporte = $this->model->obtenerUltimoReportePorInquilino($idInquilino);

        // 3) Carga la vista general de validaciones, no juridico.php
        $title = "Validaciones del inquilino";
        $headerTitle = "Validaciones del inquilino #{$idInquilino}";
        $contentView = __DIR__ . '/../Views/inquilino/validaciones.php';

        // 4) Variables disponibles para la vista:
        //    $historial (y si quisieras, $items, $reporte)
        include __DIR__ . '/../Views/layouts/main.php';
    } catch (\Throwable $e) {
        http_response_code(500);
        echo "Error interno: ".$e->getMessage();
    }
}


    /**
     * GET /inquilino/{slug}/validaciones/demandas
     */
    public function historialPorSlug(string $slug): void
{
    try {
        $inquilinoModel = new \App\Models\InquilinoModel();
        $inquilino = $inquilinoModel->obtenerPorSlug($slug);
        if (!$inquilino || empty($inquilino['id'])) {
            http_response_code(404);
            echo "Inquilino no encontrado";
            return;
        }
        $this->historial((int)$inquilino['id']);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo "Error interno: ".$e->getMessage();
    }
}


    // ===== Utilidades privadas =====
    private function buildExecutionName(string $portal, int $idInquilino): string
    {
        $ts  = date('Ymd-His');
        $raw = "validacion-{$portal}-{$idInquilino}-{$ts}";
        $san = preg_replace('/[^A-Za-z0-9\-_]/', '-', $raw);
        return substr($san, 0, 80);
    }

    private function buildPersonKeyFromParts(string $nombre, string $apP, ?string $apM = null): string
    {
        $base = trim($nombre . ' ' . $apP . ' ' . ($apM ?? ''));
        $trans = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',
                  'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N'];
        $base = strtr($base, $trans);
        $key = strtolower(preg_replace('/[^a-z0-9]/i', '', $base));
        return $key;
    }

    /**
     * POST /validaciones/demandas/callback
     */
    public function callback()
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $raw  = file_get_contents('php://input');
            $data = json_decode($raw, true);
            if (!is_array($data)) {
                http_response_code(400);
                echo json_encode(['ok'=>false,'mensaje'=>'JSON inválido']);
                return;
            }
            $idInquilino = (int)($data['id_inquilino'] ?? 0);
            $portal      = $data['portal'] ?? '';
            if ($idInquilino<=0 || $portal==='') {
                http_response_code(400);
                echo json_encode(['ok'=>false,'mensaje'=>'id_inquilino y portal son obligatorios']);
                return;
            }
            $status        = $data['status'] ?? 'ok';
            $clasificacion = $data['clasificacion'] ?? 'sin_evidencia';
            $scoreMax      = (int)($data['score_max'] ?? 0);
            $evidKey       = $data['evidencia_s3_key'] ?? null;
            $rawKey        = $data['raw_json_s3_key']  ?? null;
            $resultadoArr  = (isset($data['resultado']) && is_array($data['resultado'])) ? $data['resultado'] : [];
            $errorMsg      = $data['error_message'] ?? null;
            $queryUsada = ['fuente'=>'callback','fecha'=>date('Y-m-d')];
            $ultimo = $this->model->obtenerUltimoReportePorInquilino($idInquilino, $portal);
            if (!$ultimo) {
                $nombre  = trim($data['nombre'] ?? '');
                $apP     = trim($data['apellido_p'] ?? '');
                $apM     = isset($data['apellido_m']) ? trim($data['apellido_m']) : null;
                $curp    = $data['curp'] ?? null;
                $rfc     = $data['rfc'] ?? null;
                $nuevoId = $this->model->registrarIntento([
                    'id_inquilino'=>$idInquilino,'nombre'=>$nombre,'apellido_p'=>$apP,
                    'apellido_m'=>$apM,'curp'=>$curp,'rfc'=>$rfc,
                    'portal'=>$portal,'query_usada'=>$queryUsada,'status'=>'no_data',
                ]);
                if (!$nuevoId) {
                    http_response_code(500);
                    echo json_encode(['ok'=>false,'mensaje'=>'No se pudo insertar intento']);
                    return;
                }
                $ultimo = ['id'=>(int)$nuevoId];
            }
            $ok = $this->model->guardarResultado(
                (int)$ultimo['id'],$resultadoArr,$scoreMax,$clasificacion,$evidKey,$rawKey,$status,$errorMsg
            );
            $this->model->actualizarSnapshotInquilino($idInquilino,[
                'status'=>$status,'clasificacion'=>$clasificacion,'score_max'=>$scoreMax,
                'evidencias'=>$resultadoArr,'evidencia_s3_key'=>$evidKey,
                'raw_json_s3_key'=>$rawKey,'portal'=>$portal,'fecha'=>date('Y-m-d'),
            ],$status,sprintf('portal=%s • status=%s • clasif=%s • score=%s • evidencias=%d • %s',
                $portal,$status,$clasificacion,(string)$scoreMax,count($resultadoArr),date('Y-m-d')));
            echo json_encode(['ok'=>(bool)$ok,'mensaje'=>$ok?'Resultado guardado':'No se pudo guardar','id_actualizado'=>(int)$ultimo['id']]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok'=>false,'mensaje'=>'Error interno','error'=>$e->getMessage()]);
        }
    }
}
