<?php
/**
 * Conexión a la base de datos (MySQLi). Credenciales en config/config.local.php.
 */
require_once __DIR__ . '/config/app.php';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}
