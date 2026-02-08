<?php
// modulos/ingreso/descargar.php
require_once '../../config/database.php';
require_once '../../config/functions.php';

// Verificar sesión
verificarSesion();

// Verificar parámetros
if (!isset($_GET['token']) && !isset($_GET['id'])) {
    http_response_code(400);
    die('Parámetros inválidos');
}

try {
    $archivo_info = null;
    
    // Descarga por token (más segura)
    if (isset($_GET['token'])) {
        $archivo_info = obtenerArchivoPorToken($_GET['token']);
    } 
    // Descarga directa por ID (requiere permisos)
    elseif (isset($_GET['id'])) {
        $archivo_info = obtenerArchivoPorId($_GET['id']);
    }
    
    if (!$archivo_info) {
        http_response_code(404);
        die('Archivo no encontrado o token expirado');
    }
    
    // Verificar permisos del usuario
    if (!verificarPermisoArchivo($archivo_info)) {
        http_response_code(403);
        die('Sin permisos para acceder a este archivo');
    }
    
    // Ruta completa del archivo
    $ruta_archivo = '../../uploads/antecedentes/' . $archivo_info['ruta_archivo'];
    
    // Verificar que el archivo existe físicamente
    if (!file_exists($ruta_archivo)) {
        http_response_code(404);
        die('Archivo físico no encontrado');
    }
    
    // Verificar integridad del archivo
    $hash_actual = hash_file('sha256', $ruta_archivo);
    if ($hash_actual !== $archivo_info['hash_archivo']) {
        error_log("Integridad comprometida para archivo ID: {$archivo_info['id_archivo']}");
        http_response_code(500);
        die('Error de integridad del archivo');
    }
    
    // Registrar descarga
    registrarDescarga($archivo_info['id_archivo'], $_GET['token'] ?? null);
    
    // Determinar tipo de descarga
    $tipo_descarga = $_GET['tipo'] ?? 'download';
    
    switch ($tipo_descarga) {
        case 'view':
            mostrarArchivo($ruta_archivo, $archivo_info);
            break;
        case 'thumbnail':
            mostrarThumbnail($archivo_info);
            break;
        case 'download':
        default:
            descargarArchivo($ruta_archivo, $archivo_info);
            break;
    }
    
} catch (Exception $e) {
    error_log('Error en descarga: ' . $e->getMessage());
    http_response_code(500);
    die('Error interno del servidor');
}

/**
 * Obtener información del archivo por token
 */
function obtenerArchivoPorToken($token) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT a.*, t.usado, t.fecha_expiracion
        FROM archivos_antecedentes a
        INNER JOIN tokens_descarga t ON a.id_archivo = t.id_archivo
        WHERE t.token = ? 
        AND t.fecha_expiracion > NOW() 
        AND a.activo = 1
    ");
    
    $stmt->execute([$token]);
    $resultado = $stmt->fetch();
    
    if ($resultado) {
        // Marcar token como usado si es de un solo uso
        $stmt_uso = $pdo->prepare("
            UPDATE tokens_descarga 
            SET usado = 1, fecha_uso = NOW(), ip_uso = ?
            WHERE token = ?
        ");
        $stmt_uso->execute([$_SERVER['REMOTE_ADDR'] ?? '', $token]);
    }
    
    return $resultado;
}

/**
 * Obtener información del archivo por ID
 */
function obtenerArchivoPorId($id_archivo) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM archivos_antecedentes 
        WHERE id_archivo = ? AND activo = 1
    ");
    
    $stmt->execute([$id_archivo]);
    return $stmt->fetch();
}

/**
 * Verificar permisos del usuario para acceder al archivo
 */
function verificarPermisoArchivo($archivo_info) {
    global $pdo;
    $usuario = obtenerUsuarioActual();
    
    // Administradores tienen acceso total
    if ($usuario['rol'] === 'Admin') {
        return true;
    }
    
    // Verificar si el usuario tiene acceso al caso
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as tiene_acceso
        FROM casos_clinicos cc
        LEFT JOIN medicos_responsables mr ON cc.id_caso = mr.id_caso
        WHERE cc.id_caso = ? 
        AND (
            cc.id_medico_responsable = ? 
            OR mr.id_medico = ?
            OR ? IN (SELECT id_medico_ingreso FROM datos_ingreso WHERE id_caso = cc.id_caso)
        )
    ");
    
    $stmt->execute([
        $archivo_info['id_caso'],
        $usuario['id'],
        $usuario['id'],
        $usuario['id']
    ]);
    
    $resultado = $stmt->fetch();
    return $resultado['tiene_acceso'] > 0;
}

/**
 * Registrar la descarga en logs de auditoría
 */
function registrarDescarga($id_archivo, $token = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO log_descargas 
            (id_archivo, id_usuario, token_usado, ip_usuario, user_agent, fecha_descarga)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $usuario = obtenerUsuarioActual();
        $stmt->execute([
            $id_archivo,
            $usuario['id'],
            $token,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $e) {
        // No interrumpir la descarga por errores de log
        error_log('Error registrando descarga: ' . $e->getMessage());
    }
}

/**
 * Descargar archivo
 */
function descargarArchivo($ruta_archivo, $archivo_info) {
    // Headers para descarga
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $archivo_info['nombre_original'] . '"');
    header('Content-Length: ' . filesize($ruta_archivo));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Leer archivo en chunks para manejar archivos grandes
    $handle = fopen($ruta_archivo, 'rb');
    if ($handle) {
        while (!feof($handle)) {
            echo fread($handle, 8192);
            flush();
        }
        fclose($handle);
    }
}

/**
 * Mostrar archivo en el navegador
 */
function mostrarArchivo($ruta_archivo, $archivo_info) {
    // Tipos MIME seguros para mostrar en navegador
    $tipos_seguros = [
        'image/jpeg', 'image/png', 'image/gif', 'image/bmp',
        'application/pdf', 'text/plain', 'text/csv'
    ];
    
    if (in_array($archivo_info['mime_type'], $tipos_seguros)) {
        header('Content-Type: ' . $archivo_info['mime_type']);
        header('Content-Disposition: inline; filename="' . $archivo_info['nombre_original'] . '"');
    } else {
        // Para tipos no seguros, forzar descarga
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $archivo_info['nombre_original'] . '"');
    }
    
    header('Content-Length: ' . filesize($ruta_archivo));
    header('Cache-Control: public, max-age=3600');
    
    readfile($ruta_archivo);
}

/**
 * Mostrar thumbnail de imagen
 */
function mostrarThumbnail($archivo_info) {
    if (!$archivo_info['thumbnail']) {
        // Mostrar imagen por defecto o error
        http_response_code(404);
        die('Thumbnail no disponible');
    }
    
    $ruta_thumb = '../../uploads/antecedentes/' . $archivo_info['thumbnail'];
    
    if (!file_exists($ruta_thumb)) {
        http_response_code(404);
        die('Thumbnail no encontrado');
    }
    
    // Obtener tipo MIME del thumbnail
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $ruta_thumb);
    finfo_close($finfo);
    
    header('Content-Type: ' . $mime_type);
    header('Content-Length: ' . filesize($ruta_thumb));
    header('Cache-Control: public, max-age=86400'); // Cache por 24 horas
    
    readfile($ruta_thumb);
}

/**
 * Crear tabla de logs de descarga si no existe
 */
function crearTablaLogDescargas() {
    global $pdo;
    
    $sql = "
    CREATE TABLE IF NOT EXISTS `log_descargas` (
        `id_log` int NOT NULL AUTO_INCREMENT,
        `id_archivo` int NOT NULL,
        `id_usuario` int NOT NULL,
        `token_usado` varchar(64) DEFAULT NULL,
        `ip_usuario` varchar(45) NOT NULL,
        `user_agent` text,
        `fecha_descarga` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id_log`),
        KEY `idx_archivo` (`id_archivo`),
        KEY `idx_usuario` (`id_usuario`),
        KEY `idx_fecha` (`fecha_descarga`),
        CONSTRAINT `fk_log_archivo` FOREIGN KEY (`id_archivo`) REFERENCES `archivos_antecedentes` (`id_archivo`),
        CONSTRAINT `fk_log_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `personal` (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
    ";
    
    $pdo->exec($sql);
}

// Crear tabla al incluir el archivo
crearTablaLogDescargas();
?>