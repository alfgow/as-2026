<section class="relative min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-950 to-indigo-950 py-16 px-2">
  <!-- Fondo visual animado -->
  <div class="absolute inset-0 -z-10">
    <div class="absolute left-0 top-1/2 -translate-y-1/2 w-48 h-48 bg-indigo-900/30 blur-3xl rounded-full"></div>
    <div class="absolute right-0 bottom-0 w-32 h-32 bg-indigo-500/30 blur-2xl rounded-full"></div>
  </div>

  <!-- Card principal -->
  <div class="relative w-full max-w-md mx-auto rounded-3xl bg-gradient-to-br from-white/10 to-indigo-900/60 shadow-2xl backdrop-blur-lg px-8 py-10 flex flex-col items-center gap-8 border border-indigo-700/10">
    
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
    
    <!-- Documentos tipo "chips" -->
    <div class="flex flex-col gap-5 w-full">
      <!-- INE Frente -->
      <div class="flex items-center gap-4 bg-white/10 hover:bg-indigo-900/40 transition rounded-xl px-4 py-3 shadow group">
        <img id="previewFront"
          src="<?= htmlspecialchars($url_frontal ?? 'https://arrendamientoseguro.app/img/ine_frente_demo.png') ?>"
          alt="INE Frente"
          class="w-16 h-10 object-cover rounded-lg border-2 border-indigo-500/20 shadow-inner group-hover:scale-105 transition">
        <div class="flex-1">
          <span class="text-indigo-100 font-semibold text-sm">Frente INE</span>
        </div>
        <label for="ineFrente" class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full font-semibold text-xs shadow transition cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M12 4v16m8-8H4"/>
          </svg>
          Cambiar
          <input type="file" name="ineFrente" id="ineFrente" accept="image/*" class="hidden">
        </label>
      </div>
      
      <!-- INE Reverso -->
      <div class="flex items-center gap-4 bg-white/10 hover:bg-indigo-900/40 transition rounded-xl px-4 py-3 shadow group">
        <img id="previewBack"
          src="<?= htmlspecialchars($url_reverso ?? 'https://arrendamientoseguro.app/img/ine_reverso_demo.png') ?>"
          alt="INE Reverso"
          class="w-16 h-10 object-cover rounded-lg border-2 border-indigo-500/20 shadow-inner group-hover:scale-105 transition">
        <div class="flex-1">
          <span class="text-indigo-100 font-semibold text-sm">Reverso INE</span>
        </div>
        <label for="ineReverso" class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full font-semibold text-xs shadow transition cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M12 4v16m8-8H4"/>
          </svg>
          Cambiar
          <input type="file" name="ineReverso" id="ineReverso" accept="image/*" class="hidden">
        </label>
      </div>
      
      <!-- Selfie chip extra (opcional, sólo si quieres dejar doble, puedes quitar esto) -->

      <div class="flex items-center gap-4 bg-white/10 hover:bg-indigo-900/40 transition rounded-xl px-4 py-3 shadow group">
        <img id="previewSelfie"
          src="<?= htmlspecialchars($url_selfie ?? 'https://arrendamientoseguro.app/img_selfie_demo.png') ?>"
          alt="Selfie"
          class="w-12 h-12 object-cover rounded-full border-2 border-indigo-500/20 shadow-inner group-hover:scale-105 transition">
        <div class="flex-1">
          <span class="text-indigo-100 font-semibold text-sm">Selfie</span>
        </div>
        <label for="selfie" class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full font-semibold text-xs shadow transition cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M12 4v16m8-8H4"/>
          </svg>
          Cambiar
          <input type="file" name="selfie" id="selfie" accept="image/*" class="hidden">
        </label>
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

    <!-- Resultados de validación -->
    <div id="resultadosValidacion" class="mt-6 hidden"></div>
  </div>
</section>

<script>


</script>