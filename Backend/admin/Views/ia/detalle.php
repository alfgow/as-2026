<?php /** @var array|null $row */ ?>
<div class="min-h-screen bg-gray-900 text-white p-6">
    <div class="max-w-4xl mx-auto bg-white/5 border border-white/20 rounded-2xl p-6 shadow-xl space-y-4">
        <h1 class="text-2xl font-bold text-indigo-400"><?= htmlspecialchars($headerTitle) ?></h1>

        <?php if (!$row): ?>
            <p class="text-gray-300">No encontrado.</p>
        <?php else: ?>
            <div class="grid md:grid-cols-2 gap-4">
                <div class="bg-gray-800 rounded-xl p-4">
                    <p class="text-sm text-gray-400">ID</p>
                    <p class="font-mono"><?= (int)$row['id'] ?></p>
                </div>
                <div class="bg-gray-800 rounded-xl p-4">
                    <p class="text-sm text-gray-400">Modelo</p>
                    <p class="font-mono"><?= htmlspecialchars($row['modelo_key']) ?></p>
                    <p class="text-xs text-gray-400 break-all"><?= htmlspecialchars($row['modelo_id']) ?></p>
                </div>
                <div class="bg-gray-800 rounded-xl p-4">
                    <p class="text-sm text-gray-400">Duración</p>
                    <p class="font-mono"><?= (int)$row['duration_ms'] ?> ms</p>
                </div>
                <div class="bg-gray-800 rounded-xl p-4">
                    <p class="text-sm text-gray-400">Fecha</p>
                    <p class="font-mono"><?= htmlspecialchars($row['created_at']) ?></p>
                </div>
                <div class="bg-gray-800 rounded-xl p-4 md:col-span-2">
                    <p class="text-sm text-gray-400">Prompt</p>
                    <pre class="whitespace-pre-wrap"><?= htmlspecialchars($row['prompt']) ?></pre>
                </div>
                <div class="bg-gray-800 rounded-xl p-4 md:col-span-2">
                    <p class="text-sm text-gray-400">Respuesta</p>
                    <pre class="whitespace-pre-wrap"><?= htmlspecialchars($row['respuesta']) ?></pre>
                </div>
            </div>
        <?php endif; ?>

        <div class="pt-2">
            <a href="/ia/historial" class="text-indigo-400 hover:underline">← Volver al historial</a>
        </div>
    </div>
</div>
