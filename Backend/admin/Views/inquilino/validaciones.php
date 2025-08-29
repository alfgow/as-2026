<?php
    $h       = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $nombreCompleto = trim(
        ($inquilino['nombre_inquilino'] ?? '') . ' ' .
        ($inquilino['apellidop_inquilino'] ?? '') . ' ' .
        ($inquilino['apellidom_inquilino'] ?? '')
    );
    $nombre = $h($nombreCompleto ?: 'Nombre Apellido');
    $slug    = $h($inquilino['slug'] ?? 'slug-ejemplo');
    $idInq   = (int)($inquilino['id'] ?? 0);
    $ADMIN_BASE = $admin_base_url ?? '';
    $idInquilino = (int)($inquilino['id'] ?? $idInquilino ?? 0);
    $apP         = $inquilino['apellidop_inquilino'] ?? '';
    $apM         = $inquilino['apellidom_inquilino'] ?? '';
    $curp        = $inquilino['curp'] ?? null;
    $rfc         = $inquilino['rfc'] ?? null;
    $slug        = $inquilino['slug'] ?? ($slug ?? null);
    $tipoId = strtolower(trim($inquilino['tipo_id'] ?? ''));
    $tiposIne = ['ine', 'ife', 'ine/ife'];
    function chipColor($valor) {
    return match((int)$valor) {
        1       => 'rounded-full border px-3 py-1 text-xs border-emerald-400/30 bg-emerald-400/15',  // OK
        0       => 'rounded-full border px-3 py-1 text-xs border-rose-400/30 bg-rose-400/15',    // NO_OK
        default => 'rounded-full border px-3 py-1 text-xs border-amber-400/30 bg-amber-400/15', // PENDIENTE
    };
}
?>

<script src="https://cdn.tailwindcss.com"></script>
<style>
    @keyframes shimmer{0%{background-position:-450px 0}100%{background-position:450px 0}}
    .skel {
    animation: shimmer 1.2s linear infinite;
    background: linear-gradient(to right, rgba(255,255,255,.06) 8%, rgba(255,255,255,.12) 18%, rgba(255,255,255,.06) 33%);
    background-size: 800px 104px;
    }
</style>

<!-- 👇 container mobile-first + sin overflow lateral -->
<div id="validaciones-app" class="mx-auto w-full max-w-screen-2xl px-3 sm:px-4 lg:px-6 text-slate-100 overflow-x-hidden">

    <!-- HERO -->
    <section class="grid gap-4 md:grid-cols-1">
    
        <!-- Card superior (nombre + estatus validaciones) -->
        <div class="rounded-2xl border border-white/10 bg-white/5 p-6 shadow-xl backdrop-blur flex flex-col items-center justify-center text-center">
            <div class="text-xl font-bold tracking-tight" id="vh-nombre"><?= $nombre ?></div>
            <!-- Select estilizado -->
            <select id="select-status"
                class="my-4 ml-2 rounded-lg border border-white/10 bg-gray-800 text-slate-200 px-3 py-1 text-sm font-medium
                    focus:outline-none focus:ring-2 focus:ring-indigo-400 hover:bg-gray-700 transition">
                <option value="1" <?= ($inquilino['status'] ?? 1) == 1 ? 'selected' : '' ?>>Nuevo</option>
                <option value="2" <?= ($inquilino['status'] ?? 1) == 2 ? 'selected' : '' ?>>Aprobado</option>
                <option value="3" <?= ($inquilino['status'] ?? 1) == 3 ? 'selected' : '' ?>>En Proceso</option>
                <option value="4" <?= ($inquilino['status'] ?? 1) == 4 ? 'selected' : '' ?>>Rechazado</option>
            </select>
            <div class="mt-1 text-sm text-slate-400 break-words">
            🦖 Estatus de validaciones:
            </div>
            <div class="mt-3 flex flex-wrap justify-center gap-2">
            <span id="pill-archivos"   class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-sm"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Archivos</span>
            <span id="pill-rostro"     class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-sm"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Rostro</span>
            <span id="pill-identidad"  class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-sm"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Identidad</span>
            <?php if (in_array($tipoId, $tiposIne, true)): ?>
                <span id="pill-verificamex" class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-sm"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Verificamex</span>
            <?php endif; ?>
            <span id="pill-ingresos"   class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-sm"><span class="h-2 w-2 rounded-full bg-rose-500"></span>Ingresos</span>
            <span id="pill-pago"       class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-sm"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Pago inicial</span>
            <span id="pill-demandas"   class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-sm"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Demandas</span>
            </div>
                <div class="h-2 w-full overflow-hidden rounded-full border border-white/10 bg-white/10 mt-5">
                    <span id="vh-progress" class="block h-full bg-gradient-to-r from-cyan-400 to-indigo-400" style="width:64%"></span>
                </div>
                <div id="vh-progress-text" class="mt-2 text-sm text-slate-300">0 de 7 validaciones completas</div>
        </div>

        <!-- Card inferior (contenedor de sub-cards) -->
        <div class="grid gap-4 md:grid-cols-2">

            <!-- Card: Validación de Archivos y Pago Inicial -->
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 shadow-xl backdrop-blur">
                
                <!-- Sección Archivos -->
                <h3 class="mb-3 text-base font-semibold text-white">Archivos Recibidos</h3>

                <!-- Chips -->
                <div id="chips-archivos" class="flex flex-wrap justify-center sm:justify-start gap-2 mb-4">
                    <span data-key="selfie" class="rounded-full border border-rose-400/30 bg-rose-400/15 px-3 py-1 text-xs">Selfie</span>
                    <span data-key="ine_frontal" class="rounded-full border border-rose-400/30 bg-rose-400/15 px-3 py-1 text-xs">INE - frontal</span>
                    <span data-key="ine_reverso" class="rounded-full border border-rose-400/30 bg-rose-400/15 px-3 py-1 text-xs">INE - reverso</span>
                    <span data-key="pasaporte" class="rounded-full border border-rose-400/30 bg-rose-400/15 px-3 py-1 text-xs">Pasaporte</span>
                    <span data-key="fm" class="rounded-full border border-rose-400/30 bg-rose-400/15 px-3 py-1 text-xs">FM2/FM3</span>
                    <span data-key="comprobante_ingreso" class="rounded-full border border-rose-400/30 bg-rose-400/15 px-3 py-1 text-xs">Comprobantes</span>
                </div>

                <!-- Switch Archivos -->
                <div class="flex items-center gap-3 mb-6">
                    <span class="text-sm text-slate-300">Validación Archivos</span>
                    <label class="inline-flex items-center cursor-pointer relative">
                        <input
                            id="toggle-archivos"
                            type="checkbox"
                            class="sr-only peer"
                            onchange="window.saveSwitch('archivos')"
                            <?= ((int)($validaciones['proceso_validacion_archivos'] ?? 2) === 1) ? 'checked' : '' ?>
                        />
                        <!-- Track -->
                        <div class="w-11 h-6 bg-gray-600 rounded-full peer-checked:bg-emerald-500 transition"></div>
                        <!-- Knob -->
                        <div class="absolute left-0.5 top-0.5 h-5 w-5 bg-white rounded-full transition-transform peer-checked:translate-x-5"></div>
                    </label>
                    <em id="toggle-archivos-label" class="not-italic text-xs text-slate-400 peer-checked:text-emerald-400">
                        <?= ((int)($validaciones['proceso_validacion_archivos'] ?? 2) === 1) ? 'Confirmado' : 'Pendiente' ?>
                    </em>
                </div>

                <!-- Divider -->
                <div class="border-t border-white/10 my-4"></div>

                <!-- Sección Pago inicial -->
                <h3 class="text-base font-semibold text-white">Pago Inicial</h3>

                <!-- Switch Pago Inicial -->
                <div class="mt-3 flex items-center gap-3">
                    <span class="text-sm text-slate-300">Pago Recibido?</span>
                    <label class="inline-flex items-center cursor-pointer relative">
                        <input
                            id="toggle-pago_inicial"
                            type="checkbox"
                            class="peer sr-only"
                            onchange="window.saveSwitch('pago_inicial')"
                            <?= ((int)($validaciones['proceso_pago_inicial'] ?? 2) === 1) ? 'checked' : '' ?>
                        />
                        <!-- track -->
                        <div
                            class="relative h-7 w-12 rounded-full border border-white/10 bg-white/10 shadow-inner
                                transition-colors duration-300 ease-out
                                focus-within:outline-none focus-within:ring-2 focus-within:ring-fuchsia-500/40
                                peer-checked:bg-gradient-to-r peer-checked:from-fuchsia-500/60 peer-checked:to-indigo-500/60

                                /* knob (pseudo) */
                                after:absolute after:top-1 after:left-1 after:h-5 after:w-5 after:rounded-full after:bg-slate-200
                                after:shadow after:transition-all after:duration-300 after:ease-out
                                peer-checked:after:translate-x-5 peer-checked:after:bg-white">
                        </div>
                    </label>
                    <em id="toggle-pago-label" class="not-italic text-xs text-slate-400 peer-checked:text-emerald-400">
                        <?= ((int)($validaciones['proceso_pago_inicial'] ?? 2) === 1) ? 'Confirmado' : 'Pendiente' ?>
                    </em>
                </div>

                <div id="pago-status-msg" class="mt-2 text-xs text-slate-400"></div>

                <!-- Botones -->
                <div class="mt-3 grid grid-cols-1 gap-2 sm:auto-cols-max sm:grid-flow-col">
                    <button class="vh-detalle w-full sm:w-auto rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15" data-cat="pago_inicial">
                        Ver detalle
                    </button>
                </div>
            </div>

             <!-- Sub-card: Ingresos -->
        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 shadow-xl backdrop-blur mt-6">
            <h3 class="mb-2 text-sm font-semibold">Validación de Ingresos</h3>

            <!-- Datos resumidos -->
            <div class="mb-4 text-sm space-y-1">
                <p><span class="font-semibold text-slate-300">Ingreso declarado:</span>
                    <span id="ingreso-declarado" class="text-emerald-400">
                        <?= number_format((float)($inquilino['trabajo']['sueldo'] ?? 0), 2) ?>
                    </span>
                </p>
                <p><span class="font-semibold text-slate-300">Ingreso calculado:</span>
                    <span id="ingreso-calculado" class="text-indigo-400">
                        <!-- este se llena por JS tras OCR -->
                    </span>
                </p>
                <p><span class="font-semibold text-slate-300">Diferencia:</span>
                    <span id="ingreso-diferencia" class="text-rose-400">
                        <!-- este se llena por JS tras OCR -->
                    </span>
                </p>
            </div>

            <!-- Switch -->
            <div class="flex items-center justify-start gap-2 mb-4">
                <span class="text-sm">Ingresos Validados</span>
                <label class="inline-flex items-center cursor-pointer relative">
                    <input
                        id="toggle-ingresos"
                        type="checkbox"
                        class="sr-only peer"
                        onchange="window.saveSwitch('ingresos')"
                        <?= ((int)($validaciones['proceso_validacion_ingresos'] ?? 2) === 1) ? 'checked' : '' ?>
                    />
                    <!-- Track -->
                    <div class="w-11 h-6 bg-gray-600 rounded-full peer-checked:bg-emerald-500 transition"></div>
                    <!-- Knob -->
                    <div class="absolute left-0.5 top-0.5 h-5 w-5 bg-white rounded-full transition-transform peer-checked:translate-x-5"></div>
                </label>
                <em id="toggle-ingresos-label"
                    class="not-italic text-xs text-slate-400 peer-checked:text-emerald-400">
                    <?= ((int)($validaciones['proceso_validacion_ingresos'] ?? 2) === 1) ? 'Confirmado' : 'Pendiente' ?>
                </em>
            </div>

            <!-- Botones -->
            <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                <button class="vh-detalle rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15"
                        data-cat="ingresos">
                    Ver detalle
                </button>
                <button class="vh-recalc rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15"
                        data-check="ingresos_ocr" disabled>
                    *Disabled - Procesar OCR
                </button>
            </div>
        </div>

        </div>
    </section>

    <!-- GRID VALIDACIONES -->
    <section class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">

 
    <!-- VerificaMex -->
        <?php if (in_array($tipoId, $tiposIne, true)): ?>
            <!-- VerificaMex -->
            <div class="w-full max-w-full overflow-hidden rounded-2xl border border-white/10 bg-white/5 p-4 shadow-xl backdrop-blur" data-cat="verificamex">
                <h3 class="text-base font-semibold text-white">Validación INE</h3>

                <!-- Switch -->
                <div class="mt-3 flex items-center gap-3">
                    <span class="text-sm text-slate-300">Estatus Valicación INE</span>
                    <label class="inline-flex items-center cursor-pointer relative">
                        <input
                            id="toggle-verificamex"
                            type="checkbox"
                            class="sr-only peer"
                            onchange="window.saveSwitch('verificamex')"
                            <?= ((int)($validaciones['proceso_validacion_verificamex'] ?? 2) === 1) ? 'checked' : '' ?>
                        />
                        <!-- Track -->
                        <div class="w-11 h-6 bg-gray-600 rounded-full peer-checked:bg-emerald-500 transition"></div>
                        <!-- Knob -->
                        <div class="absolute left-0.5 top-0.5 h-5 w-5 bg-white rounded-full transition-transform peer-checked:translate-x-5"></div>
                    </label>
                    <em id="toggle-verificamex-label" class="not-italic text-xs text-slate-400 peer-checked:text-emerald-400">
                        <?= ((int)($validaciones['proceso_validacion_verificamex'] ?? 2) === 1) ? 'Confirmado' : 'Pendiente' ?>
                    </em>
                </div>

                <!-- Resumen humano -->
                <p id="txt-verificamex" class="vh-scroll mt-2 pr-2 text-sm text-slate-300 break-words w-full">
                    🚫No hay información aún de VerificaMex🚫
                </p>

                <!-- Botones -->
                <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <button class="vh-detalle rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15" data-cat="verificamex">
                        Ver detalle
                    </button>
                    <button class="vh-recalc rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15" data-check="verificamex">
                        Reprocesar
                    </button>

                </div>
            </div>
        <?php endif; ?>


        <!-- Validacion de Rostro=ID -->
        <div class="min-w-0">
            <!-- Rostro -->
            <div class="w-full max-w-full overflow-hidden rounded-2xl border border-white/10 bg-white/5 p-4 shadow-xl backdrop-blur" data-cat="rostro">
                <h3 class="text-base font-semibold">Rostro</h3>
                <div id="chips-rostro" class="mt-2 flex flex-wrap justify-center sm:justify-start gap-2 text-center">
                     <?php
                    $proceso_validacion_rostro = (int)($validaciones['proceso_validacion_rostro'] ?? 2);
                    ?>
                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?= chipColor($validaciones['proceso_validacion_rostro'] ?? 2) ?>">
                        <?= $proceso_validacion_rostro === 1 ? '✅ OK' : ($proceso_validacion_rostro === 0 ? '❌ NO_OK' : '⏳ PENDIENTE') ?>
                    </span>
                   
                    <p id="txt-rostro" class="mt-2 max-h-24 overflow-y-auto pr-1 text-sm text-slate-300 break-words w-full">
                        🚫No hay información, actualiza el perfil del inquilino🚫.
                    </p>
                </div>
                <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <button class="vh-detalle rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15" data-cat="rostro">
                        Ver detalle
                    </button>
                    <!-- Botón dinámico si es proceso verificamex -->
                    <?php if (!in_array($tipoId, $tiposIne, true)): ?>
                        <button class="vh-recalc rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15" data-check="save_face">
                            Volver a comparar
                        </button>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <div class="min-w-0">
            <!-- Identidad -->
            <div class="w-full max-w-full overflow-hidden rounded-2xl border border-white/10 bg-white/5 p-4 shadow-xl backdrop-blur" data-cat="identidad">
                <h3 class="text-base font-semibold">Identidad</h3>

                <div id="chips-identidad" class="mt-2 flex flex-wrap justify-center sm:justify-start gap-2 text-center">
                    <?php
                    $proceso_validacion_id = (int)($validaciones['proceso_validacion_id'] ?? 2);
                    ?>
                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?= chipColor($validaciones['proceso_validacion_id'] ?? 2) ?>">
                        <?= $proceso_validacion_id === 1 ? '✅ OK' : ($proceso_validacion_id === 0 ? '❌ NO_OK' : '⏳ PENDIENTE') ?>
                    </span>
                    <p id="txt-identidad" class="vh-scroll mt-2 pr-2 text-sm text-slate-300 break-words w-full">
                        🚫No hay información, actualiza el perfil del inquilino🚫.
                    </p>
                </div>

                <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <button class="vh-detalle rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15" data-cat="identidad">
                        Ver detalle
                    </button>
                    <?php if (!in_array($tipoId, $tiposIne, true)): ?>
                    <button class="vh-recalc rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15" data-check="save_match">
                        Leer CURP/CIC
                    </button>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <!-- Aquí continuarías con Documentos, Ingresos, Pago inicial y Demandas repitiendo el patrón -->
    </section>

    <div id="vh-meta"
    data-id="<?= $idInquilino ?>"
    data-nombre="<?= htmlspecialchars($nombre) ?>"
    data-apellido_p="<?= htmlspecialchars($apP) ?>"
    data-apellido_m="<?= htmlspecialchars($apM) ?>"
    data-curp="<?= htmlspecialchars($curp ?? '') ?>"
    data-rfc="<?= htmlspecialchars($rfc ?? '') ?>"
    data-slug="<?= htmlspecialchars($slug ?? '') ?>">
</div>

<!-- Sección de Demandas y Litigios -->
<section id="cardJuridico" class="bg-gray-900 border border-white/10 rounded-2xl p-6 shadow-xl my-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-3">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-indigo-600/30 rounded-xl">
                <svg class="w-6 h-6 text-indigo-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M3 7h18M3 12h18M3 17h18" />
                </svg>
            </div>
            <h2 class="text-xl font-semibold text-white">Demandas y litigios</h2>
        </div>
        <div class="flex flex-col sm:flex-row gap-2">
            <button id="btnRunValidacion"
                class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white shadow">
                Ejecutar validación ahora
            </button>
            <button id="btnVerUltimo"
                class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white">
                Ver último reporte
            </button>
            <div class="flex flex-col sm:flex-row gap-2 items-center">
               <label for="toggle-demandas" class="flex items-center cursor-pointer">
                    <div class="relative">
                        <!-- Checkbox real (oculto) -->
                        <input id="toggle-demandas" type="checkbox"
                        class="sr-only peer"
                        data-id="<?= htmlspecialchars($inquilino['id'] ?? 0) ?>"
                        <?= ($procesoDemandas ?? 2) == 1 ? 'checked' : '' ?>>

                        <!-- Fondo del switch -->
                        <div class="w-14 h-8 bg-gray-600 rounded-full peer-checked:bg-green-500 transition-colors"></div>

                        <!-- Bolita del switch -->
                        <div class="absolute left-1 top-1 w-6 h-6 bg-white rounded-full transition-transform peer-checked:translate-x-6"></div>
                    </div>

                    <!-- Texto al lado -->
                    <span class="ml-3 text-sm text-gray-300">Demandas</span>
                </label>
            </div>

        </div>
        </div>

        <!-- Resumen jurídico -->
        <div id="juridicoResumen" class="bg-white/5 rounded-xl p-4 text-sm text-gray-200 mb-4">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-lg font-semibold text-white">Resumen jurídico</h3>
                <span id="juridicoStatus" class="text-sm text-gray-300">cargando…</span>
            </div>
            <div id="juridicoEvidencias" class="space-y-3">
                <!-- Aquí se mostrarán los resultados filtrados de Google -->
            </div>
        </div>

        <!-- Último reporte (resultados Google) -->
        <div id="reporteContainer" class="bg-white/5 rounded-xl p-4 text-sm text-gray-200 hidden mb-6">
            <!-- Se llena con JS -->
        </div>

        <!-- Historial de validaciones -->
        <div id="historialContainer">
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M12 20l9-5-9-5-9 5 9 5z" />
                    <path d="M12 12V4l9-5-9-5-9 5z" />
                </svg>
                Historial de validaciones
            </h3>
            <?php if (!empty($historial)): ?>
                <div class="overflow-x-auto rounded-xl border border-gray-700">
                    <table class="min-w-full text-sm text-left text-gray-300">
                        <thead class="bg-gray-800 text-gray-400 uppercase text-xs">
                            <tr>
                                <th class="px-4 py-2">Fecha</th>
                                <th class="px-4 py-2">Clasificación</th>
                                <th class="px-4 py-2">Estatus</th>
                                <th class="px-4 py-2">Resultados</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700 bg-gray-900">
                            <?php foreach ($historial as $item): ?>
                                <tr>
                                    <td class="px-4 py-2">
                                        <?= htmlspecialchars($item['searched_at'] ?? '-') ?>
                                    </td>
                                    <td class="px-4 py-2">
                                        <span class="px-2 py-1 rounded-full text-xs 
                                            <?= $item['clasificacion'] === 'match_alto' ? 'bg-red-600 text-white' :
                                                ($item['clasificacion'] === 'posible_match' ? 'bg-yellow-400 text-black' :
                                                'bg-green-600 text-white') ?>">
                                            <?= htmlspecialchars($item['clasificacion'] ?? 'sin_evidencia') ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2">
                                        <span class="px-2 py-1 rounded-full text-xs
                                            <?= $item['status'] === 'ok' ? 'bg-emerald-600 text-white' :
                                                ($item['status'] === 'error' ? 'bg-red-600 text-white' :
                                                ($item['status'] === 'manual_required' ? 'bg-amber-500 text-black' :
                                                'bg-slate-600 text-white')) ?>">
                                            <?= htmlspecialchars($item['status'] ?? '-') ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-gray-400">
                                        <?php
                                        $resultados = [];
                                        if (!empty($item['resultado'])) {
                                            $decoded = json_decode($item['resultado'], true);
                                            if (json_last_error() === JSON_ERROR_NONE) {
                                                $resultados = $decoded;
                                            }
                                        }
                                        echo count($resultados) > 0
                                            ? count($resultados) . ' coincidencia(s)'
                                            : '⚠️ Sin resultados';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center text-gray-400">
                    ⚠️ No hay registros de validaciones legales previas para este inquilino.
                </div>
            <?php endif; ?>



    </div>


</section>


    <!-- ARCHIVOS (previews) -->
    <section class="my-8">
    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 shadow-xl backdrop-blur">
        <h3 class="text-base font-semibold">Archivos (previsualización)</h3>

        <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <!-- Selfie -->
        <div class="flex flex-col gap-2">
            <div class="grid h-44 place-items-center overflow-hidden rounded-xl border border-white/10 bg-white/5">
            <img id="prev-selfie" class="h-full w-full object-cover" src="https://picsum.photos/seed/selfie/600/400" alt="Selfie">
            </div>
            <div class="flex items-center justify-between text-sm">
            <span>Selfie</span><button class="rounded-xl border border-white/10 bg-white/10 px-3 py-1.5 font-semibold opacity-50">Reemplazar</button>
            </div>
        </div>

        <!-- INE frontal -->
        <div class="flex flex-col gap-2">
            <div class="grid h-44 place-items-center overflow-hidden rounded-xl border border-white/10 bg-white/5">
            <img id="prev-ine-front" class="h-full w-full object-cover" src="https://picsum.photos/seed/inef/600/400" alt="INE frontal">
            </div>
            <div class="flex items-center justify-between text-sm">
            <span>INE — frontal</span><button class="rounded-xl border border-white/10 bg-white/10 px-3 py-1.5 font-semibold opacity-50">Reemplazar</button>
            </div>
        </div>

        <!-- INE reverso -->
        <div class="flex flex-col gap-2">
            <div class="grid h-44 place-items-center overflow-hidden rounded-xl border border-white/10 bg-white/5">
            <img id="prev-ine-back" class="h-full w-full object-cover" src="https://picsum.photos/seed/iner/600/400" alt="INE reverso">
            </div>
            <div class="flex items-center justify-between text-sm">
            <span>INE — reverso</span><button class="rounded-xl border border-white/10 bg-white/10 px-3 py-1.5 font-semibold opacity-50">Reemplazar</button>
            </div>
        </div>

        <!-- Comprobante 1 -->
        <div class="flex flex-col gap-2">
            <div id="prev-comp-1" class="grid h-44 place-items-center rounded-xl border border-white/10 bg-white/5 text-slate-400">PDF</div>
            <div class="flex items-center justify-between text-sm">
            <span>Comprobante 1</span><button class="rounded-xl border border-white/10 bg-white/10 px-3 py-1.5 font-semibold opacity-50">Subir</button>
            </div>
        </div>

        <!-- Comprobante 2 -->
        <div class="flex flex-col gap-2">
            <div id="prev-comp-2" class="grid h-44 place-items-center rounded-xl border border-white/10 bg-white/5 text-slate-400">PDF</div>
            <div class="flex items-center justify-between text-sm">
            <span>Comprobante 2</span><button class="rounded-xl border border-white/10 bg-white/10 px-3 py-1.5 font-semibold opacity-50">Subir</button>
            </div>
        </div>

        <!-- Comprobante 3 -->
        <div class="flex flex-col gap-2">
            <div id="prev-comp-3" class="grid h-44 place-items-center rounded-xl border border-rose-400/30 bg-rose-400/10 text-slate-300">PDF</div>
            <div class="flex items-center justify-between text-sm">
            <span>Comprobante 3</span><button class="rounded-xl border border-rose-400/30 bg-rose-400/15 px-3 py-1.5 font-semibold opacity-50">Falta</button>
            </div>
        </div>
        </div>
    </div>
    </section>


<!-- Modal JSON: bottom-sheet móvil, centrado desktop -->
<div id="vh-modal"
     class="fixed inset-0 z-50 hidden opacity-0
            bg-black/60 p-2 sm:p-4 overflow-x-hidden
            flex items-center sm:items-center justify-center
            transition-opacity duration-200">

  <!-- overlay clickeable -->
  <div id="vh-modal-overlay" class="absolute inset-0"></div>

  <!-- caja -->
  <div id="vh-modal-box"
       class="relative mx-auto w-full max-w-full sm:max-w-3xl
              rounded-2xl border border-white/10 bg-slate-900/70 p-4 shadow-2xl backdrop-blur
              transition-transform duration-200 ease-out
              translate-y-3 sm:translate-y-0
              flex flex-col">
    <div class="flex items-center justify-between gap-3">
      <h3 id="vh-modal-title" class="text-base font-semibold">Detalle</h3>
      <button type="button"
              class="rounded-xl border border-white/10 bg-white/10 px-3 py-1.5 font-semibold hover:bg-white/15"
              onclick="cerrarVHModal()">Cerrar</button>
    </div>

    <div class="mt-3 max-h-[70vh] overflow-y-auto rounded-xl border border-white/5 bg-black/30 p-3">
      <pre id="vh-modal-pre" class="whitespace-pre-wrap break-words text-xs leading-relaxed text-slate-200"></pre>
    </div>
  </div>
</div>
<!-- Loader principal -->
<div id="vh-loader" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 hidden">
  <div class="flex flex-col items-center gap-3 text-slate-200">
    <!-- Spinner -->
    <svg class="animate-spin h-10 w-10 text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
    </svg>
    <span class="text-sm">Cargando validaciones...</span>
  </div>
</div>
<script>
  // Contexto global de validaciones
  window.baseUrl   = <?= json_encode($baseUrl ?? '/as-2026/Backend/admin') ?>;
  window.ADMIN_BASE = <?= json_encode($ADMIN_BASE ?? '/as-2026/Backend/admin') ?>;

  // 👇 aseguramos que siempre se definan correctamente
  window.ID_INQ = <?= (int)($inquilino['id'] ?? 0) ?>;
  window.SLUG   = <?= json_encode($inquilino['slug'] ?? '') ?>;

  // Objeto unificado de contexto
  window.VH_CTX = {
    baseUrl: window.baseUrl,
    adminBase: window.ADMIN_BASE,
    idInq: window.ID_INQ,
    slug: window.SLUG
  };
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $baseUrl ?>/assets/validaciones-core.js"></script>
<script src="<?= $baseUrl ?>/assets/validaciones-archivos.js"></script>
<script src="<?= $baseUrl ?>/assets/validaciones-rostro.js"></script>
<script src="<?= $baseUrl ?>/assets/validaciones-identidad.js"></script>
<script src="<?= $baseUrl ?>/assets/validaciones-pago.js"></script>
<script src="<?= $baseUrl ?>/assets/validaciones-modal.js"></script>
<script src="<?= $baseUrl ?>/assets/validaciones-botones.js"></script>
<script src="<?= $baseUrl ?>/assets/validaciones-demandas.js"></script>