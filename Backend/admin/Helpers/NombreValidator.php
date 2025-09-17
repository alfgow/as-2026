<?php

namespace App\Helpers;

class NombreValidator
{
    public static function normalizar(string $nombre): string
    {
        $replacements = [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'á' => 'A',
            'é' => 'E',
            'í' => 'I',
            'ó' => 'O',
            'ú' => 'U',
            'ü' => 'U',
            'Ñ' => 'N',
            'ñ' => 'N'
        ];
        $nombre = strtr($nombre, $replacements);
        $nombre = strtoupper($nombre);
        $nombre = preg_replace('/[^A-Z ]/', '', $nombre);
        $nombre = preg_replace('/\s+/', ' ', $nombre);
        return trim($nombre);
    }

    public static function comparar(string $nombreBD, array $documentData): array
    {
        $nombreBDNorm = self::normalizar($nombreBD);

        // Concatenar valores de VerificaMex relevantes
        $partes = [];
        foreach ($documentData as $campo) {
            if (in_array($campo['type'], ['Surname', 'FatherSurname', 'MotherSurname', 'SecondSurname', 'Name'])) {
                $partes[] = $campo['value'];
            }
        }
        $nombreVerif = implode(' ', $partes);
        $nombreVerifNorm = self::normalizar($nombreVerif);

        if ($nombreBDNorm === $nombreVerifNorm) {
            return [
                'status' => 1,
                'resumen' => "✔️ Identidad coincide ({$nombreBDNorm})"
            ];
        }

        similar_text($nombreBDNorm, $nombreVerifNorm, $percent);
        if ($percent >= 90) {
            return [
                'status' => 1,
                'resumen' => "✔️ Coincidencia alta ({$percent}%)"
            ];
        } elseif ($percent >= 80) {
            return [
                'status' => 2,
                'resumen' => "⚠️ Posible match ({$percent}%). BD: {$nombreBDNorm} / INE: {$nombreVerifNorm}"
            ];
        } else {
            return [
                'status' => 0,
                'resumen' => "❌ No coincide. BD: {$nombreBDNorm} / INE: {$nombreVerifNorm} ({$percent}%)"
            ];
        }
    }
}
