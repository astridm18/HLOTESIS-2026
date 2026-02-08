<?php
// modulos/procesos/solicitar_interconsulta.php
require_once '../../config/database.php';
require_once '../../config/functions.php';

verificarSesion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = sanitizar($_POST);

    // CAMPOS REQUERIDOS
    $campos_requeridos = ['id_caso', 'id_medico_solicitante', 'servicio_solicitante', 'especialidad', 'motivo_consulta'];
    foreach ($campos_requeridos as $campo) {
        if (empty($datos[$campo])) {
            respuestaJSON('error', "El campo '$campo' es requerido.");
        }
    }

    try {
        // VALIDAR QUE EL MÉDICO SOLICITANTE EXISTE Y ESTÁ ACTIVO
        $stmt_medico = $pdo->prepare("
            SELECT id, CONCAT(nombres, ' ', apellidos) as nombre_completo 
            FROM personal 
            WHERE id = ? AND rol = 'Medicina' AND activo = 1
        ");
        $stmt_medico->execute([$datos['id_medico_solicitante']]);
        $medico_solicitante = $stmt_medico->fetch();

        if (!$medico_solicitante) {
            respuestaJSON('error', 'El Médico Solicitante seleccionado no es válido o no está activo.');
        }

        $pdo->beginTransaction();

        // 1. Crear Orden Médica para la interconsulta
        // El estado de la orden médica para una interconsulta será 'pendiente'
        $stmt_orden = $pdo->prepare("
            INSERT INTO ordenes_medicas 
            (id_caso, tipo_orden, descripcion, instrucciones_especiales, urgente, estado, id_medico_ordena)
            VALUES (?, 'interconsulta', ?, ?, ?, 'pendiente', ?)
        ");

        $descripcion_orden = "Interconsulta a {$datos['especialidad']} por: {$datos['motivo_consulta']}";
        $instrucciones_orden = $datos['motivo_consulta'];
        $urgente_orden = isset($datos['urgente']) ? 1 : 0;

        $stmt_orden->execute([
            $datos['id_caso'],
            $descripcion_orden,
            $instrucciones_orden,
            $urgente_orden,
            $datos['id_medico_solicitante'] // Usar el ID del médico seleccionado en el formulario
        ]);

        $id_orden = $pdo->lastInsertId();

        // 2. Insertar en la tabla interconsultas
        // NOTA: La tabla `interconsultas` en tu BDD actual no tiene una columna `id_orden`.
        // Si necesitas enlazar directamente la interconsulta con la orden, deberás añadirla a la tabla `interconsultas`.
        // Por ahora, se crea la interconsulta y la orden de forma independiente pero con el mismo id_caso y médico.
        $stmt_interconsulta = $pdo->prepare("
            INSERT INTO interconsultas 
            (id_caso, fecha_solicitud, servicio_solicitante, servicio_consultado, especialidad, motivo_consulta, urgente, estado, id_medico_solicitante)
            VALUES (?, NOW(), ?, ?, ?, ?, ?, 'solicitada', ?)
        ");
        // 'servicio_consultado' lo tomaremos como la 'especialidad' de la solicitud si no hay un campo específico.
        // Si tienes una lista predefinida de servicios consultados, podrías ajustarlo.

        $stmt_interconsulta->execute([
            $datos['id_caso'],
            $datos['servicio_solicitante'],
            $datos['especialidad'], // Usamos la especialidad como servicio_consultado
            $datos['especialidad'],
            $datos['motivo_consulta'],
            $urgente_orden,
            $datos['id_medico_solicitante'] // Usar el ID del médico seleccionado en el formulario
        ]);

        $id_interconsulta = $pdo->lastInsertId();

        // Registro de Auditoría
        registrarAuditoria($pdo, 'interconsultas', $id_interconsulta, 'INSERT', null, [
            'caso' => $datos['id_caso'],
            'medico_solicitante' => $medico_solicitante['nombre_completo'],
            'especialidad_consultada' => $datos['especialidad'],
            'id_orden_medica' => $id_orden // Referencia a la orden médica
        ]);

        $pdo->commit();

        respuestaJSON('ok', 'Interconsulta solicitada exitosamente', [
            'id_interconsulta' => $id_interconsulta,
            'id_orden' => $id_orden,
            'medico' => $medico_solicitante['nombre_completo']
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error al solicitar interconsulta: " . $e->getMessage()); // Para depuración
        respuestaJSON('error', 'Error al solicitar interconsulta: ' . $e->getMessage());
    }
} else {
    respuestaJSON('error', 'Método no permitido');
}
?>