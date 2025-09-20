<?php

declare(strict_types=1);

namespace App\Core;

require_once __DIR__ . '/../aws-sdk-php/aws-autoloader.php';
require_once __DIR__ . '/../config/awsconfig.php';

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;

class Dynamo
{
    private static ?DynamoDbClient $client = null;
    private static ?Marshaler $marshaler = null;
    private static string $tableName = 'as-db';

    /**
     * Retorna el cliente DynamoDB
     */
    public static function client(): DynamoDbClient
    {
        if (!self::$client) {
            // cargamos config de awsconfig.php
            $config = require __DIR__ . '/../config/awsconfig.php';
            if (isset($config['table'])) {
                self::$tableName = (string) $config['table'];
                unset($config['table']);
            }
            self::$client = new DynamoDbClient($config);
        }
        return self::$client;
    }

    /**
     * Retorna el Marshaler para traducir arrays ⇆ Dynamo
     */
    public static function marshaler(): Marshaler
    {
        if (!self::$marshaler) {
            self::$marshaler = new Marshaler();
        }
        return self::$marshaler;
    }

    /**
     * Nombre de la tabla principal en Dynamo
     */
    public static function table(): string
    {
        return self::$tableName;
    }
}
