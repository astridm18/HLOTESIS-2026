<?php
// modulos/procesos/listar_signos.php
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
            sv.*,
            CONCAT(p.nombres, ' ', p.apellidos) as enfermero,
            DATE_FORMAT(sv.fecha_registro, '%d/%m/%Y %H:%i') as fecha_registro_format
        FROM signos_vitales sv
        INNER JOIN personal p ON sv.id_enfermero = p.id
        WHERE sv.id_caso = ?
        ORDER BY sv.fecha_registro DESC
        LIMIT 20
    ");

    $stmt->execute([$id_caso]);
    $signos = $stmt->fetchAll();

    respuestaJSON('ok', 'Signos vitales obtenidos', $signos);

} catch (Exception $e) {
    respuestaJSON('error', 'Error al obtener signos vitales: ' . $e->getMessage());
}
?>