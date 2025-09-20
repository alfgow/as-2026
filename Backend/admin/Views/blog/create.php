<!-- admin/Views/blog/create.php -->
<div class="max-w-3xl mx-auto py-10 px-4">
  <h1 class="text-3xl font-bold text-indigo-200 mb-3">Nueva Entrada de Blog</h1>
  <p class="mb-7 text-indigo-400">Publica artículos con imágenes, categorías y contenido enriquecido. Optimiza para SEO.</p>

  <form method="POST" action="<?= admin_url('blog/store') ?>" enctype="multipart/form-data" class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-2xl p-8 border border-indigo-900/20 space-y-7" id="blogForm">

    <!-- Título -->
    <div>
      <label for="title" class="block font-bold text-indigo-300 mb-2">Título del blog *</label>
      <input type="text" id="title" name="title" required maxlength="140"
        class="w-full rounded-lg px-4 py-3 bg-[#232336] text-indigo-100 border border-indigo-800 placeholder-indigo-400 shadow focus:ring-indigo-600 focus:border-indigo-600 transition"
        placeholder="Ejemplo: ¿Por qué contratar una póliza jurídica de arrendamiento?">
    </div>

    <!-- Categoría -->
    <div>
      <label for="category" class="block font-bold text-indigo-300 mb-2">Categoría *</label>
      <select id="category" name="category" required
        class="w-full rounded-lg px-4 py-3 bg-[#232336] text-indigo-100 border border-indigo-800 shadow focus:ring-indigo-600 focus:border-indigo-600 transition">
        <option value="" disabled selected>Selecciona una categoría</option>
        <option value="Consejos">Consejos</option>
        <option value="Legal">Legal</option>
        <option value="Inquilinos">Inquilinos</option>
        <option value="Dueños">Dueños</option>
        <option value="Noticias">Noticias</option>
      </select>
    </div>

    <!-- Etiquetas SEO -->
    <div>
      <label for="tags" class="block font-bold text-indigo-300 mb-2">Etiquetas SEO</label>
      <input type="text" id="tags" name="tags"
        class="w-full rounded-lg px-4 py-3 bg-[#232336] text-indigo-100 border border-indigo-800 shadow placeholder-indigo-400 focus:ring-indigo-600 focus:border-indigo-600 transition"
        placeholder="Ejemplo: póliza jurídica, renta segura, arrendamiento">
      <p class="text-xs text-indigo-400 mt-1">Separa las etiquetas con comas.</p>
    </div>

    <!-- Imágenes (drag & drop) -->
<div>
  <label class="block font-bold text-indigo-300 mb-2">Imagen principal *</label>
  <div id="dropzone" class="relative flex items-center justify-center bg-[#232336] border-2 border-dashed border-indigo-700 rounded-xl py-7 px-3 mb-2 text-indigo-300 cursor-pointer hover:border-indigo-400 transition">
    <span id="dropzone-text" class="text-sm z-10">
      Arrastra una imagen aquí o <span class="underline text-indigo-400">haz clic para seleccionar</span>
    </span>
    <input type="file" id="image" name="image" accept="image/*" required
           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" style="z-index:20;">
  </div>
  <img id="image-preview" src="" class="hidden rounded-xl mt-2 max-h-36 mx-auto" alt="Vista previa imagen">
  <p class="text-xs text-indigo-400 mt-1">Tamaño recomendado: 1200x630 px. Formato JPG o PNG.</p>
</div>


    <!-- Contenido enriquecido -->
    <div>
      <label for="content" class="block font-bold text-indigo-300 mb-2">Contenido *</label>
      <textarea id="contenido" name="contenido" class="w-full rounded-xl border bg-[#23243a] p-3 min-h-[220px] text-gray-100" ></textarea>
      <p class="text-xs text-indigo-400 mt-1">Puedes usar formato HTML básico.</p>
    </div>

    <div class="flex justify-end">
      <button type="submit"
        class="px-8 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold shadow-lg transition text-base">
        Publicar blog
      </button>
    </div>
  </form>
</div>
<!-- TinyMCE CDN con tu API Key -->
<script src="https://cdn.tiny.cloud/1/g62vvex6kxlmgwndrpkcix8mabqfyep57qhfn3jwnwxg1iuf/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
<script src="<?= asset_url('blog.js') ?>"></script>
