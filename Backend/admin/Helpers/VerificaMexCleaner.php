<?php
namespace App\Helpers;

class VerificaMexCleaner
{
    /**
     * Limpia un JSON de VerificaMex removiendo claves pesadas
     * que contienen base64 o datos redundantes.
     *
     * @param array $json
     * @return array
     */
    public static function limpiar(array $json): array
    {
        // 🔑 Claves a eliminar siempre que existan
        $keysToRemove = [
            'images',          // Base64 pesado
            'reporte',         // Reporte en PDF/HTML
            'inputFields',     // Datos redundantes en cada verificación
            'ineNominalList'   // Listado nominal muy grande
        ];

        foreach ($keysToRemove as $k) {
            if (isset($json[$k])) {
                unset($json[$k]);
            }
        }

        // 🔄 Recorremos recursivamente para limpiar también en niveles anidados
        foreach ($json as $key => &$value) {
            if (is_array($value)) {
                $value = self::limpiar($value);
            }
        }

        return $json;
    }
}