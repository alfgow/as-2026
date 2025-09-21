<div class="flex flex-col items-center justify-center mt-16">
    <h1 class="text-3xl font-bold text-indigo-200 mb-6">Inmuebles</h1>

    <form method="get" action="<?= $baseUrl ?>/inmuebles" class="flex flex-wrap gap-4 justify-center mb-10">
        <input
            type="text"
            name="q"
            placeholder="Buscar dirección, colonia o arrendador"
            value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
            class="w-80 px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 placeholder-indigo-400 text-indigo-100 text-center"
            autofocus />
        <button class="px-6 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg shadow transition">
            Buscar
        </button>
    </form>

    <div class="w-full max-w-6xl px-4">
        <?php if (!empty($_GET['q'])): ?>
            <?php if (!empty($inmuebles)): ?>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <?php foreach ($inmuebles as $inmueble): ?>
                        <?php
                            $pk = (string)($inmueble['pk'] ?? '');
                            $sk = (string)($inmueble['sk'] ?? '');
                            $idInmueble = isset($inmueble['id']) ? (string) $inmueble['id'] : '';

                            if ($idInmueble !== '') {
                                $verUrl = $baseUrl . '/inmuebles/' . rawurlencode($idInmueble);
                                $editarUrl = $baseUrl . '/inmuebles/editar/' . rawurlencode($idInmueble);
                            } elseif ($pk !== '' && $sk !== '') {
                                $verUrl = $baseUrl . '/inmuebles/' . rawurlencode($pk) . '/' . rawurlencode($sk);
                                $editarUrl = $baseUrl . '/inmuebles/editar/' . rawurlencode($pk) . '/' . rawurlencode($sk);
                            } else {
                                $verUrl = '#';
                                $editarUrl = '#';
                            }

                            $direccion = trim((string)($inmueble['direccion_inmueble'] ?? '')) ?: 'Sin dirección';
                            $tipo = trim((string)($inmueble['tipo'] ?? ''));
                            $renta = trim((string)($inmueble['renta'] ?? ''));
                            $arrendador = trim((string)($inmueble['nombre_arrendador'] ?? ''));
                            $asesor = trim((string)($inmueble['nombre_asesor'] ?? ''));
                            $fecha = !empty($inmueble['fecha_registro'])
                                ? date('d M Y, H:i', strtotime((string)$inmueble['fecha_registro']))
                                : null;
                        ?>

                        <div class="bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl p-5 shadow flex flex-col gap-3">
                            <div>
                                <h3 class="text-lg font-bold text-indigo-200 mb-1">
                                    <?= htmlspecialchars($direccion) ?>
                                </h3>
                                <?php if ($tipo !== ''): ?>
                                    <p class="text-sm text-indigo-300">
                                        Tipo: <span class="text-indigo-100"><?= htmlspecialchars($tipo) ?></span>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <?php if ($arrendador !== '' || $asesor !== ''): ?>
                                <div class="text-sm text-indigo-300 space-y-1">
                                    <?php if ($arrendador !== ''): ?>
                                        <p>Arrendador: <span class="text-indigo-100"><?= htmlspecialchars($arrendador) ?></span></p>
                                    <?php endif; ?>
                                    <?php if ($asesor !== ''): ?>
                                        <p>Asesor: <span class="text-indigo-100"><?= htmlspecialchars($asesor) ?></span></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($renta !== ''): ?>
                                <p class="text-sm text-indigo-300">
                                    Renta mensual: <span class="text-indigo-100">$<?= htmlspecialchars($renta) ?></span>
                                </p>
                            <?php endif; ?>

                            <?php if ($fecha): ?>
                                <p class="text-xs text-indigo-400">Registrado el <?= htmlspecialchars($fecha) ?></p>
                            <?php endif; ?>

                            <div class="flex flex-wrap gap-3 pt-2">
                                <?php if ($verUrl !== '#'): ?>
                                    <a href="<?= htmlspecialchars($verUrl, ENT_QUOTES, 'UTF-8') ?>"
                                        class="flex-1 text-center px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-sm shadow transition">
                                        Ver detalle
                                    </a>
                                <?php endif; ?>
                                <?php if ($editarUrl !== '#'): ?>
                                    <a href="<?= htmlspecialchars($editarUrl, ENT_QUOTES, 'UTF-8') ?>"
                                        class="flex-1 text-center px-4 py-2 bg-pink-500 hover:bg-pink-400 text-white rounded-lg text-sm shadow transition">
                                        Editar
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (!empty($rcuUsed)): ?>
                    <p class="mt-6 text-xs text-indigo-300 text-center">
                        RCU utilizadas en la búsqueda: <?= number_format((float)$rcuUsed, 2) ?>
                    </p>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-indigo-300 text-center">No se encontraron inmuebles para la búsqueda.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('form[action$="/inmuebles"]');
        if (form) {
            form.addEventListener('submit', () => {
                showLoader("Buscando inmuebles...");
            });
        }
    });
</script>
