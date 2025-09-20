<?php
require_once __DIR__ . '/../../Helpers/S3Helper.php';
use App\Helpers\S3Helper;
$s3 = new S3Helper('blog');
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Entradas del Blog</h1>
    <a href="<?= admin_url('blog/create') ?>"
       class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded shadow">
        + Nueva Entrada
    </a>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($posts as $post): ?>
        <div class="bg-gray-800 rounded-2xl shadow-lg flex flex-col overflow-hidden">
            <?php if (!empty($post['imagen_key'])): ?>
                <?php $url = $s3->getPresignedUrl($post['imagen_key'], '+5 minutes'); ?>
                <img src="<?= htmlspecialchars($url) ?>" alt="Imagen" class="w-full h-auto">
            <?php else: ?>
                <div class="w-full h-48 bg-gray-700 flex items-center justify-center text-gray-400 italic">
                    Sin imagen
                </div>
            <?php endif; ?>
            <div class="flex-1 flex flex-col p-4">
                <div class="flex justify-between items-center mb-2">
                    <span class="bg-indigo-700 text-xs rounded-full px-3 py-1 font-semibold"><?= htmlspecialchars($post['categoria']) ?></span>
                    <span class="text-xs text-gray-400"><?= date("d/m/Y", strtotime($post['created_at'])) ?></span>
                </div>
                <h2 class="text-lg font-bold text-white mb-2 truncate"><?= htmlspecialchars($post['titulo']) ?></h2>
                <p class="text-gray-300 text-sm mb-3">
                    <?= mb_strimwidth(strip_tags($post['contenido']), 0, 110, "...") ?>
                </p>
                <div class="mb-3 flex flex-wrap gap-1">
                    <?php
                    if ($post['etiquetas']) {
                        foreach (explode(',', $post['etiquetas']) as $tag) {
                            echo '<span class="inline-block bg-indigo-900 text-indigo-100 text-xs rounded px-2 py-0.5">' . htmlspecialchars(trim($tag)) . '</span>';
                        }
                    }
                    ?>
                </div>
                <div class="mt-auto flex gap-2">
                    <?php $postId = (string)($post['id'] ?? ''); ?>
                    <a href="<?= admin_url('blog/edit') . '?id=' . urlencode($postId) ?>"
                       class="flex-1 text-center py-1.5 bg-indigo-600 hover:bg-indigo-700 rounded text-white text-sm transition">Editar</a>
                    <!-- Si quieres eliminar, añade aquí -->
                    <!--
                    <button class="flex-1 text-center py-1.5 bg-red-600 hover:bg-red-700 rounded text-white text-sm transition"
                        onclick="eliminarBlog(<?= $post['id'] ?>)">Eliminar</button>
                    -->
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Si quieres Eliminar, añade el siguiente script: -->
<!--
<script>
function eliminarBlog(id) {
    Swal.fire({
        title: '¿Eliminar entrada?',
        text: "Esta acción no se puede deshacer.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e53e3e',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Puedes hacer un fetch POST aquí para eliminar el blog por id
            // Y luego refrescar la página
        }
    });
}
</script>
-->
