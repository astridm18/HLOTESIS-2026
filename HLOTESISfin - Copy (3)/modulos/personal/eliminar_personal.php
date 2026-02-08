<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.html");
    exit();
}

include("../../conexion.php");

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
            text: 'No se especificó qué personal eliminar.',
            icon: 'error',
            confirmButtonText: 'Volver',
            confirmButtonColor: '#e74c3c'
        }).then(() => {
            window.location.href = 'listar_personal.php';
        });
    </script>
    </body>
    </html>";
    exit();
}

$id_personal = intval($_GET['id']);

// ✅ VERIFICAR QUE EL PERSONAL EXISTE Y ESTÁ ACTIVO
$stmt_check = $conn->prepare("SELECT id, nombres, apellidos, rol, especialidad, activo FROM personal WHERE id = ?");
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
            window.location.href = 'listar_personal.php';
        });
    </script>
    </body>
    </html>";
    exit();
}

$personal = $result->fetch_assoc();
$stmt_check->close();

// Verificar si ya está desactivado
if ($personal['activo'] == 0) {
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <title>Personal Ya Desactivado</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
    <script>
        Swal.fire({
            title: '⚠️ Personal Ya Desactivado',
            html: '<strong>" . htmlspecialchars($personal['nombres']) . " " . htmlspecialchars($personal['apellidos']) . "</strong><br>ya se encuentra desactivado en el sistema.',
            icon: 'info',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#3498db'
        }).then(() => {
            window.location.href = 'listar_personal.php';
        });
    </script>
    </body>
    </html>";
    exit();
}

// ✅ VERIFICAR DEPENDENCIAS MÉDICAS
$dependencias = [];
$puede_desactivar = true;

try {
    // 1. Verificar casos clínicos activos como médico responsable
    $stmt_casos = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM casos_clinicos 
        WHERE id_medico_responsable = ? AND estado_caso = 'activo'
    ");
    $stmt_casos->bind_param("i", $id_personal);
    $stmt_casos->execute();
    $casos_activos = $stmt_casos->get_result()->fetch_assoc()['total'];
    $stmt_casos->close();

    if ($casos_activos > 0) {
        $dependencias[] = "👨‍⚕️ {$casos_activos} caso(s) clínico(s) activo(s) como médico responsable";
        $puede_desactivar = false;
    }

    // 2. Verificar evoluciones recientes (últimos 7 días)
    $stmt_evoluciones = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM evoluciones 
        WHERE id_medico = ? AND fecha_evolucion >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stmt_evoluciones->bind_param("i", $id_personal);
    $stmt_evoluciones->execute();
    $evoluciones_recientes = $stmt_evoluciones->get_result()->fetch_assoc()['total'];
    $stmt_evoluciones->close();

    if ($evoluciones_recientes > 0) {
        $dependencias[] = "📝 {$evoluciones_recientes} evolución(es) médica(s) en los últimos 7 días";
    }

    // 3. Verificar órdenes médicas pendientes
    $stmt_ordenes = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM ordenes_medicas 
        WHERE id_medico_ordena = ? AND estado IN ('pendiente', 'en_proceso')
    ");
    $stmt_ordenes->bind_param("i", $id_personal);
    $stmt_ordenes->execute();
    $ordenes_pendientes = $stmt_ordenes->get_result()->fetch_assoc()['total'];
    $stmt_ordenes->close();

    if ($ordenes_pendientes > 0) {
        $dependencias[] = "🩺 {$ordenes_pendientes} orden(es) médica(s) pendiente(s)";
        $puede_desactivar = false;
    }

    // 4. Verificar interconsultas activas
    $stmt_interconsultas = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM interconsultas 
        WHERE (id_medico_solicitante = ? OR id_medico_consultor = ?) 
        AND estado IN ('solicitada', 'en_revision')
    ");
    $stmt_interconsultas->bind_param("ii", $id_personal, $id_personal);
    $stmt_interconsultas->execute();
    $interconsultas_activas = $stmt_interconsultas->get_result()->fetch_assoc()['total'];
    $stmt_interconsultas->close();

    if ($interconsultas_activas > 0) {
        $dependencias[] = "🔄 {$interconsultas_activas} interconsulta(s) activa(s)";
        $puede_desactivar = false;
    }

    // 5. Verificar si es el único administrador activo (CRÍTICO)
    if (strtolower($personal['rol']) === 'administrador' || strtolower($personal['rol']) === 'admin') {
        $stmt_admin = $conn->prepare("
            SELECT COUNT(*) as total 
            FROM personal 
            WHERE (LOWER(rol) = 'administrador' OR LOWER(rol) = 'admin') AND activo = 1 AND id != ?
        ");
        $stmt_admin->bind_param("i", $id_personal);
        $stmt_admin->execute();
        $otros_admins = $stmt_admin->get_result()->fetch_assoc()['total'];
        $stmt_admin->close();

        if ($otros_admins === 0) {
            echo "<!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <title>Error Crítico</title>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            </head>
            <body>
            <script>
                Swal.fire({
                    title: '🚨 ERROR CRÍTICO',
                    html: '<div class=\"alert alert-danger\"><strong>No se puede desactivar este Administrador</strong><br><br>🔒 Es el único administrador activo en el sistema<br>⚠️ Desactivarlo dejaría el sistema sin administración<br><br><strong>Solución:</strong> Active otro administrador primero</div>',
                    icon: 'error',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#e74c3c',
                    allowOutsideClick: false
                }).then(() => {
                    window.location.href = 'listar_personal.php';
                });
            </script>
            </body>
            </html>";
            exit();
        }
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
            text: 'Error al verificar dependencias: " . addslashes($e->getMessage()) . "',
            icon: 'error',
            confirmButtonText: 'Volver',
            confirmButtonColor: '#e74c3c'
        }).then(() => {
            window.location.href = 'listar_personal.php';
        });
    </script>
    </body>
    </html>";
    exit();
}

// ✅ SI HAY DEPENDENCIAS CRÍTICAS, MOSTRAR ADVERTENCIA
if (!$puede_desactivar) {
    $lista_dependencias = implode('<br>', array_map(function ($dep) {
        return "• " . $dep; }, $dependencias));

    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <title>Dependencias Críticas</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
    <script>
        Swal.fire({
            title: '⛔ No Se Puede Desactivar',
            html: `<div class='text-start'>
                <div class='alert alert-warning mb-3'>
                    <strong>" . htmlspecialchars($personal['nombres']) . " " . htmlspecialchars($personal['apellidos']) . "</strong><br>
                    <small>Rol: " . htmlspecialchars($personal['rol']) . " | Especialidad: " . htmlspecialchars($personal['especialidad']) . "</small>
                </div>
                <div class='alert alert-danger'>
                    <strong>🚫 Dependencias Críticas Encontradas:</strong><br><br>
                    " . $lista_dependencias . "
                </div>
                <div class='alert alert-info'>
                    <strong>📋 Acciones Requeridas:</strong><br>
                    • Complete o transfiera los casos activos<br>
                    • Finalice las órdenes médicas pendientes<br>
                    • Resuelva las interconsultas activas<br>
                    • Luego podrá desactivar al personal
                </div>
            </div>`,
            icon: 'warning',
            confirmButtonText: '📋 Gestionar Dependencias',
            confirmButtonColor: '#f39c12',
            cancelButtonText: 'Volver a Lista',
            showCancelButton: true,
            cancelButtonColor: '#6c757d',
            width: '600px'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../../index.php?seccion=casos-activos';
            } else {
                window.location.href = 'listar_personal.php';
            }
        });
    </script>
    </body>
    </html>";
    exit();
}

// ✅ SI NO HAY DEPENDENCIAS CRÍTICAS, PROCEDER CON LA DESACTIVACIÓN
try {
    // Registrar fecha y usuario que desactiva
    $usuario_que_desactiva = $_SESSION['usuario_id'];
    $fecha_desactivacion = date('Y-m-d H:i:s');

    // SOFT DELETE: Cambiar activo = 0 en lugar de DELETE
    $stmt_desactivar = $conn->prepare("
        UPDATE personal 
        SET activo = 0
        WHERE id = ?
    ");
    $stmt_desactivar->bind_param("i", $id_personal);

    if ($stmt_desactivar->execute()) {
        $stmt_desactivar->close();

        $mensaje_dependencias = '';
        if (!empty($dependencias)) {
            $lista_info = implode('<br>', array_map(function ($dep) {
                return "• " . $dep; }, $dependencias));
            $mensaje_dependencias = "<div class='alert alert-info mt-3'><strong>📊 Registros Asociados (mantenidos):</strong><br>{$lista_info}</div>";
        }

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
                title: '✅ Personal Desactivado Exitosamente',
                html: `<div class='text-start'>
                    <div class='alert alert-success'>
                        <strong>" . htmlspecialchars($personal['nombres']) . " " . htmlspecialchars($personal['apellidos']) . "</strong><br>
                        <small>Rol: " . htmlspecialchars($personal['rol']) . " | Especialidad: " . htmlspecialchars($personal['especialidad']) . "</small><br>
                        ha sido <strong>desactivado</strong> del sistema.
                    </div>
                    <div class='alert alert-info'>
                        <strong>📋 Información Importante:</strong><br>
                        • El personal no podrá acceder al sistema<br>
                        • Sus registros históricos se mantienen<br>
                        • Puede ser reactivado cuando sea necesario<br>
                        • No aparecerá en nuevas asignaciones
                    </div>
                    " . $mensaje_dependencias . "
                </div>`,
                icon: 'success',
                confirmButtonText: '📋 Ver Lista de Personal',
                confirmButtonColor: '#27ae60',
                showCancelButton: true,
                cancelButtonText: '➕ Registrar Nuevo Personal',
                cancelButtonColor: '#3498db',
                allowOutsideClick: false,
                width: '600px'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'listar_personal.php';
                } else if (result.isDismissed) {
                    window.location.href = 'registro_personal.php';
                }
            });
        </script>
        </body>
        </html>";

    } else {
        throw new Exception("Error al ejecutar la desactivación: " . $stmt_desactivar->error);
    }

} catch (Exception $e) {
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <title>Error de Desactivación</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
    <script>
        Swal.fire({
            title: '💥 Error al Desactivar',
            text: 'Error: " . addslashes($e->getMessage()) . "',
            icon: 'error',
            confirmButtonText: 'Intentar de Nuevo',
            confirmButtonColor: '#e74c3c'
        }).then(() => {
            window.location.href = 'listar_personal.php';
        });
    </script>
    </body>
    </html>";
}

$conn->close();
?>