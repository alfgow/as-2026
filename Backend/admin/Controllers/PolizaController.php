<?php

declare(strict_types=1);

namespace App\Controllers;

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

require_once __DIR__ . '/../Models/PolizaModel.php';
require_once __DIR__ . '/../Models/ArrendadorModel.php';
require_once __DIR__ . '/../Models/InquilinoModel.php';
require_once __DIR__ . '/../Models/InmueblesModel.php';
require_once __DIR__ . '/../Models/AsesorModel.php';
require_once __DIR__ . '/../Models/FinancieroModel.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Core/RequestContext.php';

use App\Core\RequestContext;
use App\Models\PolizaModel;
use App\Models\ArrendadorModel;
use App\Models\InquilinoModel;
use App\Models\InmuebleModel;
use App\Models\AsesorModel;
use App\Models\FinancieroModel;

use DateTime;
use IntlDateFormatter;
use NumberFormatter;
use PhpOffice\PhpWord\TemplateProcessor;

require_once __DIR__ . '/../Middleware/AuthMiddleware.php';

use App\Middleware\AuthMiddleware;

if (!defined('REQUEST_IS_API') || REQUEST_IS_API === false) {
    AuthMiddleware::verificarSesion();
}

class PolizaController
{
    private bool $requestIsApi;

    public function __construct(bool $requestIsApi = false)
    {
        $this->requestIsApi = $requestIsApi;
    }

    /* =========================
       Listado / PDF / Búsqueda
       ========================= */

    public function index(): void
    {
        $model = new PolizaModel();

        // Paginación
        $porPagina = 10;
        $pagina = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $offset = ($pagina - 1) * $porPagina;

        // Filtros
        $estado = $_GET['estado'] ?? null;
        $tipo   = $_GET['tipo'] ?? null;
        $buscar = $_GET['buscar'] ?? null;

        // Datos
        $polizas       = $model->obtenerPaginadasFiltradas($porPagina, $offset, $estado, $tipo, $buscar);
        $totalPolizas  = (int)$model->contarFiltradas($estado, $tipo, $buscar);
        $totalPaginas  = (int)ceil($totalPolizas / $porPagina);
        $ultimaPoliza  = (int)$model->obtenerUltimaPolizaEmitida();

        // Indicadores
        $polizasVigentes    = (int)$model->contarPorEstado('1');
        $polizasConcluidas  = (int)$model->contarPorEstado('2');
        $polizasIncumplidas = (int)$model->contarPorEstado('4');

        if ($this->requestIsApi) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok'            => true,
                'filters'       => [
                    'estado' => $estado,
                    'tipo'   => $tipo,
                    'buscar' => $buscar,
                ],
                'pagination'    => [
                    'per_page' => $porPagina,
                    'page'     => $pagina,
                    'pages'    => $totalPaginas,
                    'total'    => $totalPolizas,
                ],
                'stats'         => [
                    'vigentes'    => $polizasVigentes,
                    'concluidas'  => $polizasConcluidas,
                    'incumplidas' => $polizasIncumplidas,
                    'ultima'      => $ultimaPoliza,
                ],
                'polizas'       => $polizas,
            ]);
            return;
        }

        $title       = 'Pólizas - AS';
        $headerTitle = 'Pólizas Jurídicas';
        $contentView = __DIR__ . '/../Views/polizas/index.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }

    public function pdf(int $numero): void
    {
        $modelo = new PolizaModel();
        $poliza = $modelo->obtenerPorNumero($numero);
        if (!$poliza) {
            http_response_code(404);
            echo 'No se encontró la póliza';
            return;
        }
        // La vista pdf.php debe emitir headers y renderizar
        include __DIR__ . '/../Views/polizas/pdf.php';
    }

    public function generarPdf(int $numero): void
    {
        $modelo = new PolizaModel();
        $poliza = $modelo->obtenerPorNumero($numero);

        if (!$poliza) {
            http_response_code(404);
            echo 'No se encontró la póliza';
            return;
        }
        $normalizeName = function (string $str): string {
            $str = mb_strtolower(trim($str), 'UTF-8');
            $str = strtr($str, [
                'á' => 'a',
                'é' => 'e',
                'í' => 'i',
                'ó' => 'o',
                'ú' => 'u',
                'ä' => 'a',
                'ë' => 'e',
                'ï' => 'i',
                'ö' => 'o',
                'ü' => 'u',
                'Á' => 'a',
                'É' => 'e',
                'Í' => 'i',
                'Ó' => 'o',
                'Ú' => 'u',
                'ñ' => 'n',
                'Ñ' => 'n',
                'ç' => 'c'
            ]);
            return preg_replace('/[^a-z0-9]/', '', $str);
        };

        // --- Normalización de nombres para el s3_key ---
        $nombreNormalizado = $normalizeName($poliza['nombre_arrendador']);
        $direccionSlug = substr(
            preg_replace('/[^a-z0-9]+/i', '_', strtolower($poliza['direccion_inmueble'])),
            0,
            40
        );

        // --- Construcción del s3_key ---
        $s3Key = "{$poliza['id_arrendador']}_{$nombreNormalizado}/Poliza_{$poliza['numero_poliza']}_{$direccionSlug}.docx";

        // --- Selección de plantilla según tipo de póliza ---
        $tipoPolizaOriginal = $poliza['tipo_poliza'];
        $tipoPolizaKey = mb_strtolower($tipoPolizaOriginal, 'UTF-8');
        $plantillasPorTipo = [
            'clásica' => 'Plantilla_Poliza_Clásica.docx',
            'plus' => 'Plantilla_Poliza_Plus.docx',
        ];

        if (!isset($plantillasPorTipo[$tipoPolizaKey])) {
            throw new \Exception("No se encontró la plantilla para el tipo de póliza: {$tipoPolizaOriginal}");
        }

        $plantillaPath = __DIR__ . '/../../plantillas/' . $plantillasPorTipo[$tipoPolizaKey];

        if (!file_exists($plantillaPath)) {
            throw new \Exception("No se encontró la plantilla para el tipo de póliza: {$tipoPolizaOriginal}");
        }

        // --- Cargar plantilla ---
        $template = new \PhpOffice\PhpWord\TemplateProcessor($plantillaPath);

        // --- Reemplazar placeholders ---
        $template->setValue('NUM', $poliza['numero_poliza']);
        $template->setValue('FECHA_EMISION', date('d/m/Y', strtotime($poliza['fecha_poliza'])));
        $template->setValue('SERIE', $poliza['serie_poliza']);
        $template->setValue('ASESOR', $poliza['nombre_asesor']);
        $template->setValue('VIGENCIA', $poliza['vigencia']);
        $template->setValue('MONTO_RENTA', '$' . number_format((float)$poliza['monto_renta'], 2));
        $template->setValue('MONTO_POLIZA', '$' . number_format((float)$poliza['monto_poliza'], 2));
        $template->setValue('TIPO_INMUEBLE', $poliza['tipo_inmueble']);
        $template->setValue('DIRECCION_INMUEBLE', $poliza['direccion_inmueble']);
        $template->setValue('ARRENDADOR', $poliza['nombre_arrendador']);
        $template->setValue('ARRENDATARIO', $poliza['nombre_inquilino_completo']);
        $template->setValue('OBLIGADO_SOLIDARIO', $poliza['nombre_obligado_completo'] ?? 'N/A');
        $template->setValue('FIADOR', $poliza['nombre_fiador'] ?? 'N/A');

        // --- Guardar temporal DOCX ---
        $tmpDocx = sys_get_temp_dir() . "/poliza_{$numero}.docx";
        $template->saveAs($tmpDocx);

        // --- Configurar cliente S3 usando bucket arrendadores ---
        $config = require __DIR__ . '/../config/s3config.php';
        $s3Config = $config['arrendadores'];

        $s3 = new \Aws\S3\S3Client([
            'region'      => $s3Config['region'],
            'version'     => 'latest',
            'credentials' => $s3Config['credentials'],
        ]);

        // --- Subir a S3 ---
        $s3->putObject([
            'Bucket'      => $s3Config['bucket'],
            'Key'         => $s3Key,
            'SourceFile'  => $tmpDocx,
            'ACL'         => 'private',
            'ContentType' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);

        // --- Registrar en arrendadores_archivos ---
        $modelo->guardarArchivoPoliza($poliza['id_arrendador'], $s3Key);

        // --- Forzar descarga en navegador ---
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="Poliza_' . $poliza['numero_poliza'] . '.docx"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($tmpDocx));
        readfile($tmpDocx);
        exit;
    }

    public function buscar(): void
    {
        $numero = $_GET['numero'] ?? null;
        $model  = new PolizaModel();
        $poliza = $numero ? $model->obtenerPorNumero((int)$numero) : null;

        $title       = 'Buscar póliza';
        $headerTitle = 'Buscar póliza';
        $contentView = __DIR__ . '/../Views/polizas/buscar.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }

    public function mostrar(int $numero): void
    {
        $model           = new PolizaModel();
        $arrendadorModel = new ArrendadorModel();
        $inquilinoModel  = new InquilinoModel();
        $inmuebleModel   = new InmuebleModel();
        $asesorModel     = new AsesorModel();

        $poliza = $model->obtenerPorNumero($numero);
        if (!$poliza) {
            http_response_code(404);
            $headerTitle = 'Póliza no encontrada';
            $contentView = __DIR__ . '/../Views/404.php';
            include __DIR__ . '/../Views/layouts/main.php';
            return;
        }

        $title       = 'Póliza #' . htmlspecialchars((string)$poliza['numero_poliza']);
        $headerTitle = 'Póliza #' . htmlspecialchars((string)$poliza['numero_poliza']);

        $arrendadores = $arrendadorModel->obtenerTodos();
        $asesores     = $asesorModel->all();
        $inquilinos = $inquilinoModel->getInquilinosAll();
        $fiadores = $inquilinoModel->getFiadoresAll();
        $obligados = $inquilinoModel->getObligadosAll();


        $inmuebles    = $inmuebleModel->obtenerTodos();
        $inmueble     = $inmuebleModel->obtenerPorId((int)$poliza['id_inmueble']);

        // Compatibilidad con ambos nombres de métodos


        $siguienteNumero = (int)$model->obtenerUltimaPolizaEmitida() + 1;

        $contentView = __DIR__ . '/../Views/polizas/detalle.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }


    public function nueva(): void
    {
        $polizaModel     = new PolizaModel();
        $arrendadorModel = new ArrendadorModel();
        $inquilinoModel  = new InquilinoModel();
        $inmuebleModel   = new InmuebleModel();
        $asesorModel     = new AsesorModel();

        $siguienteNumero = (int)$polizaModel->obtenerUltimaPolizaEmitida() + 1;
        $arrendadores    = $arrendadorModel->obtenerTodos();
        $asesores        = $asesorModel->all();
        $inmuebles       = $inmuebleModel->obtenerTodos();
        $inquilinos = $inquilinoModel->getInquilinosAll();
        $fiadores = $inquilinoModel->getFiadoresAll();
        $obligados = $inquilinoModel->getObligadosAll();

        // Compatibilidad con ambos nombres de métodos

        $title       = 'Nueva póliza';
        $headerTitle = 'Registrar póliza';
        $contentView = __DIR__ . '/../Views/polizas/nueva.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }

    // Dentro de App\Controllers\PolizaController

    public function editar(int $numeroPoliza): void
    {
        try {
            $polizaModel     = new \App\Models\PolizaModel();
            $arrendadorModel = new \App\Models\ArrendadorModel();
            $inquilinoModel  = new \App\Models\InquilinoModel();
            $inmuebleModel   = new \App\Models\InmuebleModel();
            $asesorModel     = new \App\Models\AsesorModel();

            $poliza = $polizaModel->obtenerPorNumero($numeroPoliza);
            if (!$poliza) {
                http_response_code(404);
                $headerTitle = 'Póliza no encontrada';
                $contentView = __DIR__ . '/../Views/404.php';
                include __DIR__ . '/../Views/layouts/main.php';
                return;
            }

            // Catálogos básicos
            $arrendadores = $arrendadorModel->obtenerTodos();
            $asesores     = $asesorModel->all();
            $inmuebles    = $inmuebleModel->obtenerTodos();

            // Trae TODOS los prospectos y filtra por rol (aceptando sinónimos)
            $todos = $inquilinoModel->buscarConFiltros('', '', '', 10000, 0); // SELECT * FROM inquilinos
            $norm = static fn($r) => mb_strtolower(trim($r['tipo'] ?? ''), 'UTF-8');

            $inquilinos = array_values(array_filter($todos, fn($r) => in_array($norm($r), [
                'arrendatario',
                'inquilino'
            ], true)));

            $fiadores = array_values(array_filter($todos, fn($r) => in_array($norm($r), [
                'fiador'
            ], true)));

            $obligados = array_values(array_filter($todos, fn($r) => in_array($norm($r), [
                'obligado solidario',
                'obligado'
            ], true)));

            // Render de la vista de edición (asegúrate de apuntar a editar.php)
            $editMode    = true;
            $headerTitle = 'Editar Póliza #' . htmlspecialchars((string)$poliza['numero_poliza']);
            $contentView = __DIR__ . '/../Views/polizas/editar.php';
            include __DIR__ . '/../Views/layouts/main.php';
        } catch (\Throwable $e) {
            http_response_code(500);
            echo "Error al cargar edición: " . $e->getMessage();
        }
    }


    public function renta(int $numeroPoliza): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            http_response_code(405);
            echo json_encode([
                'ok'    => false,
                'error' => 'Método no permitido'
            ]);
            return;
        }

        try {
            $polizaModel   = new \App\Models\PolizaModel();
            $inmuebleModel = new \App\Models\InmuebleModel();

            $poliza = $polizaModel->obtenerPorNumero($numeroPoliza);
            if (!$poliza) {
                http_response_code(404);
                echo json_encode([
                    'ok'    => false,
                    'error' => 'Póliza no encontrada'
                ]);
                return;
            }

            $inmuebleIdParam = isset($_GET['id_inmueble']) ? (int) $_GET['id_inmueble'] : 0;
            $inmuebleId = $inmuebleIdParam > 0
                ? $inmuebleIdParam
                : (int)($poliza['id_inmueble'] ?? 0);

            if ($inmuebleId <= 0) {
                http_response_code(404);
                echo json_encode([
                    'ok'    => false,
                    'error' => 'La póliza no tiene un inmueble asociado'
                ]);
                return;
            }

            $inmueble = $inmuebleModel->obtenerPorId($inmuebleId);
            if (!$inmueble) {
                http_response_code(404);
                echo json_encode([
                    'ok'    => false,
                    'error' => 'Inmueble no encontrado'
                ]);
                return;
            }

            $renta = (string)($inmueble['renta'] ?? '');
            $rentaNormalizada = preg_replace('/[^\d.]/', '', $renta);

            echo json_encode([
                'ok'                  => true,
                'monto_renta'         => $renta,
                'monto_renta_numerica' => $rentaNormalizada,
                'id_inmueble'         => $inmuebleId,
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'ok'    => false,
                'error' => 'No se pudo obtener la renta',
                'detalle' => $e->getMessage(),
            ]);
        }
    }


    public function renovar(int $numeroPoliza): void
    {
        try {
            // ── Modelos
            $polizaModel     = new \App\Models\PolizaModel();
            $asesorModel     = new \App\Models\AsesorModel();
            $arrendadorModel = new \App\Models\ArrendadorModel();
            $inmueblesModel  = new \App\Models\InmuebleModel(); // nombre del archivo que tienes
            $inquilinoModel  = new \App\Models\InquilinoModel();

            // ── 1) Póliza base
            $poliza = $polizaModel->obtenerPorNumero($numeroPoliza);
            if (!$poliza) {
                http_response_code(404);
                echo "Póliza no encontrada";
                return;
            }

            // ── 2) Siguiente número de póliza (CORREGIDO)
            $ultimaNumero    = (int) $polizaModel->obtenerUltimaPolizaEmitida(); // devuelve string, conviértelo
            $siguienteNumero = $ultimaNumero > 0
                ? ($ultimaNumero + 1)
                : ((int)$poliza['numero_poliza'] + 1);

            // ── 3) Catálogos base
            $asesores     = method_exists($asesorModel, 'all') ? $asesorModel->all()
                : (method_exists($asesorModel, 'obtenerTodos') ? $asesorModel->all() : []);
            $arrendadores = method_exists($arrendadorModel, 'obtenerTodos') ? $arrendadorModel->obtenerTodos() : [];
            $inmuebles    = method_exists($inmueblesModel, 'obtenerTodos')  ? $inmueblesModel->obtenerTodos()  : [];

            // ── 4) Prospectos (trae TODOS y filtra por tipo en PHP)
            if (method_exists($inquilinoModel, 'buscarConFiltros')) {
                $todosProspectos = $inquilinoModel->buscarConFiltros('', '', '', 10000, 0);
            } elseif (method_exists($inquilinoModel, 'obtenerTodos')) {
                $todosProspectos = $inquilinoModel->getInquilinosAll();
            } else {
                $todosProspectos = [];
            }

            $norm = static function ($row) {
                return mb_strtolower(trim($row['tipo'] ?? ''), 'UTF-8');
            };

            $inquilinos = array_values(array_filter(
                $todosProspectos,
                fn($r) =>
                in_array($norm($r), ['arrendatario', 'inquilino'], true)
            ));

            $fiadores = array_values(array_filter(
                $todosProspectos,
                fn($r) =>
                $norm($r) === 'fiador'
            ));

            $obligados = array_values(array_filter(
                $todosProspectos,
                fn($r) =>
                in_array($norm($r), ['obligado', 'obligado solidario'], true)
            ));

            // ── 5) Inmueble seleccionado (para renta mostrada en la vista)
            $inmueble = null;
            foreach ($inmuebles as $i) {
                if ((int)($i['id'] ?? 0) === (int)($poliza['id_inmueble'] ?? 0)) {
                    $inmueble = $i;
                    break;
                }
            }

            // ── 6) Variables de vista
            $baseUrl      = function_exists('base_url') ? base_url() : '';
            $headerTitle  = 'Renovar póliza';
            $title        = 'Renovar póliza #' . htmlspecialchars((string)$poliza['numero_poliza']);
            $editMode     = false;

            // ── 7) Vista
            $contentView = __DIR__ . '/../Views/polizas/renovar.php';
            include __DIR__ . '/../Views/layouts/main.php';
        } catch (\Throwable $e) {
            http_response_code(500);
            echo "Error al cargar renovación: " . $e->getMessage();
        }
    }

    // ─────────────────────────────────────────────────────────────
    // EDITAR (POST /polizas/actualizar)
    // ─────────────────────────────────────────────────────────────
    public function actualizar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $numero = $_POST['numero_poliza'] ?? null;
            if (!$numero) throw new \Exception('Falta número de póliza');

            $data = $this->mapEntradaPoliza($_POST, /*esCreacion=*/ false);

            $polizaModel = new \App\Models\PolizaModel();
            $ok = $polizaModel->update((int)$numero, $data);

            echo json_encode(['ok' => (bool)$ok]);
        } catch (\Throwable $e) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // RENOVAR (POST /polizas/store)
    // ─────────────────────────────────────────────────────────────
    public function store(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {

            $data = $this->mapEntradaPoliza($_POST, /*esCreacion=*/ true);

            // Recalcula SIEMPRE el número en servidor para evitar duplicados
            $polizaModel = new \App\Models\PolizaModel();
            $data['numero_poliza'] = (int)$polizaModel->obtenerUltimaPolizaEmitida() + 1;

            // Campos requeridos por crear()
            $data['usuario'] = $this->resolveUsuarioId();
            $data['serie_poliza'] = $data['serie_poliza'] ?? date('Y');
            if (!empty($data['fecha_fin'])) {
                $ts = strtotime($data['fecha_fin']);
                $data['mes_vencimiento']  = (int)date('n', $ts);
                $data['year_vencimiento'] = (int)date('Y', $ts);
            }

            // Intento 1
            $ok = $polizaModel->crear($data);

            // Si hubo colisión por carrera, vuelve a calcular y reintenta 1 vez
            if (!$ok) {
                // opcional: lee el código de error desde PDO, pero con un reintento sencillo suele bastar
                $data['numero_poliza'] = (int)$polizaModel->obtenerUltimaPolizaEmitida() + 1;
                $ok = $polizaModel->crear($data);
            }

            // Registrar venta automática si se creó la póliza
            $ventaOk = false;
            if ($ok) {
                $finModel = new \App\Models\FinancieroModel();
                $ventaOk = $finModel->registrarVentaAutomatica([
                    'monto_poliza'     => $data['monto_poliza'] ?? 0,
                    'numero_poliza'    => $data['numero_poliza'],
                    'year_vencimiento' => $data['year_vencimiento'] ?? date('Y'),
                ]);
            }

            echo json_encode(['ok' => (bool)$ok, 'venta_ok' => (bool)$ventaOk, 'numero' => $data['numero_poliza']]);
        } catch (\Throwable $e) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // HELPERS PRIVADOS
    // ─────────────────────────────────────────────────────────────

    /**
     * Mapea y sanea la entrada de formularios de editar/renovar a columnas del modelo.
     * @param array $in POST
     * @param bool $esCreacion true para store (renovar)
     * @return array
     * @throws \Exception
     */
    private function mapEntradaPoliza(array $in, bool $esCreacion): array
    {
        // Normalizar/validar
        $tipoPoliza   = trim($in['tipo_poliza'] ?? '');
        $idAsesor     = $this->intvalOrNull($in['id_asesor'] ?? null);
        $idArrendador = $this->intvalOrNull($in['id_arrendador'] ?? null);
        $idInmueble   = $this->intvalOrNull($in['id_inmueble'] ?? null);
        $tipoInmueble = trim($in['tipo_inmueble'] ?? '');
        $fechaInicio  = $this->yyyy_mm_dd($in['fecha_poliza'] ?? null);
        $fechaFin     = $this->yyyy_mm_dd($in['fecha_fin'] ?? null);

        if (!$tipoPoliza)         throw new \Exception('Falta tipo de póliza');
        if (!$idArrendador)       throw new \Exception('Selecciona un arrendador');
        if (!$idInmueble)         throw new \Exception('Selecciona un inmueble');
        if (!$fechaInicio)        throw new \Exception('Falta fecha de inicio');
        if (!$fechaFin)           throw new \Exception('Falta fecha de fin');
        if ($fechaInicio > $fechaFin) throw new \Exception('La fecha de inicio no puede ser mayor a la de fin');

        // Estado: soporta numérico o texto; guardamos SIEMPRE el número
        $estado = (int)($in['estado'] ?? 1);
        if ($estado < 1 || $estado > 4) {
            $estado = 1; // default Vigente
        }

        // Montos
        // renta llega como "$12,345.67" o "12345.67"
        $montoRenta  = $this->toFloat($in['monto_renta'] ?? null);
        // monto póliza puede venir vacío: lo calculamos con la fórmula JS, pero en PHP
        $montoPoliza = $this->toFloat($in['monto_poliza'] ?? null);
        if ($montoPoliza <= 0 && $montoRenta > 0) {
            $montoPoliza = $this->calcularMontoPolizaPHP($montoRenta, $tipoPoliza);
        }

        // Vigencia en texto (si viene vacío, la generamos)
        $vigencia = trim($in['vigencia'] ?? '');
        if ($vigencia === '' && $fechaInicio && $fechaFin) {
            $vigencia = $this->vigenciaTexto($fechaInicio, $fechaFin);
        }

        $idInquilino = $this->intvalOrNull($in['id_inquilino'] ?? null);
        $idFiador    = $this->intvalOrNull($in['id_fiador'] ?? null);
        $idObligado  = $this->intvalOrNull($in['id_obligado'] ?? null);

        // Comentarios
        $comentarios = trim($in['comentarios'] ?? '');

        // Armamos payload para el modelo (ajusta claves si tu modelo usa otros nombres)
        $data = [
            'tipo_poliza'   => $tipoPoliza,
            'vigencia'      => $vigencia,
            'fecha_poliza'  => $fechaInicio,
            'fecha_fin'     => $fechaFin,
            'monto_poliza'  => $montoPoliza,
            'monto_renta'   => $montoRenta,
            'estado'        => $estado,
            'id_inmueble'   => $idInmueble,
            'tipo_inmueble' => $tipoInmueble,
            'id_arrendador' => $idArrendador,
            'id_inquilino'  => $idInquilino,
            'id_fiador'     => $idFiador,
            'id_obligado'   => $idObligado,
            'id_asesor'     => $idAsesor,
            'comentarios'   => $comentarios,
        ];

        // En creación podrías setear defaults adicionales si aplica
        if ($esCreacion) {
            // por ejemplo, estado inicial Vigente si no viene:
            if (!$data['estado']) $data['estado'] = '1';
        }

        return $data;
    }

    private function intvalOrNull($v): ?int
    {
        if ($v === '' || $v === null) return null;
        return (int)$v;
    }

    private function toFloat($v): float
    {
        if ($v === null || $v === '') return 0.0;
        // elimina símbolos de moneda y separadores de miles
        $s = preg_replace('/[^\d\.\-]/', '', (string)$v);
        return (float)$s;
    }

    private function yyyy_mm_dd($v): ?string
    {
        if (!$v) return null;
        // acepta 'YYYY-MM-DD' estrictamente
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) return $v;
        // si llega en otro formato, intenta normalizar (puedes ampliar este bloque si lo necesitas)
        return null;
    }

    private function estadoATexto($estado): string
    {
        // Soporta numérico (1..4) o texto directo
        if (is_numeric($estado)) {
            $map = [
                1 => 'Vigente',
                2 => 'Concluida',
                3 => 'Término Anticipado',
                4 => 'Incumplimiento',
            ];
            return $map[(int)$estado] ?? 'Vigente';
        }
        $txt = trim((string)$estado);
        return $txt !== '' ? $txt : 'Vigente';
    }

    private function vigenciaTexto(string $ini, string $fin): string
    {
        static $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        [$yi, $mi, $di] = explode('-', $ini);
        [$yf, $mf, $df] = explode('-', $fin);
        $mi = (int)$mi;
        $mf = (int)$mf;
        return sprintf('del %d de %s de %d al %d de %s de %d', (int)$di, $meses[$mi - 1], (int)$yi, (int)$df, $meses[$mf - 1], (int)$yf);
    }

    /**
     * Réplica de la regla JS en PHP
     */
    private function calcularMontoPolizaPHP(float $renta, string $tipo): float
    {
        $rangos = [10000, 15000, 20000, 25000, 30000, 35000, 40000, 45000, 50000];
        $tramosClasica = [3700, 4300, 4500, 5200, 5500, 8100, 9300, 10000, 12000, $renta * 0.25];
        $tramosPlus    = [4800, 5500, 7500, 8600, 9400, 11000, 11500, 13750, 14250, $renta * 0.30];

        $precios = (mb_strtolower($tipo) === 'plus') ? $tramosPlus : $tramosClasica;

        foreach ($rangos as $i => $limite) {
            if ($renta <= $limite) return (float)$precios[$i];
        }
        return (float)end($precios);
    }

    /* =========================
       Generación de contratos
       ========================= */

    public function generacionContrato(int $numeroPoliza): void
    {
        $modelo = new PolizaModel();
        $poliza = $modelo->obtenerPorNumero($numeroPoliza);

        if (!$poliza) {
            echo "❌ No se encontró la póliza con número: $numeroPoliza";
            return;
        }

        // Calcula vigencia legible si no viene
        $fechaInicio = $poliza['fecha_poliza'] ?? date('Y-m-d');
        $vigencia    = date('d/m/Y', strtotime($fechaInicio)) . ' al ' . date('d/m/Y', strtotime('+1 year -1 day', strtotime($fechaInicio)));
        $poliza['vigencia'] = $poliza['vigencia'] ?: $vigencia;

        $title       = "Generación de Contrato";
        $headerTitle = "Contrato para póliza #{$numeroPoliza}";
        $contentView = __DIR__ . '/../Views/polizas/generacion-contrato.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }

    public function generarContratoDesdeFormulario(): void
    {
        header('Content-Type: application/json');

        $numeroPoliza = $_POST['numero_poliza'] ?? null;
        $tipoContrato = $_POST['tipo_contrato'] ?? null;

        if (!$tipoContrato) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Selecciona el tipo de contrato.']);
            return;
        }

        $modelo = new PolizaModel();
        $poliza = $modelo->obtenerPorNumero((int)$numeroPoliza);

        if (!$poliza) {
            echo json_encode(['status' => 'error', 'mensaje' => 'No se encontró la póliza.']);
            return;
        }

        // Plantilla por tipo
        $tipoContratoOriginal = $tipoContrato;
        $tipoContratoKey = mb_strtolower($tipoContratoOriginal, 'UTF-8');
        $plantillasContrato = [
            'normal_pf' => 'Contrato_Normal_PF 2025.docx',
            'os_pf'     => 'Contrato_ObligadoSolidario_PF 2025.docx',
            'fiador_pf'    => 'Contrato_Fiador_PF 2025.docx',
            'pmoral'    => 'Contrato_Persona_Moral.docx',
        ];

        if (!isset($plantillasContrato[$tipoContratoKey])) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Sorry!, aún no cargamos ese tipo de contrato.']);
            return;
        }

        $plantillaDocx = __DIR__ . '/../../plantillas/' . $plantillasContrato[$tipoContratoKey];

        if (!file_exists($plantillaDocx)) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Sorry!, aún no cargamos ese tipo de contrato.']);
            return;
        }

        $template = new TemplateProcessor($plantillaDocx);
        $vars = $template->getVariables();
        $set = function (string $key, $value) use ($template, $vars) {
            if (in_array($key, $vars, true)) {
                $template->setValue($key, (string)$value);
            }
        };
        $mayus = fn($v) => mb_strtoupper((string)$v, 'UTF-8');


        // Nombres
        $inquilino = trim($poliza['nombre_inquilino_completo'] ?? '');
        $arrendador = trim($poliza['nombre_arrendador'] ?? '');
        $obligadoSolidario = trim($poliza['nombre_obligado_completo'] ?? '');
        $fiador = trim($poliza['nombre_fiador_completo'] ?? '');

        // Helpers locales
        $nf = new NumberFormatter('es', NumberFormatter::SPELLOUT);
        $textoCajones = function ($valor) use ($nf) {
            $cantidad = (int) $valor;
            if ($cantidad === 0) return 'SIN ESTACIONAMIENTO';
            if ($cantidad === 1) return 'CON DERECHO AL USO EXCLUSIVO DE UN CAJÓN DE ESTACIONAMIENTO';
            $enTexto = mb_strtoupper($nf->format($cantidad), 'UTF-8');
            $enTexto = str_replace('UNO', 'UN', $enTexto);
            return "CON DERECHO AL USO EXCLUSIVO DE $enTexto CAJONES DE ESTACIONAMIENTO";
        };
        $montoEnNumeroYTexto = function ($monto) use ($nf): string {
            // Acepta float/int o string tipo "$8,800.75"
            if (!is_float($monto) && !is_int($monto)) {
                $monto = (float) str_replace(['$', ',', ' '], '', (string)$monto);
            }
            $monto   = round((float)$monto, 2);
            $numero  = '$' . number_format($monto, 2);

            // Separar entero y centavos cuidando el redondeo
            $entero   = (int) floor($monto);
            $centavos = (int) round(($monto - $entero) * 100);
            if ($centavos === 100) {
                $entero += 1;
                $centavos = 0;
            }

            // Texto en español en mayúsculas, ajustando "UNO" -> "UN"
            $texto = mb_strtoupper($nf->format($entero), 'UTF-8');
            $texto = str_replace('UNO', 'UN', $texto);

            return sprintf('%s (%s PESOS %02d/100 M.N.)', $numero, $texto, $centavos);
        };
        $normalizarTipoIdentificacion = function ($tipo) {
            $tipo = trim(mb_strtoupper($tipo ?? '', 'UTF-8'));
            if (in_array($tipo, ['INE', 'IFE', 'INE/IFE', 'INE / IFE'])) return 'CREDENCIAL DE ELECTOR';
            if ($tipo === 'PASAPORTE') return 'PASAPORTE';
            return $tipo;
        };

        // Mantenimiento
        /**
         * Convierte un número a letras en español con formato de moneda (M.N.)
         */
        function numeroALetras($monto): string
        {
            $formatter = new NumberFormatter("es", NumberFormatter::SPELLOUT);

            // Asegurar decimales
            $monto = number_format((float)$monto, 2, '.', '');
            [$enteros, $decimales] = explode('.', $monto);

            $textoEnteros = mb_strtoupper($formatter->format((int)$enteros), 'UTF-8');
            $textoDecimales = str_pad($decimales, 2, '0', STR_PAD_RIGHT);

            return "{$textoEnteros} PESOS {$textoDecimales}/100 M.N.";
        }

        // 🔹 Normalizamos el valor
        $raw = (string)($poliza['mantenimiento_inmueble'] ?? '');
        $val = mb_strtolower(trim($raw), 'UTF-8');
        $monto = (float)($poliza['monto_mantenimiento'] ?? 0);

        // Formatos
        $montoFormateado = '$' . number_format($monto, 2, '.', ',');
        $montoEnLetras   = numeroALetras($monto);

        $positivos = ['si', 'sí', '1', 'true', 'incluye', 'incluido'];
        $negativos = ['no', '0', 'false', 'excluye', 'excluido'];
        $noaplica  = ['no aplica', 'na', 'n/a', 'none'];

        if (in_array($val, $positivos, true)) {
            $clausulaMtto = <<<TXT
LAS PARTES ACUERDAN QUE EL PAGO DE LA CUOTA MENSUAL DE MANTENIMIENTO CORRESPONDIENTE AL INMUEBLE, QUE AL MOMENTO DE LA FIRMA DEL PRESENTE CONTRATO ASCIENDE A LA CANTIDAD DE {$montoFormateado} ({$montoEnLetras}), YA ESTÁ INCLUIDO EN EL MONTO DE LA RENTA MENSUAL ESTABLECIDO EN LA CLÁUSULA SEGUNDA DEL PRESENTE CONTRATO. EN CONSECUENCIA, "EL ARRENDADOR" SE OBLIGA A REALIZAR DICHO PAGO PUNTUALMENTE ANTE LA ADMINISTRACIÓN DEL EDIFICIO O CONJUNTO HABITACIONAL CORRESPONDIENTE, GARANTIZANDO A "EL ARRENDATARIO" QUE LOS SERVICIOS COMUNES INCLUIDOS EN DICHA CUOTA NO SERÁN INTERRUMPIDOS, LIMITADOS NI SUSPENDIDOS DURANTE LA VIGENCIA DEL PRESENTE CONTRATO.
NO OBSTANTE LO ANTERIOR, EN CASO DE QUE LA CUOTA DE MANTENIMIENTO SUFRA UN INCREMENTO DURANTE LA VIGENCIA DEL PRESENTE CONTRATO, LAS PARTES CONVIENEN QUE DICHO INCREMENTO SERÁ CUBIERTO POR “EL ARRENDATARIO”, POR SER EL USUARIO DIRECTO DE LOS BENEFICIOS DERIVADOS DEL MANTENIMIENTO, SIEMPRE Y CUANDO DICHO AUMENTO SEA DEBIDAMENTE JUSTIFICADO POR LA ADMINISTRACIÓN CORRESPONDIENTE.
TXT;
        } elseif (in_array($val, $negativos, true)) {
            $clausulaMtto = <<<TXT
LAS PARTES ACUERDAN QUE EL PAGO DE LA CUOTA MENSUAL DE MANTENIMIENTO CORRESPONDIENTE AL INMUEBLE, QUE AL MOMENTO DE LA FIRMA DEL PRESENTE CONTRATO ASCIENDE A LA CANTIDAD DE {$montoFormateado} ({$montoEnLetras}), NO ESTÁ INCLUIDO EN EL MONTO DE LA RENTA MENSUAL ESTABLECIDO EN LA CLÁUSULA SEGUNDA DEL PRESENTE CONTRATO. EN CONSECUENCIA, "EL ARRENDATARIO" SE OBLIGA A REALIZAR DICHO PAGO PUNTUALMENTE ANTE LA ADMINISTRACIÓN DEL EDIFICIO O CONJUNTO HABITACIONAL CORRESPONDIENTE, GARANTIZANDO A "EL ARRENDADOR" QUE LOS SERVICIOS COMUNES INCLUIDOS EN DICHA CUOTA NO SERÁN INTERRUMPIDOS, LIMITADOS NI SUSPENDIDOS DURANTE LA VIGENCIA DEL PRESENTE CONTRATO.
NO OBSTANTE LO ANTERIOR, EN CASO DE QUE LA CUOTA DE MANTENIMIENTO SUFRA UN INCREMENTO DURANTE LA VIGENCIA DEL PRESENTE CONTRATO, LAS PARTES CONVIENEN QUE DICHO INCREMENTO SERÁ CUBIERTO POR “EL ARRENDATARIO”, POR SER EL USUARIO DIRECTO DE LOS BENEFICIOS DERIVADOS DEL MANTENIMIENTO, SIEMPRE Y CUANDO DICHO AUMENTO SEA DEBIDAMENTE JUSTIFICADO POR LA ADMINISTRACIÓN CORRESPONDIENTE.
TXT;
        } elseif (in_array($val, $noaplica, true)) {
            $clausulaMtto = <<<TXT
LAS PARTES ACUERDAN QUE EN EL PRESENTE CONTRATO NO APLICA EL PAGO DE CUOTAS DE MANTENIMIENTO, YA QUE EL INMUEBLE OBJETO DE ARRENDAMIENTO NO SE ENCUENTRA SUJETO A DICHO CONCEPTO.
TXT;
        } else {
            $clausulaMtto = <<<TXT
LAS PARTES ACUERDAN QUE EL PAGO DE LA CUOTA DE MANTENIMIENTO DEL INMUEBLE NO HA SIDO ESPECIFICADO CLARAMENTE, POR LO QUE SE SUJETARÁ A LO QUE POSTERIORMENTE PACTEN POR ESCRITO.
TXT;
        }

        // 👉 Finalmente lo seteas en tu template
        $set('CLAUSULA_MTTO', $mayus($clausulaMtto));


        // Normaliza el campo de mascotas del inmueble (adáptalo a tus posibles valores)
        $rawMascotas = (string)($poliza['mascotas_inmueble'] ?? '');
        $valor = mb_strtolower(trim($rawMascotas), 'UTF-8');
        $permiteMascotas = in_array($valor, ['si', 'sí', '1', 'true', 'permitidas'], true);
        // Si tu app sólo guarda "Sí" / "No", con esto basta:
        // $permiteMascotas = ($valor === 'sí' || $valor === 'si');

        $clausulaProhibido = <<<'TXT'
        "EL ARRENDATARIO" TIENE PROHIBIDO TENER MASCOTAS DENTRO DEL INMUEBLE ARRENDADO, EN CASO DE INCUMPLIMIENTO, "EL ARRENDADOR" PODRÁ RESCINDIR EL CONTRATO DE MANERA INMEDIATA, SIN NECESIDAD DE REQUERIMIENTO PREVIO, CONSIDERÁNDOSE DICHO INCUMPLIMIENTO COMO CAUSA JUSTIFICADA PARA DAR POR TERMINADO EL CONTRATO DE ARRENDAMIENTO.
        TXT;

        $clausulaPermitido = <<<'TXT'
        "EL ARRENDADOR" OTORGA PERMISO EXPRESO A "EL ARRENDATARIO" PARA TENER UNA MASCOTA, "EL ARRENDATARIO" DEBERÁ CUMPLIR CON LAS NORMATIVAS DEL DESARROLLO, NO CAUSAR MOLESTIAS A TERCEROS NI DAÑOS A LA PROPIEDAD, MANTENER LA LIMPIEZA DEL INMUEBLE Y REPARAR CUALQUIER DAÑO OCASIONADO POR LA MASCOTA. ASIMISMO, DEBERÁ CUMPLIR CON LAS DISPOSICIONES LEGALES Y REGLAMENTARIAS APLICABLES.
        TXT;

        $mascotasTexto = $permiteMascotas ? $clausulaPermitido : $clausulaProhibido;

        $set('ARRENDADOR',        $mayus($arrendador));
        $set('ARRENDATARIO',      $mayus($inquilino));
        $set('FIADOR',            $mayus($fiador !== '' ? $fiador : 'N/A'));
        $set('OBLIGADO SOLIDARIO', $mayus($obligadoSolidario));
        $set('INMUEBLE',          $mayus($poliza['direccion_inmueble'] ?? ''));
        $set('ESTACIONAMIENTO',   $textoCajones($poliza['estacionamiento_inmueble'] ?? 0));

        $set('TIPO_ID_ARRENDADOR', $normalizarTipoIdentificacion($poliza['tipo_id_arrendador'] ?? ''));
        $set('NUM_ID_ARRENDADOR',  $mayus($poliza['num_id_arrendador'] ?? ''));
        $set('DIRECCION_ARRENDADOR', $mayus($poliza['direccion_arrendador'] ?? ''));
        $set('NACIONALIDAD_ARRENDADOR', $mayus(trim((string)($poliza['nacionalidad_arrendador'] ?? ''))));

        $set('TIPO_ID_ARRENDATARIO', $normalizarTipoIdentificacion($poliza['tipo_id_inquilino'] ?? ''));
        $set('NUM_ID_ARRENDATARIO', $mayus($poliza['num_id_inquilino'] ?? ''));
        $set('NACIONALIDAD_ARRENDATARIO', $mayus(trim((string)($poliza['nacionalidad_inquilino'] ?? ''))));

        $set('TIPO_ID_OBLIGADO', $normalizarTipoIdentificacion($poliza['tipo_id_obligado'] ?? ''));
        $set('NUM_ID_OBLIGADO',  $mayus($poliza['num_id_obligado'] ?? ''));
        $set('NACIONALIDAD_OBLIGADO', $mayus(trim((string)($poliza['nacionalidad_obligado'] ?? ''))));

        $set('TIPO_ID_FIADOR', $normalizarTipoIdentificacion($poliza['tipo_id_fiador'] ?? ''));
        $set('NUM_ID_FIADOR',  $mayus($poliza['num_id_fiador'] ?? ''));
        $set('NACIONALIDAD_FIADOR', $mayus(trim((string)($poliza['nacionalidad_fiador'] ?? ''))));
        $set('DIRECCION_FIADOR', $mayus($poliza['direccion_fiador'] ?? ''));

        $set('monto_renta',         $montoEnNumeroYTexto((float)($poliza['monto_renta'] ?? 0)));
        $set('monto_mantenimiento', $montoEnNumeroYTexto((float)($poliza['monto_mantenimiento'] ?? 0)));
        $set('MASCOTAS',            $mayus($mascotasTexto));
        $set('CLAUSULA_MTTO', $mayus($clausulaMtto));

        if ($tipoContratoKey === 'fiador_pf') {
            $dirPartes = [];
            $append = function (string $campo) use (&$dirPartes, $poliza): void {
                $valor = trim((string)($poliza[$campo] ?? ''));
                if ($valor !== '') {
                    if ($campo === 'fiador_cp_garantia') {
                        $valor = 'C.P. ' . $valor;
                    }
                    $dirPartes[] = $valor;
                }
            };

            $append('fiador_calle_garantia');
            $append('fiador_num_ext_garantia');
            $append('fiador_num_int_garantia');
            $append('fiador_colonia_garantia');
            $append('fiador_alcaldia_garantia');
            $append('fiador_estado_garantia');
            $append('fiador_cp_garantia');

            $dirGarantia = $dirPartes !== []
                ? mb_strtoupper(implode(' ', $dirPartes), 'UTF-8')
                : 'N/A';

            $normaliza = function ($campo) use ($poliza, $mayus): string {
                $keys = is_array($campo) ? $campo : [$campo];

                foreach ($keys as $key) {
                    $valor = trim((string)($poliza[$key] ?? ''));
                    if ($valor !== '') {
                        return $mayus($valor);
                    }
                }

                return 'N/A';
            };

            $fechaEscritura = 'N/A';
            $fechaEscrituraRaw = '';

            if (array_key_exists('fiador_fecha_escritura', $poliza)) {
                $fechaEscrituraRaw = trim((string)($poliza['fiador_fecha_escritura'] ?? ''));
            }

            if ($fechaEscrituraRaw === '' && array_key_exists('fecha_escritura', $poliza)) {
                $fechaEscrituraRaw = trim((string)($poliza['fecha_escritura'] ?? ''));
            }

            if ($fechaEscrituraRaw !== '') {
                $fechaObj = date_create($fechaEscrituraRaw);
                if ($fechaObj instanceof DateTime) {
                    $formatterMes = new IntlDateFormatter(
                        'es_MX',
                        IntlDateFormatter::FULL,
                        IntlDateFormatter::NONE,
                        'America/Mexico_City',
                        IntlDateFormatter::GREGORIAN,
                        'MMMM'
                    );

                    $mesFormateado = $formatterMes->format($fechaObj);

                    if ($mesFormateado !== false) {
                        $fechaFormateada = sprintf(
                            '%s DE %s DE %s',
                            $fechaObj->format('d'),
                            $mesFormateado,
                            $fechaObj->format('Y')
                        );
                        $fechaEscritura = $mayus($fechaFormateada);
                    } else {
                        $fechaEscritura = $mayus($fechaObj->format('d/m/Y'));
                    }
                } else {
                    $fechaEscritura = $mayus($fechaEscrituraRaw);
                }
            }

            $set('DIR_GARANTIA', $dirGarantia);
            $set('ESCRITURA', $normaliza('fiador_numero_escritura'));
            $set('FECHA_ESCRITURA', $fechaEscritura);
            $set('NOMBRE_NOTARIO', $normaliza(['fiador_nombre_notario', 'nombre_notario']));
            $set('NUM_NOTARIO', $normaliza('fiador_numero_notario'));
            $set('CIUDAD_NOTARIO', $normaliza('fiador_estado_notario'));
            $set('FOLIO_REAL', $normaliza('fiador_folio_real'));
        }

        // Vigencia
        $fechaInicio   = $poliza['fecha_poliza'] ?? date('Y-m-d');
        $vigenciaTexto = $poliza['vigencia'] ?: (date('d/m/Y', strtotime($fechaInicio)) . ' al ' . date('d/m/Y', strtotime('+1 year -1 day', strtotime($fechaInicio))));
        $set('VIGENCIA',  $mayus($vigenciaTexto));
        $set('DIA_PAGO',  date('d', strtotime($fechaInicio)));

        // Bancarios
        $set('num_cuenta', $poliza['cuenta_arrendador'] ?? '');
        $set('banco',      $mayus($poliza['banco_arrendador'] ?? ''));
        $set('clabe',      $poliza['clabe_arrendador'] ?? '');

        // Mes y fecha inicio en texto
        $fecha  = new DateTime($fechaInicio);
        $fmtMes = new IntlDateFormatter('es_MX', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'America/Mexico_City', IntlDateFormatter::GREGORIAN, 'LLLL');
        $fmtLar = new IntlDateFormatter('es_MX', IntlDateFormatter::LONG, IntlDateFormatter::NONE, 'America/Mexico_City', IntlDateFormatter::GREGORIAN, "dd 'de' MMMM 'de' yyyy");
        $set('mes_renta',    $mayus($fmtMes->format($fecha)));
        $set('fecha_inicio', $mayus($fmtLar->format($fecha)));


        // Helper: nombre de archivo seguro (ASCII) para guardar en disco
        // --- Helpers ---
        function ensureUtf8(string $s): string
        {
            // Si viniera en ISO-8859-1, conviértelo a UTF-8
            return mb_check_encoding($s, 'UTF-8') ? $s : mb_convert_encoding($s, 'UTF-8', 'ISO-8859-1');
        }
        function safeAsciiFilename(string $s, int $max = 180): string
        {
            // Quita comillas para no romper el header
            $s = str_replace(['"', "'"], '', $s);
            // Translit a ASCII y limpia caracteres raros
            $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
            $ascii = preg_replace('/[^A-Za-z0-9 _.\-]/', '', $ascii);
            $ascii = preg_replace('/\s+/', ' ', trim($ascii));
            // Asegura extensión
            if (!str_ends_with($ascii, '.docx')) $ascii .= '.docx';
            return substr($ascii, 0, $max);
        }
        function versionTag(?string $fromDate = null, string $minor = '2.0'): string
        {
            $y = $fromDate ? (new DateTime($fromDate))->format('Y') : date('Y');
            return 'v' . $y . '.' . $minor;
        }

        // --- Construcción del nombre “bonito” con acentos ---
        // Construye el nombre “bonito” en UTF-8
        $direccion = (string)($poliza['direccion_inmueble'] ?? '');
        $anio      = !empty($poliza['fecha_poliza'])
            ? (new DateTime($poliza['fecha_poliza']))->format('Y')
            : date('Y');
        $nombreUtf8  = 'Contrato ' . trim($direccion) . " v{$anio}.2.0.docx";

        // Fallback ASCII para guardar y para filename=
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nombreUtf8);
        $ascii = preg_replace('/[^A-Za-z0-9 _.\-]/', '', $ascii);
        $ascii = preg_replace('/\s+/', ' ', trim($ascii));
        $nombreAscii = $ascii !== '' ? $ascii : 'Contrato.docx';

        // Guarda solo en archivo temporal
        $tmpDocx = tempnam(sys_get_temp_dir(), 'contrato_') . '.docx';
        $template->saveAs($tmpDocx);

        // Limpia cualquier salida previa
        while (function_exists('ob_get_level') && ob_get_level() > 0) {
            ob_end_clean();
        }

        // Descarga directa
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Length: ' . filesize($tmpDocx));
        header('Content-Disposition: attachment; filename="' . $nombreAscii . '"');
        readfile($tmpDocx);

        // Limpia el archivo temporal
        @unlink($tmpDocx);
        exit;
    }

    public function eliminar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'ok'    => false,
                'error' => 'Método no permitido',
            ]);
            return;
        }

        try {
            $payload = file_get_contents('php://input') ?: '';
            $decoded = $payload !== '' ? json_decode($payload, true) : null;

            $numero = null;
            if (is_array($decoded) && isset($decoded['numero'])) {
                $numero = (int) $decoded['numero'];
            }

            if ($numero === null && isset($_POST['numero'])) {
                $numero = (int) $_POST['numero'];
            }

            if (!is_int($numero) || $numero <= 0) {
                throw new \InvalidArgumentException('Número de póliza inválido.');
            }

            $polizaModel = new PolizaModel();
            $eliminada   = $polizaModel->eliminarPorNumero($numero);

            if (!$eliminada) {
                throw new \RuntimeException('No se encontró la póliza solicitada.');
            }

            echo json_encode([
                'ok' => true,
            ]);
        } catch (\InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode([
                'ok'    => false,
                'error' => $e->getMessage(),
            ]);
        } catch (\RuntimeException $e) {
            http_response_code(404);
            echo json_encode([
                'ok'    => false,
                'error' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'ok'    => false,
                'error' => 'Ocurrió un error inesperado al eliminar la póliza.',
            ]);
        }
    }

    /* =========================
       Helpers
       ========================= */

    /** Normaliza "$3,800.00" | "3800,00" | "3800" → "3800.00" */
    private function normalizarMonto(string $valor): string
    {
        $v = trim($valor);
        if ($v === '') return '0.00';
        $v = str_replace(['$', ' '], '', $v);

        // Formato 3.800,50 → 3800.50
        if (preg_match('/^\d{1,3}(\.\d{3})+,\d{2}$/', $v)) {
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
        } else {
            // Quitar comas de miles
            $v = str_replace(',', '', $v);
        }

        if (!str_contains($v, '.')) {
            $v .= '.00';
        } else {
            [$ent, $dec] = array_pad(explode('.', $v, 2), 2, '00');
            $v = $ent . '.' . substr($dec . '00', 0, 2);
        }

        return preg_match('/^\d+(\.\d{2})$/', $v) ? $v : '0.00';
    }

    private function resolveUsuarioId(): int
    {
        $contextUser = RequestContext::user();
        if (is_array($contextUser) && !empty($contextUser['id'])) {
            return (int)$contextUser['id'];
        }

        if (isset($_SESSION['user']['id'])) {
            return (int)$_SESSION['user']['id'];
        }

        if (isset($_SESSION['user_id'])) {
            return (int)$_SESSION['user_id'];
        }

        return 1;
    }
}
