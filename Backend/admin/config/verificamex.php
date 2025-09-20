<?php
$credentials = require __DIR__ . '/credentials.php';

$verificamex = $credentials['verificamex'] ?? [];

return [
    'token' => $verificamex['token'] ?? '',
];
