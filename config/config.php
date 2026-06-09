<?php
// ============================================================
// config/config.php  –  Configuración global de la aplicación
// ============================================================

// ── URL Base ─────────────────────────────────────────────────
// Ajusta según donde esté alojado el proyecto:
//   XAMPP raíz:       'http://localhost/certificados'
//   XAMPP en public:  'http://localhost/certificados/public'
//   Dominio propio:   'https://midominio.com'
define('BASE_URL', 'http://localhost/certificados');

define('APP_NAME',    'INETAM – Certificados y Diplomas');
define('APP_VERSION', '1.0.0');
define('BASE_PATH',   dirname(__DIR__));

// ── Base de datos ─────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'inetam_certificados');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// ── Rutas internas ────────────────────────────────────────────
define('VIEWS_PATH',       BASE_PATH . '/app/Views');
define('CONTROLLERS_PATH', BASE_PATH . '/app/Controllers');
define('MODELS_PATH',      BASE_PATH . '/app/Models');
define('ASSETS_PATH',      BASE_PATH . '/public/assets');

// ── Vendor / DOMPDF ───────────────────────────────────────────
define('VENDOR_PATH', BASE_PATH . '/vendor');

// ── Zona horaria ──────────────────────────────────────────────
date_default_timezone_set('America/Bogota');

// ── Modo debug (false en producción) ─────────────────────────
define('DEBUG', true);

if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
