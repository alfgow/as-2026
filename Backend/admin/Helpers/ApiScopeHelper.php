<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Catálogo centralizado de scopes soportados por la API.
 */
class ApiScopeHelper
{
    /** @var array<string, string> */
    private const SCOPES = [
        'auth.tokens'    => 'Permite refrescar y revocar tokens.',
        'tenants.read'   => 'Lectura de información de inquilinos.',
        'tenants.write'  => 'Alta o actualización de inquilinos.',
        'policies.read'  => 'Consulta de pólizas y relaciones.',
        'payments.write' => 'Registro de pagos ligados a pólizas.',
        'documents.read' => 'Acceso a URLs firmadas de documentos.',
        'webhooks.ingest'=> 'Ingesta de eventos externos.',
    ];

    /**
     * Regresa el listado completo de scopes con su descripción.
     *
     * @return array<string, string>
     */
    public static function descriptions(): array
    {
        return self::SCOPES;
    }

    /**
     * Devuelve solamente los scopes válidos, normalizados y sin duplicados.
     *
     * @param array<int, string> $scopes
     * @return array<int, string>
     */
    public static function filter(array $scopes): array
    {
        $validKeys = array_keys(self::SCOPES);
        $normalized = [];

        foreach ($scopes as $scope) {
            if (!is_string($scope)) {
                continue;
            }

            $scope = trim($scope);
            if ($scope === '') {
                continue;
            }

            if (in_array($scope, $validKeys, true)) {
                $normalized[$scope] = true;
            }
        }

        return array_values(array_keys($normalized));
    }
}
