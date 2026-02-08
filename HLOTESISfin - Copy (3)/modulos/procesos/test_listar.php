<?php
require_once '../../config/forms/database.php';
require_once '../../config/forms/functions.php';

$id_caso = $_GET['caso'] ?? 0;
echo "Testing listar evoluciones para caso: $id_caso<br><br>";

if (!$id_caso) {
    echo "ERROR: ID de caso requerido<br>";
    exit;
}

try {
    $sql = "
        SELECT 
            e.*,
            CONCAT(p.nombres, ' ', p.apellidos) as medico,
            p.especialidad,
            DATE_FORMAT(e.fecha_evolucion, '%d/%m/%Y %H:%i') as fecha_evolucion
        FROM evoluciones e
        INNER JOIN personal p ON e.id_medico = p.id
        WHERE e.id_caso = ?
        ORDER BY e.fecha_evolucion DESC
    ";

    echo "SQL Query: $sql<br><br>";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_caso]);
    $evoluciones = $stmt->fetchAll();

    echo "Evoluciones encontradas: " . count($evoluciones) . "<br><br>";

    if (count($evoluciones) > 0) {
        echo "<pre>";
        print_r($evoluciones);
        echo "</pre>";
    }

    // Probar respuesta JSON
    echo "<br><hr><br>";
    echo "Probando respuestaJSON:<br>";
    respuestaJSON('ok', 'Test exitoso', $evoluciones);

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
    echo "SQL State: " . $e->getCode() . "<br>";
}
?>