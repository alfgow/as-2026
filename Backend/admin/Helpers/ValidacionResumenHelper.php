<?php
namespace App\Helpers;

/**
 * Genera resúmenes humanos (legibles) para inquilinos_validaciones.
 * Convención proceso: 0 = NO_OK ✖️, 1 = OK ✔️, 2 = PEND. ⏳
 */
class ValidacionResumenHelper
{
    /* ========== UTILIDADES ========== */

    public static function semaforo(int $p): string {
        return $p === 1 ? '✔️' : ($p === 0 ? '✖️' : '⏳');
    }

    public static function procesoTexto(int $p): string {
        return $p === 1 ? 'OK' : ($p === 0 ? 'NO_OK' : 'PENDIENTE');
    }

    public static function yesNo(bool $v, string $ok='Sí', string $no='No'): string {
        return $v ? $ok : $no;
    }

    public static function joinTipos(array $tipos): string {
        // "selfie, ine_frontal, comprobante_ingreso (2)" -> orden alfabético, compactar repetidos
        $counts = [];
        foreach ($tipos as $t) {
            $t = trim((string)$t);
            if ($t === '') continue;
            $counts[$t] = ($counts[$t] ?? 0) + 1;
        }
        ksort($counts);
        $out = [];
        foreach ($counts as $k => $n) {
            $out[] = $n > 1 ? "{$k} ({$n})" : $k;
        }
        return implode(', ', $out);
    }

    public static function fmtMoney(?float $n): string {
        if ($n === null) return '—';
        // Formato MX: $12,345.67
        return '$' . number_format($n, 2, '.', ',');
    }

    public static function fmtPct(float $v): string {
        return number_format($v * 100, 0) . '%';
    }

    public static function monthNameEs(?string $mmYYYY): string {
        if (!$mmYYYY || !preg_match('/^(0[1-9]|1[0-2])-(\d{4})$/', $mmYYYY, $m)) return '—';
        $meses = [1=>'enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
        $mes = (int)$m[1]; $y = $m[2];
        return $meses[$mes] . ' ' . $y;
    }

    /* ========== 1) ARCHIVOS ========== */
    public static function archivos(int $proceso, array $payload): string {
        // payload esperado: ['archivos'=>['selfie'=>..,'ine_frontal'=>.., 'comprobantes'=>[...]] ...] O lista
        $tipos = [];
        if (isset($payload['archivos'])) {
            foreach ($payload['archivos'] as $k => $v) {
                if ($k === 'comprobantes' && is_array($v)) {
                    foreach ($v as $_) $tipos[] = 'comprobante_ingreso';
                } else {
                    $tipos[] = $k;
                }
            }
        } elseif (isset($payload['items'])) {
            foreach ($payload['items'] as $it) $tipos[] = $it['tipo'] ?? 'desconocido';
        }
        $total = count($tipos);
        $lista = self::joinTipos($tipos);
        return sprintf('%s Archivos: %d encontrado(s): %s.', self::semaforo($proceso), $total, $lista ?: '—');
    }

    /* ========== 2) ROSTRO (CompareFaces) ========== */
    public static function rostroCompareFaces(int $proceso, array $payload): string {
        // payload: ['best'=>['similarity'=>..,'confidence'=>..], 'threshold'=>..]
        $thr = (int)($payload['threshold'] ?? 90);
        $sim = (float)($payload['best']['similarity'] ?? 0);
        $conf = (float)($payload['best']['confidence'] ?? 0);
        return sprintf('%s Rostro: similitud %.1f%% (umbral %d%%), confianza %.1f%%.',
            self::semaforo($proceso), $sim, $thr, $conf
        );
    }

    /* ========== 3) IDENTIDAD (Textract FORMS -> nombres/apellidos) ========== */
    public static function identidadNombres(int $proceso, array $payload): string {
        // payload: ['overall'=>bool, 'ocr'=>['nombres','apellidop','apellidom'], 'bd'=>...]
        $ocrN = trim($payload['ocr']['nombres'] ?? '');
        $ocrP = trim($payload['ocr']['apellidop'] ?? '');
        $ocrM = trim($payload['ocr']['apellidom'] ?? '');
        $overall = $payload['overall'] ?? false;
        return sprintf('%s Identidad (nombres): %s. OCR: %s %s %s.',
            self::semaforo($proceso),
            self::yesNo($overall, 'coincide con BD', 'no coincide con BD'),
            $ocrP ?: '—', $ocrM ?: '—', $ocrN ?: '—'
        );
    }

    /* ========== 4) IDENTIDAD (CURP/CIC/Vigencia desde OCR) ========== */
    public static function identidadCurpCic(int $proceso, array $parsed): string {
        // parsed: ['curp'=>?, 'cic'=>?, 'vigencia'=>?]
        $curp = $parsed['curp'] ?? '—';
        $cic  = $parsed['cic'] ?? '—';
        $vig  = $parsed['vigencia'] ?? '—';
        return sprintf('%s Identidad (INE): CURP %s, CIC %s, Vigencia %s.',
            self::semaforo($proceso), $curp, $cic, $vig
        );
    }

    /* ========== 5) DOCUMENTOS OCR (INE, Forma Migratoria, Pasaporte) ========== */
    public static function docsOcr(int $proceso, array $payload): string {
        // payload: ['doc_type'=>'ine|forma_migratoria|pasaporte', 'frontal'=>['lineas'], 'reverso'=>['lineas'], 'alt'=>['lineas','tipo']]
        $docType = $payload['doc_type'] ?? 'ine';
        $icon = self::semaforo($proceso);
        if ($docType === 'ine' || $docType === 'forma_migratoria') {
            $lf = (int)($payload['frontal']['lineas'] ?? 0);
            $lr = (int)($payload['reverso']['lineas'] ?? 0);
            $docName = ($docType === 'ine') ? 'INE' : 'Forma Migratoria';
            return sprintf('%s %s OCR: %d líneas frente, %d reverso.', $icon, $docName, $lf, $lr);
        } else {
            $la = (int)($payload['alt']['lineas'] ?? 0);
            return sprintf('%s Pasaporte OCR: %d líneas detectadas.', $icon, $la);
        }
    }

    /* ========== 6) INGRESOS (listado simple) ========== */
    public static function ingresosList(int $proceso, array $payload): string {
        // payload: ['total'=>int]
        $n = (int)($payload['total'] ?? 0);
        return sprintf('%s Comprobantes de ingreso: %d documento(s) listado(s).',
            self::semaforo($proceso), $n
        );
    }

    /* ========== 7) INGRESOS (regla simple por conteo) ========== */
    public static function ingresosSimple(int $proceso, array $payload): string {
        // payload: ['conteo'=>n, 'status'=>'OK|REVIEW|FAIL']
        $n = (int)($payload['conteo'] ?? 0);
        $st = strtoupper((string)($payload['status'] ?? ''));
        return sprintf('%s Ingresos (simple): %d PDF(s) → %s.',
            self::semaforo($proceso), $n, $st ?: '—'
        );
    }

    /* ========== 8) INGRESOS (OCR avanzado) ========== */
    public static function ingresosOcr(int $proceso, array $payload): string {
        // payload: {
        //   'status','metricas'=>['meses_en_rango6', 'montos_detectados'=>[], 'sueldo_declarado', 'consistencia_monto', 'docs_total'],
        // }
        $st = strtoupper((string)($payload['status'] ?? ''));
        $m6 = (int)($payload['metricas']['meses_en_rango6'] ?? 0);
        $docs = (int)($payload['metricas']['docs_total'] ?? 0);
        $sueldo = $payload['metricas']['sueldo_declarado'] ?? null;
        $cons = $payload['metricas']['consistencia_monto'] ?? null;

        $sueldoTxt = $sueldo !== null ? self::fmtMoney((float)$sueldo) : '—';
        $consTxt = ($cons === null) ? 'sin comparación'
                 : ($cons ? 'consistente (±20%)' : 'inconsistente (±20%)');

        return sprintf('%s Ingresos (OCR): %s. %d mes(es) válidos en 6 meses, %d doc(s), sueldo %s → %s.',
            self::semaforo($proceso), $st ?: '—', $m6, $docs, $sueldoTxt, $consTxt
        );
    }

    /* ========== 9) DOCUMENTOS (checklist genérico) ========== */
    public static function documentos(int $proceso, array $payload): string {
        // payload: ['faltantes'=>[], 'observaciones'=>string]
        $falt = $payload['faltantes'] ?? [];
        $obs  = trim($payload['observaciones'] ?? '');
        $txtF = empty($falt) ? 'Sin faltantes' : ('Faltan: ' . implode(', ', $falt));
        $obs  = $obs ? (' Obs: ' . $obs) : '';
        return sprintf('%s Documentos: %s.%s',
            self::semaforo($proceso), $txtF, $obs
        );
    }

    /* ========== 10) PAGO INICIAL ========== */
    public static function pagoInicial(int $proceso, array $payload): string {
        // payload: ['monto'=>float, 'fecha'=>'YYYY-MM-DD', 'referencia'=>string]
        $monto = isset($payload['monto']) ? self::fmtMoney((float)$payload['monto']) : '—';
        $fecha = $payload['fecha'] ?? '—';
        $ref   = $payload['referencia'] ?? '—';
        return sprintf('%s Pago inicial: %s el %s (ref. %s).',
            self::semaforo($proceso), $monto, $fecha, $ref
        );
    }

    /* ========== 11) INVESTIGACIÓN DE DEMANDAS ========== */
    public static function invDemandas(int $proceso, array $payload): string {
        // payload: ['hit'=>bool, 'fuentes'=>[], 'folio'=>string]
        $hit = (bool)($payload['hit'] ?? false);
        $fuentes = $payload['fuentes'] ?? [];
        $folio = $payload['folio'] ?? '—';
        $fTxt = empty($fuentes) ? 'sin fuentes' : implode(', ', $fuentes);
        return sprintf('%s Investigación de demandas: %s (%s). Folio %s.',
            self::semaforo($proceso),
            $hit ? 'se hallaron antecedentes' : 'sin antecedentes',
            $fTxt,
            $folio
        );
    }

    /* ========== 12) STATUS GLOBAL (resumen tablero) ========== */
    public static function statusGlobal(array $semaforos): string {
        // $semaforos: ['documentos'=>int,'archivos'=>int,'rostro'=>int,'identidad'=>int,'ingresos'=>int,'pago_inicial'=>int,'demandas'=>int]
        $map = [
            'documentos'   => 'Docs',
            'archivos'     => 'Arch',
            'rostro'       => 'Rostro',
            'identidad'    => 'ID',
            'ingresos'     => 'Ingresos',
            'pago_inicial' => 'Pago',
            'demandas'     => 'Demandas',
        ];
        $parts = [];
        foreach ($map as $k => $etq) {
            if (!array_key_exists($k, $semaforos)) continue;
            $p = (int)$semaforos[$k];
            $parts[] = sprintf('%s %s', self::semaforo($p), $etq);
        }
        return 'Resumen: ' . implode(' · ', $parts);
    }
}