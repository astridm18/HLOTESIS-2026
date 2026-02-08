<?php
// modulos/ingreso/buscar_paciente.php
require_once '../../config/database.php';
require_once '../../config/functions.php';

verificarSesion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cedula_busqueda = trim($_POST['cedula']);

    if (empty($cedula_busqueda)) {
        respuestaJSON('error', 'Debe proporcionar una cédula para buscar');
    }

    try {
        // 🔍 BÚSQUEDA FLEXIBLE DE CÉDULA (maneja diferentes formatos)
        $stmt = $pdo->prepare("
            SELECT * FROM pacientes 
            WHERE cedula = ? OR 
                  cedula = CONCAT('V-', ?) OR 
                  cedula = CONCAT('E-', ?) OR 
                  cedula = CONCAT('P-', ?) OR
                  REPLACE(cedula, '-', '') = REPLACE(?, '-', '')
            LIMIT 1
        ");

        // Ejecutar con diferentes variaciones de la cédula
        $cedula_limpia = str_replace('-', '', $cedula_busqueda);
        $stmt->execute([
            $cedula_busqueda,
            $cedula_limpia,
            $cedula_limpia,
            $cedula_limpia,
            $cedula_busqueda
        ]);

        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$paciente) {
            // PACIENTE NO EXISTE
            respuestaJSON('no_existe', 'Paciente no encontrado');
        }

        // ✅ VERIFICAR CASOS ACTIVOS DEL PACIENTE
        $stmt_casos = $pdo->prepare("
            SELECT 
                cc.id_caso,
                cc.numero_caso,
                cc.prioridad,
                cc.servicio_actual,
                cc.fecha_apertura,
                di.motivo_consulta,
                di.fecha_ingreso,
                CONCAT(p.nombres, ' ', p.apellidos) as medico_responsable
            FROM casos_clinicos cc
            LEFT JOIN datos_ingreso di ON cc.id_caso = di.id_caso
            LEFT JOIN personal p ON cc.id_medico_responsable = p.id
            WHERE cc.id_paciente = ? AND cc.estado_caso = 'activo'
            ORDER BY cc.fecha_apertura DESC
        ");

        $stmt_casos->execute([$paciente['id_paciente']]);
        $casos_activos = $stmt_casos->fetchAll(PDO::FETCH_ASSOC);

        if (count($casos_activos) > 0) {
            // PACIENTE CON CASOS ACTIVOS - NO PERMITIR NUEVO INGRESO
            respuestaJSON('paciente_con_caso_activo', 'Paciente tiene casos activos', [
                'paciente' => $paciente,
                'casos_activos' => $casos_activos,
                'cantidad_casos' => count($casos_activos)
            ]);
        }

        // ✅ PACIENTE EXISTE Y NO TIENE CASOS ACTIVOS - PERMITIR
        respuestaJSON('existe', 'Paciente encontrado sin casos activos', $paciente);

    } catch (Exception $e) {
        error_log("Error en buscar_paciente.php: " . $e->getMessage());
        respuestaJSON('error', 'Error al buscar paciente: ' . $e->getMessage());
    }
} else {
    respuestaJSON('error', 'Método no permitido');
}
?>