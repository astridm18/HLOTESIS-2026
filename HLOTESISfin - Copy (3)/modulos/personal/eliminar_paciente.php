<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.html");
    exit;
}

include("../../conexion.php");

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_paciente = (int) $_GET['id'];

    try {
        // Comenzar transacción
        $conn->autocommit(FALSE);

        // 1. Primero eliminar registros relacionados (si existen)

        // Eliminar antecedentes médicos relacionados con casos del paciente
        $sql_antecedentes = "DELETE antecedentes FROM antecedentes 
                            INNER JOIN casos_clinicos ON antecedentes.id_caso = casos_clinicos.id_caso 
                            WHERE casos_clinicos.id_paciente = ?";
        $stmt_antecedentes = $conn->prepare($sql_antecedentes);
        $stmt_antecedentes->bind_param("i", $id_paciente);
        $stmt_antecedentes->execute();

        // Eliminar signos vitales relacionados con casos del paciente
        $sql_signos = "DELETE signos_vitales FROM signos_vitales 
                      INNER JOIN casos_clinicos ON signos_vitales.id_caso = casos_clinicos.id_caso 
                      WHERE casos_clinicos.id_paciente = ?";
        $stmt_signos = $conn->prepare($sql_signos);
        $stmt_signos->bind_param("i", $id_paciente);
        $stmt_signos->execute();

        // Eliminar evoluciones médicas relacionadas con casos del paciente
        $sql_evoluciones = "DELETE evoluciones FROM evoluciones 
                           INNER JOIN casos_clinicos ON evoluciones.id_caso = casos_clinicos.id_caso 
                           WHERE casos_clinicos.id_paciente = ?";
        $stmt_evoluciones = $conn->prepare($sql_evoluciones);
        $stmt_evoluciones->bind_param("i", $id_paciente);
        $stmt_evoluciones->execute();

        // 2. Eliminar casos clínicos del paciente
        $sql_casos = "DELETE FROM casos_clinicos WHERE id_paciente = ?";
        $stmt_casos = $conn->prepare($sql_casos);
        $stmt_casos->bind_param("i", $id_paciente);
        $stmt_casos->execute();

        // 3. Finalmente eliminar el paciente
        $sql_paciente = "DELETE FROM pacientes WHERE id_paciente = ?";
        $stmt_paciente = $conn->prepare($sql_paciente);
        $stmt_paciente->bind_param("i", $id_paciente);

        if ($stmt_paciente->execute()) {
            if ($stmt_paciente->affected_rows > 0) {
                // Confirmar transacción
                $conn->commit();

                // Mostrar mensaje de éxito y redirigir
                echo "<!DOCTYPE html>
                <html>
                <head>
                    <meta charset='utf-8'>
                    <title>Paciente Eliminado</title>
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                </head>
                <body>
                    <script>
                        Swal.fire({
                            title: '✅ Eliminado',
                            text: 'El paciente y toda su información médica han sido eliminados correctamente.',
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then((result) => {
                            window.location.href = 'listar_pacientes.php';
                        });
                    </script>
                </body>
                </html>";
            } else {
                // Revertir transacción
                $conn->rollback();
                echo "<!DOCTYPE html>
                <html>
                <head>
                    <meta charset='utf-8'>
                    <title>Error</title>
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                </head>
                <body>
                    <script>
                        Swal.fire({
                            title: '❌ Error',
                            text: 'No se encontró el paciente a eliminar.',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        }).then((result) => {
                            window.location.href = 'listar_pacientes.php';
                        });
                    </script>
                </body>
                </html>";
            }
        } else {
            // Revertir transacción
            $conn->rollback();
            throw new Exception("Error al eliminar el paciente: " . $stmt_paciente->error);
        }

        // Cerrar statements
        $stmt_antecedentes->close();
        $stmt_signos->close();
        $stmt_evoluciones->close();
        $stmt_casos->close();
        $stmt_paciente->close();

    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conn->rollback();

        echo "<!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Error</title>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head>
        <body>
            <script>
                Swal.fire({
                    title: '❌ Error',
                    text: 'Error al eliminar el paciente: " . addslashes($e->getMessage()) . "',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                }).then((result) => {
                    window.location.href = 'listar_pacientes.php';
                });
            </script>
        </body>
        </html>";
    }

    // Restaurar autocommit
    $conn->autocommit(TRUE);

} else {
    // ID no válido
    echo "<!DOCTYPE html>
    <html>
    <head>
        <meta charset='utf-8'>
        <title>Error</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
        <script>
            Swal.fire({
                title: '❌ Error',
                text: 'ID de paciente no válido.',
                icon: 'error',
                confirmButtonText: 'Aceptar'
            }).then((result) => {
                window.location.href = 'listar_pacientes.php';
            });
        </script>
    </body>
    </html>";
}

$conn->close();
?>