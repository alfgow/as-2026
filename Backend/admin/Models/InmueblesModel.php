<?php

namespace App\Models;

require_once __DIR__ . '/../Core/Database.php';

use App\Core\Database;
use PDO;

require_once __DIR__ . '/../Core/Dynamo.php';

use App\Core\Dynamo;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;

class InmuebleModel extends Database
{
    private DynamoDbClient $client;
    private Marshaler $marshaler;
    private string $table;

    public function __construct()
    {
        parent::__construct();
        $this->client    = Dynamo::client();
        $this->marshaler = Dynamo::marshaler();
        $this->table     = Dynamo::table();
    }

    /** Listado completo (ojo: sin paginar) */
    public function obtenerTodos(): array
    {
        $sql = "SELECT i.*, a.nombre_arrendador, s.nombre_asesor
                FROM inmuebles i
                JOIN arrendadores a ON i.id_arrendador = a.id
                JOIN asesores s ON i.id_asesor = s.id
                ORDER BY i.fecha_registro DESC";
        return $this->getConnection()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPaginados(int $limite, int $offset): array
    {
        $sql = "SELECT i.*, a.nombre_arrendador, s.nombre_asesor
                FROM inmuebles i
                JOIN arrendadores a ON i.id_arrendador = a.id
                JOIN asesores s ON i.id_asesor = s.id
                ORDER BY i.fecha_registro DESC
                LIMIT :limite OFFSET :offset";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPaginados(string $query, int $limite, int $offset): array
    {
        $sql = "SELECT i.*, a.nombre_arrendador, s.nombre_asesor
                FROM inmuebles i
                JOIN arrendadores a ON i.id_arrendador = a.id
                JOIN asesores s ON i.id_asesor = s.id
                WHERE i.direccion_inmueble LIKE :q1
                OR a.nombre_arrendador   LIKE :q2
                OR s.nombre_asesor       LIKE :q3
                ORDER BY i.fecha_registro DESC
                LIMIT :limite OFFSET :offset";

        $stmt  = $this->getConnection()->prepare($sql);
        $param = '%' . $query . '%';

        $stmt->bindValue(':q1', $param, PDO::PARAM_STR);
        $stmt->bindValue(':q2', $param, PDO::PARAM_STR);
        $stmt->bindValue(':q3', $param, PDO::PARAM_STR);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarBusqueda(string $query): int
    {
        $sql = "SELECT COUNT(*)
                FROM inmuebles i
                JOIN arrendadores a ON i.id_arrendador = a.id
                JOIN asesores s ON i.id_asesor = s.id
                WHERE i.direccion_inmueble LIKE :q1
                OR a.nombre_arrendador   LIKE :q2
                OR s.nombre_asesor       LIKE :q3";

        $stmt  = $this->getConnection()->prepare($sql);
        $param = '%' . $query . '%';

        $stmt->bindValue(':q1', $param, PDO::PARAM_STR);
        $stmt->bindValue(':q2', $param, PDO::PARAM_STR);
        $stmt->bindValue(':q3', $param, PDO::PARAM_STR);

        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function contarTodos(): int
    {
        return (int) $this->getConnection()->query('SELECT COUNT(*) FROM inmuebles')->fetchColumn();
    }

    public function contarPorArrendador(int $idArrendador): int
    {
        $stmt = $this->getConnection()->prepare('SELECT COUNT(*) FROM inmuebles WHERE id_arrendador = :id');
        $stmt->execute([':id' => $idArrendador]);
        return (int) $stmt->fetchColumn();
    }

    public function obtenerPorId(int $id): ?array
    {
        $sql = "SELECT i.*, a.nombre_arrendador, s.nombre_asesor
                FROM inmuebles i
                JOIN arrendadores a ON i.id_arrendador = a.id
                JOIN asesores s ON i.id_asesor = s.id
                WHERE i.id = :id
                LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function crear(array $data): bool
    {
        /**
         * 🚩 ATENCIÓN:
         * Esta función ya fue actualizada a DynamoDB.
         * NO volver a modificar para MySQL.
         */
        $sk = 'INM#' . uniqid();

        // 1️⃣ Construir item de inmueble
        $item = [
            'pk'                 => ['S' => $data['pk']], // ej: ARR#557
            'sk'                 => ['S' => $sk],
            'tipo'               => ['S' => $data['tipo']],
            'direccion_inmueble' => ['S' => $data['direccion_inmueble']],
            'renta'              => ['N' => (string) $data['renta']],
            'mantenimiento'      => ['S' => $data['mantenimiento']],
            'estacionamiento'    => ['N' => (string) $data['estacionamiento']],
            'mascotas'           => ['S' => strtoupper((string) $data['mascotas'])],
            'fecha_registro'     => ['S' => date('Y-m-d H:i:s')],
        ];

        if (isset($data['monto_mantenimiento']) && $data['monto_mantenimiento'] !== null && $data['monto_mantenimiento'] !== '') {
            $item['monto_mantenimiento'] = ['N' => (string) $data['monto_mantenimiento']];
        }

        if (isset($data['deposito']) && $data['deposito'] !== null && $data['deposito'] !== '') {
            $item['deposito'] = ['N' => (string) $data['deposito']];
        }

        if (!empty($data['comentarios'])) {
            $item['comentarios'] = ['S' => $data['comentarios']];
        }

        if (!empty($data['asesor'])) {
            $item['asesor'] = ['S' => $data['asesor']];
        }

        try {
            // 2️⃣ Guardar inmueble
            $this->client->putItem([
                'TableName' => $this->table,
                'Item'      => $item,
            ]);

            // 3️⃣ Actualizar arrendador → agregar inmueble a inmuebles_ids
            $this->client->updateItem([
                'TableName' => $this->table,
                'Key' => [
                    'pk' => ['S' => $data['pk']],      // ARR#557
                    'sk' => ['S' => 'PROFILE']
                ],
                'UpdateExpression' => 'SET inmuebles_ids = list_append(if_not_exists(inmuebles_ids, :empty_list), :new_inm)',
                'ExpressionAttributeValues' => [
                    ':new_inm'    => ['L' => [['S' => $sk]]],
                    ':empty_list' => ['L' => []]
                ]
            ]);

            return true;
        } catch (\Throwable $e) {
            error_log("❌ Error guardando inmueble en Dynamo: " . $e->getMessage());
            return false;
        }
    }



    public function actualizarPorPkSk(string $pk, string $sk, array $data): bool
    {
        try {
            $setParts = [
                'tipo = :tipo',
                'direccion_inmueble = :direccion',
                'renta = :renta',
                'mantenimiento = :mantenimiento',
                'estacionamiento = :estacionamiento',
                'mascotas = :mascotas',
            ];

            $values = [
                ':tipo'           => ['S' => (string) $data['tipo']],
                ':direccion'      => ['S' => (string) $data['direccion_inmueble']],
                ':renta'          => ['N' => (string) $data['renta']],
                ':mantenimiento'  => ['S' => (string) $data['mantenimiento']],
                ':estacionamiento'=> ['N' => (string) (int) $data['estacionamiento']],
                ':mascotas'       => ['S' => strtoupper((string) $data['mascotas'])],
            ];

            $remove = [];

            if (isset($data['monto_mantenimiento']) && $data['monto_mantenimiento'] !== null && $data['monto_mantenimiento'] !== '') {
                $setParts[] = 'monto_mantenimiento = :monto_mantenimiento';
                $values[':monto_mantenimiento'] = ['N' => (string) $data['monto_mantenimiento']];
            } else {
                $remove[] = 'monto_mantenimiento';
            }

            if (isset($data['deposito']) && $data['deposito'] !== null && $data['deposito'] !== '') {
                $setParts[] = 'deposito = :deposito';
                $values[':deposito'] = ['N' => (string) $data['deposito']];
            } else {
                $remove[] = 'deposito';
            }

            if (!empty($data['comentarios'])) {
                $setParts[] = 'comentarios = :comentarios';
                $values[':comentarios'] = ['S' => (string) $data['comentarios']];
            } else {
                $remove[] = 'comentarios';
            }

            if (!empty($data['asesor'])) {
                $setParts[] = 'asesor = :asesor';
                $values[':asesor'] = ['S' => (string) $data['asesor']];
            } else {
                $remove[] = 'asesor';
            }

            $updateExpression = 'SET ' . implode(', ', $setParts);
            $remove = array_unique(array_filter($remove));
            if (!empty($remove)) {
                $updateExpression .= ' REMOVE ' . implode(', ', $remove);
            }

            $params = [
                'TableName' => $this->table,
                'Key'       => [
                    'pk' => ['S' => $pk],
                    'sk' => ['S' => $sk],
                ],
                'UpdateExpression'          => $updateExpression,
                'ExpressionAttributeValues' => $values,
            ];

            $this->client->updateItem($params);

            return true;
        } catch (\Throwable $e) {
            error_log('❌ Error actualizando inmueble en Dynamo: ' . $e->getMessage());
            return false;
        }
    }

    public function eliminar(string $pk, string $sk): bool
    {
        try {
            // 1️⃣ Eliminar el item del inmueble
            $this->client->deleteItem([
                'TableName' => $this->table,
                'Key' => [
                    'pk' => ['S' => $pk],
                    'sk' => ['S' => $sk],
                ],
            ]);

            // 2️⃣ Obtener el PROFILE del arrendador para localizar el índice del inmueble en inmuebles_ids
            $result = $this->client->getItem([
                'TableName' => $this->table,
                'Key' => [
                    'pk' => ['S' => $pk],
                    'sk' => ['S' => 'PROFILE'],
                ],
                'ProjectionExpression' => 'inmuebles_ids'
            ]);

            if (!isset($result['Item']['inmuebles_ids']['L'])) {
                // No hay lista de inmuebles_ids, nada más que hacer
                return true;
            }

            $lista = $result['Item']['inmuebles_ids']['L'];
            $index = null;

            foreach ($lista as $i => $val) {
                if (isset($val['S']) && $val['S'] === $sk) {
                    $index = $i;
                    break;
                }
            }

            if ($index === null) {
                // El inmueble no estaba en la lista
                return true;
            }

            // 3️⃣ Eliminar el inmueble del array inmuebles_ids por índice
            $this->client->updateItem([
                'TableName' => $this->table,
                'Key' => [
                    'pk' => ['S' => $pk],
                    'sk' => ['S' => 'PROFILE'],
                ],
                'UpdateExpression' => "REMOVE inmuebles_ids[$index]"
            ]);

            return true;
        } catch (\Throwable $e) {
            error_log("❌ Error al eliminar inmueble: " . $e->getMessage());
            return false;
        }
    }


    public function obtenerPorArrendador(int $idArrendador): array
    {
        $stmt = $this->getConnection()->prepare(
            "SELECT id, direccion_inmueble, renta
             FROM inmuebles
             WHERE id_arrendador = :id
             ORDER BY direccion_inmueble"
        );
        $stmt->execute([':id' => $idArrendador]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* Helpers opcionales de filtrado */
    public function filtrarPorTipo(string $tipo, int $limite = 50, int $offset = 0): array
    {
        $sql = "SELECT i.*, a.nombre_arrendador, s.nombre_asesor
                FROM inmuebles i
                JOIN arrendadores a ON i.id_arrendador = a.id
                JOIN asesores s ON i.id_asesor = s.id
                WHERE i.tipo = :tipo
                ORDER BY i.fecha_registro DESC
                LIMIT :limite OFFSET :offset";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function filtrarPorAsesor(int $idAsesor, int $limite = 50, int $offset = 0): array
    {
        $sql = "SELECT i.*, a.nombre_arrendador, s.nombre_asesor
                FROM inmuebles i
                JOIN arrendadores a ON i.id_arrendador = a.id
                JOIN asesores s ON i.id_asesor = s.id
                WHERE i.id_asesor = :idAsesor
                ORDER BY i.fecha_registro DESC
                LIMIT :limite OFFSET :offset";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':idAsesor', $idAsesor, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
