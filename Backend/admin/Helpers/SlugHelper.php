<?php
namespace App\Helpers;

class SlugHelper
{
    public static function fromName(string $text): string
    {
        // quita acentos
        $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        // minúsculas
        $text = strtolower($text);
        // reemplaza no alfanuméricos por guión
        $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
        // limpia guiones dobles
        $text = trim($text, '-');
        return $text ?: 'inquilino';
    }
}
