<?php
require_once '../../config/database.php';
require_once '../../config/functions.php';

verificarSesion();

$tipo = $_GET['tipo'] ?? 'todos';

try {
    $personal = obtener_personal_bd($pdo, $tipo);

    // Solo desarrollo: si no hay personal médico, crear uno temporal
    if (empty($personal) && in_array(strtolower($tipo), ['medico', 'medicina', 'todos'], true)) {
        $pdo->prepare("
            INSERT INTO personal (nombres, apellidos, cedula, rol, especialidad, activo, fecha_registro)
            VALUES ('Dr. Sistema', 'Temporal', '0000000000', 'medico', 'Medicina General', 1, NOW())
            ON DUPLICATE KEY UPDATE activo = 1
        ")->execute();
        $personal = obtener_personal_bd($pdo, $tipo);
    }

    respuestaJSON('ok', 'Personal obtenido', $personal);
} catch (Exception $e) {
    respuestaJSON('error', 'Error al obtener personal: ' . $e->getMessage());
}
