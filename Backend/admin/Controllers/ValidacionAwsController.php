<?php
declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Models/InquilinoModel.php';

use App\Core\Database;
use App\Models\InquilinoModel;
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
            $inquilinoModel = new InquilinoModel();
            $inquilino = $inquilinoModel->obtenerPorSlug($slug);

            if (!$inquilino) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'mensaje' => 'Inquilino no encontrado por slug.']);
                return;
            }

            $idInquilino = (int)($inquilino['id'] ?? 0);
            if ($idInquilino <= 0) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'mensaje' => 'Inquilino inválido.']);
                return;
            }

            // 1) Archivos del inquilino desde el modelo (MySQL)
            $archivos = $inquilino['archivos'] ?? [];
            if ($archivos === []) {
                $archivos = $inquilinoModel->obtenerArchivos($idInquilino);
            }

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
                $tipo = strtolower((string)($a['tipo'] ?? ''));
                switch ($tipo) {
                    case 'selfie':               $flags['selfie'] = true; break;
                    case 'ine_frontal':          $flags['ine_frontal'] = true; break;
                    case 'ine_reverso':          $flags['ine_reverso'] = true; break;
                    case 'pasaporte':            $flags['pasaporte'] = true; break;
                    case 'forma_migratoria':     $flags['forma_migratoria'] = true; break;
                    case 'comprobante_ingreso':  $flags['comprobantes']++; break;
                }
            }

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

            // 2) Bitácora y registro en MySQL
            $this->db->beginTransaction();

            $stmt3 = $this->db->prepare('SELECT id FROM inquilinos_validaciones WHERE id_inquilino = :id LIMIT 1');
            $stmt3->execute([':id' => $idInquilino]);
            $valRow = $stmt3->fetch(PDO::FETCH_ASSOC);

            if ($valRow && isset($valRow['id'])) {
                $sqlUpdate = <<<'SQL'
                    UPDATE inquilinos_validaciones
                    SET comentarios = CASE
                        WHEN COALESCE(TRIM(comentarios), '') = '' THEN :comentario
                        ELSE CONCAT(comentarios, '\n', :comentario)
                    END,
                        updated_at = NOW()
                    WHERE id_inquilino = :id
                SQL;
                $stmt4 = $this->db->prepare($sqlUpdate);
                $stmt4->execute([
                    ':comentario' => $comentario,
                    ':id' => $idInquilino
                ]);
            } else {
                $sqlInsert = <<<'SQL'
                    INSERT INTO inquilinos_validaciones
                        (id_inquilino, comentarios)
                    VALUES
                        (:id, :comentario)
                SQL;
                $stmt4 = $this->db->prepare($sqlInsert);
                $stmt4->execute([
                    ':id' => $idInquilino,
                    ':comentario' => $comentario
                ]);
            }

            $this->db->commit();

            // 3) Snapshot en columnas de validación (MySQL)
            $payload = [
                'evento'    => 'validacion_iniciada',
                'comentario'=> $comentario,
                'archivos'  => $flags,
                'timestamp' => date(DATE_ATOM),
            ];
            $validacionGuardada = $inquilinoModel->guardarValidacionIdentidad($idInquilino, $payload, $comentario, 2);

            // 4) Respuesta (aún sin AWS, solo handshake)
            echo json_encode([
                'ok' => true,
                'mensaje' => 'Validación iniciada. (Siguiente paso: integrar llamadas a AWS Textract/Rekognition).',
                'resumen' => [
                    'slug' => $inquilino['slug'],
                    'nombre' => trim($inquilino['nombre_inquilino'] . ' ' . $inquilino['apellidop_inquilino'] . ' ' . ($inquilino['apellidom_inquilino'] ?? '')),
                    'tipo_id' => $inquilino['tipo_id'],
                    'archivos' => $flags
                ],
                'validacion_actualizada' => $validacionGuardada
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
