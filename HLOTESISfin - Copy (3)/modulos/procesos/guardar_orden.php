<?php
// modulos/procesos/guardar_orden.php
require_once '../../config/forms/database.php';
require_once '../../config/forms/functions.php';

verificarSesion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = sanitizar($_POST);

    // CAMPOS REQUERIDOS ACTUALIZADOS: id_medico_ordena
    $campos_requeridos = ['id_caso', 'tipo_orden', 'descripcion', 'id_medico_ordena'];
    foreach ($campos_requeridos as $campo) {
        if (empty($datos[$campo])) {
            respuestaJSON('error', "El campo '$campo' es requerido.");
        }
    }

    try {
        // VALIDAR QUE EL MÉDICO QUE ORDENA EXISTE Y ESTÁ ACTIVO
        $stmt_medico = $pdo->prepare("
            SELECT id, CONCAT(nombres, ' ', apellidos) as nombre_completo 
            FROM personal 
            WHERE id = ? AND rol = 'Medicina' AND activo = 1
        ");
        $stmt_medico->execute([$datos['id_medico_ordena']]);
        $medico_ordena = $stmt_medico->fetch();

        if (!$medico_ordena) {
            respuestaJSON('error', 'El Médico que Ordena seleccionado no es válido o no está activo.');
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO ordenes_medicas 
            (id_caso, tipo_orden, descripcion, instrucciones_especiales, 
             urgente, estado, id_medico_ordena)
            VALUES (?, ?, ?, ?, ?, 'pendiente', ?)
        ");

        $stmt->execute([
            $datos['id_caso'],
            $datos['tipo_orden'],
            $datos['descripcion'],
            $datos['instrucciones_especiales'] ?? null,
            isset($datos['urgente']) ? 1 : 0,
            // 'pendiente', // Ya está definido en el SQL
            $datos['id_medico_ordena'] // Usar el ID del médico seleccionado en el formulario
        ]);

        $id_orden = $pdo->lastInsertId();

        // Registrar auditoría
        registrarAuditoria($pdo, 'ordenes_medicas', $id_orden, 'INSERT', null, [
            'caso' => $datos['id_caso'],
            'tipo_orden' => $datos['tipo_orden'],
            'descripcion' => $datos['descripcion'],
            'medico_ordena' => $medico_ordena['nombre_completo']
        ]);

        // Si es urgente, crear notificación (esta función ya estaba y se mantiene)
        if (isset($datos['urgente']) && $datos['urgente'] == 1) { // Asegurarse de que el valor sea '1' si el checkbox está marcado
            crearNotificacionUrgente($pdo, $datos['id_caso'], $id_orden, $datos['tipo_orden']);
        }

        $pdo->commit();

        respuestaJSON('ok', 'Orden médica creada exitosamente', [
            'id_orden' => $id_orden
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error al crear orden: " . $e->getMessage()); // Para depuración
        respuestaJSON('error', 'Error al crear orden: ' . $e->getMessage());
    }
} else {
    respuestaJSON('error', 'Método no permitido');
}

// La función crearNotificacionUrgente se mantiene tal cual
function crearNotificacionUrgente($pdo, $id_caso, $id_orden, $tipo_orden)
{
    try {
        // Notificar a enfermería si es medicamento o cuidados
        if (in_array($tipo_orden, ['medicamento', 'cuidados'])) {
            $stmt = $pdo->prepare("
                SELECT id FROM personal 
                WHERE rol IN ('enfermero', 'Enfermeria') AND activo = 1
            ");
            $stmt->execute();
            $enfermeros = $stmt->fetchAll();

            foreach ($enfermeros as $enfermero) {
                // Aquí podrías enviar notificaciones push, emails, etc.
                error_log("Notificación urgente enviada a enfermero ID: " . $enfermero['id']);
            }
        }
    } catch (Exception $e) {
        error_log("Error enviando notificación urgente: " . $e->getMessage());
    }
}
?>