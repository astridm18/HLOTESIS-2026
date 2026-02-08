<?php
echo "ID Caso desde GET: " . ($_GET['caso'] ?? 'NO DEFINIDO') . "<br>";
echo "URL actual: " . $_SERVER['REQUEST_URI'] . "<br>";

// Probar conexión BD
require_once '../../config/database.php';
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM evoluciones");
    $stmt->execute();
    echo "Conexión BD: OK - Total evoluciones: " . $stmt->fetchColumn() . "<br>";
} catch (Exception $e) {
    echo "Error BD: " . $e->getMessage() . "<br>";
}

// Probar functions.php
require_once '../../config/functions.php';
echo "Functions.php: Cargado OK<br>";

if (function_exists('respuestaJSON')) {
    echo "Función respuestaJSON: Existe<br>";
} else {
    echo "Función respuestaJSON: NO EXISTE<br>";
}

// Probar consulta específica de evoluciones
$id_caso = $_GET['caso'] ?? 0;
if ($id_caso) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM evoluciones WHERE id_caso = ?");
        $stmt->execute([$id_caso]);
        echo "Evoluciones para caso $id_caso: " . $stmt->fetchColumn() . "<br>";
    } catch (Exception $e) {
        echo "Error consultando evoluciones: " . $e->getMessage() . "<br>";
    }
}
?>