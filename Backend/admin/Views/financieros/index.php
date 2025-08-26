<section class="min-h-screen px-4 md:px-10 py-10 bg-gray-950 text-white">
    <h1 class="text-4xl font-extrabold text-center mb-10 text-indigo-300">
        📊 Reportes Financieros
    </h1>

    <!-- Formulario de selección de mes -->
    <form method="GET" class="flex flex-wrap gap-4 items-end justify-center mb-10">
        <div>
            <label class="block text-sm mb-1 text-indigo-200">Selecciona un mes</label>
            <input type="month" name="mes" value="<?= htmlspecialchars($mesSeleccionado) ?>"
                   class="bg-gray-800 text-white px-4 py-2 rounded-lg border border-indigo-500 shadow">
        </div>
        <button type="submit"
                class="bg-indigo-600 hover:bg-indigo-500 text-white px-5 py-2 rounded-lg shadow font-semibold">
            Consultar
        </button>
    </form>

    <!-- Botón Registrar Nueva Venta -->
    <div class="flex justify-center mb-10">
        <a href="<?= $baseUrl ?>/financieros/registro"
           class="inline-block bg-green-600 hover:bg-green-500 text-white font-semibold px-6 py-3 rounded-xl shadow-lg transition">
            ➕ Registrar nueva venta
        </a>
    </div>

    <?php
        $meses = [
            'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo',
            'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio',
            'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre',
            'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
        ];

        try {
            $fecha = new DateTime($mesSeleccionado);
        } catch (\Throwable $e) {
            $fecha = new DateTime(); // fallback
        }

        $mesTraducido = $meses[$fecha->format('F')] ?? $fecha->format('F');
        $anio = $fecha->format('Y');
    ?>

    <!-- Indicador de mes consultado -->
    <p class="text-center text-indigo-400 mb-6">
        Consulta generada para el mes: <strong><?= "$mesTraducido $anio" ?></strong>
    </p>

    <!-- Cards de resumen -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-12">
        <!-- Ingresos del Mes -->
        <div class="bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl p-4 shadow-[0_8px_32px_0_rgba(31,38,135,0.37)] flex flex-col items-center text-center">
            <div class="flex items-center gap-2 text-indigo-400 mb-1">
                <div class="p-2 bg-indigo-600 bg-opacity-20 rounded-full">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3z"/>
                    </svg>
                </div>
                <span class="text-sm">Ingresos Totales del Mes</span>
            </div>
            <p class="text-3xl font-bold text-indigo-300">$<?= number_format((float)$ingresosMes, 2) ?></p>
        </div>

        <!-- Arrendamiento Seguro -->
        <div class="bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl p-4 shadow-[0_8px_32px_0_rgba(31,38,135,0.37)] flex flex-col items-center text-center">
            <div class="flex items-center gap-2 text-indigo-400 mb-1">
                <div class="p-2 bg-indigo-600 bg-opacity-20 rounded-full">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <span class="text-sm">Arrendamiento Seguro</span>
            </div>
            <p class="text-3xl font-bold text-indigo-300">$<?= number_format((float)$totalArrendamiento, 2) ?></p>
        </div>

        <!-- Inmobiliaria -->
        <div class="bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl p-4 shadow-[0_8px_32px_0_rgba(31,38,135,0.37)] flex flex-col items-center text-center">
            <div class="flex items-center gap-2 text-indigo-400 mb-1">
                <div class="p-2 bg-indigo-600 bg-opacity-20 rounded-full">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M7 17l9-5-9-5v10z"/>
                    </svg>
                </div>
                <span class="text-sm">Inmobiliaria</span>
            </div>
            <p class="text-3xl font-bold text-indigo-300">$<?= number_format((float)$totalInmobiliaria, 2) ?></p>
        </div>

        <!-- Ingresos Acumulados -->
        <div class="bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl p-4 shadow-[0_8px_32px_0_rgba(31,38,135,0.37)] flex flex-col items-center text-center">
            <div class="flex items-center gap-2 text-indigo-400 mb-1">
                <div class="p-2 bg-indigo-600 bg-opacity-20 rounded-full">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M3 3v18h18"/>
                    </svg>
                </div>
                <span class="text-sm">Ingresos Acumulados <?= htmlspecialchars((string)date('Y', strtotime($mesSeleccionado))) ?></span>
            </div>
            <p class="text-3xl font-bold text-indigo-300">$<?= number_format((float)$ingresosAcumulados, 2) ?></p>
        </div>
    </div>

    <!-- Gráfico de Ingresos por Mes -->
    <div class="bg-gray-900 p-6 rounded-xl shadow-xl mb-10 max-w-4xl mx-auto w-full">
        <h2 class="text-xl font-semibold text-indigo-300 mb-4 text-center">Ingresos Mensuales (<?= htmlspecialchars($anioConsulta) ?>)</h2>
        <canvas id="graficaIngresos"></canvas>
    </div>

        <!-- Tabla de ventas del periodo -->
    <div class="bg-gray-900 p-6 rounded-xl shadow-xl max-w-6xl mx-auto w-full">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold text-indigo-300">Ventas del periodo seleccionado</h2>
            <div class="text-sm text-indigo-200">
                <span class="mr-4">Bruto: <strong>$<?= number_format((float)($totalBrutoPeriodo ?? 0), 2) ?></strong></span>
                <span>Neto: <strong>$<?= number_format((float)($totalNetoPeriodo ?? 0), 2) ?></strong></span>
            </div>
        </div>

        <?php if (!empty($ventasPeriodo)): ?>
            <div class="overflow-x-auto rounded-lg border border-white/10">
                <table class="min-w-full text-sm">
                    <thead class="bg-white/5 sticky top-0">
                        <tr class="text-indigo-200 text-left">
                            <th class="px-4 py-3 font-medium">Fecha</th>
                            <th class="px-4 py-3 font-medium">Canal</th>
                            <th class="px-4 py-3 font-medium">Concepto</th>
                            <th class="px-4 py-3 font-medium text-right">Monto</th>
                            <th class="px-4 py-3 font-medium text-right">Comisiones</th>
                            <th class="px-4 py-3 font-medium text-right">Importe Neto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ventasPeriodo as $venta): ?>
                            <tr class="border-t border-white/10 hover:bg-white/5 transition">
                                <td class="px-4 py-3 text-slate-200">
                                    <?= htmlspecialchars(date('d/m/Y', strtotime($venta['fecha_venta']))) ?>
                                </td>
                                <td class="px-4 py-3">
                                    <?php
                                        $canal = (string)$venta['canal_venta'];
                                        $isAS  = trim($canal) === 'Arrendamiento Seguro';
                                        $badge = $isAS ? 'bg-indigo-600/30 text-indigo-300 border-indigo-500/40'
                                                       : 'bg-emerald-600/30 text-emerald-300 border-emerald-500/40';
                                    ?>
                                    <span class="inline-block px-2 py-1 rounded-full text-xs border <?= $badge ?>">
                                        <?= htmlspecialchars($canal) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-200">
                                    <?= htmlspecialchars($venta['concepto_venta']) ?>
                                </td>
                                <td class="px-4 py-3 text-right text-slate-100">
                                    $<?= number_format((float)$venta['monto_venta'], 2) ?>
                                </td>
                                <td class="px-4 py-3 text-right text-slate-100">
                                    $<?= number_format((float)$venta['comision_asesor'], 2) ?>
                                </td>
                                <td class="px-4 py-3 text-right text-slate-100">
                                    $<?= number_format((float)$venta['ganancia_neta'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-white/5">
                        <tr>
                            <td class="px-4 py-3 font-semibold text-indigo-200" colspan="3">Totales del periodo</td>
                            <td class="px-4 py-3 text-right font-semibold text-indigo-200">
                                $<?= number_format((float)($totalBrutoPeriodo ?? 0), 2) ?>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-indigo-200">
                                <!-- suma de comisiones = bruto - neto si aplica 20%, pero mostramos sumatoria real -->
                                <?php
                                    $sumaComisiones = 0.0;
                                    if (!empty($ventasPeriodo)) {
                                        foreach ($ventasPeriodo as $v) { $sumaComisiones += (float)$v['comision_asesor']; }
                                    }
                                ?>
                                $<?= number_format($sumaComisiones, 2) ?>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-indigo-200">
                                $<?= number_format((float)($totalNetoPeriodo ?? 0), 2) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php else: ?>
            <div class="bg-white/5 border border-white/10 rounded-lg p-6 text-center text-slate-300">
                No hay ventas registradas para este periodo.
            </div>
        <?php endif; ?>
    </div>


</section>



<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('graficaIngresos').getContext('2d');

    const mesesES = {'01':'Enero','02':'Febrero','03':'Marzo','04':'Abril','05':'Mayo','06':'Junio','07':'Julio','08':'Agosto','09':'Septiembre','10':'Octubre','11':'Noviembre','12':'Diciembre'};

    const ingresosPorMes = <?= json_encode($ingresosPorMes ?? []) ?>;

    const labels = ingresosPorMes.map(item => {
        const mm = String(item.mes).padStart(2, '0');
        return mesesES[mm] ?? mm;
    });

    const dataValues = ingresosPorMes.map(item => Math.round((item.total ?? 0) * 100) / 100);

    const data = {
        labels: labels,
        datasets: [{
            label: 'Ingresos por Mes ($MXN)',
            data: dataValues,
            backgroundColor: 'rgba(99, 102, 241, 0.5)',
            borderColor: 'rgba(99, 102, 241, 1)',
            borderWidth: 2,
            borderRadius: 8,
        }]
    };

    new Chart(ctx, {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            try { return '$' + Number(value).toLocaleString('es-MX'); }
                            catch { return '$' + value; }
                        },
                        color: '#cbd5e1'
                    },
                    grid: { color: 'rgba(148,163,184,0.2)' }
                },
                x: {
                    ticks: { color: '#cbd5e1' },
                    grid: { display: false }
                }
            }
        }
    });
</script>
