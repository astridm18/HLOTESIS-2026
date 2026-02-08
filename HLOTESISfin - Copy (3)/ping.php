<?php
session_start();
header('Content-Type: application/json');

// Verificar si la sesión está activa
if (isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'status' => 'ok',
        'message' => 'Sesión activa',
        'timestamp' => time()
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Sesión expirada',
        'timestamp' => time()
    ]);
}
?>