<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

include("../conexion.php");

// Verificar que se recibió el ID del paciente (puede venir como 'paciente_id' o 'paciente')
$paciente_id = 0;
if (isset($_GET['paciente_id']) && !empty($_GET['paciente_id'])) {
    $paciente_id = intval($_GET['paciente_id']);
} elseif (isset($_GET['paciente']) && !empty($_GET['paciente'])) {
    $paciente_id = intval($_GET['paciente']);
}

if ($paciente_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de paciente no válido']);
    exit();
}

try {
    // Obtener información del paciente
    $query_paciente = "SELECT nombres, apellidos FROM pacientes WHERE id_paciente = ?";
    $stmt = $conn->prepare($query_paciente);
    $stmt->bind_param("i", $paciente_id);
    $stmt->execute();
    $result_paciente = $stmt->get_result();

    if ($result_paciente->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Paciente no encontrado']);
        exit();
    }

    $paciente = $result_paciente->fetch_assoc();
    $paciente_nombre = $paciente['nombres'] . ' ' . $paciente['apellidos'];

    // ✅ CONSULTA CORREGIDA: Usar id_caso que es la columna correcta según tu base de datos
    $query_casos = "
        SELECT 
            SUM(CASE WHEN estado_caso = 'cerrado' THEN 1 ELSE 0 END) as casos_cerrados,
            SUM(CASE WHEN estado_caso = 'activo' THEN 1 ELSE 0 END) as casos_activos,
            COUNT(*) as total_casos,
            (SELECT id_caso FROM casos_clinicos WHERE id_paciente = ? AND estado_caso = 'activo' ORDER BY fecha_apertura DESC LIMIT 1) as caso_activo_id
        FROM casos_clinicos 
        WHERE id_paciente = ?
    ";

    $stmt_casos = $conn->prepare($query_casos);
    $stmt_casos->bind_param("ii", $paciente_id, $paciente_id); // ✅ Dos parámetros ahora
    $stmt_casos->execute();
    $result_casos = $stmt_casos->get_result();
    $casos_info = $result_casos->fetch_assoc();

    // Preparar respuesta
    $response = [
        'success' => true,
        'paciente_nombre' => $paciente_nombre,
        'casos_cerrados' => intval($casos_info['casos_cerrados']),
        'casos_activos' => intval($casos_info['casos_activos']),
        'total_casos' => intval($casos_info['total_casos']),
        'caso_activo_id' => $casos_info['caso_activo_id'] ? intval($casos_info['caso_activo_id']) : null, // ✅ NUEVO
        'paciente_id' => $paciente_id // ✅ AGREGADO para facilitar uso en JS
    ];

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}

$conn->close();
?>