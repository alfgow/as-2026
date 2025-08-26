<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Asesores Inmobiliarios</h1>
    <a href="<?= $baseUrl ?>/asesores/create"
       class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded shadow">
        + Nuevo Asesor
    </a>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($asesores as $asesor): ?>
        <div class="bg-gray-800 rounded-2xl shadow-lg p-6 flex flex-col">
            <h2 class="text-lg font-bold text-white mb-1">
                <?= htmlspecialchars($asesor['nombre_asesor']) ?>
            </h2>
            <p class="text-sm text-gray-300">Email: <?= htmlspecialchars($asesor['email']) ?></p>
            <p class="text-sm text-gray-300">Celular: <?= htmlspecialchars($asesor['celular']) ?></p>
            <p class="text-sm text-gray-300 mb-4">Teléfono: <?= htmlspecialchars($asesor['telefono']) ?></p>
            <a href="<?= $baseUrl ?>/asesores/edit?id=<?= $asesor['id'] ?>"
               class="mt-auto text-center py-1.5 bg-indigo-600 hover:bg-indigo-700 rounded text-white text-sm transition">Editar</a>
        </div>
    <?php endforeach; ?>
</div>
