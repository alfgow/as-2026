<?php /** @var array $items */ ?>
<div class="min-h-screen bg-gray-900 text-white p-6">
    <div class="max-w-5xl mx-auto bg-white/5 border border-white/20 rounded-2xl p-6 shadow-xl">
        <h1 class="text-2xl font-bold text-indigo-400 mb-4"><?= htmlspecialchars($headerTitle) ?></h1>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-800 text-gray-300">
                    <tr>
                        <th class="text-left px-3 py-2">ID</th>
                        <th class="text-left px-3 py-2">Modelo</th>
                        <th class="text-left px-3 py-2">Duration</th>
                        <th class="text-left px-3 py-2">Fecha</th>
                        <th class="text-left px-3 py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    <?php foreach ($items as $it): ?>
                        <tr class="hover:bg-white/5">
                            <td class="px-3 py-2"><?= (int)$it['id'] ?></td>
                            <td class="px-3 py-2"><?= htmlspecialchars($it['modelo_key']) ?></td>
                            <td class="px-3 py-2"><?= (int)$it['duration_ms'] ?> ms</td>
                            <td class="px-3 py-2"><?= htmlspecialchars($it['created_at']) ?></td>
                            <td class="px-3 py-2">
                                <a href="/ia/historial/<?= (int)$it['id'] ?>" class="text-indigo-400 hover:underline">Ver</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="5" class="px-3 py-6 text-center text-gray-400">Sin registros</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
