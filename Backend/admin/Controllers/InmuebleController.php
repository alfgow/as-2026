<?php

declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . '/../Models/InmueblesModel.php';
require_once __DIR__ . '/../Models/ArrendadorModel.php';
require_once __DIR__ . '/../Models/AsesorModel.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
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
     * Vista principal con buscador de inmuebles.
     */
    public function index(): void
    {
        $query = trim((string) ($_GET['q'] ?? ''));
        $resultado = ['items' => [], 'consumedCapacity' => 0.0];

        if ($query !== '') {
            $resultado = $this->model->buscarPorDireccion($query);
        }

        $inmuebles = $resultado['items'] ?? [];
        $rcuUsed = (float) ($resultado['consumedCapacity'] ?? 0.0);

        $title = 'Inmuebles - AS';
        $headerTitle = 'Inmuebles';
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
        } catch (\InvalidArgumentException $e) {
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
            $data = $this->buildInmueblePayload();

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

    public function guardarAjax(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $pk = isset($_POST['pk']) ? (string) $_POST['pk'] : '';

            $calle    = trim((string)($_POST['calle'] ?? ''));
            $numExt   = trim((string)($_POST['num_exterior'] ?? ''));
            $numInt   = trim((string)($_POST['num_interior'] ?? ''));
            $colonia  = trim((string)($_POST['colonia'] ?? ''));
            $alcaldia = trim((string)($_POST['alcaldia'] ?? ''));
            $ciudad   = trim((string)($_POST['ciudad'] ?? ''));
            $cp       = trim((string)($_POST['codigo_postal'] ?? ''));

            $direccionInmueble = sprintf(
                '%s %s%s, col. %s, %s, %s, cp %s',
                $calle,
                $numExt,
                $numInt !== '' ? ' int. ' . $numInt : '',
                $colonia,
                $alcaldia,
                $ciudad,
                $cp
            );

            $input = [
                'pk'                  => $pk,
                'direccion_inmueble'  => $direccionInmueble,
                'tipo'                => (string)($_POST['tipo'] ?? ''),
                'renta'               => (string)($_POST['renta'] ?? ''),
                'mantenimiento'       => (string)($_POST['mantenimiento'] ?? ''),
                'monto_mantenimiento' => (string)($_POST['monto_mantenimiento'] ?? ''),
                'deposito'            => (string)($_POST['deposito'] ?? ''),
                'estacionamiento'     => $_POST['estacionamiento'] ?? 0,
                'mascotas'            => (string)($_POST['mascotas'] ?? ''),
                'comentarios'         => (string)($_POST['comentarios'] ?? ''),
                'asesor_pk'           => $_POST['asesor_pk'] ?? ($_POST['id_asesor'] ?? ''),
            ];

            $payload = $this->prepareInmueblePayload($input);

            $ok = $this->model->crear($payload);
            echo json_encode(['ok' => (bool)$ok]);
        } catch (\InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            http_response_code(500);
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
        } catch (\InvalidArgumentException $e) {
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
            $data = $this->buildInmueblePayload();

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
            $data = $this->buildInmueblePayload(true);
            $id = (int) $data['id'];
            unset($data['id']);

            $ok = $this->model->actualizarPorId($id, $data);
            echo json_encode([
                'ok' => (bool) $ok,
                'id' => $id,
            ]);
        } catch (\InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'mensaje' => 'Error al actualizar inmueble', 'error' => $e->getMessage()]);
        }
    }

    /**
     * Eliminar inmueble (JSON)
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
        $id = trim((string)($_POST['id'] ?? ''));

        if (($pk === '' || $sk === '') && $id !== '') {
            $inmueble = $this->model->obtenerPorId($id);
            if ($inmueble) {
                $pk = $pk !== '' ? $pk : (string)($inmueble['pk'] ?? '');
                $sk = $sk !== '' ? $sk : (string)($inmueble['sk'] ?? '');
            }
        }

        if ($pk === '' || $sk === '') {
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
     * Construye el arreglo $data para crear/actualizar inmuebles a partir de los datos recibidos.
     *
     * @param bool $isUpdate
     * @return array<string, mixed>
     */
    private function buildInmueblePayload(bool $isUpdate = false): array
    {
        return $this->prepareInmueblePayload($_POST, $isUpdate);
    }

    private function normalizarMantenimiento(string $valor): string
    {
        $normalizado = strtoupper(str_replace(' ', '_', trim($valor)));

        switch ($normalizado) {
            case 'SI':
                return 'Si';
            case 'NO':
                return 'No';
            case 'NO_APLICA':
            case 'NA':
                return 'na';
            default:
                return 'No';
        }
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function prepareInmueblePayload(array $input, bool $isUpdate = false): array
    {
        $pkRaw = $input['pk'] ?? ($input['arrendador_pk'] ?? ($input['id_arrendador'] ?? ''));
        if (is_array($pkRaw)) {
            $pkRaw = reset($pkRaw);
        }
        $pkInput = trim((string) $pkRaw);

        if ($pkInput === '') {
            throw new \InvalidArgumentException('Debe seleccionar un arrendador');
        }

        $arrendadorId = $this->parseArrendadorId($pkInput);
        if ($arrendadorId === null) {
            throw new \InvalidArgumentException('Identificador de arrendador inválido');
        }

        $inmuebleId = null;
        if ($isUpdate) {
            $skRaw = $input['sk'] ?? ($input['id'] ?? '');
            if (is_array($skRaw)) {
                $skRaw = reset($skRaw);
            }
            $skInput = trim((string) $skRaw);
            if ($skInput === '') {
                throw new \InvalidArgumentException('Identificador del inmueble requerido');
            }

            $inmuebleId = $this->parseInmuebleId($skInput);
            if ($inmuebleId === null) {
                throw new \InvalidArgumentException('Identificador del inmueble inválido');
            }
        }

        $direccion = trim((string)($input['direccion_inmueble'] ?? ''));
        $tipo      = trim((string)($input['tipo'] ?? ''));
        $rentaRaw  = (string)($input['renta'] ?? '');

        if ($direccion === '' || $tipo === '' || trim($rentaRaw) === '') {
            throw new \InvalidArgumentException('Faltan datos obligatorios del inmueble');
        }

        $mantenimiento = $this->normalizarMantenimiento((string)($input['mantenimiento'] ?? ''));

        $montoMantenimiento = $this->normalizarMonto((string)($input['monto_mantenimiento'] ?? '0'));
        $deposito           = $this->normalizarMonto((string)($input['deposito'] ?? '0'));

        $estacionamientoVal = $input['estacionamiento'] ?? 0;
        if (is_array($estacionamientoVal)) {
            $estacionamientoVal = reset($estacionamientoVal);
        }
        $estacionamiento = max(0, (int) $estacionamientoVal);

        $mascotasRaw = strtoupper(trim((string)($input['mascotas'] ?? 'NO')));
        $mascotas    = $mascotasRaw === 'SI' ? 'SI' : 'NO';

        $comentarios = trim((string)($input['comentarios'] ?? ''));

        $asesorRaw = $input['asesor_pk'] ?? ($input['id_asesor'] ?? '');
        if (is_array($asesorRaw)) {
            $asesorRaw = reset($asesorRaw);
        }
        $asesorInput = trim((string) $asesorRaw);
        $asesorId    = $this->parseAsesorId($asesorInput);

        if ($asesorId === null) {
            $profile = $this->arrendadorModel->obtenerProfilePorPk('arr#' . $arrendadorId);
            if ($profile && array_key_exists('id_asesor', $profile)) {
                $asesorId = $profile['id_asesor'] !== null
                    ? (int) $profile['id_asesor']
                    : null;
            }
        }

        $data = [
            'id_arrendador'       => $arrendadorId,
            'id_asesor'           => $asesorId,
            'direccion_inmueble'  => $direccion,
            'tipo'                => $tipo,
            'renta'               => $this->normalizarMonto($rentaRaw),
            'mantenimiento'       => $mantenimiento,
            'monto_mantenimiento' => $montoMantenimiento,
            'deposito'            => $deposito,
            'estacionamiento'     => $estacionamiento,
            'mascotas'            => $mascotas,
            'comentarios'         => $comentarios,
        ];

        if ($isUpdate && $inmuebleId !== null) {
            $data['id'] = $inmuebleId;
        }

        return $data;
    }

    private function parseArrendadorId(string $valor): ?int
    {
        $valor = trim($valor);
        if ($valor === '') {
            return null;
        }

        if (ctype_digit($valor)) {
            $id = (int) $valor;

            return $id > 0 ? $id : null;
        }

        if (preg_match('/^arr#(\d+)$/i', $valor, $matches)) {
            $id = (int) $matches[1];

            return $id > 0 ? $id : null;
        }

        return null;
    }

    private function parseAsesorId(?string $valor): ?int
    {
        if ($valor === null) {
            return null;
        }

        $valor = trim($valor);
        if ($valor === '') {
            return null;
        }

        if (ctype_digit($valor)) {
            $id = (int) $valor;

            return $id > 0 ? $id : null;
        }

        if (preg_match('/^ase#(\d+)$/i', $valor, $matches)) {
            $id = (int) $matches[1];

            return $id > 0 ? $id : null;
        }

        return null;
    }

    private function parseInmuebleId(string $valor): ?int
    {
        $valor = trim($valor);
        if ($valor === '') {
            return null;
        }

        if (ctype_digit($valor)) {
            $id = (int) $valor;

            return $id > 0 ? $id : null;
        }

        if (preg_match('/^INM#(\d+)$/i', $valor, $matches)) {
            $id = (int) $matches[1];

            return $id > 0 ? $id : null;
        }

        return null;
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
