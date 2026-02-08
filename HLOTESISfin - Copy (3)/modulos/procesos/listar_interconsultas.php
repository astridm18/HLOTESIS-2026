<?php
// modulos/procesos/listar_interconsultas.php
require_once '../../config/forms/database.php';
require_once '../../config/forms/functions.php';

verificarSesion();

$id_caso = $_GET['caso'] ?? 0;
$estado_filtro = $_GET['estado'] ?? ''; // Filtro por estado
if ($estado_filtro === 'todos') {
    $estado_filtro = ''; // Si es 'todos', no aplicar filtro
}

if (!$id_caso) {
    respuestaJSON('error', 'ID de caso requerido');
}

try {
    $sql = "
        SELECT 
            ic.*,
            CONCAT(ms.nombres, ' ', ms.apellidos) as medico_solicitante,
            ms.especialidad as especialidad_solicitante_medico,
            CONCAT(mc.nombres, ' ', mc.apellidos) as medico_consultor,
            DATE_FORMAT(ic.fecha_solicitud, '%d/%m/%Y %H:%i') as fecha_solicitud_formato,
            DATE_FORMAT(ic.fecha_respuesta, '%d/%m/%Y %H:%i') as fecha_respuesta_formato
        FROM interconsultas ic
        INNER JOIN personal ms ON ic.id_medico_solicitante = ms.id
        LEFT JOIN personal mc ON ic.id_medico_consultor = mc.id
        WHERE ic.id_caso = ?
    ";

    $params = [$id_caso];

    // Aplicar filtro por estado si no es 'todos' y no está vacío
    if (!empty($estado_filtro)) {
        $sql .= " AND ic.estado = ?";
        $params[] = $estado_filtro;
    }

    $sql .= " ORDER BY ic.fecha_solicitud DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $interconsultas = $stmt->fetchAll();

    // Formatear las fechas para la visualización
    foreach ($interconsultas as &$ic) {
        $ic['fecha_solicitud'] = $ic['fecha_solicitud_formato'];
        unset($ic['fecha_solicitud_formato']);
        if ($ic['fecha_respuesta_formato']) {
            $ic['fecha_respuesta'] = $ic['fecha_respuesta_formato'];
        } else {
            $ic['fecha_respuesta'] = null; // Para asegurar que sea null si no hay fecha
        }
        unset($ic['fecha_respuesta_formato']);
    }

    respuestaJSON('ok', 'Interconsultas obtenidas exitosamente', $interconsultas);

} catch (Exception $e) {
    error_log("Error en listar_interconsultas.php: " . $e->getMessage()); // Para depuración
    respuestaJSON('error', 'Error al obtener interconsultas: ' . $e->getMessage());
}
?>