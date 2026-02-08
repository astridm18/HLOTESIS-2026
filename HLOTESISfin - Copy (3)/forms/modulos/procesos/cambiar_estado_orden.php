<?php
// modulos/procesos/cambiar_estado_orden.php
require_once '../../config/database.php';
require_once '../../config/functions.php';

verificarSesion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = sanitizar($_POST);
    
    $campos_requeridos = ['id_orden', 'estado'];
    foreach ($campos_requeridos as $campo) {
        if (empty($datos[$campo])) {
            respuestaJSON('error', "El campo $campo es requerido");
        }
    }
    
    try {
        $id_orden = $datos['id_orden'];
        $nuevo_estado = $datos['estado'];
        $observaciones = $datos['observaciones_ejecucion'] ?? null;
        $motivo_cancelacion = $datos['motivo_cancelacion'] ?? null;
        
        // Preparar campos a actualizar
        $campos_actualizar = ['estado = ?'];
        $valores = [$nuevo_estado];
        
        if ($nuevo_estado === 'completada') {
            $campos_actualizar[] = 'fecha_ejecucion = NOW()';
            $campos_actualizar[] = 'id_personal_ejecuta = ?';
            $campos_actualizar[] = 'observaciones_ejecucion = ?';
            $valores[] = obtenerUsuarioActual()['id'];
            $valores[] = $observaciones;
        } elseif ($nuevo_estado === 'cancelada') {
            $campos_actualizar[] = 'fecha_cancelacion = NOW()';
            $campos_actualizar[] = 'motivo_cancelacion = ?';
            $valores[] = $motivo_cancelacion;
        } elseif ($nuevo_estado === 'en_proceso') {
            $campos_actualizar[] = 'id_personal_ejecuta = ?';
            $valores[] = obtenerUsuarioActual()['id'];
        }
        
        $valores[] = $id_orden;
        
        $stmt = $pdo->prepare("
            UPDATE ordenes_medicas 
            SET " . implode(', ', $campos_actualizar) . "
            WHERE id_orden = ?
        ");
        
        $stmt->execute($valores);
        
        // Registrar auditoría
        registrarAuditoria($pdo, 'ordenes_medicas', $id_orden, 'UPDATE', 
                          ['estado_anterior'], ['estado' => $nuevo_estado]);
        
        respuestaJSON('ok', 'Estado actualizado exitosamente');
        
    } catch(Exception $e) {
        respuestaJSON('error', 'Error al actualizar estado: ' . $e->getMessage());
    }
}
?>