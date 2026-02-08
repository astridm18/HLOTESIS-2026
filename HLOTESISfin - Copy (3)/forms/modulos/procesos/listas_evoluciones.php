<?php
// modulos/procesos/listar_evoluciones.php
require_once '../../config/database.php';
require_once '../../config/functions.php';

verificarSesion();

$id_caso = $_GET['caso'] ?? 0;

if (!$id_caso) {
    respuestaJSON('error', 'ID de caso requerido');
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            e.*,
            CONCAT(p.nombres, ' ', p.apellidos) as medico,
            p.especialidad
        FROM evoluciones e
        INNER JOIN personal p ON e.id_medico = p.id
        WHERE e.id_caso = ?
        ORDER BY e.fecha_evolucion DESC
    ");

    $stmt->execute([$id_caso]);
    $evoluciones = $stmt->fetchAll();

    // Formatear datos para el frontend
    foreach ($evoluciones as &$evolucion) {
        $evolucion['fecha_evolucion'] = date('Y-m-d H:i:s', strtotime($evolucion['fecha_evolucion']));
    }

    respuestaJSON('ok', 'Evoluciones obtenidas', $evoluciones);

} catch (Exception $e) {
    respuestaJSON('error', 'Error al obtener evoluciones: ' . $e->getMessage());
}
?>