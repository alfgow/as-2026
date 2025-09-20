<?php
declare(strict_types=1);

namespace App\Models;
require_once __DIR__ . '/../Core/Database.php';
use App\Core\Database;
use PDO;
use PDOException;

/**
 * Modelo de Blog (tabla: blog_posts)
 *
 * Columnas:
 *  - id (AI, PK)
 *  - titulo (varchar 255, NOT NULL)
 *  - contenido (longtext, NOT NULL)
 *  - categoria (varchar 100, NOT NULL)
 *  - etiquetas (varchar 255, NULL)           // CSV opcional
 *  - imagen_key (varchar 255, NULL)          // S3 key opcional
 *  - created_at (datetime, NOT NULL)
 *  - updated_at (datetime, NOT NULL)
 *  - slug (varchar 100, NULL)                 // recomendamos índice UNIQUE
 *
 * Notas:
 *  - created_at / updated_at se setean con NOW() desde SQL.
 *  - Si no se provee slug, se genera desde el título y se garantiza unicidad
 *    con sufijos -2, -3, etc. (a nivel de aplicación).
 */
class BlogModel extends Database
{
    public function __construct()
    {
        parent::__construct();
    }

    /* ============
       Lecturas
       ============ */

    /**
     * Lista todos los posts (recientes primero).
     * @return array<int, array<string,mixed>>
     */
    public function all(): array
    {
        $sql = "SELECT * FROM blog_posts ORDER BY created_at DESC";
        return $this->fetchAll($sql);
    }

    /**
     * Obtiene un post por ID.
     */
    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM blog_posts WHERE id = ? LIMIT 1";
        return $this->fetch($sql, [$id]);
    }

    /**
     * Obtiene un post por slug.
     */
    public function findBySlug(string $slug): ?array
    {
        $sql = "SELECT * FROM blog_posts WHERE slug = ? LIMIT 1";
        return $this->fetch($sql, [$slug]);
    }

    /**
     * Búsqueda con paginación (por título, categoría, etiquetas).
     * @return array<int, array<string,mixed>>
     */
    public function search(string $q, int $offset = 0, int $limit = 10): array
    {
        $sql = "SELECT *
                FROM blog_posts
                WHERE titulo   LIKE :q
                   OR categoria LIKE :q
                   OR etiquetas LIKE :q
                ORDER BY created_at DESC
                LIMIT :offset, :limit";
        $stmt = $this->db->prepare($sql);
        $like = "%{$q}%";
        $stmt->bindValue(':q', $like, PDO::PARAM_STR);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->execute();
        return (array) $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Total para la misma búsqueda de search().
     */
    public function searchCount(string $q): int
    {
        $sql = "SELECT COUNT(*) AS total
                FROM blog_posts
                WHERE titulo   LIKE :q
                   OR categoria LIKE :q
                   OR etiquetas LIKE :q";
        $stmt = $this->db->prepare($sql);
        $like = "%{$q}%";
        $stmt->bindValue(':q', $like, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($row['total'] ?? 0);
    }

    /**
     * Últimos N posts.
     * @return array<int, array<string,mixed>>
     */
    public function latest(int $limit = 5): array
    {
        $stmt = $this->db->prepare("SELECT * FROM blog_posts ORDER BY created_at DESC LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return (array) $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ============
       Escrituras
       ============ */

    /**
     * Crea un post y devuelve el ID insertado.
     * Reglas:
     *  - Si no viene slug, se genera desde el título.
     *  - Se garantiza slug único (a nivel app).
     *
     * @param array<string,mixed> $data
     */
    public function create(array $data): int
    {
        $titulo     = trim((string) ($data['titulo']     ?? ''));
        $contenido  = (string) ($data['contenido']  ?? '');
        $categoria  = trim((string) ($data['categoria']  ?? ''));
        $etiquetas  = trim((string) ($data['etiquetas']  ?? ''));    // CSV opcional
        $imagenKey  = trim((string) ($data['imagen_key'] ?? ''));
        $slugInput  = isset($data['slug']) ? trim((string)$data['slug']) : '';

        if ($titulo === '' || $contenido === '' || $categoria === '') {
            throw new PDOException("Faltan campos obligatorios: titulo, contenido, categoria.");
        }

        $slug = $this->ensureUniqueSlug($slugInput !== '' ? $slugInput : $this->slugify($titulo));

        $sql = "INSERT INTO blog_posts
                   (titulo, contenido, categoria, etiquetas, imagen_key, created_at, updated_at, slug)
                VALUES
                   (:titulo, :contenido, :categoria, :etiquetas, :imagen_key, NOW(), NOW(), :slug)";
        $this->execute($sql, [
            ':titulo'     => $titulo,
            ':contenido'  => $contenido,
            ':categoria'  => $categoria,
            ':etiquetas'  => $etiquetas,
            ':imagen_key' => $imagenKey,
            ':slug'       => $slug,
        ]);

        return (int) $this->lastInsertId();
    }

    /**
     * Actualiza un post por ID.
     * - Si cambias el título y no mandas slug, se re-genera y asegura unicidad.
     * - Si envías slug explícito, se asegura unicidad.
     *
     * @param int $id
     * @param array<string,mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        $post = $this->find($id);
        if (!$post) {
            throw new PDOException("El post #{$id} no existe.");
        }

        $titulo     = trim((string) ($data['titulo']     ?? $post['titulo']));
        $contenido  = (string) ($data['contenido']  ?? $post['contenido']);
        $categoria  = trim((string) ($data['categoria']  ?? $post['categoria']));
        $etiquetas  = trim((string) ($data['etiquetas']  ?? (string)$post['etiquetas']));
        $imagenKey  = trim((string) ($data['imagen_key'] ?? (string)$post['imagen_key']));
        $slugNew    = isset($data['slug']) ? trim((string)$data['slug']) : '';

        // lógica de slug:
        if ($slugNew !== '') {
            $slug = $this->ensureUniqueSlug($slugNew, $id);
        } elseif ($titulo !== (string)$post['titulo']) {
            $slug = $this->ensureUniqueSlug($this->slugify($titulo), $id);
        } else {
            $slug = (string) $post['slug'];
        }

        $sql = "UPDATE blog_posts
                SET titulo     = :titulo,
                    contenido  = :contenido,
                    categoria  = :categoria,
                    etiquetas  = :etiquetas,
                    imagen_key = :imagen_key,
                    slug       = :slug,
                    updated_at = NOW()
                WHERE id = :id";

        return $this->execute($sql, [
            ':titulo'     => $titulo,
            ':contenido'  => $contenido,
            ':categoria'  => $categoria,
            ':etiquetas'  => $etiquetas,
            ':imagen_key' => $imagenKey,
            ':slug'       => $slug,
            ':id'         => $id,
        ]) > 0;
    }

    /**
     * Elimina un post por ID.
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM blog_posts WHERE id = ?";
        return $this->execute($sql, [$id]) > 0;
    }

    /* ====================
       Helpers de Slug
       ==================== */

    /**
     * Genera un slug básico a partir de un texto.
     */
    public function slugify(string $texto): string
    {
        $texto = trim($texto);
        $reemplazos = [
            'ñ' => 'n', 'Ñ' => 'n',
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u',
            'ü' => 'u', 'Ü' => 'u',
        ];
        $texto = strtr($texto, $reemplazos);
        $texto = strtolower($texto);
        $texto = preg_replace('/[^a-z0-9]+/u', '-', $texto) ?? '';
        $texto = trim($texto, '-');
        return $texto;
    }

    /**
     * Garantiza que el slug sea único (agrega -2, -3, ... si existe).
     * Si $excludeId está presente, ignora ese ID (para updates).
     */
    public function ensureUniqueSlug(string $baseSlug, ?int $excludeId = null): string
    {
        $slug     = $baseSlug !== '' ? $baseSlug : 'post';
        $sufijo   = 1;

        while ($this->slugExists($slug, $excludeId)) {
            $sufijo++;
            $slug = $baseSlug . '-' . $sufijo;
        }
        return $slug;
    }

    /**
     * ¿Existe un post con este slug? (opcionalmente excluye un ID).
     */
    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM blog_posts WHERE slug = :slug";
        $params = [':slug' => $slug];

        if ($excludeId !== null) {
            $sql .= " AND id <> :id";
            $params[':id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return ((int) $stmt->fetchColumn()) > 0;
    }
}