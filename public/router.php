<?php
/**
 * Router de apoyo para el servidor embebido de desarrollo de PHP.
 * Uso: php -S localhost:8000 -t public public/router.php
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$rutaFisica = __DIR__ . $uri;

// Servir archivos estaticos directamente si existen
if ($uri !== '/' && file_exists($rutaFisica) && !is_dir($rutaFisica)) {
    return false;
}

// Redirigir peticiones a la API hacia api/index.php
if (str_starts_with($uri, '/api')) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'index.php';
    return;
}

// Servir la aplicacion de una sola pagina
require_once __DIR__ . DIRECTORY_SEPARATOR . 'index.html';
