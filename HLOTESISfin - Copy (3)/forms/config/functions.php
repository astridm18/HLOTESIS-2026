<?php
// config/functions.php

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verificar si el usuario está logueado
 */
function verificarSesion() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ../../login.php');
        exit();
    }
}

/**
 * Verificar que el usuario tenga uno de los roles permitidos (después de verificarSesion).
 * @param array $roles_permitidos Ej: ['medico', 'administrador']
 * @param string $redireccion URL si no tiene permiso (opcional)
 */
function verificarRol(array $roles_permitidos, $redireccion = null) {
    $rol_actual = strtolower(trim($_SESSION['usuario_rol'] ?? ''));
    foreach ($roles_permitidos as $r) {
        if ($rol_actual === strtolower(trim($r))) {
            return;
        }
    }
    if ($redireccion) {
        header('Location: ' . $redireccion);
    } else {
        header('HTTP/1.1 403 Forbidden');
        echo 'Acceso no permitido para su rol.';
    }
    exit();
}

/**
 * Obtener información del usuario actual
 */
function obtenerUsuarioActual() {
    if (isset($_SESSION['usuario_id'])) {
        return [
            'id' => $_SESSION['usuario_id'],
            'nombre' => $_SESSION['usuario_nombre'] ?? '',
            'apellido' => $_SESSION['usuario_apellido'] ?? '',
            'nombre_completo' => ($_SESSION['usuario_nombre'] ?? '') . ' ' . ($_SESSION['usuario_apellido'] ?? ''),
            'rol' => $_SESSION['usuario_rol'] ?? '',
            'especialidad' => $_SESSION['usuario_especialidad'] ?? '',
            'cedula' => $_SESSION['usuario_cedula'] ?? ''
        ];
    }
    return null;
}

/**
 * Función para respuesta JSON
 */
function respuestaJSON($status, $message, $data = null) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Obtener roles de la aplicación (config/roles.php). Fuente única para etiquetas y listados.
 * @return array [ clave => etiqueta ]
 */
function getRoles() {
    static $roles = null;
    if ($roles === null) {
        $path = __DIR__ . '/../../config/roles.php';
        $roles = is_file($path) ? require $path : [];
    }
    return $roles;
}

/**
 * Obtener listado de personal activo (compartido por ingreso y procesos).
 * @param PDO $pdo Conexión PDO
 * @param string $tipo 'todos', 'medico', 'enfermero', 'medicina', 'enfermeria'
 * @return array
 */
function obtener_personal_bd($pdo, $tipo = 'todos') {
    $where = "WHERE activo = 1";
    $params = [];
    $t = strtolower(trim($tipo));
    if ($t === 'medico' || $t === 'medicina') {
        $where .= " AND (rol = 'medico' OR rol = 'Medicina' OR rol LIKE '%medico%')";
    } elseif ($t === 'enfermero' || $t === 'enfermeria') {
        $where .= " AND (rol = 'enfermero' OR rol = 'Enfermeria' OR rol LIKE '%enferm%')";
    } elseif ($t !== 'todos' && $t !== '') {
        $where .= " AND rol = ?";
        $params[] = $tipo;
    }
    $stmt = $pdo->prepare("
        SELECT id, nombres, apellidos, cedula, rol, especialidad,
               CONCAT(nombres, ' ', apellidos) as nombre_completo
        FROM personal
        $where
        ORDER BY nombres, apellidos
    ");
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Sanitizar datos de entrada
 */
function sanitizar($data) {
    if (is_array($data)) {
        return array_map('sanitizar', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Registrar auditoría de cambios
 */
function registrarAuditoria($pdo, $tabla, $id_registro, $accion, $valores_anteriores = null, $valores_nuevos = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO auditoria 
            (tabla_afectada, id_registro, accion, valores_anteriores, valores_nuevos, id_usuario, ip_usuario, navegador)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $usuario = obtenerUsuarioActual();
        $stmt->execute([
            $tabla,
            $id_registro,
            $accion,
            $valores_anteriores ? json_encode($valores_anteriores, JSON_UNESCAPED_UNICODE) : null,
            $valores_nuevos ? json_encode($valores_nuevos, JSON_UNESCAPED_UNICODE) : null,
            $usuario['id'] ?? 0,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch(Exception $e) {
        error_log("Error en auditoría: " . $e->getMessage());
    }
}

/**
 * Validar archivo subido
 */
function validarArchivo($archivo) {
    $errores = [];
    $max_size = 10 * 1024 * 1024; // 10MB
    $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'txt'];
    
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        switch($archivo['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $errores[] = "El archivo es demasiado grande";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $errores[] = "El archivo excede el tamaño permitido";
                break;
            case UPLOAD_ERR_PARTIAL:
                $errores[] = "El archivo se subió parcialmente";
                break;
            case UPLOAD_ERR_NO_FILE:
                $errores[] = "No se subió ningún archivo";
                break;
            default:
                $errores[] = "Error desconocido al subir el archivo";
                break;
        }
        return $errores;
    }
    
    if ($archivo['size'] > $max_size) {
        $errores[] = "El archivo es demasiado grande (máximo 10MB)";
    }
    
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $extensiones_permitidas)) {
        $errores[] = "Tipo de archivo no permitido. Extensiones permitidas: " . implode(', ', $extensiones_permitidas);
    }
    
    // Validar tipo MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $archivo['tmp_name']);
    finfo_close($finfo);
    
    $mimes_permitidos = [
        'image/jpeg', 'image/png', 'image/gif',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain'
    ];
    
    if (!in_array($mime_type, $mimes_permitidos)) {
        $errores[] = "Tipo de archivo no seguro";
    }
    
    return $errores;
}

/**
 * Generar nombre único para archivo
 */
function generarNombreArchivo($nombre_original, $prefijo = '') {
    $extension = pathinfo($nombre_original, PATHINFO_EXTENSION);
    $nombre_limpio = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($nombre_original, PATHINFO_FILENAME));
    return $prefijo . $nombre_limpio . '_' . uniqid() . '_' . time() . '.' . $extension;
}

/**
 * Crear directorio si no existe
 */
function crearDirectorio($ruta) {
    if (!is_dir($ruta)) {
        return mkdir($ruta, 0755, true);
    }
    return true;
}

/**
 * Formatear fecha para mostrar
 */
function formatearFecha($fecha, $incluir_hora = false) {
    if (empty($fecha) || $fecha === '0000-00-00' || $fecha === '0000-00-00 00:00:00') {
        return '-';
    }
    
    $timestamp = strtotime($fecha);
    if ($timestamp === false) {
        return '-';
    }
    
    if ($incluir_hora) {
        return date('d/m/Y H:i', $timestamp);
    } else {
        return date('d/m/Y', $timestamp);
    }
}

/**
 * Calcular edad a partir de fecha de nacimiento
 */
function calcularEdad($fecha_nacimiento) {
    if (empty($fecha_nacimiento) || $fecha_nacimiento === '0000-00-00') {
        return null;
    }
    
    $nacimiento = new DateTime($fecha_nacimiento);
    $hoy = new DateTime();
    $edad = $hoy->diff($nacimiento);
    
    return $edad->y;
}

/**
 * Validar cédula ecuatoriana (básico)
 */
function validarCedula($cedula) {
    $cedula = trim($cedula);
    
    // Debe tener 10 dígitos
    if (strlen($cedula) !== 10 || !ctype_digit($cedula)) {
        return false;
    }
    
    // Los dos primeros dígitos deben ser válidos (01-24)
    $provincia = intval(substr($cedula, 0, 2));
    if ($provincia < 1 || $provincia > 24) {
        return false;
    }
    
    // Algoritmo de validación del dígito verificador
    $coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
    $suma = 0;
    
    for ($i = 0; $i < 9; $i++) {
        $valor = intval($cedula[$i]) * $coeficientes[$i];
        if ($valor >= 10) {
            $valor = $valor - 9;
        }
        $suma += $valor;
    }
    
    $digito_verificador = ($suma % 10 === 0) ? 0 : 10 - ($suma % 10);
    
    return $digito_verificador === intval($cedula[9]);
}

/**
 * Generar contraseña aleatoria
 */
function generarContrasena($longitud = 8) {
    $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $contrasena = '';
    
    for ($i = 0; $i < $longitud; $i++) {
        $contrasena .= $caracteres[rand(0, strlen($caracteres) - 1)];
    }
    
    return $contrasena;
}

/**
 * Hashear contraseña
 */
function hashearContrasena($contrasena) {
    return password_hash($contrasena, PASSWORD_DEFAULT);
}

/**
 * Verificar contraseña
 */
function verificarContrasena($contrasena, $hash) {
    return password_verify($contrasena, $hash);
}

/**
 * Limpiar número de teléfono
 */
function limpiarTelefono($telefono) {
    // Remover todo excepto números
    $telefono = preg_replace('/[^0-9]/', '', $telefono);
    
    // Si empieza con 593 (código de Ecuador), removerlo
    if (substr($telefono, 0, 3) === '593') {
        $telefono = substr($telefono, 3);
    }
    
    return $telefono;
}

/**
 * Formatear número de teléfono
 */
function formatearTelefono($telefono) {
    $telefono = limpiarTelefono($telefono);
    
    if (strlen($telefono) === 10) {
        return substr($telefono, 0, 4) . '-' . substr($telefono, 4, 3) . '-' . substr($telefono, 7);
    } elseif (strlen($telefono) === 9) {
        return substr($telefono, 0, 2) . '-' . substr($telefono, 2, 3) . '-' . substr($telefono, 5);
    }
    
    return $telefono;
}

/**
 * Validar email
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Obtener lista de servicios médicos
 */
function obtenerServicios() {
    return [
        'Emergencias' => 'Emergencias',
        'Medicina Interna' => 'Medicina Interna',
        'Cirugía General' => 'Cirugía General',
        'Pediatría' => 'Pediatría',
        'Ginecología' => 'Ginecología',
        'Traumatología' => 'Traumatología',
        'Cardiología' => 'Cardiología',
        'Neurología' => 'Neurología',
        'Urología' => 'Urología',
        'Oftalmología' => 'Oftalmología',
        'Otorrinolaringología' => 'Otorrinolaringología',
        'Dermatología' => 'Dermatología',
        'Psiquiatría' => 'Psiquiatría',
        'Anestesiología' => 'Anestesiología',
        'Radiología' => 'Radiología',
        'Laboratorio' => 'Laboratorio',
        'UCI' => 'UCI',
        'Neonatología' => 'Neonatología'
    ];
}

/**
 * Obtener roles de personal
 */
function obtenerRoles() {
    return [
        'Admin' => 'Administrador',
        'Dirección' => 'Dirección',
        'Medicina' => 'Médico',
        'Enfermería' => 'Enfermero/a',
        'Laboratorio' => 'Laboratorista',
        'Radiología' => 'Radiólogo',
        'Farmacia' => 'Farmacéutico',
        'Recepción' => 'Recepcionista'
    ];
}

/**
 * Verificar permisos de usuario
 */
function verificarPermiso($permiso_requerido) {
    $usuario = obtenerUsuarioActual();
    if (!$usuario) {
        return false;
    }
    
    $permisos_por_rol = [
        'Admin' => ['todos'],
        'Dirección' => ['ver_reportes', 'gestionar_personal', 'ver_pacientes'],
        'Medicina' => ['ver_pacientes', 'crear_evoluciones', 'crear_ordenes', 'crear_casos'],
        'Enfermería' => ['ver_pacientes', 'signos_vitales', 'notas_enfermeria'],
        'Laboratorio' => ['ver_ordenes_lab', 'subir_resultados'],
        'Radiología' => ['ver_ordenes_imagen', 'subir_resultados'],
        'Farmacia' => ['ver_prescripciones', 'dispensar_medicamentos'],
        'Recepción' => ['ver_pacientes', 'crear_pacientes']
    ];
    
    $permisos_usuario = $permisos_por_rol[$usuario['rol']] ?? [];
    
    return in_array('todos', $permisos_usuario) || in_array($permiso_requerido, $permisos_usuario);
}

/**
 * Enviar notificación por email (básico)
 */
function enviarNotificacion($destinatario, $asunto, $mensaje) {
    // Implementación básica - en producción usar PHPMailer o similar
    $headers = 'From: sistema@hospital.com' . "\r\n" .
               'Reply-To: noreply@hospital.com' . "\r\n" .
               'Content-Type: text/html; charset=UTF-8' . "\r\n" .
               'X-Mailer: PHP/' . phpversion();
    
    return mail($destinatario, $asunto, $mensaje, $headers);
}

/**
 * Crear alerta en el sistema
 */
function crearAlerta($pdo, $id_caso, $tipo, $titulo, $mensaje, $id_usuario_destino, $prioridad = 'media') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO alertas 
            (id_caso, tipo_alerta, titulo, mensaje, prioridad, id_usuario_destino, id_usuario_creador)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $usuario = obtenerUsuarioActual();
        $stmt->execute([
            $id_caso,
            $tipo,
            $titulo,
            $mensaje,
            $prioridad,
            $id_usuario_destino,
            $usuario['id']
        ]);
        
        return $pdo->lastInsertId();
    } catch(Exception $e) {
        error_log("Error creando alerta: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener alertas activas del usuario
 */
function obtenerAlertasUsuario($pdo, $id_usuario = null) {
    if (!$id_usuario) {
        $usuario = obtenerUsuarioActual();
        $id_usuario = $usuario['id'];
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT a.*, cc.numero_caso, CONCAT(p.nombres, ' ', p.apellidos) as paciente
            FROM alertas a
            INNER JOIN casos_clinicos cc ON a.id_caso = cc.id_caso
            INNER JOIN pacientes p ON cc.id_paciente = p.id_paciente
            WHERE a.id_usuario_destino = ? 
            AND a.activa = 1 
            AND a.leida = 0
            AND (a.fecha_vencimiento IS NULL OR a.fecha_vencimiento >= NOW())
            ORDER BY a.prioridad DESC, a.fecha_creacion DESC
        ");
        
        $stmt->execute([$id_usuario]);
        return $stmt->fetchAll();
    } catch(Exception $e) {
        error_log("Error obteniendo alertas: " . $e->getMessage());
        return [];
    }
}

/**
 * Marcar alerta como leída
 */
function marcarAlertaLeida($pdo, $id_alerta) {
    try {
        $stmt = $pdo->prepare("
            UPDATE alertas 
            SET leida = 1, fecha_lectura = NOW() 
            WHERE id_alerta = ?
        ");
        
        return $stmt->execute([$id_alerta]);
    } catch(Exception $e) {
        error_log("Error marcando alerta: " . $e->getMessage());
        return false;
    }
}

/**
 * Escapar datos para CSV
 */
function escaparCSV($data) {
    if (strpos($data, ',') !== false || strpos($data, '"') !== false || strpos($data, "\n") !== false) {
        $data = '"' . str_replace('"', '""', $data) . '"';
    }
    return $data;
}

/**
 * Generar token único
 */
function generarToken($longitud = 32) {
    return bin2hex(random_bytes($longitud / 2));
}

/**
 * Log de errores personalizado
 */
function logError($mensaje, $archivo = '', $linea = '') {
    $fecha = date('Y-m-d H:i:s');
    $usuario = obtenerUsuarioActual();
    $usuario_info = $usuario ? " [Usuario: {$usuario['id']} - {$usuario['nombre_completo']}]" : " [Usuario: No autenticado]";
    $archivo_info = $archivo ? " [Archivo: $archivo]" : "";
    $linea_info = $linea ? " [Línea: $linea]" : "";
    
    $log_mensaje = "[$fecha]$usuario_info$archivo_info$linea_info $mensaje" . PHP_EOL;
    
    error_log($log_mensaje, 3, __DIR__ . '/../logs/sistema.log');
}

/**
 * Verificar si es una fecha válida
 */
function esFechaValida($fecha, $formato = 'Y-m-d') {
    $d = DateTime::createFromFormat($formato, $fecha);
    return $d && $d->format($formato) === $fecha;
}

/**
 * Convertir array a XML
 */
function arrayToXML($data, $rootNodeName = 'root', $xml = null) {
    if ($xml === null) {
        $xml = new SimpleXMLElement('<' . $rootNodeName . '/>');
    }
    
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $subnode = $xml->addChild($key);
            arrayToXML($value, $key, $subnode);
        } else {
            $xml->addChild($key, htmlspecialchars($value));
        }
    }
    
    return $xml->asXML();
}

/**
 * Redimensionar imagen
 */
function redimensionarImagen($archivo_origen, $archivo_destino, $ancho_max = 800, $alto_max = 600) {
    $info = getimagesize($archivo_origen);
    if (!$info) {
        return false;
    }
    
    list($ancho_original, $alto_original, $tipo) = $info;
    
    // Calcular nuevas dimensiones
    $ratio = min($ancho_max / $ancho_original, $alto_max / $alto_original);
    $nuevo_ancho = $ancho_original * $ratio;
    $nuevo_alto = $alto_original * $ratio;
    
    // Crear imagen desde archivo
    switch ($tipo) {
        case IMAGETYPE_JPEG:
            $imagen_original = imagecreatefromjpeg($archivo_origen);
            break;
        case IMAGETYPE_PNG:
            $imagen_original = imagecreatefrompng($archivo_origen);
            break;
        case IMAGETYPE_GIF:
            $imagen_original = imagecreatefromgif($archivo_origen);
            break;
        default:
            return false;
    }
    
    // Crear nueva imagen
    $nueva_imagen = imagecreatetruecolor($nuevo_ancho, $nuevo_alto);
    
    // Preservar transparencia para PNG
    if ($tipo === IMAGETYPE_PNG) {
        imagealphablending($nueva_imagen, false);
        imagesavealpha($nueva_imagen, true);
    }
    
    // Redimensionar
    imagecopyresampled(
        $nueva_imagen, $imagen_original,
        0, 0, 0, 0,
        $nuevo_ancho, $nuevo_alto,
        $ancho_original, $alto_original
    );
    
    // Guardar imagen
    $resultado = false;
    switch ($tipo) {
        case IMAGETYPE_JPEG:
            $resultado = imagejpeg($nueva_imagen, $archivo_destino, 85);
            break;
        case IMAGETYPE_PNG:
            $resultado = imagepng($nueva_imagen, $archivo_destino);
            break;
        case IMAGETYPE_GIF:
            $resultado = imagegif($nueva_imagen, $archivo_destino);
            break;
    }
    
    // Limpiar memoria
    imagedestroy($imagen_original);
    imagedestroy($nueva_imagen);
    
    return $resultado;
}

/**
 * Función para debug (solo en desarrollo)
 */
function debug($data, $die = false) {
    if (defined('DEBUG') && DEBUG === true) {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        
        if ($die) {
            die();
        }
    }
}

// Definir constantes si no existen
if (!defined('DEBUG')) {
    define('DEBUG', false);
}

if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', __DIR__ . '/../uploads/');
}

if (!defined('LOG_PATH')) {
    define('LOG_PATH', __DIR__ . '/../logs/');
}

// Crear directorios necesarios
if (!is_dir(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}

if (!is_dir(LOG_PATH)) {
    mkdir(LOG_PATH, 0755, true);
}
/**
 * Obtener el próximo número de historia disponible
 */
function obtenerProximoNumeroHistoria($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT COALESCE(MAX(CAST(numero_historia AS UNSIGNED)), 0) + 1 as proximo
            FROM pacientes
        ");
        $stmt->execute();
        $resultado = $stmt->fetch();
        
        return str_pad($resultado['proximo'], 6, '0', STR_PAD_LEFT);
    } catch(Exception $e) {
        return '000001'; // Número por defecto si hay error
    }
}
?>