<?php
/**
 * Devuelve la conexión MySQLi. Usa la configuración centralizada (config/app.php).
 * Si un script necesita $conn, puede usar include 'conexion.php' o getConnection().
 */
require_once __DIR__ . '/../config/app.php';

function getConnection()
{
    global $db_host, $db_user, $db_pass, $db_name;
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }
    return $conn;
}
