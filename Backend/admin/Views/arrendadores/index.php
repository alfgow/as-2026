<?php include __DIR__ . '/_indicadores.php'; ?>

<!-- FILTRO -->
<form method="get" action="<?= $baseUrl ?>/arrendadores" class="mb-6 flex flex-wrap gap-4 items-center">
    <input
        type="text"
        name="q"
        placeholder="Buscar nombre, teléfono o correo"
        value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
        class="w-full sm:w-64 px-3 py-2 rounded-lg bg-[#232336] border border-indigo-800 placeholder-indigo-400 text-indigo-100"
    />
    <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg">Buscar</button>
</form>

<!-- TABLA -->
<div class="overflow-x-auto bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl">
    <table class="min-w-full text-sm divide-y divide-gray-700">
        <thead class="bg-[#232336] text-indigo-200">
            <tr>
                <th class="px-4 py-3 text-left">Nombre</th>
                <th class="px-4 py-3">Contacto</th>
                <th class="px-4 py-3"># Propiedades</th>
                <th class="px-4 py-3">Pólizas activas</th>
                <th class="px-4 py-3">Última póliza</th>
                <th class="px-4 py-3">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-700 text-indigo-100">
            <?php foreach ($arrendadores as $arr): ?>
                <tr>
                    <td class="px-4 py-2 whitespace-nowrap font-semibold text-indigo-200"><?= htmlspecialchars($arr['nombre_arrendador']) ?></td>
                    <td class="px-4 py-2 text-center">
                        <div><?= htmlspecialchars($arr['celular']) ?></div>
                        <div class="text-xs text-indigo-400"><?= htmlspecialchars($arr['email']) ?></div>
                    </td>
                    <td class="px-4 py-2 text-center"><?= $arr['num_inmuebles'] ?></td>
                    <td class="px-4 py-2 text-center"><?= $arr['polizas_activas'] ?></td>
                    <td class="px-4 py-2 text-center">
                        <?= $arr['ultima_poliza'] ? date('d/m/Y', strtotime($arr['ultima_poliza'])) : '-' ?>
                    </td>
                    <td class="px-4 py-2 text-center">
                       <div class="flex flex-col sm:flex-row gap-2 justify-center items-center">
    <a href="<?= $baseUrl ?>/arrendadores/<?= $arr['id'] ?>"
       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-sm shadow transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Ver
    </a>
    <!-- <button
    class="btn-editar-arrendador inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-pink-600 hover:bg-pink-500 text-white text-sm shadow transition"
    data-id="<?= $arr['id'] ?>"
    data-nombre="<?= htmlspecialchars($arr['nombre_arrendador']) ?>"
    data-email="<?= htmlspecialchars($arr['email']) ?>"
    data-celular="<?= htmlspecialchars($arr['celular']) ?>"
    data-telefono="<?= htmlspecialchars($arr['telefono'] ?? '') ?>"
    data-rfc="<?= htmlspecialchars($arr['rfc'] ?? '') ?>"
>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z" />
    </svg>
    Editar
</button> -->
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
        
        // Botón Anterior
        if ($paginaActual > 1):
            $urlAnterior = $baseUrl . "/arrendadores?q=" . urlencode($_GET['q'] ?? '') . "&pagina=" . ($paginaActual - 1);
        ?>
            <a href="<?= $urlAnterior ?>" class="flex items-center justify-center w-10 h-10 rounded-full border border-indigo-300 hover:bg-indigo-100 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
        <?php endif; ?>

        <?php if ($inicio > 1): ?>
            <a href="<?= $baseUrl ?>/arrendadores?q=<?= urlencode($_GET['q'] ?? '') ?>&pagina=1" class="px-4 py-2 rounded-lg border border-indigo-300 hover:bg-indigo-100 transition-colors <?= (1 == $paginaActual) ? 'bg-indigo-600 text-white border-indigo-600' : '' ?>">
                1
            </a>
            <?php if ($inicio > 2): ?>
                <span class="px-2 py-2 text-indigo-400">...</span>
            <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $inicio; $i <= $fin; $i++): ?>
            <a href="<?= $baseUrl ?>/arrendadores?q=<?= urlencode($_GET['q'] ?? '') ?>&pagina=<?= $i ?>" class="flex items-center justify-center w-10 h-10 rounded-full border border-indigo-300 hover:bg-indigo-100 transition-colors <?= ($i == $paginaActual) ? 'bg-indigo-600 text-white border-indigo-600' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($fin < $totalPaginas): ?>
            <?php if ($fin < $totalPaginas - 1): ?>
                <span class="px-2 py-2 text-indigo-400">...</span>
            <?php endif; ?>
            <a href="<?= $baseUrl ?>/arrendadores?q=<?= urlencode($_GET['q'] ?? '') ?>&pagina=<?= $totalPaginas ?>" class="px-4 py-2 rounded-lg border border-indigo-300 hover:bg-indigo-100 transition-colors <?= ($totalPaginas == $paginaActual) ? 'bg-indigo-600 text-white border-indigo-600' : '' ?>">
                <?= $totalPaginas ?>
            </a>
        <?php endif; ?>

        <?php // Botón Siguiente
        if ($paginaActual < $totalPaginas):
            $urlSiguiente = $baseUrl . "/arrendadores?q=" . urlencode($_GET['q'] ?? '') . "&pagina=" . ($paginaActual + 1);
        ?>
            <a href="<?= $urlSiguiente ?>" class="flex items-center justify-center w-10 h-10 rounded-full border border-indigo-300 hover:bg-indigo-100 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.btn-editar-arrendador').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const nombre = btn.dataset.nombre;
            const email = btn.dataset.email;
            const celular = btn.dataset.celular;
            const telefono = btn.dataset.telefono;
            const rfc = btn.dataset.rfc;

            Swal.fire({
                title: 'Editar arrendador',
                html: `
                    <div class="space-y-3 text-left">
                        <input type="hidden" id="edit-id" value="${id}">
                        <div>
                            <label for="edit-nombre" class="block text-sm text-indigo-200 mb-1">Nombre</label>
                            <input id="edit-nombre" class="w-full px-3 py-2 bg-[#232336] border border-indigo-700 rounded-lg text-indigo-100 placeholder-indigo-400" value="${nombre}" placeholder="Nombre completo">
                        </div>
                        <div>
                            <label for="edit-email" class="block text-sm text-indigo-200 mb-1">Email</label>
                            <input id="edit-email" class="w-full px-3 py-2 bg-[#232336] border border-indigo-700 rounded-lg text-indigo-100 placeholder-indigo-400" value="${email}" placeholder="Correo electrónico">
                        </div>
                        <div>
                            <label for="edit-celular" class="block text-sm text-indigo-200 mb-1">Celular</label>
                            <input id="edit-celular" class="w-full px-3 py-2 bg-[#232336] border border-indigo-700 rounded-lg text-indigo-100 placeholder-indigo-400" value="${celular}" placeholder="Celular">
                        </div>
                        <div>
                            <label for="edit-telefono" class="block text-sm text-indigo-200 mb-1">Teléfono</label>
                            <input id="edit-telefono" class="w-full px-3 py-2 bg-[#232336] border border-indigo-700 rounded-lg text-indigo-100 placeholder-indigo-400" value="${telefono}" placeholder="Teléfono fijo">
                        </div>
                        <div>
                            <label for="edit-rfc" class="block text-sm text-indigo-200 mb-1">RFC</label>
                            <input id="edit-rfc" class="w-full px-3 py-2 bg-[#232336] border border-indigo-700 rounded-lg text-indigo-100 placeholder-indigo-400" value="${rfc}" placeholder="RFC">
                        </div>
                    </div>
                `,
                customClass: {
                    popup: 'bg-[#1e1e2f] text-indigo-100 rounded-xl p-6',
                    title: 'text-lg font-bold mb-4',
                    confirmButton: 'bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg shadow',
                    cancelButton: 'bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg shadow'
                },
                showCancelButton: true,
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar',
                focusConfirm: false,
                preConfirm: () => {
                    const data = {
                        id: document.getElementById('edit-id').value,
                        nombre_arrendador: document.getElementById('edit-nombre').value.trim(),
                        email: document.getElementById('edit-email').value.trim(),
                        celular: document.getElementById('edit-celular').value.trim(),
                        telefono: document.getElementById('edit-telefono').value.trim(),
                        rfc: document.getElementById('edit-rfc').value.trim()
                    };

                    if (!data.nombre_arrendador || !data.email || !data.celular) {
                        Swal.showValidationMessage('Nombre, Email y Celular son obligatorios');
                        return false;
                    }

                    return fetch(`${baseUrl}/arrendador/update-ajax`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    }).then(res => {
                        if (!res.ok) throw new Error('Error al guardar');
                        return res.json();
                    }).catch(err => {
                        Swal.showValidationMessage(err.message);
                    });
                }
            }).then(result => {
                if (result.isConfirmed && result.value && result.value.success) {
                    const newHtml = result.value.html;
                    const id = document.getElementById('edit-id').value;
                    const oldRow = document.querySelector(`#arrendador-${id}`);
                    if (oldRow) {
                        const temp = document.createElement('tbody');
                        temp.innerHTML = newHtml;
                        const newRow = temp.querySelector('tr');
                        oldRow.replaceWith(newRow);
                        document.dispatchEvent(new Event('DOMContentLoaded'));
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Datos actualizados',
                        toast: true,
                        position: 'top-end',
                        timer: 2500,
                        showConfirmButton: false
                    });
                }
            });
        });
    });
});
</script>
