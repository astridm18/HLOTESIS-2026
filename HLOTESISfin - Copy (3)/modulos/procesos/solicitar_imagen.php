<?php
// modulos/procesos/solicitar_imagen.php
require_once '../../config/forms/database.php';
require_once '../../config/forms/functions.php';

verificarSesion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = sanitizar($_POST);

    // CAMPOS REQUERIDOS ACTUALIZADOS: id_medico_solicita
    $campos_requeridos = ['id_caso', 'tipo_estudio', 'region_anatomica', 'indicacion_clinica', 'id_medico_solicita'];
    foreach ($campos_requeridos as $campo) {
        if (empty($datos[$campo])) {
            respuestaJSON('error', "El campo $campo es requerido");
        }
    }

    try {
        // VALIDAR QUE EL MÉDICO EXISTE Y ESTÁ ACTIVO
        $stmt = $pdo->prepare("
            SELECT id, CONCAT(nombres, ' ', apellidos) as nombre_completo 
            FROM personal 
            WHERE id = ? AND rol = 'Medicina' AND activo = 1
        ");
        $stmt->execute([$datos['id_medico_solicita']]);
        $medico = $stmt->fetch();

        if (!$medico) {
            respuestaJSON('error', 'El médico seleccionado no es válido o no está activo');
        }

        $pdo->beginTransaction();

        // 1. Crear orden médica
        // ADECUADO A LA BDD: El estado de la orden médica debe ser 'pendiente', no 'solicitado'.
        // 'solicitado' es el estado del estudio de imagen, no de la orden.
        $stmt = $pdo->prepare("
            INSERT INTO ordenes_medicas 
            (id_caso, tipo_orden, descripcion, instrucciones_especiales, urgente, estado, id_medico_ordena)
            VALUES (?, 'imagen', ?, ?, ?, 'pendiente', ?)
        ");

        $descripcion = "{$datos['tipo_estudio']} de {$datos['region_anatomica']}";
        if (isset($datos['contraste'])) {
            $descripcion .= " con contraste";
        }

        $stmt->execute([
            $datos['id_caso'],
            $descripcion,
            $datos['indicacion_clinica'],
            isset($datos['urgente']) ? 1 : 0,
            $datos['id_medico_solicita'] // USAR EL MÉDICO SELECCIONADO
        ]);

        $id_orden = $pdo->lastInsertId();

        // 2. Crear registro en imágenes
        // Aquí el estado 'solicitado' sí es correcto para la tabla `imagenes`
        $stmt = $pdo->prepare("
            INSERT INTO imagenes 
            (id_caso, id_orden, tipo_estudio, region_anatomica, tecnica, contraste, 
             hallazgos, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'solicitado')
        ");

        $stmt->execute([
            $datos['id_caso'],
            $id_orden,
            $datos['tipo_estudio'],
            $datos['region_anatomica'],
            $datos['tecnica'] ?? null,
            isset($datos['contraste']) ? 1 : 0,
            $datos['indicacion_clinica']
        ]);

        registrarAuditoria($pdo, 'ordenes_medicas', $id_orden, 'INSERT', null, [
            'caso' => $datos['id_caso'],
            'medico' => $medico['nombre_completo'],
            'tipo_estudio' => $datos['tipo_estudio'],
            'region_anatomica' => $datos['region_anatomica']
        ]);

        $pdo->commit();

        respuestaJSON('ok', 'Estudio solicitado exitosamente', [
            'id_orden' => $id_orden,
            'medico' => $medico['nombre_completo'],
            'tipo_estudio' => $datos['tipo_estudio']
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error en solicitar_imagen.php: " . $e->getMessage());
        respuestaJSON('error', 'Error al solicitar estudio: ' . $e->getMessage());
    }
} else {
    respuestaJSON('error', 'Método no permitido');
}
?>