<?php

declare(strict_types=1);

namespace App\Models;

require_once __DIR__ . '/../Core/Dynamo.php';
require_once __DIR__ . '/../Helpers/S3Helper.php';

use App\Core\Dynamo;
use App\Helpers\S3Helper;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;

class InquilinoModel
{
    private DynamoDbClient $client;
    private Marshaler $marshaler;
    private string $table;

    public function __construct()
    {
        $this->client    = Dynamo::client();
        $this->marshaler = Dynamo::marshaler();
        $this->table     = Dynamo::table();
    }

    /**
     * Prefijos soportados para los distintos roles de personas.
     */
    private const PREFIXES = [
        'arrendatario' => 'inq',
        'inquilino'    => 'inq',
        'obligado'     => 'obl',
        'obligado solidario' => 'obl',
        'fiador'       => 'fia',
    ];

    /**
     * Devuelve la lista de prefijos soportados (inq/obl/fia).
     */
    private function supportedPrefixes(): array
    {
        return ['inq', 'obl', 'fia'];
    }

    /**
     * Construye el PK completo a partir de un prefijo y un identificador entero.
     */
    private function buildPk(string $prefix, int $id): string
    {
        return sprintf('%s#%d', $prefix, $id);
    }

    /**
     * Obtiene la lista de PK candidatos para un ID numérico sin conocer su tipo.
     */
    private function candidatePksForId(int $id): array
    {
        $out = [];
        foreach ($this->supportedPrefixes() as $prefix) {
            $out[] = $this->buildPk($prefix, $id);
        }
        return $out;
    }

    /**
     * Determina el prefijo a partir del tipo textual almacenado en Dynamo.
     */
    private function prefixFromTipo(?string $tipo): string
    {
        $tipo = strtolower(trim((string)$tipo));
        foreach (self::PREFIXES as $key => $prefix) {
            if ($tipo === $key) {
                return $prefix;
            }
        }
        return 'inq';
    }

    /**
     * Ejecuta un getItem y regresa el array unmarshalled o null.
     */
    private function fetchItem(string $pk, string $sk = 'profile'): ?array
    {
        $result = $this->client->getItem([
            'TableName' => $this->table,
            'Key'       => [
                'pk' => ['S' => $pk],
                'sk' => ['S' => $sk],
            ]
        ]);

        if (empty($result['Item'])) {
            return null;
        }

        return $this->marshaler->unmarshalItem($result['Item']);
    }

    /**
     * Convierte el profile bruto en un arreglo enriquecido con archivos/validaciones/pólizas.
     */
    private function hydrateProfile(array $profile): array
    {
        $pk = (string)($profile['pk'] ?? '');

        $archivos = !empty($profile['archivos_ids']) && is_array($profile['archivos_ids'])
            ? $this->obtenerItemsPorInquilino($pk, $profile['archivos_ids'])
            : [];

        $selfieUrl = $this->extraerSelfieUrl($archivos);

        $validaciones = [];
        if (!empty($profile['validaciones_ids']) && is_array($profile['validaciones_ids'])) {
            $validaciones = $this->obtenerItemsPorInquilino($pk, $profile['validaciones_ids']);
        }

        $polizas = !empty($profile['polizas_ids']) && is_array($profile['polizas_ids'])
            ? $this->obtenerPolizasPorIds($profile['polizas_ids'])
            : [];

        return [
            'profile'      => $profile,
            'archivos'     => $archivos,
            'validaciones' => $validaciones,
            'polizas'      => $polizas,
            'selfie_url'   => $selfieUrl,
        ];
    }

    /**
     * Devuelve el map de validaciones custom almacenado directamente en el profile (si existe).
     */
    private function getValidacionesSnapshot(string $pk): array
    {
        $result = $this->client->getItem([
            'TableName' => $this->table,
            'Key'       => [
                'pk' => ['S' => $pk],
                'sk' => ['S' => 'profile'],
            ],
            'ProjectionExpression' => 'validaciones_data',
        ]);

        if (empty($result['Item']['validaciones_data'])) {
            return [];
        }

        return (array)$this->marshaler->unmarshalValue($result['Item']['validaciones_data']);
    }

    /**
     * Persiste el snapshot de validaciones dentro del profile.
     */
    private function saveValidacionesSnapshot(string $pk, array $snapshot): void
    {
        $this->client->updateItem([
            'TableName' => $this->table,
            'Key'       => [
                'pk' => ['S' => $pk],
                'sk' => ['S' => 'profile'],
            ],
            'UpdateExpression'          => 'SET validaciones_data = :snapshot',
            'ExpressionAttributeValues' => [
                ':snapshot' => $this->marshaler->marshalValue($snapshot),
            ],
        ]);
    }

    /**
     * Normaliza un valor JSON entrante (string o array) a array.
     */
    private function normalizePayload($payload): array
    {
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return (array)$decoded;
            }
            return ['raw' => $payload];
        }
        if (is_array($payload)) {
            return $payload;
        }
        return [];
    }

    /**
     * Genera el resumen de validaciones esperado por los controladores legacy a partir de snapshot o items.
     */
    private function buildValidacionesOutput(array $profile, array $validacionesItems): array
    {
        $snapshot = (array)($profile['validaciones_data'] ?? []);
        $output   = [];

        $mapLegacy = [];
        if (!empty($validacionesItems)) {
            // Tomamos el primer registro legacy (son snapshots migrados del sistema anterior)
            $legacy = $validacionesItems[0];
            $mapLegacy = [
                'archivos' => [
                    'proceso' => (int)($legacy['proceso_validacion_archivos'] ?? 2),
                    'resumen' => $legacy['validacion_archivos_resumen'] ?? null,
                    'json'    => $this->normalizePayload($legacy['validacion_archivos_json'] ?? []),
                ],
                'rostro' => [
                    'proceso' => (int)($legacy['proceso_validacion_rostro'] ?? 2),
                    'resumen' => $legacy['validacion_rostro_resumen'] ?? null,
                    'json'    => $this->normalizePayload($legacy['validacion_rostro_json'] ?? []),
                ],
                'identidad' => [
                    'proceso' => (int)($legacy['proceso_validacion_id'] ?? 2),
                    'resumen' => $legacy['validacion_id_resumen'] ?? null,
                    'json'    => $this->normalizePayload($legacy['validacion_id_json'] ?? []),
                ],
                'documentos' => [
                    'proceso' => (int)($legacy['proceso_validacion_documentos'] ?? 2),
                    'resumen' => $legacy['validacion_documentos_resumen'] ?? null,
                    'json'    => $this->normalizePayload($legacy['validacion_documentos_json'] ?? []),
                ],
                'ingresos' => [
                    'proceso' => (int)($legacy['proceso_validacion_ingresos'] ?? 2),
                    'resumen' => $legacy['validacion_ingresos_resumen'] ?? null,
                    'json'    => $this->normalizePayload($legacy['validacion_ingresos_json'] ?? []),
                ],
                'pago_inicial' => [
                    'proceso' => (int)($legacy['proceso_pago_inicial'] ?? 2),
                    'resumen' => $legacy['pago_inicial_resumen'] ?? null,
                    'json'    => $this->normalizePayload($legacy['pago_inicial_json'] ?? []),
                ],
                'demandas' => [
                    'proceso' => (int)($legacy['proceso_inv_demandas'] ?? 2),
                    'resumen' => $legacy['validacion_demandas_resumen'] ?? null,
                    'json'    => $this->normalizePayload($legacy['validacion_demandas_json'] ?? []),
                ],
                'verificamex' => [
                    'proceso' => (int)($legacy['proceso_validacion_verificamex'] ?? 2),
                    'resumen' => $legacy['verificamex_resumen'] ?? null,
                    'json'    => $this->normalizePayload($legacy['verificamex_json'] ?? []),
                ],
            ];
        }

        // Mezclamos snapshot moderno con legacy (snapshot tiene prioridad).
        $keys = array_unique(array_merge(array_keys($mapLegacy), array_keys($snapshot)));
        foreach ($keys as $key) {
            $entry = $snapshot[$key] ?? null;
            if ($entry !== null) {
                $payload = $entry['payload'] ?? $entry['json'] ?? [];
                $output[$key] = [
                    'proceso' => (int)($entry['proceso'] ?? 2),
                    'resumen' => $entry['resumen'] ?? null,
                    'json'    => $payload,
                    'updated_at' => $entry['updated_at'] ?? null,
                ];
                continue;
            }

            if (isset($mapLegacy[$key])) {
                $output[$key] = $mapLegacy[$key];
            }
        }

        return $output;
    }

    /**
     * Operación base para guardar una validación en el snapshot del inquilino.
     */
    private function saveValidation(int $id, string $tipo, int $proceso, $payload, ?string $resumen = null): bool
    {
        $pk = $this->resolvePkById($id);
        if (!$pk) {
            return false;
        }

        $snapshot = $this->getValidacionesSnapshot($pk);
        $snapshot[$tipo] = [
            'proceso'    => (int)$proceso,
            'resumen'    => $resumen,
            'payload'    => $this->normalizePayload($payload),
            'updated_at' => date('c'),
        ];

        $this->saveValidacionesSnapshot($pk, $snapshot);
        return true;
    }

    /**
     * Resuelve el PK real de un inquilino a partir de su ID.
     */
    private function resolvePkById(int $id): ?string
    {
        foreach ($this->candidatePksForId($id) as $candidate) {
            $item = $this->fetchItem($candidate);
            if ($item !== null) {
                return $candidate;
            }
        }
        return null;
    }

    /**
     * Expose PK resolution for controllers/services.
     */
    public function getPkById(int $id): ?string
    {
        return $this->resolvePkById($id);
    }

    /**
     * Registers an uploaded file and appends it to archivos_ids.
     *
     * @return array Datos del archivo recién almacenado.
     */
    public function registrarArchivo(
        int $idInquilino,
        string $tipo,
        string $s3Key,
        array $meta = [],
        ?string $forcedSuffix = null
    ): array {
        $pk = $this->resolvePkById($idInquilino);
        if (!$pk) {
            throw new \RuntimeException('PK no encontrado para el inquilino.');
        }

        [$prefix] = explode('#', $pk . '#');
        $prefix = strtolower($prefix ?: 'inq');

        $suffix = $forcedSuffix !== null
            ? strtolower(preg_replace('/[^a-z0-9]/', '', $forcedSuffix))
            : strtolower(bin2hex(random_bytes(6)));

        if ($suffix === '') {
            $suffix = strtolower(bin2hex(random_bytes(6)));
        }

        $sk = sprintf('%sfile#%s', $prefix, $suffix);
        $now = date('c');

        $item = [
            'pk'            => $pk,
            'sk'            => $sk,
            'tipo'          => strtolower(trim($tipo)),
            's3_key'        => $s3Key,
            'uploaded_at'   => $now,
        ];

        if (!empty($meta['mime_type'])) {
            $item['mime_type'] = strtolower((string)$meta['mime_type']);
        }
        if (!empty($meta['size'])) {
            $item['size'] = (int)$meta['size'];
        }
        if (!empty($meta['original_name'])) {
            $item['nombre_original'] = (string)$meta['original_name'];
        }
        if (!empty($meta['categoria'])) {
            $item['categoria'] = strtolower((string)$meta['categoria']);
        }

        $item = array_filter($item, static fn($v) => $v !== null && $v !== '', ARRAY_FILTER_USE_BOTH);

        $this->client->putItem([
            'TableName' => $this->table,
            'Item'      => $this->marshaler->marshalItem($item),
        ]);

        $this->client->updateItem([
            'TableName' => $this->table,
            'Key'       => [
                'pk' => ['S' => $pk],
                'sk' => ['S' => 'profile'],
            ],
            'UpdateExpression'          => 'SET archivos_ids = list_append(if_not_exists(archivos_ids, :empty), :nuevo)',
            'ExpressionAttributeValues' => [
                ':empty' => ['L' => []],
                ':nuevo' => ['L' => [['S' => $sk]]],
            ],
        ]);

        $item['pk'] = $pk;
        $item['sk'] = $sk;
        $item['id'] = $sk;

        return $item;
    }

    /**
     * Recupera un archivo puntual por su SK.
     */
    public function obtenerArchivo(int $idInquilino, string $archivoId): ?array
    {
        $pk = $this->resolvePkById($idInquilino);
        if (!$pk) {
            return null;
        }

        $archivoId = trim($archivoId);
        if ($archivoId === '') {
            return null;
        }

        $res = $this->client->getItem([
            'TableName' => $this->table,
            'Key'       => [
                'pk' => ['S' => $pk],
                'sk' => ['S' => $archivoId],
            ],
        ]);

        if (empty($res['Item'])) {
            return null;
        }

        return $this->marshaler->unmarshalItem($res['Item']);
    }

    /**
     * Elimina un archivo (item + referencia en profile).
     */
    public function eliminarArchivo(int $idInquilino, string $archivoId): bool
    {
        $pk = $this->resolvePkById($idInquilino);
        if (!$pk) {
            return false;
        }

        $archivoId = trim($archivoId);
        if ($archivoId === '') {
            return false;
        }

        $this->client->deleteItem([
            'TableName' => $this->table,
            'Key'       => [
                'pk' => ['S' => $pk],
                'sk' => ['S' => $archivoId],
            ],
        ]);

        $profile = $this->fetchItem($pk);
        $currentIds = [];
        if (!empty($profile['archivos_ids']) && is_array($profile['archivos_ids'])) {
            foreach ($profile['archivos_ids'] as $id) {
                $id = (string)$id;
                if ($id !== '' && $id !== $archivoId) {
                    $currentIds[] = $id;
                }
            }
        }

        if ($currentIds) {
            $this->client->updateItem([
                'TableName' => $this->table,
                'Key'       => [
                    'pk' => ['S' => $pk],
                    'sk' => ['S' => 'profile'],
                ],
                'UpdateExpression'          => 'SET archivos_ids = :ids',
                'ExpressionAttributeValues' => [
                    ':ids' => $this->marshaler->marshalValue($currentIds),
                ],
            ]);
        } else {
            $this->client->updateItem([
                'TableName' => $this->table,
                'Key'       => [
                    'pk' => ['S' => $pk],
                    'sk' => ['S' => 'profile'],
                ],
                'UpdateExpression' => 'REMOVE archivos_ids',
            ]);
        }

        return true;
    }

    /**
     * Buscar inquilinos/obligados/fiadores por nombre, email o celular
     * Incluye archivos, validaciones, pólizas y selfie_url
     */
    public function searchByTerm(string $q): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }

        $qLower = mb_strtolower($q, 'UTF-8');

        $inquilinos = [];
        $lastKey = null;

        do {
            $params = [
                'TableName'                 => $this->table,
                'FilterExpression'          => '(begins_with(pk, :inq) OR begins_with(pk, :obl) OR begins_with(pk, :fia)) AND sk = :sk',
                'ExpressionAttributeValues' => [
                    ':inq' => ['S' => 'inq#'],
                    ':obl' => ['S' => 'obl#'],
                    ':fia' => ['S' => 'fia#'],
                    ':sk'  => ['S' => 'profile'],
                ]
            ];

            if ($lastKey) {
                $params['ExclusiveStartKey'] = $lastKey;
            }

            $result = $this->client->scan($params);

            foreach ($result['Items'] as $item) {
                $profile = $this->marshaler->unmarshalItem($item);

                $hayCoincidencia = false;
                $fieldsToCheck = [
                    (string)($profile['nombre'] ?? ''),
                    (string)($profile['nombre_inquilino'] ?? ''),
                    (string)($profile['apellidop_inquilino'] ?? ''),
                    (string)($profile['apellidom_inquilino'] ?? ''),
                    (string)($profile['email'] ?? ''),
                    (string)($profile['celular'] ?? ''),
                ];
                foreach ($fieldsToCheck as $field) {
                    if ($field !== '' && mb_stripos($field, $qLower, 0, 'UTF-8') !== false) {
                        $hayCoincidencia = true;
                        break;
                    }
                }

                if (!$hayCoincidencia) {
                    continue;
                }

                $inquilinos[] = $this->hydrateProfile($profile);
            }

            $lastKey = $result['LastEvaluatedKey'] ?? null;
        } while ($lastKey);

        return $inquilinos;
    }


    /**
     * Helper para obtener items relacionados de un inquilino (archivos, validaciones, etc.)
     */
    private function obtenerItemsPorInquilino(string $pk, array $ids): array
    {
        $items = [];
        foreach ($ids as $id) {
            $res = $this->client->getItem([
                'TableName' => $this->table,
                'Key'       => [
                    'pk' => ['S' => $pk],
                    'sk' => ['S' => $id],
                ]
            ]);
            if (!empty($res['Item'])) {
                $items[] = $this->marshaler->unmarshalItem($res['Item']);
            }
        }
        return $items;
    }

    /**
     * Helper para obtener pólizas completas por sus IDs
     */
    private function obtenerPolizasPorIds(array $ids): array
    {
        $polizas = [];
        foreach ($ids as $id) {
            $res = $this->client->getItem([
                'TableName' => $this->table,
                'Key'       => [
                    'pk' => ['S' => $id],
                    'sk' => ['S' => 'profile'],
                ]
            ]);
            if (!empty($res['Item'])) {
                $polizas[] = $this->marshaler->unmarshalItem($res['Item']);
            }
        }
        return $polizas;
    }

    /**
     * Helper para detectar selfie_url dentro de archivos
     */
    private function extraerSelfieUrl(array $archivos): ?string
    {
        foreach ($archivos as $archivo) {
            if (($archivo['tipo'] ?? '') === 'selfie') {
                return $archivo['url']
                    ?? ($archivo['s3_key'] ?? null);
            }
        }
        return null;
    }

    /**
     * Obtiene un inquilino completo (arrendatario/fiador/obligado) por ID.
     */
    public function obtenerPorId(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        foreach ($this->candidatePksForId($id) as $pk) {
            $profile = $this->fetchItem($pk);
            if ($profile !== null) {
                $hydrated = $this->hydrateProfile($profile);
                $combined = array_merge($profile, [
                    'id'           => $id,
                    'archivos'     => $hydrated['archivos'],
                    'validaciones' => $hydrated['validaciones'],
                    'polizas'      => $hydrated['polizas'],
                    'selfie_url'   => $hydrated['selfie_url'],
                ]);
                $parts = $this->splitNombre($profile['nombre'] ?? '');
                $combined['nombre_inquilino']    = $combined['nombre_inquilino']    ?? $parts['nombre'];
                $combined['apellidop_inquilino'] = $combined['apellidop_inquilino'] ?? $parts['ap_paterno'];
                $combined['apellidom_inquilino'] = $combined['apellidom_inquilino'] ?? $parts['ap_materno'];
                $combined['profile'] = $profile;
                return $combined;
            }
        }

        return null;
    }

    /**
     * Busca el profile cuyo slug coincida con el solicitado.
     */
    public function obtenerPorSlug(string $slug): ?array
    {
        $slug = strtolower(trim($slug));
        if ($slug === '') {
            return null;
        }

        $lastKey = null;
        do {
            $params = [
                'TableName'                 => $this->table,
                'FilterExpression'          => 'sk = :sk AND #slug = :slug',
                'ExpressionAttributeValues' => [
                    ':sk'   => ['S' => 'profile'],
                    ':slug' => ['S' => $slug],
                ],
                'ExpressionAttributeNames'  => ['#slug' => 'slug'],
            ];

            if ($lastKey) {
                $params['ExclusiveStartKey'] = $lastKey;
            }

            $result = $this->client->scan($params);

            foreach ($result['Items'] as $item) {
                $profile = $this->marshaler->unmarshalItem($item);
                $hydrated = $this->hydrateProfile($profile);
                $combined = array_merge($profile, [
                    'id'           => (int)($profile['id'] ?? 0),
                    'archivos'     => $hydrated['archivos'],
                    'validaciones' => $hydrated['validaciones'],
                    'polizas'      => $hydrated['polizas'],
                    'selfie_url'   => $hydrated['selfie_url'],
                ]);
                $parts = $this->splitNombre($profile['nombre'] ?? '');
                $combined['nombre_inquilino']    = $combined['nombre_inquilino']    ?? $parts['nombre'];
                $combined['apellidop_inquilino'] = $combined['apellidop_inquilino'] ?? $parts['ap_paterno'];
                $combined['apellidom_inquilino'] = $combined['apellidom_inquilino'] ?? $parts['ap_materno'];
                $combined['profile'] = $profile;
                return $combined;
            }

            $lastKey = $result['LastEvaluatedKey'] ?? null;
        } while ($lastKey);

        return null;
    }

    /**
     * Obtiene todos los archivos relacionados a un inquilino.
     */
    public function obtenerArchivos(int $idInquilino): array
    {
        $pk = $this->resolvePkById($idInquilino);
        if (!$pk) {
            return [];
        }

        $profile = $this->fetchItem($pk);
        if (!$profile) {
            return [];
        }

        return !empty($profile['archivos_ids'])
            ? $this->obtenerItemsPorInquilino($pk, $profile['archivos_ids'])
            : [];
    }

    /**
     * Devuelve todos los archivos (incluyendo tipo y metadatos) de un inquilino.
     */
    public function archivosPorInquilinoId(int $idInquilino): array
    {
        return $this->obtenerArchivos($idInquilino);
    }

    /**
     * Retorna únicamente los comprobantes de ingreso en formato PDF del inquilino.
     */
    public function obtenerComprobantesIngreso(int $idInquilino): array
    {
        $archivos = $this->obtenerArchivos($idInquilino);
        $out = [];
        foreach ($archivos as $archivo) {
            $tipo = strtolower((string)($archivo['tipo'] ?? ''));
            if ($tipo !== 'comprobante_ingreso') {
                continue;
            }
            $out[] = $archivo;
        }
        return $out;
    }

    /**
     * Devuelve un mapa tipo => s3_key para los tipos solicitados.
     */
    public function getArchivosByTipos(int $idInquilino, array $tipos): array
    {
        $tipos = array_map(static fn($t) => strtolower(trim((string)$t)), $tipos);
        $tipos = array_filter($tipos, static fn($t) => $t !== '');
        if (empty($tipos)) {
            return [];
        }

        $result = [];
        foreach ($this->obtenerArchivos($idInquilino) as $archivo) {
            $tipo = strtolower((string)($archivo['tipo'] ?? ''));
            if (in_array($tipo, $tipos, true)) {
                $result[$tipo] = $archivo['s3_key'] ?? '';
            }
        }
        return $result;
    }

    /**
     * Devuelve un arreglo con los s3_key principales usados en validación de identidad.
     */
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
            $tipo = strtolower((string)($archivo['tipo'] ?? ''));
            if (array_key_exists($tipo, $out)) {
                $out[$tipo] = $archivo['s3_key'] ?? null;
            }
        }

        return array_filter($out, static fn($v) => $v !== null);
    }

    /**
     * Actualiza los datos personales principales de un inquilino identificado por su PK.
     */
    public function actualizarDatosPersonalesPorPk(string $pk, array $data): bool
    {
        $pk = trim($pk);
        if ($pk === '') {
            return false;
        }

        $fields = [
            'tipo',
            'nombre_inquilino',
            'apellidop_inquilino',
            'apellidom_inquilino',
            'nombre',
            'email',
            'celular',
            'estadocivil',
            'nacionalidad',
            'curp',
            'rfc',
            'tipo_id',
            'num_id',
            'slug',
        ];

        $parts = [];
        $values = [];

        foreach ($fields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $parts[] = sprintf('%s = :%s', $field, $field);
            $values[sprintf(':%s', $field)] = $this->marshaler->marshalValue((string)$data[$field]);
        }

        if (!$parts) {
            return false;
        }

        $this->client->updateItem([
            'TableName' => $this->table,
            'Key'       => [
                'pk' => ['S' => $pk],
                'sk' => ['S' => 'profile'],
            ],
            'UpdateExpression'          => 'SET ' . implode(', ', $parts),
            'ExpressionAttributeValues' => $values,
        ]);

        return true;
    }

    /**
     * Actualiza el domicilio almacenado en el profile.
     */
    public function actualizarDomicilioPorPk(string $pk, array $domicilio): bool
    {
        $pk = trim($pk);
        if ($pk === '') {
            return false;
        }

        $payload = [];
        foreach ($domicilio as $campo => $valor) {
            if (!is_string($campo)) {
                continue;
            }
            $payload[$campo] = (string)$valor;
        }

        if (!$payload) {
            return false;
        }

        $this->client->updateItem([
            'TableName' => $this->table,
            'Key'       => [
                'pk' => ['S' => $pk],
                'sk' => ['S' => 'profile'],
            ],
            'UpdateExpression'          => 'SET direccion = :direccion',
            'ExpressionAttributeValues' => [
                ':direccion' => $this->marshaler->marshalValue($payload),
            ],
        ]);

        return true;
    }

    /**
     * Actualiza la información laboral almacenada en el profile.
     */
    public function actualizarTrabajoPorPk(string $pk, array $trabajo): bool
    {
        $pk = trim($pk);
        if ($pk === '') {
            return false;
        }

        $payload = [];
        foreach ($trabajo as $campo => $valor) {
            if (!is_string($campo)) {
                continue;
            }

            if (is_string($valor)) {
                $payload[$campo] = $valor;
            } elseif (is_int($valor) || is_float($valor)) {
                $payload[$campo] = (float)$valor;
            } elseif ($valor === null) {
                $payload[$campo] = null;
            }
        }

        if (!$payload) {
            return false;
        }

        $this->client->updateItem([
            'TableName' => $this->table,
            'Key'       => [
                'pk' => ['S' => $pk],
                'sk' => ['S' => 'profile'],
            ],
            'UpdateExpression'          => 'SET trabajo = :trabajo',
            'ExpressionAttributeValues' => [
                ':trabajo' => $this->marshaler->marshalValue($payload),
            ],
        ]);

        return true;
    }

    /**
     * Obtiene el snapshot de validaciones normalizado.
     */
    public function obtenerValidaciones(int $idInquilino): array
    {
        $pk = $this->resolvePkById($idInquilino);
        if (!$pk) {
            return [];
        }

        $profile = $this->fetchItem($pk);
        if (!$profile) {
            return [];
        }

        $validacionesItems = [];
        if (!empty($profile['validaciones_ids'])) {
            $validacionesItems = $this->obtenerItemsPorInquilino($pk, $profile['validaciones_ids']);
        }

        return $this->buildValidacionesOutput($profile, $validacionesItems);
    }

    /**
     * Actualiza la CURP almacenada en el profile.
     */
    public function actualizarCurp(int $idInquilino, string $curp): bool
    {
        $pk = $this->resolvePkById($idInquilino);
        if (!$pk) {
            return false;
        }

        $this->client->updateItem([
            'TableName' => $this->table,
            'Key'       => [
                'pk' => ['S' => $pk],
                'sk' => ['S' => 'profile'],
            ],
            'UpdateExpression'          => 'SET curp = :curp',
            'ExpressionAttributeValues' => [
                ':curp' => ['S' => strtolower(trim($curp))],
            ],
        ]);

        return true;
    }

    /**
     * Guarda un resumen de VerificaMex (campos mapeados).
     */
    public function guardarValidacionesVerificaMex(int $idInquilino, array $campos): bool
    {
        $pk = $this->resolvePkById($idInquilino);
        if (!$pk) {
            return false;
        }

        $this->client->updateItem([
            'TableName' => $this->table,
            'Key'       => [
                'pk' => ['S' => $pk],
                'sk' => ['S' => 'profile'],
            ],
            'UpdateExpression'          => 'SET verificamex_campos = :campos',
            'ExpressionAttributeValues' => [
                ':campos' => $this->marshaler->marshalValue($campos),
            ],
        ]);

        return true;
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
        return $this->saveValidation($idInquilino, 'ingresos', $proceso, $payload, $resumen);
    }

    public function guardarValidacionIngresosList(int $idInquilino, int $proceso, array $payload, ?string $resumen = null): bool
    {
        return $this->saveValidation($idInquilino, 'ingresos_lista', $proceso, $payload, $resumen);
    }

    public function guardarValidacionIngresosOCR(int $idInquilino, int $proceso, array $payload, ?string $resumen = null): bool
    {
        return $this->saveValidation($idInquilino, 'ingresos_ocr', $proceso, $payload, $resumen);
    }

    public function guardarPagoInicial(int $idInquilino, int $proceso, array $payload, ?string $resumen = null): bool
    {
        return $this->saveValidation($idInquilino, 'pago_inicial', $proceso, $payload, $resumen);
    }

    public function guardarInvestigacionDemandas(int $idInquilino, int $proceso, array $payload, ?string $resumen = null): bool
    {
        $ok = $this->saveValidation($idInquilino, 'demandas', $proceso, $payload, $resumen);
        if (!$ok) {
            return false;
        }

        // Mantener compatibilidad con procesos legacy (proceso_inv_demandas en profile).
        $pk = $this->resolvePkById($idInquilino);
        if ($pk) {
            $this->client->updateItem([
                'TableName' => $this->table,
                'Key'       => [
                    'pk' => ['S' => $pk],
                    'sk' => ['S' => 'profile'],
                ],
                'UpdateExpression'          => 'SET proceso_inv_demandas = :estado',
                'ExpressionAttributeValues' => [
                    ':estado' => ['N' => (string)$proceso],
                ],
            ]);
        }

        return true;
    }

    public function actualizarStatus(int $idInquilino, string $status): bool
    {
        $pk = $this->resolvePkById($idInquilino);
        if (!$pk) {
            return false;
        }

        $status = trim($status);
        if ($status === '') {
            return false;
        }

        $this->client->updateItem([
            'TableName' => $this->table,
            'Key'       => [
                'pk' => ['S' => $pk],
                'sk' => ['S' => 'profile'],
            ],
            'UpdateExpression'          => 'SET #status = :status',
            'ExpressionAttributeNames'  => ['#status' => 'status'],
            'ExpressionAttributeValues' => [
                ':status' => ['S' => $status],
            ],
        ]);

        return true;
    }

    /**
     * Obtiene el sueldo declarado en el perfil del inquilino (si existe).
     */
    public function obtenerSueldoDeclarado(int $idInquilino): ?float
    {
        $pk = $this->resolvePkById($idInquilino);
        if (!$pk) {
            return null;
        }

        $profile = $this->fetchItem($pk);
        if (!$profile) {
            return null;
        }

        $sueldo = $profile['trabajo']['sueldo'] ?? null;
        if ($sueldo === null || $sueldo === '') {
            return null;
        }
        return (float)$sueldo;
    }

    /**
     * Lista todos los arrendatarios (para selects en Pólizas, etc.).
     */
    public function getInquilinosAll(): array
    {
        return $this->listProfilesByPrefix('inq');
    }

    /**
     * Lista todos los fiadores existentes.
     */
    public function getFiadoresAll(): array
    {
        return $this->listProfilesByPrefix('fia');
    }

    /**
     * Lista todos los obligados solidarios existentes.
     */
    public function getObligadosAll(): array
    {
        return $this->listProfilesByPrefix('obl');
    }

    /**
     * Busca prospectos con filtros básicos (texto/tipo).
     */
    public function buscarConFiltros(string $nombre = '', string $email = '', string $tipo = '', int $limit = 50, int $offset = 0): array
    {
        $nombre = trim($nombre);
        $email  = trim($email);
        $tipo   = strtolower(trim($tipo));

        $resultados = [];
        $lastKey = null;
        $countSkipped = 0;

        do {
            $params = [
                'TableName'                 => $this->table,
                'FilterExpression'          => 'sk = :sk',
                'ExpressionAttributeValues' => [
                    ':sk' => ['S' => 'profile'],
                ],
            ];

            if ($lastKey) {
                $params['ExclusiveStartKey'] = $lastKey;
            }

            $scan = $this->client->scan($params);
            foreach ($scan['Items'] as $item) {
                $profile = $this->marshaler->unmarshalItem($item);

                $perfilTipo = strtolower((string)($profile['tipo'] ?? ''));
                if ($tipo !== '' && $tipo !== $perfilTipo) {
                    continue;
                }

                $nombreCompleto = (string)($profile['nombre'] ?? '');
                $emailPerfil    = (string)($profile['email'] ?? '');

                if ($nombre !== '' && mb_stripos($nombreCompleto, $nombre, 0, 'UTF-8') === false) {
                    continue;
                }
                if ($email !== '' && mb_stripos($emailPerfil, $email, 0, 'UTF-8') === false) {
                    continue;
                }

                if ($countSkipped++ < $offset) {
                    continue;
                }

                $resultados[] = $this->profileListEntry($profile);
                if (count($resultados) >= $limit) {
                    break;
                }
            }

            if (count($resultados) >= $limit) {
                break;
            }

            $lastKey = $scan['LastEvaluatedKey'] ?? null;
        } while ($lastKey);

        return $resultados;
    }

    /**
     * Versión utilizada por IAController para localizar un prospecto por texto.
     */
    public function buscarPorTexto(string $term, int $limit = 10): array
    {
        $rows = $this->buscarConFiltros($term, '', '', $limit, 0);

        return array_map(function (array $row) {
            return [
                'id'      => $row['id'],
                'nombre'  => $row['nombre'],
                'email'   => $row['email'],
                'telefono'=> $row['celular'],
                'tipo'    => $row['tipo'] ?? 'inquilino',
            ];
        }, $rows);
    }

    /**
     * Cuenta arrendatarios con status=1 (nuevos) para el dashboard.
     */
    public function contarInquilinosNuevos(): int
    {
        $total = 0;
        foreach ($this->listProfilesByPrefix('inq') as $profile) {
            $status = (string)($profile['status'] ?? $profile['status'] ?? '');
            if ($status === '1' || strtolower($status) === 'nuevo') {
                $total++;
            }
        }

        return $total;
    }

    /**
     * Obtiene los inquilinos nuevos (status=1) incluyendo presigned selfie (si existe).
     */
    public function getInquilinosNuevosConSelfie(int $limit = 8): array
    {
        $resultados = [];
        $s3 = new S3Helper('inquilinos');

        foreach ($this->listProfilesByPrefix('inq') as $profile) {
            $status = (string)($profile['status'] ?? '');
            if (!in_array($status, ['1', 'nuevo'], true)) {
                continue;
            }

            $full = $this->obtenerPorId((int)$profile['id']);
            if (!$full) {
                continue;
            }

            $selfie = null;
            foreach ($full['archivos'] as $archivo) {
                if (($archivo['tipo'] ?? '') === 'selfie' && !empty($archivo['s3_key'])) {
                    $selfie = $s3->getPresignedUrl($archivo['s3_key']);
                    break;
                }
            }

            $resultados[] = [
                'id'                   => (int)($profile['id'] ?? 0),
                'nombre_inquilino'     => $profile['nombre_inquilino'] ?? '',
                'apellidop_inquilino'  => $profile['apellidop_inquilino'] ?? '',
                'apellidom_inquilino'  => $profile['apellidom_inquilino'] ?? '',
                'tipo'                 => $profile['tipo'] ?? 'inquilino',
                'email'                => $profile['email'] ?? '',
                'celular'              => $profile['celular'] ?? '',
                'selfie_url'           => $selfie,
                'slug'                 => $profile['slug'] ?? null,
            ];

            if (count($resultados) >= $limit) {
                break;
            }
        }

        return $resultados;
    }

    /**
     * Devuelve una entrada resumida para los listados (id, nombre, email, etc.).
     */
    private function profileListEntry(array $profile): array
    {
        $parts = $this->splitNombre($profile['nombre'] ?? '');

        return [
            'id'                    => (int)($profile['id'] ?? 0),
            'nombre_inquilino'      => $parts['nombre'],
            'apellidop_inquilino'   => $parts['ap_paterno'],
            'apellidom_inquilino'   => $parts['ap_materno'],
            'nombre'                => $profile['nombre'] ?? '',
            'email'                 => $profile['email'] ?? '',
            'celular'               => $profile['celular'] ?? '',
            'tipo'                  => $profile['tipo'] ?? '',
            'slug'                  => $profile['slug'] ?? '',
            'status'                => (string)($profile['status'] ?? ''),
        ];
    }

    /**
     * Lista todos los perfiles de un prefijo específico.
     */
    private function listProfilesByPrefix(string $prefix): array
    {
        $resultados = [];
        $lastKey = null;
        do {
            $params = [
                'TableName'                 => $this->table,
                'FilterExpression'          => 'begins_with(pk, :pk) AND sk = :sk',
                'ExpressionAttributeValues' => [
                    ':pk' => ['S' => $prefix . '#'],
                    ':sk' => ['S' => 'profile'],
                ],
            ];

            if ($lastKey) {
                $params['ExclusiveStartKey'] = $lastKey;
            }

            $scan = $this->client->scan($params);
            foreach ($scan['Items'] as $item) {
                $profile = $this->marshaler->unmarshalItem($item);
                $resultados[] = $this->profileListEntry($profile);
            }

            $lastKey = $scan['LastEvaluatedKey'] ?? null;
        } while ($lastKey);

        return $resultados;
    }

    /**
     * Divide un nombre completo aproximado en nombre y apellidos.
     */
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
