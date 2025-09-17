<?php

declare(strict_types=1);

use App\Core\Dynamo;

require_once __DIR__ . '/Core/Dynamo.php';

$client    = Dynamo::client();
$marshaler = Dynamo::marshaler();
$table     = Dynamo::table();

try {
    // Hacemos un scan pero solo pedimos 1 item para validar conexión
    $result = $client->scan([
        'TableName' => $table,
        'Limit'     => 1
    ]);

    if (!empty($result['Items'])) {
        foreach ($result['Items'] as $item) {
            $data = $marshaler->unmarshalItem($item);
            echo "✅ Conexión exitosa. Item de prueba:\n";
            print_r($data);
        }
    } else {
        echo "⚠️ Conexión OK pero no se encontraron items en la tabla {$table}\n";
    }
} catch (Exception $e) {
    echo "❌ Error en la conexión: " . $e->getMessage() . "\n";
}
