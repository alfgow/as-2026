<?php
$siguienteNumero = $siguienteNumero ?? '';
?>
<div class="max-w-3xl mx-auto py-10">
    <?php
    $hoy = date('Y-m-d');

    $dt = new DateTime($hoy);
    $dt->modify('+1 year -1 day');
    $fin = $dt->format('Y-m-d');
    ?>
    <form id="form-nueva-poliza"
        class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-2xl p-8 border border-indigo-900/20 space-y-8"
        method="POST" action="<?= $baseUrl ?>/polizas/store">

        <!-- Título con número de póliza -->
        <h1 class="text-3xl font-bold text-indigo-300 mb-6 flex items-center gap-3">
            <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M8 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Registrando póliza Número: <span class="text-indigo-400"><?= htmlspecialchars($siguienteNumero) ?></span>
        </h1>
        <input type="hidden" name="numero_poliza" value="<?= htmlspecialchars($siguienteNumero) ?>">

        <div class="grid md:grid-cols-2 gap-6">

            <!-- Tipo de póliza -->
            <div>
                <label class="block text-indigo-300 mb-1">Tipo de Póliza</label>
                <select name="tipo_poliza" id="tipo-poliza"
                    class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="Clásica">Clásica</option>
                    <option value="Plus">Plus</option>
                </select>
            </div>

            <!-- Asesor inmobiliario -->
            <div>
                <label class="block text-indigo-300 mb-1">Asesor inmobiliario</label>
                <select name="id_asesor" id="asesor-select" class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Selecciona un asesor</option>
                    <?php foreach ($asesores as $as): ?>
                        <option value="<?= $as['id'] ?>"><?= htmlspecialchars($as['nombre_asesor']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Arrendador -->
            <div>
                <label class="block text-indigo-300 mb-1">Arrendador</label>
                <select name="id_arrendador" id="arrendador-select" class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Selecciona un arrendador</option>
                    <?php foreach ($arrendadores as $arr): ?>
                        <option value="<?= $arr['id'] ?>"><?= htmlspecialchars($arr['nombre_arrendador']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Inmueble -->
            <div>
                <label class="block text-indigo-300 mb-1">Inmueble</label>
                <div class="flex items-center gap-2">
                    <select name="id_inmueble" id="inmueble-select"
                        class="flex-1 appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">- SELECCIONA UN INMUEBLE -</option>
                        <?php foreach ($inmuebles as $inm):
                            $pk         = (string)($inm['pk'] ?? '');
                            $sk         = (string)($inm['sk'] ?? '');
                            $virtualId  = $inm['id_virtual'] ?? ($pk !== '' && $sk !== '' ? $pk . '|' . $sk : '');
                            $legacyId   = isset($inm['id']) ? (string)$inm['id'] : '';
                            $optionVal  = $legacyId !== '' ? $legacyId : $virtualId;
                            if ($optionVal === '') {
                                continue;
                            }
                            $direccion  = (string)($inm['direccion_inmueble'] ?? ($virtualId !== '' ? $virtualId : 'SIN DIRECCIÓN'));
                            $renta      = (string)($inm['renta'] ?? '');
                            $tipo       = (string)($inm['tipo'] ?? '');
                        ?>
                            <option
                                value="<?= htmlspecialchars($optionVal) ?>"
                                data-pk="<?= htmlspecialchars($pk) ?>"
                                data-sk="<?= htmlspecialchars($sk) ?>"
                                data-virtual-id="<?= htmlspecialchars($virtualId) ?>"
                                data-legacy-id="<?= htmlspecialchars($legacyId) ?>"
                                data-monto="<?= htmlspecialchars($renta) ?>"
                                data-tipo="<?= htmlspecialchars($tipo) ?>"
                            ><?= htmlspecialchars($direccion) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="btn-editar-inmueble"
                        class="shrink-0 inline-flex items-center justify-center p-2 rounded-lg bg-slate-600 text-white hover:bg-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-400 disabled:opacity-60 disabled:cursor-not-allowed"
                        title="Editar inmueble">
                        <span class="sr-only">Editar inmueble</span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L8.651 18.001a4.5 4.5 0 01-1.897 1.13l-2.685.77.77-2.685a4.5 4.5 0 011.13-1.897L16.862 4.487z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 7.125L16.875 4.5" />
                        </svg>
                    </button>
                </div>
            </div>


<!-- Tipo de inmueble -->
            <div>
                <label class="block text-indigo-300 mb-1">Tipo de inmueble</label>
                <select name="tipo_inmueble" class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">- SELECCIONE UNA OPCIÓN -</option>
                    <?php foreach (['Departamento', 'Casa', 'Terreno', 'Local Comercial', 'Oficinas', 'Edificio'] as $opt): ?>
                        <option value="<?= $opt ?>"><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

                        <!-- Monto de renta (readonly) -->
            <div>
                <label class="block text-indigo-300 mb-1">Monto de renta</label>
                <div class="flex items-center gap-2">
                    <input type="text" id="monto-renta-display" readonly
                        class="flex-1 px-3 py-2 rounded-lg bg-[#1e1e2d] text-indigo-200 border border-indigo-800 cursor-not-allowed">
                    <button type="button" id="btn-refrescar-renta"
                        class="shrink-0 inline-flex items-center justify-center p-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                        title="Refrescar monto de renta">
                        <span class="sr-only">Refrescar monto de renta</span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.023 9.348h4.992m0 0V4.356m0 4.992l-3.181-3.181a8.25 8.25 0 10.63 10.698" />
                        </svg>
                    </button>
                </div>
                <input type="hidden" name="monto_renta" id="monto-renta-hidden">
            </div>

<!-- Monto de póliza -->
            <div>
                <label class="block text-indigo-300 mb-1">Monto póliza</label>
                <input type="number" step="0.01" name="monto_poliza" id="monto-poliza"
                    class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <!-- Fecha de inicio -->
            <div>
                <label class="block text-indigo-300 mb-1">Fecha de inicio</label>
                <input type="date" name="fecha_poliza" id="fecha-inicio"
                    value="<?= $hoy ?>"
                    class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <!-- Fecha de fin -->
            <div>
                <label class="block text-indigo-300 mb-1">Fecha de fin</label>
                <input type="date" name="fecha_fin" id="fecha-fin"
                    value="<?= $fin ?>"
                    class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <!-- Vigencia -->
            <div>
                <label class="block text-indigo-300 mb-1">Vigencia</label>
                <input type="text" name="vigencia" id="vigencia-texto"
                    class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" readonly>
            </div>

            <!-- Inquilino -->
            <div>
                <label class="block text-indigo-300 mb-1">Inquilino</label>
                <select name="id_inquilino" id="id_inquilino" class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">- SELECCIONA UN INQUILINO -</option>
                    <?php foreach ($inquilinos as $inq): ?>
                        <option value="<?= $inq['id'] ?>">
                            <?= htmlspecialchars(trim($inq['nombre_inquilino'] . ' ' . $inq['apellidop_inquilino'] . ' ' . $inq['apellidom_inquilino'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Obligado solidario -->
            <div>
                <label class="block text-indigo-300 mb-1">Obligado solidario</label>
                <select name="id_obligado" class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" id="id_obligado">
                    <option value="">Selecciona un obligado solidario</option>
                    <?php foreach ($obligados as $os): ?>
                        <option value="<?= $os['id'] ?>">
                            <?= htmlspecialchars(trim($os['nombre_inquilino'] . ' ' . $os['apellidop_inquilino'] . ' ' . $os['apellidom_inquilino'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Fiador -->
            <div>
                <label class="block text-indigo-300 mb-1">Fiador</label>
                <select name="id_fiador" class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" id="id_fiador">
                    <option value="">- SELECCIONA UN FIADOR -</option>
                    <?php foreach ($fiadores as $f):
                        $idOpt   = (string)($f['id'] ?? '');
                        $idSel   = (string)($fiadorSeleccionado ?? '');
                        $nombre  = trim(
                            ($f['nombre_inquilino']    ?? '') . ' ' .
                                ($f['apellidop_inquilino'] ?? '') . ' ' .
                                ($f['apellidom_inquilino'] ?? '')
                        );
                    ?>
                        <option value="<?= htmlspecialchars($idOpt) ?>">
                            <?= htmlspecialchars($nombre !== '' ? $nombre : 'SIN NOMBRE') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- Comisión Asesor (editable) -->
            <div>
                <label class="block text-indigo-300 mb-1">Comisión del Asesor (<span id="comision-porcentaje-label">20%</span>)</label>
                <div class="flex items-center gap-2">
                    <input type="text" id="comision-asesor" readonly
                        class="flex-1 appearance-none px-4 py-2 rounded-lg bg-[#1c1c2a] border border-indigo-800 text-indigo-400 font-semibold cursor-not-allowed">
                    <button type="button" id="btn-editar-comision"
                        class="shrink-0 inline-flex items-center justify-center p-2 rounded-lg bg-slate-600 text-white hover:bg-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-400"
                        title="Editar porcentaje de comisión">
                        <span class="sr-only">Editar porcentaje de comisión</span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                            class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L8.651 18.001a4.5 4.5 0 01-1.897 1.13l-2.685.77.77-2.685a4.5 4.5 0 011.13-1.897L16.862 4.487z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 7.125L16.875 4.5" />
                        </svg>
                    </button>
                </div>
                <input type="hidden" name="porcentaje_comision" id="comision-porcentaje" value="20">
            </div>

        </div>

        <!-- Comentarios -->
        <div>
            <label class="block text-indigo-300 mb-1">Comentarios</label>
            <textarea name="comentarios" rows="3"
                class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" id="comentarios"></textarea>
        </div>

        <!-- Botón de guardar -->
        <div class="flex justify-end">
            <button type="submit" class="px-6 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white shadow font-semibold">
                Guardar
            </button>
        </div>
    </form>

    <div id="modal-editar-inmueble" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50 px-3">
        <div class="relative w-full max-w-3xl bg-[#1f1f2e] border border-indigo-900/40 rounded-2xl shadow-2xl overflow-hidden">
            <button type="button" id="modal-editar-inmueble-close" class="absolute top-3 right-3 text-indigo-200 hover:text-pink-400 transition">
                <span class="sr-only">Cerrar</span>
                &times;
            </button>
            <div class="px-6 pt-8 pb-4 border-b border-white/10">
                <h3 class="text-xl font-semibold text-indigo-100">Editar inmueble</h3>
                <p class="text-sm text-indigo-300 mt-1">Actualiza la información del inmueble y guarda los cambios.</p>
            </div>
            <div id="modal-editar-inmueble-loader" class="hidden px-6 py-4 text-sm text-indigo-200">Cargando información del inmueble...</div>
            <form id="form-editar-inmueble" class="px-6 py-6 space-y-5">
                <input type="hidden" name="pk" value="">
                <input type="hidden" name="sk" value="">
                <input type="hidden" name="id" value="">
                <input type="hidden" name="asesor_pk" value="">

                <div>
                    <label class="block text-sm text-indigo-200 mb-1" for="edit-direccion-inmueble">Dirección completa</label>
                    <textarea id="edit-direccion-inmueble" name="direccion_inmueble" rows="2" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-indigo-200 mb-1" for="edit-tipo-inmueble">Tipo</label>
                        <select id="edit-tipo-inmueble" name="tipo" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">Selecciona una opción</option>
                            <option value="Departamento">Departamento</option>
                            <option value="Casa">Casa</option>
                            <option value="Local Comercial">Local Comercial</option>
                            <option value="Oficina">Oficina</option>
                            <option value="Terreno">Terreno</option>
                            <option value="Bodega">Bodega</option>
                            <option value="Edificio">Edificio</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-indigo-200 mb-1" for="edit-renta">Renta mensual (MXN)</label>
                        <input id="edit-renta" type="number" step="0.01" min="0" name="renta" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-indigo-200 mb-1" for="edit-mantenimiento">¿Incluye mantenimiento?</label>
                        <select id="edit-mantenimiento" name="mantenimiento" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="Si">Si</option>
                            <option value="No">No</option>
                            <option value="na">No Aplica</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-indigo-200 mb-1" for="edit-monto-mantenimiento">Monto mantenimiento (MXN)</label>
                        <input id="edit-monto-mantenimiento" type="number" step="0.01" min="0" name="monto_mantenimiento" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-indigo-200 mb-1" for="edit-deposito">Depósito (MXN)</label>
                        <input id="edit-deposito" type="number" step="0.01" min="0" name="deposito" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm text-indigo-200 mb-1" for="edit-estacionamiento">Estacionamientos</label>
                        <input id="edit-estacionamiento" type="number" min="0" name="estacionamiento" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-indigo-200 mb-1" for="edit-mascotas">¿Permite mascotas?</label>
                        <select id="edit-mascotas" name="mascotas" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="NO">No</option>
                            <option value="SI">Sí</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-indigo-200 mb-1" for="edit-comentarios">Comentarios</label>
                        <textarea id="edit-comentarios" name="comentarios" rows="2" class="w-full rounded-lg px-4 py-2 bg-[#232336] text-indigo-100 border border-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" class="px-4 py-2 rounded-lg bg-gray-600 hover:bg-gray-500 text-white" data-close-modal>Cancelar</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white" data-submit>Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('form-nueva-poliza');
        if (!form) return;

        const submitBtn = form.querySelector('button[type="submit"]');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // UI: bloquear botón
            const prevText = submitBtn ? submitBtn.textContent : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Guardando...';
            }

            try {
                const fd = new FormData(form);
                console.log(...fd.entries());

                const resp = await fetch(form.action, {
                    method: 'POST',
                    body: fd,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const raw = await resp.text();
                let data;
                try {
                    data = JSON.parse(raw);
                } catch {
                    throw new Error(raw);
                }

                if (data.ok) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Póliza registrada',
                        text: `La póliza ${data.numero} se ha registrado exitosamente`
                    });
                    window.location = `${BASE_URL}/polizas/${data.numero}`;
                } else {
                    Swal.fire('Error', data.error || 'No se pudo guardar', 'error');
                }
            } catch (err) {
                Swal.fire('Error', String(err).slice(0, 500), 'error');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = prevText || 'Guardar';
                }
            }
        });
        // ---------- Helpers dinero ----------
        function parseMoneyToNumber(v) {
            if (typeof v === 'number') return v;
            if (!v) return 0;
            v = String(v).replace(/\s|\$/g, '');
            if (/\.\d{3},\d{2}$/.test(v)) {
                v = v.replace(/\./g, '').replace(',', '.');
            } else {
                v = v.replace(/,/g, '.');
            }
            const n = parseFloat(v);
            return isNaN(n) ? 0 : n;
        }

        function formatCurrency(n) {
            const num = typeof n === 'number' ? n : parseMoneyToNumber(n);
            return num.toLocaleString('es-MX', {
                style: 'currency',
                currency: 'MXN'
            });
        }


        // ---------- Regla de precios ----------
        function calcularPoliza(montoRenta, tipoPoliza) {
            let precio = 0,
                r = parseMoneyToNumber(montoRenta);
            if (tipoPoliza === 'Clásica') {
                if (r < 10001) precio = 3700;
                else if (r < 15001) precio = 4300;
                else if (r < 20001) precio = 4500;
                else if (r < 25001) precio = 5200;
                else if (r < 30001) precio = 5500;
                else if (r < 35001) precio = 8100;
                else if (r < 40001) precio = 9300;
                else if (r < 45001) precio = 10000;
                else if (r < 50001) precio = 12000;
                else precio = r * 0.25;
            } else if (tipoPoliza === 'Plus') {
                if (r < 10001) precio = 4800;
                else if (r < 15001) precio = 5500;
                else if (r < 20001) precio = 7500;
                else if (r < 25001) precio = 8600;
                else if (r < 30001) precio = 9400;
                else if (r < 35001) precio = 11000;
                else if (r < 40001) precio = 11500;
                else if (r < 45001) precio = 13750;
                else if (r < 50001) precio = 14250;
                else precio = r * 0.30;
            }
            return Number(precio.toFixed(2));
        }

        // ---------- DOM refs ----------
        const asesorSel = document.getElementById('asesor-select');
        const arrendadorSel = document.getElementById('arrendador-select');
        const inmuebleSel = document.getElementById('inmueble-select');
        const tipoInmuebleSel = document.querySelector('select[name="tipo_inmueble"]');
        const tipoPolizaSel = document.getElementById('tipo-poliza');
        const rentaDisplay = document.getElementById('monto-renta-display');
        const rentaHidden = document.getElementById('monto-renta-hidden');
        const montoPolizaInput = document.getElementById('monto-poliza');
        const comisionInput = document.getElementById('comision-asesor');
        const editCommissionBtn = document.getElementById('btn-editar-comision');
        const commissionPercentageHidden = document.getElementById('comision-porcentaje');
        const commissionPercentageLabel = document.getElementById('comision-porcentaje-label');
        let commissionPercentage = Number(commissionPercentageHidden?.value ?? '20');
        if (!Number.isFinite(commissionPercentage) || commissionPercentage < 0) {
            commissionPercentage = 20;
        }
        const MIN_COMMISSION_PERCENT = 0;
        const MAX_COMMISSION_PERCENT = 100;

        const refreshRentBtn = document.getElementById('btn-refrescar-renta');
        const editInmuebleBtn = document.getElementById('btn-editar-inmueble');
        const editModal = document.getElementById('modal-editar-inmueble');
        const editModalClose = document.getElementById('modal-editar-inmueble-close');
        const editModalForm = document.getElementById('form-editar-inmueble');
        const editModalLoader = document.getElementById('modal-editar-inmueble-loader');
        const editModalCancel = editModalForm ? editModalForm.querySelector('[data-close-modal]') : null;
        const editModalSubmit = editModalForm ? editModalForm.querySelector('[data-submit]') : null;

        function getSelectedInmuebleOption() {
            if (!inmuebleSel) return null;
            const index = inmuebleSel.selectedIndex;
            return index >= 0 ? inmuebleSel.options[index] : null;
        }

        function updateEditButtonState() {
            if (!editInmuebleBtn) {
                return;
            }
            const option = getSelectedInmuebleOption();
            const hasValue = Boolean(option && option.value);
            const hasIdentifiers = Boolean(option && (option.dataset.legacyId || (option.dataset.pk && option.dataset.sk)));
            const enabled = hasValue && hasIdentifiers;
            editInmuebleBtn.disabled = !enabled;
            editInmuebleBtn.classList.toggle('opacity-60', !enabled);
            editInmuebleBtn.classList.toggle('cursor-not-allowed', !enabled);
        }

        function toggleEditModal(show) {
            if (!editModal) {
                return;
            }
            if (show) {
                editModal.classList.remove('hidden');
                editModal.classList.add('flex');
            } else {
                editModal.classList.add('hidden');
                editModal.classList.remove('flex');
            }
        }

        function setEditModalLoading(isLoading) {
            if (!editModalForm) {
                return;
            }
            if (editModalLoader) {
                editModalLoader.classList.toggle('hidden', !isLoading);
            }
            editModalForm.classList.toggle('pointer-events-none', isLoading);
            editModalForm.classList.toggle('opacity-50', isLoading);
        }

        function closeEditModal() {
            toggleEditModal(false);
            setEditModalLoading(false);
            editModalForm?.reset();
        }

        function sanitizeModalAmount(value) {
            if (typeof value === 'number' && Number.isFinite(value)) {
                return value.toFixed(2);
            }
            if (typeof value !== 'string') {
                return '';
            }
            const normalized = value.replace(/[^0-9.,-]/g, '').replace(/,/g, '.');
            if (normalized === '') {
                return '';
            }
            const numeric = Number(normalized);
            return Number.isFinite(numeric) ? numeric.toFixed(2) : '';
        }

        function fillEditModalForm(data, arrendadorValue, option) {
            if (!editModalForm) {
                return;
            }
            editModalForm.reset();

            const pkField = editModalForm.querySelector('input[name="pk"]');
            if (pkField) {
                pkField.value = arrendadorValue || '';
            }

            const skField = editModalForm.querySelector('input[name="sk"]');
            const optionSk = option?.dataset?.sk ?? '';
            const dataSk = typeof data?.sk === 'string' && data.sk !== '' ? data.sk : optionSk;
            if (skField) {
                skField.value = dataSk;
            }

            const idField = editModalForm.querySelector('input[name="id"]');
            let idValue = data?.id ?? data?.id_inmueble ?? data?.legacy_id ?? data?.inmueble_id ?? '';
            if (!idValue && option?.dataset?.legacyId) {
                idValue = option.dataset.legacyId;
            }
            if (idField) {
                idField.value = idValue ? String(idValue) : '';
            }

            const asesorField = editModalForm.querySelector('input[name="asesor_pk"]');
            const asesorValue = data?.id_asesor ?? data?.asesor_pk ?? (asesorSel?.value || '');
            if (asesorField) {
                asesorField.value = asesorValue !== undefined && asesorValue !== null ? String(asesorValue) : '';
            }

            const direccionField = editModalForm.querySelector('#edit-direccion-inmueble');
            if (direccionField) {
                direccionField.value = typeof data?.direccion_inmueble === 'string' ? data.direccion_inmueble : '';
            }

            const tipoField = editModalForm.querySelector('#edit-tipo-inmueble');
            if (tipoField) {
                tipoField.value = typeof data?.tipo === 'string' ? data.tipo : '';
            }

            const rentaField = editModalForm.querySelector('#edit-renta');
            if (rentaField) {
                rentaField.value = sanitizeModalAmount(data?.renta ?? '');
            }

            const mantenimientoField = editModalForm.querySelector('#edit-mantenimiento');
            if (mantenimientoField) {
                const raw = typeof data?.mantenimiento === 'string' ? data.mantenimiento.toUpperCase().trim() : '';
                let value = 'No';
                if (raw === 'SI') {
                    value = 'Si';
                } else if (raw === 'NA' || raw === 'NO_APLICA') {
                    value = 'na';
                }
                mantenimientoField.value = value;
            }

            const montoMantenimientoField = editModalForm.querySelector('#edit-monto-mantenimiento');
            if (montoMantenimientoField) {
                montoMantenimientoField.value = sanitizeModalAmount(data?.monto_mantenimiento ?? '');
            }

            const depositoField = editModalForm.querySelector('#edit-deposito');
            if (depositoField) {
                depositoField.value = sanitizeModalAmount(data?.deposito ?? '');
            }

            const estacionamientoField = editModalForm.querySelector('#edit-estacionamiento');
            if (estacionamientoField) {
                const rawValue = Number.parseInt(data?.estacionamiento ?? data?.num_estacionamientos ?? '', 10);
                estacionamientoField.value = Number.isFinite(rawValue) ? String(rawValue) : '';
            }

            const mascotasField = editModalForm.querySelector('#edit-mascotas');
            if (mascotasField) {
                const rawMascotas = typeof data?.mascotas === 'string' ? data.mascotas.toUpperCase().trim() : '';
                mascotasField.value = rawMascotas === 'SI' ? 'SI' : 'NO';
            }

            const comentariosField = editModalForm.querySelector('#edit-comentarios');
            if (comentariosField) {
                comentariosField.value = typeof data?.comentarios === 'string' ? data.comentarios : '';
            }
        }

        async function fetchInmuebleData(option) {
            if (!option) {
                throw new Error('Selecciona un inmueble.');
            }
            const legacyId = option.dataset?.legacyId;
            const pkAttr = option.dataset?.pk;
            const skAttr = option.dataset?.sk;
            let url = '';
            if (legacyId) {
                url = `${BASE_URL}/inmuebles/info/${encodeURIComponent(legacyId)}`;
            } else if (pkAttr && skAttr) {
                url = `${BASE_URL}/inmuebles/info/${encodeURIComponent(pkAttr)}/${encodeURIComponent(skAttr)}`;
            } else {
                throw new Error('No se encontraron identificadores del inmueble.');
            }
            const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!response.ok) {
                throw new Error('No se pudo consultar el inmueble.');
            }
            const data = await response.json();
            if (!data || typeof data !== 'object') {
                throw new Error('Información del inmueble inválida.');
            }
            return data;
        }

        function applyInmuebleDataToUI(data, option) {
            if (!option || !data) {
                return;
            }
            if (typeof data.pk === 'string' && data.pk !== '') {
                option.dataset.pk = data.pk;
            }
            if (typeof data.sk === 'string' && data.sk !== '') {
                option.dataset.sk = data.sk;
            }
            if (data.id !== undefined && data.id !== null && data.id !== '') {
                option.dataset.legacyId = String(data.id);
                if (/^\d+$/.test(String(data.id))) {
                    option.value = String(data.id);
                    inmuebleSel.value = option.value;
                }
            }
            if (typeof data.id_virtual === 'string') {
                option.dataset.virtualId = data.id_virtual;
            }
            if (typeof data.tipo === 'string') {
                option.dataset.tipo = data.tipo;
            }
            const rentaBruta = data.renta ?? option.dataset.monto ?? '';
            option.dataset.monto = rentaBruta ?? '';
            if (typeof data.direccion_inmueble === 'string' && data.direccion_inmueble !== '') {
                option.textContent = data.direccion_inmueble;
            }
            if (option.selected) {
                if (typeof data.tipo === 'string' && tipoInmuebleSel) {
                    tipoInmuebleSel.value = data.tipo;
                }
                const rentaValor = parseMoneyToNumber(rentaBruta);
                rentaHidden.value = rentaValor ? String(rentaValor) : '';
                rentaDisplay.value = rentaValor ? formatCurrency(rentaValor) : '';
                const precio = rentaValor > 0 ? calcularPoliza(rentaValor, tipoPolizaSel.value) : 0;
                montoPolizaInput.value = precio ? String(precio) : '';
                actualizarComision();
                if (option.dataset.pk) {
                    inmueblePkHidden.value = option.dataset.pk;
                }
                if (option.dataset.sk) {
                    inmuebleSkHidden.value = option.dataset.sk;
                }
            }
        }

        async function refreshSelectedInmueble({ showSuccess = true } = {}) {
            const option = getSelectedInmuebleOption();
            if (!option || !option.value) {
                throw new Error('Selecciona un inmueble para refrescar la información.');
            }
            const data = await fetchInmuebleData(option);
            applyInmuebleDataToUI(data, option);
            updateEditButtonState();
            if (showSuccess) {
                Swal.fire({
                    icon: 'success',
                    title: 'Renta actualizada',
                    text: 'El monto se sincronizó desde el inmueble.',
                });
            }
            return data;
        }

        const inmueblePkHidden = document.createElement('input');
        inmueblePkHidden.type = 'hidden';
        inmueblePkHidden.name = 'inmueble_pk';
        form.appendChild(inmueblePkHidden);

        const inmuebleSkHidden = document.createElement('input');
        inmuebleSkHidden.type = 'hidden';
        inmuebleSkHidden.name = 'inmueble_sk';
        form.appendChild(inmuebleSkHidden);

        const fechaInicioInput = document.getElementById('fecha-inicio');
        const fechaFinInput = document.getElementById('fecha-fin');
        const vigenciaInput = document.getElementById('vigencia-texto');

        // ---------- Comisión ----------
        function formatCommissionPercentageDisplay(value) {
            const numeric = Number(value);
            if (!Number.isFinite(numeric)) {
                return '0%';
            }
            const normalized = Math.max(MIN_COMMISSION_PERCENT, Math.round(numeric * 100) / 100);
            return `${normalized.toLocaleString('es-MX', { minimumFractionDigits: 0, maximumFractionDigits: 2 })}%`;
        }

        function updateCommissionPercentageUI() {
            if (commissionPercentageHidden) {
                commissionPercentageHidden.value = commissionPercentage.toString();
            }
            if (commissionPercentageLabel) {
                commissionPercentageLabel.textContent = formatCommissionPercentageDisplay(commissionPercentage);
            }
        }

        function actualizarComision() {
            updateCommissionPercentageUI();
            const monto = parseMoneyToNumber(montoPolizaInput.value);
            if (monto > 0) {
                const decimal = Math.max(commissionPercentage, MIN_COMMISSION_PERCENT) / 100;
                const comisionCalculada = monto * decimal;
                comisionInput.value = formatCurrency(comisionCalculada);
            } else {
                comisionInput.value = '';
            }
        }

        function setCommissionPercentage(value) {
            if (!Number.isFinite(value)) {
                return;
            }
            const sanitized = Math.min(Math.max(value, MIN_COMMISSION_PERCENT), MAX_COMMISSION_PERCENT);
            commissionPercentage = Math.round(sanitized * 100) / 100;
            actualizarComision();
        }

        // ---------- Fechas / Vigencia ----------
        const MESES = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        function ymd(d) { // Date -> 'YYYY-MM-DD'
            const y = d.getFullYear(),
                m = String(d.getMonth() + 1).padStart(2, '0'),
                da = String(d.getDate()).padStart(2, '0');
            return `${y}-${m}-${da}`;
        }

        function fechaESLarga(ymdStr) {
            if (!ymdStr) return '';
            const [y, m, d] = ymdStr.split('-'); // '2025-08-11'
            const mes = MESES[parseInt(m, 10) - 1];
            return `${d} de ${mes} de ${y}`;
        }

        function recalcularFechaFinDesdeInicio() {
            if (!fechaInicioInput.value) return;
            const base = new Date(fechaInicioInput.value + 'T00:00:00'); // evita desfases por zona horaria
            const fin = new Date(base);
            fin.setFullYear(fin.getFullYear() + 1);
            fin.setDate(fin.getDate() - 1);
            fechaFinInput.value = ymd(fin);
        }

        function actualizarVigencia() {
            if (fechaInicioInput.value && fechaFinInput.value) {
                vigenciaInput.value = `del ${fechaESLarga(fechaInicioInput.value)} al ${fechaESLarga(fechaFinInput.value)}`;
            } else {
                vigenciaInput.value = '';
            }
        }

        // Listeners fechas
        fechaInicioInput.addEventListener('change', () => {
            recalcularFechaFinDesdeInicio();
            actualizarVigencia();
        });
        fechaFinInput.addEventListener('change', actualizarVigencia);

        // ---------- Carga dependientes ----------
        function clearInmuebleDetails() {
            tipoInmuebleSel.value = '';
            rentaDisplay.value = '';
            rentaHidden.value = '';
            montoPolizaInput.value = '';
            actualizarComision();
            inmueblePkHidden.value = '';
            inmuebleSkHidden.value = '';
            updateEditButtonState();
        }

        function resetInmuebleSelect(showLoading = false) {
            if (showLoading) {
                inmuebleSel.innerHTML = '<option value="">Cargando...</option>';
            } else {
                inmuebleSel.innerHTML = '<option value="">- SELECCIONA UN INMUEBLE -</option>';
            }
            clearInmuebleDetails();
            updateEditButtonState();
        }

        function populateInmuebleOptions(inmuebles) {
            inmuebleSel.innerHTML = '';

            const defaultOpt = document.createElement('option');
            defaultOpt.value = '';
            defaultOpt.textContent = '- SELECCIONA UN INMUEBLE -';
            inmuebleSel.appendChild(defaultOpt);

            if (!Array.isArray(inmuebles)) {
                return;
            }

            inmuebles.forEach((inmueble) => {
                const pk = typeof inmueble.pk === 'string' ? inmueble.pk : '';
                const sk = typeof inmueble.sk === 'string' ? inmueble.sk : '';
                const virtualId = typeof inmueble.id_virtual === 'string' && inmueble.id_virtual !== ''
                    ? inmueble.id_virtual
                    : (pk && sk ? `${pk}|${sk}` : '');
                const legacyId = inmueble.id ?? inmueble.legacy_id ?? '';
                const value = legacyId !== '' ? String(legacyId) : virtualId;
                if (!value) {
                    return;
                }
                const label = inmueble.direccion_inmueble || virtualId || 'SIN DIRECCIÓN';
                const option = document.createElement('option');
                option.value = value;
                option.textContent = label;
                option.dataset.pk = pk;
                option.dataset.sk = sk;
                option.dataset.virtualId = virtualId;
                option.dataset.legacyId = legacyId !== '' ? String(legacyId) : '';
                option.dataset.monto = inmueble.renta ?? '';
                option.dataset.tipo = inmueble.tipo ?? '';
                inmuebleSel.appendChild(option);
            });

            updateEditButtonState();
        }

        refreshRentBtn?.addEventListener('click', async () => {
            const option = getSelectedInmuebleOption();
            if (!option || !option.value) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Selecciona un inmueble',
                    text: 'Debes elegir un inmueble para refrescar la renta.',
                });
                return;
            }

            refreshRentBtn.disabled = true;
            refreshRentBtn.classList.add('opacity-60', 'cursor-not-allowed');
            const icon = refreshRentBtn.querySelector('svg');
            icon?.classList.add('animate-spin');

            try {
                await refreshSelectedInmueble({ showSuccess: true });
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error instanceof Error ? error.message : 'No se pudo refrescar la renta.',
                });
            } finally {
                refreshRentBtn.disabled = false;
                refreshRentBtn.classList.remove('opacity-60', 'cursor-not-allowed');
                icon?.classList.remove('animate-spin');
            }
        });

        editInmuebleBtn?.addEventListener('click', async () => {
            const arrendadorValue = arrendadorSel?.value || '';
            if (!arrendadorValue) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Selecciona un arrendador',
                    text: 'Debes elegir un arrendador antes de editar el inmueble.',
                });
                return;
            }

            const option = getSelectedInmuebleOption();
            if (!option || !option.value) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Selecciona un inmueble',
                    text: 'Debes elegir un inmueble para editarlo.',
                });
                return;
            }

            toggleEditModal(true);
            setEditModalLoading(true);

            try {
                const data = await fetchInmuebleData(option);
                fillEditModalForm(data, arrendadorValue, option);
                setEditModalLoading(false);
            } catch (error) {
                setEditModalLoading(false);
                toggleEditModal(false);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error instanceof Error ? error.message : 'No se pudo cargar el inmueble.',
                });
            }
        });

        editCommissionBtn?.addEventListener('click', async () => {
            const result = await Swal.fire({
                title: 'Editar porcentaje de comisión',
                input: 'number',
                inputLabel: 'Ingresa el porcentaje de comisión para el asesor',
                inputValue: commissionPercentage.toString(),
                inputAttributes: {
                    min: String(MIN_COMMISSION_PERCENT),
                    max: String(MAX_COMMISSION_PERCENT),
                    step: '0.01',
                },
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar',
                showCancelButton: true,
                inputValidator: (value) => {
                    if (value === '' || value === null) {
                        return 'Ingresa un porcentaje válido.';
                    }
                    const numeric = Number(value);
                    if (!Number.isFinite(numeric)) {
                        return 'Ingresa un número válido.';
                    }
                    if (numeric < MIN_COMMISSION_PERCENT || numeric > MAX_COMMISSION_PERCENT) {
                        return `Ingresa un valor entre ${MIN_COMMISSION_PERCENT} y ${MAX_COMMISSION_PERCENT}.`;
                    }
                    return null;
                },
            });

            if (result.isConfirmed) {
                const nuevoPorcentaje = Number(result.value);
                if (Number.isFinite(nuevoPorcentaje)) {
                    setCommissionPercentage(nuevoPorcentaje);
                    Swal.fire({
                        icon: 'success',
                        title: 'Porcentaje actualizado',
                        text: `La comisión se calculará con ${formatCommissionPercentageDisplay(commissionPercentage)}.`,
                        timer: 2000,
                        showConfirmButton: false,
                    });
                }
            }
        });

        editModalClose?.addEventListener('click', closeEditModal);
        editModalCancel?.addEventListener('click', closeEditModal);
        editModal?.addEventListener('click', (event) => {
            if (event.target === editModal) {
                closeEditModal();
            }
        });

        editModalForm?.addEventListener('submit', async (event) => {
            event.preventDefault();

            const arrendadorValue = arrendadorSel?.value || '';
            if (!arrendadorValue) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Selecciona un arrendador',
                    text: 'Debes elegir un arrendador antes de guardar el inmueble.',
                });
                return;
            }

            const option = getSelectedInmuebleOption();
            if (!option || !option.value) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Selecciona un inmueble',
                    text: 'Debes elegir un inmueble para actualizarlo.',
                });
                return;
            }

            const formData = new FormData(editModalForm);
            formData.set('pk', arrendadorValue);
            formData.set('id_arrendador', arrendadorValue);
            const asesorValue = asesorSel?.value || '';
            if (asesorValue) {
                formData.set('asesor_pk', asesorValue);
            }

            if (editModalSubmit) {
                editModalSubmit.disabled = true;
                editModalSubmit.classList.add('opacity-75');
            }

            try {
                const response = await fetch(`${BASE_URL}/inmuebles/update`, {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' },
                });

                let result = null;
                try {
                    result = await response.json();
                } catch (parseError) {
                    result = null;
                }

                if (!response.ok || !result?.ok) {
                    const message = result?.mensaje || result?.error || 'No se pudo actualizar el inmueble.';
                    throw new Error(message);
                }

                closeEditModal();
                await refreshSelectedInmueble({ showSuccess: false });
                Swal.fire({
                    icon: 'success',
                    title: 'Inmueble actualizado',
                    text: 'Los cambios se guardaron correctamente.',
                });
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error instanceof Error ? error.message : 'No se pudo actualizar el inmueble.',
                });
            } finally {
                if (editModalSubmit) {
                    editModalSubmit.disabled = false;
                    editModalSubmit.classList.remove('opacity-75');
                }
            }
        });

        updateEditButtonState();

        asesorSel.addEventListener('change', async function() {
            const id = this.value;
            arrendadorSel.innerHTML = '<option value="">Cargando...</option>';
            resetInmuebleSelect();

            if (!id) {
                arrendadorSel.innerHTML = '<option value="">Selecciona un arrendador</option>';
                return;
            }
            const resp = await fetch(BASE_URL + '/arrendadores/por-asesor/' + id);
            const data = await resp.json();
            let opts = '<option value="">Selecciona un arrendador</option>';
            data.forEach(a => {
                opts += `<option value="${a.id}">${a.nombre_arrendador}</option>`;
            });
            arrendadorSel.innerHTML = opts;
            updateEditButtonState();
        });

        arrendadorSel.addEventListener('change', async function() {
            const id = this.value;
            resetInmuebleSelect(true);

            if (!id) {
                resetInmuebleSelect();
                updateEditButtonState();
                return;
            }

            try {
                const resp = await fetch(`${BASE_URL}/inmuebles/por-arrendador/${encodeURIComponent(id)}`);
                const data = await resp.json();
                populateInmuebleOptions(Array.isArray(data) ? data : []);
            } catch (err) {
                console.error('Error cargando inmuebles', err);
                resetInmuebleSelect();
            }
        });

        inmuebleSel.addEventListener('change', function() {
            const id = this.value;
            if (!id) {
                clearInmuebleDetails();
                updateEditButtonState();
                return;
            }
            const option = this.options[this.selectedIndex];
            const pk = option?.dataset?.pk ?? '';
            const sk = option?.dataset?.sk ?? '';
            const virtualId = option?.dataset?.virtualId ?? (pk && sk ? `${pk}|${sk}` : '');
            const renta = option?.dataset?.monto ?? '';
            const tipo = option?.dataset?.tipo ?? '';
            const hasLegacy = option?.dataset?.legacyId ?? '';

            if (!hasLegacy && virtualId && this.value !== virtualId) {
                this.value = virtualId;
            }

            tipoInmuebleSel.value = tipo || '';
            rentaHidden.value = parseMoneyToNumber(renta);
            rentaDisplay.value = rentaHidden.value ? formatCurrency(rentaHidden.value) : '';

            const precio = calcularPoliza(rentaHidden.value, tipoPolizaSel.value);
            montoPolizaInput.value = String(precio);
            actualizarComision();

            inmueblePkHidden.value = pk;
            inmuebleSkHidden.value = sk;
            updateEditButtonState();
        });

        tipoPolizaSel.addEventListener('change', function() {
            const renta = parseMoneyToNumber(rentaHidden.value);
            if (renta > 0) {
                const precio = calcularPoliza(renta, tipoPolizaSel.value);
                montoPolizaInput.value = String(precio);
                actualizarComision();
            }
        });

        // Usuario edita monto manualmente
        montoPolizaInput.addEventListener('input', actualizarComision);
        montoPolizaInput.addEventListener('change', actualizarComision);

        // ---------- Inicialización ----------
        // Con valores por defecto del servidor ($hoy / $fin) ya rellenados:
        actualizarVigencia(); // pinta la vigencia en la carga inicial
        actualizarComision(); // por si hay valor inicial en monto de póliza
    });
</script>