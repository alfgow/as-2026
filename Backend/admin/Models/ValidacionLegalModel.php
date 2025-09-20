<?php

namespace App\Models;

require_once __DIR__ . '/../Core/Database.php';

use App\Core\Database;

class ValidacionLegalModel extends Database
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Ejecuta la búsqueda en Google Custom Search y guarda resultados.
     */
    public function buscarEnGoogle(
        int $idInquilino,
        string $nombreCompleto,
        string $nombreSolo,
        string $apellido_p,
        string $apellido_m,
        ?string $curp = null,
        ?string $rfc = null
    ): array {
        // 🔑 Config desde archivo config/google.php
        $cfg = require __DIR__ . '/../config/google.php';
        $API_KEY = $cfg['google']['api_key'] ?? null;
        $CX      = $cfg['google']['cx'] ?? null;

        if (!$API_KEY || !$CX) {
            return ['ok' => false, 'error' => 'Faltan credenciales Google API'];
        }

        $q = urlencode($nombreCompleto);
        $url = "https://www.googleapis.com/customsearch/v1?key={$API_KEY}&cx={$CX}&q={$q}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $resp = curl_exec($ch);
        curl_close($ch);

        if (!$resp) {
            return ['ok' => false, 'error' => 'Error al llamar Google API'];
        }

        $data = json_decode($resp, true);
        if (!isset($data['items'])) {
            $data['items'] = [];
        }

        // normalización (quita acentos y hace lowercase)
        $normalize = fn($s) => preg_replace(
            '/\p{Mn}/u',
            '',
            \Normalizer::normalize(mb_strtolower($s, 'UTF-8'), \Normalizer::FORM_D)
        );

        // variantes válidas
        $var1 = trim("$nombreSolo $apellido_p $apellido_m");     // Nombre + apellidos
        $var2 = trim("$apellido_p $apellido_m $nombreSolo");     // Apellidos + nombre
        $variantes = [$normalize($var1), $normalize($var2)];

        // filtrar resultados
        $resultados = [];
        foreach ($data['items'] as $item) {
            $texto = $normalize(($item['title'] ?? '') . ' ' . ($item['snippet'] ?? ''));
            foreach ($variantes as $v) {
                if (strpos($texto, $v) !== false) {
                    $resultados[] = [
                        'titulo'  => $item['title'] ?? '',
                        'link'    => $item['link'] ?? '',
                        'snippet' => $item['snippet'] ?? '',
                    ];
                    break;
                }
            }
        }

        // clasificación básica
        $clasificacion = count($resultados) > 0 ? 'match_alto' : 'sin_evidencia';
        $status        = count($resultados) > 0 ? 'ok' : 'no_data';

        // Guardar en BD
        $sql = "INSERT INTO validaciones_legal
            (id_inquilino, nombre, apellido_p, apellido_m, curp, rfc,
             query_usada, resultado, clasificacion, status, searched_at)
            VALUES (:id_inq, :nombre, :ap_p, :ap_m, :curp, :rfc,
             :query, :resultado, :clasif, :status, NOW())";
        $this->execute($sql, [
            ':id_inq'    => $idInquilino,
            ':nombre'    => $nombreSolo,
            ':ap_p'      => $apellido_p,
            ':ap_m'      => $apellido_m,
            ':curp'      => $curp,
            ':rfc'       => $rfc,
            ':query'     => json_encode(['variante1' => $var1, 'variante2' => $var2], JSON_UNESCAPED_UNICODE),
            ':resultado' => json_encode($resultados, JSON_UNESCAPED_UNICODE),
            ':clasif'    => $clasificacion,
            ':status'    => $status,
        ]);

        // 🔧 Si no hubo resultados, actualizar snapshot en inquilinos_validaciones
        if (count($resultados) === 0) {
            $sqlUpd = "UPDATE inquilinos_validaciones
                   SET proceso_inv_demandas = 1,
                       inv_demandas_resumen = '⚖️ No se encontraron coincidencias jurídicas para este inquilino.',
                       updated_at = NOW()
                   WHERE id_inquilino = :id LIMIT 1";
            $this->execute($sqlUpd, [':id' => $idInquilino]);
        }

        return [
            'ok'           => true,
            'query'        => $nombreCompleto,
            'variantes'    => [$var1, $var2],
            'total'        => count($resultados),
            'resultados'   => $resultados,
            'clasificacion' => $clasificacion,
            'status'       => $status
        ];
    }

    public function actualizarProcesoDemandas(int $idInquilino, int $estado): bool
    {
        $sql = "UPDATE inquilinos_validaciones 
            SET proceso_inv_demandas = :estado, updated_at = NOW()
            WHERE id_inquilino = :id";
        $this->execute($sql, [
            ':estado' => $estado,
            ':id'     => $idInquilino,
        ]);

        return true;
    }

    public function getProcesoDemandas(int $idInquilino): int
    {
        $sql = "SELECT proceso_inv_demandas 
            FROM inquilinos_validaciones 
            WHERE id_inquilino = :id LIMIT 1";
        $row = $this->fetch($sql, [':id' => $idInquilino]);
        return $row ? (int) $row['proceso_inv_demandas'] : 1; // 1 = No iniciado
    }

    public function obtenerValidaciones(int $idInquilino): array
    {
        $sql = "SELECT * FROM inquilinos_validaciones WHERE id_inquilino = :id LIMIT 1";
        return $this->fetch($sql, [':id' => $idInquilino]) ?? [];
    }



    /**
     * Trae el historial de búsquedas previas.
     */
    public function obtenerHistorialPorInquilino(int $idInquilino): array
    {
        $sql = "SELECT * FROM validaciones_legal
                WHERE id_inquilino = ?
                ORDER BY searched_at DESC";
        return $this->fetchAll($sql, [$idInquilino]);
    }

    /**
     * Trae el último reporte de un inquilino.
     */
    public function obtenerUltimoReportePorInquilino(int $idInquilino): ?array
    {
        $sql = "SELECT * FROM validaciones_legal
                WHERE id_inquilino = ?
                ORDER BY searched_at DESC, id DESC
                LIMIT 1";
        return $this->fetch($sql, [$idInquilino]);
    }
}
