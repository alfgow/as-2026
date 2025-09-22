<?php

namespace App\Helpers;

class VerificaMexMapper
{
    /**
     * Procesa un JSON de VerificaMex y devuelve los campos
     * listos para insertar/actualizar en la tabla inquilinos_validaciones.
     *
     * @param array $json
     * @param array|null $inquilino Datos actuales del inquilino (para validar nombre, curp, etc.)
     * @return array
     */
    public static function map(array $json, ?array $inquilino = null): array
    {
        $campos = [];

        // --- 1. Validación Facial ---
        $face = null;
        if (!empty($json['data']['documentInformation']['documentVerifications'])) {
            foreach ($json['data']['documentInformation']['documentVerifications'] as $verif) {
                if ($verif['key'] === 'Biometrics_FaceMatching') {
                    $face = $verif;
                    break;
                }
            }
        }

        if ($face) {
            $faceResultNormalizado = self::normalizeBoolean($face['result'] ?? null);
            $statusFace = $faceResultNormalizado === true ? 1 : 0;
            $resumenFace = $statusFace
                ? "😎 Comprobación Facial {$face['output']}%"
                : "❌ Falló comprobación facial";

            $campos['proceso_validacion_rostro'] = $statusFace;
            $campos['validacion_rostro_resumen'] = $resumenFace;
            $campos['validacion_rostro_json']    = array_merge($face, [
                'result' => $faceResultNormalizado,
            ]);
        }

        // --- 2. Nombre / Identidad ---
        $nombreIne = null;
        $ineNombre = null;
        $ineApellidos = [];
        if (!empty($json['data']['documentInformation']['documentData'])) {
            foreach ($json['data']['documentInformation']['documentData'] as $dato) {
                $valor = trim((string) ($dato['value'] ?? ''));
                if ($valor === '') {
                    continue;
                }

                $etiqueta = strtolower(trim((string) ($dato['name'] ?? '')));
                if ($etiqueta === 'nombre') {
                    $ineNombre = $valor;
                }
                if ($etiqueta === 'apellido/s') {
                    $ineApellidos[] = $valor;
                }
            }

            $ineApellidos = array_values(array_unique($ineApellidos));
            $nombreIne = trim(implode(' ', array_filter([
                $ineNombre,
                trim(implode(' ', $ineApellidos)),
            ])));
        }

        $faceComparison = $json['data']['faceComparison'] ?? null;
        $faceResult = null;
        $faceSimilarity = null;
        $faceComparisonDetalles = is_array($faceComparison) ? $faceComparison : null;
        if (is_array($faceComparison)) {
            $faceResult = self::normalizeBoolean($faceComparison['result'] ?? null);
            if (isset($faceComparison['similarity'])) {
                $faceSimilarity = (float) $faceComparison['similarity'];
            }
            if ($faceComparisonDetalles !== null) {
                $faceComparisonDetalles['result'] = $faceResult;
            }
        }

        $statusDataRaw = $json['data']['status'] ?? null;
        $statusRenapoRaw = $json['data']['renapo']['status'] ?? null;
        $statusData = self::normalizeBoolean($statusDataRaw);
        $statusRenapo = self::normalizeBoolean($statusRenapoRaw);
        $estatusDataOk = ($statusData === true);
        $estatusRenapoOk = ($statusRenapo === true);

        $nombreBdOriginal = null;
        $nombreIneOriginal = $nombreIne;
        $nombreBD = null;
        $nombreIne = null;
        $similaridad = null;
        $nombreCoincide = false;
        $rostroCoincide = ($faceResult === true);
        $todosOk = false;

        if ($nombreIneOriginal && $inquilino) {
            // Normalización de cadenas (acentos, ñ, mayúsculas, espacios)
            $normalize = function ($string) {
                $string = trim((string) $string);
                if ($string === '') {
                    return '';
                }

                $string = mb_strtolower($string, 'UTF-8');
                $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
                if ($transliterated !== false) {
                    $string = $transliterated;
                }
                $string = preg_replace('/[^a-z\s]/', '', $string) ?? '';
                $string = preg_replace('/\s+/', ' ', $string) ?? '';

                return trim($string);
            };

            $nombreBdOriginal = trim(implode(' ', array_filter([
                $inquilino['apellidop_inquilino'] ?? '',
                $inquilino['apellidom_inquilino'] ?? '',
                $inquilino['nombre_inquilino'] ?? '',
            ])));

            $nombreBD = $normalize($nombreBdOriginal);
            $nombreIne = $normalize($nombreIneOriginal);

            if ($nombreBD !== '' && $nombreIne !== '') {
                $percent = 0.0;
                similar_text($nombreBD, $nombreIne, $percent);
                $similaridad = round($percent, 2);
            }

            $nombreCoincide = ($similaridad !== null && $similaridad >= 90.0);
            $todosOk = ($nombreCoincide && $rostroCoincide && $estatusDataOk && $estatusRenapoOk);

            $campos['proceso_validacion_id'] = $todosOk ? 1 : 2;

            $emojiNombre = $nombreCoincide ? '☑️' : '✖️';
            $emojiRostro = $rostroCoincide ? '☑️' : '✖️';
            $emojiStatus = ($estatusDataOk && $estatusRenapoOk) ? '☑️' : '✖️';

            $similaridadTexto = $similaridad !== null ? number_format($similaridad, 2) . '%' : 'sin datos';
            $similaridadRostroTexto = $faceSimilarity !== null ? number_format($faceSimilarity, 2) . '%' : 'sin datos';
            $resultadoRostroTexto = $faceResult === null ? 'sin resultado' : ($faceResult ? 'aprobada' : 'rechazada');
            $statusDataTexto = $statusData === null ? 'sin dato' : ($estatusDataOk ? 'aprobado' : 'rechazado');
            $statusRenapoTexto = $statusRenapo === null ? 'sin dato' : ($estatusRenapoOk ? 'aprobado' : 'rechazado');

            $campos['validacion_id_resumen'] = implode("\n", [
                sprintf('%s Coincidencia de nombre BD vs INE: %s', $emojiNombre, $similaridadTexto),
                sprintf('%s Coincidencia facial: %s (%s)', $emojiRostro, $resultadoRostroTexto, $similaridadRostroTexto),
                sprintf('%s Estatus VerificaMex: data.status %s · renapo.status %s', $emojiStatus, $statusDataTexto, $statusRenapoTexto),
            ]);
        }

        $fechaHoraMx = (new \DateTimeImmutable('now', new \DateTimeZone('America/Mexico_City')))->format(DATE_ATOM);

        $campos['validacion_id_json'] = [
            'timestamp' => $fechaHoraMx,
            'timezone' => 'America/Mexico_City',
            'nombre' => [
                'bd' => [
                    'original' => $nombreBdOriginal,
                    'normalizado' => $nombreBD,
                ],
                'ine' => [
                    'original' => $nombreIneOriginal,
                    'normalizado' => $nombreIne,
                ],
                'similaridad' => $similaridad,
                'umbral' => 90,
            ],
            'faceComparison' => [
                'result' => $faceResult,
                'similarity' => $faceSimilarity,
                'detalles' => $faceComparisonDetalles,
            ],
            'status' => [
                'data' => $estatusDataOk,
                'renapo' => $estatusRenapoOk,
                'detalles' => [
                    'data_raw' => $statusDataRaw,
                    'renapo_raw' => $statusRenapoRaw,
                ],
            ],
        ];

        // --- 3. Documento (expiración, número, checks) ---
        $expira = null;
        if (!empty($json['data']['documentInformation']['documentData'])) {
            foreach ($json['data']['documentInformation']['documentData'] as $dato) {
                if ($dato['type'] === 'DateOfExpiry') {
                    $expira = $dato['value'];
                    break;
                }
            }
        }

        if ($expira) {
            $campos['proceso_validacion_documentos'] = 1;
            $campos['validacion_documentos_resumen'] = "📑 Documento válido, expira $expira";
            $campos['validacion_documentos_json']    = [
                'fecha_expiracion' => $expira
            ];
        }

        return $campos;
    }

    private static function normalizeBoolean($value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return null;
            }

            $lower = mb_strtolower($value, 'UTF-8');

            if ($lower === 'ok') {
                return true;
            }

            if (in_array($lower, ['true', '1', 'yes', 'si', 'sí', 'on'], true)) {
                return true;
            }

            if (in_array($lower, ['false', '0', 'no', 'off'], true)) {
                return false;
            }

            if (is_numeric($value)) {
                $numeric = (int) $value;
                if ($numeric === 1) {
                    return true;
                }
                if ($numeric === 0) {
                    return false;
                }
            }

            return null;
        }

        if (is_int($value) || is_float($value)) {
            if ((int) $value === 1) {
                return true;
            }
            if ((int) $value === 0) {
                return false;
            }
        }

        return null;
    }
}
