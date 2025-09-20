<?php
require_once __DIR__ . '/../../Helpers/TextHelper.php';

use App\Helpers\TextHelper;

/**
 * Vista: Dashboard
 * - KPIs (total inquilinos nuevos, última póliza)
 * - Inquilinos nuevos (solo 4 tarjetas + enlace "Ver más" → /inquilino)
 * - Vencimientos próximos
 *
 * Variables esperadas:
 * - $totalInquilinosNuevos (int)
 * - $ultimaPoliza (string|int)
 * - $inquilinosNuevos (array)
 * - $vencimientosProximos (array)
 * - admin_url(string $path) disponible en helpers (para enlaces internos)
 */
?>

<!-- ========================= KPIs ========================= -->
<section class="hidden md:grid xl:justify-center xl:w-full xl:px-6 grid-cols-1 sm:grid-cols-2 gap-6">
    <!-- KPI: Inquilinos nuevos -->
    <div class="bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl p-3 shadow-[0_8px_32px_0_rgba(31,38,135,0.37)] flex flex-col items-center justify-center gap-1 text-center w-full">
        <div class="flex items-center gap-2 text-indigo-400">
            <div class="p-2 bg-indigo-600 bg-opacity-20 rounded-full">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M8 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p class="text-sm text-white">Inquilinos nuevos</p>
        </div>
        <p class="text-3xl font-bold text-indigo-400"><?= htmlspecialchars((string)$totalInquilinosNuevos) ?></p>
    </div>

    <!-- KPI: Número de pólizas emitidas (última póliza) -->
    <div class="bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl p-3 shadow-[0_8px_32px_0_rgba(31,38,135,0.37)] flex flex-col items-center justify-center gap-1 text-center w-full">
        <div class="flex items-center gap-2 text-indigo-400">
            <div class="p-2 bg-indigo-600 bg-opacity-20 rounded-full">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M17 9V7a4 4 0 00-8 0v2m-2 0h12v10H7V9z" />
                </svg>
            </div>
            <p class="text-sm text-white">Número de Pólizas Emitidas</p>
        </div>
        <p class="text-3xl font-bold text-indigo-400"><?= htmlspecialchars((string)$ultimaPoliza) ?></p>
    </div>
</section>

<!-- ==================== Inquilinos Nuevos (4) ==================== -->
<section class="bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl p-5 shadow-[0_8px_32px_0_rgba(31,38,135,0.37)] mt-10">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-semibold text-indigo-300 flex items-center gap-2">
            <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M12 12c2.21 0 4-1.79 4-4S14.21 4 12 4 8 5.79 8 8s1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
            </svg>
            Inquilinos nuevos
        </h2>

        <!-- Enlace "Ver más" → /inquilino -->
        <a href="<?= admin_url('/inquilino') ?>"
            class="text-sm px-3 py-1.5 rounded-lg bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-400/30 transition">
            Ver más
        </a>
    </div>

    <?php if (!empty($inquilinosNuevos)): ?>
        <?php
        // Tomamos solo 4 para el dashboard
        $inquilinosMostrar = array_slice($inquilinosNuevos, 0, 4);
        ?>
        <!--
            Responsive:
            - sm/md: 1 por fila (grid-cols-1)
            - lg: 2 por fila (lg:grid-cols-2)
            - xl: 4 por fila (xl:grid-cols-4)
        -->
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-6">
            <?php foreach ($inquilinosMostrar as $inq): ?>
                <?php $selfie = $inq['selfie_url'] ?? null; ?>
                <div class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-2xl shadow-xl p-5 flex flex-col items-center hover:scale-[1.025] transition-all group relative">

                    <!-- Foto / Avatar -->
                    <?php if ($selfie): ?>
                        <img src="<?= htmlspecialchars($selfie) ?>"
                            alt="Selfie de <?= htmlspecialchars($inq['nombre_inquilino'] ?? '') ?>"
                            class="w-20 h-20 object-cover rounded-full shadow-lg ring-4 ring-indigo-700/30 mb-3">
                    <?php else: ?>
                        <span class="w-20 h-20 flex items-center justify-center rounded-full bg-indigo-600/20 text-indigo-400 text-4xl font-bold mb-3 shadow ring-2 ring-indigo-700/40">
                            <?= strtoupper(mb_substr((string)($inq['nombre_inquilino'] ?? ''), 0, 1, 'UTF-8')) ?>
                        </span>
                    <?php endif; ?>

                    <!-- Nombre completo -->
                    <span class="text-xl font-bold text-indigo-200 mb-1 text-center">
                        <?= ucwords(trim(($inq['nombre_inquilino'] ?? '') . ' ' . ($inq['apellidop_inquilino'] ?? '') . ' ' . ($inq['apellidom_inquilino'] ?? ''))) ?>
                    </span>

                    <!-- Badges: NUEVO + TIPO con color -->
                    <?php
                    $tipo = strtolower((string)($inq['tipo'] ?? ''));
                    // Mapa de colores por tipo
                    $estilosTipo = [
                        'inquilino'          => 'bg-blue-600/20 text-blue-300',    // alias Arrendatario
                        'arrendatario'       => 'bg-blue-600/20 text-blue-300',
                        'fiador'             => 'bg-yellow-600/20 text-yellow-300',
                        'obligado solidario' => 'bg-pink-600/20 text-pink-300',
                    ];
                    $claseTipo = $estilosTipo[$tipo] ?? 'bg-gray-600/20 text-gray-300';
                    ?>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:gap-2 mb-2">
                        <span class="px-3 py-1 rounded-full bg-green-600/20 text-green-400 text-xs font-bold shadow animate-pulse">
                            Nuevo
                        </span>
                        <span class="px-3 py-1 rounded-full <?= $claseTipo ?> text-xs font-bold shadow mt-1 sm:mt-0">
                            <?= strtoupper($tipo) ?>
                        </span>
                    </div>

                    <!-- Datos de contacto -->
                    <div class="flex flex-col items-center gap-1 text-indigo-100 text-sm mb-4">
                        <div class="flex items-center gap-2">
                            <!-- Email -->
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0v.243
                                      a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91
                                      a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                            <span class="truncate max-w-[140px]"><?= htmlspecialchars((string)($inq['email'] ?? '')) ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- Teléfono -->
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25
                                         a2.25 2.25 0 0 0 2.25-2.25v-1.372
                                         c0-.516-.351-.966-.852-1.091l-4.423-1.106
                                         c-.44-.11-.902.055-1.173.417l-.97 1.293
                                         c-.282.376-.769.542-1.21.38
                                         a12.035 12.035 0 0 1-7.143-7.143
                                         c-.162-.441.004-.928.38-1.21l1.293-.97
                                         c.363-.271.527-.734.417-1.173L6.963 3.102
                                         a1.125 1.125 0 0 0-1.091-.852H4.5
                                         A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                            </svg>
                            <?= htmlspecialchars((string)($inq['celular'] ?? '')) ?>
                        </div>
                    </div>

                    <!-- Acción: Ver inquilino -->
                    <div class="flex gap-2 mt-auto">
                        <a href="<?= admin_url('/inquilino/' . ($inq['slug'] ?? '')) /* Si tu ruta ya es /inquilino/<slug>, cámbiala aquí */ ?>"
                            class="flex items-center gap-1 px-3 py-1 bg-indigo-700 hover:bg-indigo-500 rounded transition text-white text-xs shadow"
                            title="Ver detalles del inquilino">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M15 12H9m12 0A9 9 0 1 1 3 12a9 9 0 0 1 18 0Z" />
                            </svg>
                            Ver
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center text-gray-400 py-10">No hay inquilinos nuevos.</div>
    <?php endif; ?>
</section>

<!-- ==================== Vencimientos Próximos ==================== -->
<section class="bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl p-5 shadow-[0_8px_32px_0_rgba(31,38,135,0.37)] mt-10">
    <h2 class="text-lg font-semibold text-indigo-300 mb-4">Vencimientos próximos</h2>


    <div class="overflow-x-auto">
        <table class="min-w-full table-auto">
            <thead>
                <tr class="text-indigo-300 text-left">
                    <th class="py-2 px-4">Inquilino</th>
                    <th class="py-2 px-4">Propiedad</th>
                    <th class="py-2 px-4">Fecha de vencimiento</th>
                    <th class="py-2 px-4">Días restantes</th>
                    <th class="py-2 px-4">Acciones</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($vencimientosProximos as $v): ?>
                    <?php
                    $nombreInquilino = $v['nombre_inquilino_completo'] ?? '—';
                    $direccion       = $v['direccion_inmueble'] ?? '—';

                    $fechaVencimiento = null;
                    if (!empty($v['fecha_fin'])) {
                        try {
                            $fechaVencimiento = new DateTime((string)$v['fecha_fin']);
                        } catch (\Throwable $e) {
                            $fechaVencimiento = null;
                        }
                    }

                    if ($fechaVencimiento === null) {
                        $mes  = $v['mes_vencimiento'] ?? '';
                        $anio = $v['year_vencimiento'] ?? '';

                        if ($mes !== '' && $anio !== '') {
                            $mesFormateado = str_pad((string)$mes, 2, '0', STR_PAD_LEFT);
                            $fechaConstruida = sprintf('%s-%s-01', (string)$anio, $mesFormateado);

                            try {
                                $fechaVencimiento = new DateTime($fechaConstruida);
                            } catch (\Throwable $e) {
                                $fechaVencimiento = null;
                            }
                        }
                    }

                    if ($fechaVencimiento instanceof DateTimeInterface) {
                        $fechaFormateada = TextHelper::titleCase($fechaVencimiento->format('d/m/Y'));
                        $hoy = new DateTime();
                        $diasRestantes = $hoy->diff($fechaVencimiento)->days;
                        if ($fechaVencimiento < $hoy) {
                            $dias = '-' . $diasRestantes;
                        } else {
                            $dias = (string)$diasRestantes;
                        }
                    } else {
                        $fechaFormateada = '—';
                        $dias = '—';
                    }
                    ?>
                    <tr class="border-b border-gray-700 hover:bg-gray-700 transition">
                        <td class="py-2 px-4"><?= TextHelper::titleCase((string)$nombreInquilino) ?></td>
                        <td class="py-2 px-4"><?= TextHelper::titleCase((string)$direccion) ?></td>
                        <td class="py-2 px-4"><?= htmlspecialchars($fechaFormateada) ?></td>
                        <td class="py-2 px-4">
                            <span class="px-3 py-1 rounded-full bg-red-600 bg-opacity-20 text-red-500 text-xs font-bold">
                                <?= htmlspecialchars((string)$dias) ?>
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex flex-wrap gap-2 justify-center md:justify-start">
                                <a href="<?= admin_url('/polizas/' . ($v['numero_poliza'] ?? '')) ?>"
                                    class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-lg shadow transition duration-200">
                                    Ver
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>

        </table>
    </div>
</section>