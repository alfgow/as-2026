<?php

/** @var array $poliza */
/** @var array $inmuebles */
/** @var array $arrendadores */
/** @var array $inquilinos */
/** @var array $fiadores */
/** @var array $obligados */
/** @var array $asesores */
/** @var string $baseUrl */
/** @var int|string $siguienteNumero */
?>
<section class="px-4 md:px-8 py-10 text-white">
    <h1 class="text-3xl font-extrabold mb-8 flex items-center gap-3 text-indigo-300">
        <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M8 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        Renovar póliza #<?= htmlspecialchars($poliza['numero_poliza']) ?> → Nueva #<?= htmlspecialchars($siguienteNumero) ?>
    </h1>

    <form id="form-renovar-poliza" method="POST" action="<?= $baseUrl ?>/polizas/store"
        class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-2xl p-8 border border-indigo-900/20 space-y-8">
        <input type="hidden" name="numero_poliza" value="<?= (int)$siguienteNumero ?>">

        <div class="grid md:grid-cols-2 gap-6">
            <!-- Tipo póliza (readonly + hidden real) -->
            <div>
                <label class="block text-indigo-300 mb-1">Tipo de Póliza</label>
                <input type="text" value="<?= htmlspecialchars($poliza['tipo_poliza']) ?>"
                    class="w-full px-3 py-2 rounded-lg bg-[#1e1e2d] text-indigo-200 border border-indigo-800 cursor-not-allowed" readonly>
                <input type="hidden" name="tipo_poliza" value="<?= htmlspecialchars($poliza['tipo_poliza']) ?>">
            </div>

            <!-- Asesor (readonly + hidden) -->
            <div>
                <label class="block text-indigo-300 mb-1">Asesor inmobiliario</label>
                <?php
                $asesorNombre = '';
                foreach ($asesores as $as) if ($as['id'] == $poliza['id_asesor']) {
                    $asesorNombre = $as['nombre_asesor'];
                    break;
                }
                ?>
                <input type="text" value="<?= htmlspecialchars($asesorNombre) ?>"
                    class="w-full px-3 py-2 rounded-lg bg-[#1e1e2d] text-indigo-200 border border-indigo-800 cursor-not-allowed" readonly>
                <input type="hidden" name="id_asesor" value="<?= htmlspecialchars($poliza['id_asesor']) ?>">
            </div>

            <!-- Arrendador (readonly + hidden) -->
            <div>
                <label class="block text-indigo-300 mb-1">Arrendador</label>
                <?php
                $arrNombre = '';
                foreach ($arrendadores as $ar) if ($ar['id'] == $poliza['id_arrendador']) {
                    $arrNombre = $ar['nombre_arrendador'];
                    break;
                }
                ?>
                <input type="text" value="<?= htmlspecialchars($arrNombre) ?>"
                    class="w-full px-3 py-2 rounded-lg bg-[#1e1e2d] text-indigo-200 border border-indigo-800 cursor-not-allowed" readonly>
                <input type="hidden" name="id_arrendador" value="<?= htmlspecialchars($poliza['id_arrendador']) ?>">
            </div>

            <!-- Inmueble (readonly + hidden) -->
            <div>
                <label class="block text-indigo-300 mb-1">Inmueble</label>
                <?php
                $inmSel = null;
                foreach ($inmuebles as $inm) if ($inm['id'] == $poliza['id_inmueble']) {
                    $inmSel = $inm;
                    break;
                }
                ?>
                <input type="text" value="<?= htmlspecialchars($inmSel['direccion_inmueble'] ?? '') ?>"
                    class="w-full px-3 py-2 rounded-lg bg-[#1e1e2d] text-indigo-200 border border-indigo-800 cursor-not-allowed" readonly>
                <input type="hidden" name="id_inmueble" value="<?= htmlspecialchars($poliza['id_inmueble']) ?>">
            </div>

            <!-- Tipo inmueble (readonly + hidden) -->
            <div>
                <label class="block text-indigo-300 mb-1">Tipo de inmueble</label>
                <input type="text" value="<?= htmlspecialchars($poliza['tipo_inmueble']) ?>"
                    class="w-full px-3 py-2 rounded-lg bg-[#1e1e2d] text-indigo-200 border border-indigo-800 cursor-not-allowed" readonly>
                <input type="hidden" name="tipo_inmueble" value="<?= htmlspecialchars($poliza['tipo_inmueble']) ?>">
            </div>

            <!-- Monto renta (readonly) -->
            <div>
                <label class="block text-indigo-300 mb-1">Monto de renta</label>
                <input type="text" id="monto-renta-display" readonly
                    class="w-full px-3 py-2 rounded-lg bg-[#1e1e2d] text-indigo-200 border border-indigo-800 cursor-not-allowed"
                    value="<?= $poliza['monto_renta'] ?>">
                <input type="hidden" name="monto_renta" id="monto-renta-hidden" value="<?= htmlspecialchars($poliza['monto_renta']) ?>">
            </div>

            <!-- Monto póliza (editable, se calcula default por tramo) -->
            <div>
                <label class="block text-indigo-300 mb-1">Monto póliza</label>
                <input type="number" step="0.01" name="monto_poliza" id="monto-poliza"
                    class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <!-- Fechas -->
            <?php
            $fecha_inicio_anterior = $poliza['fecha_poliza'] ?? null;
            $nueva_fecha_inicio = '';
            if ($fecha_inicio_anterior) {
                $inicio = new DateTime($fecha_inicio_anterior);
                $inicio->modify('+1 year');
                $nueva_fecha_inicio = $inicio->format('Y-m-d');
            }
            ?>
            <div>
                <label class="block text-indigo-300 mb-1">Fecha de inicio</label>
                <input type="date" name="fecha_poliza" id="fecha-inicio" value="<?= htmlspecialchars($nueva_fecha_inicio) ?>"
                    class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label class="block text-indigo-300 mb-1">Fecha de fin</label>
                <input type="date" name="fecha_fin" id="fecha-fin" value="<?= htmlspecialchars($poliza['fecha_fin']) ?>"
                    class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label class="block text-indigo-300 mb-1">Vigencia (texto)</label>
                <input type="text" name="vigencia" id="vigencia-texto"
                    class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    value="<?= htmlspecialchars($poliza['vigencia']) ?>" readonly>
            </div>

            <!-- Inquilino / Obligado / Fiador -->
            <?php $inqSel = $poliza['id_inquilino']; ?>
            <div>
                <label class="block text-indigo-300 mb-1">Inquilino</label>
                <select name="id_inquilino" class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">- SELECCIONA UN INQUILINO -</option>
                    <?php foreach ($inquilinos as $inq): ?>
                        <?php $name = trim(($inq['nombre_inquilino'] ?? '') . ' ' . ($inq['apellidop_inquilino'] ?? '') . ' ' . ($inq['apellidom_inquilino'] ?? '')); ?>
                        <option value="<?= $inq['id'] ?>" <?= $inq['id'] === $inqSel ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php $oblSel = $poliza['id_obligado']; ?>
            <div>
                <label class="block text-indigo-300 mb-1">Obligado solidario</label>
                <select name="id_obligado" class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Selecciona un obligado solidario</option>
                    <?php foreach ($obligados as $obl): ?>
                        <?php $name = trim(($obl['nombre_inquilino'] ?? '') . ' ' . ($obl['apellidop_inquilino'] ?? '') . ' ' . ($obl['apellidom_inquilino'] ?? '')); ?>
                        <option value="<?= $obl['id'] ?>" <?= $obl['id'] === $oblSel ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php $fSel = $poliza['id_fiador']; ?>
            <div>
                <label class="block text-indigo-300 mb-1">Fiador</label>
                <select name="id_fiador" class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">- SELECCIONA UN FIADOR -</option>
                    <?php foreach ($fiadores as $f): ?>
                        <?php $name = trim(($f['nombre_inquilino'] ?? '') . ' ' . ($f['apellidop_inquilino'] ?? '') . ' ' . ($f['apellidom_inquilino'] ?? '')); ?>
                        <option value="<?= $f['id'] ?>" <?= $f['id'] === $fSel ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-indigo-300 mb-1">Comentarios</label>
            <textarea name="comentarios" rows="3"
                class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"><?= htmlspecialchars($poliza['comentarios'] ?? "") ?></textarea>
        </div>

        <div class="flex justify-between items-center gap-4">
            <a href="<?= $baseUrl ?>/polizas/<?= $poliza['numero_poliza'] ?>" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded">Cancelar</a>
            <button type="submit" class="px-6 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white shadow font-semibold">Guardar</button>
        </div>
    </form>
</section>
<script>
    window.BASE_URL = "<?= $baseUrl ?>";
</script>
<script src="<?= $baseUrl ?>/assets/polizas.js"></script>