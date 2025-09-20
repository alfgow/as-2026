<?php

namespace Backend\admin\Controllers;

use Exception;

require_once __DIR__ . '/../aws-sdk-php/aws-autoloader.php';

use Aws\Credentials\Credentials;
use Aws\BedrockRuntime\BedrockRuntimeClient;

require_once __DIR__ . '/../Models/IAModel.php';

use App\Models\IAModel;

class IAController
{
    private $cfg;
    private $client;
    private $iaModel = null;

    public function __construct()
    {
        $configPath = __DIR__ . '/../config/bedrockconfig.php';
        if (!file_exists($configPath)) {
            throw new Exception("No se encontró bedrockconfig.php en: {$configPath}");
        }
        $this->cfg = require $configPath;

        $creds = new Credentials(
            $this->cfg['credentials']['key'],
            $this->cfg['credentials']['secret']
        );

        $this->client = new BedrockRuntimeClient([
            'region'      => $this->cfg['region'] ?? 'us-east-1',
            'version'     => 'latest',
            'credentials' => $creds,
        ]);

        if (class_exists('\App\Models\IAModel')) {
            $this->iaModel = new \App\Models\IAModel();
        }
    }

    // GET: vista
    public function index()
    {
        $title        = 'PolizIA - AS';
        $headerTitle  = 'PolizIA';
        $contentView  = __DIR__ . '/../Views/ia/index.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }

    // POST: invocar modelo
    public function chat()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $raw  = file_get_contents('php://input');
            $body = json_decode($raw, true) ?: [];

            $modelKey    = strtolower(trim($body['model'] ?? 'claude'));
            $prompt      = trim($body['prompt'] ?? '');
            $maxTokens   = 120;
            $temperature = 0.7;

            if ($prompt === '') {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'El campo "prompt" es requerido.']);
                return;
            }

            // 1) Intento de RESPUESTA DIRECTA (usando BD + IA narrativa)
            $direct = $this->respuestaDirecta($prompt);
            if ($direct !== null) {
                // 👇 respuestaDirecta ya registró (con contexto si aplica)
                echo json_encode([
                    'ok'         => true,
                    'model'      => 'direct-ia-narrative',
                    'model_key'  => 'direct',
                    'mode'       => 'direct',
                    'output'     => $direct,
                    'durationMs' => 0
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                return;
            }

            // 2) Si no hubo respuesta directa, usamos Claude
            if ($modelKey !== 'claude') {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Modelo inválido. Usa "claude".']);
                return;
            }
            $modelId = $this->cfg['models']['claude'];

            [$contentType, $accept, $payload] = $this->buildPayload($prompt, $maxTokens, $temperature);

            $t0 = microtime(true);
            $resp = $this->client->invokeModel([
                'modelId'     => $modelId,
                'contentType' => $contentType,
                'accept'      => $accept,
                'body'        => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ]);
            $elapsedMs = (int) round((microtime(true) - $t0) * 1000);

            $bytes  = $resp->get('body')->getContents();
            $json   = json_decode($bytes, true);
            $text   = $this->extractText($json);

            if ($this->iaModel) {
                $this->iaModel->registrarInteraccion([
                    'usuario_id'  => null,
                    'modelo_key'  => $modelKey,
                    'modelo_id'   => $modelId,
                    'prompt'      => $prompt,
                    'respuesta'   => $text,
                    'duration_ms' => $elapsedMs,
                    'ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
                    'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? null,
                ]);
            }

            echo json_encode([
                'ok'         => true,
                'model'      => $modelId,
                'model_key'  => $modelKey,
                'output'     => $text,
                'durationMs' => $elapsedMs
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    // ---- Helpers ----

    private function buildPayload(string $prompt, int $maxTokens, float $temperature): array
    {
        $system = "Eres asistente de Arrendamiento Seguro. 
        Tu única tarea es transformar datos en bruto (listas de inquilinos, pólizas o inmuebles) 
        en un resumen narrativo, breve y natural, como si hablaras con un asesor inmobiliario. 
        Nunca devuelvas listas, viñetas ni numeraciones. 
        Siempre responde con un párrafo corto y fluido en español.";

        return [
            'application/json',
            'application/json',
            [
                'anthropic_version' => 'bedrock-2023-05-31',
                'system'            => $system,
                'max_tokens'        => $maxTokens,
                'temperature'       => $temperature,
                'top_p'             => 0.9,
                'messages'          => [[
                    'role'    => 'user',
                    'content' => [['type' => 'text', 'text' => $prompt]]
                ]]
            ]
        ];
    }

    private function extractText(array $json): string
    {
        return $json['content'][0]['text'] ?? 'Sin respuesta';
    }

    private function extraerTerminoConIA(string $prompt): ?string
    {
        try {
            $payload = [
                'anthropic_version' => 'bedrock-2023-05-31',
                'max_tokens'        => 20,
                'temperature'       => 0.7,
                'messages'          => [[
                    'role'    => 'user',
                    'content' => [[
                        'type' => 'text',
                        'text' => "Extrae el término de búsqueda (nombre, correo o teléfono) de esta consulta:\n\"{$prompt}\"\nResponde solo con el término exacto."
                    ]]
                ]]
            ];

            $resp = $this->client->invokeModel([
                'modelId'     => $this->cfg['models']['claude'],
                'contentType' => 'application/json',
                'accept'      => 'application/json',
                'body'        => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ]);

            $json = json_decode($resp['body']->getContents(), true);
            return trim($json['content'][0]['text'] ?? '') ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function respuestaDirecta(string $prompt): ?string
    {
        $pNorm = $this->normalize($prompt);

        // ---------- CONSULTA COMPLETA DE INQUILINO ----------
        if (preg_match('/inquilino(s)?/u', $pNorm)) {
            $term = $this->extraerTerminoConIA($prompt);

            // 🚑 Fallback: detectar nombre propio si IA no devuelve nada
            if (!$term || mb_strlen($term, 'UTF-8') < 2) {
                if (preg_match('/([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+(?:\s+[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+){1,3})/u', $prompt, $m)) {
                    $term = $m[1];
                }
            }

            if ($term && mb_strlen($term, 'UTF-8') >= 2) {
                if (!$this->iaModel) {
                    return null;
                }

                $rows = $this->iaModel->buscarInquilinosPorTexto($term, 1);

                if (!$rows) {
                    return $this->narrarConIA("No encontré inquilinos que coincidan con “{$term}”.");
                }

                $r = $rows[0];
                $idInquilino = (int) $r['id'];

                // --- Info base del inquilino ---
                $celular = (string) ($r['celular'] ?? '');
                $contacto = $celular !== '' ? " y celular {$celular}" : '';
                $info = "Sí, tenemos registrado a {$r['nombre']} con correo {$r['email']}{$contacto}.";

                // --- Pólizas vigentes ---
                $polizas = $this->iaModel->obtenerPolizasActivasPorInquilino($idInquilino);

                if ($polizas) {
                    foreach ($polizas as $p) {
                        $info .= " Encontré que tiene relacionada la póliza número {$p['numero_poliza']} vigente hasta {$p['vigencia']}.";
                        $info .= " Correspondiente al inmueble: {$p['direccion_inmueble']}.";
                        $info .= " Arrendador: {$p['arrendador']}.";
                        $info .= " Monto de renta: {$p['renta']}.";
                        $info .= " Costo de la póliza: {$p['monto_poliza']}.";
                    }
                } else {
                    $info .= " Actualmente no tiene pólizas vigentes registradas.";
                }

                // Guardamos la interacción con contexto
                $contexto = json_encode(['inquilino_id' => $idInquilino]);
                $this->iaModel->registrarInteraccion([
                    'usuario_id'  => null,
                    'modelo_key'  => 'direct',
                    'modelo_id'   => 'direct-ia-narrative',
                    'prompt'      => $prompt,
                    'respuesta'   => $info,
                    'duration_ms' => 0,
                    'ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
                    'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? null,
                    'contexto'    => $contexto
                ]);

                return $info;
            }

            return "¿Me pasas nombre, correo o teléfono para buscar al inquilino?";
        }

        return null;
    }




    private function narrarConIA(string $texto): string
    {
        try {
            $system = "Eres un asistente de Arrendamiento Seguro, diseñado para ayudar a asesores inmobiliarios. 
                   Tu tarea es generar un resumen narrativo breve, claro y amigable en español, con un tono profesional pero cálido y conversacional. 
                   Evita listas, viñetas o numeraciones. 
                   Usa un lenguaje natural, como si hablaras directamente con el asesor, y asegúrate de que la respuesta sea fácil de leer y útil. 
                   Si los datos son sobre inquilinos, incluye solo la información relevante (como nombre y un contacto) de forma fluida.";

            $payload = [
                'anthropic_version' => 'bedrock-2023-05-31',
                'system'            => $system,
                'max_tokens'        => 150,
                'temperature'       => 0.7,
                'messages'          => [[
                    'role'    => 'user',
                    'content' => [[
                        'type' => 'text',
                        'text' => "Estos son los datos en bruto de un inquilino:\n\n{$texto}\n\n
                                Por favor genera un párrafo narrativo en tono natural como si fueras un asesor inmobiliario.
                                Menciona el nombre primero, seguido de forma fluida del correo y el teléfono.
                                Evita viñetas o enumeraciones, redacta en una sola oración."
                    ]]
                ]]
            ];

            $resp = $this->client->invokeModel([
                'modelId'     => $this->cfg['models']['claude'],
                'contentType' => 'application/json',
                'accept'      => 'application/json',
                'body'        => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ]);


            $json = json_decode($resp['body']->getContents(), true);
            return trim($json['content'][0]['text'] ?? $texto);
        } catch (\Exception $e) {
            return $texto;
        }
    }


    // Utils
    private function normalize(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
        return $s;
    }

    private function pick(array $arr)
    {
        return $arr[array_rand($arr)];
    }
}
