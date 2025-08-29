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
            $statusFace = ($face['result'] === 'Ok') ? 1 : 0;
            $resumenFace = $statusFace
                ? "😎 Comprobación Facial {$face['output']}%"
                : "❌ Falló comprobación facial";

            $campos['proceso_validacion_rostro'] = $statusFace;
            $campos['validacion_rostro_resumen'] = $resumenFace;
            $campos['validacion_rostro_json']    = $face;
        }

        // --- 2. Nombre / Identidad ---
        $nombreIne = null;
        if (!empty($json['data']['documentInformation']['documentData'])) {
            foreach ($json['data']['documentInformation']['documentData'] as $dato) {
                if ($dato['type'] === 'FullName') {
                    $nombreIne = strtoupper(trim($dato['value']));
                    break;
                }
            }
        }

        if ($nombreIne && $inquilino) {
                    // Función helper para normalizar acentos y mayúsculas
        $normalize = function ($string) {
    // Pasar a mayúsculas
    $string = mb_strtoupper($string, 'UTF-8');

    // Remover acentos
    $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);

    // Normalizar comillas/apóstrofes raros
    $string = str_replace(["´","‘","’","`"], "'", $string);

    // Quitar comillas/apóstrofes simples también (opcional, si quieres ignorarlos)
    $string = str_replace("'", "", $string);

    // Quitar espacios extra
    $string = preg_replace('/\s+/', ' ', trim($string));

    return $string;
};


        $nombreBD  = $normalize(
            ($inquilino['apellidop_inquilino'] ?? '') . ' ' .
            ($inquilino['apellidom_inquilino'] ?? '') . ' ' .
            ($inquilino['nombre_inquilino'] ?? '')
        );
        $nombreIne = $normalize($nombreIne);

        $coincide = (strpos($nombreBD, $nombreIne) !== false || strpos($nombreIne, $nombreBD) !== false);


            $campos['proceso_validacion_id'] = $coincide ? 1 : 0;
            $campos['validacion_id_resumen'] = $coincide
                ? "✔️ Identidad (nombres): coincide con BD ($nombreIne)"
                : "❌ Identidad: no coincide (INE=$nombreIne, BD=$nombreBD)";
            $campos['validacion_id_json'] = [
                'ine' => $nombreIne,
                'bd'  => $nombreBD,
            ];
        }

        // --- 3. Documento (expiración, número, checks) ---
        $docResumen = null;
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
            $docResumen = "📑 Documento válido, expira $expira";
            $campos['proceso_validacion_documentos'] = 1;
            $campos['validacion_documentos_resumen'] = $docResumen;
            $campos['validacion_documentos_json']    = [
                'fecha_expiracion' => $expira
            ];
        }

        return $campos;
    }
}