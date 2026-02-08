<?php
// modulos/ingreso/gestor_archivos.php
require_once '../../config/database.php';
require_once '../../config/functions.php';

/**
 * Clase para manejar archivos de antecedentes médicos
 */
class GestorArchivosAntecedentes {
    
    private $pdo;
    private $directorio_base;
    private $tipos_permitidos;
    private $tamaño_maximo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->directorio_base = '../../uploads/antecedentes/';
        $this->tamaño_maximo = 10 * 1024 * 1024; // 10MB
        
        $this->tipos_permitidos = [
            // Imágenes
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/gif' => ['gif'],
            'image/bmp' => ['bmp'],
            'image/tiff' => ['tiff', 'tif'],
            
            // Documentos
            'application/pdf' => ['pdf'],
            'application/msword' => ['doc'],
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
            'application/vnd.ms-excel' => ['xls'],
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
            'application/vnd.ms-powerpoint' => ['ppt'],
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => ['pptx'],
            
            // Texto
            'text/plain' => ['txt'],
            'text/csv' => ['csv'],
            'application/rtf' => ['rtf'],
            
            // Otros formatos médicos
            'application/dicom' => ['dcm', 'dicom'],
            'application/xml' => ['xml'],
            'application/json' => ['json']
        ];
        
        $this->crearDirectorios();
    }
    
    /**
     * Crear directorios necesarios
     */
    private function crearDirectorios() {
        $directorios = [
            $this->directorio_base,
            $this->directorio_base . date('Y'),
            $this->directorio_base . date('Y') . '/' . date('m'),
            $this->directorio_base . 'thumbs/',
            $this->directorio_base . 'temp/'
        ];
        
        foreach ($directorios as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Procesar archivo subido
     */
    public function procesarArchivo($archivo, $id_caso, $tipo_antecedente) {
        try {
            // Validar archivo
            $validacion = $this->validarArchivo($archivo);
            if (!$validacion['valido']) {
                throw new Exception(implode(', ', $validacion['errores']));
            }
            
            // Generar información del archivo
            $info_archivo = $this->generarInfoArchivo($archivo);
            
            // Mover archivo a ubicación final
            $ruta_destino = $this->moverArchivo($archivo, $info_archivo);
            
            // Generar thumbnail si es imagen
            $thumbnail = null;
            if ($this->esImagen($archivo)) {
                $thumbnail = $this->generarThumbnail($ruta_destino, $info_archivo);
            }
            
            // Extraer texto si es posible
            $texto_extraido = $this->extraerTexto($ruta_destino, $info_archivo['mime_type']);
            
            // Guardar información en base de datos
            $id_archivo = $this->guardarInfoArchivo([
                'id_caso' => $id_caso,
                'tipo_antecedente' => $tipo_antecedente,
                'nombre_original' => $info_archivo['nombre_original'],
                'nombre_archivo' => $info_archivo['nombre_archivo'],
                'ruta_archivo' => $info_archivo['ruta_relativa'],
                'mime_type' => $info_archivo['mime_type'],
                'tamaño' => $info_archivo['tamaño'],
                'hash_archivo' => $info_archivo['hash'],
                'thumbnail' => $thumbnail,
                'texto_extraido' => $texto_extraido,
                'metadatos' => json_encode($info_archivo['metadatos'])
            ]);
            
            return [
                'exito' => true,
                'id_archivo' => $id_archivo,
                'info_archivo' => $info_archivo,
                'texto_extraido' => $texto_extraido,
                'thumbnail' => $thumbnail
            ];
            
        } catch (Exception $e) {
            return [
                'exito' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Validar archivo subido
     */
    private function validarArchivo($archivo) {
        $errores = [];
        
        // Verificar errores de subida
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            $errores[] = $this->obtenerMensajeError($archivo['error']);
        }
        
        // Verificar tamaño
        if ($archivo['size'] > $this->tamaño_maximo) {
            $errores[] = 'El archivo es demasiado grande (máximo ' . $this->formatearTamaño($this->tamaño_maximo) . ')';
        }
        
        // Verificar tipo MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);
        
        if (!array_key_exists($mime_type, $this->tipos_permitidos)) {
            $errores[] = 'Tipo de archivo no permitido';
        }
        
        // Verificar extensión
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->tipos_permitidos[$mime_type] ?? [])) {
            $errores[] = 'Extensión de archivo no coincide con el tipo';
        }
        
        // Verificación adicional de seguridad
        if ($this->esArchivoMalicioso($archivo['tmp_name'])) {
            $errores[] = 'Archivo potencialmente peligroso';
        }
        
        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }
    
    /**
     * Generar información del archivo
     */
    private function generarInfoArchivo($archivo) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);
        
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $nombre_limpio = $this->limpiarNombreArchivo(pathinfo($archivo['name'], PATHINFO_FILENAME));
        $hash = hash_file('sha256', $archivo['tmp_name']);
        
        // Generar nombre único
        $nombre_archivo = $nombre_limpio . '_' . uniqid() . '_' . time() . '.' . $extension;
        
        // Ruta con estructura de fechas
        $ruta_relativa = date('Y') . '/' . date('m') . '/' . $nombre_archivo;
        $ruta_completa = $this->directorio_base . $ruta_relativa;
        
        // Metadatos adicionales
        $metadatos = [
            'fecha_subida' => date('Y-m-d H:i:s'),
            'ip_usuario' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        
        // Metadatos específicos por tipo
        if ($this->esImagen($archivo)) {
            $metadatos['imagen'] = $this->obtenerMetadatosImagen($archivo['tmp_name']);
        }
        
        return [
            'nombre_original' => $archivo['name'],
            'nombre_archivo' => $nombre_archivo,
            'ruta_relativa' => $ruta_relativa,
            'ruta_completa' => $ruta_completa,
            'mime_type' => $mime_type,
            'extension' => $extension,
            'tamaño' => $archivo['size'],
            'hash' => $hash,
            'metadatos' => $metadatos
        ];
    }
    
    /**
     * Mover archivo a ubicación final
     */
    private function moverArchivo($archivo, $info_archivo) {
        if (!move_uploaded_file($archivo['tmp_name'], $info_archivo['ruta_completa'])) {
            throw new Exception('Error al mover el archivo');
        }
        
        // Establecer permisos
        chmod($info_archivo['ruta_completa'], 0644);
        
        return $info_archivo['ruta_completa'];
    }
    
    /**
     * Generar thumbnail para imágenes
     */
    private function generarThumbnail($ruta_archivo, $info_archivo) {
        if (!$this->esImagen(['type' => $info_archivo['mime_type']])) {
            return null;
        }
        
        try {
            $nombre_thumb = 'thumb_' . $info_archivo['nombre_archivo'];
            $ruta_thumb = $this->directorio_base . 'thumbs/' . $nombre_thumb;
            
            if ($this->redimensionarImagen($ruta_archivo, $ruta_thumb, 200, 200)) {
                return 'thumbs/' . $nombre_thumb;
            }
            
        } catch (Exception $e) {
            error_log('Error generando thumbnail: ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Extraer texto del archivo
     */
    private function extraerTexto($ruta_archivo, $mime_type) {
        try {
            switch ($mime_type) {
                case 'text/plain':
                    return file_get_contents($ruta_archivo);
                    
                case 'application/pdf':
                    return $this->extraerTextoPDF($ruta_archivo);
                    
                case 'image/jpeg':
                case 'image/png':
                case 'image/gif':
                    return $this->extraerTextoImagen($ruta_archivo);
                    
                case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                    return $this->extraerTextoDocx($ruta_archivo);
                    
                default:
                    return null;
            }
        } catch (Exception $e) {
            error_log('Error extrayendo texto: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Guardar información del archivo en base de datos
     */
    private function guardarInfoArchivo($datos) {
        $stmt = $this->pdo->prepare("
            INSERT INTO archivos_antecedentes 
            (id_caso, tipo_antecedente, nombre_original, nombre_archivo, ruta_archivo, 
             mime_type, tamaño, hash_archivo, thumbnail, texto_extraido, metadatos, 
             id_usuario_subida, fecha_subida)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $usuario = obtenerUsuarioActual();
        
        $stmt->execute([
            $datos['id_caso'],
            $datos['tipo_antecedente'],
            $datos['nombre_original'],
            $datos['nombre_archivo'],
            $datos['ruta_archivo'],
            $datos['mime_type'],
            $datos['tamaño'],
            $datos['hash_archivo'],
            $datos['thumbnail'],
            $datos['texto_extraido'],
            $datos['metadatos'],
            $usuario['id']
        ]);
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Verificar si el archivo es una imagen
     */
    private function esImagen($archivo) {
        $mime_type = $archivo['type'] ?? $archivo['tmp_name'] ?? '';
        if (isset($archivo['tmp_name'])) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $archivo['tmp_name']);
            finfo_close($finfo);
        }
        
        return strpos($mime_type, 'image/') === 0;
    }
    
/**
     * Limpiar nombre de archivo
     */
    private function limpiarNombreArchivo($nombre) {
        // Eliminar caracteres especiales y espacios
        $nombre = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $nombre);
        // Eliminar múltiples guiones bajos consecutivos
        $nombre = preg_replace('/_+/', '_', $nombre);
        // Eliminar guiones al inicio y final
        $nombre = trim($nombre, '_');
        // Limitar longitud
        $nombre = substr($nombre, 0, 50);
        
        return $nombre ?: 'archivo';
    }
    
    /**
     * Verificar si el archivo es potencialmente malicioso
     */
    private function esArchivoMalicioso($ruta_archivo) {
        // Leer los primeros bytes del archivo
        $handle = fopen($ruta_archivo, 'rb');
        if (!$handle) return false;
        
        $primeros_bytes = fread($handle, 1024);
        fclose($handle);
        
        // Buscar patrones sospechosos
        $patrones_maliciosos = [
            '<?php',
            '<script',
            'javascript:',
            'eval(',
            'base64_decode(',
            'exec(',
            'system(',
            'shell_exec(',
            'passthru(',
            'file_get_contents(',
            'file_put_contents(',
            'fopen(',
            'fwrite(',
            'include(',
            'require(',
            'MZ', // Ejecutables Windows
            '\x7fELF' // Ejecutables Linux
        ];
        
        foreach ($patrones_maliciosos as $patron) {
            if (stripos($primeros_bytes, $patron) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Obtener metadatos de imagen
     */
    private function obtenerMetadatosImagen($ruta_archivo) {
        $metadatos = [];
        
        // Información básica de la imagen
        $info = getimagesize($ruta_archivo);
        if ($info) {
            $metadatos['ancho'] = $info[0];
            $metadatos['alto'] = $info[1];
            $metadatos['tipo_imagen'] = image_type_to_mime_type($info[2]);
            $metadatos['bits'] = $info['bits'] ?? null;
            $metadatos['canales'] = $info['channels'] ?? null;
        }
        
        // EXIF data si está disponible
        if (function_exists('exif_read_data') && in_array(strtolower(pathinfo($ruta_archivo, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'tiff'])) {
            try {
                $exif = exif_read_data($ruta_archivo);
                if ($exif) {
                    $metadatos['exif'] = [
                        'fecha_tomada' => $exif['DateTime'] ?? null,
                        'camara' => ($exif['Make'] ?? '') . ' ' . ($exif['Model'] ?? ''),
                        'orientacion' => $exif['Orientation'] ?? null,
                        'flash' => $exif['Flash'] ?? null,
                        'iso' => $exif['ISOSpeedRatings'] ?? null,
                        'focal_length' => $exif['FocalLength'] ?? null
                    ];
                }
            } catch (Exception $e) {
                // Ignorar errores de EXIF
            }
        }
        
        return $metadatos;
    }
    
    /**
     * Redimensionar imagen
     */
    private function redimensionarImagen($origen, $destino, $ancho_max, $alto_max) {
        $info = getimagesize($origen);
        if (!$info) return false;
        
        list($ancho_orig, $alto_orig, $tipo) = $info;
        
        // Calcular nuevas dimensiones manteniendo aspecto
        $ratio = min($ancho_max / $ancho_orig, $alto_max / $alto_orig);
        $nuevo_ancho = round($ancho_orig * $ratio);
        $nuevo_alto = round($alto_orig * $ratio);
        
        // Crear imagen origen
        switch ($tipo) {
            case IMAGETYPE_JPEG:
                $imagen_orig = imagecreatefromjpeg($origen);
                break;
            case IMAGETYPE_PNG:
                $imagen_orig = imagecreatefrompng($origen);
                break;
            case IMAGETYPE_GIF:
                $imagen_orig = imagecreatefromgif($origen);
                break;
            default:
                return false;
        }
        
        if (!$imagen_orig) return false;
        
        // Crear nueva imagen
        $nueva_imagen = imagecreatetruecolor($nuevo_ancho, $nuevo_alto);
        
        // Preservar transparencia para PNG y GIF
        if ($tipo == IMAGETYPE_PNG || $tipo == IMAGETYPE_GIF) {
            imagealphablending($nueva_imagen, false);
            imagesavealpha($nueva_imagen, true);
            $transparente = imagecolorallocatealpha($nueva_imagen, 255, 255, 255, 127);
            imagefill($nueva_imagen, 0, 0, $transparente);
        }
        
        // Redimensionar
        imagecopyresampled(
            $nueva_imagen, $imagen_orig,
            0, 0, 0, 0,
            $nuevo_ancho, $nuevo_alto,
            $ancho_orig, $alto_orig
        );
        
        // Guardar
        $resultado = false;
        switch ($tipo) {
            case IMAGETYPE_JPEG:
                $resultado = imagejpeg($nueva_imagen, $destino, 85);
                break;
            case IMAGETYPE_PNG:
                $resultado = imagepng($nueva_imagen, $destino, 8);
                break;
            case IMAGETYPE_GIF:
                $resultado = imagegif($nueva_imagen, $destino);
                break;
        }
        
        // Limpiar memoria
        imagedestroy($imagen_orig);
        imagedestroy($nueva_imagen);
        
        return $resultado;
    }
    
    /**
     * Extraer texto de PDF
     */
    private function extraerTextoPDF($ruta_archivo) {
        // Intentar con pdftotext
        $pdftotext = $this->buscarEjecutable('pdftotext');
        if ($pdftotext) {
            $temp_file = tempnam(sys_get_temp_dir(), 'pdf_');
            $comando = escapeshellcmd($pdftotext) . ' ' . escapeshellarg($ruta_archivo) . ' ' . escapeshellarg($temp_file);
            exec($comando, $output, $return_code);
            
            if ($return_code === 0 && file_exists($temp_file)) {
                $texto = file_get_contents($temp_file);
                unlink($temp_file);
                return $texto;
            }
        }
        
        return null;
    }
    
    /**
     * Extraer texto de imagen usando OCR
     */
    private function extraerTextoImagen($ruta_archivo) {
        // Intentar con tesseract
        $tesseract = $this->buscarEjecutable('tesseract');
        if ($tesseract) {
            $temp_file = tempnam(sys_get_temp_dir(), 'ocr_');
            $comando = escapeshellcmd($tesseract) . ' ' . escapeshellarg($ruta_archivo) . ' ' . escapeshellarg($temp_file) . ' -l spa+eng';
            exec($comando, $output, $return_code);
            
            if ($return_code === 0 && file_exists($temp_file . '.txt')) {
                $texto = file_get_contents($temp_file . '.txt');
                unlink($temp_file . '.txt');
                return $texto;
            }
        }
        
        return null;
    }
    
    /**
     * Extraer texto de DOCX
     */
    private function extraerTextoDocx($ruta_archivo) {
        try {
            $zip = new ZipArchive();
            if ($zip->open($ruta_archivo) !== TRUE) {
                return null;
            }
            
            $documento = $zip->getFromName('word/document.xml');
            $zip->close();
            
            if ($documento === false) {
                return null;
            }
            
            $xml = simplexml_load_string($documento);
            if ($xml === false) {
                return null;
            }
            
            $xml->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $textos = $xml->xpath('//w:t');
            
            $texto_completo = '';
            foreach ($textos as $texto) {
                $texto_completo .= (string)$texto . ' ';
            }
            
            return trim($texto_completo);
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Buscar ejecutable en el sistema
     */
    private function buscarEjecutable($nombre) {
        $paths = explode(PATH_SEPARATOR, getenv('PATH'));
        
        foreach ($paths as $path) {
            $ejecutable = $path . DIRECTORY_SEPARATOR . $nombre;
            if (is_executable($ejecutable)) {
                return $ejecutable;
            }
            
            // Probar con .exe en Windows
            if (DIRECTORY_SEPARATOR === '\\') {
                $ejecutable_exe = $ejecutable . '.exe';
                if (is_executable($ejecutable_exe)) {
                    return $ejecutable_exe;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Formatear tamaño de archivo
     */
    private function formatearTamaño($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Obtener mensaje de error de subida
     */
    private function obtenerMensajeError($codigo) {
        switch ($codigo) {
            case UPLOAD_ERR_INI_SIZE:
                return 'El archivo es demasiado grande (excede upload_max_filesize)';
            case UPLOAD_ERR_FORM_SIZE:
                return 'El archivo es demasiado grande (excede MAX_FILE_SIZE)';
            case UPLOAD_ERR_PARTIAL:
                return 'El archivo se subió parcialmente';
            case UPLOAD_ERR_NO_FILE:
                return 'No se subió ningún archivo';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'No hay directorio temporal';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Error al escribir el archivo';
            case UPLOAD_ERR_EXTENSION:
                return 'Subida detenida por extensión';
            default:
                return 'Error desconocido al subir el archivo';
        }
    }
    
    /**
     * Obtener archivo por ID
     */
    public function obtenerArchivo($id_archivo) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM archivos_antecedentes 
            WHERE id_archivo = ? AND activo = 1
        ");
        $stmt->execute([$id_archivo]);
        return $stmt->fetch();
    }
    
    /**
     * Listar archivos de un caso
     */
    public function listarArchivosCaso($id_caso) {
        $stmt = $this->pdo->prepare("
            SELECT a.*, p.nombres, p.apellidos
            FROM archivos_antecedentes a
            LEFT JOIN personal p ON a.id_usuario_subida = p.id
            WHERE a.id_caso = ? AND a.activo = 1
            ORDER BY a.fecha_subida DESC
        ");
        $stmt->execute([$id_caso]);
        return $stmt->fetchAll();
    }
    
    /**
     * Eliminar archivo (marcar como inactivo)
     */
    public function eliminarArchivo($id_archivo, $motivo = '') {
        try {
            $this->pdo->beginTransaction();
            
            // Obtener info del archivo
            $archivo = $this->obtenerArchivo($id_archivo);
            if (!$archivo) {
                throw new Exception('Archivo no encontrado');
            }
            
            // Marcar como inactivo
            $stmt = $this->pdo->prepare("
                UPDATE archivos_antecedentes 
                SET activo = 0, fecha_eliminacion = NOW(), motivo_eliminacion = ?,
                    id_usuario_eliminacion = ?
                WHERE id_archivo = ?
            ");
            
            $usuario = obtenerUsuarioActual();
            $stmt->execute([$motivo, $usuario['id'], $id_archivo]);
            
            // Mover archivo a papelera (opcional)
            $ruta_actual = $this->directorio_base . $archivo['ruta_archivo'];
            $ruta_papelera = $this->directorio_base . 'deleted/' . $archivo['nombre_archivo'];
            
            if (file_exists($ruta_actual)) {
                if (!is_dir(dirname($ruta_papelera))) {
                    mkdir(dirname($ruta_papelera), 0755, true);
                }
                rename($ruta_actual, $ruta_papelera);
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    /**
     * Verificar integridad de archivo
     */
    public function verificarIntegridad($id_archivo) {
        $archivo = $this->obtenerArchivo($id_archivo);
        if (!$archivo) {
            return ['valido' => false, 'error' => 'Archivo no encontrado'];
        }
        
        $ruta_archivo = $this->directorio_base . $archivo['ruta_archivo'];
        
        if (!file_exists($ruta_archivo)) {
            return ['valido' => false, 'error' => 'Archivo físico no existe'];
        }
        
        $hash_actual = hash_file('sha256', $ruta_archivo);
        if ($hash_actual !== $archivo['hash_archivo']) {
            return ['valido' => false, 'error' => 'Hash no coincide - archivo modificado'];
        }
        
        return ['valido' => true];
    }
    
    /**
     * Generar URL segura para descargar archivo
     */
    public function generarUrlDescarga($id_archivo, $duracion_horas = 24) {
        $token = hash('sha256', $id_archivo . time() . rand());
        $expiracion = date('Y-m-d H:i:s', strtotime("+{$duracion_horas} hours"));
        
        // Guardar token en base de datos
        $stmt = $this->pdo->prepare("
            INSERT INTO tokens_descarga (token, id_archivo, fecha_expiracion, id_usuario_generador)
            VALUES (?, ?, ?, ?)
        ");
        
        $usuario = obtenerUsuarioActual();
        $stmt->execute([$token, $id_archivo, $expiracion, $usuario['id']]);
        
        return 'descargar.php?token=' . $token;
    }
    
    /**
     * Obtener estadísticas de archivos
     */
    public function obtenerEstadisticas($id_caso = null) {
        $where = $id_caso ? "WHERE id_caso = ? AND " : "WHERE ";
        $params = $id_caso ? [$id_caso] : [];
        
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_archivos,
                SUM(tamaño) as tamaño_total,
                AVG(tamaño) as tamaño_promedio,
                COUNT(CASE WHEN texto_extraido IS NOT NULL THEN 1 END) as con_texto_extraido,
                COUNT(CASE WHEN thumbnail IS NOT NULL THEN 1 END) as con_thumbnail
            FROM archivos_antecedentes 
            {$where} activo = 1
        ");
        
        $stmt->execute($params);
        $stats = $stmt->fetch();
        
        // Estadísticas por tipo
        $stmt2 = $this->pdo->prepare("
            SELECT tipo_antecedente, COUNT(*) as cantidad
            FROM archivos_antecedentes 
            {$where} activo = 1
            GROUP BY tipo_antecedente
        ");
        $stmt2->execute($params);
        $stats['por_tipo'] = $stmt2->fetchAll();
        
        return $stats;
    }
}

// Crear tabla si no existe
function crearTablaArchivosAntecedentes($pdo) {
    $sql = "
    CREATE TABLE IF NOT EXISTS `archivos_antecedentes` (
        `id_archivo` int NOT NULL AUTO_INCREMENT,
        `id_caso` int NOT NULL,
        `tipo_antecedente` enum('personal','familiar','quirurgico','farmacologico','alergico','social','gineco_obstetrico') NOT NULL,
        `nombre_original` varchar(255) NOT NULL,
        `nombre_archivo` varchar(255) NOT NULL,
        `ruta_archivo` varchar(500) NOT NULL,
        `mime_type` varchar(100) NOT NULL,
        `tamaño` bigint NOT NULL,
        `hash_archivo` varchar(64) NOT NULL,
        `thumbnail` varchar(500) DEFAULT NULL,
        `texto_extraido` longtext,
        `metadatos` json DEFAULT NULL,
        `id_usuario_subida` int NOT NULL,
        `fecha_subida` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `activo` tinyint(1) DEFAULT '1',
        `fecha_eliminacion` timestamp NULL DEFAULT NULL,
        `motivo_eliminacion` text,
        `id_usuario_eliminacion` int DEFAULT NULL,
        PRIMARY KEY (`id_archivo`),
        KEY `idx_caso` (`id_caso`),
        KEY `idx_tipo` (`tipo_antecedente`),
        KEY `idx_fecha` (`fecha_subida`),
        KEY `idx_hash` (`hash_archivo`),
        CONSTRAINT `fk_archivos_caso` FOREIGN KEY (`id_caso`) REFERENCES `casos_clinicos` (`id_caso`),
        CONSTRAINT `fk_archivos_usuario` FOREIGN KEY (`id_usuario_subida`) REFERENCES `personal` (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
    
    CREATE TABLE IF NOT EXISTS `tokens_descarga` (
        `id_token` int NOT NULL AUTO_INCREMENT,
        `token` varchar(64) NOT NULL,
        `id_archivo` int NOT NULL,
        `fecha_expiracion` timestamp NOT NULL,
        `id_usuario_generador` int NOT NULL,
        `usado` tinyint(1) DEFAULT '0',
        `fecha_uso` timestamp NULL DEFAULT NULL,
        `ip_uso` varchar(45) DEFAULT NULL,
        PRIMARY KEY (`id_token`),
        UNIQUE KEY `token` (`token`),
        KEY `idx_archivo` (`id_archivo`),
        KEY `idx_expiracion` (`fecha_expiracion`),
        CONSTRAINT `fk_token_archivo` FOREIGN KEY (`id_archivo`) REFERENCES `archivos_antecedentes` (`id_archivo`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
    ";
    
    $pdo->exec($sql);
}

// Inicializar si se llama directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    verificarSesion();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
        $gestor = new GestorArchivosAntecedentes($pdo);
        $resultado = $gestor->procesarArchivo(
            $_FILES['archivo'],
            $_POST['id_caso'] ?? 0,
            $_POST['tipo_antecedente'] ?? 'personal'
        );
        
        if ($resultado['exito']) {
            respuestaJSON('ok', 'Archivo procesado exitosamente', $resultado);
        } else {
            respuestaJSON('error', $resultado['error']);
        }
    }
}
?>