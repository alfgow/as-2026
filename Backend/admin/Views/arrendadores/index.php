<?php

use App\Helpers\TextHelper;
?>
<div class="flex flex-col items-center justify-center mt-16">
    <!-- Título -->
    <h1 class="text-3xl font-bold text-indigo-200 mb-6">Arrendadores</h1>

    <!-- FILTRO / BUSCADOR -->
    <form method="get" action="<?= $baseUrl ?>/arrendadores" class="flex flex-wrap gap-4 justify-center mb-10">
        <input
            type="text"
            name="q"
            placeholder="Buscar nombre, teléfono o correo"
            value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
            class="w-80 px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 placeholder-indigo-400 text-indigo-100 text-center" autofocus />
        <button class="px-6 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg shadow transition">
            Buscar
        </button>
    </form>

    <!-- RESULTADOS -->
    <div class="w-full max-w-6xl px-4">
        <?php if (!empty($_GET['q'])): ?>
            <?php if (!empty($arrendadores)): ?>

                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <?php foreach ($arrendadores as $arr): ?>
                        <?php
                        $profile   = $arr['profile'] ?? [];
                        $selfieUrl = $arr['selfie_url'] ?? null;
                        ?>
                        <div class="bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl p-5 shadow flex flex-col items-center text-center">

                            <?php if ($selfieUrl): ?>
                                <img src="<?= htmlspecialchars($selfieUrl) ?>" alt="Foto arrendador"
                                    class="w-24 h-24 object-cover rounded-full shadow-lg ring-4 ring-indigo-400/40 border-4 border-white/10 bg-gray-800/50 backdrop-blur-sm transition-transform duration-200 hover:scale-105 mb-4">
                            <?php else: ?>
                                <div class="w-24 h-24 flex items-center justify-center bg-indigo-600/10 text-indigo-300 text-3xl font-bold rounded-full border-4 border-white/10 ring-2 ring-indigo-500/30 shadow mb-4">
                                    <?= strtoupper(mb_substr($profile['nombre_arrendador'] ?? 'A', 0, 1, 'UTF-8')) ?>
                                </div>
                            <?php endif; ?>


                            <h3 class="text-lg font-bold text-indigo-200 mb-1"><?= TextHelper::titleCase($profile['nombre_arrendador'] ?? '') ?></h3>
                            <p class="text-sm text-indigo-400 mb-1"><?= htmlspecialchars($profile['email'] ?? '') ?></p>
                            <p class="text-sm text-indigo-400 mb-4"><?= htmlspecialchars($profile['celular'] ?? '') ?></p>
                            <a href="<?= $baseUrl ?>/arrendadores/<?= htmlspecialchars($arr['profile']['slug']) ?>"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-sm shadow transition">
                                Ver detalle
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-indigo-300 text-center">No se encontraron arrendadores para la búsqueda.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('form[action*="arrendadores"]');
        if (form) {
            form.addEventListener('submit', () => {
                showLoader("Buscando arrendadores...");
            });
        }
    });
</script>