<?php
// modulos/procesos/listar_laboratorios.php
require_once '../../config/database.php';
require_once '../../config/functions.php';

verificarSesion();

$id_caso = $_GET['caso'] ?? 0;
$estado_filtro = $_GET['estado'] ?? '';

if (!$id_caso) {
    respuestaJSON('error', 'ID de caso requerido');
}

try {
    // 🆕 CONSULTA CORREGIDA - El estado viene de las órdenes médicas
    $sql = "
        SELECT 
            l.*,
            CONCAT(p.nombres, ' ', p.apellidos) as laboratorista,
            om.descripcion as orden_descripcion,
            om.urgente,
            om.estado as estado_orden,
            om.instrucciones_especiales as indicaciones,
            CONCAT(mp.nombres, ' ', mp.apellidos) as medico_solicita,
            mp.especialidad as especialidad_medico,
            DATE_FORMAT(l.fecha_solicitud, '%d/%m/%Y %H:%i') as fecha_solicitud,
            DATE_FORMAT(l.fecha_resultado, '%d/%m/%Y %H:%i') as fecha_resultado,
            CASE 
                WHEN l.fecha_resultado IS NOT NULL THEN 'informado'
                WHEN om.estado = 'en_proceso' THEN 'en_proceso'
                WHEN om.estado = 'completada' THEN 'informado'
                ELSE 'solicitado'
            END as estado_lab
        FROM laboratorios l
        LEFT JOIN personal p ON l.id_laboratorista = p.id
        INNER JOIN ordenes_medicas om ON l.id_orden = om.id_orden
        LEFT JOIN personal mp ON om.id_medico_ordena = mp.id
        WHERE l.id_caso = ?
    ";

    $params = [$id_caso];

    // 🆕 FILTRO POR ESTADO SI SE ESPECIFICA
    if (!empty($estado_filtro) && $estado_filtro !== 'todos') {
        if ($estado_filtro === 'solicitado') {
            $sql .= " AND om.estado = 'pendiente' AND l.fecha_resultado IS NULL";
        } elseif ($estado_filtro === 'informado') {
            $sql .= " AND l.fecha_resultado IS NOT NULL";
        }
    }

    $sql .= " ORDER BY l.fecha_solicitud DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $laboratorios = $stmt->fetchAll();

    // 🆕 AGREGAR ESTADÍSTICAS CORREGIDAS
    $stmt_stats = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN om.estado = 'pendiente' AND l.fecha_resultado IS NULL THEN 1 ELSE 0 END) as pendientes,
            SUM(CASE WHEN l.fecha_resultado IS NOT NULL THEN 1 ELSE 0 END) as informados,
            SUM(CASE WHEN om.urgente = 1 THEN 1 ELSE 0 END) as urgentes
        FROM laboratorios l
        INNER JOIN ordenes_medicas om ON l.id_orden = om.id_orden
        WHERE l.id_caso = ?
    ");
    $stmt_stats->execute([$id_caso]);
    $estadisticas = $stmt_stats->fetch();

    respuestaJSON('ok', 'Laboratorios obtenidos exitosamente', $laboratorios);

} catch (Exception $e) {
    error_log("Error en listar_laboratorios.php: " . $e->getMessage());
    respuestaJSON('error', 'Error al obtener laboratorios: ' . $e->getMessage());
}
?>