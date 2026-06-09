<?php
// public/index.php  –  Front Controller
declare(strict_types=1);

// Iniciar buffer de salida para evitar "headers already sent"
// Se limpia en renderPdf() antes de enviar el PDF
ob_start();

require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/vendor/autoload.php';

// ── Autoloader PSR-4 manual ───────────────────────────────────
spl_autoload_register(function (string $class): void {
    $base = BASE_PATH . '/app/';
    $rel  = str_replace('App\\', '', $class);
    $file = $base . str_replace('\\', '/', $rel) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// ── Headers de seguridad ──────────────────────────────────────
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');

// ── Router ────────────────────────────────────────────────────
$router = new \App\Core\Router();

// Vista principal SPA
$router->add('GET',    '/',                       'EstudianteController', 'index');
$router->add('GET',    '/index',                  'EstudianteController', 'index');
$router->add('GET',    '/index.php',              'EstudianteController', 'index');

// ── API REST Estudiantes ──────────────────────────────────────
$router->add('GET',    '/api/estudiantes',         'EstudianteController', 'apiList');
$router->add('GET',    '/api/estudiantes/{id}',    'EstudianteController', 'apiGet');
$router->add('POST',   '/api/estudiantes',         'EstudianteController', 'apiCreate');
$router->add('PUT',    '/api/estudiantes/{id}',    'EstudianteController', 'apiUpdate');
$router->add('DELETE', '/api/estudiantes/{id}',   'EstudianteController', 'apiDelete');

// ── Generación PDF ────────────────────────────────────────────
// Acta general NO necesita ID de estudiante
$router->add('GET',    '/pdf/acta_general',        'PdfController',        'generarActaGeneral');
// Documentos individuales: diploma, acta_individual, mencion
$router->add('GET',    '/pdf/{id}/{tipo}',          'PdfController',        'generar');

// ── Upload logo ───────────────────────────────────────────────
$router->add('POST',   '/api/upload-logo',         'UploadController',     'logo');

// ── Ruta de diagnóstico (desactivar en producción) ─────────────
if (DEBUG) {
    $router->add('GET', '/debug', 'EstudianteController', 'debug');
}

// ── Dispatch ──────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri    = $_SERVER['REQUEST_URI']    ?? '/';
$router->dispatch($method, $uri);
