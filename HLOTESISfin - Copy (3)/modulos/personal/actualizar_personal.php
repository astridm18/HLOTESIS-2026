<?php
include("../../conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST["id"];
    $nombres = $_POST["nombres"];
    $apellidos = $_POST["apellidos"];
    $cedula = $_POST["cedula"]; // ❌ NO limpiar aquí, mantener como viene
    $email = $_POST["email"];
    $cod_area = $_POST["cod_area"] ?? '';
    $numero = $_POST["numero"] ?? '';
    $rol = $_POST["rol"];
    $especialidad = $_POST["especialidad"] ?? '';
    $direccion = $_POST["direccion"] ?? '';
    $contrasena = $_POST["contrasena"] ?? '';
    $foto_ruta = $_POST["foto_actual"] ?? null;

    // Procesar imagen nueva si se cargó - RUTA CORREGIDA
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $directorio_fotos = "../../uploads/fotos_personal/";

        // Crear directorio si no existe
        if (!file_exists($directorio_fotos)) {
            mkdir($directorio_fotos, 0777, true);
        }

        $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nombre_archivo = $cedula . '_' . time() . '.' . $extension;
        $ruta_completa = $directorio_fotos . $nombre_archivo;

        $tipos_permitidos = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array(strtolower($extension), $tipos_permitidos)) {
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_completa)) {
                // Eliminar foto anterior si existe
                if (!empty($foto_ruta) && file_exists("../../uploads/fotos_personal/" . $foto_ruta)) {
                    unlink("../../uploads/fotos_personal/" . $foto_ruta);
                }
                // GUARDAR SOLO EL NOMBRE DEL ARCHIVO
                $foto_ruta = $nombre_archivo;
            } else {
                echo "error: No se pudo subir la nueva imagen.";
                exit;
            }
        } else {
            echo "error: Tipo de archivo no permitido.";
            exit;
        }
    }

    // Si se ingresó una nueva contraseña, la actualizamos también
    if (!empty($contrasena)) {
        $hash = password_hash($contrasena, PASSWORD_BCRYPT);
        $sql = "UPDATE personal SET 
            nombres = ?, apellidos = ?, cedula = ?, email = ?, cod_area = ?, numero = ?, rol = ?, foto = ?, especialidad = ?, direccion = ?, contrasena = ?
            WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssssi", $nombres, $apellidos, $cedula, $email, $cod_area, $numero, $rol, $foto_ruta, $especialidad, $direccion, $hash, $id);
    } else {
        // Sin contraseña nueva
        $sql = "UPDATE personal SET 
            nombres = ?, apellidos = ?, cedula = ?, email = ?, cod_area = ?, numero = ?, rol = ?, foto = ?, especialidad = ?, direccion = ?
            WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssssi", $nombres, $apellidos, $cedula, $email, $cod_area, $numero, $rol, $foto_ruta, $especialidad, $direccion, $id);
    }

    if ($stmt->execute()) {
        echo "ok";
    } else {
        echo "error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>