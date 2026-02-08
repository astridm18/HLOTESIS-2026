<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

include("conexion.php");

$tipo = $_POST['tipo'] ?? '';
$termino = $_POST['termino'] ?? '';

if (empty($tipo) || empty($termino)) {
    echo json_encode(['status' => 'error', 'message' => 'Parámetros incompletos']);
    exit;
}

try {
    $query = "";
    $termino = '%' . $conn->real_escape_string($termino) . '%';

    switch ($tipo) {
        case 'cedula':
            $query = "SELECT 
                        p.id_paciente,
                        p.numero_historia,
                        p.nombres,
                        p.apellidos,
                        p.cedula,
                        p.telefono,
                        p.fecha_nacimiento,
                        (SELECT MAX(di.fecha_ingreso) 
                         FROM casos_clinicos cc2 
                         INNER JOIN datos_ingreso di ON cc2.id_caso = di.id_caso 
                         WHERE cc2.id_paciente = p.id_paciente) as ultima_visita,
                        (SELECT COUNT(*) 
                         FROM casos_clinicos cc3 
                         WHERE cc3.id_paciente = p.id_paciente AND cc3.estado_caso = 'activo') as casos_activos
                      FROM pacientes p 
                      WHERE p.cedula LIKE '$termino' AND p.activo = 1
                      ORDER BY p.apellidos, p.nombres";
            break;

        case 'nombre':
            $query = "SELECT 
                        p.id_paciente,
                        p.numero_historia,
                        p.nombres,
                        p.apellidos,
                        p.cedula,
                        p.telefono,
                        p.fecha_nacimiento,
                        (SELECT MAX(di.fecha_ingreso) 
                         FROM casos_clinicos cc2 
                         INNER JOIN datos_ingreso di ON cc2.id_caso = di.id_caso 
                         WHERE cc2.id_paciente = p.id_paciente) as ultima_visita,
                        (SELECT COUNT(*) 
                         FROM casos_clinicos cc3 
                         WHERE cc3.id_paciente = p.id_paciente AND cc3.estado_caso = 'activo') as casos_activos
                      FROM pacientes p 
                      WHERE (p.nombres LIKE '$termino' OR p.apellidos LIKE '$termino' 
                             OR CONCAT(p.nombres, ' ', p.apellidos) LIKE '$termino') 
                      AND p.activo = 1
                      ORDER BY p.apellidos, p.nombres";
            break;

        case 'historia':
            $query = "SELECT 
                        p.id_paciente,
                        p.numero_historia,
                        p.nombres,
                        p.apellidos,
                        p.cedula,
                        p.telefono,
                        p.fecha_nacimiento,
                        (SELECT MAX(di.fecha_ingreso) 
                         FROM casos_clinicos cc2 
                         INNER JOIN datos_ingreso di ON cc2.id_caso = di.id_caso 
                         WHERE cc2.id_paciente = p.id_paciente) as ultima_visita,
                        (SELECT COUNT(*) 
                         FROM casos_clinicos cc3 
                         WHERE cc3.id_paciente = p.id_paciente AND cc3.estado_caso = 'activo') as casos_activos
                      FROM pacientes p 
                      WHERE p.numero_historia LIKE '$termino' AND p.activo = 1
                      ORDER BY p.apellidos, p.nombres";
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Tipo de búsqueda no válido']);
            exit;
    }

    $result = $conn->query($query);
    $pacientes = [];

    while ($row = $result->fetch_assoc()) {
        $edad = '';
        if ($row['fecha_nacimiento']) {
            $fecha_nac = new DateTime($row['fecha_nacimiento']);
            $hoy = new DateTime();
            $edad = $hoy->diff($fecha_nac)->y . ' años';
        }

        $ultima_visita = 'N/A';
        if ($row['ultima_visita']) {
            $ultima_visita = date('d/m/Y', strtotime($row['ultima_visita']));
        }

        $pacientes[] = [
            'id_paciente' => $row['id_paciente'],
            'numero_historia' => $row['numero_historia'],
            'nombre' => $row['nombres'] . ' ' . $row['apellidos'],
            'cedula' => $row['cedula'],
            'telefono' => $row['telefono'] ?: 'N/A',
            'edad' => $edad ?: 'N/A',
            'ultima_visita' => $ultima_visita,
            'casos_activos' => (int) $row['casos_activos']
        ];
    }

    echo json_encode([
        'status' => 'ok',
        'data' => $pacientes,
        'total' => count($pacientes)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error en la búsqueda: ' . $e->getMessage()
    ]);
}

$conn->close();
?>