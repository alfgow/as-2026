<?php

/** @var array $poliza */
/** @var array $inmuebles */
/** @var array $arrendadores */
/** @var array $inquilinos */
/** @var array $fiadores */
/** @var array $obligados */
/** @var array $asesores */
/** @var string $baseUrl */

use App\Helpers\TextHelper;
?>
<section class="px-4 md:px-8 py-5 text-white">
    <?php
    // Normaliza el estado a texto por si viene numérico
    $estadoTxt = is_numeric($poliza['estado'])
        ? ([
            1 => 'Vigente',
            2 => 'Concluida',
            3 => 'Término Anticipado',
            4 => 'Incumplimiento',
        ][$poliza['estado']] ?? 'Desconocido')
        : (string)$poliza['estado'];

    // Colores del badge
    $estadoColor = [
        'Vigente'              => 'bg-green-600',
        'Concluida'            => 'bg-blue-500',
        'Término Anticipado'   => 'bg-yellow-500',
        'Incumplimiento'       => 'bg-red-600',
    ][$estadoTxt] ?? 'bg-gray-600';
    ?>


    <!-- Resumen corto (centrado + badge de estado) -->
    <?php
    // Normaliza estado y color antes del bloque
    $estadoTxt = is_numeric($poliza['estado'])
        ? ([
            1 => 'Vigente',
            2 => 'Concluida',
            3 => 'Término Anticipado',
            4 => 'Incumplimiento',
        ][$poliza['estado']] ?? 'Desconocido')
        : (string)$poliza['estado'];

    $estadoColor = [
        'Vigente'             => 'bg-green-600 text-white',
        'Concluida'           => 'bg-blue-500 text-white',
        'Término Anticipado'  => 'bg-yellow-500 text-black',
        'Incumplimiento'      => 'bg-red-600 text-white',
    ][$estadoTxt] ?? 'bg-gray-600 text-white';
    ?>

    <div class=" mx-auto mb-5">
        <div class="rounded-2xl bg-white/5 border border-white/10 shadow-inner px-6 py-6 ">

            <!-- Header: título + badge -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center gap-3 justify-center md:justify-start">
                    <svg class="w-7 h-7 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M12 20l9-7-9-7-9 7 9 7z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <h1 class="text-2xl md:text-3xl font-extrabold text-indigo-200">
                        Editar póliza #<?= htmlspecialchars($poliza['numero_poliza']) ?>
                    </h1>
                </div>

                <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-semibold <?= $estadoColor ?> ring-1 ring-white/10 shadow">
                    <?= htmlspecialchars($estadoTxt) ?>
                </span>
            </div>

            <!-- Divider -->
            <div class="mt-6 border-t border-white/10"></div>

            <!-- Resumen compacto -->
            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white/5 rounded-xl border border-white/10 p-4 text-center sm:text-left">
                    <div class="text-[11px] uppercase tracking-wide text-indigo-400/80">Tipo de póliza</div>
                    <div class="mt-1 text-lg font-medium text-white">
                        <?= htmlspecialchars($poliza['tipo_poliza']) ?>
                    </div>
                </div>

                <div class="bg-white/5 rounded-xl border border-white/10 p-4 text-center sm:text-left">
                    <div class="text-[11px] uppercase tracking-wide text-indigo-400/80">Vigencia</div>
                    <div class="mt-1 text-lg font-medium text-white">
                        <?= htmlspecialchars($poliza['vigencia']) ?>
                    </div>
                </div>
            </div>

        </div>
    </div>


    <!-- Formulario edición -->
    <form id="form-editar-poliza" method="POST" action="<?= $baseUrl ?>/polizas/actualizar"
        class="space-y-4 bg-white/10 p-6 rounded-xl border border-white/10 shadow-[0_8px_32px_0_rgba(31,38,135,0.37)]">
        <input type="hidden" name="numero_poliza" value="<?= htmlspecialchars($poliza['numero_poliza']) ?>">

        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-indigo-300 mb-1">Tipo de Póliza</label>
                <select name="tipo_poliza" class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="Clásica" <?= $poliza['tipo_poliza'] === 'Clásica' ? 'selected' : '' ?>>Clásica</option>
                    <option value="Plus" <?= $poliza['tipo_poliza'] === 'Plus'    ? 'selected' : '' ?>>Plus</option>
                </select>
            </div>

            <div>
                <label class="block text-indigo-300 mb-1">Vigencia (texto)</label>
                <input type="text" name="vigencia" id="vigencia-texto" value="<?= htmlspecialchars($poliza['vigencia']) ?>"
                    class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label class="block text-indigo-300 mb-1">Fecha de inicio</label>
                <input type="date" name="fecha_poliza" value="<?= htmlspecialchars($poliza['fecha_poliza']) ?>"
                    class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" id="fecha-inicio">
            </div>

            <div>
                <label class="block text-indigo-300 mb-1">Fecha de fin</label>
                <input type="date" name="fecha_fin" value="<?= htmlspecialchars($poliza['fecha_fin']) ?>"
                    class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" id="fecha-fin">
            </div>

            <div>
                <label class="block text-indigo-300 mb-1">Monto póliza</label>
                <input type="number" step="0.01" name="monto_poliza" value="<?= htmlspecialchars($poliza['monto_poliza']) ?>"
                    class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" id="monto-poliza">
            </div>

            <div>
                <label class="block text-indigo-300 mb-1">Monto renta</label>

                <?php
                $rentaOriginal = (string)($poliza['monto_renta'] ?? '');
                $rentaNormalizada = preg_replace('/[^\d.]/', '', $rentaOriginal);
                $rentaNumerica = $rentaNormalizada !== '' ? (float) $rentaNormalizada : 0.0;
                ?>
                <div class="flex items-center gap-2">
                    <input type="text"
                        value="<?= '$' . number_format($rentaNumerica, 2) ?>"
                        class="w-full px-3 py-2 rounded-lg bg-[#1e1e2d] text-indigo-200 border border-indigo-800 cursor-not-allowed"
                        readonly id="monto-renta-display" name="monto_renta">
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

            </div>


            <div>
                <label class="block text-indigo-300 mb-1">Estado</label>
                <select name="estado" class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <?php foreach ([1 => 'Vigente', 2 => 'Concluida', 3 => 'Término Anticipado', 4 => 'Incumplimiento'] as $val => $txt): ?>
                        <option value="<?= $val ?>" <?= $poliza['estado'] == $val ? 'selected' : '' ?>><?= $txt ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-indigo-300 mb-1">Inmueble</label>
                <select name="id_inmueble" class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" id="inmueble-select">
                    <option value="">- SELECCIONA UN INMUEBLE -</option>
                    <?php foreach ($inmuebles as $inm): ?>
                        <?php
                        $rentaInmueble = preg_replace('/[^\d.]/', '', (string)($inm['renta'] ?? ''));
                        ?>
                        <option value="<?= $inm['id'] ?>" data-monto="<?= htmlspecialchars($rentaInmueble) ?>" <?= $inm['id'] == $poliza['id_inmueble'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($inm['direccion_inmueble']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" id="monto-renta-hidden" value="<?= htmlspecialchars((string) $rentaNumerica) ?>">
            </div>

            <div>
                <label class="block text-indigo-300 mb-1">Tipo de inmueble</label>
                <select name="tipo_inmueble" class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">- SELECCIONE UNA OPCIÓN -</option>
                    <?php foreach (['Departamento', 'Casa', 'Terreno', 'Local Comercial', 'Oficinas', 'Edificio'] as $op): ?>
                        <option value="<?= $op ?>" <?= $poliza['tipo_inmueble'] === $op ? 'selected' : '' ?>><?= $op ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-indigo-300 mb-1">Arrendador</label>
                <select name="id_arrendador" class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Selecciona un arrendador</option>
                    <?php foreach ($arrendadores as $arr): ?>
                        <option value="<?= $arr['id'] ?>" <?= $arr['id'] == $poliza['id_arrendador'] ? 'selected' : '' ?>><?= TextHelper::titleCase($arr['nombre_arrendador']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php
            // --- INQUILINO ---
            $inqSel = isset($poliza['id_inquilino']) ? (int)$poliza['id_inquilino'] : null;
            ?>
            <div>
                <label class="block text-indigo-300 mb-1">Inquilino</label>
                <select name="id_inquilino"
                    class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">- SELECCIONA UN INQUILINO -</option>
                    <?php foreach ($inquilinos as $inq):
                        $id = (int)($inq['id'] ?? 0);
                        $nombre = trim(($inq['nombre_inquilino'] ?? '') . ' ' . ($inq['apellidop_inquilino'] ?? '') . ' ' . ($inq['apellidom_inquilino'] ?? ''));
                    ?>
                        <option value="<?= $id ?>" <?= $id === $inqSel ? 'selected' : '' ?>>
                            <?= TextHelper::titleCase($nombre ?: 'SIN NOMBRE') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php
            // --- FIADOR ---
            // Regla: si id_fiador > 1 => clásico; si ==1 => "Sin fiador"; si viene id_fiador_2025 úsalo.
            $fSel = null;
            if (isset($poliza['id_fiador']) && (int)$poliza['id_fiador'] > 1) {
                $fSel = (int)$poliza['id_fiador'];
            } elseif (!empty($poliza['id_fiador_2025'])) {
                $fSel = (int)$poliza['id_fiador_2025'];
            }
            $sinFiadorActivo = isset($poliza['id_fiador']) && (int)$poliza['id_fiador'] === 1;
            ?>
            <div>
                <label class="block text-indigo-300 mb-1">Fiador</label>
                <select name="id_fiador"
                    class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">- SELECCIONA UN FIADOR -</option>
                    <option value="1" <?= $sinFiadorActivo ? 'selected' : '' ?>>Sin fiador</option>
                    <?php foreach ($fiadores as $f):
                        $id = (int)($f['id'] ?? 0);
                        $nombre = trim(($f['nombre_inquilino'] ?? '') . ' ' . ($f['apellidop_inquilino'] ?? '') . ' ' . ($f['apellidom_inquilino'] ?? ''));
                    ?>
                        <option value="<?= $id ?>" <?= ($id === $fSel) ? 'selected' : '' ?>>
                            <?= TextHelper::titleCase($nombre ?: 'SIN NOMBRE') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php
            // --- OBLIGADO SOLIDARIO ---
            // Usa id_obligado si existe; si no, id_obligado_2025
            $oblSel = null;
            if (!empty($poliza['id_obligado'])) {
                $oblSel = (int)$poliza['id_obligado'];
            } elseif (!empty($poliza['id_obligado_2025'])) {
                $oblSel = (int)$poliza['id_obligado_2025'];
            }
            ?>
            <div>
                <label class="block text-indigo-300 mb-1">Obligado Solidario</label>
                <select name="id_obligado"
                    class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Selecciona un obligado solidario</option>
                    <?php foreach ($obligados as $os):
                        $id = (int)($os['id'] ?? 0);
                        $nombre = trim(($os['nombre_inquilino'] ?? '') . ' ' . ($os['apellidop_inquilino'] ?? '') . ' ' . ($os['apellidom_inquilino'] ?? ''));
                    ?>
                        <option value="<?= $id ?>" <?= ($id === $oblSel) ? 'selected' : '' ?>>
                            <?= TextHelper::titleCase($nombre ?: 'SIN NOMBRE') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>


            <div>
                <label class="block text-indigo-300 mb-1">Asesor</label>
                <select name="id_asesor" class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Selecciona un asesor</option>
                    <?php foreach ($asesores as $as): ?>
                        <option value="<?= $as['id'] ?>" <?= $as['id'] == $poliza['id_asesor'] ? 'selected' : '' ?>><?= htmlspecialchars($as['nombre_asesor']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-indigo-300 mb-1">Comentarios</label>
            <textarea name="comentarios" rows="3"
                class="appearance-none w-full px-4 py-2 rounded-lg bg-[#232336] border border-indigo-800 text-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"><?= htmlspecialchars($poliza['comentarios'] ?? "") ?></textarea>
        </div>

        <div class="flex justify-between items-center gap-4">
            <a href="<?= $baseUrl ?>/polizas/<?= $poliza['numero_poliza'] ?>" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded">Cancelar</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded">Guardar cambios</button>
        </div>
    </form>
</section>
<script>
    window.BASE_URL = "<?= $baseUrl ?>";
</script>
<script src="<?= $baseUrl ?>/assets/polizas.js"></script>