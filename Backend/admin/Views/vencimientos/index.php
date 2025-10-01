<?php
require_once __DIR__ . '/../../Helpers/TextHelper.php';

use App\Helpers\TextHelper;
/**
 * Vista: Vencimientos / index.php
 * ------------------------------------------------------------------
 * - Muestra las pólizas que vencen en un mes/año seleccionados.
 * - Incluye filtro por mes y año.
 * - Diseño en tema oscuro elegante, consistente con el resto del backend.
 * - Tarjetas responsivas con badges y CTA "Ver detalle".
 * - Totalmente documentado con comentarios para fácil mantenimiento.
 *
 * Variables esperadas (inyectadas por el Controller):
 *  - string $baseUrl
 *  - int    $mesSeleccionado
 *  - int    $anioSeleccionado
 *  - array  $polizas  (cada item contiene al menos:
 *          numero_poliza, serie_poliza, tipo_poliza, estado, vigencia,
 *          mes_vencimiento, year_vencimiento, monto_renta, monto_poliza,
 *          nombre_arrendador, nombre_inquilino_completo, nombre_asesor,
 *          nombre_fiador, direccion, tipo_inmueble)
 *
 * Dependencias visuales:
 *  - TailwindCSS (ya presente en el layout general)
 */

$meses = [
    1 => 'Enero',
    2 => 'Febrero',
    3 => 'Marzo',
    4 => 'Abril',
    5 => 'Mayo',
    6 => 'Junio',
    7 => 'Julio',
    8 => 'Agosto',
    9 => 'Septiembre',
    10 => 'Octubre',
    11 => 'Noviembre',
    12 => 'Diciembre',
];

/**
 * Helper local (defensivo):
 * Si no existe estadoPolizaTexto() en helpers globales, usamos fallback.
 */
if (!function_exists('estadoPolizaTexto')) {
    function estadoPolizaTexto($estado)
    {
        $map = [
            'VIGENTE'   => 'Vigente',
            'CANCELADA' => 'Cancelada',
            'RENOVADA'  => 'Renovada',
            'VENCIDA'   => 'Vencida',
            'PENDIENTE' => 'Pendiente'
        ];
        $up = strtoupper((string)$estado);
        return $map[$up] ?? ($estado ?: 'Desconocido');
    }
}

/**
 * Helper para badge de estado (coloriza según estado).
 */
function claseBadgeEstado($estado)
{
    $up = strtoupper((string)$estado);
    return match ($up) {
        'VIGENTE'   => 'from-emerald-600/90 to-teal-600/80',
        'RENOVADA'  => 'from-indigo-600/90 to-blue-600/80',
        'VENCIDA'   => 'from-rose-700/90 to-pink-700/80',
        'CANCELADA' => 'from-gray-600/90 to-slate-600/80',
        'PENDIENTE' => 'from-amber-600/90 to-orange-600/80',
        default     => 'from-slate-600/90 to-slate-700/80',
    };
}

/**
 * Helper de número con formato moneda MX (sin símbolo para reusar).
 */
function money_mx($valor, $decimals = 2)
{
    // evitemos warnings si llega vacío
    if ($valor === '' || $valor === null) return '0.00';
    // si llega tipo texto con comas/$
    $num = floatval(str_replace([',', '$'], '', (string)$valor));
    return number_format($num, $decimals, '.', ',');
}

// Etiquetas de encabezado
$tituloMes  = $meses[$mesSeleccionado] ?? '';
$tituloAnio = (int)($anioSeleccionado ?? date('Y'));
?>

<!-- =========================================================
     Encabezado: Título y filtros
========================================================= -->
<div class="flex flex-col gap-6 mb-8">
    <!-- Título de la página -->
    <!-- Encabezado centrado en móvil -->
    <div class="w-full mb-8">
        <h1 class="text-center text-3xl md:text-4xl font-extrabold leading-tight tracking-tight text-indigo-300">
            <span class="text-pink-400">Encontré</span>
            <span class="mx-1 bg-gradient-to-r from-pink-400 to-indigo-400 bg-clip-text text-transparent">
                <?= count($polizas) ?>
            </span>
            Vencimientos en <?= htmlspecialchars($tituloMes) ?> <?= htmlspecialchars($tituloAnio) ?>
        </h1>
    </div>


    <!-- Bloque de filtros (mes/año) -->
    <form method="get" class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 md:p-5 backdrop-blur-md">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3">
            <!-- Mes -->
            <label class="flex-1">
                <span class="block text-sm text-indigo-300 mb-1">Mes</span>
                <select
                    name="mes"
                    class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-600">
                    <?php foreach ($meses as $num => $nombre): ?>
                        <option value="<?= (int)$num ?>" <?= (int)$num === (int)$mesSeleccionado ? 'selected' : '' ?>>
                            <?= htmlspecialchars($nombre) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <!-- Año -->
            <label class="flex-1">
                <span class="block text-sm text-indigo-300 mb-1">Año</span>
                <select
                    name="anio"
                    class="w-full px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-600">
                    <?php $anioActual = (int) date('Y'); ?>
                    <?php for ($y = $anioActual - 1; $y <= $anioActual + 6; $y++): ?>
                        <option value="<?= $y ?>" <?= (int)$y === (int)$anioSeleccionado ? 'selected' : '' ?>>
                            <?= $y ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </label>

            <!-- Botón Consultar -->
            <div class="sm:w-auto">
                <button
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl shadow transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
                    </svg>
                    Consultar
                </button>
            </div>
        </div>
    </form>
</div>

<?php if (empty($polizas)): ?>
    <!-- =========================================================
         Empty State (sin resultados)
    ========================================================= -->
    <div class="w-full max-w-3xl mx-auto text-center bg-white/5 border border-white/10 rounded-3xl p-10">
        <div class="mx-auto mb-4 w-12 h-12 grid place-items-center rounded-full bg-indigo-600/20">
            <svg class="w-6 h-6 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7V3m8 4V3M5 11h14M5 19h14M5 15h14" />
            </svg>
        </div>
        <h2 class="text-xl font-semibold text-indigo-200">No hay vencimientos para este periodo</h2>
        <p class="text-indigo-300/80 mt-2">Prueba con otro mes/año o verifica los filtros.</p>
    </div>
<?php else: ?>

    <!-- =========================================================
     Grid de tarjetas de pólizas
========================================================= -->
    <div class="grid grid-cols-1 lg:grid-cols-2 2xl:grid-cols-3 gap-6 md:gap-8">
        <?php foreach ($polizas as $poliza): ?>
            <?php
            // Limpieza defensiva y formateos
            $num      = htmlspecialchars($poliza['numero_poliza'] ?? '');
            $serie    = htmlspecialchars($poliza['serie_poliza'] ?? '');
            $tipo     = htmlspecialchars($poliza['tipo_poliza'] ?? '');
            $vigencia = TextHelper::titleCase($poliza['vigencia'] ?? '');
            $estado   = $poliza['estado'] ?? '';

            $renta     = money_mx($poliza['monto_renta'] ?? 0, 0);
            $mpoliza   = money_mx($poliza['monto_poliza'] ?? 0, 2);

            $arrendador = TextHelper::titleCase($poliza['nombre_arrendador'] ?? '');
            $inquilino  = trim((string)($poliza['nombre_inquilino_completo'] ?? ''));
            $inquilino  = $inquilino !== '' ? TextHelper::titleCase($inquilino) : '-';
            $fiador     = TextHelper::titleCase($poliza['nombre_fiador'] ?? 'No aplica');
            $asesor     = TextHelper::titleCase($poliza['nombre_asesor'] ?? '');
            $direccion  = TextHelper::titleCase($poliza['direccion'] ?? '');
            $tipoInm    = TextHelper::titleCase($poliza['tipo_inmueble'] ?? '');

            $fechaVencimiento = $poliza['fecha_vencimiento_normalizada'] ?? null;
            $venceTexto       = $poliza['fecha_vencimiento_formateada'] ?? null;

            if ($fechaVencimiento instanceof DateTimeImmutable) {
                $venceTexto = $fechaVencimiento->format('d/m/Y');
            }

            if (!is_string($venceTexto) || $venceTexto === '') {
                $venceTexto = 'Sin fecha';
            }
            ?>

            <article class="relative w-full max-w-2xl mx-auto bg-gradient-to-br from-indigo-950/80 to-gray-900/90 rounded-3xl shadow-2xl border border-indigo-900/70 p-5 md:p-7 overflow-hidden group transition-all hover:scale-[1.012]">
                <!-- Glow decorativo -->
                <div class="pointer-events-none absolute -top-10 -right-16 w-44 h-44 bg-pink-600/20 rounded-full blur-2xl opacity-60"></div>

                <!-- Header: ID + meta + estado -->
                <header class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div class="flex items-center gap-4">
                        <!-- Avatar del número de póliza -->
                        <div class="bg-white/5 border-2 border-indigo-700 rounded-full h-14 w-14 grid place-items-center shadow-inner">
                            <span class="font-extrabold text-2xl text-indigo-200"><?= $num ?></span>
                        </div>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="bg-pink-600 text-white text-xs font-bold px-3 py-1 rounded-full shadow">
                                    Tipo: <?= $tipo ?>
                                </span>
                                <span class="bg-indigo-900/70 text-indigo-200 text-xs font-semibold px-3 py-1 rounded-full shadow">
                                    #<?= $num ?> · Serie <?= $serie ?>
                                </span>
                            </div>
                            <p class="text-indigo-300 text-xs mt-1 truncate">
                                Vigencia: <?= $vigencia ?>
                            </p>
                        </div>
                    </div>

                    <!-- Badge de estado -->
                    <span class="bg-gradient-to-r <?= claseBadgeEstado($estado) ?> text-white text-xs font-bold px-4 py-1 rounded-xl shadow self-start md:self-auto">
                        <?= htmlspecialchars(estadoPolizaTexto($estado)) ?>
                    </span>
                </header>

                <!-- Fechas y montos -->
                <section class="relative z-10 mt-6 flex flex-wrap items-center gap-3">
                    <span class="bg-pink-500 text-white px-4 py-1 rounded-full text-sm font-bold tracking-wide shadow group-hover:bg-pink-600 transition">
                        Vence: <?= htmlspecialchars($venceTexto) ?>
                    </span>
                    <span class="bg-gray-800 text-pink-200 px-4 py-1 rounded-full text-sm font-semibold">
                        Renta: $<?= $renta ?>
                    </span>
                    <span class="bg-indigo-900 text-indigo-100 px-4 py-1 rounded-full text-sm font-semibold">
                        Póliza: $<?= $mpoliza ?>
                    </span>
                </section>

                <!-- Información relacionada -->
                <dl class="relative z-10 mt-7 grid grid-cols-1 sm:grid-cols-2 gap-y-2 gap-x-6 text-sm">
                    <div class="truncate">
                        <dt class="text-indigo-400 font-semibold">Arrendador</dt>
                        <dd class="text-white"><?= $arrendador ?></dd>
                    </div>

                    <div class="truncate">
                        <dt class="text-indigo-400 font-semibold">Inquilino</dt>
                        <dd class="text-white"><?= $inquilino ?></dd>
                    </div>

                    <div class="truncate">
                        <dt class="text-indigo-400 font-semibold">Fiador</dt>
                        <dd class="text-white"><?= $fiador ?></dd>
                    </div>

                    <div class="truncate">
                        <dt class="text-indigo-400 font-semibold">Asesor</dt>
                        <dd class="text-white"><?= $asesor ?></dd>
                    </div>

                    <div class="sm:col-span-2 truncate">
                        <dt class="text-indigo-400 font-semibold">Inmueble</dt>
                        <dd class="text-white"><?= $direccion ?></dd>
                    </div>

                    <div class="truncate">
                        <dt class="text-indigo-400 font-semibold">Tipo inmueble</dt>
                        <dd class="text-white"><?= $tipoInm ?></dd>
                    </div>
                </dl>

                <!-- Acciones -->
                <footer class="relative z-10 mt-7">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="<?= $baseUrl ?>/polizas/<?= $num ?>"
                            target="_blank"
                            class="inline-flex items-center justify-center gap-2 bg-indigo-700 hover:bg-indigo-600 text-white font-semibold px-4 py-2 rounded-xl shadow transition text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Ver detalle
                        </a>
                    </div>
                </footer>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>