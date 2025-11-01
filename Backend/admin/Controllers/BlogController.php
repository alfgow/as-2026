<?php
namespace App\Controllers;

require_once __DIR__ . '/../Models/BlogModel.php';
require_once __DIR__ . '/../Helpers/S3Helper.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';

use App\Models\BlogModel;
use App\Helpers\S3Helper;
use App\Middleware\AuthMiddleware;

AuthMiddleware::verificarSesion();

/**
 * Controlador para la gestión del Blog
 */
class BlogController
{
    /**
     * Lista de posts
     */
    public function index()
    {
        $blogModel   = new BlogModel();
        $posts       = $blogModel->all();
        $title       = 'Blog - AS';
        $headerTitle = 'Blog Arrendamiento Seguro';
        $contentView = __DIR__ . '/../Views/blog/index.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Formulario de creación de post
     */
    public function create()
    {
        $title       = 'Nuevo Post';
        $headerTitle = 'Nuevo Blog';
        $contentView = __DIR__ . '/../Views/blog/create.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Almacena un nuevo post
     */
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->jsonResponse(false, 'Método no permitido.');
        }

        $titulo    = trim($_POST['title'] ?? '');
        $contenido = trim($_POST['contenido'] ?? '');
        $etiquetas = trim($_POST['tags'] ?? '');
        $categoria = trim($_POST['category'] ?? '');
        $imagen    = $_FILES['image'] ?? null;
        $imagen_key = '';

        if ($titulo === '' || $contenido === '') {
            return $this->jsonResponse(false, 'El título y contenido son obligatorios.');
        }

        $slug = $this->slugify($titulo);

        // Subir imagen principal a S3
        if ($imagen && $imagen['error'] === UPLOAD_ERR_OK) {
            $s3         = new S3Helper('blog');
            $imagen_key = $s3->uploadImage($imagen, 'blog');
        }

        $blogModel = new BlogModel();
        $exito = $blogModel->create([
            'titulo'     => $titulo,
            'contenido'  => $contenido,
            'etiquetas'  => $etiquetas,
            'categoria'  => $categoria,
            'imagen_key' => $imagen_key,
            'slug'       => $slug,
        ]);

        return $this->jsonResponse(
            (bool)$exito,
            $exito ? 'Entrada de blog creada correctamente.' : 'No se pudo crear la entrada de blog.'
        );
    }

    /**
     * Formulario de edición
     */
    public function edit()
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: ' . admin_base_url('blog'));
            exit;
        }

        $blogModel = new BlogModel();
        $post      = $blogModel->find($id);
        if (!$post) {
            header('Location: ' . admin_base_url('blog'));
            exit;
        }

        $title       = 'Editar Post';
        $headerTitle = 'Editar Entrada';
        $contentView = __DIR__ . '/../Views/blog/edit.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Actualiza un post existente
     */
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . admin_base_url('blog'));
            exit;
        }

        $id        = (int) ($_POST['id'] ?? 0);
        $titulo    = trim($_POST['title'] ?? '');
        $contenido = trim($_POST['contenido'] ?? '');
        $etiquetas = trim($_POST['tags'] ?? '');
        $categoria = trim($_POST['category'] ?? '');
        $imagen    = $_FILES['image'] ?? null;

        if ($id <= 0 || $titulo === '' || $contenido === '') {
            header('Location: ' . admin_base_url('blog'));
            exit;
        }

        $blogModel = new BlogModel();
        $post      = $blogModel->find($id);
        if (!$post) {
            header('Location: ' . admin_base_url('blog'));
            exit;
        }

        $imagen_key = $post['imagen_key'];
        if ($imagen && $imagen['error'] === UPLOAD_ERR_OK) {
            $s3 = new S3Helper('blog');
            $imagen_key = $s3->uploadImage($imagen, 'blog');
        }

        $blogModel->update($id, [
            'titulo'     => $titulo,
            'contenido'  => $contenido,
            'etiquetas'  => $etiquetas,
            'categoria'  => $categoria,
            'imagen_key' => $imagen_key,
        ]);

        header('Location: ' . admin_base_url('blog'));
        exit;
    }

    /**
     * Elimina un post
     */
    public function delete()
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $id = 0;

        if ($method === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
        } else {
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        }

        if ($id <= 0) {
            if ($method === 'POST') {
                return $this->jsonResponse(false, 'Identificador de entrada inválido.');
            }

            header('Location: ' . admin_base_url('blog'));
            exit;
        }

        $blogModel = new BlogModel();
        $post      = $blogModel->find($id);

        if (! $post) {
            if ($method === 'POST') {
                return $this->jsonResponse(false, 'La entrada especificada no existe.');
            }

            header('Location: ' . admin_base_url('blog'));
            exit;
        }

        $blogModel->delete($id);

        if ($method === 'POST') {
            return $this->jsonResponse(true, 'Entrada eliminada correctamente.');
        }

        header('Location: ' . admin_base_url('blog'));
        exit;
    }

    /* ================= Métodos auxiliares ================= */

    private function slugify(string $string): string
    {
        $string = mb_strtolower(trim($string), 'UTF-8');
        $unwanted = [
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u',
            'ä'=>'a','ë'=>'e','ï'=>'i','ö'=>'o','ü'=>'u',
            'Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u',
            'ñ'=>'n','Ñ'=>'n','ç'=>'c','¿'=>'','¡'=>''
        ];
        $string = strtr($string, $unwanted);
        $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
        $string = preg_replace('/[\s-]+/', '-', $string);
        return trim($string, '-');
    }

    private function jsonResponse(bool $success, string $message)
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    }
}
