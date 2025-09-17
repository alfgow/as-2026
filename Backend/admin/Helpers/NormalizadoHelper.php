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
    public static function generarS3Key(string $tipoPerfil, int $id, string $nombre, string $tipoArchivo, string $ext): string
    {
        $nombreNorm = self::normalizarNombre($nombre);
        $tipoArchivo = strtolower(str_replace(' ', '_', $tipoArchivo));
        $ext = strtolower($ext);

        return "{$tipoPerfil}#{$id}_{$nombreNorm}/{$tipoArchivo}_{$nombreNorm}.{$ext}";
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
