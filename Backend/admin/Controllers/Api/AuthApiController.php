<?php

declare(strict_types=1);

namespace App\Controllers\Api;

require_once __DIR__ . '/../../Helpers/JwtHelper.php';
require_once __DIR__ . '/../../Helpers/ApiScopeHelper.php';
require_once __DIR__ . '/../../Helpers/ApiAuthConfig.php';
require_once __DIR__ . '/../../Models/ApiClientModel.php';
require_once __DIR__ . '/../../Models/ApiRefreshTokenModel.php';
require_once __DIR__ . '/../../Models/ApiTokenRevocationModel.php';

use App\Helpers\ApiAuthConfig;
use App\Helpers\ApiScopeHelper;
use App\Helpers\JwtHelper;
use App\Models\ApiClientModel;
use App\Models\ApiRefreshTokenModel;
use App\Models\ApiTokenRevocationModel;
use DateInterval;
use DateTimeImmutable;
use Throwable;

class AuthApiController
{
    private ApiClientModel $clientModel;

    private ApiRefreshTokenModel $refreshTokenModel;

    private ApiTokenRevocationModel $revocationModel;

    private int $accessTokenTtl;

    private int $refreshTokenTtl;

    private string $expectedAudience;

    public function __construct(
        ?ApiClientModel $clientModel = null,
        ?ApiRefreshTokenModel $refreshTokenModel = null,
        ?ApiTokenRevocationModel $revocationModel = null,
        ?int $accessTokenTtl = null,
        ?int $refreshTokenTtl = null,
        ?string $expectedAudience = null
    ) {
        $this->clientModel       = $clientModel ?? new ApiClientModel();
        $this->refreshTokenModel = $refreshTokenModel ?? new ApiRefreshTokenModel();
        $this->revocationModel   = $revocationModel ?? new ApiTokenRevocationModel();
        $this->accessTokenTtl    = $accessTokenTtl ?? ApiAuthConfig::accessTokenTtl();
        $this->refreshTokenTtl   = $refreshTokenTtl ?? ApiAuthConfig::refreshTokenTtl();
        $this->expectedAudience  = $expectedAudience ?? ApiAuthConfig::expectedAudience();
    }

    public function loginApi(): void
    {
        try {
            $data = $this->readJsonInput();
            if ($data === null) {
                $this->jsonResponse(['error' => 'invalid_request'], 400);
                return;
            }

            $clientId     = trim((string)($data['client_id'] ?? ''));
            $clientSecret = (string)($data['client_secret'] ?? '');
            $audience     = trim((string)($data['audience'] ?? ''));
            $scopes       = isset($data['scopes']) && is_array($data['scopes']) ? $data['scopes'] : [];

            if ($clientId === '' || $clientSecret === '' || $audience === '') {
                $this->jsonResponse(['error' => 'invalid_request'], 400);
                return;
            }

            if ($audience !== $this->expectedAudience) {
                $this->jsonResponse(['error' => 'invalid_audience'], 400);
                return;
            }

            $client = $this->clientModel->findByClientId($clientId);
            if ($client === null || ($client['status'] ?? '') !== ApiClientModel::STATUS_ACTIVE) {
                $this->jsonResponse(['error' => 'invalid_client'], 401);
                return;
            }

            if (empty($client['secret_hash']) || !password_verify($clientSecret, (string)$client['secret_hash'])) {
                $this->jsonResponse(['error' => 'invalid_client'], 401);
                return;
            }

            $allowedScopes   = is_array($client['allowed_scopes']) ? $client['allowed_scopes'] : [];
            $effectiveScopes = $this->resolveScopes($scopes, $allowedScopes);
            if ($effectiveScopes === []) {
                $this->jsonResponse(['error' => 'invalid_scope'], 400);
                return;
            }

            $payload = $this->issueTokenPair($client, $effectiveScopes, $audience);
            $this->clientModel->touchLastUsed((int)$client['id']);

            $this->jsonResponse($payload);
        } catch (Throwable $exception) {
            $this->jsonResponse(['error' => 'server_error'], 500);
        }
    }

    public function refreshToken(): void
    {
        try {
            $authHeader = $this->getAuthorizationHeader();
            if ($authHeader === null || stripos($authHeader, 'Bearer ') !== 0) {
                $this->jsonResponse(['error' => 'missing_access_token'], 401);
                return;
            }

            $data = $this->readJsonInput();
            if (!is_array($data)) {
                $this->jsonResponse(['error' => 'invalid_request'], 400);
                return;
            }

            $refreshToken = trim((string)($data['refresh_token'] ?? ''));
            if ($refreshToken === '') {
                $this->jsonResponse(['error' => 'invalid_request'], 400);
                return;
            }

            try {
                $claims = (array)JwtHelper::decode($refreshToken);
            } catch (Throwable $exception) {
                $this->jsonResponse(['error' => 'invalid_token'], 401);
                return;
            }

            if (($claims['type'] ?? '') !== 'refresh') {
                $this->jsonResponse(['error' => 'invalid_token'], 401);
                return;
            }

            $refreshJti = (string)($claims['jti'] ?? '');
            if ($refreshJti === '') {
                $this->jsonResponse(['error' => 'invalid_token'], 401);
                return;
            }

            if ($this->revocationModel->isRevoked($refreshJti)) {
                $this->jsonResponse(['error' => 'token_revoked'], 401);
                return;
            }

            $stored = $this->refreshTokenModel->findActiveByJti($refreshJti);
            if ($stored === null) {
                $this->jsonResponse(['error' => 'invalid_token'], 401);
                return;
            }

            $hash = $this->hashToken($refreshToken);
            if (!hash_equals((string)$stored['refresh_token_hash'], $hash)) {
                $this->jsonResponse(['error' => 'invalid_token'], 401);
                return;
            }

            $clientId = (int)($stored['client_id'] ?? 0);
            $client   = $clientId > 0 ? $this->clientModel->findById($clientId) : null;
            if ($client === null || ($client['status'] ?? '') !== ApiClientModel::STATUS_ACTIVE) {
                $this->jsonResponse(['error' => 'invalid_client'], 401);
                return;
            }

            $storedScopes = $this->refreshTokenModel->extractScopes($stored);
            $effective    = $this->resolveScopes($storedScopes, $client['allowed_scopes'] ?? []);
            if ($effective === []) {
                $this->jsonResponse(['error' => 'invalid_scope'], 400);
                return;
            }

            $this->refreshTokenModel->markConsumed((int)$stored['id']);

            $audience = (string)($claims['aud'] ?? $this->expectedAudience);
            $payload  = $this->issueTokenPair($client, $effective, $audience);
            $this->clientModel->touchLastUsed((int)$client['id']);

            $this->jsonResponse($payload);
        } catch (Throwable $exception) {
            $this->jsonResponse(['error' => 'server_error'], 500);
        }
    }

    private function readJsonInput(): ?array
    {
        $input = file_get_contents('php://input');
        if ($input === false || trim($input) === '') {
            return null;
        }

        $decoded = json_decode($input, true);
        if (!is_array($decoded)) {
            return null;
        }

        return $decoded;
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

    /**
     * @param array<int, string> $requested
     * @param array<int, string> $allowed
     * @return array<int, string>
     */
    private function resolveScopes(array $requested, array $allowed): array
    {
        $allowed = ApiScopeHelper::filter($allowed);
        if ($allowed === []) {
            return [];
        }

        if ($requested === []) {
            return $allowed;
        }

        $requested = ApiScopeHelper::filter($requested);
        $scopes    = array_values(array_intersect($requested, $allowed));

        return $scopes === [] ? $allowed : $scopes;
    }

    /**
     * @param array<string, mixed> $client
     * @param array<int, string> $scopes
     */
    private function issueTokenPair(array $client, array $scopes, string $audience): array
    {
        $scopeString = implode(' ', $scopes);
        $accessJti   = $this->uuid();
        $refreshJti  = $this->uuid();
        $subject     = 'client:' . (string)$client['client_id'];

        $accessClaims = [
            'sub'   => $subject,
            'cid'   => (int)$client['id'],
            'scope' => $scopeString,
            'aud'   => $audience,
            'jti'   => $accessJti,
            'type'  => 'access',
        ];

        $accessToken = JwtHelper::encode($accessClaims, $this->accessTokenTtl);

        $refreshClaims = [
            'sub'   => $subject,
            'cid'   => (int)$client['id'],
            'scope' => $scopeString,
            'aud'   => $audience,
            'jti'   => $refreshJti,
            'type'  => 'refresh',
        ];

        $refreshToken  = JwtHelper::encode($refreshClaims, $this->refreshTokenTtl);
        $refreshExpiry = (new DateTimeImmutable())->add(new DateInterval('PT' . $this->refreshTokenTtl . 'S'));

        $this->refreshTokenModel->create(
            (int)$client['id'],
            $refreshJti,
            $this->hashToken($refreshToken),
            $accessJti,
            $scopes,
            $refreshExpiry
        );

        return [
            'token_type'         => 'Bearer',
            'access_token'       => $accessToken,
            'refresh_token'      => $refreshToken,
            'expires_in'         => $this->accessTokenTtl,
            'refresh_expires_in' => $this->refreshTokenTtl,
            'scope'              => $scopeString,
            'jti'                => $accessJti,
            'client_id'          => (string)$client['client_id'],
        ];
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
