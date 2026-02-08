<?php
session_start();
include("../../conexion.php");
require_once __DIR__ . '/../../config/forms/functions.php';
verificarSesion();
verificarRol(['administrador'], '../../index.php');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Obtener datos del personal antes de desactivar
    $query = "SELECT nombres, apellidos, rol FROM personal WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $nombres = $row['nombres'];
        $apellidos = $row['apellidos'];
        $rol = $row['rol'];

        // Verificar si tiene casos activos
        $check_casos = "SELECT COUNT(*) as total FROM casos_clinicos 
                        WHERE (id_medico_responsable = ? OR id_caso IN (
                            SELECT id_caso FROM medicos_responsables WHERE id_medico = ? AND activo = 1
                        )) AND estado_caso = 'activo'";
        $stmt_casos = $conn->prepare($check_casos);
        $stmt_casos->bind_param("ii", $id, $id);
        $stmt_casos->execute();
        $result_casos = $stmt_casos->get_result();
        $casos_activos = $result_casos->fetch_assoc()['total'];
        $stmt_casos->close();

        if ($casos_activos > 0) {
            echo "<!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <title>No se puede desactivar</title>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            </head>
            <body>
            <script>
                Swal.fire({
                    title: '⚠️ No se puede desactivar',
                    html: '<div class=\"text-start\"><p><strong>$nombres $apellidos</strong> tiene <strong>$casos_activos caso(s) activo(s)</strong>.</p><div class=\"alert alert-warning\"><strong>📋 Casos activos encontrados:</strong><br>• Debe finalizar o transferir los casos activos<br>• Solo se puede desactivar personal sin casos pendientes</div></div>',
                    icon: 'warning',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#f39c12',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'listar_personal.php';
                    }
                });
            </script>
            </body>
            </html>";
            return;
        }

        // Desactivar en lugar de eliminar
        $update_query = "UPDATE personal SET activo = 0 WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $id);

        if ($update_stmt->execute()) {
            echo "<!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <title>Personal Desactivado</title>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            </head>
            <body>
            <script>
                Swal.fire({
                    title: '✅ Personal Desactivado',
                    html: '<div class=\"text-start\"><p><strong>$nombres $apellidos</strong> ha sido desactivado exitosamente.</p><div class=\"alert alert-info\"><strong>ℹ️ Información:</strong><br>• El personal no aparecerá en las listas activas<br>• Se mantienen todos sus registros médicos<br>• Puede reactivarse desde administración</div></div>',
                    icon: 'success',
                    confirmButtonText: 'Volver a Lista',
                    confirmButtonColor: '#28a745',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'listar_personal.php';
                    }
                });
            </script>
            </body>
            </html>";
        } else {
            echo "<!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <title>Error</title>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            </head>
            <body>
            <script>
                Swal.fire({
                    title: '❌ Error',
                    text: 'No se pudo desactivar el personal. Intente nuevamente.',
                    icon: 'error',
                    confirmButtonText: 'Volver',
                    confirmButtonColor: '#dc3545'
                }).then(() => {
                    window.location.href = 'listar_personal.php';
                });
            </script>
            </body>
            </html>";
        }
        $update_stmt->close();
    } else {
        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <title>No Encontrado</title>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head>
        <body>
        <script>
            Swal.fire({
                title: '⚠️ Personal No Encontrado',
                text: 'El personal seleccionado no existe.',
                icon: 'warning',
                confirmButtonText: 'Volver'
            }).then(() => {
                window.location.href = 'listar_personal.php';
            });
        </script>
        </body>
        </html>";
    }
    $stmt->close();
} else {
    header("Location: listar_personal.php");
}
$conn->close();
?>