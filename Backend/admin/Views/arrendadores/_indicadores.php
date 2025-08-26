<div class="grid grid-cols-2 grid-rows-2 md:grid-cols-2 md:grid-rows-2 lg:grid-cols-3 lg:grid-rows-1 gap-4 mb-6 max-w-4xl mx-auto">
    <!-- Card 1 -->
    <div class="bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl p-4 text-center flex flex-col justify-center">
        <p class="text-sm text-gray-400">Total arrendadores</p>
        <p class="text-2xl font-bold text-indigo-400"><?= $indicadores['total'] ?? 0 ?></p>
    </div>
    <!-- Card 2 -->
    <div class="bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl p-4 text-center flex flex-col justify-center">
        <p class="text-sm text-gray-400">Con pólizas</p>
        <p class="text-2xl font-bold text-indigo-400"><?= $indicadores['con_poliza'] ?? 0 ?></p>
    </div>
    <!-- Card 3, que en sm y md va centrado abajo -->
    <div class="bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl p-4 text-center flex flex-col justify-center col-span-2 md:col-span-2 lg:col-span-1 lg:col-start-3">
        <p class="text-sm text-gray-400">Nuevos este mes</p>
        <p class="text-2xl font-bold text-indigo-400"><?= $indicadores['nuevos_mes'] ?? 0 ?></p>
    </div>
</div>
