<?php
$localFile = __DIR__ . '/credentials.local.php';

if (file_exists($localFile)) {
    $data = require $localFile;
    if (!is_array($data)) {
        throw new \RuntimeException('El archivo credentials.local.php debe retornar un arreglo PHP.');
    }
    return $data;
}

return [
    'app' => [
        'url' => getenv('APP_URL') ?: 'https://crm.arrendamientoseguro.app',
    ],
    'database' => [
        'host'     => getenv('DB_HOST') ?: 'localhost',
        'port'     => (int) (getenv('DB_PORT') ?: 3306),
        'name'     => getenv('DB_NAME') ?: 'as-db',
        'user'     => getenv('DB_USER') ?: 'usuario_db',
        'password' => getenv('DB_PASS') ?: '',
        'charset'  => getenv('DB_CHARSET') ?: 'utf8mb4',
        'ssl_ca'   => getenv('DB_SSL_CA') ?: null,
    ],
    'aws' => [
        'access_key' => getenv('AWS_ACCESS_KEY_ID') ?: getenv('AWS_KEY') ?: '',
        'secret_key' => getenv('AWS_SECRET_ACCESS_KEY') ?: getenv('AWS_SECRET') ?: '',
        'dynamo' => [
            'table'  => getenv('AWS_DYNAMO_TABLE') ?: 'as-db',
            'region' => getenv('AWS_DYNAMO_REGION') ?: 'mx-central-1',
        ],
        's3' => [
            'inquilinos' => [
                'bucket' => getenv('AWS_S3_INQUILINOS_BUCKET') ?: 'as-s3-inquilinos',
                'region' => getenv('AWS_S3_INQUILINOS_REGION') ?: (getenv('AWS_S3_REGION') ?: 'mx-central-1'),
            ],
            'arrendadores' => [
                'bucket' => getenv('AWS_S3_ARRENDADORES_BUCKET') ?: 'as-s3-arrendadores',
                'region' => getenv('AWS_S3_ARRENDADORES_REGION') ?: (getenv('AWS_S3_REGION') ?: 'mx-central-1'),
            ],
            'blog' => [
                'bucket' => getenv('AWS_S3_BLOG_BUCKET') ?: 'as-s3-blog-images',
                'region' => getenv('AWS_S3_BLOG_REGION') ?: (getenv('AWS_S3_REGION') ?: 'mx-central-1'),
            ],
        ],
        'bedrock' => [
            'region'                => getenv('AWS_BEDROCK_REGION') ?: 'us-east-1',
            'model_id'             => getenv('AWS_BEDROCK_MODEL_ID') ?: 'anthropic.claude-3-5-sonnet-20240620-v1:0',
            'guardrail_identifier' => getenv('AWS_BEDROCK_GUARDRAIL_ARN') ?: '',
            'guardrail_version'    => getenv('AWS_BEDROCK_GUARDRAIL_VERSION') ?: '1',
            'credentials' => [
                'key'    => getenv('AWS_BEDROCK_KEY') ?: '',
                'secret' => getenv('AWS_BEDROCK_SECRET') ?: '',
            ],
        ],
        'ses' => [
            'region' => getenv('AWS_SES_REGION') ?: 'us-east-1',
            'sender' => getenv('AWS_SES_SENDER') ?: 'Arrendamiento Seguro <polizas@arrendamientoseguro.app>',
            'reply_to' => getenv('AWS_SES_REPLYTO') ?: 'polizas@arrendamientoseguro.app',
            'credentials' => [
                'key'    => getenv('AWS_SES_KEY') ?: '',
                'secret' => getenv('AWS_SES_SECRET') ?: '',
            ],
        ],
    ],
    'google' => [
        'api_key' => getenv('GOOGLE_API_KEY') ?: '',
        'cx'      => getenv('GOOGLE_CX_ID') ?: '',
    ],
    'verificamex' => [
        'token' => getenv('VERIFICAMEX_TOKEN') ?: '',
    ],
];
