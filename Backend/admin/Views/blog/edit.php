<!-- admin/Views/blog/edit.php -->
<?php
require_once __DIR__ . '/../../Helpers/S3Helper.php';
use App\Helpers\S3Helper;
$s3 = new S3Helper('blog');
?>

<div class="max-w-3xl mx-auto py-10 px-4">
  <h1 class="text-3xl font-bold text-indigo-200 mb-3">Editar Entrada de Blog</h1>
  <p class="mb-7 text-indigo-400">Modifica el contenido, imagen o etiquetas SEO de este artículo.</p>

  <?php $postId = (string)($post['id'] ?? ''); ?>
  <form method="POST" action="<?= admin_url('blog/update') ?>" enctype="multipart/form-data"
    class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-2xl p-8 border border-indigo-900/20 space-y-7">

    <input type="hidden" name="id" value="<?= htmlspecialchars($postId, ENT_QUOTES, 'UTF-8') ?>">

    <!-- Título -->
    <div>
      <label for="title" class="block font-bold text-indigo-300 mb-2">Título del blog *</label>
      <input type="text" id="title" name="title" required maxlength="140"
        value="<?= htmlspecialchars($post['titulo'] ?? '') ?>"
        class="w-full rounded-lg px-4 py-3 bg-[#232336] text-indigo-100 border border-indigo-800 placeholder-indigo-400 shadow focus:ring-indigo-600 focus:border-indigo-600 transition"
        placeholder="Ejemplo: ¿Por qué contratar una póliza jurídica de arrendamiento?">
    </div>

    <!-- Categoría -->
    <div>
      <label for="category" class="block font-bold text-indigo-300 mb-2">Categoría *</label>
      <select id="category" name="category" required
        class="w-full rounded-lg px-4 py-3 bg-[#232336] text-indigo-100 border border-indigo-800 shadow focus:ring-indigo-600 focus:border-indigo-600 transition">
        <option value="" disabled>Selecciona una categoría</option>
        <?php
          $categories = ['Consejos', 'Legal', 'Inquilinos', 'Dueños', 'Noticias'];
          foreach ($categories as $cat):
        ?>
          <option value="<?= $cat ?>" <?= ($post['categoria'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Etiquetas SEO -->
    <div>
      <label for="tags" class="block font-bold text-indigo-300 mb-2">Etiquetas SEO</label>
      <input type="text" id="tags" name="tags"
        value="<?= htmlspecialchars($post['etiquetas'] ?? '') ?>"
        class="w-full rounded-lg px-4 py-3 bg-[#232336] text-indigo-100 border border-indigo-800 shadow placeholder-indigo-400 focus:ring-indigo-600 focus:border-indigo-600 transition"
        placeholder="Ejemplo: póliza jurídica, renta segura, arrendamiento">
      <p class="text-xs text-indigo-400 mt-1">Separa las etiquetas con comas.</p>
    </div>

    <!-- Imagen principal -->
    <div>
      <label class="block font-bold text-indigo-300 mb-2">Imagen principal</label>
      <?php if (!empty($post['imagen_key'])): ?>
                <?php $url = $s3->getPresignedUrl($post['imagen_key'], '+5 minutes'); ?>
                <img src="<?= htmlspecialchars($url) ?>" alt="Imagen" class="w-full h-auto">
            <?php else: ?>
                <div class="w-full h-48 bg-gray-700 flex items-center justify-center text-gray-400 italic">
                    Sin imagen
                </div>
            <?php endif; ?>
            
      <div id="dropzone" class="flex items-center justify-center bg-[#232336] border-2 border-dashed border-indigo-700 rounded-xl py-7 px-3 mb-2 mt-3 text-indigo-300 cursor-pointer hover:border-indigo-400 transition relative">
        <span id="dropzone-text" class="text-sm">
          Arrastra una imagen aquí o <span class="underline text-indigo-400">haz clic para seleccionar</span>
        </span>
        <input type="file" id="image" name="image" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer">
      </div>
      <img id="image-preview"
        src="<?= htmlspecialchars($post['image_url'] ?? '') ?>"
        class="<?= !empty($post['image_url']) ? '' : 'hidden' ?> rounded-xl mt-2 max-h-36 mx-auto"
        alt="Vista previa imagen">
      <p class="text-xs text-indigo-400 mt-1">Puedes mantener la imagen actual o subir una nueva.</p>
    </div>

    <!-- Contenido enriquecido -->
    <div>
      <label for="contenido" class="block font-bold text-indigo-300 mb-2">Contenido *</label>
      <textarea id="contenido" name="contenido" required rows="9"
        class="w-full rounded-lg px-4 py-3 bg-[#232336] text-indigo-100 border border-indigo-800 shadow placeholder-indigo-400 focus:ring-indigo-600 focus:border-indigo-600 transition resize-y"
        placeholder="Contenido del blog"><?= htmlspecialchars($post['contenido'] ?? '') ?></textarea>
      <p class="text-xs text-indigo-400 mt-1">Puedes usar formato HTML básico.</p>
    </div>

    <div class="flex justify-between gap-4">
      <a href="<?= admin_url('blog') ?>" class="inline-block px-6 py-3 rounded-xl bg-indigo-800 hover:bg-indigo-700 text-white font-bold shadow transition text-base">Cancelar</a>
      <button type="submit"
        class="px-8 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold shadow-lg transition text-base">
        Guardar cambios
      </button>
    </div>
  </form>
</div>

<script>
  // Drag & drop image preview (mismo código que en create)
  const dropzone = document.getElementById('dropzone');
  const fileInput = document.getElementById('image');
  const imagePreview = document.getElementById('image-preview');

  dropzone.addEventListener('click', () => fileInput.click());
  dropzone.addEventListener('dragover', e => {
    e.preventDefault();
    dropzone.classList.add('border-indigo-400');
  });
  dropzone.addEventListener('dragleave', e => {
    e.preventDefault();
    dropzone.classList.remove('border-indigo-400');
  });
  dropzone.addEventListener('drop', e => {
    e.preventDefault();
    dropzone.classList.remove('border-indigo-400');
    const files = e.dataTransfer.files;
    if (files.length) {
      fileInput.files = files;
      showPreview(files[0]);
    }
  });
  fileInput.addEventListener('change', () => {
    if (fileInput.files.length) showPreview(fileInput.files[0]);
  });
  function showPreview(file) {
    const reader = new FileReader();
    reader.onload = e => {
      imagePreview.src = e.target.result;
      imagePreview.classList.remove('hidden');
    };
    reader.readAsDataURL(file);
  }
</script>
