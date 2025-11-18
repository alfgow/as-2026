<?php

declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Models/ApiClientModel.php';
require_once __DIR__ . '/../Helpers/ApiScopeHelper.php';
require_once __DIR__ . '/../Helpers/ApiAuthConfig.php';

use App\Helpers\ApiAuthConfig;
use App\Helpers\ApiScopeHelper;
use App\Middleware\AuthMiddleware;
use App\Models\ApiClientModel;
use Throwable;

AuthMiddleware::verificarSesion();

class ApiClientController
{
    private const PANEL_ROUTE = 'integrations/clients';

    public function __construct(private readonly ApiClientModel $apiClientModel = new ApiClientModel())
    {
    }

    public function index(): void
    {
        $clients          = $this->apiClientModel->all();
        $supportedScopes  = ApiScopeHelper::descriptions();
        $title            = 'API Tokens - AS';
        $headerTitle      = 'Credenciales API y Tokens';
        $expectedAudience = ApiAuthConfig::expectedAudience();
        $flashCredentials = $_SESSION['api_client_credentials'] ?? null;
        $errorMessage     = $_SESSION['api_client_error'] ?? null;

        unset($_SESSION['api_client_credentials'], $_SESSION['api_client_error']);

        $contentView = __DIR__ . '/../Views/api-clients/index.php';
        include __DIR__ . '/../Views/layouts/main.php';
    }

    public function store(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirectWithError('Método no permitido.');
            return;
        }

        $name      = trim((string)($_POST['name'] ?? ''));
        $scopes    = isset($_POST['scopes']) ? (array)$_POST['scopes'] : [];
        $rateLimit = (int)($_POST['rate_limit'] ?? 60);

        if ($name === '') {
            $this->redirectWithError('El nombre es obligatorio.');
            return;
        }

        $scopes = ApiScopeHelper::filter($scopes);
        if ($scopes === []) {
            $this->redirectWithError('Selecciona al menos un scope.');
            return;
        }

        try {
            $result = $this->apiClientModel->createClient($name, $scopes, max(1, $rateLimit));
            $_SESSION['api_client_credentials'] = [
                'title'         => 'Cliente creado',
                'client_id'     => $result['client_id'],
                'client_secret' => $result['client_secret'],
                'scopes'        => $scopes,
            ];
        } catch (Throwable $exception) {
            $this->redirectWithError($exception->getMessage());
            return;
        }

        $this->redirectToIndex();
    }

    public function rotateSecret(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirectWithError('Método no permitido.');
            return;
        }

        $clientId = (int)($_POST['client_id'] ?? 0);
        if ($clientId <= 0) {
            $this->redirectWithError('Cliente inválido.');
            return;
        }

        try {
            $result = $this->apiClientModel->rotateSecret($clientId);
            $_SESSION['api_client_credentials'] = [
                'title'         => 'Se generó un nuevo secreto',
                'client_id'     => $result['client_id'],
                'client_secret' => $result['client_secret'],
                'is_rotation'   => true,
            ];
        } catch (Throwable $exception) {
            $this->redirectWithError($exception->getMessage());
            return;
        }

        $this->redirectToIndex();
    }

    private function redirectToIndex(): void
    {
        header('Location: ' . admin_base_url(self::PANEL_ROUTE));
        exit;
    }

    private function redirectWithError(string $message): void
    {
        $_SESSION['api_client_error'] = $message;
        $this->redirectToIndex();
    }
}
