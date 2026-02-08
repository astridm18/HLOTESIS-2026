<?php
include("conexion.php");

// Validar si llegó el formulario por POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombres = $_POST['nombres'];
    $apellidos = $_POST['apellidos'];
    $cedula = $_POST['cedula'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $rol = $_POST['rol'];
    $contrasena = password_hash($_POST['contrasena'], PASSWORD_BCRYPT);

    // Validar si se subió una foto
    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['tmp_name'] != "") {
        $foto = addslashes(file_get_contents($_FILES['foto']['tmp_name']));
    }

    // Preparar sentencia SQL
    $stmt = $conn->prepare("INSERT INTO personal (nombres, apellidos, cedula, email, telefono, rol, contrasena, foto)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $nombres, $apellidos, $cedula, $email, $telefono, $rol, $contrasena, $foto);

    if ($stmt->execute()) {
        echo "<script>alert('✅ Personal registrado exitosamente'); window.location.href='Register.html';</script>";
    } else {
        echo "<script>alert('❌ Error al registrar: " . $stmt->error . "'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Acceso no autorizado.";
}
?>