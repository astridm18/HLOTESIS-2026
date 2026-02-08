<?php
session_start();
include("../../conexion.php");
require_once __DIR__ . '/../../config/forms/functions.php';
verificarSesion();
verificarRol(['administrador'], '../../index.php');

// Verificar que se reciba el ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
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
            text: 'No se especificó qué personal reactivar.',
            icon: 'error',
            confirmButtonText: 'Volver',
            confirmButtonColor: '#e74c3c'
        }).then(() => {
            window.location.href = 'personal_inactivo.php';
        });
    </script>
    </body>
    </html>";
    exit();
}

$id_personal = intval($_GET['id']);

// ✅ VERIFICAR QUE EL PERSONAL EXISTE Y ESTÁ INACTIVO
$stmt_check = $conn->prepare("SELECT id, nombres, apellidos, rol, especialidad, activo, email, cedula FROM personal WHERE id = ?");
$stmt_check->bind_param("i", $id_personal);
$stmt_check->execute();
$result = $stmt_check->get_result();

if ($result->num_rows === 0) {
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <title>Personal No Encontrado</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
    <script>
        Swal.fire({
            title: '🔍 Personal No Encontrado',
            text: 'El personal especificado no existe en el sistema.',
            icon: 'warning',
            confirmButtonText: 'Volver a la Lista',
            confirmButtonColor: '#f39c12'
        }).then(() => {
            window.location.href = 'personal_inactivo.php';
        });
    </script>
    </body>
    </html>";
    exit();
}

$personal = $result->fetch_assoc();
$stmt_check->close();

// Verificar si ya está activo
if ($personal['activo'] == 1) {
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <title>Personal Ya Activo</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
    <script>
        Swal.fire({
            title: '✅ Personal Ya Activo',
            html: '<strong>" . htmlspecialchars($personal['nombres']) . " " . htmlspecialchars($personal['apellidos']) . "</strong><br>ya se encuentra activo en el sistema.',
            icon: 'info',
            confirmButtonText: 'Ver Personal Activo',
            confirmButtonColor: '#27ae60'
        }).then(() => {
            window.location.href = 'listar_personal.php';
        });
    </script>
    </body>
    </html>";
    exit();
}

// ✅ VERIFICAR POSIBLES CONFLICTOS ANTES DE REACTIVAR
$conflictos = [];
$puede_reactivar = true;

try {
    // 1. Verificar si hay otro personal activo con la misma cédula
    $stmt_cedula = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM personal 
        WHERE cedula = ? AND activo = 1 AND id != ?
    ");
    $stmt_cedula->bind_param("si", $personal['cedula'], $id_personal);
    $stmt_cedula->execute();
    $cedula_duplicada = $stmt_cedula->get_result()->fetch_assoc()['total'];
    $stmt_cedula->close();

    if ($cedula_duplicada > 0) {
        $conflictos[] = "🆔 Ya existe personal activo con la cédula V-{$personal['cedula']}";
        $puede_reactivar = false;
    }

    // 2. Verificar si hay otro personal activo con el mismo email
    $stmt_email = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM personal 
        WHERE email = ? AND activo = 1 AND id != ?
    ");
    $stmt_email->bind_param("si", $personal['email'], $id_personal);
    $stmt_email->execute();
    $email_duplicado = $stmt_email->get_result()->fetch_assoc()['total'];
    $stmt_email->close();

    if ($email_duplicado > 0) {
        $conflictos[] = "📧 Ya existe personal activo con el email {$personal['email']}";
        $puede_reactivar = false;
    }

} catch (Exception $e) {
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <title>Error de Sistema</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
    <script>
        Swal.fire({
            title: '💥 Error de Sistema',
            text: 'Error al verificar conflictos: " . addslashes($e->getMessage()) . "',
            icon: 'error',
            confirmButtonText: 'Volver',
            confirmButtonColor: '#e74c3c'
        }).then(() => {
            window.location.href = 'personal_inactivo.php';
        });
    </script>
    </body>
    </html>";
    exit();
}

// ✅ SI HAY CONFLICTOS, MOSTRAR ADVERTENCIA
if (!$puede_reactivar) {
    $lista_conflictos = implode('<br>', array_map(function ($conflicto) {
        return "• " . $conflicto; }, $conflictos));

    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <title>Conflictos Encontrados</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
    <script>
        Swal.fire({
            title: '⚠️ No Se Puede Reactivar',
            html: `<div class='text-start'>
                <div class='alert alert-warning mb-3'>
                    <strong>" . htmlspecialchars($personal['nombres']) . " " . htmlspecialchars($personal['apellidos']) . "</strong><br>
                    <small>Cédula: V-" . htmlspecialchars($personal['cedula']) . " | Email: " . htmlspecialchars($personal['email']) . "</small>
                </div>
                <div class='alert alert-danger'>
                    <strong>🚫 Conflictos Encontrados:</strong><br><br>
                    " . $lista_conflictos . "
                </div>
                <div class='alert alert-info'>
                    <strong>📋 Soluciones:</strong><br>
                    • Desactive el personal con datos duplicados<br>
                    • O modifique los datos del personal inactivo<br>
                    • Luego podrá reactivarlo sin conflictos
                </div>
            </div>`,
            icon: 'warning',
            confirmButtonText: '📋 Ver Personal Activo',
            confirmButtonColor: '#f39c12',
            cancelButtonText: 'Volver a Inactivos',
            showCancelButton: true,
            cancelButtonColor: '#6c757d',
            width: '600px'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'listar_personal.php';
            } else {
                window.location.href = 'personal_inactivo.php';
            }
        });
    </script>
    </body>
    </html>";
    exit();
}

// ✅ SI NO HAY CONFLICTOS, PROCEDER CON LA REACTIVACIÓN
try {
    // Registrar usuario que reactiva
    $usuario_que_reactiva = $_SESSION['usuario_id'];
    $fecha_reactivacion = date('Y-m-d H:i:s');

    // REACTIVACIÓN: Cambiar activo = 1
    $stmt_reactivar = $conn->prepare("
        UPDATE personal 
        SET activo = 1
        WHERE id = ?
    ");
    $stmt_reactivar->bind_param("i", $id_personal);

    if ($stmt_reactivar->execute()) {
        $stmt_reactivar->close();

        // ✅ OPCIONAL: Registrar en auditoría si tienes tabla
        // $stmt_log = $conn->prepare("INSERT INTO auditoria_personal (id_personal, accion, usuario_id, fecha) VALUES (?, 'REACTIVADO', ?, NOW())");
        // $stmt_log->bind_param("ii", $id_personal, $usuario_que_reactiva);
        // $stmt_log->execute();
        // $stmt_log->close();

        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <title>Personal Reactivado</title>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head>
        <body>
        <script>
            Swal.fire({
                title: '🎉 ¡Personal Reactivado Exitosamente!',
                html: `<div class='text-start'>
                    <div class='alert alert-success'>
                        <i class='fas fa-user-check fa-2x mb-2'></i><br>
                        <strong>" . htmlspecialchars($personal['nombres']) . " " . htmlspecialchars($personal['apellidos']) . "</strong><br>
                        <small>Cédula: V-" . htmlspecialchars($personal['cedula']) . "</small><br>
                        <small>Rol: " . htmlspecialchars($personal['rol']) . " | Especialidad: " . htmlspecialchars($personal['especialidad']) . "</small><br>
                        ha sido <strong>reactivado</strong> exitosamente.
                    </div>
                    <div class='alert alert-info'>
                        <strong>📋 Estado Actual:</strong><br>
                        • ✅ Puede acceder al sistema nuevamente<br>
                        • ✅ Aparece en listas de personal activo<br>
                        • ✅ Disponible para nuevas asignaciones<br>
                        • ✅ Mantiene todo su historial previo<br>
                        • ✅ Conserva sus credenciales de acceso
                    </div>
                    <div class='alert alert-success'>
                        <strong>🔐 Acceso al Sistema:</strong><br>
                        • Usuario: " . htmlspecialchars($personal['email']) . "<br>
                        • Contraseña: <em>La misma que tenía antes</em><br>
                        • Estado: <span class='badge bg-success'>ACTIVO</span>
                    </div>
                </div>`,
                icon: 'success',
                confirmButtonText: '👥 Ver Personal Activo',
                confirmButtonColor: '#27ae60',
                showCancelButton: true,
                cancelButtonText: '📋 Ver Más Inactivos',
                cancelButtonColor: '#6c757d',
                allowOutsideClick: false,
                width: '650px'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'listar_personal.php';
                } else if (result.isDismissed) {
                    window.location.href = 'personal_inactivo.php';
                }
            });
        </script>
        </body>
        </html>";

    } else {
        throw new Exception("Error al ejecutar la reactivación: " . $stmt_reactivar->error);
    }

} catch (Exception $e) {
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <title>Error de Reactivación</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
    <script>
        Swal.fire({
            title: '💥 Error al Reactivar',
            text: 'Error: " . addslashes($e->getMessage()) . "',
            icon: 'error',
            confirmButtonText: 'Intentar de Nuevo',
            confirmButtonColor: '#e74c3c'
        }).then(() => {
            window.location.href = 'personal_inactivo.php';
        });
    </script>
    </body>
    </html>";
}

$conn->close();
?>