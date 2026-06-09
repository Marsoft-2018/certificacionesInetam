<?php
// test.php  –  Página de diagnóstico (ELIMINAR en producción)
// Acceder en: http://localhost/certificados/public/test.php

echo "<h2>Diagnóstico INETAM</h2><pre>";

echo "SCRIPT_NAME:  " . ($_SERVER['SCRIPT_NAME'] ?? '—') . "\n";
echo "REQUEST_URI:  " . ($_SERVER['REQUEST_URI'] ?? '—') . "\n";
echo "PHP_SELF:     " . ($_SERVER['PHP_SELF'] ?? '—') . "\n";
echo "Script dir:   " . dirname($_SERVER['SCRIPT_NAME']) . "\n\n";

$configFile = dirname(__DIR__) . '/config/config.php';
echo "config.php exists: " . (file_exists($configFile) ? 'SÍ' : 'NO') . "\n";

require_once $configFile;
echo "BASE_URL:     " . BASE_URL . "\n";
echo "BASE_PATH:    " . BASE_PATH . "\n\n";

$vendorOk = file_exists(BASE_PATH . '/vendor/autoload.php');
echo "vendor/autoload.php: " . ($vendorOk ? 'SÍ (composer install OK)' : 'NO → ejecuta: composer install') . "\n\n";

// Test DB
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $count = $pdo->query("SELECT COUNT(*) FROM estudiantes")->fetchColumn();
    echo "Base de datos: ✅ Conectada | Estudiantes registrados: $count\n";
} catch (PDOException $e) {
    echo "Base de datos: ❌ Error → " . $e->getMessage() . "\n";
    echo "→ Verifica DB_HOST, DB_NAME, DB_USER, DB_PASS en config/config.php\n";
    echo "→ Importa: database/schema.sql\n";
}
echo "</pre>";
