<?php
namespace App\Services;

use Aws\Sdk;
use Aws\StepFunctions\StepFunctionsClient;

class AwsStepFunctionsService
{
    private StepFunctionsClient $sfn;
    private string $stateMachineArn;

    public function __construct()
    {
        $region = getenv('AWS_REGION') ?: 'us-east-1';
        $sdk = new Sdk([
            'region'  => $region,
            'version' => 'latest',
        ]);
        $this->sfn = $sdk->createStepFunctions();

        $this->stateMachineArn = getenv('STATE_MACHINE_ARN_VALIDACION_LEGAL') ?: '';
        if ($this->stateMachineArn === '') {
            throw new \RuntimeException('Falta STATE_MACHINE_ARN_VALIDACION_LEGAL en env.');
        }
    }

    /**
     * Inicia una ejecución con el input dado. Regresa el executionArn.
     */
    public function startValidacion(array $input): string
    {
        $name = $this->buildName($input['portal'] ?? 'portal', $input['inquilino']['id'] ?? 0);
        $res = $this->sfn->startExecution([
            'stateMachineArn' => $this->stateMachineArn,
            'name'            => $name,
            'input'           => json_encode($input, JSON_UNESCAPED_UNICODE),
        ]);
        return (string) $res->get('executionArn');
    }

    private function buildName(string $portal, int $idInquilino): string
    {
        $ts = date('Ymd-His');
        $raw = "validacion-{$portal}-{$idInquilino}-{$ts}";
        $san = preg_replace('/[^A-Za-z0-9\-_]/', '-', $raw);
        return substr($san, 0, 80); // Límite Step Functions
    }
}