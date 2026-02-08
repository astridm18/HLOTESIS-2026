<?php
// Incluir archivo de conexión
include("../../conexion.php");

// Verificar si el formulario se envió por POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Recoger TODOS los datos del formulario (todos obligatorios)
    $nombres = trim($_POST['nombres']);
    $apellidos = trim($_POST['apellidos']);
    $cedula = trim($_POST['cedula']);
    $tipo_cedula = trim($_POST['tipo_cedula']); // NUEVA LÍNEA
    $email = trim($_POST['email']);
    $cod_area = trim($_POST['cod_area']);
    $numero = trim($_POST['numero']);
    $rol = trim($_POST['rol']);
    $contrasena = trim($_POST['contrasena']);
    $confirmar_contrasena = trim($_POST['confirmar_contrasena']);
    $especialidad = trim($_POST['especialidad']);
    $direccion = trim($_POST['direccion']);

    // Validar que no haya campos vacíos
    if (
        empty($nombres) || empty($apellidos) || empty($cedula) || empty($email) ||
        empty($cod_area) || empty($numero) || empty($rol) || empty($contrasena) ||
        empty($confirmar_contrasena) || empty($especialidad) || empty($direccion)
    ) {
        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <title>Error de Validación</title>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head>
        <body>
        <script>
            Swal.fire({
                title: '⚠️ Campos Incompletos',
                text: 'Todos los campos son obligatorios. Por favor, complete la información.',
                icon: 'warning',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#f39c12',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.history.back();
                }
            });
        </script>
        </body>
        </html>";
        exit;
    }

    // VALIDACIÓN DE CONTRASEÑAS
    if ($contrasena !== $confirmar_contrasena) {
        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <title>Error de Contraseña</title>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head>
        <body>
        <script>
            Swal.fire({
                title: '🔐 Error de Contraseña',
                text: 'Las contraseñas no coinciden. Verifique e intente nuevamente.',
                icon: 'error',
                confirmButtonText: 'Corregir',
                confirmButtonColor: '#e74c3c',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.history.back();
                }
            });
        </script>
        </body>
        </html>";
        exit;
    }

    // Validar longitud mínima de contraseña (simplificado)
    if (strlen($contrasena) < 4) {
        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <title>Contraseña Insegura</title>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head>
        <body>
        <script>
            Swal.fire({
                title: '🔒 Contraseña Muy Corta',
                text: 'La contraseña debe tener al menos 4 caracteres para mayor seguridad.',
                icon: 'warning',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#f39c12',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.history.back();
                }
            });
        </script>
        </body>
        </html>";
        exit;
    }

    // Validar formato de email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <title>Email Inválido</title>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head>
        <body>
        <script>
            Swal.fire({
                title: '📧 Email Inválido',
                text: 'El formato del email no es válido. Ejemplo: usuario@dominio.com',
                icon: 'error',
                confirmButtonText: 'Corregir',
                confirmButtonColor: '#e74c3c',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.history.back();
                }
            });
        </script>
        </body>
        </html>";
        exit;
    }

    // Validar cédula (solo números, 7-8 dígitos)
    if (!preg_match('/^\d{7,8}$/', $cedula)) {
        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <title>Cédula Inválida</title>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head>
        <body>
        <script>
            Swal.fire({
                title: '🆔 Cédula Inválida',
                text: 'La cédula debe contener solo números y tener entre 7 y 8 dígitos.',
                icon: 'error',
                confirmButtonText: 'Corregir',
                confirmButtonColor: '#e74c3c',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.history.back();
                }
            });
        </script>
        </body>
        </html>";
        exit;
    }

    // Validar número de teléfono (7 dígitos)
    if (!preg_match('/^\d{7}$/', $numero)) {
        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <title>Teléfono Inválido</title>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head>
        <body>
        <script>
            Swal.fire({
                title: '📱 Teléfono Inválido',
                text: 'El número de teléfono debe tener exactamente 7 dígitos.',
                icon: 'error',
                confirmButtonText: 'Corregir',
                confirmButtonColor: '#e74c3c',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.history.back();
                }
            });
        </script>
        </body>
        </html>";
        exit;
    }

    // Hash de la contraseña
    $contrasena_hash = password_hash($contrasena, PASSWORD_BCRYPT);
    // Combinar tipo y número de cédula
    $cedula_completa = $tipo_cedula . $cedula; // Ejemplo: V-12345678

    // Procesar imagen - RUTA CORREGIDA
    $foto_nombre = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['tmp_name'] != "") {
        $upload_dir = "../../uploads/fotos_personal/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nombre_archivo = $cedula . "_" . time() . "." . $extension;
        $ruta_completa = $upload_dir . $nombre_archivo;

        // Validar que sea una imagen
        $tipos_permitidos = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array(strtolower($extension), $tipos_permitidos)) {
            echo "<!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <title>Archivo Inválido</title>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            </head>
            <body>
            <script>
                Swal.fire({
                    title: '🖼️ Formato No Permitido',
                    text: 'Solo se permiten archivos de imagen (JPG, JPEG, PNG, GIF).',
                    icon: 'error',
                    confirmButtonText: 'Seleccionar Otra',
                    confirmButtonColor: '#e74c3c',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.history.back();
                    }
                });
            </script>
            </body>
            </html>";
            exit;
        }

        // Validar tamaño (máximo 5MB)
        if ($_FILES['foto']['size'] > 5000000) {
            echo "<!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <title>Archivo Muy Grande</title>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            </head>
            <body>
            <script>
                Swal.fire({
                    title: '📁 Archivo Muy Grande',
                    text: 'La imagen no debe superar los 5MB. Intente con una imagen más pequeña.',
                    icon: 'warning',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#f39c12',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.history.back();
                    }
                });
            </script>
            </body>
            </html>";
            exit;
        }

        // Mover el archivo
        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_completa)) {
            echo "<!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <title>Error de Subida</title>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            </head>
            <body>
            <script>
                Swal.fire({
                    title: '⬆️ Error al Subir Imagen',
                    text: 'No se pudo guardar la imagen. Verifique los permisos del servidor.',
                    icon: 'error',
                    confirmButtonText: 'Intentar de Nuevo',
                    confirmButtonColor: '#e74c3c',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.history.back();
                    }
                });
            </script>
            </body>
            </html>";
            exit;
        }

        // Guardar solo el nombre del archivo en la BD, no la ruta completa
        $foto_nombre = $nombre_archivo;
    }

    // Verificar si el email ya existe
    $check_email = $conn->prepare("SELECT id FROM personal WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $result = $check_email->get_result();

    if ($result->num_rows > 0) {
        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <title>Email Duplicado</title>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head>
        <body>
        <script>
            Swal.fire({
                title: '📧 Email Ya Registrado',
                text: 'Este email ya está registrado en el sistema. Use otro email.',
                icon: 'warning',
                confirmButtonText: 'Cambiar Email',
                confirmButtonColor: '#f39c12',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.history.back();
                }
            });
        </script>
        </body>
        </html>";
        exit;
    }
    $check_email->close();

    // Verificar si la cédula ya existe
    // Verificar si la cédula ya existe
    $check_cedula = $conn->prepare("SELECT id FROM personal WHERE cedula = ?");
    $check_cedula->bind_param("s", $cedula_completa); // usar cédula completa
    $check_cedula->execute();
    $result_cedula = $check_cedula->get_result();

    if ($result_cedula->num_rows > 0) {
        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <title>Cédula Duplicada</title>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head>
        <body>
        <script>
            Swal.fire({
                title: '🆔 Cédula Ya Registrada',
                text: 'Esta cédula ya está registrada en el sistema. Verifique el número.',
                icon: 'warning',
                confirmButtonText: 'Verificar',
                confirmButtonColor: '#f39c12',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.history.back();
                }
            });
        </script>
        </body>
        </html>";
        exit;
    }
    $check_cedula->close();

    // Insertar TODOS los campos en la base de datos
    $stmt = $conn->prepare("INSERT INTO personal 
        (nombres, apellidos, cedula, email, cod_area, numero, rol, contrasena, especialidad, direccion, foto, activo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");

    // Bind con todos los parámetros
    $stmt->bind_param(
        "sssssssssss",
        $nombres,
        $apellidos,
        $cedula_completa, // usar cédula completa en lugar de solo $cedula
        $email,
        $cod_area,
        $numero,
        $rol,
        $contrasena_hash,
        $especialidad,
        $direccion,
        $foto_nombre
    );

    if ($stmt->execute()) {
        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <title>Registro Exitoso</title>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head>
        <body>
        <script>
            Swal.fire({
                title: '🎉 ¡Registro Exitoso!',
                html: '<p><strong>$nombres $apellidos</strong> ha sido registrado exitosamente en el sistema.</p><p>Cédula: <strong>V-$cedula</strong></p><p>Rol: <strong>$rol</strong></p>',
                icon: 'success',
                confirmButtonText: 'Registrar Otro Personal',
                confirmButtonColor: '#27ae60',
                showCancelButton: true,
                cancelButtonText: 'Ver Lista de Personal',
                cancelButtonColor: '#3498db',
                allowOutsideClick: false,
                customClass: {
                    popup: 'animated bounceIn'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'registro_personal.php';
                } else if (result.isDismissed) {
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
            <title>Error de Registro</title>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head>
        <body>
        <script>
            Swal.fire({
                title: '💥 Error en el Registro',
                text: 'Ocurrió un error al registrar el personal: " . addslashes($stmt->error) . "',
                icon: 'error',
                confirmButtonText: 'Intentar de Nuevo',
                confirmButtonColor: '#e74c3c',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.history.back();
                }
            });
        </script>
        </body>
        </html>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <title>Acceso Denegado</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
    <script>
        Swal.fire({
            title: '🚫 Acceso No Autorizado',
            text: 'No se puede acceder a esta página directamente.',
            icon: 'error',
            confirmButtonText: 'Ir a Registro',
            confirmButtonColor: '#e74c3c',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'registro_personal.php';
            }
        });
    </script>
    </body>
    </html>";
}
// VALIDACIÓN DEL TIPO DE CÉDULA (agregar después de otras validaciones)
if (!in_array($tipo_cedula, ['V-', 'E-', 'P-'])) {
    echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <title>Tipo de Cédula Inválido</title>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head>
        <body>
        <script>
            Swal.fire({
                title: '🆔 Tipo de Cédula Inválido',
                text: 'Debe seleccionar un tipo de cédula válido (V-, E-, P-).',
                icon: 'error',
                confirmButtonText: 'Corregir',
                confirmButtonColor: '#e74c3c',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.history.back();
                }
            });
        </script>
        </body>
        </html>";
    exit;
}
?>