<?php
// modulos/procesos/listar_ordenes.php
require_once '../../config/forms/database.php';
require_once '../../config/forms/functions.php';

verificarSesion();

$id_caso = $_GET['caso'] ?? 0;

if (!$id_caso) {
    respuestaJSON('error', 'ID de caso requerido');
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            om.*,
            CONCAT(p.nombres, ' ', p.apellidos) as medico,
            p.especialidad,
            CONCAT(pe.nombres, ' ', pe.apellidos) as ejecutor
        FROM ordenes_medicas om
        INNER JOIN personal p ON om.id_medico_ordena = p.id
        LEFT JOIN personal pe ON om.id_personal_ejecuta = pe.id
        WHERE om.id_caso = ?
        ORDER BY om.fecha_orden DESC
    ");

    $stmt->execute([$id_caso]);
    $ordenes = $stmt->fetchAll();

    // Formatear la fecha de orden y ejecución/cancelación para la visualización
    foreach ($ordenes as &$orden) {
        $orden['fecha_orden'] = date('d/m/Y H:i', strtotime($orden['fecha_orden']));
        if ($orden['fecha_ejecucion']) {
            $orden['fecha_ejecucion'] = date('d/m/Y H:i', strtotime($orden['fecha_ejecucion']));
        }
        if ($orden['fecha_cancelacion']) {
            $orden['fecha_cancelacion'] = date('d/m/Y H:i', strtotime($orden['fecha_cancelacion']));
        }
    }


    respuestaJSON('ok', 'Órdenes médicas obtenidas', $ordenes);

} catch (Exception $e) {
    error_log("Error al obtener órdenes médicas: " . $e->getMessage()); // Para depuración
    respuestaJSON('error', 'Error al obtener órdenes médicas: ' . $e->getMessage());
}
?>