<?php

declare(strict_types=1);

namespace App\Helpers;

class ApiAuthConfig
{
    public static function expectedAudience(): string
    {
        $value = $_ENV['API_EXPECTED_AUDIENCE']
            ?? getenv('API_EXPECTED_AUDIENCE')
            ?? 'n8n-integrations';

        $value = is_string($value) ? trim($value) : '';

        return $value !== '' ? $value : 'n8n-integrations';
    }

    public static function accessTokenTtl(): int
    {
        $value = (int)($_ENV['API_ACCESS_TOKEN_TTL']
            ?? getenv('API_ACCESS_TOKEN_TTL')
            ?? 3600);

        return max(300, $value);
    }

    public static function refreshTokenTtl(): int
    {
        $value = (int)($_ENV['API_REFRESH_TOKEN_TTL']
            ?? getenv('API_REFRESH_TOKEN_TTL')
            ?? 2592000); // 30 días

        return max(3600, $value);
    }
}
