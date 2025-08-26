<?php
$h       = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$nombre  = $h($inquilino['nombre_inquilino'] ?? 'Nombre Apellido');
$slug    = $h($inquilino['slug'] ?? 'slug-ejemplo');
$idInq   = (int)($inquilino['id'] ?? 0);
$ADMIN_BASE = $admin_base_url ?? '';
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
<div id="validaciones-app" class="mx-auto w-full max-w-screen-xl px-3 sm:px-4 lg:px-6 text-slate-100 overflow-x-hidden">

  <!-- HERO -->
  <section class="grid gap-4 md:grid-cols-2">
    <!-- Card izquierda -->
    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 shadow-xl backdrop-blur">
      <div class="text-xl font-bold tracking-tight" id="vh-nombre"><?= $nombre ?></div>
      <div class="mt-1 text-sm text-slate-400 break-words">
        Slug: <code class="break-all" id="vh-slug"><?= $slug ?></code> • Proceso de validaciones
      </div>
      <div class="mt-3 flex min-w-0 flex-wrap gap-2">
        <span id="pill-archivos"   class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-sm"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Archivos</span>
        <span id="pill-rostro"     class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-sm"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Rostro</span>
        <span id="pill-identidad"  class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-sm"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Identidad</span>
        <span id="pill-documentos" class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-sm"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Documentos</span>
        <span id="pill-ingresos"   class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-sm"><span class="h-2 w-2 rounded-full bg-rose-500"></span>Ingresos</span>
        <span id="pill-pago"       class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-sm"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Pago inicial</span>
        <span id="pill-demandas"   class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-sm"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Demandas</span>
      </div>
    </div>

    <!-- Card derecha -->
    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 shadow-xl backdrop-blur">
      <h3 class="mb-2 text-sm font-semibold">Progreso</h3>
      <div class="h-2 w-full overflow-hidden rounded-full border border-white/10 bg-white/10">
        <span id="vh-progress" class="block h-full bg-gradient-to-r from-cyan-400 to-indigo-400" style="width:64%"></span>
      </div>
      <div id="vh-progress-text" class="mt-2 text-sm text-slate-300">4 de 7 validaciones completas</div>
      <div class="mt-3 grid grid-cols-1 gap-2 sm:auto-cols-max sm:grid-flow-col">
        <button id="btn-recalc"    class="w-full sm:w-auto rounded-xl border border-white/10 bg-white/10 px-4 py-2 font-semibold hover:bg-white/15">Recalcular</button>
        <button id="btn-resumen"   class="w-full sm:w-auto rounded-xl border border-white/10 bg-white/10 px-4 py-2 font-semibold hover:bg-white/15">Regenerar resúmenes</button>
        <button id="btn-continuar" class="w-full sm:w-auto rounded-xl bg-gradient-to-r from-indigo-400 to-cyan-400 px-4 py-2 font-semibold text-black">Continuar</button>
      </div>
    </div>
  </section>

  <!-- RESUMEN -->
  <section class="mt-4">
    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 shadow-xl backdrop-blur">
      <h3 class="text-sm font-semibold">Resumen humano</h3>
      <p id="vh-resumen" class="mt-1 text-slate-300 break-words">
        ✔️ Identidad consistente con INE (nombres). ⏳ Falta confirmar CURP/CIC. ✖️ Ingresos: meses insuficientes (0/6).
      </p>
      <div id="vh-ts" class="mt-3 text-xs text-slate-400">Última actualización —</div>
    </div>
  </section>

  <!-- GRID VALIDACIONES -->
<section class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
  <!-- Card base -->
  <div class="min-w-0">
    <!-- Archivos -->
    <div class="w-full max-w-full overflow-hidden rounded-2xl border border-white/10 bg-white/5 p-4 shadow-xl backdrop-blur" data-cat="archivos">
      <h3 class="text-base font-semibold">Archivos</h3>
    <div id="chips-archivos" class="mt-2 flex flex-wrap gap-2">
        <span data-key="selfie" class="rounded-full border border-rose-400/30 bg-rose-400/15 px-3 py-1 text-xs">Selfie</span>
        <span data-key="ine_frontal" class="rounded-full border border-rose-400/30 bg-rose-400/15 px-3 py-1 text-xs">INE - frontal</span>
        <span data-key="ine_reverso" class="rounded-full border border-rose-400/30 bg-rose-400/15 px-3 py-1 text-xs">INE - reverso</span>
        <span data-key="pasaporte" class="rounded-full border border-rose-400/30 bg-rose-400/15 px-3 py-1 text-xs">Pasaporte</span>
        <span data-key="fm" class="rounded-full border border-rose-400/30 bg-rose-400/15 px-3 py-1 text-xs">FM2/FM3</span>
        <span data-key="comprobante_ingreso" class="rounded-full border border-rose-400/30 bg-rose-400/15 px-3 py-1 text-xs">Comprobantes</span>
    </div>
      <p id="txt-archivos" class="mt-2 max-h-24 overflow-y-auto pr-1 text-sm text-slate-300 break-words">
        🚫No hay archivos, necesitas actualizar el perfil del inquilino 🚫.
      </p>
      <div class="mt-2 grid grid-cols-1 gap-2 sm:auto-cols-max sm:grid-flow-col">
        <button class="vh-detalle w-full sm:w-auto rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15" data-cat="archivos">Ver detalle</button>
        
      </div>
    </div>
  </div>

<div class="min-w-0">
  <!-- Rostro -->
  <div class="w-full max-w-full overflow-hidden rounded-2xl border border-white/10 bg-white/5 p-4 shadow-xl backdrop-blur" data-cat="rostro">
    <h3 class="text-base font-semibold">Rostro</h3>

    <div id="chips-rostro" class="mt-2 flex flex-wrap gap-2">
      <span id="chip-rostro-score" data-key="comparefaces"
            class="rounded-full border border-rose-400/30 bg-rose-400/15 px-3 py-1 text-xs">
        CompareFaces ≥ 0%
      </span>
      <span id="chip-rostro-matches" data-key="matches"
            class="rounded-full border border-rose-400/30 bg-rose-400/15 px-3 py-1 text-xs">
        1 coincidencia
      </span>
    </div>

    <p id="txt-rostro" class="mt-2 max-h-24 overflow-y-auto pr-1 text-sm text-slate-300 break-words">
      🚫No hay información, actualiza el perfil del inquilino🚫.
    </p>

    <div class="mt-2 grid grid-cols-1 gap-2 sm:auto-cols-max sm:grid-flow-col">
      <button class="vh-detalle w-full sm:w-auto rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15" data-cat="rostro">Ver detalle</button>
      <button class="vh-recalc w-full sm:w-auto rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15" data-check="save_face">Volver a comparar</button>
    </div>
  </div>
</div>


<div class="min-w-0">
  <!-- Identidad -->
  <div class="w-full max-w-full overflow-hidden rounded-2xl border border-white/10 bg-white/5 p-4 shadow-xl backdrop-blur" data-cat="identidad">
    <h3 class="text-base font-semibold">Identidad</h3>

    <div id="chips-identidad" class="mt-2 flex flex-wrap gap-2">
      <span id="chip-identidad-nombres" data-key="nombres_apellidos"
            class="rounded-full border border-rose-400/30 bg-rose-400/15 px-3 py-1 text-xs">
       
      </span>
      <span id="chip-identidad-curp" data-key="curp_cic"
            class="rounded-full border border-rose-400/30 bg-rose-400/15 px-3 py-1 text-xs">
      
      </span>
    </div>

    <p id="txt-identidad" class="vh-scroll mt-2 pr-2 text-sm text-slate-300 break-words">
     
    </p>

    <div class="mt-2 grid grid-cols-1 gap-2 sm:auto-cols-max sm:grid-flow-col">
      <button class="vh-detalle w-full sm:w-auto rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15" data-cat="identidad">Ver detalle</button>
      <button class="vh-recalc w-full sm:w-auto rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15" data-check="save_match">Leer CURP/CIC</button>
    </div>
  </div>
</div>

<!-- div pago inicial -->

<!-- Pago inicial -->
<div class="w-full max-w-full overflow-hidden rounded-2xl border border-white/10 bg-white/5 p-4 shadow-xl backdrop-blur" data-cat="pago_inicial">
  <h3 class="text-base font-semibold">Pago inicial</h3>

  <div class="mt-3 flex items-center gap-3">
    <label class="flex items-center gap-3 select-none">
  <span class="text-sm text-slate-300">Pago Recibido?</span>

  <!-- el peer -->
  <input id="toggle-pago" type="checkbox" class="peer sr-only" />

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

  <em id="toggle-pago-label" class="not-italic text-xs text-slate-400 peer-checked:text-emerald-400"></em>
</label>
  </div>

  <div id="pago-status-msg" class="mt-2 text-xs text-slate-400"></div>

  <div class="mt-2 grid grid-cols-1 gap-2 sm:auto-cols-max sm:grid-flow-col">
    <button class="vh-detalle w-full sm:w-auto rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15" data-cat="pago_inicial">Ver detalle</button>
  </div>
</div>



  <!-- Aquí continuarías con Documentos, Ingresos, Pago inicial y Demandas repitiendo el patrón -->
</section>

<?php
// Asegúrate de tener estos datos disponibles en la vista.
// Si tienes $inquilino, úsalo; si no, ajusta a tus variables.
$idInquilino = (int)($inquilino['id'] ?? $idInquilino ?? 0);
$nombre      = $inquilino['nombre_inquilino'] ?? '';
$apP         = $inquilino['apellidop_inquilino'] ?? '';
$apM         = $inquilino['apellidom_inquilino'] ?? '';
$curp        = $inquilino['curp'] ?? null;
$rfc         = $inquilino['rfc'] ?? null;
$slug        = $inquilino['slug'] ?? ($slug ?? null);
?>
<div id="validacionMeta"
     data-id="<?= $idInquilino ?>"
     data-nombre="<?= htmlspecialchars($nombre) ?>"
     data-apellido_p="<?= htmlspecialchars($apP) ?>"
     data-apellido_m="<?= htmlspecialchars($apM) ?>"
     data-curp="<?= htmlspecialchars($curp ?? '') ?>"
     data-rfc="<?= htmlspecialchars($rfc ?? '') ?>"
     data-slug="<?= htmlspecialchars($slug ?? '') ?>">
</div>
<section class="bg-gray-900 border border-white/10 rounded-2xl p-6 shadow-xl my-6">
  <div class="flex items-center justify-between mb-4">
    <div class="flex items-center gap-3">
      <div class="p-2 bg-indigo-600/30 rounded-xl">
        <svg class="w-6 h-6 text-indigo-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M3 7h18M3 12h18M3 17h18" />
        </svg>
      </div>
      <h2 class="text-xl font-semibold text-white">Demandas y litigios</h2>
    </div>
    <div class="flex gap-2">
      <button id="btnRunValidacion"
        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white shadow">
        Ejecutar validación ahora
      </button>
      <button id="btnVerUltimo"
        class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white">
        Ver último reporte
      </button>
    </div>
  </div>

  <!-- Chips dinámicos -->
  <div id="chipsPortales" class="flex flex-wrap gap-2 mb-4">
    <!-- Se llenan vía fetch -->
  </div>

  <!-- Último reporte -->
  <div id="reporteContainer" class="bg-white/5 rounded-xl p-4 text-sm text-gray-200 hidden">
    <!-- Se llena con JS -->
  </div>

  <!-- Historial completo -->
  <div id="historialContainer" class="mt-6">
    <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
      <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M12 20l9-5-9-5-9 5 9 5z" />
        <path d="M12 12V4l9-5-9-5-9 5z" />
      </svg>
      Historial de demandas
    </h3>

    <div class="grid md:grid-cols-2 gap-6">
      <?php if (!empty($historial)): ?>
        <?php foreach ($historial as $item): ?>
          <div class="bg-gray-800 border border-gray-700 rounded-xl p-5 shadow-xl">
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
</section>

<section id="cardJuridico" class="bg-gray-900 border border-white/10 rounded-2xl p-6 shadow-xl mt-6">
  <div class="flex items-center justify-between mb-4">
    <h2 class="text-xl font-semibold text-white">Validación jurídica</h2>
    <span id="juridicoStatus" class="text-sm text-gray-300">cargando…</span>
  </div>

  <div id="juridicoResumen" class="text-gray-200 text-sm mb-4"></div>

  <div id="juridicoEvidencias" class="space-y-3"></div>
</section>



<!-- ARCHIVOS (previews) -->
<section class="mt-4">
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


 <!-- Footer estático -->
<div class="mt-8">
  <div class="flex flex-col sm:flex-row items-center justify-between gap-3 rounded-2xl border border-white/10 bg-white/5 p-3 shadow-2xl backdrop-blur">
    <div id="vh-ts-bottom" class="text-sm text-slate-300 text-center sm:text-left">
      Última actualización —
    </div>
    <div class="grid grid-cols-1 gap-2 sm:auto-cols-max sm:grid-flow-col">
      <button id="btn-borrador" class="w-full sm:w-auto rounded-xl border border-white/10 bg-white/10 px-4 py-2 font-semibold hover:bg-white/15">
        Guardar borrador
      </button>
      <button id="btn-finalizar" class="w-full sm:w-auto rounded-xl bg-gradient-to-r from-indigo-400 to-cyan-400 px-4 py-2 font-semibold text-black">
        Finalizar validación
      </button>
    </div>
  </div>
</div>

</div>

<!-- Modal JSON: bottom-sheet móvil, centrado desktop -->
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
<script>
function setReporteLoading(isLoading){
  const $reporte = document.getElementById('reporteContainer');
  $reporte.classList.remove('hidden');
  if(!isLoading) return; // cuando termine, tú reemplazas el innerHTML con el contenido real

  $reporte.innerHTML = `
    <div class="space-y-3">
      <div class="flex items-center gap-2">
        <div class="skel h-5 w-28 rounded"></div>
        <div class="skel h-5 w-16 rounded"></div>
        <div class="skel h-5 w-24 rounded"></div>
        <div class="skel h-5 w-20 rounded"></div>
      </div>
      <div class="skel h-4 w-40 rounded"></div>
      <div class="grid md:grid-cols-2 gap-3">
        <div class="skel h-20 w-full rounded-lg"></div>
        <div class="skel h-20 w-full rounded-lg"></div>
      </div>
    </div>
  `;
}
</script>
<script>
    // Helper: fetch con timeout + parseo seguro de JSON
async function safeJsonFetch(url, options = {}, timeoutMs = 20000) {
  const ctrl = new AbortController();
  const t = setTimeout(() => ctrl.abort(), timeoutMs);
  try {
    const res = await fetch(url, { ...options, signal: ctrl.signal });
    const text = await res.text(); // leemos como texto SIEMPRE
    let data;
    try {
      data = text ? JSON.parse(text) : null;
    } catch (e) {
      // No es JSON: lanzamos error con muestra del body
      throw new Error(`Respuesta no-JSON (${res.status}): ${text.slice(0,180)}…`);
    }
    if (!res.ok) {
      // HTTP !200: arrojar con detalle del body ya parseado
      const msg = (data && (data.mensaje || data.error)) || `HTTP ${res.status}`;
      throw new Error(msg);
    }
    return data;
  } finally {
    clearTimeout(t);
  }
}

/* =====================
 * Globals (PHP -> JS)
 * ===================== */
window.baseUrl = "<?= rtrim($baseUrl ?? '/as-2026/Backend/admin','/') ?>";
window.idInquilino = <?= (int)($inquilino['id'] ?? 0) ?>;
window.slug = "<?= htmlspecialchars($inquilino['slug'] ?? '') ?>";
// 👇 agregar esto en la parte superior de tu script (antes de llamar a cargarChips)
const urls = {
  resumen: `${baseUrl}/validaciones/demandas/resumen/${idInquilino}`,
  ultimo:  `${baseUrl}/validaciones/demandas/ultimo/${idInquilino}`,
  run:     `${baseUrl}/validaciones/demandas/run/${idInquilino}`
};

/* =====================
 * Helpers de UI (únicos)
 * ===================== */
window.vhSetChipState = window.vhSetChipState || function(el, state /* 'ok'|'warn'|'fail' */){
  if (!el) return;
  el.classList.remove(
    'border-emerald-400/30','bg-emerald-400/15',
    'border-amber-400/30','bg-amber-400/15',
    'border-rose-400/30','bg-rose-400/15',
    'border-white/10','bg-white/5'
  );
  if (state === 'ok')        el.classList.add('border-emerald-400/30','bg-emerald-400/15');
  else if (state === 'warn') el.classList.add('border-amber-400/30','bg-amber-400/15');
  else                       el.classList.add('border-rose-400/30','bg-rose-400/15');
};

const ADMIN_BASE = <?= json_encode($ADMIN_BASE, JSON_UNESCAPED_SLASHES) ?> || '';
const SLUG       = <?= json_encode($slug, JSON_UNESCAPED_SLASHES) ?>;
const ID_INQ     = <?= json_encode($idInq, JSON_UNESCAPED_SLASHES) ?>;

const $  = s => document.querySelector(s);
const $$ = s => Array.from(document.querySelectorAll(s));
const setText = (sel, txt) => { const el=$(sel); if (el) el.textContent = txt; };
const pct = n => Math.max(0, Math.min(100, Math.round(n)));
const pretty = o => { try { return JSON.stringify(o, null, 2) } catch { return String(o ?? '') } };

// Alias modal JSON
function showJSON(title, data){ abrirVHModal({ title, text: pretty(data) }); }

// Pills de semáforo
function setPill(id, val){
  const el = document.getElementById('pill-'+id);
  if (!el) return;
  const dot = el.querySelector('span.rounded-full');
  if (!dot) return;
  const v = String(val).toUpperCase();
  dot.classList.remove('bg-emerald-500','bg-amber-500','bg-rose-500');
  if (v==='1' || v==='OK')        dot.classList.add('bg-emerald-500');
  else if (v==='0' || v==='NO_OK') dot.classList.add('bg-rose-500');
  else                             dot.classList.add('bg-amber-500');
}

/* =====================
 * ROSTRO (helpers robustos)
 * ===================== */
const _toNum  = v => Number.isFinite(Number(v)) ? Number(v) : NaN;
const _toInt  = v => Number.isFinite(parseInt(v)) ? parseInt(v) : NaN;
function _getRostroObj(raw){
  let d = raw && (raw.json ?? raw);
  if (typeof d === 'string') { try { d = JSON.parse(d); } catch { d = {}; } }
  return (d && typeof d === 'object') ? d : {};
}
function extractRostro(detailsObj, resumenStr=''){
  const d = _getRostroObj(detailsObj);
  let similarity = _toNum(d?.best?.similarity ?? d?.similarity ?? d?.score);
  let threshold  = _toNum(d?.threshold ?? d?.umbral);
  let matches    = _toInt(d?.match_count ?? d?.matches ?? d?.count ?? d?.matchesCount);

  const arrays = []
    .concat(Array.isArray(d.FaceMatches)  ? [d.FaceMatches]  : [])
    .concat(Array.isArray(d.MatchedFaces) ? [d.MatchedFaces] : [])
    .concat(Array.isArray(d.faceMatches)  ? [d.faceMatches]  : [])
    .concat(Array.isArray(d.matches)      ? [d.matches]      : []);
  const all = arrays.flat();

  if (!Number.isFinite(similarity) && all.length) {
    const sims = all.map(m => _toNum(m?.Similarity ?? m?.similarity ?? m?.score)).filter(Number.isFinite);
    if (sims.length) similarity = Math.max(...sims);
  }
  if (!Number.isFinite(matches) && all.length) matches = all.length;

  if (!Number.isFinite(similarity) && resumenStr) {
    const m = resumenStr.match(/similitud\s*([\d.]+)\s*%/i) || resumenStr.match(/(\d{1,3}(?:\.\d+)?)\s*%/);
    if (m) similarity = _toNum(m[1]);
  }
  if (!Number.isFinite(threshold) && resumenStr) {
    const m = resumenStr.match(/umbral\s*([\d.]+)\s*%/i);
    if (m) threshold = _toNum(m[1]);
  }
  if (!Number.isFinite(matches) && resumenStr) {
    const m = resumenStr.match(/(\d+)\s*coincidenc/i);
    if (m) matches = _toInt(m[1]);
  }

  similarity = Number.isFinite(similarity) ? Math.max(0, Math.min(100, Math.round(similarity))) : null;
  threshold  = Number.isFinite(threshold)  ? Math.max(0, Math.min(100, Math.round(threshold)))  : 90;
  matches    = Number.isFinite(matches)    ? Math.max(0, Math.round(matches))                    : 0;
  return { similarity, threshold, matches };
}
function evalRostroState(similarity, matches, threshold){
  if ((matches ?? 0) >= 1 && (similarity ?? 0) >= threshold) return 'ok';
  if ((matches ?? 0) >= 1 && (similarity ?? 0) >= Math.max(0, threshold - 5)) return 'warn';
  return 'fail';
}
function updateChipsRostro(detalles, resumenes){
  const rText = (resumenes?.rostro || '').trim();
  const { similarity, threshold, matches } = extractRostro(detalles?.rostro, rText);
  const state = evalRostroState(similarity, matches, threshold);

  const scoreEl   = document.getElementById('chip-rostro-score');
  const matchesEl = document.getElementById('chip-rostro-matches');

  if (scoreEl) {
    vhSetChipState(scoreEl, state);
    scoreEl.textContent = `CompareFaces ≥ ${Number.isFinite(similarity) ? similarity : '—'}%`;
  }
  if (matchesEl) {
    vhSetChipState(matchesEl, state);
    matchesEl.textContent = `${matches} ${matches===1 ? 'coincidencia' : 'coincidencias'}`;
  }

  const p = document.getElementById('txt-rostro');
  if (p) {
    if (rText) p.textContent = rText;
    else {
      const simStr = Number.isFinite(similarity) ? `${similarity}%` : '—';
      p.textContent = `Rostro: similitud ${simStr} (umbral ${threshold}%), ${matches} ${matches===1?'coincidencia':'coincidencias'}.`;
    }
  }
}

/* ========= IDENTIDAD ========= */
const CURP_RE = /^[A-Z][AEIOUX][A-Z]{2}\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])[HM](AS|BC|BS|CC|CL|CM|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TL|TS|VZ|YN|ZS|NE)[B-DF-HJ-NP-TV-Z]{3}[A-Z\d]\d$/;
function isCURP(s){ return typeof s === 'string' && CURP_RE.test(s.toUpperCase()); }
function hasCICLike(s){ if (!s) return false; const m = String(s).replace(/\D+/g,''); return m.length >= 9; }
function _parseJSONSafe(s){ try { return JSON.parse(s); } catch { return null; } }
function _getIdentidadData(raw){
  if (!raw) return {};
  let node = raw.json ?? raw;
  if (typeof node === 'string') {
    const inner = _parseJSONSafe(node);
    if (inner) return inner;
  }
  if (typeof node === 'object' && typeof node.resumen === 'string') {
    const inner = _parseJSONSafe(node.resumen);
    if (inner) return inner;
  }
  return (typeof node === 'object') ? node : {};
}
function extractIdentidadFromResumen(rawIdentidad){
  const identity = _getIdentidadData(rawIdentidad);
  const detalles = identity.detalles || {};
  const ocrVals  = identity.ocr || {};
  const bdVals   = identity.bd  || {};
  const flags = ['apellidop','apellidom','nombres'];
  const trues = flags.reduce((acc,k)=> acc + (detalles[k] ? 1 : 0), 0);
  const names_ok      = identity.overall === true || trues === flags.length;
  const names_partial = !names_ok && trues > 0;
  const curpStr = (ocrVals.curp || bdVals.curp || '').toString().trim();
  const cicStr  = (ocrVals.cic  || bdVals.cic  || '').toString().trim();
  const curp_ok = isCURP(curpStr) || hasCICLike(cicStr);
  return { names_ok, names_partial, curp_ok, curpStr, cicStr };
}
function updateChipsIdentidad(detalles, resumenes){
  const rText = (resumenes?.identidad || '').trim();
  const info  = extractIdentidadFromResumen(detalles?.identidad);
  const elNames = document.getElementById('chip-identidad-nombres');
  if (elNames) {
    const state = info.names_ok ? 'ok' : (info.names_partial ? 'warn' : 'fail');
    vhSetChipState(elNames, state);
    elNames.textContent = info.names_ok
      ? 'Nombres/Apellidos OK'
      : (info.names_partial ? 'Nombres/Apellidos parcial' : 'Nombres/Apellidos pendiente');
  }
  const elCurp = document.getElementById('chip-identidad-curp');
  if (elCurp) {
    const state = info.curp_ok ? 'ok' : 'fail';
    vhSetChipState(elCurp, state);
    elCurp.textContent = info.curp_ok
      ? (isCURP(info.curpStr) ? 'CURP válida' : 'CIC/OCR presente')
      : 'CURP/CIC pendiente';
  }
  const p = document.getElementById('txt-identidad');
  if (p) {
    if (rText) p.textContent = rText;
    else {
      const nText = info.names_ok ? 'Nombres y apellidos detectados'
                   : info.names_partial ? 'Solo parte del nombre detectada'
                   : 'No fue posible confirmar nombre completo';
      const cText = info.curp_ok ? (isCURP(info.curpStr) ? 'CURP válida' : 'CIC/OCR presente')
                   : 'CURP/CIC pendiente';
      p.textContent = `Identidad: ${nText} · ${cText}.`;
    }
  }
}

/* =====================
 * Auto-guardar Pago Inicial (switch)
 * ===================== */
(function attachPagoInicialAutosave(){
  const chk = document.getElementById('toggle-pago');
  if (!chk) return;
  chk.addEventListener('change', async (e) => {
    const checked = !!e.target.checked;
    const resumen = checked ? 'Se reportó pago inicial' : 'Se retiró el reporte de pago inicial';
    const jsonPayload = JSON.stringify({
      proceso_pago_inicial: checked ? 1 : 0,
      resumen,
      fecha_registro: new Date().toISOString()
    });
    const fd = new FormData();
    fd.append('id_inquilino', String(ID_INQ));
    fd.append('proceso_pago_inicial', checked ? '1' : '0');
    fd.append('pago_inicial', jsonPayload);
    setText('#toggle-pago-label', 'Guardando…');
    setText('#pago-status-msg', '');
    try {
      const r = await fetch(`${ADMIN_BASE}/inquilino/editar-validaciones`, {
        method:'POST', body: fd, credentials:'include'
      });
      const j = await r.json();
      if (!j?.ok) throw new Error(j?.error || 'No se pudo guardar cambios');
      setText('#toggle-pago-label', checked ? 'OK' : '');
      setText('#pago-status-msg', checked ? 'Pago inicial guardado.' : 'Pago inicial desmarcado.');
      loadStatus().catch(console.error);
    } catch (err) {
      e.target.checked = !checked; // revertir
      setText('#toggle-pago-label', e.target.checked ? 'OK' : '');
      setText('#pago-status-msg', 'Error al guardar. Intenta de nuevo.');
      console.error(err);
    }
  });
})();

/* =====================
 * loadStatus (limpio)
 * ===================== */
async function loadStatus(){
  const url = `${ADMIN_BASE}/inquilino/${encodeURIComponent(SLUG)}/validar?check=status`;
  const res = await fetch(url, { credentials:'include' });
  const j   = await res.json();
  if (!j?.ok) throw new Error(j?.mensaje || 'No fue posible obtener estado');

  const sem = j.semaforos || {};
  if (!Object.keys(sem).length && j.resumen) {
    for (const k of Object.keys(j.resumen)) sem[k] = j.resumen[k]?.proceso ?? 2;
  }
  setPill('archivos',   sem.archivos);
  setPill('rostro',     sem.rostro);
  setPill('identidad',  sem.identidad);
  setPill('documentos', sem.documentos);
  setPill('ingresos',   sem.ingresos);
  setPill('pago',       sem.pago_inicial);
  setPill('demandas',   sem.demandas);

  const categories     = ['archivos','rostro','identidad','documentos','ingresos','pago_inicial','demandas'];
  const completedCount = categories.reduce((acc, k) => acc + (Number(sem?.[k]) === 1 ? 1 : 0), 0);
  const totalCount     = categories.length;
  const progressPct    = pct((completedCount / totalCount) * 100);
  const bar = document.getElementById('vh-progress');
  if (bar) bar.style.width = progressPct + '%';
  setText('#vh-progress-text', `${completedCount} de ${totalCount} validaciones completas`);

  const R = j.resumenes || {};
  const resumenHumano = buildResumenHumano(sem, R);
  setText('#vh-resumen', R.global ? R.global : resumenHumano);

  if (R.archivos)     setText('#txt-archivos', R.archivos);
  if (R.rostro)       setText('#txt-rostro', R.rostro);
  if (R.identidad)    setText('#txt-identidad', R.identidad);
  if (R.documentos)   setText('#txt-documentos', R.documentos);
  if (R.ingresos)     setText('#txt-ingresos', R.ingresos);
  if (R.pago_inicial) setText('#txt-pago', R.pago_inicial);
  if (R.demandas)     setText('#txt-demandas', R.demandas);

  const okPago = Number(sem.pago_inicial) === 1;
  const chk = document.getElementById('toggle-pago');
  if (chk) chk.checked = okPago;
  setText('#toggle-pago-label', okPago ? 'OK' : '');

  const tsApi = j.updated_at || j.ts || null;
  const ts = tsApi ? new Date(tsApi).toLocaleString() : new Date().toLocaleString();
  setText('#vh-ts', `Última actualización ${ts}`);
  setText('#vh-ts-bottom', `Última actualización ${ts}`);

  window.__VH_DETALLES__ = j.detalles || j;

  if (typeof updateChipsRostro === 'function') updateChipsRostro(window.__VH_DETALLES__, R);
  if (typeof updateChipsIdentidad === 'function') updateChipsIdentidad(window.__VH_DETALLES__, R);
}

/* =====================
 * Acciones / botones generales
 * ===================== */
async function recalc(check){
  const u = `${ADMIN_BASE}/inquilino/${encodeURIComponent(SLUG)}/validar?check=${encodeURIComponent(check)}`;
  const r = await fetch(u, { credentials:'include' });
  try { await r.json(); } catch {}
  await loadStatus();
}
async function savePagoInicial(){
  const chk = document.getElementById('toggle-pago');
  const fd = new FormData();
  fd.append('id_inquilino', String(<?= json_encode($idInq) ?>));
  fd.append('proceso_pago_inicial', chk?.checked ? '1' : '0');
  fd.append('pago_inicial_resumen', chk?.checked ? 'Pago inicial confirmado' : 'Pago inicial no confirmado');
  const r = await fetch(`${ADMIN_BASE}/inquilino/editar-validaciones`, { method:'POST', body: fd, credentials:'include' });
  const j = await r.json();
  if (!j?.ok) throw new Error(j?.error || 'No se pudo guardar');
  await loadStatus();
}
document.getElementById('btn-recalc')?.addEventListener('click', ()=> recalc('resumen_full'));
document.getElementById('btn-resumen')?.addEventListener('click', ()=> recalc('resumen_full'));
document.getElementById('btn-continuar')?.addEventListener('click', ()=> location.href = `${ADMIN_BASE}/inquilino/${SLUG}`);
$$('.vh-recalc').forEach(btn=> btn.addEventListener('click', ()=> recalc(btn.dataset.check || 'status')));
$$('.vh-detalle').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    const cat  = btn.dataset.cat || 'detalle';
    const data = (window.__VH_DETALLES__ && (window.__VH_DETALLES__[cat]?.json || window.__VH_DETALLES__[cat])) || {info:'Sin detalle'};
    showJSON(`Detalle: ${cat}`, data);
  });
});
document.getElementById('btn-guardar-pago')?.addEventListener('click', async ()=>{
  try{ await savePagoInicial(); }catch(e){ Swal.fire({ icon: 'error', title: 'Error de red', text: 'No pudimos conectar con el servidor.' });
 }
});

/* =====================
 * Modal (viewer)
 * ===================== */
(function(){
  const modal   = document.getElementById('vh-modal');
  const box     = document.getElementById('vh-modal-box');
  const overlay = document.getElementById('vh-modal-overlay');
  const titleEl = document.getElementById('vh-modal-title');

  let contentEl = document.getElementById('vh-modal-content');
  if (!contentEl) {
    const pre  = document.getElementById('vh-modal-pre');
    contentEl = document.createElement('div');
    contentEl.id = 'vh-modal-content';
    contentEl.className = 'rounded-xl overflow-hidden';
    if (pre && pre.parentNode) pre.parentNode.insertBefore(contentEl, pre);
  }
  const preEl = document.getElementById('vh-modal-pre');
  const reflow = (el) => void el.offsetHeight;

  window.abrirVHModal = function ({ title = 'Detalle', text = '', html = '' } = {}) {
    titleEl.textContent = title;
    if (html) {
      contentEl.innerHTML = html;
      if (preEl) { preEl.textContent = ''; preEl.classList.add('hidden'); }
    } else {
      if (preEl) { preEl.textContent = String(text); preEl.classList.remove('hidden'); }
      contentEl.innerHTML = '';
    }
    modal.classList.remove('hidden');
    reflow(modal);
    modal.classList.remove('opacity-0');
    box.classList.remove('translate-y-3');
    document.body.classList.add('overflow-hidden');
  };
  window.cerrarVHModal = function () {
    modal.classList.add('opacity-0');
    box.classList.add('translate-y-3');
    setTimeout(() => {
      modal.classList.add('hidden');
      document.body.classList.remove('overflow-hidden');
      contentEl.innerHTML = '';
      if (preEl) preEl.textContent = '';
    }, 200);
  };
  window.abrirModal  = window.abrirVHModal;
  window.cerrarModal = window.cerrarVHModal;
  overlay?.addEventListener('click', cerrarVHModal);
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !modal.classList.contains('hidden')) cerrarVHModal();
  });
  window.mostrarDetalleJSON = function (title, data) {
    const txt = (typeof data === 'string') ? data : JSON.stringify(data, null, 2);
    abrirVHModal({ title, text: txt });
  };
  window.verMediaEnModal = function ({ title = 'Vista previa', url = '', mime = '' } = {}) {
    if (!url) return;
    const isImg = mime?.startsWith?.('image/') || /\.(jpe?g|png|webp|gif|bmp)$/i.test((url.split('?')[0]||''));
    const html = isImg
      ? `<div class="bg-black/30 grid place-items-center rounded-xl">
           <img src="${url}" alt="" class="max-h-[70vh] w-auto object-contain">
         </div>`
      : `<iframe src="${url}#toolbar=1&navpanes=0&scrollbar=1"
                 class="w-full h-[70vh] rounded-xl border border-white/10 bg-black/30"></iframe>`;
    abrirVHModal({ title, html });
  };
})();

/* =====================
 * Presignadas (previews + chips)
 * ===================== */
(function(){
  const ADMIN = ADMIN_BASE;
  const SLUGX = SLUG;
  const $id = (id) => document.getElementById(id);

  function setImgClickable(id, file, label) {
    const el = $id(id);
    if (!el || !file?.url) return;
    el.src = file.url;
    el.style.cursor = 'zoom-in';
    el.addEventListener('click', () => verMediaEnModal({
      title: label || 'Vista previa', url: file.url, mime: file.mime_type || ''
    }));
  }
  function setPdfClickable(id, file, label, accent = false) {
    const box = $id(id);
    if (!box) return;
    if (file?.url) {
      box.innerHTML = `
        <button type="button"
                class="flex h-full w-full items-center justify-center ${accent ? 'rounded-xl border border-rose-400/30 bg-rose-400/10' : 'rounded-xl border border-white/10 bg-white/5'} text-lg">
          PDF
        </button>`;
      box.querySelector('button').addEventListener('click', () => verMediaEnModal({
        title: label || 'PDF', url: file.url, mime: file.mime_type || 'application/pdf'
      }));
    } else {
      box.textContent = 'PDF';
    }
  }
  function indexByTipo(files){
    const by = { comprobantes: [] };
    for (const f of (files || [])) {
      if (f.tipo === 'comprobante_ingreso') by.comprobantes.push(f);
      else by[f.tipo] = f;
    }
    return by;
  }
  function updateChipsArchivos(by){
    const root = document.getElementById('chips-archivos');
    if (!root) return;
    const present = (keys) => (keys || []).some(k => !!by[k]);

    vhSetChipState(root.querySelector('[data-key="selfie"]'),
                   present(['selfie']) ? 'ok' : 'fail');
    vhSetChipState(root.querySelector('[data-key="ine_frontal"]'),
                   present(['ine_frontal','ine-front','ine_front']) ? 'ok' : 'fail');
    vhSetChipState(root.querySelector('[data-key="ine_reverso"]'),
                   present(['ine_reverso','ine-back','ine_back']) ? 'ok' : 'fail');
    vhSetChipState(root.querySelector('[data-key="pasaporte"]'),
                   present(['pasaporte','passport']) ? 'ok' : 'fail');
    vhSetChipState(root.querySelector('[data-key="fm"]'),
                   present(['fm','fm2','fm3','fm2_fm3']) ? 'ok' : 'fail');

    const comps = by.comprobantes || [];
    const elComp = root.querySelector('[data-key="comprobante_ingreso"]');
    if (elComp) {
      if (comps.length >= 3) vhSetChipState(elComp, 'ok');
      else if (comps.length >= 1) vhSetChipState(elComp, 'warn');
      else vhSetChipState(elComp, 'fail');

      const base = elComp.getAttribute('data-label') || 'Comprobantes';
      elComp.setAttribute('data-label', base);
      elComp.textContent = `${base}${comps.length ? ` (${comps.length})` : ''}`;
    }
  }

  let __vh_last_presign = 0;
  async function cargarPresignadas(){
    try{
      const res = await fetch(`${ADMIN}/inquilino/${encodeURIComponent(SLUGX)}/archivos-presignados`, { credentials:'include' });
      const j = await res.json();
      if (!j?.ok) return;

      const by = indexByTipo(j.files);

      if (by.selfie)      setImgClickable('prev-selfie',    by.selfie,      'Selfie');
      if (by.ine_frontal) setImgClickable('prev-ine-front', by.ine_frontal, 'INE — frontal');
      if (by.ine_reverso) setImgClickable('prev-ine-back',  by.ine_reverso, 'INE — reverso');

      setPdfClickable('prev-comp-1', by.comprobantes?.[0], 'Comprobante 1');
      setPdfClickable('prev-comp-2', by.comprobantes?.[1], 'Comprobante 2');
      setPdfClickable('prev-comp-3', by.comprobantes?.[2], 'Comprobante 3', true);

      updateChipsArchivos(by);
      __vh_last_presign = Date.now();
    }catch(e){
      console.error('Error cargando presignadas:', e);
    }
  }
  setInterval(() => {
    if (Date.now() - __vh_last_presign > 8 * 60 * 1000) {
      cargarPresignadas().catch(console.error);
    }
  }, 60 * 1000);

  if (document.readyState !== 'loading') cargarPresignadas();
  else document.addEventListener('DOMContentLoaded', cargarPresignadas);
})();

/* =====================
 * Resumen humano
 * ===================== */
function vhIcon(v){ const n=Number(v); if(n===1) return '✅'; if(n===0) return '🚫'; return '⏳'; }
function vhLabel(v){ const n=Number(v); if(n===1) return 'OK'; if(n===0) return 'No OK'; return 'Pendiente'; }
function buildResumenHumano(sem = {}, R = {}){
  if (R?.global && String(R.global).trim()) return R.global;
  const parts = [];
  const push = (k, label, extra='') => { if (sem[k] === undefined) return; parts.push(`${vhIcon(sem[k])} ${label}${extra ? ' ('+extra+')' : ''}`); };
  const extraIngresos = /\b(\d+)\s*\/\s*(\d+)/.exec(R?.ingresos || '');
  const ingresosTag = extraIngresos ? `${extraIngresos[0]}` : '';
  push('identidad','Identidad'); push('rostro','Rostro'); push('documentos','Documentos'); push('archivos','Archivos');
  push('ingresos','Ingresos', ingresosTag); push('pago_inicial','Pago inicial'); push('demandas','Demandas');
  return parts.join(' · ');
}

/* =====================
 * Arranque inicial de estado
 * ===================== */
function __bootLoadStatus(){
  loadStatus().catch(e=>{
    console.error(e);
    setText('#vh-resumen', 'No fue posible cargar el estado de validaciones.');
  });
}
if (document.readyState !== 'loading') __bootLoadStatus();
else document.addEventListener('DOMContentLoaded', __bootLoadStatus);

/* =====================
 * Módulo DEMANDAS + JURÍDICO (único)
 * ===================== */
(function(){
  const baseUrl = window.baseUrl;
  const idInquilino = window.idInquilino;

  const chipsEl   = document.getElementById('chipsPortales');
  const reporteEl = document.getElementById('reporteContainer');
  const btnRun    = document.getElementById('btnRunValidacion');
  const btnUltimo = document.getElementById('btnVerUltimo');

  function chipColor(status, clasificacion){
    if(status === 'manual_required') return 'bg-yellow-600/30 text-yellow-200 border-yellow-600/30';
    if(status === 'error') return 'bg-red-600/30 text-red-200 border-red-600/30';
    if(status === 'ok'){
      if(clasificacion === 'match_alto') return 'bg-green-600/30 text-green-200 border-green-600/30';
      if(clasificacion === 'posible_match') return 'bg-amber-600/30 text-amber-200 border-amber-600/30';
      return 'bg-white/10 text-white border-white/10';
    }
    return 'bg-white/10 text-white border-white/10';
  }

  let _autoTimer;

function setChipsLoading(isLoading){
  const $chips = document.getElementById('chipsPortales');
  if(!isLoading){ return; }
  $chips.innerHTML = `
    <div class="flex flex-wrap gap-2">
      <div class="skel h-7 w-28 rounded-full"></div>
      <div class="skel h-7 w-24 rounded-full"></div>
      <div class="skel h-7 w-36 rounded-full"></div>
      <div class="skel h-7 w-20 rounded-full"></div>
    </div>
  `;
}

  async function cargarChips() {
  const $chips = document.getElementById('chipsPortales');
  setChipsLoading(true); // ⟵ NUEVO: muestra skeleton

  try {
    const res = await fetch(urls.resumen);
    const data = await res.json();
    const items = Array.isArray(data.items) ? data.items : [];
    if (!items.length) {
      $chips.innerHTML = `<span class="text-gray-400 text-sm">Sin datos aún.</span>`;
      return;
    }
    const html = items.map(it => {
      const portal = (it.portal || '').toUpperCase();
      const status = it.status || 'no_data';
      const clasif = it.clasificacion || 'sin_evidencia';
      const score  = parseInt(it.score_max || 0, 10);
      const clsStatus = (s)=> s==='ok'?'bg-emerald-700 text-white':(s==='manual_required'?'bg-amber-600 text-black':(s==='error'?'bg-red-700 text-white':'bg-slate-600 text-white'));
      const clsClasif = (c)=> c==='match_alto'?'bg-red-600 text-white':(c==='posible_match'?'bg-yellow-400 text-black':'bg-emerald-600 text-white');

      return `
        <div class="flex items-center gap-2 bg-white/5 border border-white/10 px-3 py-1.5 rounded-full">
          <span class="text-xs text-indigo-300 font-medium">${portal}</span>
          <span class="px-2 py-0.5 rounded-full text-xs ${clsStatus(status)}">${status}</span>
          <span class="px-2 py-0.5 rounded-full text-xs ${clsClasif(clasif)}">${clasif}</span>
          <span class="px-2 py-0.5 rounded-full text-xs bg-indigo-700 text-white">score: ${score}</span>
        </div>`;
    }).join('');
    $chips.innerHTML = html;
  } catch (e) {
    console.error(e);
    $chips.innerHTML = `<span class="text-red-400 text-sm">Error al cargar chips</span>`;
  }
}

  async function verUltimo() {
  if (!reporteEl) return;
  reporteEl.classList.remove('hidden');
  reporteEl.innerHTML = '<div class="text-gray-400">Cargando…</div>';

  try {
    const r = await fetch(`${baseUrl}/validaciones/demandas/ultimo/${idInquilino}`);
    const data = await r.json();

    if (!data.ok || !data.reporte) {
      reporteEl.innerHTML = '<div class="text-gray-400">Sin reporte reciente.</div>';
      return;
    }

    const rep = data.reporte;
    const resultado = rep.resultado ? JSON.parse(rep.resultado) : null;
    const query = rep.query_usada ? JSON.parse(rep.query_usada) : null;

    // 👇 Aquí usamos linkS3 (nuevas líneas)
    const evidenciaLink = rep.evidencia_s3_key ? `<a href="${linkS3(rep.evidencia_s3_key)}" target="_blank" class="text-indigo-300 underline">Ver evidencia</a>` : '-';
    const rawJsonLink = rep.raw_json_s3_key ? `<a href="${linkS3(rep.raw_json_s3_key)}" target="_blank" class="text-indigo-300 underline">Ver JSON</a>` : '-';

    reporteEl.innerHTML = `
      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <p><span class="text-indigo-300">Portal:</span> ${rep.portal}</p>
          <p><span class="text-indigo-300">Status:</span> ${rep.status}</p>
          <p><span class="text-indigo-300">Clasificación:</span> ${rep.clasificacion ?? '-'}</p>
          <p><span class="text-indigo-300">Score máx:</span> ${rep.score_max ?? 0}</p>
          <p><span class="text-indigo-300">Consulta:</span> ${query?.variante ?? '-'} (${query?.fecha ?? '-'})</p>
          <p><span class="text-indigo-300">Fecha búsqueda:</span> ${rep.searched_at}</p>
        </div>
        <div>
          <p><span class="text-indigo-300">Evidencia S3:</span> ${evidenciaLink}</p>
          <p><span class="text-indigo-300">RAW JSON S3:</span> ${rawJsonLink}</p>
          ${rep.error_message ? `<p class="text-red-300"><span class="text-indigo-300">Error:</span> ${rep.error_message}</p>` : ''}
        </div>
      </div>
      <div class="mt-4">
        <h3 class="text-white font-semibold mb-2">Resultados</h3>
        ${Array.isArray(resultado) && resultado.length ? `
          <div class="space-y-2">
            ${resultado.map(it => `
              <div class="bg-black/20 rounded-lg p-3">
                <p class="text-sm"><span class="text-indigo-300">Expediente:</span> ${it.expediente ?? '-'}</p>
                <p class="text-sm"><span class="text-indigo-300">Juzgado:</span> ${it.juzgado ?? '-'}</p>
                <p class="text-sm"><span class="text-indigo-300">Tipo juicio:</span> ${it.tipo_juicio ?? '-'}</p>
                <p class="text-sm"><span class="text-indigo-300">Actor:</span> ${it.actor ?? '-'}</p>
                <p class="text-sm"><span class="text-indigo-300">Demandado:</span> ${it.demandado ?? '-'}</p>
                <p class="text-sm"><span class="text-indigo-300">Fecha:</span> ${it.fecha ?? '-'}</p>
                ${it.url ? `<a class="text-indigo-300 underline text-xs" href="${it.url}" target="_blank" rel="noopener">Ver enlace</a>` : ''}
              </div>
            `).join('')}
          </div>
        ` : `<p class="text-gray-400 text-sm">Sin resultados.</p>`}
      </div>
    `;
  } catch (e) {
    reporteEl.innerHTML = `<div class="text-red-300">Error: ${e.message}</div>`;
  }
}

  btnRun   ?.addEventListener('click', ejecutarValidacion);
  btnUltimo?.addEventListener('click', verUltimo);

  // Arranque del módulo Demandas
  (async () => {
    await cargarChips();
  })();

  // Exponer en namespace para otros bloques (auto-refresh)
  window.VH_DEMANDAS = {
    cargarChips,
    verUltimo
  };
})();

// 1) Toma los datos del inquilino desde un meta-div (si ya lo tienes en la vista)
const metaEl = document.getElementById('validacionMeta');
const datos = metaEl ? {
  nombre:     metaEl.dataset.nombre || '',
  apellido_p: metaEl.dataset.apellido_p || '',
  apellido_m: metaEl.dataset.apellido_m || '',
  curp:       metaEl.dataset.curp || '',
  rfc:        metaEl.dataset.rfc || '',
  slug:       metaEl.dataset.slug || ''
} : { nombre:'', apellido_p:'' }; // mínimos

// 2) Auto-refresh tras disparar la validación (cada 6s por 90s)
let _autoTimer;

// 3) Ejecutar validación ahora
// 3) Ejecutar validación ahora
async function ejecutarValidacion() {
  const btn = document.getElementById('btnRunValidacion');

  // Datos de la card superior (ya presentes en tu archivo)
  const metaEl = document.getElementById('vh-meta');
  const datos = metaEl ? {
    nombre:     metaEl.dataset.nombre || '',
    apellido_p: metaEl.dataset.apellidop || '',
    apellido_m: metaEl.dataset.apellidom || '',
    curp:       metaEl.dataset.curp || '',
    rfc:        metaEl.dataset.rfc || '',
    slug:       metaEl.dataset.slug || ''
  } : { nombre:'', apellido_p:'' };

  if (!datos.nombre || !datos.apellido_p) {
    Swal && Swal.fire
      ? Swal.fire({ icon:'warning', title:'Faltan datos', text:'Nombre y Apellido paterno son obligatorios.' })
      : alert('Nombre y Apellido paterno son obligatorios.');
    return;
  }

  btn.disabled = true;
  const prevTxt = btn.textContent;
  btn.textContent = 'Ejecutando…';

  try {
    const body = new FormData();
    body.append('nombre', datos.nombre);
    body.append('apellido_p', datos.apellido_p);
    if (datos.apellido_m) body.append('apellido_m', datos.apellido_m);
    if (datos.curp)       body.append('curp', datos.curp);
    if (datos.rfc)        body.append('rfc', datos.rfc);
    if (datos.slug)       body.append('slug', datos.slug);

    const res = await fetch(urls.run, { method: 'POST', body });
    const data = await res.json();

    if (data.ok) {
      Swal && Swal.fire
        ? Swal.fire({ icon:'success', title:'🔎 Intentos registrados', text:'Se inició la validación.' })
        : alert('Intentos registrados');

      // refresca chips y programa refresco automático
      await window.VH_DEMANDAS?.cargarChips?.();
      postRunAutoRefresh();
    } else {
      Swal && Swal.fire
        ? Swal.fire({ icon:'error', title:'No se pudo iniciar', text: data.mensaje || 'Intenta de nuevo.' })
        : alert(data.mensaje || 'No se pudo iniciar');
    }
  } catch (e) {
    Swal && Swal.fire
      ? Swal.fire({ icon:'error', title:'Error de red', text:'No pudimos conectar con el servidor.' })
      : alert('Error de red');
  } finally {
    btn.disabled = false;
    btn.textContent = prevTxt;
  }
}

// 4) Bind del botón
document.getElementById('btnRunValidacion')?.addEventListener('click', ejecutarValidacion);


/* =====================
 * JURÍDICO (IIFE async)
 * ===================== */
(async () => {
  const endpoint = `${window.baseUrl}/inquilino/${window.slug}/validaciones/juridico`;
  const statusEl = document.getElementById('juridicoStatus');
  const resumenEl = document.getElementById('juridicoResumen');
  const evidEl = document.getElementById('juridicoEvidencias');
  const badge = (txt, tone='slate') =>
    `<span class="inline-block px-2 py-0.5 rounded-full text-xs bg-${tone}-600/30 text-${tone}-200 border border-${tone}-600/30">${txt}</span>`;

  try {
    const r = await fetch(endpoint, { headers: { 'Accept': 'application/json' }});
    const data = await r.json();
    if (!data.ok) {
      statusEl && (statusEl.textContent = 'error');
      resumenEl && (resumenEl.innerHTML = `<span class="text-red-300">${data.mensaje || 'No se pudo obtener la validación'}</span>`);
      return;
    }
    const rep = data.reporte || {};
    const evidencias = Array.isArray(rep.evidencias) ? rep.evidencias : [];
    const statusTone = rep.status === 'ok' ? 'green' : (rep.status === 'error' ? 'red' : 'amber');
    statusEl && (statusEl.innerHTML = badge(rep.status || 'sin_datos', statusTone));

    const clasTone = rep.clasificacion === 'alto' ? 'red' : (rep.clasificacion === 'medio' ? 'amber' : 'slate');
    const score = (rep.scoring ?? rep.score ?? rep.score_max ?? '-');
    resumenEl && (resumenEl.innerHTML = `
      <div class="flex flex-wrap gap-2 items-center">
        ${badge('clasificación: ' + (rep.clasificacion || '-'), clasTone)}
        ${badge('score: ' + score, 'indigo')}
        ${badge('evidencias: ' + evidencias.length, 'cyan')}
      </div>
    `);

    if (!evidencias.length) {
      evidEl && (evidEl.innerHTML = `<p class="text-gray-400 text-sm">Sin evidencias.</p>`);
      return;
    }
    evidEl && (evidEl.innerHTML = evidencias.map(ev => {
      const fecha = ev.fecha || '-';
      const tribunal = ev.tribunal || '-';
      const expediente = ev.expediente || '-';
      const link = ev.link ? `<a href="${ev.link}" target="_blank" rel="noopener" class="text-indigo-300 underline text-xs">ver enlace</a>` : '';
      const archivo = ev.archivo ? `<div class="text-xs text-gray-400 break-all">archivo: ${ev.archivo}</div>` : '';
      return `
        <div class="bg-white/5 rounded-lg p-3">
          <div class="flex flex-wrap gap-2 mb-2">
            ${badge(tribunal, 'purple')}
            ${badge('Exp.: ' + expediente, 'slate')}
            ${badge(fecha, 'teal')}
          </div>
          <div class="flex items-center gap-3">
            ${link}
          </div>
          ${archivo}
        </div>
      `;
    }).join(''));
  } catch (e) {
    statusEl && (statusEl.textContent = 'error');
    resumenEl && (resumenEl.innerHTML = `<span class="text-red-300">${e.message}</span>`);
  }
})();

/* =====================
 * Utilidad: cargarResumen(id) (opcional)
 * ===================== */
async function cargarResumen(id) {
  try {
    const endpoint = `${window.baseUrl}/validaciones/demandas/resumen/${id}`;
    const r = await fetch(endpoint, { headers: { 'Accept': 'application/json' }});
    const ct = r.headers.get('content-type') || '';
    if (!ct.includes('application/json')) {
      const txt = await r.text();
      throw new Error('Respuesta no-JSON:\n' + txt.slice(0, 200));
    }
    const data = await r.json();
    console.log("✅ Resumen cargado:", data);
  } catch (err) {
    console.error("❌ Error al cargar resumen:", err);
    if (window.Swal) Swal.fire("Error", err.message, "error");
    Swal.fire({ icon: 'error', title: 'Error de red', text: 'No pudimos conectar con el servidor.' });
  }
}

/* =====================
 * Auto-refresh tras ejecutar validación (sin await top-level)
 * ===================== */
let autoTimer;
function postRunAutoRefresh(){
  const start = Date.now();
  clearInterval(autoTimer);
  autoTimer = setInterval(() => {
    // Llamamos a las APIs expuestas del módulo Demandas
    window.VH_DEMANDAS?.cargarChips?.();
    window.VH_DEMANDAS?.verUltimo?.();
    if (Date.now() - start > 90_000) clearInterval(autoTimer); // 90s
  }, 6_000); // cada 6s
}

</script>
