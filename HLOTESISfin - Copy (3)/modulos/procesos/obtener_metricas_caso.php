<?php
// modulos/procesos/obtener_metricas_caso.php
require_once '../../config/forms/database.php';
require_once '../../config/forms/functions.php';

verificarSesion();

$id_caso = $_GET['caso'] ?? 0;

if (!$id_caso) {
    respuestaJSON('error', 'ID de caso requerido');
}

try {
    // Última evolución
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(fecha_evolucion, '%d/%m %H:%i') as fecha_ultima
        FROM evoluciones 
        WHERE id_caso = ? 
        ORDER BY fecha_evolucion DESC 
        LIMIT 1
    ");
    $stmt->execute([$id_caso]);
    $ultima_evolucion = $stmt->fetchColumn() ?: 'Sin registro';

    // Últimos signos vitales
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(fecha_registro, '%d/%m %H:%i') as fecha_ultimo
        FROM signos_vitales 
        WHERE id_caso = ? 
        ORDER BY fecha_registro DESC 
        LIMIT 1
    ");
    $stmt->execute([$id_caso]);
    $ultimos_signos = $stmt->fetchColumn() ?: 'Sin registro';

    // Órdenes activas (pendientes + en proceso)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM ordenes_medicas 
        WHERE id_caso = ? 
        AND estado IN ('pendiente', 'en_proceso')
    ");
    $stmt->execute([$id_caso]);
    $ordenes_activas = $stmt->fetchColumn() ?: 0;

    // Exámenes pendientes (laboratorio + imágenes sin resultado)
    $stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM laboratorios WHERE id_caso = ? AND fecha_resultado IS NULL) +
            (SELECT COUNT(*) FROM imagenes WHERE id_caso = ? AND estado IN ('solicitado', 'en_proceso')) 
            as total
    ");
    $stmt->execute([$id_caso, $id_caso]);
    $examenes_pendientes = $stmt->fetchColumn() ?: 0;

    respuestaJSON('ok', 'Métricas obtenidas', [
        'ultima_evolucion' => $ultima_evolucion,
        'ultimos_signos' => $ultimos_signos,
        'ordenes_activas' => $ordenes_activas,
        'examenes_pendientes' => $examenes_pendientes
    ]);

} catch (Exception $e) {
    respuestaJSON('error', 'Error al obtener métricas: ' . $e->getMessage());
}
?>