<?php
// modulos/procesos/listar_notas_enfermeria.php
require_once '../../config/database.php';
require_once '../../config/functions.php';

verificarSesion();

$id_caso = $_GET['caso'] ?? 0;
$turno = $_GET['turno'] ?? '';

if (!$id_caso) {
    respuestaJSON('error', 'Caso no especificado');
}

try {
    $sql = "
        SELECT 
            ne.*, 
            CONCAT(p.nombres, ' ', p.apellidos) as enfermero,
            DATE_FORMAT(ne.fecha_nota, '%d/%m/%Y %H:%i') as fecha_nota
        FROM notas_enfermeria ne
        INNER JOIN personal p ON ne.id_enfermero = p.id
        WHERE ne.id_caso = ?
    ";

    $params = [$id_caso];

    // Filtro por turno si se especifica
    if (!empty($turno) && $turno !== 'todas') {
        $sql .= " AND ne.turno = ?";
        $params[] = $turno;
    }

    $sql .= " ORDER BY ne.fecha_nota DESC LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $notas = $stmt->fetchAll();

    respuestaJSON('ok', 'Notas obtenidas exitosamente', $notas);

} catch (Exception $e) {
    error_log("Error en listar_notas_enfermeria.php: " . $e->getMessage());
    respuestaJSON('error', 'Error al obtener notas: ' . $e->getMessage());
}
?>