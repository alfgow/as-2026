<?php
$adminIdentityBase = '/';
if (function_exists('admin_url')) {
    $adminIdentityBase = admin_url();
} elseif (function_exists('admin_base_url')) {
    $adminIdentityBase = admin_base_url();
}

$adminIdentityBase = rtrim((string) $adminIdentityBase, '/');
if ($adminIdentityBase === '') {
    $adminIdentityBase = '/';
}
?>
<section class="relative min-h-screen flex items-center justify-center bg-white/5 backdrop-blur-md border border-white/20 rounded-2xl shadow-[0_8px_32px_0_rgba(31,38,135,0.37)] py-8 md:py-16 px-2">
    <input type="hidden" id="baseUrl" value="<?= htmlspecialchars($adminIdentityBase, ENT_QUOTES, 'UTF-8') ?>">
  <!-- Fondo visual animado -->
  <div class="absolute inset-0 -z-10">
    <div class="absolute left-0 top-1/2 -translate-y-1/2 w-48 h-48 bg-indigo-900/30 blur-3xl rounded-full"></div>
    <div class="absolute right-0 bottom-0 w-32 h-32 bg-indigo-500/30 blur-2xl rounded-full"></div>
  </div>

  <!-- Card principal -->
  <div class="relative w-full max-w-4xl lg:max-w-7xl mx-auto rounded-3xl bg-gradient-to-br from-white/10 to-indigo-900/60 shadow-2xl backdrop-blur-lg px-8 py-10 flex flex-col items-center gap-8 border border-indigo-700/10">
    
    <!-- Selfie principal -->
    <div class="relative -mt-24 mb-2">
      <div class="w-32 h-32 rounded-full ring-8 ring-indigo-700/40 shadow-lg bg-gray-800 overflow-hidden border-4 border-white/10">
        <img src="<?= htmlspecialchars($prospecto['selfie_url'] ?? $url_selfie ?? 'https://arrendamientoseguro.app/img_selfie_demo.png') ?>"
          alt="Selfie"
          class="w-full h-full object-cover">
      </div>
    </div>
    
    <!-- Nombre y email -->
    <div class="text-center">
      <h2 class="text-2xl sm:text-3xl font-extrabold text-white drop-shadow-sm">
        <?= ucwords($prospecto['nombre_inquilino'] . ' ' . $prospecto['apellidop_inquilino'] . ' ' . $prospecto['apellidom_inquilino']) ?>
      </h2>
      <div class="text-indigo-300 font-medium mt-1 tracking-wide text-base"><?= htmlspecialchars($prospecto['email']) ?></div>
    </div>
    
    <!-- Sección de Documentos -->
    <div class="flex flex-col md:flex-row gap-8 w-full justify-center items-stretch">
      <!-- Documento: INE Frente -->
      <div class="bg-white/10 hover:bg-indigo-900/30 transition rounded-2xl px-4 py-5 shadow-lg flex flex-col items-center w-full max-w-xs">
        <span class="text-indigo-100 font-semibold text-lg mb-2">Frente INE</span>
        <div id="view-ineFrente" class="flex flex-col items-center">
          <img
            id="staticFront"
            src="<?= htmlspecialchars($url_frontal ?? 'https://arrendamientoseguro.app/img/ine_frente_demo.png') ?>"
            alt="INE Frente"
            class="w-52 h-36 object-cover rounded-xl border-4 border-indigo-400/40 shadow-md cursor-pointer"
            onclick="abrirModalImagen(this.src, 'INE Frente')" />
          <button type="button" data-target="ineFrente" class="btn-edit-photo mt-2 text-sm px-3 py-1 rounded-full bg-indigo-600 text-white">Editar foto</button>
        </div>
        <div id="dropzone-ineFrente" class="hidden dropzone-casero flex flex-col items-center justify-center border-2 border-dashed border-indigo-400 bg-white/10 rounded-xl py-4 px-4 cursor-pointer transition hover:bg-indigo-900/20 shadow w-full max-w-xs mt-2">
          <img
            id="previewFront"
            src="<?= htmlspecialchars($url_frontal ?? 'https://arrendamientoseguro.app/img/ine_frente_demo.png') ?>"
            alt="INE Frente"
            class="w-52 h-36 object-cover rounded-xl border-4 border-indigo-400/40 shadow-md cursor-pointer"
            onclick="abrirModalImagen(this.src, 'INE Frente')" />
          <p class="dz-text text-indigo-200 font-semibold mt-2">Arrastra una imagen o haz clic aquí</p>
          <button type="button" class="dz-cancel hidden mt-2 text-sm text-red-500">Cancelar</button>
          <input type="file" name="ineFrente" id="ineFrente" accept="image/*" class="hidden">
        </div>
      </div>

      <!-- Documento: INE Reverso -->
      <div class="bg-white/10 hover:bg-indigo-900/30 transition rounded-2xl px-4 py-5 shadow-lg flex flex-col items-center w-full max-w-xs">
        <span class="text-indigo-100 font-semibold text-lg mb-2">Reverso INE</span>
        <div id="view-ineReverso" class="flex flex-col items-center">
          <img
            id="staticBack"
            src="<?= htmlspecialchars($url_reverso ?? 'https://arrendamientoseguro.app/img/ine_reverso_demo.png') ?>"
            alt="INE Reverso"
            class="w-52 h-36 object-cover rounded-xl border-4 border-indigo-400/40 shadow-md cursor-pointer"
            onclick="abrirModalImagen(this.src, 'INE Reverso')" />
          <button type="button" data-target="ineReverso" class="btn-edit-photo mt-2 text-sm px-3 py-1 rounded-full bg-indigo-600 text-white">Editar foto</button>
        </div>
        <div id="dropzone-ineReverso" class="hidden dropzone-casero flex flex-col items-center justify-center border-2 border-dashed border-indigo-400 bg-white/10 rounded-xl py-4 px-4 cursor-pointer transition hover:bg-indigo-900/20 shadow w-full max-w-xs mt-2">
          <img
            id="previewBack"
            src="<?= htmlspecialchars($url_reverso ?? 'https://arrendamientoseguro.app/img/ine_reverso_demo.png') ?>"
            alt="INE Reverso"
            class="w-52 h-36 object-cover rounded-xl border-4 border-indigo-400/40 shadow-md cursor-pointer"
            onclick="abrirModalImagen(this.src, 'INE Reverso')" />
          <p class="dz-text text-indigo-200 font-semibold mt-2">Arrastra una imagen o haz clic aquí</p>
          <button type="button" class="dz-cancel hidden mt-2 text-sm text-red-500">Cancelar</button>
          <input type="file" name="ineReverso" id="ineReverso" accept="image/*" class="hidden">
        </div>
      </div>

      <!-- Documento: Selfie -->
      <div class="bg-white/10 hover:bg-indigo-900/30 transition rounded-2xl px-4 py-5 shadow-lg flex flex-col items-center w-full max-w-xs">
        <span class="text-indigo-100 font-semibold text-lg mb-2">Selfie</span>
        <div id="view-selfie" class="flex flex-col items-center">
          <img
            id="staticSelfie"
            src="<?= htmlspecialchars($url_selfie ?? 'https://arrendamientoseguro.app/img_selfie_demo.png') ?>"
            alt="Selfie"
            class="w-36 h-36 object-cover rounded-full border-4 border-indigo-400/40 shadow-md cursor-pointer"
            onclick="abrirModalImagen(this.src, 'Selfie')" />
          <button type="button" data-target="selfie" class="btn-edit-photo mt-2 text-sm px-3 py-1 rounded-full bg-indigo-600 text-white">Editar foto</button>
        </div>
        <div id="dropzone-selfie" class="hidden dropzone-casero flex flex-col items-center justify-center border-2 border-dashed border-indigo-400 bg-white/10 rounded-xl py-4 px-4 cursor-pointer transition hover:bg-indigo-900/20 shadow w-full max-w-xs mt-2">
          <img
            id="previewSelfie"
            src="<?= htmlspecialchars($url_selfie ?? 'https://arrendamientoseguro.app/img_selfie_demo.png') ?>"
            alt="Selfie"
            class="w-36 h-36 object-cover rounded-full border-4 border-indigo-400/40 shadow-md cursor-pointer"
            onclick="abrirModalImagen(this.src, 'Selfie')" />
          <p class="dz-text text-indigo-200 font-semibold mt-2">Arrastra una imagen o haz clic aquí</p>
          <button type="button" class="dz-cancel hidden mt-2 text-sm text-red-500">Cancelar</button>
          <input type="file" name="selfie" id="selfie" accept="image/*" class="hidden">
        </div>
      </div>
    </div>
    
    <!-- Botón de validar -->
    <div class="w-full flex justify-center mt-2">
      <button id="btn-validar-identidad"
        class="flex items-center gap-3 px-10 py-4 bg-indigo-700 hover:bg-indigo-600 text-white text-xl font-extrabold rounded-full shadow-2xl ring-2 ring-indigo-300/30 hover:ring-indigo-400/40 transition-all duration-150 focus:outline-none focus:ring-4 focus:ring-indigo-400/40">
        <svg class="w-7 h-7 animate-pulse" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0A9 9 0 11 3 12a9 9 0 0118 0z"/>
        </svg>
        Validar identidad
      </button>
    </div>


    <!-- Modal de imagen fullscreen (¡solo manipúlalo con JS, nunca desde los resultados!) -->
    <div id="modalImagen" class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 hidden">
      <div class="relative max-w-3xl w-full flex flex-col items-center">
        <button id="boton-validar" type="button" onclick="cerrarModalImagen()" class="absolute top-2 right-2 bg-white/20 hover:bg-white/40 text-white rounded-full p-2 z-50 transition">
          <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
        <img id="imagenGrande" src="" alt="Documento" class="rounded-xl shadow-2xl max-h-[80vh] w-auto bg-white object-contain" />
        <div id="modalTitulo" class="text-white text-lg font-bold mt-4"></div>
      </div>
    </div>

    <!-- Resultados de validación -->
    <div id="resultadosValidacion" class="mt-6 hidden"></div>
  </div>

<!-- Loader oculto inicialmente -->
<div id="loaderValidacion" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
	<div class="bg-white p-6 rounded-xl shadow-lg flex items-center gap-4">
		<svg class="animate-spin h-6 w-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
			<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
			<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
		</svg>
		<span class="text-indigo-700 font-medium">Validando identidad...</span>
	</div>
</div>

</section>


<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= asset_url('validacionIdentidad.js') ?>"></script>
