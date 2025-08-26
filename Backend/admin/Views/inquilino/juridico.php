<div class="p-6">
    <h1 class="text-2xl font-bold text-white mb-6 flex items-center gap-2">
        <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M12 20l9-5-9-5-9 5 9 5z" />
            <path d="M12 12V4l9-5-9-5-9 5z" />
        </svg>
        Historial de demandas
    </h1>

    <div class="grid md:grid-cols-2 gap-6">
        <?php if (!empty($historial)): ?>
            <?php foreach ($historial as $item): ?>
                <div class="bg-gray-900 border border-gray-700 rounded-xl p-5 shadow-xl">
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-sm text-gray-400"><?= htmlspecialchars($item['portal']) ?></span>
                        <span class="px-3 py-1 text-xs rounded-full
                            <?= $item['clasificacion'] === 'match_alto' ? 'bg-red-600 text-white' :
                                ($item['clasificacion'] === 'posible_match' ? 'bg-yellow-500 text-black' : 'bg-green-600 text-white') ?>">
                            <?= htmlspecialchars($item['clasificacion']) ?>
                        </span>
                    </div>

                    <p class="text-gray-300 text-sm mb-2">Score: <?= (int)$item['score_max'] ?></p>
                    <p class="text-gray-400 text-xs mb-4">Fecha: <?= htmlspecialchars($item['searched_at']) ?></p>

                    <div class="flex gap-3">
                        <?php if ($item['evidencia_s3_key']): ?>
                            <a href="https://<?= getenv('S3_BUCKET_INQUILINOS') ?>.s3.amazonaws.com/<?= urlencode($item['evidencia_s3_key']) ?>"
                               target="_blank"
                               class="px-3 py-1 bg-indigo-600 hover:bg-indigo-500 text-white text-xs rounded-lg">
                                Ver evidencia
                            </a>
                        <?php endif; ?>

                        <?php if ($item['raw_json_s3_key']): ?>
                            <a href="https://<?= getenv('S3_BUCKET_INQUILINOS') ?>.s3.amazonaws.com/<?= urlencode($item['raw_json_s3_key']) ?>"
                               target="_blank"
                               class="px-3 py-1 bg-gray-600 hover:bg-gray-500 text-white text-xs rounded-lg">
                                Ver JSON
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-2 text-center text-gray-400">
                No hay registros de validaciones legales para este inquilino.
            </div>
        <?php endif; ?>
    </div>
</div>
