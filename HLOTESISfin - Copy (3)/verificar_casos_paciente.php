<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

include("conexion.php");

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$numero_historia = $_GET['numero_historia'] ?? '';

if (empty($numero_historia)) {
    echo json_encode(['success' => false, 'message' => 'Número de historia requerido']);
    exit;
}

try {
    // Primero obtener información del paciente
    $query_paciente = "SELECT id_paciente, nombres, apellidos, cedula 
                       FROM pacientes 
                       WHERE numero_historia = ?";

    $stmt_paciente = $conn->prepare($query_paciente);
    $stmt_paciente->bind_param("s", $numero_historia);
    $stmt_paciente->execute();
    $result_paciente = $stmt_paciente->get_result();

    if ($result_paciente->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontró paciente con ese número de historia'
        ]);
        exit;
    }

    $paciente = $result_paciente->fetch_assoc();
    $paciente_id = $paciente['id_paciente'];
    $paciente_nombre = $paciente['nombres'] . ' ' . $paciente['apellidos'];

    // Contar casos cerrados
    $query_cerrados = "SELECT COUNT(*) as total_cerrados 
                       FROM casos_clinicos 
                       WHERE id_paciente = ? AND estado_caso = 'cerrado'";

    $stmt_cerrados = $conn->prepare($query_cerrados);
    $stmt_cerrados->bind_param("i", $paciente_id);
    $stmt_cerrados->execute();
    $result_cerrados = $stmt_cerrados->get_result();
    $casos_cerrados = $result_cerrados->fetch_assoc()['total_cerrados'];

    // Contar casos activos y obtener ID del caso activo
    $query_activos = "SELECT COUNT(*) as total_activos, id_caso as caso_activo_id
                      FROM casos_clinicos 
                      WHERE id_paciente = ? AND estado_caso = 'activo' 
                      LIMIT 1";

    $stmt_activos = $conn->prepare($query_activos);
    $stmt_activos->bind_param("i", $paciente_id);
    $stmt_activos->execute();
    $result_activos = $stmt_activos->get_result();
    $casos_activos_data = $result_activos->fetch_assoc();
    $casos_activos = $casos_activos_data['total_activos'];
    $caso_activo_id = $casos_activos_data['caso_activo_id'];

    echo json_encode([
        'success' => true,
        'paciente_id' => $paciente_id,
        'paciente_nombre' => $paciente_nombre,
        'casos_cerrados' => (int) $casos_cerrados,
        'casos_activos' => (int) $casos_activos,
        'caso_activo_id' => $caso_activo_id
    ]);

    $stmt_paciente->close();
    $stmt_cerrados->close();
    $stmt_activos->close();

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}

$conn->close();
?>