<?php
// modulos/ingreso/validar_cama_disponible.php
require_once '../../config/forms/database.php';
require_once '../../config/forms/functions.php';

verificarSesion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = sanitizar($_POST);

    $idCama = $datos['id_cama'] ?? '';

    if (empty($idCama)) {
        respuestaJSON('error', 'ID de cama requerido');
    }

    try {
        // Verificar estado actual de la cama
        $stmt = $pdo->prepare("
            SELECT 
                id_cama,
                numero_cama,
                sala,
                piso,
                tipo_cama,
                estado,
                id_caso_actual,
                fecha_asignacion,
                -- Datos del caso actual si existe
                CASE 
                    WHEN estado = 'ocupada' AND id_caso_actual IS NOT NULL THEN
                        (SELECT CONCAT(p.nombres, ' ', p.apellidos) 
                         FROM casos_clinicos cc 
                         JOIN pacientes p ON cc.id_paciente = p.id_paciente 
                         WHERE cc.id_caso = id_caso_actual)
                    ELSE NULL
                END as paciente_ocupante,
                CASE 
                    WHEN estado = 'ocupada' AND id_caso_actual IS NOT NULL THEN
                        (SELECT numero_caso 
                         FROM casos_clinicos 
                         WHERE id_caso = id_caso_actual)
                    ELSE NULL
                END as numero_caso_ocupante
            FROM camas 
            WHERE id_cama = ? AND activa = 1
        ");

        $stmt->execute([$idCama]);
        $cama = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cama) {
            respuestaJSON('error', 'Cama no encontrada');
        }

        // Verificar disponibilidad
        if ($cama['estado'] !== 'disponible') {
            $mensaje = '';
            $detalles = [];

            switch ($cama['estado']) {
                case 'ocupada':
                    $mensaje = 'La cama está ocupada';
                    $detalles = [
                        'paciente' => $cama['paciente_ocupante'],
                        'numero_caso' => $cama['numero_caso_ocupante'],
                        'fecha_asignacion' => $cama['fecha_asignacion']
                    ];
                    break;
                case 'mantenimiento':
                    $mensaje = 'La cama está en mantenimiento';
                    break;
                case 'bloqueada':
                    $mensaje = 'La cama está bloqueada';
                    break;
                default:
                    $mensaje = 'La cama no está disponible';
            }

            respuestaJSON('ocupada', $mensaje, $detalles);
        }

        // Cama disponible
        respuestaJSON('ok', 'Cama disponible para asignación', [
            'id_cama' => $cama['id_cama'],
            'numero_cama' => $cama['numero_cama'],
            'sala' => $cama['sala'],
            'piso' => $cama['piso'],
            'tipo_cama' => $cama['tipo_cama']
        ]);

    } catch (Exception $e) {
        error_log("Error en validar_cama_disponible.php: " . $e->getMessage());
        respuestaJSON('error', 'Error al validar disponibilidad de cama');
    }
}
?>