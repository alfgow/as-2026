<?php
namespace Backend\admin\Controllers;

use Exception;
require_once __DIR__ . '/../aws-sdk-php/aws-autoloader.php';
use Aws\Credentials\Credentials;
use Aws\BedrockRuntime\BedrockRuntimeClient;

require_once __DIR__ . '/../Models/IAModel.php';
use App\Models\IAModel;

require_once __DIR__ . '/../Core/Database.php';
use App\Core\Database;
use PDO;

require_once __DIR__ . '/../Models/InquilinoModel.php';
use App\Models\InquilinoModel;


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
            // Forzamos límites conservadores para costo y verborrea
            $maxTokens   = 60; // ignoramos lo enviado para recortar costo/salida
            $temperature = 0.2;

            if ($prompt === '') {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'El campo "prompt" es requerido.']);
                return;
            }

            // 1) Intento de RESPUESTA DIRECTA (sin IA) para consultas internas conocidas
            $direct = $this->respuestaDirecta($prompt);
            if ($direct !== null) {
                $text = $direct;
                $elapsedMs = 0;

                // Registrar historial
                if ($this->iaModel) {
                    $this->iaModel->registrarInteraccion([
                        'usuario_id'  => null,
                        'modelo_key'  => 'direct',
                        'modelo_id'   => 'direct-no-llm',
                        'prompt'      => $prompt,
                        'respuesta'   => $text,
                        'duration_ms' => $elapsedMs,
                        'ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
                        'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? null,
                    ]);
                }

                echo json_encode([
                    'ok'         => true,
                    'model'      => 'direct-no-llm',
                    'model_key'  => 'direct',
                    'mode'       => 'direct',
                    'hintDelayMs'=> 900, 
                    'output'     => $text,
                    'durationMs' => $elapsedMs
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                return;
            }

            // 2) Si no hubo respuesta directa, usamos el modelo con reglas estrictas
            $models = $this->cfg['models'] ?? [];
            if (!isset($models[$modelKey])) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Modelo inválido. Usa "claude", "titan" o "llama".']);
                return;
            }
            $modelId = $models[$modelKey];

            // Reglas de estilo (1 frase) + dato si existiera en el prompt
            $prompt = "Responde en **una sola frase corta**, sin consejos generales ni listas.\n\nUsuario: " . $prompt;

            [$contentType, $accept, $payload] = $this->buildPayload($modelKey, $prompt, $maxTokens, $temperature);

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
            $text   = $this->extractText($modelKey, $json);

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

    private function buildPayload(string $modelKey, string $prompt, int $maxTokens, float $temperature): array
    {
        switch ($modelKey) {
            case 'claude':
                // Usamos SYSTEM fuerte para cortar verborrea
                $system = "Eres asistente de Arrendamiento Seguro. Responde SIEMPRE en una sola frase concisa. "
                        . "Si no hay datos internos explícitos en el mensaje del usuario, responde: "
                        . "\"No tengo datos internos para eso.\" No des consejos generales ni listas.";
                return ['application/json', 'application/json', [
                    'anthropic_version' => 'bedrock-2023-05-31',
                    'system'            => $system,
                    'max_tokens'        => $maxTokens,
                    'temperature'       => $temperature,
                    'top_p'             => 0.9,
                    'messages'          => [[
                        'role'    => 'user',
                        'content' => [['type' => 'text', 'text' => $prompt]]
                    ]]
                ]];

            case 'titan':
                $promptTitan = "Eres asistente de Arrendamiento Seguro. Responde en una sola frase concisa, sin listas. "
                             . "Si no hay datos internos explícitos, contesta: 'No tengo datos internos para eso.'\n\n"
                             . $prompt;
                return ['application/json', 'application/json', [
                    'inputText' => $promptTitan,
                    'textGenerationConfig' => [
                        'maxTokenCount' => $maxTokens,
                        'temperature'   => $temperature,
                        'topP'          => 0.9
                    ]
                ]];

            case 'llama':
                $system = "You are Arrendamiento Seguro's assistant. Always answer in ONE short sentence. "
                        . "If there is no explicit internal data, reply: 'No tengo datos internos para eso.' "
                        . "Do not give generic advice or lists.";
                $fullPrompt = "<|begin_of_text|><|start_header_id|>system<|end_header_id|>\n{$system}\n<|eot_id|>"
                            . "<|start_header_id|>user<|end_header_id|>\n{$prompt}\n<|eot_id|>"
                            . "<|start_header_id|>assistant<|end_header_id|>\n";
                return ['application/json', 'application/json', [
                    'prompt'      => $fullPrompt,
                    'max_tokens'  => $maxTokens,
                    'temperature' => $temperature,
                    'top_p'       => 0.9
                ]];

            default:
                throw new Exception('Modelo no soportado.');
        }
    }

    private function extractText(string $modelKey, array $json): string
    {
        switch ($modelKey) {
            case 'claude':
                return $json['content'][0]['text'] ?? 'Sin respuesta';
            case 'titan':
                return $json['results'][0]['outputText'] ?? 'Sin respuesta';
            case 'llama':
                return $json['generation'] ?? 'Sin respuesta';
            default:
                return 'Sin respuesta';
        }
    }

    /**
     * Respuestas determinísticas y baratas (sin IA) para preguntas internas conocidas.
     * Devuelve string si puede responder directo; null si debe pasar al modelo.
     */

// --- AGREGA ESTOS HELPERS ---

private function detectarMesPorNombre(string $texto): ?int
{
    // texto viene normalizado (sin acentos, minúsculas)
    $map = [
        'enero'=>1, 'febrero'=>2, 'marzo'=>3, 'abril'=>4, 'mayo'=>5, 'junio'=>6,
        'julio'=>7, 'agosto'=>8, 'septiembre'=>9, 'setiembre'=>9,
        'octubre'=>10, 'noviembre'=>11, 'diciembre'=>12
    ];
    foreach ($map as $nombre => $num) {
        if (strpos($texto, $nombre) !== false) return $num;
    }
    return null;
}

private function nombreMes(int $mes): string
{
    $nombres = [
        1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril', 5=>'Mayo', 6=>'Junio',
        7=>'Julio', 8=>'Agosto', 9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'
    ];
    return $nombres[$mes] ?? (string)$mes;
}

private function normalize(string $s): string
{
    // minúsculas y sin acentos para hacer matching robusto
    $s = mb_strtolower($s, 'UTF-8');
    $s = strtr($s, [
        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n'
    ]);
    return $s;
}

private function baseUrl(): string
{
    $https   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script  = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir     = str_replace('\\', '/', dirname($script));
    $base    = rtrim(preg_replace('#/index\.php$#', '', $dir), '/');
    $url     = $https . '://' . $host . ($base ? $base : '');
    // limpiar dobles barras
    return preg_replace('#(?<!:)//+#', '/', $url);
}

private function pick(array $arr) {
    return $arr[array_rand($arr)];
}

private function plural(int $n, string $singular, string $plural): string {
    return $n === 1 ? $singular : $plural;
}

    private function respuestaDirecta(string $prompt): ?string
    {
        // ---------- 1) BÚSQUEDA DE INQUILINOS ----------
        $pNorm = $this->normalize($prompt); // minúsculas + sin acentos

        if (preg_match('/\binquilinos?\b/u', $pNorm)) {
            // Termino de búsqueda (nombre / correo / teléfono)
            $term = null;

            // a) Entre comillas: inquilinos "Juan Pérez"
            if (preg_match('/"([^"]{2,})"/u', $prompt, $m)) {
                $term = trim($m[1]);
            }

            // b) Email
            if (!$term && preg_match('/([A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,})/u', $prompt, $m)) {
                $term = trim($m[1]);
            }

            // c) Teléfono (7+ dígitos)
            if (!$term && preg_match('/(\+?\d[\d\s\-\(\)]{6,})/u', $prompt, $m)) {
                $term = preg_replace('/\D+/', '', $m[1]); // solo dígitos
            }

            // d) “por/de …”
            if (!$term && preg_match('/(?:\bpor\b|\bde\b)\s+([A-Za-zÁÉÍÓÚÜÑáéíóúüñ0-9@\.\-\s]{2,})$/u', $prompt, $m)) {
                $term = trim($m[1]);
            }

            // e) Fallback: quitamos palabras vacías típicas
            if (!$term) {
                $tmp = preg_replace('/\b(inquilinos?|buscar|busca|encuentra|localiza|por|de|el|la|los|las|con|que|y|ver)\b/iu', ' ', $prompt);
                $term = trim(preg_replace('/\s+/', ' ', $tmp));
            }

            if ($term && mb_strlen($term, 'UTF-8') >= 2) {
                // Requiere que hayas hecho: require_once InquilinoModel + use App\Models\InquilinoModel;
                $inqModel = new \App\Models\InquilinoModel();
                $rows = $inqModel->buscarPorTexto($term, 8);

                $href = rtrim($this->baseUrl(), '/') . '/inquilinos?query=' . urlencode($term);
                $link = '<a href="'.$href.'" class="text-indigo-400 underline" target="_blank">Ver resultados</a>';

                if (!$rows) {
                    $noTpl = [
                        "No encontré inquilinos que coincidan con “{$term}” {$link}",
                        "Nada por ahora con “{$term}” {$link}",
                        "Parece que no hay coincidencias para “{$term}” {$link}",
                        "Sin resultados para “{$term}” {$link}",
                    ];
                    return $this->pick($noTpl);
                }

                // Previsualización corta
                $labels = [];
                foreach ($rows as $r) {
                    $name = trim($r['nombre'] ?? '');
                    $em   = trim($r['email'] ?? '');
                    $tel  = trim($r['telefono'] ?? '');
                    $extra = $em ?: $tel;
                    $labels[] = $extra ? "{$name} ({$extra})" : $name;
                }

                $preview = array_slice($labels, 0, 3);
                $yMas    = max(0, count($labels) - 3);

                $yesTpl = [
                    "Encontré ".count($rows)." coincidencias para “{$term}”: ".implode(', ', $preview).($yMas ? " … y {$yMas} más" : "")." {$link}",
                    "Tengo resultados para “{$term}”: ".implode(', ', $preview).($yMas ? " … y {$yMas} más" : "")." {$link}",
                    "Me salió esto con “{$term}”: ".implode(', ', $preview).($yMas ? " … y {$yMas} más" : "")." {$link}",
                    "Aquí hay matches para “{$term}”: ".implode(', ', $preview).($yMas ? " … y {$yMas} más" : "")." {$link}",
                ];
                return $this->pick($yesTpl);
            }

            // Le faltó el término de búsqueda
            return "¿Me pasas nombre, correo o teléfono para buscar al inquilino?";
        }

        // ---------- 2) VENCIMIENTOS DE PÓLIZAS ----------
        // ¿Pregunta por vencimientos?
        $hayVenc   = (bool) preg_match('/\b(vencen|vencimiento|vencimientos)\b/u', $pNorm);
        $esteMes   = (bool) preg_match('/\b(este|actual)\s*mes\b/u', $pNorm);
        $sigMes    = (bool) preg_match('/\b(siguiente|proximo)\s*mes\b/u', $pNorm);
        $dameEste  = (bool) preg_match('/dame.*vencimientos.*este\s*mes/u', $pNorm);

        // Mes explícito por nombre
        $mesN = $this->detectarMesPorNombre($pNorm); // 1..12 o null

        // Año explícito tipo 2025
        $anioN = null;
        if (preg_match('/\b(20\d{2})\b/u', $pNorm, $m)) {
            $anioN = (int) $m[1];
        }

        if (!$hayVenc && !$dameEste && is_null($mesN)) {
            // No aplica respuesta directa
            return null;
        }

        // Resolver mes/año objetivo
        if ($mesN !== null) {
            $mes  = $mesN;
            $anio = $anioN ?? (int)date('Y');
            if ($anioN === null) {
                $mesActual = (int)date('n');
                if ($mes < $mesActual) $anio += 1; // si el mes ya pasó, asumimos el siguiente año
            }
        } elseif ($esteMes || $dameEste) {
            $mes  = (int) date('n');
            $anio = (int) date('Y');
        } elseif ($sigMes) {
            $ts   = strtotime('first day of next month');
            $mes  = (int) date('n', $ts);
            $anio = (int) date('Y', $ts);
        } else {
            // Genérica → siguiente mes por defecto
            $ts   = strtotime('first day of next month');
            $mes  = (int) date('n', $ts);
            $anio = (int) date('Y', $ts);
        }

        // Consulta a BD
        $db  = (new \App\Core\Database())->getDB();
        $sql = "SELECT COUNT(*) AS total
                FROM polizas
                WHERE mes_vencimiento = :mes AND year_vencimiento = :anio";
        $stmt = $db->prepare($sql);
        $stmt->execute([':mes' => $mes, ':anio' => $anio]);
        $total = (int) ($stmt->fetchColumn() ?: 0);

        // Mes en texto y enlace absoluto
        $mesTxt = $this->nombreMes($mes);
        $href   = rtrim($this->baseUrl(), '/') . "/vencimientos?mes={$mes}&anio={$anio}";

        // Gramática y plantillas naturales
        $poliza = $this->plural($total, 'póliza', 'pólizas');
        $verbo  = $this->plural($total, 'vence',  'vencen');
        $link   = '<a href="'.$href.'" class="text-indigo-400 underline" target="_blank">Ver vencimientos</a>';

        if ($total > 0) {
            $templates = [
                "Qué onda, tenemos {n} {poliza} que {verbo} en {mes} de {anio} {link}",
                "Oye, me encontré con {n} {poliza} que {verbo} en {mes} {anio} {link}",
                "Mira, hay {n} {poliza} que {verbo} en {mes} {anio} {link}",
                "Te aviso que {n} {poliza} {verbo} en {mes} {anio} {link}",
                "Acabo de ver que {n} {poliza} {verbo} en {mes} {anio} {link}",
                "Parece que {n} {poliza} {verbo} en {mes} {anio} {link}",
                "Encontré {n} {poliza} que {verbo} en {mes} {anio} {link}",
                "Ojo, hay {n} {poliza} que {verbo} en {mes} {anio} {link}",
                "Buenas noticias, tenemos {n} {poliza} que {verbo} en {mes} {anio} {link}",
                "Todo listo, {n} {poliza} que {verbo} en {mes} {anio} {link}",
                "Estuve revisando y vi {n} {poliza} que {verbo} en {mes} {anio} {link}",
                "Me salió que {n} {poliza} {verbo} en {mes} {anio} {link}",
                "En la agenda aparece que {n} {poliza} {verbo} en {mes} {anio} {link}",
                "Tengo el dato, {n} {poliza} que {verbo} en {mes} {anio} {link}",
                "Revisé y sí, hay {n} {poliza} que {verbo} en {mes} {anio} {link}",
                "Te confirmo que {n} {poliza} {verbo} en {mes} {anio} {link}",
                "Ya revisé y encontré {n} {poliza} que {verbo} en {mes} {anio} {link}",
                "En {mes} {anio} están programadas {n} {poliza} para vencerse {link}",
                "Por lo que veo, {n} {poliza} {verbo} en {mes} {anio} {link}",
                "Sí, hay {n} {poliza} que {verbo} en {mes} {anio} {link}",
            ];
        } else {
            $templates = [
                "No hay nada por vencer en {mes} {anio} {link}",
                "Parece que {mes} {anio} viene libre de vencimientos {link}",
                "Nada pendiente para {mes} {anio} {link}",
                "Sin vencimientos en {mes} {anio} {link}",
                "No encontré vencimientos para {mes} {anio} {link}",
                "Todo tranquilo en {mes} {anio}, sin vencimientos {link}",
                "Cero vencimientos para {mes} {anio} {link}",
                "Nada registrado para {mes} {anio} {link}",
                "Por ahora, {mes} {anio} está libre de vencimientos {link}",
                "En {mes} {anio} no hay nada programado para vencer {link}",
            ];
        }

        $tpl = $this->pick($templates);
        return strtr($tpl, [
            '{n}'      => (string)$total,
            '{poliza}' => $poliza,
            '{verbo}'  => $verbo,
            '{mes}'    => $mesTxt,
            '{anio}'   => (string)$anio,
            '{link}'   => $link
        ]);
    }


}
