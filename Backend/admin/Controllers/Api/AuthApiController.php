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

    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
