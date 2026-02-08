<?php
/**
 * Carga la configuración de la aplicación (credenciales BD).
 * Las credenciales reales están en config.local.php (no se sube al repositorio).
 */
$config_local = __DIR__ . '/config.local.php';
if (!is_file($config_local)) {
    http_response_code(500);
    die(
        '<p><strong>Configuración requerida</strong></p>' .
        '<p>Copia <code>config/config.local.php.example</code> a <code>config/config.local.php</code> ' .
        'y define las credenciales de la base de datos.</p>'
    );
}
require $config_local;

// Entorno: 'prod' o 'dev'. En dev se permite session_temporal y otras herramientas de desarrollo.
if (!defined('APP_ENV')) {
    define('APP_ENV', isset($app_env) ? $app_env : 'prod');
}
