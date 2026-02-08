<?php
function safe_escape($value)
{
    return htmlspecialchars(trim($value ?? ''), ENT_QUOTES);
}

function alert_redirect($mensaje, $url)
{
    echo "<script>alert('$mensaje'); window.location.href='$url';</script>";
    exit();
}

function validar_imagen($archivo)
{
    $permitidos = ['image/jpeg', 'image/png', 'image/jpg'];
    return in_array($archivo['type'], $permitidos);
}

function guardar_imagen($archivo, $directorio = "../uploads/fotos_personal/")
{
    $nombre_archivo = uniqid("foto_") . "." . pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $ruta = $directorio . $nombre_archivo;
    if (move_uploaded_file($archivo['tmp_name'], $ruta)) {
        return $nombre_archivo;
    }
    return null;
}

function get_avatar_url($usuario_id, $conn)
{
    $avatar_url = "../assets/img/default-avatar.png";
    $stmt = $conn->prepare("SELECT foto FROM personal WHERE id = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $foto = $row['foto'];
        $ruta = "../uploads/fotos_personal/" . $foto;
        if (!empty($foto) && file_exists($ruta)) {
            $avatar_url = $ruta;
        }
    }

    return $avatar_url;
}

?>