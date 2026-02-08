<?php
// modulos/procesos/listar_procedimientos.php
require_once '../../config/database.php';
require_once '../../config/functions.php';

verificarSesion();

$id_caso = $_GET['caso'] ?? 0;
if (!$id_caso) {
    respuestaJSON('error', 'Caso no especificado');
}

try {
    $stmt = $pdo->prepare("
        SELECT pr.*, 
               CONCAT(mp.nombres, ' ', mp.apellidos) as medico_principal,
               CONCAT(ma.nombres, ' ', ma.apellidos) as medico_asistente,
               CONCAT(en.nombres, ' ', en.apellidos) as enfermero,
               DATE_FORMAT(pr.fecha_procedimiento, '%d/%m/%Y %H:%i') as fecha_procedimiento_formato,
               om.estado as estado_orden_medica, -- Añadimos el estado de la orden médica
               om.fecha_orden -- Añadimos la fecha de la orden médica si se quiere mostrar
        FROM procedimientos pr
        INNER JOIN personal mp ON pr.id_medico_principal = mp.id
        LEFT JOIN personal ma ON pr.id_medico_asistente = ma.id
        LEFT JOIN personal en ON pr.id_enfermero = en.id
        LEFT JOIN ordenes_medicas om ON pr.id_orden = om.id_orden -- Unimos con ordenes_medicas
        WHERE pr.id_caso = ?
        ORDER BY pr.fecha_procedimiento DESC
    ");

    $stmt->execute([$id_caso]);
    $procedimientos = $stmt->fetchAll();

    foreach ($procedimientos as &$procedimiento) {
        $procedimiento['fecha_procedimiento'] = $procedimiento['fecha_procedimiento_formato'];
        unset($procedimiento['fecha_procedimiento_formato']);
        // Puedes añadir aquí lógica para mostrar el estado de la orden si lo consideras relevante en la interfaz
        // Por ejemplo: $procedimiento['estado_procedimiento_orden'] = $procedimiento['estado_orden_medica'];
    }

    respuestaJSON('ok', 'Procedimientos obtenidos', $procedimientos);

} catch (Exception $e) {
    error_log("Error al obtener procedimientos: " . $e->getMessage()); // Para depuración
    respuestaJSON('error', 'Error al obtener procedimientos: ' . $e->getMessage());
}
?>