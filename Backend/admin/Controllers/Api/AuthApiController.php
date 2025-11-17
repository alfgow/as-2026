<?php

declare(strict_types=1);

namespace App\Controllers\Api;

require_once __DIR__ . '/../../Models/UserModel.php';
require_once __DIR__ . '/../../Helpers/JwtHelper.php';

use App\Helpers\JwtHelper;
use App\Models\UserModel;
use Throwable;

class AuthApiController
{
    private const REFRESH_WINDOW_SECONDS = 900;

    private UserModel $userModel;

    private int $tokenTtl;

    public function __construct(?UserModel $userModel = null, int $tokenTtl = 3600)
    {
        $this->userModel = $userModel ?? new UserModel();
        $this->tokenTtl  = max(1, $tokenTtl);
    }

    public function loginApi(): void
    {
        try {
            $data = $this->readJsonInput();

            if ($data === null) {
                $this->jsonResponse(['error' => 'invalid_request'], 400);
                return;
            }

            $user     = trim((string)($data['user'] ?? ''));
            $password = (string)($data['password'] ?? '');

            if ($user === '' || $password === '' || !is_string($data['user']) || !is_string($data['password'])) {
                $this->jsonResponse(['error' => 'invalid_request'], 400);
                return;
            }

            $record = $this->userModel->findByUser($user);
            if (!$record || empty($record['password']) || !password_verify($password, (string)$record['password'])) {
                $this->jsonResponse(['error' => 'invalid_credentials'], 401);
                return;
            }

            $scope = 'admin:' . (string)($record['tipo_usuario'] ?? '');

            $claims = [
                'sub'   => (string)($record['id'] ?? ''),
                'scope' => $scope,
                'exp'   => time() + $this->tokenTtl,
            ];

            $token = JwtHelper::encode($claims, $this->tokenTtl);

            $this->jsonResponse([
                'access_token' => $token,
                'expires_in'   => $this->tokenTtl,
                'scope'        => $scope,
            ]);
        } catch (Throwable $exception) {
            $this->jsonResponse(['error' => 'server_error'], 500);
        }
    }

    public function refreshToken(): void
    {
        try {
            $token = $this->extractTokenFromRequest();
            if ($token === null) {
                $this->jsonResponse(['error' => 'invalid_request'], 400);
                return;
            }

            try {
                $decoded = JwtHelper::decode($token);
            } catch (Throwable $exception) {
                $this->jsonResponse(['error' => 'invalid_token'], 401);
                return;
            }

            $claims = (array)$decoded;
            $now    = time();
            $exp    = isset($claims['exp']) ? (int)$claims['exp'] : 0;

            if ($exp <= $now) {
                $this->jsonResponse(['error' => 'token_expired'], 401);
                return;
            }

            $remainingLifetime = $exp - $now;
            if ($remainingLifetime > self::REFRESH_WINDOW_SECONDS) {
                $this->jsonResponse(['error' => 'refresh_not_required'], 400);
                return;
            }

            if (empty($claims['sub']) || empty($claims['scope'])) {
                $this->jsonResponse(['error' => 'invalid_token'], 401);
                return;
            }

            $newClaims = $claims;
            unset($newClaims['exp'], $newClaims['iat'], $newClaims['nbf']);

            $token = JwtHelper::encode($newClaims, $this->tokenTtl);
            $scope = (string)$claims['scope'];

            $this->jsonResponse([
                'access_token' => $token,
                'expires_in'   => $this->tokenTtl,
                'scope'        => $scope,
            ]);
        } catch (Throwable $exception) {
            $this->jsonResponse(['error' => 'server_error'], 500);
        }
    }

    private function readJsonInput(): ?array
    {
        $input = file_get_contents('php://input');
        if ($input === false || $input === '') {
            return null;
        }

        $decoded = json_decode($input, true);
        if (!is_array($decoded)) {
            return null;
        }

        return $decoded;
    }

    private function extractTokenFromRequest(): ?string
    {
        $token = $this->getBearerTokenFromHeaders();
        if ($token !== null) {
            return $token;
        }

        $data = $this->readJsonInput();
        if (!is_array($data)) {
            return null;
        }

        $bodyToken = $data['token'] ?? $data['access_token'] ?? null;
        if (!is_string($bodyToken) || trim($bodyToken) === '') {
            return null;
        }

        return trim($bodyToken);
    }

    private function getBearerTokenFromHeaders(): ?string
    {
        $header = $this->getAuthorizationHeader();
        if ($header === null) {
            return null;
        }

        if (stripos($header, 'Bearer ') !== 0) {
            return null;
        }

        $token = trim(substr($header, 7));
        return $token === '' ? null : $token;
    }

    private function getAuthorizationHeader(): ?string
    {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return (string)$_SERVER['HTTP_AUTHORIZATION'];
        }

        if (isset($_SERVER['Authorization'])) {
            return (string)$_SERVER['Authorization'];
        }

        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                if (strcasecmp((string)$name, 'Authorization') === 0) {
                    return (string)$value;
                }
            }
        }

        return null;
    }

    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
