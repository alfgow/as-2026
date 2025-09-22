<?php

namespace App\Helpers;

require_once __DIR__ . '/NormalizadoHelper.php';

class SlugHelper
{
    public static function fromName(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return 'inquilino';
        }

        // Normaliza quitando diacríticos y artefactos de transliteración
        $text = NormalizadoHelper::sinDiacriticos($text);
        $text = strtr($text, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Ü' => 'U', 'ü' => 'u', 'Ñ' => 'N', 'ñ' => 'n',
        ]);
        $text = str_replace(["'", '"', '`', '´', '^', '¨', '~'], '', $text);

        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9]+/u', '-', $text);
        $text = trim((string) $text, '-');

        return $text !== '' ? $text : 'inquilino';
    }
}
