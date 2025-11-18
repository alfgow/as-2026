<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Simple per-request context store for sharing authenticated user data.
 */
class RequestContext
{
    /**
     * @var array<string, mixed>
     */
    private static array $store = [];

    /**
     * Persist an arbitrary value in the context for the lifetime of the request.
     */
    public static function set(string $key, mixed $value): void
    {
        self::$store[$key] = $value;
    }

    /**
     * Obtain a value from the context store.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$store[$key] ?? $default;
    }

    /**
     * Remove a value from the context.
     */
    public static function forget(string $key): void
    {
        unset(self::$store[$key]);
    }

    /**
     * Clear all values.
     */
    public static function clear(): void
    {
        self::$store = [];
    }

    /**
     * Helper accessor for the authenticated API user payload.
     */
    public static function user(): ?array
    {
        $user = self::get('api_user');

        return is_array($user) ? $user : null;
    }
}
