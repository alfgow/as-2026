<div class="max-w-3xl mx-auto py-10 px-4">
    <h1 class="text-3xl font-bold text-indigo-200 mb-6">Buscar Póliza</h1>
    <form method="GET" action="<?= $baseUrl ?>/polizas/buscar" class="mb-8 flex gap-4">
        <input type="text" name="numero" placeholder="Número de póliza" required
               value="<?= htmlspecialchars($_GET['numero'] ?? '') ?>"
               class="flex-1 rounded-lg px-4 py-3 bg-[#232336] text-indigo-100 border border-indigo-800 placeholder-indigo-400 shadow focus:ring-indigo-600 focus:border-indigo-600 transition" />
        <button type="submit"
                class="px-6 py-3 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-md">Buscar</button>
    </form>

    <?php if ($poliza): ?>
        <div class="bg-white/10 backdrop-blur-lg border border-indigo-900/20 rounded-2xl shadow-2xl p-6 text-indigo-100">
            <h2 class="text-xl font-bold text-indigo-300 mb-4">Póliza #<?= htmlspecialchars($poliza['numero_poliza'] ?? '') ?></h2>
            <div class="grid gap-4 md:grid-cols-2">
                <?php foreach ($poliza as $campo => $valor): ?>
                    <?php if ($valor !== '' && !is_numeric($campo)): ?>
                        <div>
                            <span class="font-semibold capitalize text-indigo-200">
                                <?= htmlspecialchars(str_replace('_', ' ', $campo))?>:</span>
                            <span class="block text-white/90 break-words">
                                <?= htmlspecialchars($valor ?? '')?>
                            </span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php elseif(isset($_GET['numero'])): ?>
        <div class="text-center text-red-500 mt-6">No se encontró la póliza solicitada.</div>
    <?php endif; ?>
</div>
