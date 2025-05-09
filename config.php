<?php
// Verificar si la sesión ya está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración de la aplicación
define('APP_NAME', 'Sistema Restaurante');
define('APP_VERSION', '1.0.0');
define('BASE_URL', '/restauranteBD/');
//define('BASE_URL', 'http://localhost/restauranteBD/');

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'restaurantebd');
define('DB_USER', 'root');
define('DB_PASS', 'WILLY1994');
define('DB_CHARSET', 'utf8mb4');

// Nivel de reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Crear directorio de logs si no existe
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

// Configuración de zona horaria
date_default_timezone_set('America/Lima');

// Otras constantes útiles
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', dirname(__DIR__) . DS);