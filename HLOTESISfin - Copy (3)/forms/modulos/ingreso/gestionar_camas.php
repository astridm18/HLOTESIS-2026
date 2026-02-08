<?php
// modulos/ingreso/gestionar_camas.php - CORREGIDO
require_once '../../config/database.php';
require_once '../../config/functions.php';

verificarSesion();

// ✅ CONFIGURACIÓN CORREGIDA - Coincide con la base de datos real
$servicios_config = [
    'Emergencias' => [
        // Use the exact 'sala' names as they appear in the 'camas' table
        'salas' => ['Emergencias'], // Changed from ['Emergencia A', 'Emergencia B', 'Emergencia C']
        'pisos' => ['1'],
        'camas_por_sala' => 30,
        'tipos_cama' => ['normal', 'observacion']
    ],
    'UCI' => [
        'salas' => ['UCI'], // Changed from ['UCI-A', 'UCI-B']
        'pisos' => ['2'],
        'camas_por_sala' => 30,
        'tipos_cama' => ['uci']
    ],
    'Medicina Interna' => [
        'salas' => ['Medicina Interna'], // Changed from ['Medicina A', 'Medicina B', 'Medicina C']
        'pisos' => ['3'],
        'camas_por_sala' => 30,
        'tipos_cama' => ['normal']
    ],
    'Cirugía' => [
        'salas' => ['Cirugía'], // Changed from ['Cirugía A', 'Cirugía B']
        'pisos' => ['4'],
        'camas_por_sala' => 30,
        'tipos_cama' => ['normal', 'quirofano']
    ],
    'Pediatría' => [
        'salas' => ['Pediatría'], // Changed from ['Pediatría A', 'Pediatría B']
        'pisos' => ['5'],
        'camas_por_sala' => 30,
        'tipos_cama' => ['normal']
    ],
    'Ginecología' => [
        'salas' => ['Ginecología'], // Changed from ['Gineco A']
        'pisos' => ['6'],
        'camas_por_sala' => 30,
        'tipos_cama' => ['normal']
    ]
];

// Resto del código permanece igual...
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    switch ($accion) {
        case 'crear_camas_masivo':
            crearCamasMasivo();
            break;
        case 'obtener_camas':
            obtenerCamas();
            break;
        case 'cambiar_estado_cama':
            cambiarEstadoCama();
            break;
        case 'asignar_cama':
            asignarCama();
            break;
        case 'liberar_cama':
            liberarCama();
            break;
        default:
            respuestaJSON('error', 'Acción no válida');
    }
}

// 🏥 FUNCIÓN: Obtener salas por servicio - CORREGIDA
function obtenerSalasPorServicio($servicio)
{
    global $servicios_config;

    if (isset($servicios_config[$servicio])) {
        return $servicios_config[$servicio]['salas'];
    }

    return [];
}

// 🔍 FUNCIÓN: Obtener camas con filtros - CORREGIDA
function obtenerCamas()
{
    global $pdo;

    $servicio = $_POST['servicio'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $sala = $_POST['sala'] ?? '';
    $solo_disponibles = $_POST['solo_disponibles'] ?? false;

    try {
        $sql = "
            SELECT 
                c.id_cama,
                c.numero_cama,
                c.sala,
                c.piso,
                c.tipo_cama,
                c.estado,
                c.observaciones,
                c.fecha_asignacion,
                cc.numero_caso,
                CONCAT(p.nombres, ' ', p.apellidos) as paciente_actual,
                p.cedula as cedula_paciente
            FROM camas c
            LEFT JOIN casos_clinicos cc ON c.id_caso_actual = cc.id_caso
            LEFT JOIN pacientes p ON cc.id_paciente = p.id_paciente
            WHERE c.activa = 1
        ";

        $params = [];

        // Filtros dinámicos
        if ($solo_disponibles) {
            $sql .= " AND c.estado = 'disponible'";
        }

        if ($estado) {
            $sql .= " AND c.estado = ?";
            $params[] = $estado;
        }

        if ($sala) {
            $sql .= " AND c.sala = ?";
            $params[] = $sala;
        }

        // Filtro por servicio (buscar salas relacionadas) - CORREGIDO
        if ($servicio) {
            $salas_servicio = obtenerSalasPorServicio($servicio);
            if (!empty($salas_servicio)) {
                $placeholders = str_repeat('?,', count($salas_servicio) - 1) . '?';
                $sql .= " AND c.sala IN ($placeholders)";
                $params = array_merge($params, $salas_servicio);
            }
        }

        $sql .= " ORDER BY c.sala, CAST(c.numero_cama AS UNSIGNED)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $camas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        respuestaJSON('ok', 'Camas obtenidas correctamente', $camas);

    } catch (Exception $e) {
        error_log("Error obteniendo camas: " . $e->getMessage());
        respuestaJSON('error', 'Error al obtener las camas');
    }
}

// Resto de funciones permanecen iguales...
function crearCamasMasivo()
{
    global $pdo, $servicios_config;
    // Ya no es necesario ejecutar esto ya que las camas ya existen
    respuestaJSON('ok', 'Las camas ya están creadas en la base de datos');
}

function cambiarEstadoCama()
{
    global $pdo;

    $id_cama = $_POST['id_cama'] ?? '';
    $nuevo_estado = $_POST['nuevo_estado'] ?? '';
    $motivo = $_POST['motivo'] ?? '';

    if (!$id_cama || !$nuevo_estado) {
        respuestaJSON('error', 'ID de cama y nuevo estado son requeridos');
    }

    $estados_validos = ['disponible', 'ocupada', 'mantenimiento', 'bloqueada'];
    if (!in_array($nuevo_estado, $estados_validos)) {
        respuestaJSON('error', 'Estado no válido');
    }

    try {
        $pdo->beginTransaction();

        // Obtener estado actual
        $stmt = $pdo->prepare("SELECT estado, id_caso_actual FROM camas WHERE id_cama = ?");
        $stmt->execute([$id_cama]);
        $cama_actual = $stmt->fetch();

        if (!$cama_actual) {
            respuestaJSON('error', 'Cama no encontrada');
        }

        // Validaciones específicas
        if ($nuevo_estado === 'disponible' && $cama_actual['id_caso_actual']) {
            respuestaJSON('error', 'No se puede marcar como disponible una cama con caso asignado');
        }

        if ($nuevo_estado === 'ocupada' && !$cama_actual['id_caso_actual']) {
            respuestaJSON('error', 'No se puede marcar como ocupada una cama sin caso asignado');
        }

        // Actualizar estado
        $stmt = $pdo->prepare("
            UPDATE camas 
            SET estado = ?, 
                observaciones = CONCAT(COALESCE(observaciones, ''), IF(COALESCE(observaciones, '') = '', '', '\n'), 
                                     'Cambio a $nuevo_estado: ', COALESCE(?, 'Sin motivo'), ' - ', NOW()),
                actualizado_en = NOW()
            WHERE id_cama = ?
        ");

        $stmt->execute([$nuevo_estado, $motivo, $id_cama]);

        $pdo->commit();

        respuestaJSON('ok', "Estado de cama actualizado a: $nuevo_estado");

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error cambiando estado de cama: " . $e->getMessage());
        respuestaJSON('error', 'Error al cambiar estado de la cama');
    }
}

function asignarCama()
{
    global $pdo;

    $id_cama = $_POST['id_cama'] ?? '';
    $id_caso = $_POST['id_caso'] ?? '';

    if (!$id_cama || !$id_caso) {
        respuestaJSON('error', 'ID de cama e ID de caso son requeridos');
    }

    try {
        $pdo->beginTransaction();

        // Verificar que la cama esté disponible
        $stmt = $pdo->prepare("
            SELECT estado, id_caso_actual FROM camas 
            WHERE id_cama = ? AND activa = 1
        ");
        $stmt->execute([$id_cama]);
        $cama = $stmt->fetch();

        if (!$cama) {
            respuestaJSON('error', 'Cama no encontrada');
        }

        if ($cama['estado'] !== 'disponible') {
            respuestaJSON('error', 'La cama no está disponible');
        }

        if ($cama['id_caso_actual']) {
            respuestaJSON('error', 'La cama ya tiene un caso asignado');
        }

        // Verificar que el caso exista y esté activo
        $stmt = $pdo->prepare("
            SELECT estado_caso FROM casos_clinicos 
            WHERE id_caso = ?
        ");
        $stmt->execute([$id_caso]);
        $caso = $stmt->fetch();

        if (!$caso) {
            respuestaJSON('error', 'Caso no encontrado');
        }

        if ($caso['estado_caso'] !== 'activo') {
            respuestaJSON('error', 'El caso no está activo');
        }

        // Asignar cama
        $stmt = $pdo->prepare("
            UPDATE camas 
            SET estado = 'ocupada',
                id_caso_actual = ?,
                fecha_asignacion = NOW(),
                observaciones = CONCAT(COALESCE(observaciones, ''), '\nAsignada a caso ID: ', ?, ' - ', NOW()),
                actualizado_en = NOW()
            WHERE id_cama = ?
        ");
        $stmt->execute([$id_caso, $id_caso, $id_cama]);

        // Obtener datos de la cama para actualizar el caso
        $stmt = $pdo->prepare("
            SELECT numero_cama, sala, piso FROM camas 
            WHERE id_cama = ?
        ");
        $stmt->execute([$id_cama]);
        $info_cama = $stmt->fetch();

        // Actualizar caso con información de la cama
        $stmt = $pdo->prepare("
            UPDATE casos_clinicos 
            SET cama_actual = ?,
                sala_actual = ?,
                piso = ?,
                actualizado_en = NOW()
            WHERE id_caso = ?
        ");

        $stmt->execute([
            $info_cama['numero_cama'],
            $info_cama['sala'],
            $info_cama['piso'],
            $id_caso
        ]);

        $pdo->commit();

        respuestaJSON('ok', 'Cama asignada correctamente al caso', [
            'numero_cama' => $info_cama['numero_cama'],
            'sala' => $info_cama['sala'],
            'piso' => $info_cama['piso']
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error asignando cama: " . $e->getMessage());
        respuestaJSON('error', 'Error al asignar la cama');
    }
}

function liberarCama()
{
    global $pdo;

    $id_cama = $_POST['id_cama'] ?? '';
    $motivo = $_POST['motivo'] ?? 'Liberación manual';

    if (!$id_cama) {
        respuestaJSON('error', 'ID de cama es requerido');
    }

    try {
        $pdo->beginTransaction();

        // Obtener información actual de la cama
        $stmt = $pdo->prepare("
            SELECT estado, id_caso_actual, numero_cama, sala 
            FROM camas WHERE id_cama = ?
        ");
        $stmt->execute([$id_cama]);
        $cama = $stmt->fetch();

        if (!$cama) {
            respuestaJSON('error', 'Cama no encontrada');
        }

        $id_caso_anterior = $cama['id_caso_actual'];

        // Liberar cama
        $stmt = $pdo->prepare("
            UPDATE camas 
            SET estado = 'disponible',
                id_caso_actual = NULL,
                fecha_asignacion = NULL,
                observaciones = CONCAT(COALESCE(observaciones, ''), '\nLiberada: ', ?, ' - ', NOW()),
                actualizado_en = NOW()
            WHERE id_cama = ?
        ");
        $stmt->execute([$motivo, $id_cama]);

        // Si había un caso asignado, actualizar sus datos de cama
        if ($id_caso_anterior) {
            $stmt = $pdo->prepare("
                UPDATE casos_clinicos 
                SET cama_actual = NULL,
                    sala_actual = NULL,
                    piso = NULL,
                    actualizado_en = NOW()
                WHERE id_caso = ?
            ");
            $stmt->execute([$id_caso_anterior]);
        }

        $pdo->commit();

        respuestaJSON('ok', 'Cama liberada correctamente', [
            'numero_cama' => $cama['numero_cama'],
            'sala' => $cama['sala'],
            'caso_anterior' => $id_caso_anterior
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error liberando cama: " . $e->getMessage());
        respuestaJSON('error', 'Error al liberar la cama');
    }
}

// Si se accede por GET, mostrar estadísticas
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $accion = $_GET['accion'] ?? 'estadisticas';

    if ($accion === 'estadisticas') {
        obtenerEstadisticasCamas();
    } else {
        respuestaJSON('error', 'Acción GET no válida');
    }
}

function obtenerEstadisticasCamas()
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_camas,
                SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) as disponibles,
                SUM(CASE WHEN estado = 'ocupada' THEN 1 ELSE 0 END) as ocupadas,
                SUM(CASE WHEN estado = 'mantenimiento' THEN 1 ELSE 0 END) as mantenimiento,
                SUM(CASE WHEN estado = 'bloqueada' THEN 1 ELSE 0 END) as bloqueadas,
                sala,
                tipo_cama
            FROM camas 
            WHERE activa = 1 
            GROUP BY sala, tipo_cama
            ORDER BY sala
        ");

        $stmt->execute();
        $estadisticas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        respuestaJSON('ok', 'Estadísticas obtenidas correctamente', $estadisticas);

    } catch (Exception $e) {
        error_log("Error obteniendo estadísticas: " . $e->getMessage());
        respuestaJSON('error', 'Error al obtener estadísticas');
    }
}
?>