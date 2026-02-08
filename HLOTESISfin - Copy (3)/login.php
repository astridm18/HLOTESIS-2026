<?php
session_start();
include("conexion.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password']; // Coincide con el HTML

    try {
        // Verificar si existe el usuario (SIN verificar activo primero para dar mejor feedback)
        $stmt = $conn->prepare("SELECT id, nombres, apellidos, email, contrasena, rol, activo FROM personal WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $usuario = $resultado->fetch_assoc();

            // Verificar si la cuenta está activa
            if ($usuario['activo'] != 1) {
                echo "<!DOCTYPE html>
                <html lang='es'>
                <head>
                    <meta charset='UTF-8'>
                    <title>Cuenta Desactivada</title>
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                </head>
                <body>
                <script>
                    Swal.fire({
                        title: '🚫 Cuenta Desactivada',
                        text: 'Su cuenta ha sido desactivada. Contacte al administrador para más información.',
                        icon: 'warning',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#f39c12',
                        allowOutsideClick: false,
                        customClass: {
                            popup: 'animated bounceIn'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'login.html';
                        }
                    });
                </script>
                </body>
                </html>";
                exit;
            }

            // Verificar contraseña (compatibilidad con hasheadas y texto plano)
            $password_valida = false;

            // Intentar verificación con hash
            if (password_verify($password, $usuario['contrasena'])) {
                $password_valida = true;
            }
            // Fallback para contraseñas en texto plano (temporal)
            elseif ($password === $usuario['contrasena']) {
                $password_valida = true;

                // Opcional: actualizar a hash para seguridad futura
                $hash_nuevo = password_hash($password, PASSWORD_DEFAULT);
                $stmt_update = $conn->prepare("UPDATE personal SET contrasena = ? WHERE id = ?");
                $stmt_update->bind_param("si", $hash_nuevo, $usuario['id']);
                $stmt_update->execute();
                $stmt_update->close();
            }

            if ($password_valida) {
                // Login exitoso - guardar información en sesión
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nombre'] = $usuario['nombres'];
                $_SESSION['usuario_apellidos'] = $usuario['apellidos'];
                $_SESSION['usuario_email'] = $usuario['email'];
                $_SESSION['usuario_rol'] = $usuario['rol'];

                // Redirigir directamente al dashboard
                header("Location: index.php");
                exit;
            } else {
                echo "<!DOCTYPE html>
                <html lang='es'>
                <head>
                    <meta charset='UTF-8'>
                    <title>Contraseña Incorrecta</title>
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                </head>
                <body>
                <script>
                    Swal.fire({
                        title: '🔐 Contraseña Incorrecta',
                        text: 'La contraseña ingresada no es correcta. Verifique e intente nuevamente.',
                        icon: 'error',
                        confirmButtonText: 'Intentar de Nuevo',
                        confirmButtonColor: '#e74c3c',
                        allowOutsideClick: false,
                        customClass: {
                            popup: 'animated shake'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'login.html';
                        }
                    });
                </script>
                </body>
                </html>";
            }
        } else {
            echo "<!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <title>Usuario No Encontrado</title>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            </head>
            <body>
            <script>
                Swal.fire({
                    title: '👤 Usuario No Encontrado',
                    text: 'No se encontró una cuenta con este email. Verifique el correo electrónico.',
                    icon: 'warning',
                    confirmButtonText: 'Verificar Email',
                    confirmButtonColor: '#f39c12',
                    allowOutsideClick: false,
                    customClass: {
                        popup: 'animated bounceIn'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'login.html';
                    }
                });
            </script>
            </body>
            </html>";
        }

        $stmt->close();

    } catch (Exception $e) {
        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <title>Error del Sistema</title>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head>
        <body>
        <script>
            Swal.fire({
                title: '💥 Error del Sistema',
                text: 'Ocurrió un error inesperado. Intente nuevamente en unos momentos.',
                icon: 'error',
                confirmButtonText: 'Intentar de Nuevo',
                confirmButtonColor: '#e74c3c',
                allowOutsideClick: false,
                customClass: {
                    popup: 'animated bounceIn'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'login.html';
                }
            });
        </script>
        </body>
        </html>";
    }

    $conn->close();
} else {
    header("Location: login.html");
    exit;
}
?>