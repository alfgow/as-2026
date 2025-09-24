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
                    } else {
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

        <?php $actionBtnBase = 'w-full md:w-auto px-5 py-2.5 rounded-xl text-sm font-semibold shadow-lg transition flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-[#1b1b29]'; ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 pt-6 w-full max-w-4xl mx-auto">
            <a href="<?= $baseUrl ?>/polizas/generar-pdf/<?= $poliza['numero_poliza'] ?>"
                class="<?= $actionBtnBase ?> bg-indigo-700 hover:bg-indigo-600 text-white focus:ring-indigo-500"
                id="btn-descargar-poliza">
                Descargar Póliza
            </a>

            <a href="<?= $baseUrl ?>/polizas"
                class="<?= $actionBtnBase ?> bg-indigo-600 hover:bg-indigo-500 text-white focus:ring-indigo-400"
                id="btn-volver-polizas">
                Volver al listado
            </a>

            <a href="<?= $baseUrl ?>/polizas/editar/<?= $poliza['numero_poliza'] ?>"
                class="<?= $actionBtnBase ?> bg-pink-600 hover:bg-pink-500 text-white focus:ring-pink-400"
                id="btn-editar-poliza">
                Editar
            </a>

            <a href="<?= $baseUrl ?>/polizas/renovar/<?= $poliza['numero_poliza'] ?>"
                class="<?= $actionBtnBase ?> bg-green-600 hover:bg-green-500 text-white focus:ring-green-400"
                id="btn-renovar-poliza">
                Renovar
            </a>

            <a href="<?= $baseUrl ?>/polizas/generacion-contrato/<?= $poliza['numero_poliza'] ?>"
                class="<?= $actionBtnBase ?> bg-yellow-500 hover:bg-yellow-400 text-[#1b1b29] focus:ring-yellow-300"
                id="btn-generar-contrato">
                Generar Contrato
            </a>

            <button type="button" id="btn-eliminar-poliza"
                class="<?= $actionBtnBase ?> bg-red-600 hover:bg-red-500 text-white focus:ring-red-500">
                Eliminar
            </button>
        </div>

</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const baseUrl = window.BASE_URL || window.baseurl || BASE_URL || '';

        // Forma flexible de obtener el número de póliza sin acoplarse al markup:
        const getPolizaNumero = () =>
            document.querySelector('[data-numero-poliza]')?.dataset.numeroPoliza ||
            document.querySelector('input[name="numero_poliza"]')?.value ||
            (typeof window.POLIZA_NUMERO !== 'undefined' ? window.POLIZA_NUMERO : null);

        const POLIZA_NUM = getPolizaNumero();

        // Botones (si existen en la vista)
        const btnEditar = document.getElementById('btn-editar-poliza');
        const btnRenovar = document.getElementById('btn-renovar-poliza');
        const btnEliminar = document.getElementById('btn-eliminar-poliza');

        if (btnEditar && POLIZA_NUM) {
            btnEditar.addEventListener('click', () => {
                window.location.href = `${baseUrl}/polizas/editar/${encodeURIComponent(POLIZA_NUM)}`;
            });
        }

        if (btnRenovar && POLIZA_NUM) {
            btnRenovar.addEventListener('click', () => {
                // Ajusta si tu ruta de renovación difiere
                window.location.href = `${baseUrl}/polizas/renovar/${encodeURIComponent(POLIZA_NUM)}`;
            });
        }

        if (btnEliminar && POLIZA_NUM) {
            btnEliminar.addEventListener('click', async () => {
                const confirmacion = await (window.Swal ?
                    Swal.fire({
                        title: 'Eliminar póliza',
                        text: `¿Deseas eliminar la póliza #${POLIZA_NUM}? Esta acción no se puede deshacer.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc2626',
                        cancelButtonColor: '#4b5563',
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar',
                        focusCancel: true
                    }).then((result) => result.isConfirmed) :
                    Promise.resolve(window.confirm(`¿Deseas eliminar la póliza #${POLIZA_NUM}?`)));

                if (!confirmacion) {
                    return;
                }

                btnEliminar.disabled = true;
                btnEliminar.classList.add('opacity-70', 'cursor-not-allowed');
                if (typeof showLoader === 'function') {
                    showLoader('Eliminando póliza...');
                }

                try {
                    const respuesta = await fetch(`${baseUrl}/polizas/eliminar`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            numero: POLIZA_NUM
                        }),
                        credentials: 'same-origin'
                    });

                    const data = await respuesta.json();

                    if (!respuesta.ok || !data?.ok) {
                        throw new Error(data?.error || 'No se pudo eliminar la póliza.');
                    }

                    if (window.Swal) {
                        await Swal.fire({
                            title: 'Póliza eliminada',
                            text: 'La póliza se eliminó correctamente.',
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        });
                    } else {
                        window.alert('La póliza se eliminó correctamente.');
                    }

                    window.location.href = `${baseUrl}/polizas`;
                } catch (error) {
                    const mensaje = error instanceof Error ? error.message : 'Error desconocido al eliminar la póliza.';
                    if (window.Swal) {
                        Swal.fire({
                            title: 'No se pudo eliminar',
                            text: mensaje,
                            icon: 'error'
                        });
                    } else {
                        window.alert(mensaje);
                    }
                } finally {
                    btnEliminar.disabled = false;
                    btnEliminar.classList.remove('opacity-70', 'cursor-not-allowed');
                    if (typeof hideLoader === 'function') {
                        hideLoader();
                    }
                }
            });
        }
    });
</script>