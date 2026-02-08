<?php
// modulos/ingreso/listar_antecedentes.php
require_once '../../config/forms/database.php';
require_once '../../config/forms/functions.php';

verificarSesion();

$id_paciente = $_GET['paciente'] ?? 0;

if (!$id_paciente) {
    respuestaJSON('error', 'ID de paciente no especificado');
}

try {
    // Obtener antecedentes del paciente de casos anteriores
    $stmt = $pdo->prepare("
        SELECT 
            a.id_antecedente,
            a.tipo_antecedente,
            a.descripcion,
            a.fecha_evento,
            a.relevancia,
            a.archivo_adjunto,
            a.fecha_registro,
            cc.numero_caso
        FROM antecedentes a
        INNER JOIN casos_clinicos cc ON a.id_caso = cc.id_caso
        WHERE cc.id_paciente = ?
        ORDER BY a.fecha_registro DESC, a.relevancia DESC
    ");

    $stmt->execute([$id_paciente]);
    $antecedentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Agrupar por tipo para evitar duplicados similares
    $antecedentesAgrupados = [];
    $tipos_vistos = [];

    foreach ($antecedentes as $antecedente) {
        $clave = $antecedente['tipo_antecedente'] . '_' . substr(md5($antecedente['descripcion']), 0, 8);

        if (!isset($tipos_vistos[$clave])) {
            $antecedentesAgrupados[] = $antecedente;
            $tipos_vistos[$clave] = true;
        }
    }

    respuestaJSON('ok', 'Antecedentes obtenidos', $antecedentesAgrupados);

} catch (Exception $e) {
    respuestaJSON('error', 'Error al obtener antecedentes: ' . $e->getMessage());
}
?>

<?php
// =============================================================================
// ARCHIVO ADICIONAL: modificar_agregar_antecedente.php
// Modificación para agregar antecedentes desde el formulario de ingreso
// =============================================================================

// Este es el contenido modificado para agregar_antecedente.php para que funcione desde el ingreso

/*
// modulos/ingreso/agregar_antecedente.php - VERSIÓN MODIFICADA
require_once '../../config/forms/database.php';
require_once '../../config/forms/functions.php';

verificarSesion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = sanitizar($_POST);
    
    $campos_requeridos = ['id_caso', 'tipo_antecedente', 'descripcion'];
    foreach ($campos_requeridos as $campo) {
        if (empty($datos[$campo])) {
            respuestaJSON('error', "El campo $campo es requerido");
        }
    }
    
    try {
        $archivo_adjunto = null;
        $texto_extraido = null;
        
        // Manejar archivo adjunto si existe
        if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
            $errores = validarArchivo($_FILES['archivo']);
            if (!empty($errores)) {
                respuestaJSON('error', implode(', ', $errores));
            }
            
            $nombre_archivo = generarNombreArchivo($_FILES['archivo']['name']);
            $ruta_destino = "../../uploads/antecedentes/" . $nombre_archivo;
            
            if (!is_dir(dirname($ruta_destino))) {
                mkdir(dirname($ruta_destino), 0755, true);
            }
            
            if (move_uploaded_file($_FILES['archivo']['tmp_name'], $ruta_destino)) {
                $archivo_adjunto = $nombre_archivo;
            }
        }
        
        // SOLUCIÓN: Obtener y validar el ID del médico actual
        $usuario_actual = obtenerUsuarioActual();
        $id_medico_registro = null;
        
        if ($usuario_actual && isset($usuario_actual['id'])) {
            // Verificar que el usuario existe en la tabla personal
            $stmt_verificar = $pdo->prepare("SELECT id FROM personal WHERE id = ? AND activo = 1");
            $stmt_verificar->execute([$usuario_actual['id']]);
            
            if ($stmt_verificar->fetchColumn()) {
                $id_medico_registro = $usuario_actual['id'];
            } else {
                // Si el usuario actual no está en personal, usar un médico por defecto
                $stmt_medico = $pdo->prepare("SELECT id FROM personal WHERE rol = 'medico' AND activo = 1 LIMIT 1");
                $stmt_medico->execute();
                $id_medico_registro = $stmt_medico->fetchColumn();
                
                if (!$id_medico_registro) {
                    // Si no hay médicos, usar cualquier personal activo
                    $stmt_personal = $pdo->prepare("SELECT id FROM personal WHERE activo = 1 LIMIT 1");
                    $stmt_personal->execute();
                    $id_medico_registro = $stmt_personal->fetchColumn();
                }
            }
        } else {
            // Si no se puede obtener usuario actual, usar médico por defecto
            $stmt_medico = $pdo->prepare("SELECT id FROM personal WHERE rol = 'medico' AND activo = 1 LIMIT 1");
            $stmt_medico->execute();
            $id_medico_registro = $stmt_medico->fetchColumn();
            
            if (!$id_medico_registro) {
                // Si no hay médicos, usar cualquier personal activo
                $stmt_personal = $pdo->prepare("SELECT id FROM personal WHERE activo = 1 LIMIT 1");
                $stmt_personal->execute();
                $id_medico_registro = $stmt_personal->fetchColumn();
            }
        }
        
        // Validar que tenemos un ID de médico válido
        if (!$id_medico_registro) {
            respuestaJSON('error', 'No se pudo determinar el médico responsable. Contacte al administrador.');
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO antecedentes 
            (id_caso, tipo_antecedente, descripcion, fecha_evento, relevancia,
             archivo_adjunto, texto_extraido, id_medico_registro)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $datos['id_caso'],
            $datos['tipo_antecedente'],
            $datos['descripcion'],
            $datos['fecha_evento'] ?? null,
            $datos['relevancia'] ?? 'media',
            $archivo_adjunto,
            $texto_extraido,
            $id_medico_registro  // Usar el ID validado
        ]);
        
        $id_antecedente = $pdo->lastInsertId();
        
        // Registrar auditoría si la función existe
        if (function_exists('registrarAuditoria')) {
            registrarAuditoria($pdo, 'antecedentes', $id_antecedente, 'INSERT', null, $datos);
        }
        
        respuestaJSON('ok', 'Antecedente agregado exitosamente', [
            'id_antecedente' => $id_antecedente
        ]);
        
    } catch(Exception $e) {
        respuestaJSON('error', 'Error al agregar antecedente: ' . $e->getMessage());
    }
}

// Función auxiliar para validar archivos
function validarArchivo($archivo) {
    $errores = [];
    
    $tiposPermitidos = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/jpg',
        'image/png'
    ];
    
    $tamañoMaximo = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($archivo['type'], $tiposPermitidos)) {
        $errores[] = 'Tipo de archivo no permitido';
    }
    
    if ($archivo['size'] > $tamañoMaximo) {
        $errores[] = 'El archivo es muy grande (máximo 5MB)';
    }
    
    return $errores;
}

// Función auxiliar para generar nombre único de archivo
function generarNombreArchivo($nombreOriginal) {
    $extension = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
    $nombreBase = pathinfo($nombreOriginal, PATHINFO_FILENAME);
    $nombreBase = preg_replace('/[^a-zA-Z0-9]/', '_', $nombreBase);
    
    return $nombreBase . '_' . uniqid() . '.' . $extension;
}
*/
?>