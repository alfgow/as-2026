<?php

use App\Helpers\TextHelper;

$h       = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$nombreCompleto = trim(
    ($inquilino['nombre_inquilino'] ?? '') . ' ' .
        ($inquilino['apellidop_inquilino'] ?? '') . ' ' .
        ($inquilino['apellidom_inquilino'] ?? '')
);
$nombre = $h($nombreCompleto ?: 'Nombre Apellido');
$slug    = $h($inquilino['slug'] ?? 'slug-ejemplo');
$idInq   = (int)($inquilino['id'] ?? 0);
$helperBaseUrl = null;
if (function_exists('admin_base_url')) {
    $helperBaseUrl = admin_base_url();
} elseif (function_exists('admin_url')) {
    $helperBaseUrl = admin_url();
}

if (!is_string($helperBaseUrl) || trim($helperBaseUrl) === '') {
    $helperBaseUrl = '/';
}

$ADMIN_BASE = $admin_base_url ?? $helperBaseUrl;
if (!is_string($ADMIN_BASE) || trim($ADMIN_BASE) === '') {
    $ADMIN_BASE = $helperBaseUrl;
}

$ADMIN_BASE = rtrim($ADMIN_BASE, '/');
$resolvedBaseUrl = $baseUrl ?? $helperBaseUrl;
$resolvedBaseUrl = rtrim((string) $resolvedBaseUrl, '/');
if ($resolvedBaseUrl === '') {
    $resolvedBaseUrl = '/';
}
$idInquilino = (int)($inquilino['id'] ?? $idInquilino ?? 0);
$apP         = $inquilino['apellidop_inquilino'] ?? '';
$apM         = $inquilino['apellidom_inquilino'] ?? '';
$curp        = $inquilino['curp'] ?? null;
$rfc         = $inquilino['rfc'] ?? null;
$slug        = $inquilino['slug'] ?? ($slug ?? null);
$tipoId = strtolower(trim($inquilino['tipo_id'] ?? ''));

$byType = [];
foreach ($archivos ?? [] as $archivoItem) {
    $tipoArchivo = strtolower((string)($archivoItem['tipo'] ?? ''));
    if ($tipoArchivo === '') {
        continue;
    }
    $byType[$tipoArchivo][] = $archivoItem;
}

function archivoPrimer(array $list = null): ?array
{
    if (!$list) {
        return null;
    }
    return $list[0] ?? null;
}

$selfie         = archivoPrimer($byType['selfie'] ?? []);
$ineFrontal     = archivoPrimer($byType['ine_frontal'] ?? []);
$ineReverso     = archivoPrimer($byType['ine_reverso'] ?? []);
$pasaporte      = archivoPrimer($byType['pasaporte'] ?? []);
$formaMigratoria = archivoPrimer($byType['forma_migratoria'] ?? []);
$comprobantes   = $byType['comprobante_ingreso'] ?? [];
function archivoId(?array $archivo): string
{
    if (!$archivo) {
        return '';
    }
    return (string)($archivo['id'] ?? $archivo['sk'] ?? '');
}

function archivo_label(array $archivo = null, string $fallback = 'Archivo'): string
{
    if (!$archivo) {
        return $fallback;
    }
    return $archivo['nombre_original'] ?? $fallback;
}

function estadoLabel(int $estado): string
{
    return match ($estado) {
        1       => 'Confirmado',
        0       => 'No OK',
        default => 'Pendiente',
    };
}

$estadoValidaciones = [
    'archivos'      => (int)($validaciones['archivos']['proceso'] ?? 2),
    'rostro'        => (int)($validaciones['rostro']['proceso'] ?? 2),
    'identidad'     => (int)($validaciones['identidad']['proceso'] ?? 2),
    'documentos'    => (int)($validaciones['documentos']['proceso'] ?? 2),
    'ingresos'      => (int)($validaciones['ingresos']['proceso'] ?? 2),
    'pago_inicial'  => (int)($validaciones['pago_inicial']['proceso'] ?? 2),
    'demandas'      => (int)($validaciones['demandas']['proceso'] ?? 2),
    'verificamex'   => (int)($validaciones['verificamex']['proceso'] ?? 2),
];

$tipoIdLower = strtolower((string)($inquilino['tipo_id'] ?? ''));
$isIne       = str_contains($tipoIdLower, 'ine') || str_contains($tipoIdLower, 'ife');
$isPassport  = str_contains($tipoIdLower, 'pasaporte') || str_contains($tipoIdLower, 'passport');
$isFm        = str_contains($tipoIdLower, 'fm') || str_contains($tipoIdLower, 'forma');

$verificamexValidacion = $validaciones['verificamex'] ?? [];
if (!is_array($verificamexValidacion)) {
    $verificamexValidacion = [];
}

$verificamexResumen = trim((string)($verificamexValidacion['resumen'] ?? ''));
$verificamexJson    = $verificamexValidacion['json'] ?? null;
if (is_string($verificamexJson)) {
    $decodedJson = json_decode($verificamexJson, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $verificamexValidacion['json'] = $decodedJson;
    }
}

$hasVerificamexData = $verificamexResumen !== ''
    || (is_array($verificamexValidacion['json'] ?? null) && !empty($verificamexValidacion['json']))
    || (is_string($verificamexJson) && trim($verificamexJson) !== '')
    || array_key_exists('proceso', $verificamexValidacion);

$showVerificamex = $isIne || $hasVerificamexData;

$visibleIne = $isIne || (!$isPassport && !$isFm) || $ineFrontal || $ineReverso;
$visiblePassport = (!$isIne && ($isPassport || $pasaporte));
$visibleFm = (!$isIne && ($isFm || $formaMigratoria));

function renderArchivoTag(string $key, string $label, bool $visible, bool $hasFile): void
{
    if (!$visible) {
        return;
    }

    $base = 'rounded-full px-3 py-1 text-xs font-semibold transition-colors border';
    $class = $hasFile
        ? $base . ' border-emerald-400/40 bg-emerald-400/15 text-emerald-100'
        : $base . ' border-rose-400/30 bg-rose-400/10 text-rose-200';

    echo '<span data-key="' . htmlspecialchars($key) . '" class="' . $class . '">' . htmlspecialchars($label) . '</span>';
}

function chipColor($valor)
{
    return match ((int)$valor) {
        1       => 'rounded-full border px-3 py-1 text-xs border-emerald-400/30 bg-emerald-400/15',  // OK
        0       => 'rounded-full border px-3 py-1 text-xs border-rose-400/30 bg-rose-400/15',    // NO_OK
        default => 'rounded-full border px-3 py-1 text-xs border-amber-400/30 bg-amber-400/15', // PENDIENTE
    };
}
?>

<script src="https://cdn.tailwindcss.com"></script>
<style>
    @keyframes shimmer {
        0% {
            background-position: -450px 0
        }

        100% {
            background-position: 450px 0
        }
    }

    .skel {
        animation: shimmer 1.2s linear infinite;
        background: linear-gradient(to right, rgba(255, 255, 255, .06) 8%, rgba(255, 255, 255, .12) 18%, rgba(255, 255, 255, .06) 33%);
        background-size: 800px 104px;
    }
</style>

<!-- 👇 container mobile-first + sin overflow lateral -->
<div id="validaciones-app" class="mx-auto w-full max-w-screen-2xl px-3 sm:px-4 lg:px-6 text-slate-100 overflow-x-hidden">

    <!-- HERO -->
    <section class="grid gap-4 md:grid-cols-1">

        <!-- Card superior (nombre + estatus validaciones) -->
        <div class="rounded-2xl border border-white/10 bg-white/5 p-6 shadow-xl backdrop-blur flex flex-col items-center justify-center text-center">
            <div class="text-xl font-bold tracking-tight" id="vh-nombre"><?= TextHelper::titleCase($nombre) ?></div>
            <!-- Select estilizado -->
            <select id="select-status"
                class="my-4 ml-2 rounded-lg border border-white/10 bg-gray-800 text-slate-200 px-3 py-1 text-sm font-medium
                    focus:outline-none focus:ring-2 focus:ring-indigo-400 hover:bg-gray-700 transition">
                <option value="1" <?= ($inquilino['status'] ?? 1) == 1 ? 'selected' : '' ?>>Nuevo</option>
                <option value="2" <?= ($inquilino['status'] ?? 1) == 2 ? 'selected' : '' ?>>Aprobado</option>
                <option value="3" <?= ($inquilino['status'] ?? 1) == 3 ? 'selected' : '' ?>>En Proceso</option>
                <option value="4" <?= ($inquilino['status'] ?? 1) == 4 ? 'selected' : '' ?>>Rechazado</option>
            </select>
            <div class="mt-1 text-sm text-slate-400 break-words">
                🦖 Estatus de validaciones:
            </div>
            <div class="mt-3 flex flex-wrap justify-center gap-2">
                <span id="pill-archivos" class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-sm"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Archivos</span>
                <span id="pill-rostro" class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-sm"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Rostro</span>
                <span id="pill-identidad" class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-sm"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Identidad</span>
                <?php if ($showVerificamex): ?>
                    <span id="pill-verificamex" class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-sm"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Verificamex</span>
                <?php endif; ?>
                <span id="pill-ingresos" class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-sm"><span class="h-2 w-2 rounded-full bg-rose-500"></span>Ingresos</span>
                <span id="pill-pago" class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-sm"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Pago inicial</span>
                <span id="pill-demandas" class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-sm"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Demandas</span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full border border-white/10 bg-white/10 mt-5">
                <span id="vh-progress" class="block h-full bg-gradient-to-r from-cyan-400 to-indigo-400" style="width:64%"></span>
            </div>
            <div id="vh-progress-text" class="mt-2 text-sm text-slate-300">0 de 7 validaciones completas</div>
        </div>

        <!-- Card inferior (contenedor de sub-cards) -->
        <div class="grid gap-4 md:grid-cols-2">

            <!-- Card: Validación de Archivos y Pago Inicial -->
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 shadow-xl backdrop-blur">

                <!-- Sección Archivos -->
                <h3 class="mb-3 text-base font-semibold text-white">Archivos Recibidos</h3>

                <!-- Chips -->
                <div id="chips-archivos" class="flex flex-wrap justify-center sm:justify-start gap-2 mb-4">
                    <?php
                    renderArchivoTag('selfie', 'Selfie', true, (bool)$selfie);
                    renderArchivoTag('ine_frontal', 'INE - frontal', $visibleIne, (bool)$ineFrontal);
                    renderArchivoTag('ine_reverso', 'INE - reverso', $visibleIne, (bool)$ineReverso);
                    renderArchivoTag('pasaporte', 'Pasaporte', $visiblePassport, (bool)$pasaporte);
                    renderArchivoTag('fm', 'FM2/FM3', $visibleFm, (bool)$formaMigratoria);
                    renderArchivoTag('comprobante_ingreso', 'Comprobantes', true, count($comprobantes) > 0);
                    ?>
                </div>

                <!-- Switch Archivos -->
                <div class="flex items-center gap-3 mb-6">
                    <span class="text-sm text-slate-300">Validación Archivos</span>
                    <label class="inline-flex items-center cursor-pointer relative">
                        <input
                            id="toggle-archivos"
                            type="checkbox"
                            class="sr-only peer"
                            onchange="window.saveSwitch('archivos')"
                            <?= $estadoValidaciones['archivos'] === 1 ? 'checked' : '' ?> />
                        <!-- Track -->
                        <div class="w-11 h-6 bg-gray-600 rounded-full peer-checked:bg-emerald-500 transition"></div>
                        <!-- Knob -->
                        <div class="absolute left-0.5 top-0.5 h-5 w-5 bg-white rounded-full transition-transform peer-checked:translate-x-5"></div>
                    </label>
                    <em id="toggle-archivos-label" class="not-italic text-xs text-slate-400 peer-checked:text-emerald-400">
                        <?= estadoLabel($estadoValidaciones['archivos']) ?>
                    </em>
                </div>

                <!-- Divider -->
                <div class="border-t border-white/10 my-4"></div>

                <!-- Sección Pago inicial -->
                <h3 class="text-base font-semibold text-white">Pago Inicial</h3>

                <!-- Switch Pago Inicial -->
                <div class="mt-3 flex items-center gap-3">
                    <span class="text-sm text-slate-300">Pago Recibido?</span>
                    <label class="inline-flex items-center cursor-pointer relative">
                        <input
                            id="toggle-pago_inicial"
                            type="checkbox"
                            class="peer sr-only"
                            onchange="window.saveSwitch('pago_inicial')"
                            <?= $estadoValidaciones['pago_inicial'] === 1 ? 'checked' : '' ?> />
                        <!-- track -->
                        <div
                            class="relative h-7 w-12 rounded-full border border-white/10 bg-white/10 shadow-inner
                                transition-colors duration-300 ease-out
                                focus-within:outline-none focus-within:ring-2 focus-within:ring-fuchsia-500/40
                                peer-checked:bg-gradient-to-r peer-checked:from-fuchsia-500/60 peer-checked:to-indigo-500/60

                                /* knob (pseudo) */
                                after:absolute after:top-1 after:left-1 after:h-5 after:w-5 after:rounded-full after:bg-slate-200
                                after:shadow after:transition-all after:duration-300 after:ease-out
                                peer-checked:after:translate-x-5 peer-checked:after:bg-white">
                        </div>
                    </label>
                    <em id="toggle-pago_inicial-label" class="not-italic text-xs text-slate-400 peer-checked:text-emerald-400">
                        <?= estadoLabel($estadoValidaciones['pago_inicial']) ?>
                    </em>
                </div>

                <div id="pago-status-msg" class="mt-2 text-xs text-slate-400"></div>

                <!-- Botones -->
                <div class="mt-3 grid grid-cols-1 gap-2 sm:auto-cols-max sm:grid-flow-col">
                    <button class="vh-detalle w-full sm:w-auto rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15" data-cat="pago_inicial">
                        Ver detalle
                    </button>
                </div>
            </div>

            <!-- Sub-card: Ingresos -->
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 shadow-xl backdrop-blur mt-6">
                <h3 class="mb-2 text-sm font-semibold">Validación de Ingresos</h3>

                <!-- Datos resumidos -->
                <div class="mb-4 text-sm space-y-1">
                    <p><span class="font-semibold text-slate-300">Ingreso declarado:</span>
                        <span id="ingreso-declarado" class="text-emerald-400">
                            <?= number_format((float)($inquilino['trabajo']['sueldo'] ?? 0), 2) ?>
                        </span>
                    </p>
                    <p><span class="font-semibold text-slate-300">Ingreso calculado:</span>
                        <span id="ingreso-calculado" class="text-indigo-400">
                            <!-- este se llena por JS tras OCR -->
                        </span>
                    </p>
                    <p><span class="font-semibold text-slate-300">Diferencia:</span>
                        <span id="ingreso-diferencia" class="text-rose-400">
                            <!-- este se llena por JS tras OCR -->
                        </span>
                    </p>
                </div>

                <!-- Switch -->
                <div class="flex items-center justify-start gap-2 mb-4">
                    <span class="text-sm">Ingresos Validados</span>
                    <label class="inline-flex items-center cursor-pointer relative">
                        <input
                            id="toggle-ingresos"
                            type="checkbox"
                            class="sr-only peer"
                            onchange="window.saveSwitch('ingresos')"
                            <?= $estadoValidaciones['ingresos'] === 1 ? 'checked' : '' ?> />
                        <!-- Track -->
                        <div class="w-11 h-6 bg-gray-600 rounded-full peer-checked:bg-emerald-500 transition"></div>
                        <!-- Knob -->
                        <div class="absolute left-0.5 top-0.5 h-5 w-5 bg-white rounded-full transition-transform peer-checked:translate-x-5"></div>
                    </label>
                    <em id="toggle-ingresos-label"
                        class="not-italic text-xs text-slate-400 peer-checked:text-emerald-400">
                        <?= estadoLabel($estadoValidaciones['ingresos']) ?>
                    </em>
                </div>

                <!-- Botones -->
                <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <button class="vh-detalle rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15"
                        data-cat="ingresos">
                        Ver detalle
                    </button>
                    <button class="vh-recalc rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15"
                        data-check="ingresos_ocr" disabled>
                        *Disabled - Procesar OCR
                    </button>
                </div>
            </div>

        </div>
    </section>

    <!-- GRID VALIDACIONES -->
    <section class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">


        <!-- VerificaMex -->
        <?php if ($showVerificamex): ?>
            <!-- VerificaMex -->
            <div class="w-full max-w-full overflow-hidden rounded-2xl border border-white/10 bg-white/5 p-4 shadow-xl backdrop-blur" data-cat="verificamex">
                <h3 class="text-base font-semibold text-white">Validación INE</h3>

                <!-- Switch -->
                <div class="mt-3 flex items-center gap-3">
                    <span class="text-sm text-slate-300">Estatus Valicación INE</span>
                    <label class="inline-flex items-center cursor-pointer relative">
                        <input
                            id="toggle-verificamex"
                            type="checkbox"
                            class="sr-only peer"
                            onchange="window.saveSwitch('verificamex')"
                            <?= $estadoValidaciones['verificamex'] === 1 ? 'checked' : '' ?> />
                        <!-- Track -->
                        <div class="w-11 h-6 bg-gray-600 rounded-full peer-checked:bg-emerald-500 transition"></div>
                        <!-- Knob -->
                        <div class="absolute left-0.5 top-0.5 h-5 w-5 bg-white rounded-full transition-transform peer-checked:translate-x-5"></div>
                    </label>
                    <em id="toggle-verificamex-label" class="not-italic text-xs text-slate-400 peer-checked:text-emerald-400">
                        <?= estadoLabel($estadoValidaciones['verificamex']) ?>
                    </em>
                </div>

                <!-- Resumen humano -->
                <p id="txt-verificamex" class="vh-scroll mt-2 pr-2 text-sm text-slate-300 break-words w-full">
                    <?= $verificamexResumen !== '' ? nl2br($h($verificamexResumen)) : '🚫No hay información aún de VerificaMex🚫' ?>
                </p>

                <!-- Botones -->
                <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <button class="vh-detalle rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15" data-cat="verificamex">
                        Ver detalle
                    </button>
                    <button class="vh-recalc rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15" data-check="verificamex">
                        Procesar
                    </button>

                </div>
            </div>
            <?php if (!empty($verificamexValidacion)): ?>
                <script>
                    window.__VH_DETALLES__ = window.__VH_DETALLES__ || {};
                    if (!window.__VH_DETALLES__.verificamex) {
                        window.__VH_DETALLES__.verificamex = <?= json_encode($verificamexValidacion, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
                    }
                </script>
            <?php endif; ?>
        <?php endif; ?>


        <!-- Validacion de Rostro=ID -->
        <div class="min-w-0">
            <!-- Rostro -->
            <div class="w-full max-w-full overflow-hidden rounded-2xl border border-white/10 bg-white/5 p-4 shadow-xl backdrop-blur" data-cat="rostro">
                <h3 class="text-base font-semibold">Rostro</h3>
                <div id="chips-rostro" class="mt-2 flex flex-wrap justify-center sm:justify-start gap-2 text-center">
                    <?php $proceso_validacion_rostro = $estadoValidaciones['rostro']; ?>
                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?= chipColor($proceso_validacion_rostro) ?>">
                        <?= $proceso_validacion_rostro === 1 ? '✅ OK' : ($proceso_validacion_rostro === 0 ? '❌ NO_OK' : '⏳ PENDIENTE') ?>
                    </span>

                    <p id="txt-rostro" class="mt-2 max-h-24 overflow-y-auto pr-1 text-sm text-slate-300 break-words w-full">
                        🚫No hay información, actualiza el perfil del inquilino🚫.
                    </p>
                </div>
                <div class="mt-2 flex items-center gap-2 justify-start">
                    <span class="text-sm text-slate-300">Confirmar rostro</span>
                    <label class="inline-flex items-center cursor-pointer relative">
                        <input
                            id="toggle-rostro"
                            type="checkbox"
                            class="sr-only peer"
                            onchange="window.saveSwitch('rostro')"
                            <?= $estadoValidaciones['rostro'] === 1 ? 'checked' : '' ?> />
                        <div class="w-11 h-6 bg-gray-600 rounded-full peer-checked:bg-emerald-500 transition"></div>
                        <div class="absolute left-0.5 top-0.5 h-5 w-5 bg-white rounded-full transition-transform peer-checked:translate-x-5"></div>
                    </label>
                    <em id="toggle-rostro-label" class="not-italic text-xs text-slate-400 peer-checked:text-emerald-400">
                        <?= estadoLabel($estadoValidaciones['rostro']) ?>
                    </em>
                </div>
                <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <button class="vh-detalle rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15" data-cat="rostro">
                        Ver detalle
                    </button>
                    <!-- Botón dinámico si es proceso verificamex -->
                    <?php if (!$showVerificamex): ?>
                        <button class="vh-recalc rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15" data-check="save_face">
                            Volver a comparar
                        </button>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <div class="min-w-0">
            <!-- Identidad -->
            <div class="w-full max-w-full overflow-hidden rounded-2xl border border-white/10 bg-white/5 p-4 shadow-xl backdrop-blur" data-cat="identidad">
                <h3 class="text-base font-semibold">Identidad</h3>

                <div id="chips-identidad" class="mt-2 flex flex-wrap justify-center sm:justify-start gap-2 text-center">
                    <?php $proceso_validacion_id = $estadoValidaciones['identidad']; ?>
                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?= chipColor($proceso_validacion_id) ?>">
                        <?= $proceso_validacion_id === 1 ? '✅ OK' : ($proceso_validacion_id === 0 ? '❌ NO_OK' : '⏳ PENDIENTE') ?>
                    </span>
                    <p id="txt-identidad" class="vh-scroll mt-2 pr-2 text-sm text-slate-300 break-words w-full">
                        🚫No hay información, actualiza el perfil del inquilino🚫.
                    </p>
                </div>
                <div class="mt-2 flex items-center gap-2 justify-start">
                    <span class="text-sm text-slate-300">Confirmar identidad</span>
                    <label class="inline-flex items-center cursor-pointer relative">
                        <input
                            id="toggle-identidad"
                            type="checkbox"
                            class="sr-only peer"
                            onchange="window.saveSwitch('identidad')"
                            <?= $estadoValidaciones['identidad'] === 1 ? 'checked' : '' ?> />
                        <div class="w-11 h-6 bg-gray-600 rounded-full peer-checked:bg-emerald-500 transition"></div>
                        <div class="absolute left-0.5 top-0.5 h-5 w-5 bg-white rounded-full transition-transform peer-checked:translate-x-5"></div>
                    </label>
                    <em id="toggle-identidad-label" class="not-italic text-xs text-slate-400 peer-checked:text-emerald-400">
                        <?= estadoLabel($estadoValidaciones['identidad']) ?>
                    </em>
                </div>

                <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <button class="vh-detalle rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15" data-cat="identidad">
                        Ver detalle
                    </button>
                    <?php if (!$showVerificamex): ?>
                        <button class="vh-recalc rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15" data-check="save_match">
                            Leer CURP/CIC
                        </button>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <!-- Aquí continuarías con Documentos, Ingresos, Pago inicial y Demandas repitiendo el patrón -->
    </section>

    <div id="vh-meta"
        data-id="<?= $idInquilino ?>"
        data-nombre="<?= htmlspecialchars($nombre) ?>"
        data-apellido_p="<?= htmlspecialchars($apP) ?>"
        data-apellido_m="<?= htmlspecialchars($apM) ?>"
        data-curp="<?= htmlspecialchars($curp ?? '') ?>"
        data-rfc="<?= htmlspecialchars($rfc ?? '') ?>"
        data-slug="<?= htmlspecialchars($slug ?? '') ?>">
    </div>

    <!-- Sección de Demandas y Litigios -->
    <section id="cardJuridico" class="bg-gray-900 border border-white/10 rounded-2xl p-6 shadow-xl my-6">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-3">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-indigo-600/30 rounded-xl">
                    <svg class="w-6 h-6 text-indigo-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M3 7h18M3 12h18M3 17h18" />
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-white">Demandas y litigios</h2>
            </div>
            <div class="flex flex-col sm:flex-row gap-2">
                <button id="btnRunValidacion"
                    class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white shadow">
                    Ejecutar validación ahora
                </button>
                <button id="btnVerUltimo"
                    class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white">
                    Ver último reporte
                </button>
                <div class="flex flex-col sm:flex-row gap-2 items-center">
                    <label for="toggle-demandas" class="flex items-center cursor-pointer">
                        <div class="relative">
                            <!-- Checkbox real (oculto) -->
                            <input id="toggle-demandas" type="checkbox"
                                class="sr-only peer"
                                data-id="<?= htmlspecialchars($inquilino['id'] ?? 0) ?>"
                                <?= $estadoValidaciones['demandas'] === 1 ? 'checked' : '' ?>>

                            <!-- Fondo del switch -->
                            <div class="w-14 h-8 bg-gray-600 rounded-full peer-checked:bg-green-500 transition-colors"></div>

                            <!-- Bolita del switch -->
                            <div class="absolute left-1 top-1 w-6 h-6 bg-white rounded-full transition-transform peer-checked:translate-x-6"></div>
                        </div>

                        <!-- Texto al lado -->
                        <span class="ml-3 text-sm text-gray-300">Demandas</span>
                    </label>
                </div>

            </div>
        </div>

        <!-- Resumen jurídico -->
        <div id="juridicoResumen" class="bg-white/5 rounded-xl p-4 text-sm text-gray-200 mb-4">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-lg font-semibold text-white">Resumen jurídico</h3>
                <span id="juridicoStatus" class="text-sm text-gray-300">cargando…</span>
            </div>
            <div id="juridicoEvidencias" class="space-y-3">
                <!-- Aquí se mostrarán los resultados filtrados de Google -->
            </div>
        </div>

        <!-- Último reporte (resultados Google) -->
        <div id="reporteContainer" class="bg-white/5 rounded-xl p-4 text-sm text-gray-200 hidden mb-6">
            <!-- Se llena con JS -->
        </div>

        <!-- Historial de validaciones -->
        <div id="historialContainer">
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M12 20l9-5-9-5-9 5 9 5z" />
                    <path d="M12 12V4l9-5-9-5-9 5z" />
                </svg>
                Historial de validaciones
            </h3>
            <?php if (!empty($historial)): ?>
                <div class="overflow-x-auto rounded-xl border border-gray-700">
                    <table class="min-w-full text-sm text-left text-gray-300">
                        <thead class="bg-gray-800 text-gray-400 uppercase text-xs">
                            <tr>
                                <th class="px-4 py-2">Fecha</th>
                                <th class="px-4 py-2">Clasificación</th>
                                <th class="px-4 py-2">Estatus</th>
                                <th class="px-4 py-2">Resultados</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700 bg-gray-900">
                            <?php foreach ($historial as $item): ?>
                                <tr>
                                    <td class="px-4 py-2">
                                        <?= htmlspecialchars($item['searched_at'] ?? '-') ?>
                                    </td>
                                    <td class="px-4 py-2">
                                        <span class="px-2 py-1 rounded-full text-xs 
                                            <?= $item['clasificacion'] === 'match_alto' ? 'bg-red-600 text-white' : ($item['clasificacion'] === 'posible_match' ? 'bg-yellow-400 text-black' :
                                                'bg-green-600 text-white') ?>">
                                            <?= htmlspecialchars($item['clasificacion'] ?? 'sin_evidencia') ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2">
                                        <span class="px-2 py-1 rounded-full text-xs
                                            <?= $item['status'] === 'ok' ? 'bg-emerald-600 text-white' : ($item['status'] === 'error' ? 'bg-red-600 text-white' : ($item['status'] === 'manual_required' ? 'bg-amber-500 text-black' :
                                                'bg-slate-600 text-white')) ?>">
                                            <?= htmlspecialchars($item['status'] ?? '-') ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-gray-400">
                                        <?php
                                        $resultados = [];
                                        if (!empty($item['resultado'])) {
                                            $decoded = json_decode($item['resultado'], true);
                                            if (json_last_error() === JSON_ERROR_NONE) {
                                                $resultados = $decoded;
                                            }
                                        }
                                        echo count($resultados) > 0
                                            ? count($resultados) . ' coincidencia(s)'
                                            : '⚠️ Sin resultados';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center text-gray-400">
                    ⚠️ No hay registros de validaciones legales previas para este inquilino.
                </div>
            <?php endif; ?>



        </div>


    </section>


    <!-- ARCHIVOS (previews) -->
    <section class="my-8">
        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 shadow-xl backdrop-blur">
            <h3 class="text-base font-semibold">Archivos (previsualización)</h3>

            <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <?php
                $renderImage = function (?array $archivo, string $label, string $tipo) {
                    $url = $archivo['url'] ?? null;
                    $title = $archivo['nombre_original'] ?? $label;
                    $archivoId = archivoId($archivo);
                    $hasFile = (bool)$url;
                ?>
                    <div class="archivo-card flex flex-col gap-2" data-tipo="<?= htmlspecialchars($tipo) ?>"
                        data-archivo-id="<?= htmlspecialchars($archivoId) ?>"
                        data-accept="image/*" data-label="<?= htmlspecialchars($label) ?>">
                        <div class="relative h-48 overflow-hidden rounded-2xl border border-white/10 bg-white/5">
                            <?php if ($hasFile): ?>
                                <img src="<?= htmlspecialchars($url) ?>" alt="<?= htmlspecialchars($label) ?>"
                                    class="h-full w-full object-contain bg-black/40 cursor-zoom-in"
                                    onclick="openArchivoModal('image', '<?= htmlspecialchars($url) ?>', '<?= htmlspecialchars($title) ?>')">
                            <?php else: ?>
                                <div class="flex h-full items-center justify-center text-sm text-slate-400">Sin archivo</div>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span><?= htmlspecialchars($label) ?></span>
                            <div class="flex items-center gap-2">
                                <button type="button" data-action="replace"
                                    class="rounded-xl border border-white/10 bg-white/10 px-3 py-1.5 font-semibold <?= $hasFile ? '' : '' ?>">
                                    <?= $hasFile ? 'Reemplazar' : 'Subir' ?>
                                </button>
                                <?php if ($hasFile): ?>
                                    <button type="button" data-action="delete"
                                        class="rounded-xl border border-rose-500/40 bg-rose-500/10 px-3 py-1.5 font-semibold text-rose-200 hover:bg-rose-500/20">
                                        Eliminar
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <input type="file" class="hidden archivo-input" accept="image/*">
                    </div>
                <?php };

                $renderPdf = function (?array $archivo, string $label, string $tipo, string $emptyLabel = 'Subir') {
                    $url = $archivo['url'] ?? null;
                    $title = $archivo['nombre_original'] ?? $label;
                    $hasFile = (bool) $url;
                    $actionLabel = $hasFile ? 'Reemplazar' : $emptyLabel;
                    $archivoId = archivoId($archivo);
                ?>
                    <div class="archivo-card flex flex-col gap-2" data-tipo="<?= htmlspecialchars($tipo) ?>"
                        data-archivo-id="<?= htmlspecialchars($archivoId) ?>" data-accept="application/pdf" data-label="<?= htmlspecialchars($label) ?>">
                        <div class="grid h-48 place-items-center rounded-2xl border border-white/10 bg-white/5 text-slate-300">
                            <?php if ($hasFile): ?>
                                <button type="button"
                                    class="flex flex-col items-center gap-2 text-slate-200 hover:text-white"
                                    onclick="openArchivoModal('pdf', '<?= htmlspecialchars($url) ?>', '<?= htmlspecialchars($title) ?>')">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.5 2.25H9.5L4.5 6.75v10.5c0 1.242 1.008 2.25 2.25 2.25h10.5c1.242 0 2.25-1.008 2.25-2.25V6.75l-5-4.5z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14h6m-6-3h6M9.75 2.25v4.5h4.5v-4.5" />
                                    </svg>
                                    <span class="text-sm">Ver PDF</span>
                                </button>
                            <?php else: ?>
                                <span class="text-sm">PDF</span>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span><?= htmlspecialchars($label) ?></span>
                            <div class="flex items-center gap-2">
                                <button type="button" data-action="replace"
                                    class="rounded-xl border border-white/10 bg-white/10 px-3 py-1.5 font-semibold <?= $hasFile ? '' : '' ?>">
                                    <?= htmlspecialchars($actionLabel) ?>
                                </button>
                                <?php if ($hasFile): ?>
                                    <button type="button" data-action="delete"
                                        class="rounded-xl border border-rose-500/40 bg-rose-500/10 px-3 py-1.5 font-semibold text-rose-200 hover:bg-rose-500/20">
                                        Eliminar
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <input type="file" class="hidden archivo-input" accept="application/pdf">
                    </div>
                <?php };

                $renderImage($selfie, 'Selfie', 'selfie');
                $renderImage($ineFrontal, 'INE — frontal', 'ine_frontal');
                $renderImage($ineReverso, 'INE — reverso', 'ine_reverso');

                $renderPdf($comprobantes[0] ?? null, 'Comprobante 1', 'comprobante_ingreso');
                $renderPdf($comprobantes[1] ?? null, 'Comprobante 2', 'comprobante_ingreso');
                $renderPdf($comprobantes[2] ?? null, 'Comprobante 3', 'comprobante_ingreso', 'Falta');
                ?>
            </div>
        </div>
    </section>

    <section class="my-12 flex justify-center">
        <a href="<?= $baseUrl ?>/inquilino/<?= htmlspecialchars($inquilino['slug']) ?>/validar-identidad/resultado"
            target="_blank"
            class="px-6 py-3 bg-lime-400 text-black font-semibold rounded-xl shadow-lg 
              hover:bg-lime-700 hover:text-white transition text-lg">
            📄 Generar Resumen
        </a>
    </section>



    <!-- Modal JSON: bottom-sheet móvil, centrado desktop -->
    <div id="archivo-preview-modal" class="fixed inset-0 z-50 hidden bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="relative w-full max-w-5xl bg-slate-900/95 border border-indigo-400/20 rounded-2xl shadow-2xl overflow-hidden flex flex-col">
            <button type="button" id="archivo-preview-close" class="absolute top-3 right-3 p-2 rounded-full bg-black/40 hover:bg-indigo-500/40 transition" aria-label="Cerrar">
                <svg class="w-5 h-5 text-slate-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <div class="flex-1 flex items-center justify-center bg-black/30 p-4">
                <img id="archivo-preview-img" class="max-h-[75vh] max-w-full hidden object-contain rounded-xl" alt="Previsualización">
                <iframe id="archivo-preview-pdf" class="w-full h-[75vh] hidden rounded-xl border-none bg-black" src=""></iframe>
            </div>
            <div id="archivo-preview-caption" class="px-4 py-3 text-sm text-slate-200 border-t border-indigo-400/10"></div>
        </div>
    </div>

    <!-- Modal JSON: bottom-sheet móvil, centrado desktop -->
    <div id="vh-modal"
        class="fixed inset-0 z-50 hidden opacity-0
            bg-black/60 p-2 sm:p-4 overflow-x-hidden
            flex items-center sm:items-center justify-center
            transition-opacity duration-200">

        <!-- overlay clickeable -->
        <div id="vh-modal-overlay" class="absolute inset-0"></div>

        <!-- caja -->
        <div id="vh-modal-box"
            class="relative mx-auto w-full max-w-full sm:max-w-3xl
              rounded-2xl border border-white/10 bg-slate-900/70 p-4 shadow-2xl backdrop-blur
              transition-transform duration-200 ease-out
              translate-y-3 sm:translate-y-0
              flex flex-col">
            <div class="flex items-center justify-between gap-3">
                <h3 id="vh-modal-title" class="text-base font-semibold">Detalle</h3>
                <button type="button"
                    class="rounded-xl border border-white/10 bg-white/10 px-3 py-1.5 font-semibold hover:bg-white/15"
                    onclick="cerrarVHModal()">Cerrar</button>
            </div>

            <div class="mt-3 max-h-[70vh] overflow-y-auto rounded-xl border border-white/5 bg-black/30 p-3">
                <pre id="vh-modal-pre" class="whitespace-pre-wrap break-words text-xs leading-relaxed text-slate-200"></pre>
            </div>
        </div>
    </div>
    <!-- Loader principal -->
    <div id="vh-loader" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 hidden">
        <div class="flex flex-col items-center gap-3 text-slate-200">
            <!-- Spinner -->
            <svg class="animate-spin h-10 w-10 text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
            <span class="text-sm">Cargando validaciones...</span>
        </div>
    </div>
    <script>
        // Contexto global de validaciones
        window.baseUrl = <?= json_encode($resolvedBaseUrl) ?>;
        window.ADMIN_BASE = <?= json_encode($ADMIN_BASE) ?>;

        // 👇 aseguramos que siempre se definan correctamente
        window.ID_INQ = <?= (int)($inquilino['id'] ?? 0) ?>;
        window.SLUG = <?= json_encode($inquilino['slug'] ?? '') ?>;

        // Objeto unificado de contexto
        window.VH_CTX = {
            baseUrl: window.baseUrl,
            adminBase: window.ADMIN_BASE,
            idInq: window.ID_INQ,
            slug: window.SLUG
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= asset_url('validaciones-core.js') ?>"></script>
    <script src="<?= asset_url('validaciones-archivos.js') ?>"></script>
    <script src="<?= asset_url('validaciones-rostro.js') ?>"></script>
    <script src="<?= asset_url('validaciones-identidad.js') ?>"></script>
    <script src="<?= asset_url('validaciones-pago.js') ?>"></script>
    <script src="<?= asset_url('validaciones-modal.js') ?>"></script>
    <script src="<?= asset_url('validaciones-botones.js') ?>"></script>
    <script src="<?= asset_url('validaciones-demandas.js') ?>"></script>
    <script>
        (function() {
            const modal = document.getElementById('archivo-preview-modal');
            const img = document.getElementById('archivo-preview-img');
            const pdf = document.getElementById('archivo-preview-pdf');
            const caption = document.getElementById('archivo-preview-caption');
            const closeBtn = document.getElementById('archivo-preview-close');

            function resetPreview() {
                if (img) {
                    img.classList.add('hidden');
                    img.removeAttribute('src');
                }
                if (pdf) {
                    pdf.classList.add('hidden');
                    pdf.removeAttribute('src');
                }
                if (caption) {
                    caption.textContent = '';
                }
            }

            function closeModal() {
                if (!modal) return;
                resetPreview();
                modal.classList.add('hidden');
            }

            window.closeArchivoModal = closeModal;

            window.openArchivoModal = function(tipo, url, titulo) {
                if (!modal || !url) {
                    return;
                }
                resetPreview();

                if (tipo === 'pdf') {
                    if (pdf) {
                        pdf.src = url;
                        pdf.classList.remove('hidden');
                    }
                } else if (img) {
                    img.src = url;
                    img.classList.remove('hidden');
                }

                if (caption) {
                    caption.textContent = titulo || '';
                }

                modal.classList.remove('hidden');
            };

            closeBtn?.addEventListener('click', closeModal);
            modal?.addEventListener('click', function(event) {
                if (event.target === modal) {
                    closeModal();
                }
            });

            function showLoading(msg) {
                Swal.fire({
                    title: msg || 'Procesando...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => Swal.showLoading(),
                    background: '#18181b',
                    color: '#fff'
                });
            }

            function showSuccess(msg) {
                Swal.fire({
                    icon: 'success',
                    title: msg || '¡Listo!',
                    timer: 1400,
                    showConfirmButton: false,
                    background: '#18181b',
                    color: '#fff'
                });
            }

            function showError(msg) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: msg || 'Ocurrió un error',
                    confirmButtonColor: '#de6868',
                    background: '#18181b',
                    color: '#fff'
                });
            }

            async function subirArchivo(card, file) {
                const tipo = card.dataset.tipo;
                if (!tipo) {
                    showError('Tipo de archivo no definido');
                    return;
                }

                const fd = new FormData();
                fd.append('id_inquilino', window.ID_INQ || 0);
                fd.append('tipo', tipo);
                fd.append('archivo', file);

                const archivoId = card.dataset.archivoId || '';
                let endpoint = '/inquilino/subir-archivo';
                if (archivoId) {
                    fd.append('archivo_id', archivoId);
                    endpoint = '/inquilino/reemplazar_archivo';
                }

                try {
                    showLoading('Subiendo archivo...');
                    const resp = await fetch((window.ADMIN_BASE || '') + endpoint, {
                        method: 'POST',
                        body: fd
                    });
                    const data = await resp.json().catch(() => ({}));
                    Swal.close();
                    if (!resp.ok || !data.ok) {
                        throw new Error(data.error || data.mensaje || `HTTP ${resp.status}`);
                    }
                    showSuccess('Archivo actualizado');
                    setTimeout(() => window.location.reload(), 900);
                } catch (err) {
                    Swal.close();
                    showError(err.message);
                }
            }

            async function eliminarArchivo(card) {
                const archivoId = card.dataset.archivoId || '';
                if (!archivoId) {
                    showError('No hay archivo para eliminar');
                    return;
                }

                const confirm = await Swal.fire({
                    title: '¿Eliminar archivo?',
                    text: 'Esta acción no se puede deshacer.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    background: '#18181b',
                    color: '#fff',
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#4b5563'
                });

                if (!confirm.isConfirmed) {
                    return;
                }

                const fd = new FormData();
                fd.append('id_inquilino', window.ID_INQ || 0);
                fd.append('archivo_id', archivoId);

                try {
                    showLoading('Eliminando archivo...');
                    const resp = await fetch((window.ADMIN_BASE || '') + '/inquilino/eliminar_archivo', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await resp.json().catch(() => ({}));
                    Swal.close();
                    if (!resp.ok || !data.ok) {
                        throw new Error(data.error || data.mensaje || `HTTP ${resp.status}`);
                    }
                    showSuccess('Archivo eliminado');
                    setTimeout(() => window.location.reload(), 900);
                } catch (err) {
                    Swal.close();
                    showError(err.message);
                }
            }

            function bindArchivoCards() {
                const cards = document.querySelectorAll('.archivo-card');
                cards.forEach((card) => {
                    const replaceBtn = card.querySelector('[data-action="replace"]');
                    const deleteBtn = card.querySelector('[data-action="delete"]');
                    const input = card.querySelector('.archivo-input');
                    const accept = card.dataset.accept || '';

                    if (input && accept) {
                        input.setAttribute('accept', accept);
                    }

                    replaceBtn?.addEventListener('click', () => {
                        if (!input) return;
                        input.value = '';
                        input.click();
                    });

                    input?.addEventListener('change', () => {
                        if (!input.files || !input.files.length) return;
                        const file = input.files[0];
                        subirArchivo(card, file);
                    });

                    deleteBtn?.addEventListener('click', () => {
                        eliminarArchivo(card);
                    });
                });
            }

            bindArchivoCards();
        })();
    </script>