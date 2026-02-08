<?php
// modulos/ingreso/obtener_camas.php - CORREGIDO
require_once '../../config/forms/database.php';
require_once '../../config/forms/functions.php';

verificarSesion();

$servicio = $_GET['servicio'] ?? '';
$solo_disponibles = $_GET['disponibles'] ?? false;

// ✅ MAPEO CORREGIDO - Coincide con la base de datos
$servicio_salas = [
    'Emergencias' => ['Emergencias'],
    'UCI' => ['UCI'],
    'Medicina Interna' => ['Medicina Interna'],
    'Cirugía' => ['Cirugía'],
    'Pediatría' => ['Pediatría'],
    'Ginecología' => ['Ginecología']
];

try {
    $sql = "
        SELECT id_cama, numero_cama, sala, piso, tipo_cama
        FROM camas 
        WHERE activa = 1 AND estado = 'disponible'
    ";

    $params = [];

    if ($servicio && isset($servicio_salas[$servicio])) {
        $salas = $servicio_salas[$servicio];
        $placeholders = str_repeat('?,', count($salas) - 1) . '?';
        $sql .= " AND sala IN ($placeholders)";
        $params = $salas;
    }

    $sql .= " ORDER BY sala, CAST(numero_cama AS UNSIGNED)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $camas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Estadísticas
    $stmt = $pdo->prepare("
        SELECT 
            sala,
            COUNT(*) as total,
            SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) as disponibles
        FROM camas 
        WHERE activa = 1
        GROUP BY sala
    ");
    $stmt->execute();
    $estadisticas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    respuestaJSON('ok', 'Camas obtenidas', $camas, ['estadisticas' => $estadisticas]);

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    respuestaJSON('error', 'Error al obtener camas');
}
?>