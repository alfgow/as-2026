<?php

/**
 * Archivo de configuración principal
 *
 * Siempre carga credentials.local.php (ignorado en Git).
 * Nunca usa variables de entorno (.env) porque IONOS no las soporta.
 */

$localFile = __DIR__ . '/credentials.local.php';

if (!file_exists($localFile)) {
    throw new \RuntimeException('Falta el archivo credentials.local.php en la carpeta config/');
}

$data = require $localFile;

if (!is_array($data)) {
    throw new \RuntimeException('El archivo credentials.local.php debe retornar un arreglo PHP.');
}

return $data;
