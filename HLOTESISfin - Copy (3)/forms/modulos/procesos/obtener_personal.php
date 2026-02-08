<?php
require_once '../../config/database.php';
require_once '../../config/functions.php';

verificarSesion();

$tipo = $_GET['tipo'] ?? 'todos';

try {
    $personal = obtener_personal_bd($pdo, $tipo);
    respuestaJSON('ok', 'Personal obtenido exitosamente', $personal);
} catch (Exception $e) {
    respuestaJSON('error', 'Error al obtener personal: ' . $e->getMessage());
}
