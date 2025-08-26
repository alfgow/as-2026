
<section class="px-4 md:px-8 py-6 text-white">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold flex items-center gap-3">
            <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4M7 7h10M7 17h10" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Gestión de Pólizas Jurídicas
        </h1>
        <a href="<?= $baseUrl ?>/polizas/nueva" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow">Nueva</a>
    </div>

    <!-- Indicadores (KPIs) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <?php include __DIR__ . '/_indicadores.php'; ?>
    </div>

    <!-- Filtros -->
    <div class="bg-white/5 backdrop-blur-md border border-white/10 rounded-xl p-4 mb-6 shadow-lg">
        <form class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <input type="text" name="buscar" placeholder="Buscar por nombre, ID, dirección..." 
            value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>"
            class="px-4 py-2 rounded-lg bg-gray-800 text-white border border-white/10 placeholder-gray-400">
            <?php
                $estados = [
                    '1' => 'Vigente',
                    '2' => 'Concluida',
                    '3' => 'Término Anticipado',
                    '4' => 'Incumplimiento',
                ];
                $estadoSeleccionado = $_GET['estado'] ?? '';
            ?>
            <select name="estado" class="px-4 py-2 rounded-lg bg-gray-800 text-white border border-white/10">
                <option value="">Todos los estados</option>
                <?php foreach ($estados as $valor => $nombre): ?>
                    <option value="<?= $valor ?>" <?= $estadoSeleccionado === $valor ? 'selected' : '' ?>>
                        <?= $nombre ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php $tipoSeleccionado = $_GET['tipo'] ?? ''; ?>
            <select name="tipo" class="px-4 py-2 rounded-lg bg-gray-800 text-white border border-white/10">
                <option value="">Todos los tipos</option>
                <option value="clasica" <?= $tipoSeleccionado === 'clasica' ? 'selected' : '' ?>>Clásica</option>
                <option value="plus" <?= $tipoSeleccionado === 'plus' ? 'selected' : '' ?>>Plus</option>
            </select>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 transition text-white font-semibold rounded-lg px-4 py-2 shadow">
                Filtrar
            </button>
        </form>
    </div>

   <!-- Tabla de pólizas -->
<div class="overflow-x-auto rounded-xl shadow-xl border border-white/10 bg-white/5 backdrop-blur-md">
    <table class="min-w-full text-sm text-white">
        <thead class="bg-indigo-900/60 text-indigo-200">
            <tr>
                <th class="px-4 py-3 text-left">#</th>
                <th class="px-4 py-3 text-left">Inquilino</th>
                <th class="px-4 py-3 text-left">Arrendador</th>
                <th class="px-4 py-3 text-left">Dirección</th>
                <th class="px-4 py-3 text-left">Vigencia</th>
                <th class="px-4 py-3 text-left hidden lg:table-cell">Tipo</th>
                <th class="px-4 py-3 text-left">Estado</th>
                <th class="px-4 py-3 text-left">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/10">
            <?php foreach ($polizas as $poliza): ?>
                <tr class="hover:bg-indigo-900/30 transition">
                    <td class="px-4 py-3"><?= htmlspecialchars($poliza['numero_poliza']) ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($poliza['nombre_inquilino_completo']) ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($poliza['nombre_arrendador']) ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($poliza['direccion_inmueble']) ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($poliza['fecha_poliza']) ?> – <?= htmlspecialchars($poliza['fecha_fin']?? '') ?></td>
                    <td class="px-4 py-3 hidden lg:table-cell"><?= htmlspecialchars($poliza['tipo_poliza']) ?></td>
                    <td class="px-4 py-3">
                        <?php
                            $estado = $poliza['estado'];
                            $badgeColor = estadoBadgeColor($estado);
                            $textoEstado = estadoPolizaTexto($estado);
                        ?>
                        <div class="flex items-center justify-center lg:justify-start gap-2">
                            <!-- Círculo visible solo en sm y md -->
                            <span class="w-3 h-3 rounded-full <?= estadoBadgeColor($poliza['estado']) ?> inline-block lg:hidden" title="<?= estadoPolizaTexto($poliza['estado']) ?>"></span>

                            <!-- Texto visible solo en lg+ -->
                            <span class="hidden lg:inline-block px-3 py-1 rounded-full text-sm font-semibold <?= estadoBadgeColor($poliza['estado']) ?> text-white shadow">
                                <?= estadoPolizaTexto($poliza['estado']) ?>
                            </span>
                        </div>

                    </td>
                    <td class="px-4 py-3">
                        <div class="flex flex-col gap-2">
                            <a href="<?= $baseUrl ?>/polizas/<?= $poliza['numero_poliza'] ?>" 
                            class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg shadow transition text-center">
                                Ver
                            </a>

                
                        </div>
                    </td>

                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Botones de paginación -->
<?php if ($totalPaginas > 1): ?>
    <?php
        // Construimos los parámetros activos de la URL
        $urlBase = '?';
        if (!empty($_GET['estado'])) $urlBase .= 'estado=' . urlencode($_GET['estado']) . '&';
        if (!empty($_GET['tipo']))   $urlBase .= 'tipo=' . urlencode($_GET['tipo']) . '&';
        if (!empty($_GET['buscar'])) $urlBase .= 'buscar=' . urlencode($_GET['buscar']) . '&';

        $maxVisible = 5;
        $inicio = max(1, $pagina - 2);
        $fin = min($totalPaginas, $pagina + 2);
    ?>

    <div class="flex justify-center mt-6 gap-1 flex-wrap">
        <?php if ($inicio > 1): ?>
            <a href="<?= $urlBase ?>page=1" class="px-3 py-2 rounded-md bg-white/10 text-indigo-200 text-sm hover:bg-indigo-800">1</a>
            <?php if ($inicio > 2): ?>
                <span class="px-2 py-2 text-indigo-400">...</span>
            <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $inicio; $i <= $fin; $i++): ?>
            <a href="<?= $urlBase ?>page=<?= $i ?>"
                class="px-3 py-2 rounded-md text-sm font-medium transition 
                <?= $i == $pagina 
                    ? 'bg-indigo-600 text-white' 
                    : 'bg-white/10 text-indigo-200 hover:bg-indigo-800' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($fin < $totalPaginas): ?>
            <?php if ($fin < $totalPaginas - 1): ?>
                <span class="px-2 py-2 text-indigo-400">...</span>
            <?php endif; ?>
            <a href="<?= $urlBase ?>page=<?= $totalPaginas ?>" class="px-3 py-2 rounded-md bg-white/10 text-indigo-200 text-sm hover:bg-indigo-800"><?= $totalPaginas ?></a>
        <?php endif; ?>
    </div>
<?php endif; ?>



</section>
