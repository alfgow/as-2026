<?php

declare(strict_types=1);

namespace App\Helpers;

if (!class_exists('\\Firebase\\JWT\\JWT')) {
    require_once __DIR__ . '/JwtHelperFallback.php';
}

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHelper
{
    private static string $fallbackSecret = 'cambia-esto-en-env';

    /**
     * Allow overriding the default secret when no env var is present.
     */
    public static function setFallbackSecret(string $secret): void
    {
        self::$fallbackSecret = $secret;
    }

    /**
     * Encode claims as a signed JWT string.
     *
     * @param array $claims
     */
    public static function encode(array $claims, int $ttlSeconds): string
    {
        $ttlSeconds = max(1, $ttlSeconds);
        $now        = time();
        $payload    = $claims;
        $payload['iat'] = $payload['iat'] ?? $now;
        $payload['nbf'] = $payload['nbf'] ?? $now;
        $payload['exp'] = $payload['exp'] ?? ($payload['iat'] + $ttlSeconds);

        return JWT::encode($payload, self::secret(), 'HS256');
    }

    /**
     * Decode and validate a JWT string.
     */
    public static function decode(string $token): object
    {
        return JWT::decode($token, new Key(self::secret(), 'HS256'));
    }

    private static function secret(): string
    {
        return (string)($_ENV['JWT_SECRET'] ?? self::$fallbackSecret);
    }
}
