
<section class="min-h-screen px-4 md:px-10 py-10 bg-gray-950 text-white">
    <h1 class="text-3xl font-extrabold text-center text-indigo-300 mb-10">
        ➕ Registrar Nueva Venta
    </h1>

    <form method="POST" action="<?= $baseUrl ?>/financieros/store"
          class="bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl shadow-xl p-6 max-w-3xl mx-auto space-y-6">

        <!-- Canal de venta -->
        <div>
            <label class="block mb-1 text-indigo-200">Canal de Venta</label>
            <select name="canal_venta" required
                    class="w-full bg-gray-800 text-white px-4 py-2 rounded-lg border border-indigo-500">
                <option value="">Selecciona una opción</option>
                <option value="Arrendamiento Seguro">Arrendamiento Seguro</option>
                <option value="Inmobiliaria: Renta">Inmobiliaria: Renta</option>
                <option value="Inmobiliaria: Venta">Inmobiliaria: Venta</option>
            </select>
        </div>

        <!-- Concepto -->
        <div>
            <label class="block mb-1 text-indigo-200">Concepto de Venta</label>
            <input type="text" name="concepto_venta" required
                   class="w-full bg-gray-800 text-white px-4 py-2 rounded-lg border border-indigo-500"
                   placeholder="Ej. Comisión de renta mensual">
        </div>

        <!-- Monto -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block mb-1 text-indigo-200">Monto Total</label>
                <input type="number" name="monto_venta" step="0.01" required
                       class="w-full bg-gray-800 text-white px-4 py-2 rounded-lg border border-indigo-500">
            </div>
            <div>
                <label class="block mb-1 text-indigo-200">Comisión Asesor</label>
                <input type="number" name="comision_asesor" step="0.01" required
                       class="w-full bg-gray-800 text-white px-4 py-2 rounded-lg border border-indigo-500">
            </div>
            <div>
                <label class="block mb-1 text-indigo-200">Ganancia Neta</label>
                <input type="number" name="ganancia_neta" step="0.01" required
                       class="w-full bg-gray-800 text-white px-4 py-2 rounded-lg border border-indigo-500">
            </div>
        </div>

        <!-- Mes y Año -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block mb-1 text-indigo-200">Mes de Venta</label>
               <?php
$mesesEsp = [
    '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo',
    '04' => 'Abril', '05' => 'Mayo', '06' => 'Junio',
    '07' => 'Julio', '08' => 'Agosto', '09' => 'Septiembre',
    '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
];
$anioActual = date('Y');
?>

<select name="mes_venta" required
    class="w-full bg-gray-800 text-white px-4 py-2 rounded-lg border border-indigo-500">
    <option value="">Selecciona un mes</option>
    <?php foreach ($mesesEsp as $num => $nombre): ?>
        <option value="<?= $num . ' ' . $anioActual ?>"><?= $nombre ?></option>
    <?php endforeach; ?>
</select>
            </div>
            <div>
                <label class="block mb-1 text-indigo-200">Año de Venta</label>
                <input type="text" name="year_venta" required
                       value="<?= date('Y') ?>"
                       class="w-full bg-gray-800 text-white px-4 py-2 rounded-lg border border-indigo-500">
            </div>
        </div>

        <!-- Botón -->
        <div class="text-center pt-4">
            <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-500 text-white px-6 py-3 rounded-xl font-bold shadow">
                Guardar Venta
            </button>
        </div>
    </form>
</section>