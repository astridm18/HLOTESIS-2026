<?php
// modulos/ingreso/obtener_proximo_numero.php
require_once '../../config/forms/database.php';
require_once '../../config/forms/functions.php';

verificarSesion();

try {
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(CAST(numero_historia AS UNSIGNED)), 0) + 1 as siguiente FROM pacientes");
    $stmt->execute();
    $resultado = $stmt->fetch();
    
    $numero_historia = str_pad($resultado['siguiente'], 6, '0', STR_PAD_LEFT);
    
    respuestaJSON('ok', 'Próximo número obtenido', ['numero_historia' => $numero_historia]);
    
} catch(Exception $e) {
    respuestaJSON('error', 'Error al obtener número: ' . $e->getMessage());
}
?>