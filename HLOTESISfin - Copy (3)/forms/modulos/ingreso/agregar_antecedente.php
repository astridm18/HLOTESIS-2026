<?php
// modulos/ingreso/agregar_antecedente.php - VERSIÓN MEJORADA
require_once '../../config/database.php';
require_once '../../config/functions.php';
require_once 'gestor_archivos.php';

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
        $pdo->beginTransaction();
        
        // Crear gestor de archivos
        $gestor_archivos = new GestorArchivosAntecedentes($pdo);
        
        $archivo_adjunto = null;
        $texto_extraido = $datos['texto_extraido'] ?? null;
        $id_archivo = null;
        
        // Manejar archivo adjunto si existe
        if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
            // Procesar archivo con el gestor mejorado
            $resultado_archivo = $gestor_archivos->procesarArchivo(
                $_FILES['archivo'],
                $datos['id_caso'],
                $datos['tipo_antecedente']
            );
            
            if (!$resultado_archivo['exito']) {
                throw new Exception('Error procesando archivo: ' . $resultado_archivo['error']);
            }
            
            $archivo_adjunto = $resultado_archivo['info_archivo']['nombre_archivo'];
            $id_archivo = $resultado_archivo['id_archivo'];
            
            // Si se extrajo texto del archivo, usarlo si no hay texto manual
            if ($resultado_archivo['texto_extraido'] && empty($texto_extraido)) {
                $texto_extraido = $resultado_archivo['texto_extraido'];
            }
        }
        
        // Insertar antecedente
        $stmt = $pdo->prepare("
            INSERT INTO antecedentes 
            (id_caso, tipo_antecedente, descripcion, fecha_evento, relevancia,
             archivo_adjunto, texto_extraido, id_medico_registro, id_archivo_adjunto)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $datos['id_caso'],
            $datos['tipo_antecedente'],
            $datos['descripcion'],
            $datos['fecha_evento'] ?? null,
            $datos['relevancia'] ?? 'media',
            $archivo_adjunto,
            $texto_extraido,
            obtenerUsuarioActual()['id'],
            $id_archivo
        ]);
        
        $id_antecedente = $pdo->lastInsertId();
        
        // Actualizar referencia en archivo si existe
        if ($id_archivo) {
            $stmt_update = $pdo->prepare("
                UPDATE archivos_antecedentes 
                SET id_antecedente = ? 
                WHERE id_archivo = ?
            ");
            $stmt_update->execute([$id_antecedente, $id_archivo]);
        }
        
        // Registrar auditoría
        registrarAuditoria($pdo, 'antecedentes', $id_antecedente, 'INSERT', null, $datos);
        
        // Crear alerta si es de alta relevancia
        if (($datos['relevancia'] ?? 'media') === 'alta') {
            crearAlertaAntecedente($pdo, $datos['id_caso'], $id_antecedente, $datos);
        }
        
        $pdo->commit();
        
        // Preparar respuesta con información completa
        $respuesta = [
            'id_antecedente' => $id_antecedente,
            'archivo_procesado' => $archivo_adjunto !== null,
            'texto_extraido' => !empty($texto_extraido),
            'longitud_texto' => strlen($texto_extraido ?? ''),
            'id_archivo' => $id_archivo
        ];
        
        if ($archivo_adjunto) {
            $respuesta['info_archivo'] = [
                'nombre_original' => $_FILES['archivo']['name'],
                'nombre_archivo' => $archivo_adjunto,
                'tamaño' => $_FILES['archivo']['size'],
                'tipo' => $_FILES['archivo']['type']
            ];
        }
        
        respuestaJSON('ok', 'Antecedente agregado exitosamente', $respuesta);
        
    } catch(Exception $e) {
        $pdo->rollBack();
        respuestaJSON('error', 'Error al agregar antecedente: ' . $e->getMessage());
    }
}

/**
 * Crear alerta para antecedentes de alta relevancia
 */
function crearAlertaAntecedente($pdo, $id_caso, $id_antecedente, $datos) {
    try {
        // Obtener médicos responsables del caso
        $stmt = $pdo->prepare("
            SELECT DISTINCT id_medico
            FROM medicos_responsables 
            WHERE id_caso = ? AND activo = 1
            UNION
            SELECT id_medico_responsable
            FROM casos_clinicos 
            WHERE id_caso = ? AND id_medico_responsable IS NOT NULL
        ");
        
        $stmt->execute([$id_caso, $id_caso]);
        $medicos = $stmt->fetchAll();
        
        $titulo = "Antecedente de Alta Relevancia Registrado";
        $mensaje = "Se ha registrado un antecedente de alta relevancia:\n\n" .
                   "Tipo: " . ucfirst(str_replace('_', ' ', $datos['tipo_antecedente'])) . "\n" .
                   "Descripción: " . substr($datos['descripcion'], 0, 100) . 
                   (strlen($datos['descripcion']) > 100 ? '...' : '');
        
        foreach ($medicos as $medico) {
            crearAlerta($pdo, $id_caso, 'antecedente_alta_relevancia', $titulo, $mensaje, $medico['id_medico'], 'alta');
        }
        
    } catch (Exception $e) {
        // No interrumpir el proceso principal por errores de alertas
        error_log('Error creando alerta de antecedente: ' . $e->getMessage());
    }
}

/**
 * Obtener antecedentes de un caso con archivos
 */
function obtenerAntecedentesConArchivos($pdo, $id_caso) {
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            CONCAT(p.nombres, ' ', p.apellidos) as medico_registro,
            p.especialidad,
            aa.nombre_original as archivo_nombre_original,
            aa.mime_type as archivo_tipo,
            aa.tamaño as archivo_tamaño,
            aa.thumbnail as archivo_thumbnail,
            aa.id_archivo
        FROM antecedentes a
        LEFT JOIN personal p ON a.id_medico_registro = p.id
        LEFT JOIN archivos_antecedentes aa ON a.id_archivo_adjunto = aa.id_archivo
        WHERE a.id_caso = ? AND a.activo = 1
        ORDER BY a.relevancia DESC, a.fecha_evento DESC, a.creado_en DESC
    ");
    
    $stmt->execute([$id_caso]);
    return $stmt->fetchAll();
}

/**
 * Buscar antecedentes por texto
 */
function buscarAntecedentes($pdo, $termino_busqueda, $id_caso = null, $tipo = null) {
    $where_clauses = ["a.activo = 1"];
    $params = [];
    
    // Búsqueda en descripción y texto extraído
    $where_clauses[] = "(a.descripcion LIKE ? OR a.texto_extraido LIKE ?)";
    $params[] = "%{$termino_busqueda}%";
    $params[] = "%{$termino_busqueda}%";
    
    if ($id_caso) {
        $where_clauses[] = "a.id_caso = ?";
        $params[] = $id_caso;
    }
    
    if ($tipo) {
        $where_clauses[] = "a.tipo_antecedente = ?";
        $params[] = $tipo;
    }
    
    $where_sql = implode(' AND ', $where_clauses);
    
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            CONCAT(p.nombres, ' ', p.apellidos) as medico_registro,
            CONCAT(pac.nombres, ' ', pac.apellidos) as paciente,
            cc.numero_caso
        FROM antecedentes a
        LEFT JOIN personal p ON a.id_medico_registro = p.id
        LEFT JOIN casos_clinicos cc ON a.id_caso = cc.id_caso
        LEFT JOIN pacientes pac ON cc.id_paciente = pac.id_paciente
        WHERE {$where_sql}
        ORDER BY a.relevancia DESC, a.creado_en DESC
        LIMIT 50
    ");
    
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Generar reporte de antecedentes
 */
function generarReporteAntecedentes($pdo, $filtros = []) {
    $where_clauses = ["a.activo = 1"];
    $params = [];
    
    if (!empty($filtros['fecha_desde'])) {
        $where_clauses[] = "a.creado_en >= ?";
        $params[] = $filtros['fecha_desde'] . ' 00:00:00';
    }
    
    if (!empty($filtros['fecha_hasta'])) {
        $where_clauses[] = "a.creado_en <= ?";
        $params[] = $filtros['fecha_hasta'] . ' 23:59:59';
    }
    
    if (!empty($filtros['tipo_antecedente'])) {
        $where_clauses[] = "a.tipo_antecedente = ?";
        $params[] = $filtros['tipo_antecedente'];
    }
    
    if (!empty($filtros['relevancia'])) {
        $where_clauses[] = "a.relevancia = ?";
        $params[] = $filtros['relevancia'];
    }
    
    if (!empty($filtros['con_archivo'])) {
        $where_clauses[] = "a.archivo_adjunto IS NOT NULL";
    }
    
    $where_sql = implode(' AND ', $where_clauses);
    
    $stmt = $pdo->prepare("
        SELECT 
            a.tipo_antecedente,
            a.relevancia,
            COUNT(*) as cantidad,
            COUNT(CASE WHEN a.archivo_adjunto IS NOT NULL THEN 1 END) as con_archivo,
            COUNT(CASE WHEN a.texto_extraido IS NOT NULL THEN 1 END) as con_texto_extraido,
            AVG(LENGTH(a.descripcion)) as promedio_descripcion
        FROM antecedentes a
        WHERE {$where_sql}
        GROUP BY a.tipo_antecedente, a.relevancia
        ORDER BY a.tipo_antecedente, a.relevancia DESC
    ");
    
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Validar y corregir texto de antecedente médico
 */
function validarTextoAntecedente($texto) {
    // Limpiar texto
    $texto = trim($texto);
    
    // Verificar longitud mínima
    if (strlen($texto) < 10) {
        return [
            'valido' => false,
            'error' => 'La descripción debe tener al menos 10 caracteres'
        ];
    }
    
    // Verificar longitud máxima
    if (strlen($texto) > 5000) {
        return [
            'valido' => false,
            'error' => 'La descripción no puede exceder 5000 caracteres'
        ];
    }
    
    // Correcciones automáticas comunes
    $correcciones = [
        // Errores de OCR comunes
        'paci ente' => 'paciente',
        'medi camento' => 'medicamento',
        'diag nóstico' => 'diagnóstico',
        'trata miento' => 'tratamiento',
        
        // Términos médicos
        'hipertencion' => 'hipertensión',
        'diabetes mellitos' => 'diabetes mellitus',
        'colesterol alto' => 'hipercolesterolemia',
        'trigliceridos altos' => 'hipertrigliceridemia'
    ];
    
    foreach ($correcciones as $error => $correccion) {
        $texto = str_ireplace($error, $correccion, $texto);
    }
    
    return [
        'valido' => true,
        'texto_corregido' => $texto
    ];
}

/**
 * Actualizar antecedente existente
 */
function actualizarAntecedente($pdo, $id_antecedente, $datos, $archivo = null) {
    try {
        $pdo->beginTransaction();
        
        // Obtener antecedente actual
        $stmt = $pdo->prepare("SELECT * FROM antecedentes WHERE id_antecedente = ?");
        $stmt->execute([$id_antecedente]);
        $antecedente_actual = $stmt->fetch();
        
        if (!$antecedente_actual) {
            throw new Exception('Antecedente no encontrado');
        }
        
        // Procesar nuevo archivo si se proporciona
        $nuevo_archivo = null;
        $nuevo_texto_extraido = $datos['texto_extraido'] ?? $antecedente_actual['texto_extraido'];
        
        if ($archivo && $archivo['error'] === UPLOAD_ERR_OK) {
            $gestor_archivos = new GestorArchivosAntecedentes($pdo);
            $resultado_archivo = $gestor_archivos->procesarArchivo(
                $archivo,
                $antecedente_actual['id_caso'],
                $datos['tipo_antecedente']
            );
            
            if ($resultado_archivo['exito']) {
                $nuevo_archivo = $resultado_archivo['info_archivo']['nombre_archivo'];
                
                // Eliminar archivo anterior si existe
                if ($antecedente_actual['id_archivo_adjunto']) {
                    $gestor_archivos->eliminarArchivo(
                        $antecedente_actual['id_archivo_adjunto'],
                        'Reemplazado por nuevo archivo'
                    );
                }
                
                // Usar texto extraído del nuevo archivo si está disponible
                if ($resultado_archivo['texto_extraido']) {
                    $nuevo_texto_extraido = $resultado_archivo['texto_extraido'];
                }
            }
        }
        
        // Actualizar antecedente
        $stmt = $pdo->prepare("
            UPDATE antecedentes 
            SET tipo_antecedente = ?, descripcion = ?, fecha_evento = ?, relevancia = ?,
                archivo_adjunto = COALESCE(?, archivo_adjunto),
                texto_extraido = ?, actualizado_en = CURRENT_TIMESTAMP
            WHERE id_antecedente = ?
        ");
        
        $stmt->execute([
            $datos['tipo_antecedente'],
            $datos['descripcion'],
            $datos['fecha_evento'] ?? null,
            $datos['relevancia'] ?? 'media',
            $nuevo_archivo,
            $nuevo_texto_extraido,
            $id_antecedente
        ]);
        
        // Registrar auditoría
        registrarAuditoria($pdo, 'antecedentes', $id_antecedente, 'UPDATE', $antecedente_actual, $datos);
        
        $pdo->commit();
        
        return [
            'exito' => true,
            'mensaje' => 'Antecedente actualizado exitosamente'
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return [
            'exito' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Endpoint para obtener antecedentes (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['accion'])) {
    switch ($_GET['accion']) {
        case 'listar':
            if (!isset($_GET['id_caso'])) {
                respuestaJSON('error', 'ID de caso requerido');
            }
            $antecedentes = obtenerAntecedentesConArchivos($pdo, $_GET['id_caso']);
            respuestaJSON('ok', 'Antecedentes obtenidos', $antecedentes);
            break;
            
        case 'buscar':
            if (!isset($_GET['termino'])) {
                respuestaJSON('error', 'Término de búsqueda requerido');
            }
            $resultados = buscarAntecedentes(
                $pdo, 
                $_GET['termino'], 
                $_GET['id_caso'] ?? null,
                $_GET['tipo'] ?? null
            );
            respuestaJSON('ok', 'Búsqueda completada', $resultados);
            break;
            
        case 'reporte':
            $filtros = array_intersect_key($_GET, array_flip([
                'fecha_desde', 'fecha_hasta', 'tipo_antecedente', 'relevancia', 'con_archivo'
            ]));
            $reporte = generarReporteAntecedentes($pdo, $filtros);
            respuestaJSON('ok', 'Reporte generado', $reporte);
            break;
            
        default:
            respuestaJSON('error', 'Acción no válida');
    }
}

// Endpoint para actualizar antecedente (PUT)
if ($_SERVER['REQUEST_METHOD'] === 'PUT' || 
    ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'PUT')) {
    
    if (!isset($_POST['id_antecedente'])) {
        respuestaJSON('error', 'ID de antecedente requerido');
    }
    
    $resultado = actualizarAntecedente($pdo, $_POST['id_antecedente'], sanitizar($_POST), $_FILES['archivo'] ?? null);
    
    if ($resultado['exito']) {
        respuestaJSON('ok', $resultado['mensaje']);
    } else {
        respuestaJSON('error', $resultado['error']);
    }
}
?>