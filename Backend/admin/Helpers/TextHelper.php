<?php

declare(strict_types=1);

namespace App\Helpers;

class TextHelper
{
    /**
     * Convierte un string a "Capitalizado" (cada palabra inicia con mayúscula).
     * Ej: "edgardo montesinos urdapilleta" → "Edgardo Montesinos Urdapilleta"
     */
    public static function titleCase(?string $text): string
    {
        if (empty($text)) {
            return '';
        }
        return mb_convert_case($text, MB_CASE_TITLE, "UTF-8");
    }

    /**
     * Convierte un string a solo primera letra mayúscula.
     * Ej: "edgardo montesinos urdapilleta" → "Edgardo montesinos urdapilleta"
     */
    public static function ucfirst(?string $text): string
    {
        if (empty($text)) {
            return '';
        }
        return mb_strtoupper(mb_substr($text, 0, 1, "UTF-8"), "UTF-8")
            . mb_substr($text, 1, null, "UTF-8");
    }

    /**
     * Convierte un string COMPLETO a mayúsculas.
     * Ej: "edgardo montesinos urdapilleta" → "EDGARDO MONTESINOS URDAPILLETA"
     */
    public static function upper(?string $text): string
    {
        if (empty($text)) {
            return '';
        }
        return mb_strtoupper($text, "UTF-8");
    }
    public static function formatCurrency($value): string
    {
        if ($value === null || $value === '') {
            return '$0';
        }
        return '$' . number_format((float)$value, 0, '.', ',');
    }
}
