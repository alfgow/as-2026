<?php

declare(strict_types=1);

namespace App\Middleware;

require_once __DIR__ . '/../Core/RequestContext.php';
require_once __DIR__ . '/../Helpers/JwtHelper.php';
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../Models/ApiClientModel.php';
require_once __DIR__ . '/../Models/ApiTokenRevocationModel.php';

use App\Core\RequestContext;
use App\Helpers\JwtHelper;
use App\Models\UserModel;
use App\Models\ApiClientModel;
use App\Models\ApiTokenRevocationModel;
use RuntimeException;
use Throwable;

class ApiTokenMiddleware
{
    private UserModel $userModel;

    private ApiClientModel $apiClientModel;

    private ApiTokenRevocationModel $revocationModel;

    /** @var array<string, array> */
    private static array $userCache = [];

    public function __construct(
        ?UserModel $userModel = null,
        ?ApiClientModel $apiClientModel = null,
        ?ApiTokenRevocationModel $revocationModel = null
    ) {
        $this->userModel        = $userModel ?? new UserModel();
        $this->apiClientModel   = $apiClientModel ?? new ApiClientModel();
        $this->revocationModel  = $revocationModel ?? new ApiTokenRevocationModel();
    }

    public function handle(): void
    {
        $token = $this->parseRequest();
        if ($token === null) {
            throw ApiTokenException::missingToken();
        }

        $claims = $this->validateToken($token);
        $this->attachActor($claims);
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
     * @param array<string, mixed> $claims
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

        $this->shareContext($contextUser);
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

        $jti = isset($claims['jti']) ? (string)$claims['jti'] : '';
        if ($jti === '') {
            throw ApiTokenException::invalidToken();
        }

        if ($this->revocationModel->isRevoked($jti)) {
            throw ApiTokenException::revoked();
        }

        return $claims;
    }

    /**
     * @param array<string, mixed> $claims
     */
    private function attachActor(array $claims): void
    {
        if (isset($claims['cid'])) {
            $this->attachClient($claims);

            return;
        }

        $this->attachUser($claims);
    }

    /**
     * @param array<string, mixed> $claims
     */
    private function attachClient(array $claims): void
    {
        $clientId = isset($claims['cid']) ? (int)$claims['cid'] : 0;
        if ($clientId <= 0) {
            throw ApiTokenException::invalidToken();
        }

        $client = $this->apiClientModel->findById($clientId);
        if ($client === null || ($client['status'] ?? '') !== ApiClientModel::STATUS_ACTIVE) {
            throw ApiTokenException::invalidToken();
        }

        $context = [
            'id'            => null,
            'usuario'       => $client['client_id'] ?? null,
            'email'         => null,
            'nombre'        => $client['name'] ?? null,
            'tipo_usuario'  => 'api_client',
            'scope'         => (string)$claims['scope'],
            'token_claims'  => $claims,
            'api_client'    => $client,
        ];

        $this->shareContext($context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function shareContext(array $context): void
    {
        RequestContext::set('api_user', $context);
        $_SERVER['api_user'] = $context;
    }

    private function getAuthorizationHeader(): ?string
    {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return (string)$_SERVER['HTTP_AUTHORIZATION'];
        }
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return (string)$_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
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

    public static function revoked(): self
    {
        return new self('Revoked API token', 'token_revoked');
    }
}
