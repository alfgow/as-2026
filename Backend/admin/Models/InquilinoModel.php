<?php

declare(strict_types=1);

namespace App\Models;

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Helpers/S3Helper.php';
require_once __DIR__ . '/../Helpers/TextHelper.php';
require_once __DIR__ . '/AsesorModel.php';

use App\Core\Database;
use App\Helpers\S3Helper;
use App\Helpers\TextHelper;
use PDO;
use RuntimeException;

class InquilinoModel extends Database
{
    private const PREFIXES = [
        'arrendatario' => 'inq',
        'inquilino'    => 'inq',
        'obligado'     => 'obl',
        'obligado solidario' => 'obl',
        'fiador'       => 'fia',
    ];

    public function __construct()
    {
        parent::__construct();
    }

    private function prefixFromTipo(?string $tipo): string
    {
        $tipo = strtolower(trim((string) $tipo));
        foreach (self::PREFIXES as $key => $prefix) {
            if ($tipo === $key) {
                return $prefix;
            }
        }
        return 'inq';
    }

    private function buildPk(int $id, ?string $tipo = null): string
    {
        return sprintf('%s#%d', $this->prefixFromTipo($tipo), $id);
    }

    private function resolveIdFromPk(?string $pk, ?int $fallbackId = null): ?int
    {
        $pk = $pk !== null ? trim($pk) : '';
        if ($pk !== '' && preg_match('/^(inq|obl|fia)#(\d+)$/i', $pk, $matches)) {
            return (int) $matches[2];
        }
        if ($fallbackId !== null && $fallbackId > 0) {
            return $fallbackId;
        }
        return null;
    }

    private function normalizePayload($payload): array
    {
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return (array) $decoded;
            }
            return ['raw' => $payload];
        }
        if (is_array($payload)) {
            return $payload;
        }
        return [];
    }

    private function encodeJson($payload): ?string
    {
        $data = $this->normalizePayload($payload);
        if ($data === []) {
            return null;
        }
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, string>   $keys
     *
     * @return array<string, mixed>
     */
    private function applyTitleCase(array $data, array $keys): array
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = TextHelper::titleCase((string) $data[$key]);
            }
        }

        return $data;
    }

    private function decodeJson($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return (array) $decoded;
            }
        }
        return [];
    }
    private function defaultDireccion(): array
    {
        return [
            'calle'         => '',
            'num_exterior'  => '',
            'num_interior'  => '',
            'colonia'       => '',
            'alcaldia'      => '',
            'ciudad'        => '',
            'codigo_postal' => '',
        ];
    }

    private function defaultTrabajo(): array
    {
        return [
            'empresa'           => '',
            'direccion_empresa' => '',
            'telefono_empresa'  => '',
            'puesto'            => '',
            'antiguedad'        => '',
            'sueldo'            => '',
            'otrosingresos'     => '',
            'nombre_jefe'       => '',
            'tel_jefe'          => '',
            'web_empresa'       => '',
        ];
    }

    private function defaultFiador(): array
    {
        return [
            'calle_inmueble'    => '',
            'num_ext_inmueble'  => '',
            'num_int_inmueble'  => '',
            'colonia_inmueble'  => '',
            'alcaldia_inmueble' => '',
            'estado_inmueble'   => '',
            'cp_inmueble'       => '',
            'numero_escritura'  => '',
            'fecha_escritura'   => '',
            'numero_notario'    => '',
            'nombre_notario'    => '',
            'estado_notario'    => '',
            'folio_real'        => '',
            's3_key'            => '',
        ];
    }

    private function defaultHistorial(): array
    {
        return [
            'renta_actualmente'        => '',
            'arrendador_actual'        => '',
            'cel_arrendador_actual'    => '',
            'monto_renta_actual'       => '',
            'tiempo_habitacion_actual' => '',
            'motivo_arrendamiento'     => '',
            'vive_actualmente'         => '',
        ];
    }

    private function mapProfileRow(array $row): array
    {
        $id   = (int) ($row['id'] ?? 0);
        $tipo = (string) ($row['tipo'] ?? 'inquilino');
        $nombre = trim(sprintf(
            '%s %s %s',
            (string) ($row['nombre_inquilino'] ?? ''),
            (string) ($row['apellidop_inquilino'] ?? ''),
            (string) ($row['apellidom_inquilino'] ?? '')
        ));

        return [
            'id'                   => $id,
            'pk'                   => $id > 0 ? $this->buildPk($id, $tipo) : '',
            'tipo'                 => $tipo,
            'nombre_inquilino'     => (string) ($row['nombre_inquilino'] ?? ''),
            'apellidop_inquilino'  => (string) ($row['apellidop_inquilino'] ?? ''),
            'apellidom_inquilino'  => (string) ($row['apellidom_inquilino'] ?? ''),
            'representante'        => (string) ($row['representante'] ?? ''),
            'estadocivil'          => (string) ($row['estadocivil'] ?? ''),
            'rfc'                  => (string) ($row['rfc'] ?? ''),
            'curp'                 => (string) ($row['curp'] ?? ''),
            'email'                => (string) ($row['email'] ?? ''),
            'celular'              => (string) ($row['celular'] ?? ''),
            'nacionalidad'         => (string) ($row['nacionalidad'] ?? ''),
            'tipo_id'              => (string) ($row['tipo_id'] ?? ''),
            'num_id'               => (string) ($row['num_id'] ?? ''),
            'fecha'                => $row['fecha'] ?? null,
            'conyuge'              => (string) ($row['conyuge'] ?? ''),
            'device_id'            => (string) ($row['device_id'] ?? ''),
            'ip'                   => (string) ($row['ip'] ?? ''),
            'status'               => (string) ($row['status'] ?? ''),
            'slug'                 => (string) ($row['slug'] ?? ''),
            'updated_at'           => $row['updated_at'] ?? null,
            'nombre'               => $nombre,
            'asesor_id'            => isset($row['id_asesor']) ? (int) $row['id_asesor'] : null,
        ];
    }

    private function buildFullProfile(array $row): array
    {
        $profile = $this->mapProfileRow($row);
        $id      = $profile['id'];

        $profile['direccion'] = $this->fetchDireccion($id);
        $profile['trabajo']   = $this->fetchTrabajo($id);
        $profile['fiador']    = $this->fetchFiador($id);

        $historialVivienda = $this->fetchHistorial($id);
        $profile['historial'] = $historialVivienda;
        $profile['historial_vivienda'] = $historialVivienda;

        return $profile;
    }
    private function fetchDireccion(int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM inquilinos_direccion WHERE id_inquilino = :id ORDER BY id DESC LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return $this->defaultDireccion();
        }

        return [
            'calle'         => (string) ($row['calle'] ?? ''),
            'num_exterior'  => (string) ($row['num_exterior'] ?? ''),
            'num_interior'  => (string) ($row['num_interior'] ?? ''),
            'colonia'       => (string) ($row['colonia'] ?? ''),
            'alcaldia'      => (string) ($row['alcaldia'] ?? ''),
            'ciudad'        => (string) ($row['ciudad'] ?? ''),
            'codigo_postal' => (string) ($row['codigo_postal'] ?? ''),
        ];
    }

    private function fetchTrabajo(int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM inquilinos_trabajo WHERE id_inquilino = :id ORDER BY id DESC LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return $this->defaultTrabajo();
        }

        return [
            'empresa'           => (string) ($row['empresa'] ?? ''),
            'direccion_empresa' => (string) ($row['direccion_empresa'] ?? ''),
            'telefono_empresa'  => (string) ($row['telefono_empresa'] ?? ''),
            'puesto'            => (string) ($row['puesto'] ?? ''),
            'antiguedad'        => (string) ($row['antiguedad'] ?? ''),
            'sueldo'            => (string) ($row['sueldo'] ?? ''),
            'otrosingresos'     => (string) ($row['otrosingresos'] ?? ''),
            'nombre_jefe'       => (string) ($row['nombre_jefe'] ?? ''),
            'tel_jefe'          => (string) ($row['tel_jefe'] ?? ''),
            'web_empresa'       => (string) ($row['web_empresa'] ?? ''),
        ];
    }

    private function fetchFiador(int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM inquilinos_fiador WHERE id_inquilino = :id ORDER BY id DESC LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return $this->defaultFiador();
        }

        return [
            'calle_inmueble'    => (string) ($row['calle_inmueble'] ?? ''),
            'num_ext_inmueble'  => (string) ($row['num_ext_inmueble'] ?? ''),
            'num_int_inmueble'  => (string) ($row['num_int_inmueble'] ?? ''),
            'colonia_inmueble'  => (string) ($row['colonia_inmueble'] ?? ''),
            'alcaldia_inmueble' => (string) ($row['alcaldia_inmueble'] ?? ''),
            'estado_inmueble'   => (string) ($row['estado_inmueble'] ?? ''),
            'cp_inmueble'       => (string) ($row['cp_inmueble'] ?? ''),
            'numero_escritura'  => (string) ($row['numero_escritura'] ?? ''),
            'fecha_escritura'   => (string) ($row['fecha_escritura'] ?? ''),
            'numero_notario'    => (string) ($row['numero_notario'] ?? ''),
            'nombre_notario'    => (string) ($row['nombre_notario'] ?? ''),
            'estado_notario'    => (string) ($row['estado_notario'] ?? ''),
            'folio_real'        => (string) ($row['folio_real'] ?? ''),
            's3_key'            => (string) ($row['s3_key'] ?? ''),
        ];
    }

    private function fetchHistorial(int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM inquilinos_historial_vivienda WHERE id_inquilino = :id ORDER BY id DESC LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return $this->defaultHistorial();
        }

        return [
            'renta_actualmente'        => (string) ($row['renta_actualmente'] ?? ''),
            'arrendador_actual'        => (string) ($row['arrendador_actual'] ?? ''),
            'cel_arrendador_actual'    => (string) ($row['cel_arrendador_actual'] ?? ''),
            'monto_renta_actual'       => (string) ($row['monto_renta_actual'] ?? ''),
            'tiempo_habitacion_actual' => (string) ($row['tiempo_habitacion_actual'] ?? ''),
            'motivo_arrendamiento'     => (string) ($row['motivo_arrendamiento'] ?? ''),
            'vive_actualmente'         => (string) ($row['vive_actualmente'] ?? ''),
        ];
    }

    private function extraerSelfieUrl(array $archivos): ?string
    {
        foreach ($archivos as $archivo) {
            if (($archivo['tipo'] ?? '') === 'selfie') {
                return $archivo['url'] ?? ($archivo['s3_key'] ?? null);
            }
        }
        return null;
    }

    private function obtenerPolizas(int $idInquilino): array
    {
        $stmt = $this->db->prepare('SELECT * FROM polizas WHERE id_inquilino = :id ORDER BY fecha_poliza DESC, id_poliza DESC');
        $stmt->execute([':id' => $idInquilino]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $rows ?: [];
    }

    private function ensureValidacionesRow(int $idInquilino): void
    {
        $stmt = $this->db->prepare('SELECT id FROM inquilinos_validaciones WHERE id_inquilino = :id LIMIT 1');
        $stmt->execute([':id' => $idInquilino]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            return;
        }

        $insert = $this->db->prepare('INSERT INTO inquilinos_validaciones (id_inquilino) VALUES (:id)');
        $insert->execute([':id' => $idInquilino]);
    }

    private function mapValidacionesRow(array $row): array
    {
        return [
            'documentos' => [
                'proceso' => (int) ($row['proceso_validacion_documentos'] ?? 2),
                'resumen' => $row['validacion_documentos_resumen'] ?? null,
                'json'    => $this->decodeJson($row['validacion_documentos_json'] ?? null),
            ],
            'archivos' => [
                'proceso' => (int) ($row['proceso_validacion_archivos'] ?? 2),
                'resumen' => $row['validacion_archivos_resumen'] ?? null,
                'json'    => $this->decodeJson($row['validacion_archivos_json'] ?? null),
            ],
            'rostro' => [
                'proceso' => (int) ($row['proceso_validacion_rostro'] ?? 2),
                'resumen' => $row['validacion_rostro_resumen'] ?? null,
                'json'    => $this->decodeJson($row['validacion_rostro_json'] ?? null),
            ],
            'identidad' => [
                'proceso' => (int) ($row['proceso_validacion_id'] ?? 2),
                'resumen' => $row['validacion_id_resumen'] ?? null,
                'json'    => $this->decodeJson($row['validacion_id_json'] ?? null),
            ],
            'ingresos' => [
                'proceso' => (int) ($row['proceso_validacion_ingresos'] ?? 2),
                'resumen' => $row['validacion_ingresos_resumen'] ?? null,
                'json'    => $this->decodeJson($row['validacion_ingresos_json'] ?? null),
            ],
            'pago_inicial' => [
                'proceso' => (int) ($row['proceso_pago_inicial'] ?? 2),
                'resumen' => $row['pago_inicial_resumen'] ?? null,
                'json'    => $this->decodeJson($row['pago_inicial_json'] ?? null),
            ],
            'demandas' => [
                'proceso' => (int) ($row['proceso_inv_demandas'] ?? 2),
                'resumen' => $row['inv_demandas_resumen'] ?? null,
                'json'    => $this->decodeJson($row['inv_demandas_json'] ?? null),
            ],
            'verificamex' => [
                'proceso' => (int) ($row['proceso_validacion_verificamex'] ?? 2),
                'resumen' => $row['verificamex_resumen'] ?? null,
                'json'    => $this->decodeJson($row['verificamex_json'] ?? null),
            ],
        ];
    }

    private function saveValidation(int $idInquilino, string $tipo, int $proceso, $payload, ?string $resumen = null): bool
    {
        $idInquilino = max(0, $idInquilino);
        if ($idInquilino <= 0) {
            return false;
        }

        $map = [
            'documentos'   => ['proceso_validacion_documentos', 'validacion_documentos_resumen', 'validacion_documentos_json'],
            'archivos'     => ['proceso_validacion_archivos', 'validacion_archivos_resumen', 'validacion_archivos_json'],
            'rostro'       => ['proceso_validacion_rostro', 'validacion_rostro_resumen', 'validacion_rostro_json'],
            'identidad'    => ['proceso_validacion_id', 'validacion_id_resumen', 'validacion_id_json'],
            'ingresos'     => ['proceso_validacion_ingresos', 'validacion_ingresos_resumen', 'validacion_ingresos_json'],
            'pago_inicial' => ['proceso_pago_inicial', 'pago_inicial_resumen', 'pago_inicial_json'],
            'demandas'     => ['proceso_inv_demandas', 'inv_demandas_resumen', 'inv_demandas_json'],
            'verificamex'  => ['proceso_validacion_verificamex', 'verificamex_resumen', 'verificamex_json'],
        ];

        if (!isset($map[$tipo])) {
            return false;
        }

        $this->ensureValidacionesRow($idInquilino);

        [$procesoCol, $resumenCol, $jsonCol] = $map[$tipo];
        $sql = sprintf(
            'UPDATE inquilinos_validaciones SET %s = :proceso, %s = :resumen, %s = :json, updated_at = NOW() WHERE id_inquilino = :id',
            $procesoCol,
            $resumenCol,
            $jsonCol
        );

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':proceso', $proceso, PDO::PARAM_INT);
        if ($resumen === null) {
            $stmt->bindValue(':resumen', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':resumen', $resumen, PDO::PARAM_STR);
        }

        $json = $this->encodeJson($payload);
        if ($json === null) {
            $stmt->bindValue(':json', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':json', $json, PDO::PARAM_STR);
        }

        $stmt->bindValue(':id', $idInquilino, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function getPkById(int $id): ?string
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = $this->db->prepare('SELECT tipo FROM inquilinos WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        return $this->buildPk($id, (string) ($row['tipo'] ?? ''));
    }
    public function registrarArchivo(
        int $idInquilino,
        string $tipo,
        string $s3Key,
        array $meta = [],
        ?string $forcedSuffix = null
    ): array {
        unset($forcedSuffix);

        if ($idInquilino <= 0) {
            throw new \RuntimeException('Inquilino inválido.');
        }

        $tipo = strtolower(trim($tipo));
        if ($tipo === '') {
            throw new \RuntimeException('Tipo de archivo inválido.');
        }

        $stmt = $this->db->prepare('INSERT INTO inquilinos_archivos (id_inquilino, tipo, s3_key, mime_type, size) VALUES (:id, :tipo, :s3, :mime, :size)');
        $stmt->execute([
            ':id'   => $idInquilino,
            ':tipo' => $tipo,
            ':s3'   => $s3Key,
            ':mime' => (string) ($meta['mime_type'] ?? ''),
            ':size' => (int) ($meta['size'] ?? 0),
        ]);

        $archivoId = (int) $this->db->lastInsertId();
        $registro  = $this->obtenerArchivo($idInquilino, (string) $archivoId);
        if (!$registro) {
            $registro = [
                'id'           => $archivoId,
                'id_inquilino' => $idInquilino,
                'tipo'         => $tipo,
                's3_key'       => $s3Key,
                'mime_type'    => (string) ($meta['mime_type'] ?? ''),
                'size'         => (int) ($meta['size'] ?? 0),
            ];
        }

        $registro['nombre_original'] = $meta['original_name'] ?? null;
        $registro['categoria']       = $meta['categoria'] ?? null;

        return $registro;
    }

    public function obtenerArchivo(int $idInquilino, string $archivoId): ?array
    {
        $archivoId = (int) trim($archivoId);
        if ($idInquilino <= 0 || $archivoId <= 0) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT * FROM inquilinos_archivos WHERE id = :id AND id_inquilino = :inq LIMIT 1');
        $stmt->execute([':id' => $archivoId, ':inq' => $idInquilino]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $row['id'] = (int) $row['id'];
        $row['id_inquilino'] = (int) $row['id_inquilino'];
        return $row;
    }

    public function eliminarArchivo(int $idInquilino, string $archivoId): bool
    {
        $archivoId = (int) trim($archivoId);
        if ($idInquilino <= 0 || $archivoId <= 0) {
            return false;
        }

        $stmt = $this->db->prepare('DELETE FROM inquilinos_archivos WHERE id = :id AND id_inquilino = :inq');
        $stmt->bindValue(':id', $archivoId, PDO::PARAM_INT);
        $stmt->bindValue(':inq', $idInquilino, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * @throws RuntimeException
     */
    public function eliminarInquilino(int $idInquilino): bool
    {
        if ($idInquilino <= 0) {
            throw new RuntimeException('ID de inquilino inválido.');
        }

        $numeroPoliza = $this->tienePolizasRelacionadas($idInquilino);
        if ($numeroPoliza !== null) {
            throw new RuntimeException(sprintf(
                'No se puede eliminar porque el prospecto está relacionado con la póliza %s',
                $numeroPoliza
            ));
        }

        $s3Keys = $this->recolectarClavesS3($idInquilino);

        $this->beginTransaction();

        try {
            $tablas = [
                'inquilinos_archivos',
                'inquilinos_validaciones',
                'inquilinos_historial_vivienda',
                'inquilinos_trabajo',
                'inquilinos_fiador',
                'inquilinos_direccion',
            ];

            foreach ($tablas as $tabla) {
                $stmt = $this->db->prepare("DELETE FROM {$tabla} WHERE id_inquilino = :id");
                $stmt->bindValue(':id', $idInquilino, PDO::PARAM_INT);
                $stmt->execute();
            }

            $stmtProspecto = $this->db->prepare('DELETE FROM inquilinos WHERE id = :id');
            $stmtProspecto->bindValue(':id', $idInquilino, PDO::PARAM_INT);
            $stmtProspecto->execute();

            if ($stmtProspecto->rowCount() === 0) {
                throw new RuntimeException('El prospecto no existe.');
            }

            $erroresS3 = $this->eliminarArchivosS3($s3Keys);
            if ($erroresS3 !== []) {
                throw new RuntimeException('No se pudieron eliminar algunos archivos del almacenamiento.');
            }

            $this->commit();
            return true;
        } catch (RuntimeException $e) {
            $this->rollBack();
            throw $e;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw new RuntimeException('No se pudo eliminar al prospecto. Inténtalo más tarde.', 0, $e);
        }
    }

    public function searchByTerm(string $q): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }

        $needle = '%' . mb_strtolower($q, 'UTF-8') . '%';
        $searchableFields = [
            'LOWER(CONCAT_WS(" ", nombre_inquilino, apellidop_inquilino, COALESCE(apellidom_inquilino, "")))',
            'LOWER(email)',
            'LOWER(celular)',
            'LOWER(COALESCE(slug, ""))',
            'LOWER(COALESCE(rfc, ""))',
            'LOWER(COALESCE(curp, ""))',
        ];

        $whereParts = [];
        $params = [];
        foreach ($searchableFields as $index => $field) {
            $placeholder = ':needle' . $index;
            $whereParts[] = $field . ' LIKE ' . $placeholder;
            $params[$placeholder] = $needle;
        }

        $sql = 'SELECT * FROM inquilinos WHERE ' . implode(' OR ', $whereParts);

        if (ctype_digit($q)) {
            $sql .= ' OR id = :idExact';
            $params[':idExact'] = (int) $q;
        }

        $sql .= ' ORDER BY updated_at DESC, id DESC LIMIT 50';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            if ($key === ':idExact') {
                $stmt->bindValue($key, (int) $value, PDO::PARAM_INT);
                continue;
            }

            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }

        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $resultados = [];
        foreach ($rows as $row) {
            $full = $this->obtenerPorId((int) ($row['id'] ?? 0));
            if ($full) {
                $resultados[] = $full;
            }
        }

        return $resultados;
    }
    public function obtenerPorId(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT * FROM inquilinos WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $profile   = $this->buildFullProfile($row);
        $archivos  = $this->obtenerArchivos($id);
        $validaciones = $this->obtenerValidaciones($id);
        $polizas   = $this->obtenerPolizas($id);
        $selfieUrl = $this->extraerSelfieUrl($archivos);

        $combined = $profile;
        $combined['archivos']     = $archivos;
        $combined['validaciones'] = $validaciones;
        $combined['polizas']      = $polizas;
        $combined['selfie_url']   = $selfieUrl;
        $combined['profile']      = $profile;

        return $combined;
    }

    public function obtenerPorSlug(string $slug): ?array
    {
        $slug = strtolower(trim($slug));
        if ($slug === '') {
            return null;
        }

        $stmt = $this->db->prepare('SELECT * FROM inquilinos WHERE LOWER(slug) = :slug LIMIT 1');
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return $this->obtenerPorId((int) $row['id']);
    }

    public function obtenerArchivos(int $idInquilino): array
    {
        if ($idInquilino <= 0) {
            return [];
        }

        $stmt = $this->db->prepare('SELECT * FROM inquilinos_archivos WHERE id_inquilino = :id ORDER BY created_at ASC, id ASC');
        $stmt->execute([':id' => $idInquilino]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(function (array $row): array {
            $row['id'] = (int) ($row['id'] ?? 0);
            $row['id_inquilino'] = (int) ($row['id_inquilino'] ?? 0);
            return $row;
        }, $rows);
    }

    public function archivosPorInquilinoId(int $idInquilino): array
    {
        return $this->obtenerArchivos($idInquilino);
    }

    public function obtenerComprobantesIngreso(int $idInquilino): array
    {
        $archivos = $this->obtenerArchivos($idInquilino);
        $out = [];
        foreach ($archivos as $archivo) {
            $tipo = strtolower((string) ($archivo['tipo'] ?? ''));
            if ($tipo === 'comprobante_ingreso') {
                $out[] = $archivo;
            }
        }
        return $out;
    }

    public function getArchivosByTipos(int $idInquilino, array $tipos): array
    {
        $tipos = array_map(static fn($t) => strtolower(trim((string) $t)), $tipos);
        $tipos = array_filter($tipos, static fn($t) => $t !== '');
        if ($tipos === []) {
            return [];
        }

        $result = [];
        foreach ($this->obtenerArchivos($idInquilino) as $archivo) {
            $tipo = strtolower((string) ($archivo['tipo'] ?? ''));
            if (in_array($tipo, $tipos, true)) {
                $result[$tipo] = $archivo['s3_key'] ?? '';
            }
        }
        return $result;
    }

    public function obtenerArchivosIdentidad(int $idInquilino): array
    {
        $archivos = $this->obtenerArchivos($idInquilino);
        $out = [
            'selfie'          => null,
            'ine_frontal'     => null,
            'ine_reverso'     => null,
            'pasaporte'       => null,
            'forma_migratoria'=> null,
        ];

        foreach ($archivos as $archivo) {
            $tipo = strtolower((string) ($archivo['tipo'] ?? ''));
            if (array_key_exists($tipo, $out)) {
                $out[$tipo] = $archivo['s3_key'] ?? null;
            }
        }

        return array_filter($out, static fn($v) => $v !== null);
    }
    public function actualizarDatosPersonalesPorPk(string $pk, array $data): bool
    {
        $id = $this->resolveIdFromPk($pk, isset($data['id']) ? (int) $data['id'] : null);
        if ($id === null) {
            return false;
        }

        $data = $this->applyTitleCase($data, [
            'nombre_inquilino',
            'apellidop_inquilino',
            'apellidom_inquilino',
            'conyuge',
        ]);

        $campos = [
            'tipo', 'nombre_inquilino', 'apellidop_inquilino', 'apellidom_inquilino',
            'email', 'celular', 'estadocivil', 'nacionalidad', 'curp', 'rfc',
            'tipo_id', 'num_id', 'slug', 'conyuge'
        ];

        $parts = [];
        $params = [':id' => $id];
        foreach ($campos as $campo) {
            if (array_key_exists($campo, $data)) {
                $parts[] = sprintf('%s = :%s', $campo, $campo);
                $params[sprintf(':%s', $campo)] = (string) $data[$campo];
            }
        }

        if ($parts === []) {
            return false;
        }

        $sql = 'UPDATE inquilinos SET ' . implode(', ', $parts) . ', updated_at = NOW() WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function actualizarDomicilioPorPk(string $pk, array $domicilio): bool
    {
        $id = $this->resolveIdFromPk($pk, isset($domicilio['id']) ? (int) $domicilio['id'] : null);
        if ($id === null) {
            return false;
        }

        $datos = array_intersect_key($domicilio, $this->defaultDireccion());
        $datos = array_map(static fn($v) => (string) $v, $datos);
        $datos = $this->applyTitleCase($datos, ['calle', 'colonia', 'alcaldia', 'ciudad']);

        $stmt = $this->db->prepare('SELECT id FROM inquilinos_direccion WHERE id_inquilino = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $set = [];
            foreach ($datos as $campo => $valor) {
                $set[] = sprintf('%s = :%s', $campo, $campo);
            }
            $sql = 'UPDATE inquilinos_direccion SET ' . implode(', ', $set) . ', updated_at = NOW() WHERE id = :pk';
            $params = array_merge([':pk' => (int) $existing['id']], array_combine(
                array_map(static fn($c) => ':' . $c, array_keys($datos)),
                array_values($datos)
            ));
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        }

        $sql = 'INSERT INTO inquilinos_direccion (id_inquilino, calle, num_exterior, num_interior, colonia, alcaldia, ciudad, codigo_postal)
                VALUES (:id, :calle, :num_exterior, :num_interior, :colonia, :alcaldia, :ciudad, :codigo_postal)';
        $params = array_merge([':id' => $id], array_combine(
            array_map(static fn($c) => ':' . $c, array_keys($datos)),
            array_values($datos)
        ));
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function actualizarTrabajoPorPk(string $pk, array $trabajo): bool
    {
        $id = $this->resolveIdFromPk($pk, isset($trabajo['id']) ? (int) $trabajo['id'] : null);
        if ($id === null) {
            return false;
        }

        $datos = array_intersect_key($trabajo, $this->defaultTrabajo());
        $datos = array_map(static fn($v) => (string) $v, $datos);
        $datos = $this->applyTitleCase($datos, ['empresa', 'direccion_empresa', 'nombre_jefe']);

        $stmt = $this->db->prepare('SELECT id FROM inquilinos_trabajo WHERE id_inquilino = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $set = [];
            foreach ($datos as $campo => $valor) {
                $set[] = sprintf('%s = :%s', $campo, $campo);
            }
            $sql = 'UPDATE inquilinos_trabajo SET ' . implode(', ', $set) . ', updated_at = NOW() WHERE id = :pk';
            $params = array_merge([':pk' => (int) $existing['id']], array_combine(
                array_map(static fn($c) => ':' . $c, array_keys($datos)),
                array_values($datos)
            ));
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        }

        $sql = 'INSERT INTO inquilinos_trabajo (id_inquilino, empresa, direccion_empresa, telefono_empresa, puesto, antiguedad, sueldo, otrosingresos, nombre_jefe, tel_jefe, web_empresa)
                VALUES (:id, :empresa, :direccion_empresa, :telefono_empresa, :puesto, :antiguedad, :sueldo, :otrosingresos, :nombre_jefe, :tel_jefe, :web_empresa)';
        $params = array_merge([':id' => $id], array_combine(
            array_map(static fn($c) => ':' . $c, array_keys($datos)),
            array_values($datos)
        ));
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function actualizarFiadorPorPk(string $pk, array $fiador): bool
    {
        $id = $this->resolveIdFromPk($pk, isset($fiador['id']) ? (int) $fiador['id'] : null);
        if ($id === null) {
            return false;
        }

        $datos = array_intersect_key($fiador, $this->defaultFiador());
        $datos = array_map(static fn($v) => (string) $v, $datos);
        $datos = $this->applyTitleCase($datos, [
            'calle_inmueble',
            'colonia_inmueble',
            'alcaldia_inmueble',
            'estado_inmueble',
            'nombre_notario',
            'estado_notario',
        ]);

        $stmt = $this->db->prepare('SELECT id FROM inquilinos_fiador WHERE id_inquilino = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $set = [];
            foreach ($datos as $campo => $valor) {
                $set[] = sprintf('%s = :%s', $campo, $campo);
            }
            $sql = 'UPDATE inquilinos_fiador SET ' . implode(', ', $set) . ', updated_at = NOW() WHERE id = :pk';
            $params = array_merge([':pk' => (int) $existing['id']], array_combine(
                array_map(static fn($c) => ':' . $c, array_keys($datos)),
                array_values($datos)
            ));
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        }

        $sql = 'INSERT INTO inquilinos_fiador (id_inquilino, calle_inmueble, num_ext_inmueble, num_int_inmueble, colonia_inmueble, alcaldia_inmueble, estado_inmueble, cp_inmueble, numero_escritura, fecha_escritura, numero_notario, nombre_notario, estado_notario, folio_real, s3_key)
                VALUES (:id, :calle_inmueble, :num_ext_inmueble, :num_int_inmueble, :colonia_inmueble, :alcaldia_inmueble, :estado_inmueble, :cp_inmueble, :numero_escritura, :fecha_escritura, :numero_notario, :nombre_notario, :estado_notario, :folio_real, :s3_key)';
        $params = array_merge([':id' => $id], array_combine(
            array_map(static fn($c) => ':' . $c, array_keys($datos)),
            array_values($datos)
        ));
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function actualizarHistorialViviendaPorPk(string $pk, array $historial): bool
    {
        $id = $this->resolveIdFromPk($pk, isset($historial['id']) ? (int) $historial['id'] : null);
        if ($id === null) {
            return false;
        }

        $datos = array_intersect_key($historial, $this->defaultHistorial());
        $datos = array_map(static fn($v) => (string) $v, $datos);
        $datos = $this->applyTitleCase($datos, ['arrendador_actual']);

        $stmt = $this->db->prepare('SELECT id FROM inquilinos_historial_vivienda WHERE id_inquilino = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $set = [];
            foreach ($datos as $campo => $valor) {
                $set[] = sprintf('%s = :%s', $campo, $campo);
            }
            $sql = 'UPDATE inquilinos_historial_vivienda SET ' . implode(', ', $set) . ' WHERE id = :pk';
            $params = array_merge([':pk' => (int) $existing['id']], array_combine(
                array_map(static fn($c) => ':' . $c, array_keys($datos)),
                array_values($datos)
            ));
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        }

        $sql = 'INSERT INTO inquilinos_historial_vivienda (id_inquilino, renta_actualmente, arrendador_actual, cel_arrendador_actual, monto_renta_actual, tiempo_habitacion_actual, motivo_arrendamiento, vive_actualmente)
                VALUES (:id, :renta_actualmente, :arrendador_actual, :cel_arrendador_actual, :monto_renta_actual, :tiempo_habitacion_actual, :motivo_arrendamiento, :vive_actualmente)';
        $params = array_merge([':id' => $id], array_combine(
            array_map(static fn($c) => ':' . $c, array_keys($datos)),
            array_values($datos)
        ));
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    public function obtenerValidaciones(int $idInquilino): array
    {
        if ($idInquilino <= 0) {
            return [];
        }

        $stmt = $this->db->prepare('SELECT * FROM inquilinos_validaciones WHERE id_inquilino = :id LIMIT 1');
        $stmt->execute([':id' => $idInquilino]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return [];
        }

        return $this->mapValidacionesRow($row);
    }

    public function actualizarCurp(int $idInquilino, string $curp): bool
    {
        if ($idInquilino <= 0) {
            return false;
        }

        $stmt = $this->db->prepare('UPDATE inquilinos SET curp = :curp, updated_at = NOW() WHERE id = :id');
        return $stmt->execute([
            ':curp' => strtoupper(trim($curp)),
            ':id'   => $idInquilino,
        ]);
    }

    public function guardarValidacionesVerificaMex(int $idInquilino, array $campos): bool
    {
        $idInquilino = max(0, $idInquilino);
        if ($idInquilino <= 0 || $campos === []) {
            return false;
        }

        $validacionIdJson = $campos['validacion_id_json'] ?? null;
        if (is_array($validacionIdJson)) {
            $status = $validacionIdJson['status'] ?? [];
            $statusData = filter_var($status['data'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $statusRenapo = filter_var($status['renapo'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            $similaridad = $validacionIdJson['nombre']['similaridad'] ?? null;
            $similaridad = is_numeric($similaridad) ? (float) $similaridad : null;

            $faceComparison = $validacionIdJson['faceComparison'] ?? [];
            $faceResult = filter_var($faceComparison['result'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($statusData === true && $statusRenapo === true && $faceResult === true && $similaridad !== null && $similaridad > 90.0) {
                $campos['proceso_validacion_id'] = 1;
            }
        }

        $this->ensureValidacionesRow($idInquilino);

        $set = [];
        $params = [];
        $index = 0;

        foreach ($campos as $columna => $valor) {
            if (!is_string($columna) || $columna === '') {
                continue;
            }

            if (!preg_match('/^[a-zA-Z0-9_]+$/', $columna)) {
                continue;
            }

            if (is_array($valor)) {
                $valor = $this->encodeJson($valor);
            }

            $placeholder = 'v' . $index++;
            $set[] = sprintf('%s = :%s', $columna, $placeholder);
            $params[$placeholder] = $valor;
        }

        if ($set === []) {
            return false;
        }

        $sql = sprintf(
            'UPDATE inquilinos_validaciones SET %s, updated_at = NOW() WHERE id_inquilino = :id',
            implode(', ', $set)
        );

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $idInquilino, PDO::PARAM_INT);

        foreach ($params as $placeholder => $valor) {
            $paramName = ':' . $placeholder;

            if ($valor === null) {
                $stmt->bindValue($paramName, null, PDO::PARAM_NULL);
                continue;
            }

            if (is_bool($valor)) {
                $stmt->bindValue($paramName, $valor ? 1 : 0, PDO::PARAM_INT);
                continue;
            }

            if (is_int($valor)) {
                $stmt->bindValue($paramName, $valor, PDO::PARAM_INT);
                continue;
            }

            $stmt->bindValue($paramName, (string) $valor, PDO::PARAM_STR);
        }

        return $stmt->execute();
    }

    public function guardarValidacionVerificaMex(int $idInquilino, int $proceso, array $json, string $resumen): bool
    {
        return $this->saveValidation($idInquilino, 'verificamex', $proceso, $json, $resumen);
    }

    public function guardarValidacionArchivos(int $idInquilino, int $proceso, array $payload, ?string $resumen = null): bool
    {
        return $this->saveValidation($idInquilino, 'archivos', $proceso, $payload, $resumen);
    }

    public function guardarValidacionRostro(int $idInquilino, int $proceso, array $payload, ?string $resumen = null): bool
    {
        return $this->saveValidation($idInquilino, 'rostro', $proceso, $payload, $resumen);
    }

    public function guardarValidacionIdentidad(int $idInquilino, $payload, ?string $resumen = null, int $proceso = 1): bool
    {
        return $this->saveValidation($idInquilino, 'identidad', $proceso, $payload, $resumen);
    }

    public function guardarValidacionDocumentos(int $idInquilino, int $proceso, array $payload, ?string $resumen = null): bool
    {
        return $this->saveValidation($idInquilino, 'documentos', $proceso, $payload, $resumen);
    }

    public function guardarValidacionIngresosSimple(int $idInquilino, int $proceso, array $payload, ?string $resumen = null): bool
    {
        $payload['tipo'] = $payload['tipo'] ?? 'ingresos_pdf_simple';
        return $this->saveValidation($idInquilino, 'ingresos', $proceso, $payload, $resumen);
    }

    public function guardarValidacionIngresosList(int $idInquilino, int $proceso, array $payload, ?string $resumen = null): bool
    {
        $payload['tipo'] = $payload['tipo'] ?? 'ingresos_list';
        return $this->saveValidation($idInquilino, 'ingresos', $proceso, $payload, $resumen);
    }

    public function guardarValidacionIngresosOCR(int $idInquilino, int $proceso, array $payload, ?string $resumen = null): bool
    {
        $payload['tipo'] = $payload['tipo'] ?? 'ingresos_ocr';
        return $this->saveValidation($idInquilino, 'ingresos', $proceso, $payload, $resumen);
    }

    public function guardarPagoInicial(int $idInquilino, int $proceso, array $payload, ?string $resumen = null): bool
    {
        return $this->saveValidation($idInquilino, 'pago_inicial', $proceso, $payload, $resumen);
    }

    public function guardarInvestigacionDemandas(int $idInquilino, int $proceso, array $payload, ?string $resumen = null): bool
    {
        return $this->saveValidation($idInquilino, 'demandas', $proceso, $payload, $resumen);
    }

    public function actualizarStatus(int $idInquilino, string $status): bool
    {
        if ($idInquilino <= 0) {
            return false;
        }

        $status = trim($status);
        if ($status === '') {
            return false;
        }

        $stmt = $this->db->prepare('UPDATE inquilinos SET status = :status, updated_at = NOW() WHERE id = :id');
        if (ctype_digit($status)) {
            $stmt->bindValue(':status', (int) $status, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        }
        $stmt->bindValue(':id', $idInquilino, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function cambiarAsesor(int $idInquilino, array $asesorData): array
    {
        $idInquilino = (int) $idInquilino;
        if ($idInquilino <= 0) {
            throw new \RuntimeException('Inquilino no encontrado.');
        }

        $nuevoId = (int) ($asesorData['id'] ?? 0);
        if ($nuevoId <= 0) {
            throw new \RuntimeException('Asesor inválido.');
        }

        $stmt = $this->db->prepare('UPDATE inquilinos SET id_asesor = :asesor, updated_at = NOW() WHERE id = :id');
        $stmt->execute([':asesor' => $nuevoId, ':id' => $idInquilino]);

        $asesorModel = new AsesorModel();
        $asesor      = $asesorModel->find($nuevoId);
        if (!$asesor) {
            $asesor = [
                'id'            => $nuevoId,
                'nombre_asesor' => (string) ($asesorData['nombre_asesor'] ?? ''),
                'email'         => (string) ($asesorData['email'] ?? ''),
                'celular'       => (string) ($asesorData['celular'] ?? ''),
            ];
        }

        $asesor['pk'] = $asesor['pk'] ?? sprintf('ase#%d', $asesor['id']);
        return $asesor;
    }

    public function obtenerSueldoDeclarado(int $idInquilino): ?float
    {
        if ($idInquilino <= 0) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT sueldo FROM inquilinos_trabajo WHERE id_inquilino = :id ORDER BY id DESC LIMIT 1');
        $stmt->execute([':id' => $idInquilino]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || ($row['sueldo'] ?? '') === '') {
            return null;
        }
        return (float) $row['sueldo'];
    }
    private function tiposPorPrefix(string $prefix): array
    {
        $prefix = strtolower($prefix);
        return match ($prefix) {
            'inq' => ['arrendatario', 'inquilino'],
            'obl' => ['obligado', 'obligado solidario'],
            'fia' => ['fiador'],
            default => [],
        };
    }

    private function listProfilesByPrefix(string $prefix): array
    {
        $tipos = $this->tiposPorPrefix($prefix);
        if ($tipos === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($tipos), '?'));
        $sql = 'SELECT * FROM inquilinos WHERE LOWER(tipo) IN (' . $placeholders . ') ORDER BY id ASC';
        $stmt = $this->db->prepare($sql);
        foreach ($tipos as $idx => $tipo) {
            $stmt->bindValue($idx + 1, strtolower($tipo), PDO::PARAM_STR);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(fn(array $row): array => $this->profileListEntry($this->mapProfileRow($row)), $rows);
    }

    public function getInquilinosAll(): array
    {
        return $this->listProfilesByPrefix('inq');
    }

    public function getFiadoresAll(): array
    {
        return $this->listProfilesByPrefix('fia');
    }

    public function getObligadosAll(): array
    {
        return $this->listProfilesByPrefix('obl');
    }

    public function buscarConFiltros(string $nombre = '', string $email = '', string $tipo = '', int $limit = 50, int $offset = 0): array
    {
        $limit  = max(1, $limit);
        $offset = max(0, $offset);

        $sql = 'SELECT * FROM inquilinos WHERE 1=1';
        $params = [];

        if ($tipo !== '') {
            $tipos = $this->tiposPorPrefix($this->prefixFromTipo($tipo));
            $tipos = $tipos ?: [$tipo];
            $placeholders = implode(',', array_fill(0, count($tipos), '?'));
            $sql .= ' AND LOWER(tipo) IN (' . $placeholders . ')';
            foreach ($tipos as $t) {
                $params[] = strtolower($t);
            }
        }

        if ($nombre !== '') {
            $sql .= ' AND LOWER(CONCAT_WS(" ", nombre_inquilino, apellidop_inquilino, COALESCE(apellidom_inquilino, ""))) LIKE ?';
            $params[] = '%' . mb_strtolower($nombre, 'UTF-8') . '%';
        }

        if ($email !== '') {
            $sql .= ' AND LOWER(email) LIKE ?';
            $params[] = '%' . mb_strtolower($email, 'UTF-8') . '%';
        }

        $sql .= ' ORDER BY updated_at DESC, id DESC LIMIT ' . $limit . ' OFFSET ' . $offset;

        $stmt = $this->db->prepare($sql);
        foreach ($params as $idx => $value) {
            $stmt->bindValue($idx + 1, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(fn(array $row): array => $this->profileListEntry($this->mapProfileRow($row)), $rows);
    }

    public function buscarPorTexto(string $term, int $limit = 10): array
    {
        $rows = $this->buscarConFiltros($term, '', '', $limit, 0);
        return array_map(static function (array $row) {
            return [
                'id'      => $row['id'],
                'nombre'  => $row['nombre'],
                'email'   => $row['email'],
                'celular' => $row['celular'] ?? '',
                'tipo'    => $row['tipo'] ?? 'inquilino',
            ];
        }, $rows);
    }

    public function contarInquilinosNuevos(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM inquilinos WHERE status = 1 OR LOWER(CAST(status AS CHAR)) = 'nuevo'";
        $stmt = $this->db->query($sql);
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        return (int) ($row['total'] ?? 0);
    }

    public function getInquilinosNuevosConSelfie(int $limit = 8): array
    {
        $limit = max(1, $limit);
        $sql = 'SELECT i.*, (
                    SELECT s3_key FROM inquilinos_archivos
                    WHERE id_inquilino = i.id AND tipo = "selfie"
                    ORDER BY created_at DESC, id DESC
                    LIMIT 1
                ) AS selfie_key
                FROM inquilinos i
                WHERE i.status = 1 OR LOWER(CAST(i.status AS CHAR)) = "nuevo"
                ORDER BY i.updated_at DESC, i.id DESC
                LIMIT ' . $limit;

        $stmt = $this->db->query($sql);
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $s3 = new S3Helper('inquilinos');
        $resultados = [];
        foreach ($rows as $row) {
            $selfieKey = $row['selfie_key'] ?? null;
            $resultados[] = [
                'id'                  => (int) ($row['id'] ?? 0),
                'nombre_inquilino'    => (string) ($row['nombre_inquilino'] ?? ''),
                'apellidop_inquilino' => (string) ($row['apellidop_inquilino'] ?? ''),
                'apellidom_inquilino' => (string) ($row['apellidom_inquilino'] ?? ''),
                'tipo'                => (string) ($row['tipo'] ?? 'inquilino'),
                'email'               => (string) ($row['email'] ?? ''),
                'celular'             => (string) ($row['celular'] ?? ''),
                'selfie_url'          => $selfieKey ? $s3->getPresignedUrl($selfieKey) : null,
                'slug'                => (string) ($row['slug'] ?? ''),
            ];
        }

        return $resultados;
    }

    /**
     * @return array<int, string>
     */
    private function recolectarClavesS3(int $idInquilino): array
    {
        $keys = [];

        foreach ($this->obtenerArchivos($idInquilino) as $archivo) {
            $key = trim((string)($archivo['s3_key'] ?? ''));
            if ($key !== '') {
                $keys[$key] = $key;
            }
        }

        $stmt = $this->db->prepare("SELECT s3_key FROM inquilinos_fiador WHERE id_inquilino = :id AND s3_key IS NOT NULL AND s3_key <> ''");
        $stmt->bindValue(':id', $idInquilino, PDO::PARAM_INT);
        $stmt->execute();

        $fiadorKeys = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        foreach ($fiadorKeys as $fiadorKey) {
            $key = trim((string) $fiadorKey);
            if ($key !== '') {
                $keys[$key] = $key;
            }
        }

        return array_values($keys);
    }

    /**
     * @param array<int, string> $keys
     * @return array<int, string>
     */
    private function eliminarArchivosS3(array $keys): array
    {
        if ($keys === []) {
            return [];
        }

        $s3 = new S3Helper('inquilinos');
        $fallidos = [];

        foreach (array_unique($keys) as $key) {
            $key = trim($key);
            if ($key === '') {
                continue;
            }

            if (!$s3->deleteFile($key)) {
                $fallidos[] = $key;
            }
        }

        return $fallidos;
    }

    private function tienePolizasRelacionadas(int $idInquilino): ?string
    {
        $query = 'SELECT numero_poliza FROM polizas WHERE estado = 1 AND (
                id_inquilino = :idInquilino OR id_obligado = :idObligado OR id_fiador = :idFiador

            ) ORDER BY id_poliza ASC LIMIT 1';

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':idInquilino', $idInquilino, PDO::PARAM_INT);
        $stmt->bindValue(':idObligado', $idInquilino, PDO::PARAM_INT);
        $stmt->bindValue(':idFiador', $idInquilino, PDO::PARAM_INT);
        $stmt->execute();

        $numeroPoliza = $stmt->fetchColumn();
        $numeroPoliza = is_string($numeroPoliza) ? trim($numeroPoliza) : null;

        return $numeroPoliza !== '' ? $numeroPoliza : null;
    }

    private function profileListEntry(array $profile): array
    {
        $parts = $this->splitNombre($profile['nombre'] ?? '');

        return [
            'id'                  => (int) ($profile['id'] ?? 0),
            'nombre_inquilino'    => $parts['nombre'],
            'apellidop_inquilino' => $parts['ap_paterno'],
            'apellidom_inquilino' => $parts['ap_materno'],
            'nombre'              => $profile['nombre'] ?? '',
            'email'               => $profile['email'] ?? '',
            'celular'             => $profile['celular'] ?? '',
            'tipo'                => $profile['tipo'] ?? '',
            'slug'                => $profile['slug'] ?? '',
            'status'              => (string) ($profile['status'] ?? ''),
        ];
    }

    private function splitNombre(string $nombreCompleto): array
    {
        $nombreCompleto = trim($nombreCompleto);
        if ($nombreCompleto === '') {
            return ['nombre' => '', 'ap_paterno' => '', 'ap_materno' => ''];
        }

        $parts = preg_split('/\s+/', $nombreCompleto);
        if (!$parts) {
            return ['nombre' => $nombreCompleto, 'ap_paterno' => '', 'ap_materno' => ''];
        }

        if (count($parts) === 1) {
            return ['nombre' => $parts[0], 'ap_paterno' => '', 'ap_materno' => ''];
        }

        $apMaterno = array_pop($parts);
        $apPaterno = array_pop($parts) ?? '';
        $nombre    = implode(' ', $parts);
        if ($nombre === '') {
            $nombre = $apPaterno;
            $apPaterno = $apMaterno;
            $apMaterno = '';
        }

        return [
            'nombre'      => trim($nombre),
            'ap_paterno'  => trim($apPaterno),
            'ap_materno'  => trim($apMaterno),
        ];
    }
}
