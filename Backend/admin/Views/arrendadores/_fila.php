<tr id="arrendador-<?= $arr['id'] ?>">
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
            <a href="<?= $baseUrl ?>/arrendador/<?= urlencode($arr['slug'] ?? $arr['id']) ?>"
               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-sm shadow transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Ver
            </a>
            <button
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
            </button>
        </div>
    </td>
</tr>
