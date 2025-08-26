<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-indigo-200">Inmuebles</h1>
    <a href="<?= $baseUrl ?>/inmuebles/crear" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white shadow">
        Nuevo
    </a>
</div>

<!-- Buscador de inmuebles -->
<form method="get" action="<?= $baseUrl ?>/inmuebles" class="mb-6 flex flex-wrap gap-4 items-center">
    <input
        type="text"
        name="q"
        placeholder="Buscar dirección o arrendador"
        value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
        class="w-full sm:w-64 px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 placeholder-indigo-400 text-indigo-100"
    />
    <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg">Buscar</button>
</form>

<div class="overflow-x-auto bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl">
    <table class="min-w-full text-sm divide-y divide-gray-700">
        <thead class="bg-[#232336] text-indigo-200">
            <tr>
                <th class="px-4 py-3 text-left">Dirección</th>
                <th class="px-4 py-3">Tipo</th>
                <th class="px-4 py-3">Renta</th>
                <th class="px-4 py-3">Arrendador</th>
                <th class="px-4 py-3">Asesor</th>
                <th class="px-4 py-3">Fecha</th>
                <th class="px-4 py-3">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-700 text-indigo-100">
            <?php foreach ($inmuebles as $inm): ?>
                <tr id="row-<?= $inm['id'] ?>">
                    <td class="px-4 py-2 whitespace-nowrap"><?= htmlspecialchars($inm['direccion_inmueble']) ?></td>
                    <td class="px-4 py-2 text-center"><?= htmlspecialchars($inm['tipo']) ?></td>
                    <td class="px-4 py-2 text-center">$<?= htmlspecialchars($inm['renta']) ?></td>
                    <td class="px-4 py-2 text-center"><?= htmlspecialchars($inm['nombre_arrendador']) ?></td>
                    <td class="px-4 py-2 text-center"><?= htmlspecialchars($inm['nombre_asesor']) ?></td>
                    <td class="px-4 py-2 text-center">
                        <?= date('d M Y, H:i', strtotime($inm['fecha_registro'])) ?>
                    </td>
                    <td class="px-4 py-2 text-center">
                        <div class="flex gap-2 justify-center">
                            <a href="<?= $baseUrl ?>/inmuebles/<?= $inm['id'] ?>" class="text-green-400 hover:text-green-300" title="Ver">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </a>
                            <a href="<?= $baseUrl ?>/inmuebles/editar/<?= $inm['id'] ?>" class="text-pink-400 hover:text-pink-300" title="Editar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z" />
                                </svg>
                            </a>
                            <button data-id="<?= $inm['id'] ?>" class="btn-eliminar text-red-400 hover:text-red-300" title="Eliminar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- PAGINACIÓN MEJORADA -->

<?php if ($totalPaginas > 1): ?>
    <div class="mt-8 flex flex-wrap justify-center items-center gap-2">
        <?php
        // Configuración de páginas visibles
        $paginaActual = $pagina;
        $maxPaginasVisibles = 5;
        $inicio = max(1, $paginaActual - floor($maxPaginasVisibles/2));
        $fin = min($totalPaginas, $inicio + $maxPaginasVisibles - 1);
        $inicio = max(1, $fin - $maxPaginasVisibles + 1);

        // Base URL para paginación (ajusta si usas otros filtros)
        $urlBase = $baseUrl . '/inmuebles?';
        if (!empty($_GET['q'])) {
            $urlBase .= 'q=' . urlencode($_GET['q']) . '&';
        }
        $urlBase .= 'page=';
        ?>

        <!-- Botón Anterior -->
        <?php if ($paginaActual > 1): ?>
            <a href="<?= $urlBase . ($paginaActual - 1) ?>"
               class="flex items-center justify-center w-10 h-10 rounded-full border border-indigo-300 hover:bg-indigo-100 transition-colors"
               aria-label="Anterior">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
        <?php endif; ?>

        <?php if ($inicio > 1): ?>
            <a href="<?= $urlBase ?>1"
               class="px-4 py-2 rounded-lg border border-indigo-300 hover:bg-indigo-100 transition-colors <?= (1 == $paginaActual) ? 'bg-indigo-600 text-white border-indigo-600' : '' ?>">
                1
            </a>
            <?php if ($inicio > 2): ?>
                <span class="px-2 py-2 text-indigo-400">...</span>
            <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $inicio; $i <= $fin; $i++): ?>
            <a href="<?= $urlBase . $i ?>"
               class="flex items-center justify-center w-10 h-10 rounded-full border border-indigo-300 hover:bg-indigo-100 transition-colors <?= ($i == $paginaActual) ? 'bg-indigo-600 text-white border-indigo-600' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($fin < $totalPaginas): ?>
            <?php if ($fin < $totalPaginas - 1): ?>
                <span class="px-2 py-2 text-indigo-400">...</span>
            <?php endif; ?>
            <a href="<?= $urlBase . $totalPaginas ?>"
               class="px-4 py-2 rounded-lg border border-indigo-300 hover:bg-indigo-100 transition-colors <?= ($totalPaginas == $paginaActual) ? 'bg-indigo-600 text-white border-indigo-600' : '' ?>">
                <?= $totalPaginas ?>
            </a>
        <?php endif; ?>

        <!-- Botón Siguiente -->
        <?php if ($paginaActual < $totalPaginas): ?>
            <a href="<?= $urlBase . ($paginaActual + 1) ?>"
               class="flex items-center justify-center w-10 h-10 rounded-full border border-indigo-300 hover:bg-indigo-100 transition-colors"
               aria-label="Siguiente">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>




<script>
    document.querySelectorAll('.btn-eliminar').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            Swal.fire({
                title: '¿Eliminar inmueble?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                background: '#1e1e2f',
                color: '#fff'
            }).then(res => {
                if (res.isConfirmed) {
                    fetch('<?= $baseUrl ?>/inmuebles/delete', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'id=' + id
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.ok) {
                            document.getElementById('row-' + id).remove();
                            Swal.fire('Eliminado', '', 'success');
                        } else {
                            Swal.fire('Error', 'No se pudo eliminar', 'error');
                        }
                    });
                }
            });
        });
    });
</script>
