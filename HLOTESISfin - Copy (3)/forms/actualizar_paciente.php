<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    echo "no_session";
    exit;
}

include("../conexion.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Obtener datos del formulario
        $id_paciente = $_POST['id_paciente'] ?? '';
        $cedula = $_POST['cedula'] ?? '';
        $nombres = $_POST['nombres'] ?? '';
        $apellidos = $_POST['apellidos'] ?? '';
        $sexo = $_POST['sexo'] ?? '';
        $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
        $telefono = $_POST['telefono'] ?? '';
        $direccion = $_POST['direccion'] ?? '';

        // Validaciones básicas
        if (empty($id_paciente) || empty($cedula) || empty($nombres) || empty($apellidos) || empty($sexo)) {
            echo "Faltan campos obligatorios";
            exit;
        }

        // Verificar que la cédula no esté en uso por otro paciente
        $stmt_check = $conn->prepare("SELECT id_paciente FROM pacientes WHERE cedula = ? AND id_paciente != ?");
        $stmt_check->bind_param("si", $cedula, $id_paciente);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            echo "La cédula ya está registrada por otro paciente";
            exit;
        }

        // Preparar la consulta de actualización
        $sql = "UPDATE pacientes SET 
                cedula = ?, 
                nombres = ?, 
                apellidos = ?, 
                sexo = ?, 
                fecha_nacimiento = ?, 
                telefono = ?, 
                direccion = ?,
                actualizado_en = NOW()
                WHERE id_paciente = ?";

        $stmt = $conn->prepare($sql);

        // Manejar fecha de nacimiento vacía
        $fecha_nacimiento_final = empty($fecha_nacimiento) ? null : $fecha_nacimiento;

        $stmt->bind_param(
            "sssssssi",
            $cedula,
            $nombres,
            $apellidos,
            $sexo,
            $fecha_nacimiento_final,
            $telefono,
            $direccion,
            $id_paciente
        );

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo "ok";
            } else {
                echo "No se realizaron cambios";
            }
        } else {
            echo "Error al actualizar: " . $stmt->error;
        }

        $stmt->close();
        $stmt_check->close();

    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Método no permitido";
}

$conn->close();
?>