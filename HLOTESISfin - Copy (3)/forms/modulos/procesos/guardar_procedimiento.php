<?php
// modulos/procesos/guardar_procedimiento.php
require_once '../../config/database.php';
require_once '../../config/functions.php';

verificarSesion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = sanitizar($_POST);

    // CAMPOS REQUERIDOS ACTUALIZADOS: id_medico_principal
    $campos_requeridos = ['id_caso', 'tipo_procedimiento', 'descripcion', 'id_medico_principal'];
    foreach ($campos_requeridos as $campo) {
        if (empty($datos[$campo])) {
            respuestaJSON('error', "El campo $campo es requerido");
        }
    }

    try {
        // VALIDAR QUE EL MÉDICO PRINCIPAL EXISTE Y ESTÁ ACTIVO
        $stmt = $pdo->prepare("
            SELECT id, CONCAT(nombres, ' ', apellidos) as nombre_completo 
            FROM personal 
            WHERE id = ? AND rol = 'Medicina' AND activo = 1
        ");
        $stmt->execute([$datos['id_medico_principal']]);
        $medico_principal = $stmt->fetch();

        if (!$medico_principal) {
            respuestaJSON('error', 'El Médico Principal seleccionado no es válido o no está activo');
        }

        // Validar Médico Asistente si fue seleccionado
        if (!empty($datos['id_medico_asistente'])) {
            $stmt = $pdo->prepare("
                SELECT id 
                FROM personal 
                WHERE id = ? AND rol = 'Medicina' AND activo = 1
            ");
            $stmt->execute([$datos['id_medico_asistente']]);
            if (!$stmt->fetch()) {
                respuestaJSON('error', 'El Médico Asistente seleccionado no es válido o no está activo');
            }
        }

        // Validar Enfermero si fue seleccionado
        if (!empty($datos['id_enfermero'])) {
            $stmt = $pdo->prepare("
                SELECT id 
                FROM personal 
                WHERE id = ? AND rol = 'Enfermería' AND activo = 1
            ");
            $stmt->execute([$datos['id_enfermero']]);
            if (!$stmt->fetch()) {
                respuestaJSON('error', 'El Enfermero seleccionado no es válido o no está activo');
            }
        }

        $pdo->beginTransaction();

        // 1. Crear Orden Médica asociada al procedimiento
        $stmt_orden = $pdo->prepare("
            INSERT INTO ordenes_medicas 
            (id_caso, tipo_orden, descripcion, instrucciones_especiales, urgente, estado, id_medico_ordena)
            VALUES (?, 'procedimiento', ?, ?, ?, 'pendiente', ?)
        ");

        $descripcion_orden = "Procedimiento: {$datos['tipo_procedimiento']} - {$datos['descripcion']}";
        $instrucciones_orden = $datos['indicaciones'] ?? null; // Usar indicaciones como instrucciones de la orden
        $urgente_orden = 0; // Por defecto no urgente, podrías añadir un checkbox en el formulario si lo necesitas

        $stmt_orden->execute([
            $datos['id_caso'],
            $descripcion_orden,
            $instrucciones_orden,
            $urgente_orden,
            $datos['id_medico_principal'] // El médico que ORDENA/REALIZA el procedimiento
        ]);

        $id_orden = $pdo->lastInsertId();

        // 2. Insertar en la tabla procedimientos
        $stmt = $pdo->prepare("
            INSERT INTO procedimientos 
            (id_caso, id_orden, tipo_procedimiento, descripcion, indicaciones, tecnica,
             material_utilizado, duracion_minutos, anestesia, id_medico_principal,
             id_medico_asistente, id_enfermero)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $datos['id_caso'],
            $id_orden, // Asociar con la orden médica recién creada
            $datos['tipo_procedimiento'],
            $datos['descripcion'],
            $datos['indicaciones'] ?? null,
            $datos['tecnica'] ?? null,
            $datos['material_utilizado'] ?? null,
            $datos['duracion_minutos'] ?? null,
            $datos['anestesia'] ?? null,
            $datos['id_medico_principal'], // Usar el ID del médico principal del formulario
            $datos['id_medico_asistente'] ?? null,
            $datos['id_enfermero'] ?? null
        ]);

        $id_procedimiento = $pdo->lastInsertId();

        // Registro de Auditoría
        registrarAuditoria($pdo, 'procedimientos', $id_procedimiento, 'INSERT', null, [
            'caso' => $datos['id_caso'],
            'tipo_procedimiento' => $datos['tipo_procedimiento'],
            'medico_principal' => $medico_principal['nombre_completo'],
            'id_orden_medica' => $id_orden
        ]);

        $pdo->commit();

        respuestaJSON('ok', 'Procedimiento registrado exitosamente');

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error al registrar procedimiento: " . $e->getMessage()); // Para depuración
        respuestaJSON('error', 'Error al registrar procedimiento: ' . $e->getMessage());
    }
} else {
    respuestaJSON('error', 'Método no permitido');
}
?>