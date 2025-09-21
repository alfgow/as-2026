<div class="max-w-xl mx-auto py-8">
    <?php
        $pk = (string)($inmueble['pk'] ?? '');
        $sk = (string)($inmueble['sk'] ?? '');
        $idInmueble = isset($inmueble['id']) ? (string) $inmueble['id'] : '';

        if ($idInmueble !== '') {
            $editUrl = $baseUrl . '/inmuebles/editar/' . rawurlencode($idInmueble);
        } elseif ($pk !== '' && $sk !== '') {
            $editUrl = $baseUrl . '/inmuebles/editar/' . rawurlencode($pk) . '/' . rawurlencode($sk);
        } else {
            $editUrl = $baseUrl . '/inmuebles';
        }
    ?>
    <div class="bg-white/5 backdrop-blur-md border border-white/10 rounded-2xl p-6 space-y-4">
        <h2 class="text-xl font-semibold text-indigo-300 text-center mb-4">Detalle de Inmueble</h2>
        <div class="text-sm text-indigo-100 space-y-2">
            <div><span class="font-semibold text-indigo-400">Dirección:</span> <?= htmlspecialchars($inmueble['direccion_inmueble']) ?></div>
            <div><span class="font-semibold text-indigo-400">Tipo:</span> <?= htmlspecialchars($inmueble['tipo']) ?></div>
            <div><span class="font-semibold text-indigo-400">Renta:</span> $<?= htmlspecialchars($inmueble['renta']) ?></div>
            <div><span class="font-semibold text-indigo-400">Mantenimiento:</span> <?= htmlspecialchars($inmueble['mantenimiento']) ?> - $<?= htmlspecialchars($inmueble['monto_mantenimiento']) ?></div>
            <div><span class="font-semibold text-indigo-400">Depósito:</span> <?= htmlspecialchars($inmueble['deposito']) ?></div>
            <div><span class="font-semibold text-indigo-400">Estacionamiento:</span> <?= $inmueble['estacionamiento'] ? 'Sí' : 'No' ?></div>
            <div><span class="font-semibold text-indigo-400">Mascotas:</span> <?= htmlspecialchars($inmueble['mascotas']) ?></div>
            <div><span class="font-semibold text-indigo-400">Arrendador:</span> <?= htmlspecialchars($inmueble['nombre_arrendador']) ?></div>
            <div><span class="font-semibold text-indigo-400">Asesor:</span> <?= htmlspecialchars($inmueble['nombre_asesor']) ?></div>
            <div><span class="font-semibold text-indigo-400">Comentarios:</span> <?= nl2br(htmlspecialchars($inmueble['comentarios'])) ?></div>
            <div><span class="font-semibold text-indigo-400">Registrado:</span> <?= date('d M Y, H:i', strtotime($inmueble['fecha_registro'])) ?></div>
        </div>
        <div class="flex justify-center gap-3 pt-4">
            <a href="<?= $baseUrl ?>/inmuebles" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white">Regresar</a>

            <a href="<?= htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8') ?>" class="px-4 py-2 rounded-lg bg-pink-600 hover:bg-pink-500 text-white">Editar</a>

        </div>
    </div>
</div>
