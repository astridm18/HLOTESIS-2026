<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

include("conexion.php");

try {
    $query = "SELECT 
                cc.id_caso,
                cc.numero_caso,
                p.nombres,
                p.apellidos,
                p.cedula,
                p.numero_historia,
                di.fecha_ingreso,
                cc.servicio_actual,
                cc.estado_caso,
                cc.prioridad,
                CONCAT(pe.nombres, ' ', pe.apellidos) as medico_responsable,
                di.motivo_consulta
              FROM casos_clinicos cc
              INNER JOIN pacientes p ON cc.id_paciente = p.id_paciente
              INNER JOIN datos_ingreso di ON cc.id_caso = di.id_caso
              LEFT JOIN personal pe ON cc.id_medico_responsable = pe.id
              WHERE cc.estado_caso = 'activo'
              ORDER BY di.fecha_ingreso DESC, cc.prioridad DESC";

    $result = $conn->query($query);
    $casos = [];

    while ($row = $result->fetch_assoc()) {
        $casos[] = [
            'id_caso' => $row['id_caso'],
            'numero_caso' => $row['numero_caso'],
            'paciente' => $row['nombres'] . ' ' . $row['apellidos'],
            'cedula' => $row['cedula'],
            'numero_historia' => $row['numero_historia'], // Campo agregado
            'fecha_ingreso' => date('d/m/Y', strtotime($row['fecha_ingreso'])),
            'servicio' => $row['servicio_actual'] ?: 'Sin asignar',
            'medico' => $row['medico_responsable'] ?: 'Sin asignar',
            'estado' => $row['estado_caso'],
            'prioridad' => $row['prioridad'],
            'motivo_consulta' => $row['motivo_consulta']
        ];
    }

    echo json_encode([
        'status' => 'ok',
        'data' => $casos
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al obtener casos activos: ' . $e->getMessage()
    ]);
}

$conn->close();
?>