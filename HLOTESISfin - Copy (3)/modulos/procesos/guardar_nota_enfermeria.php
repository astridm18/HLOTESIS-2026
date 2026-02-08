<?php
// modulos/procesos/guardar_nota_enfermeria.php
require_once '../../config/forms/database.php';
require_once '../../config/forms/functions.php';

verificarSesion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = sanitizar($_POST);

    // 🆕 CAMPOS REQUERIDOS ACTUALIZADOS
    $campos_requeridos = ['id_caso', 'contenido', 'id_enfermero'];
    foreach ($campos_requeridos as $campo) {
        if (empty($datos[$campo])) {
            respuestaJSON('error', "El campo $campo es requerido");
        }
    }

    try {
        // 🆕 VALIDAR QUE LA ENFERMERA EXISTE Y ESTÁ ACTIVA
        $stmt = $pdo->prepare("
            SELECT id, CONCAT(nombres, ' ', apellidos) as nombre_completo 
            FROM personal 
            WHERE id = ? AND rol = 'Enfermería' AND activo = 1
        ");
        $stmt->execute([$datos['id_enfermero']]);
        $enfermera = $stmt->fetch();

        if (!$enfermera) {
            respuestaJSON('error', 'La enfermera seleccionada no es válida o no está activa');
        }

        // 🆕 INSERTAR NOTA CON ENFERMERA VALIDADA
        $stmt = $pdo->prepare("
            INSERT INTO notas_enfermeria 
            (id_caso, turno, tipo_nota, contenido, cuidados_realizados,
             medicamentos_administrados, reacciones_adversas, estado_paciente, id_enfermero)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $datos['id_caso'],
            $datos['turno'] ?? 'mañana',
            $datos['tipo_nota'] ?? 'evolucion',
            $datos['contenido'],
            $datos['cuidados_realizados'] ?? null,
            $datos['medicamentos_administrados'] ?? null,
            $datos['reacciones_adversas'] ?? null,
            $datos['estado_paciente'] ?? null,
            $datos['id_enfermero']  // 🆕 USAR LA ENFERMERA SELECCIONADA
        ]);

        $id_nota = $pdo->lastInsertId();

        // 🆕 REGISTRAR AUDITORÍA CON INFORMACIÓN COMPLETA
        registrarAuditoria($pdo, 'notas_enfermeria', $id_nota, 'INSERT', null, [
            'caso' => $datos['id_caso'],
            'enfermera' => $enfermera['nombre_completo'],
            'tipo' => $datos['tipo_nota'],
            'turno' => $datos['turno']
        ]);

        respuestaJSON('ok', 'Nota guardada exitosamente', [
            'id_nota' => $id_nota,
            'enfermera' => $enfermera['nombre_completo']
        ]);

    } catch (Exception $e) {
        respuestaJSON('error', 'Error al guardar nota: ' . $e->getMessage());
    }
} else {
    respuestaJSON('error', 'Método no permitido');
}
?>