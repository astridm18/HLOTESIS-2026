<?php
// modulos/procesos/solicitar_laboratorio.php
require_once '../../config/database.php';
require_once '../../config/functions.php';

verificarSesion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = sanitizar($_POST);

    // 🆕 CAMPOS REQUERIDOS ACTUALIZADOS
    $campos_requeridos = ['id_caso', 'tipo_examen', 'examenes_solicitados', 'id_medico_solicita'];
    foreach ($campos_requeridos as $campo) {
        if (empty($datos[$campo])) {
            respuestaJSON('error', "El campo $campo es requerido");
        }
    }

    try {
        // 🆕 VALIDAR QUE EL MÉDICO EXISTE Y ESTÁ ACTIVO
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

        // 🆕 1. CREAR ORDEN MÉDICA SIN ESPECIFICAR ESTADO (usará default)
        $stmt = $pdo->prepare("
            INSERT INTO ordenes_medicas 
            (id_caso, tipo_orden, descripcion, instrucciones_especiales, urgente, id_medico_ordena)
            VALUES (?, 'laboratorio', ?, ?, ?, ?)
        ");

        // 🆕 1. CREAR ORDEN MÉDICA CON ESTADO PENDIENTE
        $stmt = $pdo->prepare("
            INSERT INTO ordenes_medicas 
            (id_caso, tipo_orden, descripcion, instrucciones_especiales, urgente, estado, id_medico_ordena)
            VALUES (?, 'laboratorio', ?, ?, ?, 'pendiente', ?)
        ");

        $descripcion = "Exámenes de {$datos['tipo_examen']}: {$datos['examenes_solicitados']}";

        $stmt->execute([
            $datos['id_caso'],
            $descripcion,
            $datos['indicaciones'] ?? null,
            isset($datos['urgente']) ? 1 : 0,
            $datos['id_medico_solicita']  // 🆕 USAR EL MÉDICO SELECCIONADO
        ]);

        $id_orden = $pdo->lastInsertId();

        // 2. CREAR REGISTRO EN LABORATORIOS
        $examenes = explode(',', $datos['examenes_solicitados']);
        foreach ($examenes as $examen) {
            $examen = trim($examen);
            if (!empty($examen)) {
                $stmt = $pdo->prepare("
                    INSERT INTO laboratorios 
                    (id_caso, id_orden, tipo_examen, parametro)
                    VALUES (?, ?, ?, ?)
                ");

                $stmt->execute([
                    $datos['id_caso'],
                    $id_orden,
                    $datos['tipo_examen'],
                    $examen
                ]);
            }
        }

        // 🆕 REGISTRAR AUDITORÍA CON INFORMACIÓN COMPLETA
        registrarAuditoria($pdo, 'ordenes_medicas', $id_orden, 'INSERT', null, [
            'caso' => $datos['id_caso'],
            'medico' => $medico['nombre_completo'],
            'tipo_examen' => $datos['tipo_examen'],
            'examenes' => $datos['examenes_solicitados']
        ]);

        $pdo->commit();

        respuestaJSON('ok', 'Examen solicitado exitosamente', [
            'id_orden' => $id_orden,
            'medico' => $medico['nombre_completo'],
            'tipo_examen' => $datos['tipo_examen']
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        respuestaJSON('error', 'Error al solicitar examen: ' . $e->getMessage());
    }
} else {
    respuestaJSON('error', 'Método no permitido');
}
?>