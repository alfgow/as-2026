<?php
declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . '/../Core/Database.php';

use App\Core\Database;
use PDO;
use Exception;

class ValidacionAwsController extends Database
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * POST /ia/validar/{slug}
     * - Localiza inquilino por slug
     * - Lee archivos asociados (selfie, INE, pasaporte, comprobantes)
     * - Asegura un registro en inquilinos_validaciones y deja una bitácora
     * - (Aún SIN llamadas a AWS; eso será el siguiente paso)
     */
    public function validar(string $slug): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Método no permitido. Usa POST.'
            ]);
            return;
        }

        try {
            $this->db->beginTransaction();

            // 1) Inquilino por slug
            $stmt = $this->db->prepare("
                SELECT id, slug, nombre_inquilino, apellidop_inquilino, apellidom_inquilino, tipo_id
                FROM inquilinos
                WHERE slug = ?
                LIMIT 1
            ");
            $stmt->execute([$slug]);
            $inquilino = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$inquilino) {
                $this->db->rollBack();
                http_response_code(404);
                echo json_encode(['ok' => false, 'mensaje' => 'Inquilino no encontrado por slug.']);
                return;
            }

            $idInquilino = (int)$inquilino['id'];

            // 2) Archivos del inquilino
            $stmt2 = $this->db->prepare("
                SELECT id, tipo, s3_key, mime_type, created_at
                FROM inquilinos_archivos
                WHERE id_inquilino = ?
                ORDER BY created_at ASC, id ASC
            ");
            $stmt2->execute([$idInquilino]);
            $archivos = $stmt2->fetchAll(PDO::FETCH_ASSOC);

            // Resumen rápido de lo que hay
            $flags = [
                'selfie'            => false,
                'ine_frontal'       => false,
                'ine_reverso'       => false,
                'pasaporte'         => false,
                'forma_migratoria'  => false,
                'comprobantes'      => 0,
            ];

            foreach ($archivos as $a) {
                switch ($a['tipo']) {
                    case 'selfie':               $flags['selfie'] = true; break;
                    case 'ine_frontal':          $flags['ine_frontal'] = true; break;
                    case 'ine_reverso':          $flags['ine_reverso'] = true; break;
                    case 'pasaporte':            $flags['pasaporte'] = true; break;
                    case 'forma_migratoria':     $flags['forma_migratoria'] = true; break;
                    case 'comprobante_ingreso':  $flags['comprobantes']++; break;
                }
            }

            // 3) Aseguramos registro en validaciones (si no existe, insert con defaults)
            $stmt3 = $this->db->prepare("SELECT id FROM inquilinos_validaciones WHERE id_inquilino = ? LIMIT 1");
            $stmt3->execute([$idInquilino]);
            $valRow = $stmt3->fetch(PDO::FETCH_ASSOC);

            $comentario = sprintf(
                "[%s] Validación iniciada manualmente (mock). Archivos: selfie=%s, INE(F)=%s, INE(R)=%s, pasaporte=%s, FM=%s, comprobantes=%d",
                date('Y-m-d H:i:s'),
                $flags['selfie'] ? 'sí' : 'no',
                $flags['ine_frontal'] ? 'sí' : 'no',
                $flags['ine_reverso'] ? 'sí' : 'no',
                $flags['pasaporte'] ? 'sí' : 'no',
                $flags['forma_migratoria'] ? 'sí' : 'no',
                $flags['comprobantes']
            );

            if ($valRow && isset($valRow['id'])) {
                $stmt4 = $this->db->prepare("
                    UPDATE inquilinos_validaciones
                    SET comentarios = CONCAT(COALESCE(comentarios,''), '\n', :comentario)
                    WHERE id_inquilino = :id
                ");
                $stmt4->execute([
                    ':comentario' => $comentario,
                    ':id' => $idInquilino
                ]);
            } else {
                $stmt4 = $this->db->prepare("
                    INSERT INTO inquilinos_validaciones
                        (id_inquilino, comentarios)
                    VALUES
                        (:id, :comentario)
                ");
                $stmt4->execute([
                    ':id' => $idInquilino,
                    ':comentario' => $comentario
                ]);
            }

            $this->db->commit();

            // 4) Respuesta (aún sin AWS, solo handshake)
            echo json_encode([
                'ok' => true,
                'mensaje' => 'Validación iniciada. (Siguiente paso: integrar llamadas a AWS Textract/Rekognition).',
                'resumen' => [
                    'slug' => $inquilino['slug'],
                    'nombre' => trim($inquilino['nombre_inquilino'] . ' ' . $inquilino['apellidop_inquilino'] . ' ' . ($inquilino['apellidom_inquilino'] ?? '')),
                    'tipo_id' => $inquilino['tipo_id'],
                    'archivos' => $flags
                ]
            ]);
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Error al iniciar la validación.',
                'error' => $e->getMessage()
            ]);
        }
    }
}
