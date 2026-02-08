<?php
// modulos/procesos/listar_evoluciones.php
require_once '../../config/database.php';
require_once '../../config/functions.php';

verificarSesion();

$id_caso = $_GET['caso'] ?? 0;
$tipo_filtro = $_GET['tipo'] ?? '';

if (!$id_caso) {
    respuestaJSON('error', 'ID de caso requerido');
}

try {
    $sql = "
        SELECT 
            e.*,
            CONCAT(p.nombres, ' ', p.apellidos) as medico,
            p.especialidad,
            DATE_FORMAT(e.fecha_evolucion, '%d/%m/%Y %H:%i') as fecha_evolucion
        FROM evoluciones e
        INNER JOIN personal p ON e.id_medico = p.id
        WHERE e.id_caso = ?
    ";

    $params = [$id_caso];

    // Filtro por tipo si se especifica
    if (!empty($tipo_filtro) && $tipo_filtro !== 'todas') {
        $sql .= " AND e.tipo_evolucion = ?";
        $params[] = $tipo_filtro;
    }

    $sql .= " ORDER BY e.fecha_evolucion DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $evoluciones = $stmt->fetchAll();

    respuestaJSON('ok', 'Evoluciones obtenidas exitosamente', $evoluciones);

} catch (Exception $e) {
    error_log("Error en listar_evoluciones.php: " . $e->getMessage());
    respuestaJSON('error', 'Error al obtener evoluciones: ' . $e->getMessage());
}
?>