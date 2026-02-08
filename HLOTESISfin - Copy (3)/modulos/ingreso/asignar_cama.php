<?php
// asignar_cama.php
header('Content-Type: application/json');
require_once '../../config/forms/database.php';
require_once '../../config/forms/functions.php';

try {
    $id_cama = $_POST['id_cama'] ?? '';
    $id_caso = $_POST['id_caso'] ?? '';
    $accion = $_POST['accion'] ?? 'asignar'; // asignar o liberar

    if (empty($id_cama)) {
        throw new Exception('ID de cama requerido');
    }

    $pdo->beginTransaction();

    if ($accion === 'asignar') {
        if (empty($id_caso)) {
            throw new Exception('ID de caso requerido para asignar');
        }

        // Verificar que la cama esté disponible
        $stmt = $pdo->prepare("SELECT estado FROM camas WHERE id_cama = ? AND activa = 1");
        $stmt->execute([$id_cama]);
        $cama = $stmt->fetch();

        if (!$cama) {
            throw new Exception('Cama no encontrada');
        }

        if ($cama['estado'] !== 'disponible') {
            throw new Exception('La cama no está disponible');
        }

        // Verificar que el caso esté activo
        $stmt = $pdo->prepare("SELECT estado_caso FROM casos_clinicos WHERE id_caso = ?");
        $stmt->execute([$id_caso]);
        $caso = $stmt->fetch();

        if (!$caso || $caso['estado_caso'] !== 'activo') {
            throw new Exception('El caso no está activo');
        }

        // Liberar cualquier cama anterior del mismo caso
        $stmt = $pdo->prepare("
            UPDATE camas 
            SET estado = 'disponible', id_caso_actual = NULL, fecha_asignacion = NULL 
            WHERE id_caso_actual = ?
        ");
        $stmt->execute([$id_caso]);

        // Asignar la nueva cama
        $stmt = $pdo->prepare("
            UPDATE camas 
            SET estado = 'ocupada', id_caso_actual = ?, fecha_asignacion = NOW() 
            WHERE id_cama = ?
        ");
        $stmt->execute([$id_caso, $id_cama]);

        // Actualizar el campo cama_actual en casos_clinicos
        $stmt = $pdo->prepare("
            UPDATE casos_clinicos 
            SET cama_actual = (SELECT numero_cama FROM camas WHERE id_cama = ?)
            WHERE id_caso = ?
        ");
        $stmt->execute([$id_cama, $id_caso]);

        $mensaje = 'Cama asignada correctamente';

    } else { // liberar
        // Liberar la cama
        $stmt = $pdo->prepare("
            UPDATE camas 
            SET estado = 'disponible', id_caso_actual = NULL, fecha_asignacion = NULL 
            WHERE id_cama = ?
        ");
        $stmt->execute([$id_cama]);

        // Limpiar el campo en casos_clinicos si corresponde
        $stmt = $pdo->prepare("
            UPDATE casos_clinicos 
            SET cama_actual = NULL 
            WHERE cama_actual = (SELECT numero_cama FROM camas WHERE id_cama = ?)
        ");
        $stmt->execute([$id_cama]);

        $mensaje = 'Cama liberada correctamente';
    }

    $pdo->commit();

    echo json_encode([
        'status' => 'ok',
        'message' => $mensaje
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>