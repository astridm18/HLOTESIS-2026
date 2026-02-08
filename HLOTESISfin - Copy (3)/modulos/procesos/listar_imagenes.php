<?php
// modulos/procesos/listar_imagenes.php
require_once '../../config/forms/database.php';
require_once '../../config/forms/functions.php';

verificarSesion();

$id_caso = $_GET['caso'] ?? 0;
$estado_filtro = $_GET['estado'] ?? ''; // Nuevo filtro por estado

if (!$id_caso) {
    respuestaJSON('error', 'ID de caso requerido');
}

try {
    $sql = "
        SELECT 
            i.*,
            CONCAT(p.nombres, ' ', p.apellidos) as radiologo,
            om.descripcion as orden_descripcion,
            CONCAT(mp.nombres, ' ', mp.apellidos) as medico_solicita,
            DATE_FORMAT(i.fecha_solicitud, '%d/%m/%Y %H:%i') as fecha_solicitud_formato,
            om.urgente as urgente_orden_medica, -- Añadir esta columna para usar el estado de urgencia de la orden
            om.estado as estado_orden_medica -- Añadir esta columna para usar el estado de la orden
        FROM imagenes i
        LEFT JOIN personal p ON i.id_radiologo = p.id
        LEFT JOIN ordenes_medicas om ON i.id_orden = om.id_orden
        LEFT JOIN personal mp ON om.id_medico_ordena = mp.id
        WHERE i.id_caso = ?
    ";

    $params = [$id_caso];

    // FILTRO POR ESTADO SI SE ESPECIFICA
    if (!empty($estado_filtro) && $estado_filtro !== 'todos') {
        // Ajustamos el filtro para usar el estado de la tabla `imagenes`
        $sql .= " AND i.estado = ?";
        $params[] = $estado_filtro;
    }

    $sql .= " ORDER BY i.fecha_solicitud DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $imagenes = $stmt->fetchAll();

    // Formatear la fecha de solicitud para la visualización
    foreach ($imagenes as &$imagen) {
        $imagen['fecha_solicitud'] = $imagen['fecha_solicitud_formato'];
        unset($imagen['fecha_solicitud_formato']); // Eliminar el campo temporal
        // También puedes usar urgente_orden_medica si prefieres la de la orden
        // $imagen['urgente'] = $imagen['urgente_orden_medica']; 
        // unset($imagen['urgente_orden_medica']);
    }

    respuestaJSON('ok', 'Estudios de imagen obtenidos', $imagenes);

} catch (Exception $e) {
    error_log("Error en listar_imagenes.php: " . $e->getMessage());
    respuestaJSON('error', 'Error al obtener estudios de imagen: ' . $e->getMessage());
}
?>