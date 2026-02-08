<?php
// modulos/ingreso/procesar_ocr.php
require_once '../../config/forms/database.php';
require_once '../../config/forms/functions.php';

verificarSesion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No se recibió un archivo válido');
        }
        
        $archivo = $_FILES['archivo'];
        $errores = validarArchivo($archivo);
        
        if (!empty($errores)) {
            throw new Exception(implode(', ', $errores));
        }
        
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $mime_type = mime_content_type($archivo['tmp_name']);
        
        $texto_extraido = '';
        
        // Procesar según el tipo de archivo
        if (in_array($mime_type, ['image/jpeg', 'image/png', 'image/gif'])) {
            $texto_extraido = procesarImagenOCR($archivo['tmp_name']);
        } elseif ($mime_type === 'application/pdf') {
            $texto_extraido = procesarPDFTexto($archivo['tmp_name']);
        } elseif (in_array($mime_type, ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])) {
            $texto_extraido = procesarDocumentoWord($archivo['tmp_name'], $extension);
        } elseif ($mime_type === 'text/plain') {
            $texto_extraido = file_get_contents($archivo['tmp_name']);
        } else {
            throw new Exception('Tipo de archivo no soportado para extracción de texto');
        }
        
        respuestaJSON('ok', 'Texto extraído exitosamente', [
            'texto' => trim($texto_extraido),
            'longitud' => strlen(trim($texto_extraido))
        ]);
        
    } catch (Exception $e) {
        respuestaJSON('error', 'Error al procesar archivo: ' . $e->getMessage());
    }
}

/**
 * Procesar imagen con OCR usando Tesseract
 */
function procesarImagenOCR($ruta_archivo) {
    // Verificar si Tesseract está disponible
    $tesseract_path = which('tesseract');
    
    if (!$tesseract_path) {
        // Si no está disponible Tesseract, usar método alternativo
        return procesarImagenAlternativo($ruta_archivo);
    }
    
    try {
        // Crear archivo temporal para el resultado
        $archivo_salida = tempnam(sys_get_temp_dir(), 'ocr_');
        
        // Comando Tesseract (español e inglés)
        $comando = escapeshellcmd($tesseract_path) . ' ' . 
                   escapeshellarg($ruta_archivo) . ' ' . 
                   escapeshellarg($archivo_salida) . ' -l spa+eng 2>&1';
        
        exec($comando, $output, $return_code);
        
        if ($return_code === 0 && file_exists($archivo_salida . '.txt')) {
            $texto = file_get_contents($archivo_salida . '.txt');
            unlink($archivo_salida . '.txt');
            return $texto;
        } else {
            throw new Exception('Error ejecutando Tesseract: ' . implode(' ', $output));
        }
        
    } catch (Exception $e) {
        error_log('Error OCR: ' . $e->getMessage());
        return procesarImagenAlternativo($ruta_archivo);
    }
}

/**
 * Método alternativo para extraer texto de imágenes (simulado)
 */
function procesarImagenAlternativo($ruta_archivo) {
    // Este es un método de fallback - en producción podrías usar:
    // - Google Vision API
    // - Amazon Textract
    // - Azure Computer Vision
    // - Otro servicio OCR
    
    // Por ahora retornamos un mensaje indicativo
    return "TEXTO EXTRAÍDO DE IMAGEN (método alternativo)\n\n" .
           "Para obtener OCR real, instale Tesseract o configure un servicio de OCR externo.\n\n" .
           "Archivo procesado: " . basename($ruta_archivo) . "\n" .
           "Fecha: " . date('Y-m-d H:i:s');
}

/**
 * Extraer texto de archivos PDF
 */
function procesarPDFTexto($ruta_archivo) {
    // Intentar con pdftotext (parte de poppler-utils)
    $pdftotext_path = which('pdftotext');
    
    if ($pdftotext_path) {
        try {
            $archivo_salida = tempnam(sys_get_temp_dir(), 'pdf_text_');
            $comando = escapeshellcmd($pdftotext_path) . ' ' . 
                       escapeshellarg($ruta_archivo) . ' ' . 
                       escapeshellarg($archivo_salida) . ' 2>&1';
            
            exec($comando, $output, $return_code);
            
            if ($return_code === 0 && file_exists($archivo_salida)) {
                $texto = file_get_contents($archivo_salida);
                unlink($archivo_salida);
                return $texto;
            }
        } catch (Exception $e) {
            error_log('Error pdftotext: ' . $e->getMessage());
        }
    }
    
    // Método alternativo con PHP-PDF parser
    try {
        return extraerTextoPDFAlternativo($ruta_archivo);
    } catch (Exception $e) {
        error_log('Error PDF alternativo: ' . $e->getMessage());
        return "TEXTO EXTRAÍDO DE PDF (método básico)\n\n" .
               "Para mejor extracción de PDF, instale poppler-utils (pdftotext).\n\n" .
               "Archivo procesado: " . basename($ruta_archivo) . "\n" .
               "Fecha: " . date('Y-m-d H:i:s');
    }
}

/**
 * Método alternativo para extraer texto de PDF
 */
function extraerTextoPDFAlternativo($ruta_archivo) {
    // Método básico usando regex para encontrar texto en PDF
    $contenido = file_get_contents($ruta_archivo);
    
    if (!$contenido) {
        throw new Exception('No se pudo leer el archivo PDF');
    }
    
    // Buscar streams de texto en el PDF
    $texto = '';
    
    // Patrón para encontrar objetos de texto
    if (preg_match_all('/BT\s*(.*?)\s*ET/s', $contenido, $matches)) {
        foreach ($matches[1] as $match) {
            // Extraer texto entre paréntesis
            if (preg_match_all('/\((.*?)\)/s', $match, $text_matches)) {
                foreach ($text_matches[1] as $text) {
                    $texto .= $text . ' ';
                }
            }
        }
    }
    
    // Limpiar y decodificar
    $texto = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $texto);
    $texto = preg_replace('/\s+/', ' ', $texto);
    
    return trim($texto);
}

/**
 * Extraer texto de documentos Word
 */
function procesarDocumentoWord($ruta_archivo, $extension) {
    if ($extension === 'docx') {
        return extraerTextoDocx($ruta_archivo);
    } elseif ($extension === 'doc') {
        return extraerTextoDoc($ruta_archivo);
    }
    
    throw new Exception('Formato de documento Word no soportado');
}

/**
 * Extraer texto de archivos DOCX
 */
function extraerTextoDocx($ruta_archivo) {
    try {
        $zip = new ZipArchive();
        
        if ($zip->open($ruta_archivo) !== TRUE) {
            throw new Exception('No se pudo abrir el archivo DOCX');
        }
        
        // Buscar el documento principal
        $documento = $zip->getFromName('word/document.xml');
        
        if ($documento === false) {
            throw new Exception('No se encontró el contenido del documento');
        }
        
        $zip->close();
        
        // Parsear XML y extraer texto
        $xml = simplexml_load_string($documento);
        
        if ($xml === false) {
            throw new Exception('Error al parsear el XML del documento');
        }
        
        // Registrar namespace
        $xml->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        
        // Extraer todo el texto
        $textos = $xml->xpath('//w:t');
        $texto_completo = '';
        
        foreach ($textos as $texto) {
            $texto_completo .= (string)$texto . ' ';
        }
        
        return trim($texto_completo);
        
    } catch (Exception $e) {
        error_log('Error extrayendo texto DOCX: ' . $e->getMessage());
        return "TEXTO EXTRAÍDO DE DOCX (método básico)\n\n" .
               "Error al procesar el documento: " . $e->getMessage() . "\n\n" .
               "Archivo: " . basename($ruta_archivo) . "\n" .
               "Fecha: " . date('Y-m-d H:i:s');
    }
}

/**
 * Extraer texto de archivos DOC (formato binario)
 */
function extraerTextoDoc($ruta_archivo) {
    // Los archivos .doc tienen formato binario complejo
    // Se necesitaría una librería especializada como PHPWord o antiword
    
    // Intentar con antiword si está disponible
    $antiword_path = which('antiword');
    
    if ($antiword_path) {
        try {
            $comando = escapeshellcmd($antiword_path) . ' ' . 
                       escapeshellarg($ruta_archivo) . ' 2>&1';
            
            exec($comando, $output, $return_code);
            
            if ($return_code === 0) {
                return implode("\n", $output);
            }
        } catch (Exception $e) {
            error_log('Error antiword: ' . $e->getMessage());
        }
    }
    
    return "TEXTO EXTRAÍDO DE DOC (método básico)\n\n" .
           "Para mejor extracción de archivos .doc, instale antiword.\n\n" .
           "Archivo procesado: " . basename($ruta_archivo) . "\n" .
           "Fecha: " . date('Y-m-d H:i:s');
}

/**
 * Buscar ejecutable en el PATH del sistema
 */
function which($comando) {
    $paths = explode(PATH_SEPARATOR, getenv('PATH'));
    
    foreach ($paths as $path) {
        $archivo_completo = $path . DIRECTORY_SEPARATOR . $comando;
        
        if (is_executable($archivo_completo)) {
            return $archivo_completo;
        }
        
        // En Windows, probar con .exe
        if (DIRECTORY_SEPARATOR === '\\') {
            $archivo_exe = $archivo_completo . '.exe';
            if (is_executable($archivo_exe)) {
                return $archivo_exe;
            }
        }
    }
    
    return false;
}

/**
 * Limpiar y normalizar texto extraído
 */
function limpiarTextoExtraido($texto) {
    // Eliminar caracteres de control
    $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $texto);
    
    // Normalizar espacios en blanco
    $texto = preg_replace('/\s+/', ' ', $texto);
    
    // Eliminar líneas vacías múltiples
    $texto = preg_replace('/\n\s*\n\s*\n/', "\n\n", $texto);
    
    // Trim general
    $texto = trim($texto);
    
    return $texto;
}

/**
 * Detectar idioma del texto (básico)
 */
function detectarIdioma($texto) {
    // Palabras comunes en español
    $palabras_es = ['el', 'la', 'de', 'que', 'y', 'a', 'en', 'un', 'es', 'se', 'no', 'te', 'lo', 'le', 'da', 'su', 'por', 'son', 'con', 'para', 'al', 'del', 'los', 'las', 'una', 'como', 'más', 'pero', 'sus', 'me', 'ya', 'muy', 'mi', 'sin', 'sobre', 'este', 'año', 'antes', 'ser', 'dos', 'otros', 'hasta', 'día', 'parte', 'puede', 'estado', 'cada', 'gran', 'bien', 'vida', 'cuando', 'vez', 'tiempo'];
    
    // Palabras comunes en inglés
    $palabras_en = ['the', 'be', 'to', 'of', 'and', 'a', 'in', 'that', 'have', 'i', 'it', 'for', 'not', 'on', 'with', 'he', 'as', 'you', 'do', 'at', 'this', 'but', 'his', 'by', 'from', 'they', 'we', 'say', 'her', 'she', 'or', 'an', 'will', 'my', 'one', 'all', 'would', 'there', 'their', 'what', 'so', 'up', 'out', 'if', 'about', 'who', 'get', 'which', 'go', 'me'];
    
    $texto_lower = strtolower($texto);
    $palabras = explode(' ', $texto_lower);
    
    $contador_es = 0;
    $contador_en = 0;
    
    foreach ($palabras as $palabra) {
        $palabra = trim($palabra, '.,;:!?()[]');
        if (in_array($palabra, $palabras_es)) {
            $contador_es++;
        }
        if (in_array($palabra, $palabras_en)) {
            $contador_en++;
        }
    }
    
    if ($contador_es > $contador_en) {
        return 'es';
    } elseif ($contador_en > $contador_es) {
        return 'en';
    } else {
        return 'unknown';
    }
}

/**
 * Generar resumen automático del texto (básico)
 */
function generarResumen($texto, $max_oraciones = 3) {
    $oraciones = preg_split('/[.!?]+/', $texto);
    $oraciones = array_filter(array_map('trim', $oraciones));
    
    if (count($oraciones) <= $max_oraciones) {
        return $texto;
    }
    
    // Calcular puntuación de oraciones basada en longitud y palabras clave médicas
    $palabras_clave = [
        'paciente', 'diagnóstico', 'tratamiento', 'síntoma', 'enfermedad', 
        'medicamento', 'dolor', 'fiebre', 'presión', 'examen', 'análisis',
        'cirugía', 'operación', 'hospital', 'clínica', 'médico', 'doctor'
    ];
    
    $oraciones_puntuadas = [];
    
    foreach ($oraciones as $i => $oracion) {
        $puntuacion = strlen($oracion) / 100; // Puntuación base por longitud
        
        // Bonus por palabras clave
        foreach ($palabras_clave as $palabra) {
            if (stripos($oracion, $palabra) !== false) {
                $puntuacion += 2;
            }
        }
        
        // Penalty por ser muy corta o muy larga
        if (strlen($oracion) < 20 || strlen($oracion) > 200) {
            $puntuacion *= 0.5;
        }
        
        $oraciones_puntuadas[] = [
            'oracion' => $oracion,
            'puntuacion' => $puntuacion,
            'indice' => $i
        ];
    }
    
    // Ordenar por puntuación
    usort($oraciones_puntuadas, function($a, $b) {
        return $b['puntuacion'] <=> $a['puntuacion'];
    });
    
    // Tomar las mejores oraciones y reordenar por posición original
    $mejores = array_slice($oraciones_puntuadas, 0, $max_oraciones);
    usort($mejores, function($a, $b) {
        return $a['indice'] <=> $b['indice'];
    });
    
    $resumen = implode('. ', array_column($mejores, 'oracion'));
    
    return $resumen . '.';
}

/**
 * Validar y corregir texto médico común
 */
function corregirTextoMedico($texto) {
    // Correcciones comunes en texto médico OCR
    $correcciones = [
        // Errores comunes de OCR
        'paci ente' => 'paciente',
        'medi camento' => 'medicamento',
        'diag nóstico' => 'diagnóstico',
        'trata miento' => 'tratamiento',
        'pre sión' => 'presión',
        'temper atura' => 'temperatura',
        'fre cuencia' => 'frecuencia',
        'respir atoria' => 'respiratoria',
        'cardí aca' => 'cardíaca',
        'satur ación' => 'saturación',
        'oxí geno' => 'oxígeno',
        
        // Abreviaciones médicas comunes
        'FC' => 'Frecuencia Cardíaca',
        'FR' => 'Frecuencia Respiratoria',
        'TA' => 'Tensión Arterial',
        'PA' => 'Presión Arterial',
        'T°' => 'Temperatura',
        'SatO2' => 'Saturación de Oxígeno',
        'SpO2' => 'Saturación de Oxígeno',
        'BPM' => 'latidos por minuto',
        'RPM' => 'respiraciones por minuto',
        'mmHg' => 'milímetros de mercurio',
        '°C' => 'grados Celsius',
        'Kg' => 'kilogramos',
        'gr' => 'gramos',
        'ml' => 'mililitros',
        'cc' => 'centímetros cúbicos'
    ];
    
    foreach ($correcciones as $buscar => $reemplazar) {
        $texto = str_ireplace($buscar, $reemplazar, $texto);
    }
    
    return $texto;
}

/**
 * Extraer datos estructurados del texto médico
 */
function extraerDatosMedicos($texto) {
    $datos = [];
    
    // Patrones para extraer datos comunes
    $patrones = [
        'presion' => '/(?:PA|TA|Presión|Tensión):\s*(\d+\/\d+)\s*(?:mmHg)?/i',
        'temperatura' => '/(?:T°|Temperatura):\s*(\d+(?:\.\d+)?)\s*(?:°C)?/i',
        'frecuencia_cardiaca' => '/(?:FC|Frecuencia Cardíaca):\s*(\d+)\s*(?:bpm|lpm)?/i',
        'frecuencia_respiratoria' => '/(?:FR|Frecuencia Respiratoria):\s*(\d+)\s*(?:rpm)?/i',
        'saturacion' => '/(?:SatO2|SpO2|Saturación):\s*(\d+(?:\.\d+)?)\s*%?/i',
        'peso' => '/(?:Peso):\s*(\d+(?:\.\d+)?)\s*(?:kg|kilos)?/i',
        'talla' => '/(?:Talla|Altura):\s*(\d+(?:\.\d+)?)\s*(?:cm|metros|m)?/i'
    ];
    
    foreach ($patrones as $campo => $patron) {
        if (preg_match($patron, $texto, $matches)) {
            $datos[$campo] = $matches[1];
        }
    }
    
    return $datos;
}

// Si el archivo se ejecuta directamente (para testing)
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    echo "Procesador OCR - Sistema de Historias Médicas\n";
    echo "Este archivo debe ser llamado via POST con un archivo adjunto.\n";
}
?>