<?php

declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . '/../Models/InmueblesModel.php';
require_once __DIR__ . '/../Models/ArrendadorModel.php';
require_once __DIR__ . '/../Models/AsesorModel.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Helpers/NormalizadoHelper.php';


use App\Helpers\NormalizadoHelper;
use App\Models\InmuebleModel;
use App\Models\ArrendadorModel;
use App\Models\AsesorModel;
use App\Middleware\AuthMiddleware;

/**
 * Controlador de Inmuebles
 *
 * Funcionalidades:
 * - Listado con búsqueda y paginación
 * - Ver detalle
 * - Crear / Editar / Eliminar (JSON)
 * - Endpoints auxiliares: inmueblesPorArrendador, info
 *
 * Notas:
 * - Normaliza montos (renta, mantenimiento, depósito) a formato decimal "####.##"
 * - Maneja correctamente checkbox de estacionamiento (1/0) y mascotas (SI/NO)
 * - Respuestas JSON coherentes con Content-Type y mensajes
 */
AuthMiddleware::verificarSesion();

class InmuebleController
{
    private InmuebleModel $model;
    private ArrendadorModel $arrendadorModel;
    private AsesorModel $asesorModel;

    public function __construct()
    {
        $this->model = new InmuebleModel();
        $this->arrendadorModel = new ArrendadorModel();
        $this->asesorModel = new AsesorModel();
    }



    /**
     * Listado con búsqueda y paginación básica
     */
    public function index(): void
    {
        $porPagina = 10;
        $pagina = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $offset = ($pagina - 1) * $porPagina;

        $query = trim((string)($_GET['q'] ?? ''));

        if ($query !== '') {
            $inmuebles = $this->model->buscarPaginados($query, $porPagina, $offset);
            $totalInmuebles = (int)$this->model->contarBusqueda($query);
        } else {
            $inmuebles = $this->model->obtenerPaginados($porPagina, $offset);
            $totalInmuebles = (int)$this->model->contarTodos();
        }

        $totalPaginas = (int) ceil($totalInmuebles / $porPagina);

        $title = 'Inmuebles - AS';
        $headerTitle = 'Listado de inmuebles';
        $contentView = __DIR__ . '/../Views/inmuebles/index.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Ver detalle de un inmueble
     */

    public function ver(string $pk, ?string $sk = null): void
    {
        $pkDecodificado = rawurldecode($pk);
        $skDecodificado = $sk !== null ? rawurldecode($sk) : null;

        try {
            $inmueble = $this->model->obtenerPorId($pkDecodificado, $skDecodificado);
        } catch (InvalidArgumentException $e) {
            $inmueble = null;
        }

        if (!$inmueble) {
            http_response_code(404);
            $title = 'No encontrado';
            $headerTitle = 'Recurso no encontrado';
            $contentView = __DIR__ . '/../Views/404.php';
            include __DIR__ . '/../Views/layouts/main.php';
            return;
        }

        $title = 'Detalle de inmueble';
        $headerTitle = 'Detalle de inmueble';
        $contentView = __DIR__ . '/../Views/inmuebles/detalle.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Formulario de creación
     */
    public function crear(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
            return;
        }

        try {
            $data = $this->buildDynamoInmueblePayload();

            $ok = $this->model->crear($data);
            echo json_encode(['ok' => (bool) $ok]);
        } catch (\InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'ok'    => false,
                'error' => 'No se pudo guardar el inmueble',
                'detalle' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 🚩 ATENCIÓN:
     * Esta función ya fue actualizada a DynamoDB (migración completa).
     * NO volver a modificar para MySQL.
     */
    public function guardarAjax(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $pk = $_POST['pk'] ?? null;

            // Aplicamos helper lower a todos los strings
            $calle    = NormalizadoHelper::lower(trim($_POST['calle'] ?? ''));
            $numExt   = NormalizadoHelper::lower(trim($_POST['num_exterior'] ?? ''));
            $numInt   = NormalizadoHelper::lower(trim($_POST['num_interior'] ?? ''));
            $colonia  = NormalizadoHelper::lower(trim($_POST['colonia'] ?? ''));
            $alcaldia = NormalizadoHelper::lower(trim($_POST['alcaldia'] ?? ''));
            $ciudad   = NormalizadoHelper::lower(trim($_POST['ciudad'] ?? ''));
            $cp       = NormalizadoHelper::lower(trim($_POST['codigo_postal'] ?? ''));

            $direccionInmueble = sprintf(
                "%s %s%s, col. %s, %s, %s, cp %s",
                $calle,
                $numExt,
                $numInt ? " int. $numInt" : "",
                $colonia,
                $alcaldia,
                $ciudad,
                $cp
            );

            $tipo               = NormalizadoHelper::lower(trim($_POST['tipo'] ?? ''));
            $renta              = NormalizadoHelper::lower(trim($_POST['renta'] ?? ''));
            $mantenimiento      = NormalizadoHelper::lower(trim($_POST['mantenimiento'] ?? ''));
            $montoMantenimiento = NormalizadoHelper::lower(trim($_POST['monto_mantenimiento'] ?? ''));
            $deposito           = NormalizadoHelper::lower(trim($_POST['deposito'] ?? ''));
            $estacionamiento    = isset($_POST['estacionamiento']) ? (int) $_POST['estacionamiento'] : 0;
            $mascotas           = NormalizadoHelper::lower(trim($_POST['mascotas'] ?? ''));
            $comentarios        = NormalizadoHelper::lower(trim($_POST['comentarios'] ?? ''));

            if (!$pk || !$direccionInmueble || !$tipo || !$renta) {
                echo json_encode(['ok' => false, 'error' => 'Campos obligatorios faltantes']);
                return;
            }

            $ok = $this->model->crear([
                'pk'                  => $pk,
                'direccion_inmueble'  => $direccionInmueble,
                'tipo'                => $tipo,
                'renta'               => $renta,
                'mantenimiento'       => $mantenimiento,
                'monto_mantenimiento' => $montoMantenimiento,
                'deposito'            => $deposito,
                'estacionamiento'     => $estacionamiento,
                'mascotas'            => $mascotas,
                'comentarios'         => $comentarios,
            ]);

            echo json_encode(['ok' => $ok]);
        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Formulario de edición
     */
    public function editar(string $pk, ?string $sk = null): void
    {
        $pkDecodificado = rawurldecode($pk);
        $skDecodificado = $sk !== null ? rawurldecode($sk) : null;

        try {
            $inmueble = $this->model->obtenerPorId($pkDecodificado, $skDecodificado);
        } catch (InvalidArgumentException $e) {
            $inmueble = null;
        }

        if (!$inmueble) {
            header('Location: ' . getBaseUrl() . '/inmuebles');
            exit;
        }

        $arrendadores = $this->arrendadorModel->obtenerTodos();
        $asesores = $this->asesorModel->all();
        $editMode = true;

        $title = 'Editar inmueble';
        $headerTitle = 'Editar inmueble';
        $contentView = __DIR__ . '/../Views/inmuebles/form.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Crear (JSON)
     */
    public function store(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
            return;
        }

        try {
            $data = $this->buildDynamoInmueblePayload();

            $ok = $this->model->crear($data);
            echo json_encode(['ok' => (bool)$ok]);
        } catch (\InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'mensaje' => 'Error al crear inmueble', 'error' => $e->getMessage()]);
        }
    }

    /**
     * Actualizar (JSON)
     */
    public function update(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
            return;
        }

        try {
            $data = $this->buildDynamoInmueblePayload(true);
            $pk = $data['pk'];
            $sk = $data['sk'];
            unset($data['sk']);

            $ok = $this->model->actualizarPorPkSk($pk, $sk, $data);
            echo json_encode(['ok' => (bool)$ok]);
        } catch (\InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'mensaje' => 'Error al actualizar inmueble', 'error' => $e->getMessage()]);
        }
    }

    /**
     * Eliminar inmueble (JSON, Dynamo)
     */
    public function delete(?string $pkRoute = null, ?string $skRoute = null): void
    {

        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
            return;
        }


        $pk = trim((string)($_POST['pk'] ?? ''));
        $sk = trim((string)($_POST['sk'] ?? ''));


        if (!$pk || !$sk) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Parámetros inválidos']);
            return;
        }

        try {
            $ok = $this->model->eliminar($pk, $sk);
            echo json_encode(['ok' => $ok]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'ok'    => false,
                'error' => 'Error al eliminar inmueble: ' . $e->getMessage()
            ]);
        }
    }



    /**
     * Devuelve inmuebles por arrendador (JSON)
     */
    public function inmueblesPorArrendador(string $identificador): void
    {
        header('Content-Type: application/json');

        $identificador = trim(rawurldecode($identificador));

        if ($identificador === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'mensaje' => 'Identificador de arrendador inválido']);
            return;
        }

        $idOClave = ctype_digit($identificador)
            ? (int) $identificador
            : $identificador;

        try {
            $inmuebles = $this->model->obtenerPorArrendador($idOClave);
            echo json_encode($inmuebles ?? []);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'mensaje' => 'Error al consultar inmuebles', 'error' => $e->getMessage()]);
        }
    }

    /**
     * Devuelve la información de un inmueble específico en formato JSON
     */
    public function info(string $pk, ?string $sk = null): void
    {
        header('Content-Type: application/json');

        try {
            $inmueble = $this->model->obtenerPorId(rawurldecode($pk), $sk !== null ? rawurldecode($sk) : null);
            echo json_encode($inmueble ?: []);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'mensaje' => 'Error al consultar inmueble', 'error' => $e->getMessage()]);
        }
    }

    // =========================
    // Métodos auxiliares
    // =========================

    /**
     * Construye el arreglo $data para crear/actualizar inmuebles, saneando y normalizando.
     *
     * @param bool $isUpdate Si es true, procesa 'estacionamiento' aceptando 0/1 directos además de checkbox
     * @return array<string, string|int>
     */
    private function buildDynamoInmueblePayload(bool $isUpdate = false): array
    {
        $pkInput = trim((string)($_POST['pk'] ?? $_POST['arrendador_pk'] ?? $_POST['id_arrendador'] ?? ''));
        if ($pkInput === '') {
            throw new \InvalidArgumentException('Debe seleccionar un arrendador');
        }

        if (ctype_digit($pkInput)) {
            $pkInput = 'arr#' . $pkInput;
        }

        $pk = NormalizadoHelper::lower($pkInput);
        if ($pk === '') {
            throw new \InvalidArgumentException('Identificador de arrendador inválido');
        }

        $sk = '';
        if ($isUpdate) {
            $skInput = trim((string)($_POST['sk'] ?? ''));
            if ($skInput === '') {
                throw new \InvalidArgumentException('Identificador del inmueble requerido');
            }
            $sk = NormalizadoHelper::lower($skInput);
        }

        $direccion = NormalizadoHelper::lower(trim((string)($_POST['direccion_inmueble'] ?? '')));
        $tipo = NormalizadoHelper::lower(trim((string)($_POST['tipo'] ?? '')));
        $rentaRaw = (string)($_POST['renta'] ?? '');
        $renta = $this->normalizarMonto($rentaRaw);

        if ($direccion === '' || $tipo === '' || trim($rentaRaw) === '') {
            throw new \InvalidArgumentException('Faltan datos obligatorios del inmueble');
        }

        $mantenimientoRaw = NormalizadoHelper::lower(trim((string)($_POST['mantenimiento'] ?? 'no')));
        $mantenimiento = $mantenimientoRaw === 'si' ? 'si' : 'no';

        $montoMantenimiento = $this->normalizarMonto((string)($_POST['monto_mantenimiento'] ?? '0'));
        $deposito = $this->normalizarMonto((string)($_POST['deposito'] ?? '0'));

        $estacionamientoVal = $_POST['estacionamiento'] ?? 0;
        if (is_array($estacionamientoVal)) {
            $estacionamientoVal = reset($estacionamientoVal);
        }
        $estacionamiento = max(0, (int)$estacionamientoVal);

        $mascotasRaw = strtoupper(trim((string)($_POST['mascotas'] ?? 'NO')));
        $mascotas = $mascotasRaw === 'SI' ? 'SI' : 'NO';

        $comentarios = NormalizadoHelper::lower(trim((string)($_POST['comentarios'] ?? '')));

        $asesorInput = trim((string)($_POST['asesor_pk'] ?? ($_POST['id_asesor'] ?? '')));
        $asesor = '';
        if ($asesorInput !== '') {
            if (ctype_digit($asesorInput)) {
                $asesorInput = 'ase#' . $asesorInput;
            }
            $asesor = NormalizadoHelper::lower($asesorInput);
        }

        if ($asesor === '' && $pk !== '') {
            $profile = $this->arrendadorModel->obtenerProfilePorPk($pk);
            if ($profile && !empty($profile['asesor'])) {
                $asesor = NormalizadoHelper::lower((string)$profile['asesor']);
            }
        }

        $data = [
            'pk'                  => $pk,
            'tipo'                => $tipo,
            'direccion_inmueble'  => $direccion,
            'renta'               => $renta,
            'mantenimiento'       => $mantenimiento,
            'monto_mantenimiento' => $montoMantenimiento,
            'deposito'            => $deposito,
            'estacionamiento'     => $estacionamiento,
            'mascotas'            => $mascotas,
            'comentarios'         => $comentarios,
        ];

        if ($asesor !== '') {
            $data['asesor'] = $asesor;
        }

        if ($isUpdate) {
            $data['sk'] = $sk;
        }

        return $data;
    }

    /**
     * Convierte montos de "$3,800.00" | "3,800" | "3800,00" → "3800.00"
     */
    private function normalizarMonto(string $valor): string
    {
        $v = trim($valor);
        if ($v === '') return '0.00';

        // Quitar símbolo de moneda y espacios
        $v = str_replace(['$', ' '], '', $v);

        // Si trae separadores miles (,) y punto decimal, limpiamos miles y dejamos punto
        // También soporta formatos tipo "3.800,50" → "3800.50"
        if (preg_match('/^\d{1,3}(\.\d{3})+,\d{2}$/', $v)) {
            // Formato europeo: miles con punto, decimales con coma
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
        } else {
            // Quitar comas de miles
            $v = str_replace(',', '', $v);
        }

        // Si termina sin decimales, agregamos .00
        if (!str_contains($v, '.')) {
            $v .= '.00';
        } else {
            // Normalizar a 2 decimales
            $parts = explode('.', $v, 2);
            $dec = substr($parts[1] . '00', 0, 2);
            $v = $parts[0] . '.' . $dec;
        }

        // Asegurar que sólo queden dígitos y un punto
        if (!preg_match('/^\d+(\.\d{2})$/', $v)) {
            // Fallback seguro
            return '0.00';
        }

        return $v;
    }
}
