<?php

namespace App\Helpers;

class NormalizadoHelper
{
    /**
     * Normaliza un string para usarlo en keys de S3.
     * - Convierte a minúsculas
     * - Reemplaza acentos y ñ
     * - Elimina caracteres no alfanuméricos
     */
    public static function normalizarNombre(string $str): string
    {
        $replacements = [
            'Á' => 'a',
            'É' => 'e',
            'Í' => 'i',
            'Ó' => 'o',
            'Ú' => 'u',
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'Ñ' => 'n',
            'ñ' => 'n'
        ];

        $str = strtr($str, $replacements);
        $str = strtolower($str);
        $str = preg_replace('/[^a-z0-9]/', '', $str);

        return $str;
    }

    /**
     * Construye un key de S3 para arrendadores o inquilinos.
     * 
     * Ejemplo:
     *   tipoPerfil = arr
     *   id = 557
     *   nombre = "Edgardo Montesinos Urdapilleta"
     *   tipoArchivo = "identificacion_frontal"
     *   ext = "jpg"
     * 
     * Resultado:
     *   arr#557_edgardomontesinosurdapilleta/identificacion_frontal_edgardomontesinosurdapilleta.jpg
     */
    public static function generarS3Key(
        string $tipoPerfil,
        int $id,
        string $nombre,
        string $tipoArchivo,
        string $ext,
        ?string $uniqueSuffix = null
    ): string {
        $nombreNorm = self::normalizarNombre($nombre) ?: 'inquilino';

        $tipoArchivo = strtolower(str_replace(' ', '_', $tipoArchivo));
        $tipoArchivo = preg_replace('/[^a-z0-9_]/', '', $tipoArchivo) ?: 'archivo';

        $ext = strtolower($ext);
        $ext = preg_replace('/[^a-z0-9]/', '', $ext) ?: 'dat';

        $folder = sprintf('%s#%d_%s', $tipoPerfil, $id, $nombreNorm);

        $fileBase = sprintf('%s_%s', $tipoArchivo, $nombreNorm);

        if ($uniqueSuffix) {
            $suffix = strtolower($uniqueSuffix);
            $suffix = preg_replace('/[^a-z0-9_-]/', '', $suffix);
            if ($suffix !== '') {
                $fileBase .= '_' . $suffix;
            }
        }

        return sprintf('%s/%s.%s', $folder, $fileBase, $ext);
    }
    /**
     * Normaliza texto a minúsculas seguras.
     */
    public static function lower(?string $val): string
    {
        if ($val === null || $val === '') {
            return '';
        }
        return mb_strtolower((string)$val, 'UTF-8');
    }

    /**
     * Elimina tildes/diacríticos conservando el texto base.
     */
    public static function sinDiacriticos(?string $val): string
    {
        if ($val === null || $val === '') {
            return '';
        }

        $normalizado = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $val);

        if ($normalizado === false || $normalizado === null) {
            $normalizado = (string) $val;
        }

        if (class_exists('\Normalizer')) {
            $normalized = \Normalizer::normalize($normalizado, \Normalizer::FORM_D);
            if ($normalized !== false && $normalized !== null) {
                $normalizado = preg_replace('/\p{Mn}+/u', '', $normalized) ?? $normalized;
            }
        }

        return (string) $normalizado;
    }

    /**
     * Normaliza texto para búsquedas en minúsculas sin diacríticos.
     */
    public static function normalizarBusqueda(?string $val): string
    {
        $val = self::lower($val);

        if ($val === '') {
            return '';
        }

        return self::sinDiacriticos($val);
    }

    /**
     * Normaliza texto a slug (ej. para URLs).
     */
    public static function slug(?string $val): string
    {
        if (empty($val)) return '';
        $val = mb_strtolower($val, 'UTF-8');
        $val = preg_replace('/[^a-z0-9]+/u', '-', $val);
        return trim($val, '-');
    }
}
