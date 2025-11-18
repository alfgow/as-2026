<?php

declare(strict_types=1);

namespace App\Middleware;

require_once __DIR__ . '/../Core/RequestContext.php';
require_once __DIR__ . '/../Helpers/JwtHelper.php';
require_once __DIR__ . '/../Models/UserModel.php';

use App\Core\RequestContext;
use App\Helpers\JwtHelper;
use App\Models\UserModel;
use RuntimeException;
use Throwable;

class ApiTokenMiddleware
{
    private UserModel $userModel;

    /** @var array<string, array> */
    private static array $userCache = [];

    public function __construct(?UserModel $userModel = null)
    {
        $this->userModel = $userModel ?? new UserModel();
    }

    public function handle(): void
    {
        $token = $this->parseRequest();
        if ($token === null) {
            throw ApiTokenException::missingToken();
        }

        $claims = $this->validateToken($token);
        $this->attachUser($claims);
    }

    public function parseRequest(): ?string
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

    /**
     * @param array $claims
     */
    public function attachUser(array $claims): void
    {
        $userId = (string)($claims['sub'] ?? '');
        if ($userId === '') {
            throw ApiTokenException::invalidToken();
        }

        $user = self::$userCache[$userId] ?? null;
        if ($user === null) {
            $record = $this->userModel->findByIdAsArray($userId);
            if ($record === null) {
                throw ApiTokenException::invalidToken();
            }

            $user = [
                'id'            => $record['id'] ?? null,
                'usuario'       => $record['usuario'] ?? null,
                'email'         => $record['mail_usuario'] ?? null,
                'nombre'        => trim(((string)($record['nombre_usuario'] ?? '')) . ' ' . ((string)($record['apellidos_usuario'] ?? ''))),
                'tipo_usuario'  => $record['tipo_usuario'] ?? null,
            ];

            self::$userCache[$userId] = $user;
        }

        $contextUser                = $user;
        $contextUser['scope']       = (string)$claims['scope'];
        $contextUser['token_claims'] = $claims;

        RequestContext::set('api_user', $contextUser);
        $_SERVER['api_user'] = $contextUser;
    }

    /**
     * @return array<string, mixed>
     */
    public function validateToken(string $token): array
    {
        try {
            $claims = (array)JwtHelper::decode($token);
        } catch (Throwable) {
            throw ApiTokenException::invalidToken();
        }

        $now = time();
        $exp = isset($claims['exp']) ? (int)$claims['exp'] : 0;
        if ($exp <= $now) {
            throw ApiTokenException::expired();
        }

        $scope = isset($claims['scope']) ? (string)$claims['scope'] : '';
        if ($scope === '') {
            throw ApiTokenException::invalidToken();
        }

        return $claims;
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
            $headers = getallheaders();
            if (is_array($headers)) {
                foreach ($headers as $name => $value) {
                    if (strcasecmp((string)$name, 'Authorization') === 0) {
                        return (string)$value;
                    }
                }
            }
        }

        return null;
    }
}

class ApiTokenException extends RuntimeException
{
    private string $reason;

    public function __construct(string $message, string $reason = 'invalid_token')
    {
        parent::__construct($message);
        $this->reason = $reason;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public static function invalidToken(): self
    {
        return new self('Invalid API token', 'invalid_token');
    }

    public static function missingToken(): self
    {
        return new self('Missing API token', 'missing_token');
    }

    public static function expired(): self
    {
        return new self('Expired API token', 'token_expired');
    }
}
