<?php
// modulos/procesos/guardar_evolucion.php
require_once '../../config/database.php';
require_once '../../config/functions.php';

verificarSesion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = sanitizar($_POST);

    // 🔧 DEBUG: Verificar qué datos llegan
    error_log("Datos recibidos: " . print_r($datos, true));

    // 🆕 CAMPOS REQUERIDOS ACTUALIZADOS (incluye id_medico)
    $campos_requeridos = ['id_caso', 'plan', 'id_medico'];
    foreach ($campos_requeridos as $campo) {
        if (empty($datos[$campo])) {
            respuestaJSON('error', "El campo $campo es requerido. Valor recibido: " . ($datos[$campo] ?? 'NULL'));
        }
    }

    try {
        // 🆕 VALIDAR QUE EL MÉDICO EXISTE Y ESTÁ ACTIVO
        $stmt = $pdo->prepare("
            SELECT id, CONCAT(nombres, ' ', apellidos) as nombre_completo 
            FROM personal 
            WHERE id = ? AND rol = 'Medicina' AND activo = 1
        ");
        $stmt->execute([$datos['id_medico']]);
        $medico = $stmt->fetch();

        if (!$medico) {
            respuestaJSON('error', 'El médico seleccionado no es válido o no está activo');
        }

        // 🆕 INSERTAR EVOLUCIÓN CON MÉDICO RESPONSABLE
        $stmt = $pdo->prepare("
            INSERT INTO evoluciones 
            (id_caso, tipo_evolucion, subjetivo, objetivo, evaluacion, plan, 
             estado_paciente, id_medico, fecha_evolucion)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $datos['id_caso'],
            $datos['tipo_evolucion'] ?? 'diaria',
            $datos['subjetivo'] ?? null,
            $datos['objetivo'] ?? null,
            $datos['evaluacion'] ?? null,
            $datos['plan'],
            $datos['estado_paciente'] ?? 'estable',
            $datos['id_medico']  // 🆕 MÉDICO RESPONSABLE
        ]);

        $id_evolucion = $pdo->lastInsertId();

        // 🆕 REGISTRAR AUDITORÍA CON INFORMACIÓN COMPLETA
        registrarAuditoria($pdo, 'evoluciones', $id_evolucion, 'INSERT', null, [
            'caso' => $datos['id_caso'],
            'medico' => $medico['nombre_completo'],
            'tipo' => $datos['tipo_evolucion'],
            'estado_paciente' => $datos['estado_paciente']
        ]);

        respuestaJSON('ok', 'Evolución guardada exitosamente', [
            'id_evolucion' => $id_evolucion,
            'medico' => $medico['nombre_completo']
        ]);

    } catch (Exception $e) {
        respuestaJSON('error', 'Error al guardar evolución: ' . $e->getMessage());
    }
} else {
    respuestaJSON('error', 'Método no permitido');
}
?>