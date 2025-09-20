<?php
declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
use App\Middleware\AuthMiddleware;
AuthMiddleware::verificarSesion();
require_once __DIR__ . '/../Helpers/MailHelper.php';
use App\Helpers\MailHelper;
require_once __DIR__ . '/../Models/ProspectAccessModel.php';
use App\Models\ProspectAccessModel;

class ProspectAccessController
{
    private ProspectAccessModel $model;

    public function __construct()
    {
        $this->model = new ProspectAccessModel();
    }

    /** GET /prospectos/code  -> Render con tu layout principal */
    public function code(): void
    {
        $title        = 'Emitir acceso - AS';
        $headerTitle  = 'Acceso para edición';
        $prefillEmail = filter_input(INPUT_GET, 'email', FILTER_SANITIZE_EMAIL) ?: '';

        $contentView = __DIR__ . '/../Views/prospectos/code.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * POST /prospectos/code
     * Body JSON: { email: string, actor?: 'inquilino'|'arrendador', ttl_minutes?: number }
     * Genera OTP + Magic Link con JWT HS256 y guarda en DB.
     */
    public function issue(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $in    = json_decode(file_get_contents('php://input'), true) ?: [];
            $email = strtolower(trim((string)($in['email'] ?? '')));
            $actor = isset($in['actor']) ? strtolower(trim((string)$in['actor'])) : null;
            $ttl   = max(5, (int)($in['ttl_minutes'] ?? 1440)); // default 24h; sube/baja a gusto

            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['ok' => false, 'mensaje' => 'Email inválido']); 
                return;
            }

            $resolved = $this->model->resolveActorByEmail($email, $actor);
            if (!$resolved) {
                echo json_encode(['ok' => false, 'mensaje' => 'No encontramos inquilino/arrendador con ese email']); 
                return;
            }
            [$actorType, $actorId, $actorName] = $resolved; // 'inquilino'|'arrendador', int, nombre

            // ===== Generación segura =====
            $otp        = (string)random_int(100000, 999999);
            $otpHash    = password_hash($otp, PASSWORD_BCRYPT);

            $jti        = $this->uuidv4();
            $now        = time();
            $exp        = $now + ($ttl * 60);
            $scope      = 'self:update';

            // Claims del JWT:
            $payload = [
                'iss'        => 'as-backend',
                'aud'        => 'as-frontend',
                'iat'        => $now,
                'nbf'        => $now,
                'exp'        => $exp,
                'jti'        => $jti,
                'sub'        => (string)$actorId,
                'type'       => 'prospect_update',
                'scope'      => $scope,
                'actor_type' => $actorType,
            ];

            $secret   = $this->jwtSecret();
            $tokenRaw = $this->jwtEncodeHS256($payload, $secret);

            // Guardamos solo el hash del token por higiene (no el token plano)
            $tokenHash = hash('sha256', $tokenRaw);
            $expiresAt = (new \DateTimeImmutable("@{$exp}"))->format('Y-m-d H:i:s');

            // Persistir en DB
            $this->model->insertToken([
                'actor_type' => $actorType,
                'actor_id'   => $actorId,
                'email'      => $email,
                'jti'        => $jti,
                'otp'        => $otp,       // en prod puedes guardar NULL y usar solo otp_hash
                'otp_hash'   => $otpHash,
                'token_hash' => $tokenHash,
                'expires_at' => $expiresAt,
                'scope'      => $scope,
            ]);

            // Magic link hacia el Frontend público (sin email en query)
            $magicLink = $this->frontendPublicBase() . '/auth/code?j=' . $jti;

            // 👇 Aquí estaba el error: no estabas mandando "email"
            echo json_encode([
                'ok'         => true,
                'email'      => $email,        // ✅ agregado
                'otp'        => $otp,
                'magic_link' => $magicLink,
                'expires_at' => $expiresAt,
                'actor_type' => $actorType,
                'actor_id'   => $actorId,
                'actor_name' => $actorName,
            ]);
        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
        }
    }

    // =================
    // Helpers privados
    // =================

    /** Devuelve el secreto JWT de env/config */
    private function jwtSecret(): string
    {
        // PRODUCCIÓN: define JWT_SECRET en el entorno del backend
        return (string)($_ENV['JWT_SECRET'] ?? 'cambia-esto-en-env');
    }

    /** Codifica JWT HS256: header.payload.signature (base64url) */
    private function jwtEncodeHS256(array $payload, string $secret): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $h = $this->b64url(json_encode($header,  JSON_UNESCAPED_SLASHES));
        $p = $this->b64url(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $sig = hash_hmac('sha256', "{$h}.{$p}", $secret, true);
        $s = $this->b64url($sig);
        return "{$h}.{$p}.{$s}";
    }

    /** Base64 URL-safe sin padding */
    private function b64url(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    /** UUID v4 */
    private function uuidv4(): string
    {
        $d = random_bytes(16);
        $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
        $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
    }

    /** Base del Frontend público para armar el link */
    private function frontendPublicBase(): string
    {
        $scheme =  'https';
        // Ajusta si tienes subcarpeta distinta:
        return $scheme . '://' . 'arrendamientoseguro.app';
    }

    /**
     * POST /prospectos/sendEmails
     * Body: { email, otp, magic_link, expires_at, actor_name }
     */
    public function sendEmails(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $in     = json_decode(file_get_contents('php://input'), true) ?: [];
            $email  = (string)($in['email'] ?? '');
            $otp    = (string)($in['otp'] ?? '');
            $link   = (string)($in['magic_link'] ?? '');
            $exp    = (string)($in['expires_at'] ?? '');
            $nombre = (string)($in['actor_name'] ?? 'Usuario');

            if (!$email || !$otp || !$link) {
                echo json_encode(['ok' => false, 'mensaje' => 'Datos incompletos']); 
                return;
            }

            $ok1 = MailHelper::sendMagicLinkEmail($email, $nombre, $link, $exp);
            $ok2 = MailHelper::sendOtpEmail($email, $nombre, $otp, $exp);

            if ($ok1 && $ok2) {
                echo json_encode(['ok' => true, 'mensaje' => 'Correos enviados correctamente']);
            } else {
                echo json_encode(['ok' => false, 'mensaje' => 'Error al enviar uno o más correos']);
            }
        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
        }
    }

}
 