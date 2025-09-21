<?php $editing = $editMode ?? false; ?>

<section class="px-4 md:px-8 py-10 text-white">
    <h1 class="text-4xl font-extrabold mb-8 flex items-center justify-center gap-3 text-indigo-300 text-center">
        <svg class="w-9 h-9 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M9 12l2 2 4-4M7 7h10M7 17h10" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        Póliza #
        <?= htmlspecialchars($poliza['numero_poliza']) ?>
    </h1>


    <div id="vista-poliza"
        class="bg-white/5 backdrop-blur-md border border-white/10 rounded-2xl shadow-[0_8px_32px_0_rgba(31,38,135,0.37)] p-8 space-y-8" data-numero-poliza="<?= htmlspecialchars($poliza['numero_poliza']) ?>">

        <!-- Datos Generales -->

        <div class="grid md:grid-cols-2 gap-6">

            <div class="bg-white/5 p-5 rounded-xl border border-white/10 shadow-inner">
                <h2 class="text-lg font-semibold text-indigo-300 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M8 17l4-4-4-4m8 8l-4-4 4-4" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                    Datos Generales
                </h2>
                <div class="space-y-1 text-sm">
                    <div><span class="font-semibold text-indigo-400">Tipo:</span> <span id="val-tipo">
                            <?= htmlspecialchars($poliza['tipo_poliza']) ?>
                        </span></div>
                    <div><span class="font-semibold text-indigo-400">Vigencia:</span> <span id="val-vigencia">
                            <?= htmlspecialchars($poliza['vigencia']) ?>
                        </span></div>
                    <div><span class="font-semibold text-indigo-400">Monto póliza:</span> $<span id="val-monto-poliza">
                            <?= number_format($poliza['monto_poliza']) ?>
                        </span></div>
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-indigo-400">Estado:</span>
                        <span id="val-estado"
                            class="px-2 py-1 rounded <?= estadoBadgeColor($poliza['estado']) ?> text-white text-xs font-semibold">
                            <?= estadoPolizaTexto($poliza['estado']) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Partes -->
            <div class="bg-white/5 p-5 rounded-xl border border-white/10 shadow-inner">
                <h2 class="text-lg font-semibold text-indigo-300 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M5 13l4 4L19 7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Partes
                </h2>

                <div class="space-y-1 text-sm">
                    <div>
                        <?php
                        $slugArrendador = trim((string)($poliza['slug_arrendador'] ?? ''));
                        $arrendadorHref = $slugArrendador !== ''
                            ? $baseUrl . '/arrendadores/' . $slugArrendador
                            : $baseUrl . '/arrendadores/' . $poliza['id_arrendador'];
                        ?>
                        <p class="text-sm text-white">
                            <span class="font-semibold text-indigo-400">Arrendador:</span>
                            <a href="<?= htmlspecialchars($arrendadorHref) ?>"
                                class="text-indigo-300 underline hover:text-indigo-100 transition font-medium ml-1"
                                id="val-arrendador" target="_blank">
                                <?= htmlspecialchars($poliza['nombre_arrendador']) ?>
                            </a>
                        </p>
                    </div>

                    <?php
                    $linkInquilino = "$baseUrl/inquilino/" . $poliza['slug_inquilino'];
                    ?>
                    <div class="">
                        <p class="text-sm text-white">
                            <span class="font-semibold text-indigo-400">Inquilino:</span>
                            <a href="<?= $linkInquilino ?>"
                                class="text-indigo-300 underline hover:text-indigo-100 transition font-medium ml-1"
                                id="val-inquilino" target="_blank">
                                <?= htmlspecialchars($poliza['nombre_inquilino_completo']) ?>
                            </a>
                        </p>
                    </div>
                    <?php
                    // Determinar el enlace o el texto según los valores de id_fiador y id_fiador_2025
                    $fiadorHtml = '';

                    if ($poliza['id_fiador'] == 40) {
                        // Caso: sin fiador
                        $fiadorHtml = '<span class="text-gray-300 ml-1">No Aplica</span>';

                        $fiadorHtml = '<a href="' . $baseUrl . '/inquilino/' . $poliza['slug_fiador'] . '" 
                                class="text-indigo-300 underline hover:text-indigo-100 transition font-medium ml-1"
                                id="val-fiador" target="_blank">'
                            . htmlspecialchars($poliza['nombre_fiador_completo']) .
                            '</a>';
                    }
                    ?>

                    <div>
                        <span class="font-semibold text-indigo-400">Fiador:</span>
                        <?= $fiadorHtml ?>
                    </div>

                    <?php
                    $linkObligado =  "$baseUrl/inquilino/" . $poliza['slug_obligado'];
                    ?>
                    <div>
                        <span class="font-semibold text-indigo-400">Obligado solidario:</span>
                        <a href="<?= $linkObligado ?>"
                            class="text-indigo-300 underline hover:text-indigo-100 transition font-medium ml-1"
                            id="val-obligado" target="_blank">
                            <?= htmlspecialchars($poliza['nombre_obligado_completo']) ?>
                        </a>
                    </div>

                    <div>
                        <span class="font-semibold text-indigo-400">Asesor:</span>
                        <a href="https://wa.me/+52<?= preg_replace('/\D/', '', $poliza['celular_asesor']) ?>"
                            target="_blank" id="val-asesor-link"
                            class="text-green-400 hover:underline hover:text-green-300 font-medium">
                            <span id="val-asesor-nombre">
                                <?= htmlspecialchars($poliza['nombre_asesor']) ?>
                            </span>
                        </a>
                    </div>

                </div>
            </div>
        </div>

        <!-- Inmueble -->
        <div class="bg-white/5 p-5 rounded-xl border border-white/10 shadow-inner">
            <h2 class="text-lg font-semibold text-indigo-300 mb-3 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M3 10l1-2 4-2 4 2 4-2 4 2 1 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2v-8z" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Inmueble
            </h2>
            <div class="text-sm space-y-1">
                <div>
                    <?php
                    $idInmueble = isset($poliza['id_inmueble']) ? (int)$poliza['id_inmueble'] : 0;
                    $direccion  = $poliza['direccion_inmueble'] ?? '';
                    $base       = isset($baseUrl) ? rtrim($baseUrl, '/') : ''; // opcional
                    $urlInm     = $idInmueble ? ($base . '/inmuebles/' . $idInmueble) : null;
                    ?>
                    <span class="font-semibold text-indigo-400">Dirección:</span>
                    <?php if ($urlInm): ?>
                        <a id="val-direccion"
                            href="<?= htmlspecialchars($urlInm) ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-pink-300 hover:underline">
                            <?= htmlspecialchars($direccion) ?>
                        </a>
                    <?php else: ?>
                        <span id="val-direccion"><?= htmlspecialchars($direccion) ?></span>
                    <?php endif; ?>

                </div>
                <div><span class="font-semibold text-indigo-400">Tipo de inmueble:</span> <span id="val-tipo-inmueble">
                        <?= htmlspecialchars($poliza['tipo_inmueble']) ?>
                    </span></div>
                <div><span class="font-semibold text-indigo-400">Monto renta:</span> $<span id="val-monto-renta">
                        <?= number_format($inmueble['renta']) ?>
                    </span></div>
            </div>
        </div>

        <!-- Comentarios -->
        <div class="bg-white/5 p-5 rounded-xl border border-white/10 shadow-inner">
            <h2 class="text-lg font-semibold text-indigo-300 mb-3 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M7 8h10M7 12h6m-6 4h10M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Comentarios
            </h2>
            <p id="val-comentarios" class="text-sm text-indigo-100">
                <?= htmlspecialchars($poliza['comentarios'] ?? 'N/A') ?>
            </p>
        </div>

        <div class="flex flex-col md:flex-row justify-center items-center gap-3 pt-4 text-center w-full">
            <a href="<?= $baseUrl ?>/polizas/generar-pdf/<?= $poliza['numero_poliza'] ?>"
                class="px-4 py-2 bg-indigo-700 hover:bg-indigo-600 rounded-lg text-white font-semibold shadow transition text-center">
                Descargar Póliza
            </a>

            <a href="<?= $baseUrl ?>/polizas"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-full text-white text-sm shadow-lg transition text-center">
                Volver al listado
            </a>

            <a href="<?= $baseUrl ?>/polizas/editar/<?= $poliza['numero_poliza'] ?>"
                class="px-4 py-2 bg-pink-600 hover:bg-pink-700 rounded-full text-white text-sm shadow-lg transition text-center">
                Editar
            </a>

            <a href="<?= $baseUrl ?>/polizas/renovar/<?= $poliza['numero_poliza'] ?>"
                class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-full text-white text-sm shadow-lg transition text-center">
                Renovar
            </a>

            <a href="<?= $baseUrl ?>/polizas/generacion-contrato/<?= $poliza['numero_poliza'] ?>"
                class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 rounded-full text-white text-sm shadow-lg transition text-center">
                Generar Contrato
            </a>
        </div>

</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const BASE_URL = window.BASE_URL || '';

        // Forma flexible de obtener el número de póliza sin acoplarse al markup:
        const getPolizaNumero = () =>
            document.querySelector('[data-numero-poliza]')?.dataset.numeroPoliza ||
            document.querySelector('input[name="numero_poliza"]')?.value ||
            (typeof window.POLIZA_NUMERO !== 'undefined' ? window.POLIZA_NUMERO : null);

        const POLIZA_NUM = getPolizaNumero();

        // Botones (si existen en la vista)
        const btnEditar = document.getElementById('btn-editar-poliza');
        const btnRenovar = document.getElementById('btn-renovar-poliza');

        if (btnEditar && POLIZA_NUM) {
            btnEditar.addEventListener('click', () => {
                window.location.href = `${BASE_URL}/polizas/editar/${encodeURIComponent(POLIZA_NUM)}`;
            });
        }

        if (btnRenovar && POLIZA_NUM) {
            btnRenovar.addEventListener('click', () => {
                // Ajusta si tu ruta de renovación difiere
                window.location.href = `${BASE_URL}/polizas/renovar/${encodeURIComponent(POLIZA_NUM)}`;
            });
        }
    });
</script>