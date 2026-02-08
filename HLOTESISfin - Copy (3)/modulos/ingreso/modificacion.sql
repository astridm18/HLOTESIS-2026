-- Actualización de la base de datos para soporte completo de antecedentes con archivos
-- Ejecutar estas consultas para actualizar la estructura existente

-- 1. Agregar columna para referenciar archivos adjuntos en antecedentes
ALTER TABLE `antecedentes` 
ADD COLUMN `id_archivo_adjunto` int DEFAULT NULL AFTER `archivo_adjunto`,
ADD CONSTRAINT `fk_antecedentes_archivo` FOREIGN KEY (`id_archivo_adjunto`) REFERENCES `archivos_antecedentes` (`id_archivo`);

-- 2. Crear tabla para archivos de antecedentes si no existe
CREATE TABLE IF NOT EXISTS `archivos_antecedentes` (
    `id_archivo` int NOT NULL AUTO_INCREMENT,
    `id_caso` int NOT NULL,
    `id_antecedente` int DEFAULT NULL,
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
    KEY `idx_antecedente` (`id_antecedente`),
    KEY `idx_tipo` (`tipo_antecedente`),
    KEY `idx_fecha` (`fecha_subida`),
    KEY `idx_hash` (`hash_archivo`),
    KEY `idx_activo` (`activo`),
    CONSTRAINT `fk_archivos_caso` FOREIGN KEY (`id_caso`) REFERENCES `casos_clinicos` (`id_caso`) ON DELETE CASCADE,
    CONSTRAINT `fk_archivos_antecedente` FOREIGN KEY (`id_antecedente`) REFERENCES `antecedentes` (`id_antecedente`) ON DELETE SET NULL,
    CONSTRAINT `fk_archivos_usuario` FOREIGN KEY (`id_usuario_subida`) REFERENCES `personal` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 3. Crear tabla para tokens de descarga segura
CREATE TABLE IF NOT EXISTS `tokens_descarga` (
    `id_token` int NOT NULL AUTO_INCREMENT,
    `token` varchar(64) NOT NULL,
    `id_archivo` int NOT NULL,
    `fecha_expiracion` timestamp NOT NULL,
    `id_usuario_generador` int NOT NULL,
    `usado` tinyint(1) DEFAULT '0',
    `fecha_uso` timestamp NULL DEFAULT NULL,
    `ip_uso` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    PRIMARY KEY (`id_token`),
    UNIQUE KEY `token` (`token`),
    KEY `idx_archivo` (`id_archivo`),
    KEY `idx_expiracion` (`fecha_expiracion`),
    KEY `idx_usado` (`usado`),
    CONSTRAINT `fk_token_archivo` FOREIGN KEY (`id_archivo`) REFERENCES `archivos_antecedentes` (`id_archivo`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 4. Crear tabla para log de descargas
CREATE TABLE IF NOT EXISTS `log_descargas` (
    `id_log` int NOT NULL AUTO_INCREMENT,
    `id_archivo` int NOT NULL,
    `id_usuario` int NOT NULL,
    `token_usado` varchar(64) DEFAULT NULL,
    `ip_usuario` varchar(45) NOT NULL,
    `user_agent` text,
    `tipo_descarga` enum('download','view','thumbnail') DEFAULT 'download',
    `fecha_descarga` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_log`),
    KEY `idx_archivo` (`id_archivo`),
    KEY `idx_usuario` (`id_usuario`),
    KEY `idx_fecha` (`fecha_descarga`),
    CONSTRAINT `fk_log_archivo` FOREIGN KEY (`id_archivo`) REFERENCES `archivos_antecedentes` (`id_archivo`) ON DELETE CASCADE,
    CONSTRAINT `fk_log_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `personal` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 5. Crear tabla para auditoría general si no existe
CREATE TABLE IF NOT EXISTS `auditoria` (
    `id_auditoria` int NOT NULL AUTO_INCREMENT,
    `tabla_afectada` varchar(50) NOT NULL,
    `id_registro` int NOT NULL,
    `accion` enum('INSERT','UPDATE','DELETE') NOT NULL,
    `valores_anteriores` json DEFAULT NULL,
    `valores_nuevos` json DEFAULT NULL,
    `id_usuario` int NOT NULL,
    `ip_usuario` varchar(45) DEFAULT NULL,
    `navegador` text DEFAULT NULL,
    `fecha_accion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_auditoria`),
    KEY `idx_tabla_registro` (`tabla_afectada`, `id_registro`),
    KEY `idx_usuario` (`id_usuario`),
    KEY `idx_fecha` (`fecha_accion`),
    KEY `idx_accion` (`accion`),
    CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `personal` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 6. Crear tabla para alertas del sistema
CREATE TABLE IF NOT EXISTS `alertas` (
    `id_alerta` int NOT NULL AUTO_INCREMENT,
    `id_caso` int DEFAULT NULL,
    `tipo_alerta` varchar(50) NOT NULL,
    `titulo` varchar(200) NOT NULL,
    `mensaje` text NOT NULL,
    `prioridad` enum('baja','media','alta','critica') DEFAULT 'media',
    `id_usuario_destino` int NOT NULL,
    `id_usuario_creador` int NOT NULL,
    `leida` tinyint(1) DEFAULT '0',
    `fecha_lectura` timestamp NULL DEFAULT NULL,
    `activa` tinyint(1) DEFAULT '1',
    `fecha_vencimiento` timestamp NULL DEFAULT NULL,
    `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_alerta`),
    KEY `idx_caso` (`id_caso`),
    KEY `idx_usuario_destino` (`id_usuario_destino`),
    KEY `idx_usuario_creador` (`id_usuario_creador`),
    KEY `idx_leida` (`leida`),
    KEY `idx_activa` (`activa`),
    KEY `idx_fecha_creacion` (`fecha_creacion`),
    KEY `idx_tipo_prioridad` (`tipo_alerta`, `prioridad`),
    CONSTRAINT `fk_alertas_caso` FOREIGN KEY (`id_caso`) REFERENCES `casos_clinicos` (`id_caso`) ON DELETE CASCADE,
    CONSTRAINT `fk_alertas_destino` FOREIGN KEY (`id_usuario_destino`) REFERENCES `personal` (`id`),
    CONSTRAINT `fk_alertas_creador` FOREIGN KEY (`id_usuario_creador`) REFERENCES `personal` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 7. Crear índices adicionales para mejor rendimiento
CREATE INDEX `idx_antecedentes_caso_tipo` ON `antecedentes` (`id_caso`, `tipo_antecedente`);
CREATE INDEX `idx_antecedentes_relevancia` ON `antecedentes` (`relevancia`);
CREATE INDEX `idx_antecedentes_fecha_evento` ON `antecedentes` (`fecha_evento`);
CREATE INDEX `idx_antecedentes_texto_extraido` ON `antecedentes` (`texto_extraido`(100));

-- 8. Crear vista para resumen de antecedentes por caso
CREATE OR REPLACE VIEW `v_resumen_antecedentes` AS
SELECT 
    a.id_caso,
    cc.numero_caso,
    CONCAT(p.nombres, ' ', p.apellidos) as paciente,
    COUNT(*) as total_antecedentes,
    COUNT(CASE WHEN a.relevancia = 'alta' THEN 1 END) as alta_relevancia,
    COUNT(CASE WHEN a.relevancia = 'media' THEN 1 END) as media_relevancia,
    COUNT(CASE WHEN a.relevancia = 'baja' THEN 1 END) as baja_relevancia,
    COUNT(CASE WHEN a.archivo_adjunto IS NOT NULL THEN 1 END) as con_archivos,
    COUNT(CASE WHEN a.texto_extraido IS NOT NULL THEN 1 END) as con_texto_extraido,
    GROUP_CONCAT(DISTINCT a.tipo_antecedente ORDER BY a.tipo_antecedente) as tipos_antecedentes,
    MAX(a.actualizado_en) as ultima_actualizacion
FROM antecedentes a
INNER JOIN casos_clinicos cc ON a.id_caso = cc.id_caso
INNER JOIN pacientes p ON cc.id_paciente = p.id_paciente
WHERE a.activo = 1
GROUP BY a.id_caso, cc.numero_caso, p.nombres, p.apellidos;

-- 9. Crear vista para estadísticas de archivos
CREATE OR REPLACE VIEW `v_estadisticas_archivos` AS
SELECT 
    aa.tipo_antecedente,
    aa.mime_type,
    COUNT(*) as cantidad_archivos,
    SUM(aa.tamaño) as tamaño_total,
    AVG(aa.tamaño) as tamaño_promedio,
    COUNT(CASE WHEN aa.texto_extraido IS NOT NULL THEN 1 END) as con_texto_extraido,
    COUNT(CASE WHEN aa.thumbnail IS NOT NULL THEN 1 END) as con_thumbnail,
    MIN(aa.fecha_subida) as primer_archivo,
    MAX(aa.fecha_subida) as ultimo_archivo
FROM archivos_antecedentes aa
WHERE aa.activo = 1
GROUP BY aa.tipo_antecedente, aa.mime_type
ORDER BY cantidad_archivos DESC;

-- 10. Crear vista para antecedentes completos con archivos
CREATE OR REPLACE VIEW `v_antecedentes_completos` AS
SELECT 
    a.id_antecedente,
    a.id_caso,
    cc.numero_caso,
    CONCAT(p.nombres, ' ', p.apellidos) as paciente,
    p.cedula,
    a.tipo_antecedente,
    a.descripcion,
    a.fecha_evento,
    a.relevancia,
    a.texto_extraido,
    a.creado_en,
    a.actualizado_en,
    CONCAT(pm.nombres, ' ', pm.apellidos) as medico_registro,
    pm.especialidad as especialidad_medico,
    -- Información del archivo
    aa.id_archivo,
    aa.nombre_original,
    aa.nombre_archivo,
    aa.mime_type,
    aa.tamaño as tamaño_archivo,
    aa.thumbnail,
    aa.fecha_subida,
    -- Estadísticas de descarga
    (SELECT COUNT(*) FROM log_descargas ld WHERE ld.id_archivo = aa.id_archivo) as total_descargas,
    (SELECT MAX(ld.fecha_descarga) FROM log_descargas ld WHERE ld.id_archivo = aa.id_archivo) as ultima_descarga
FROM antecedentes a
INNER JOIN casos_clinicos cc ON a.id_caso = cc.id_caso
INNER JOIN pacientes p ON cc.id_paciente = p.id_paciente
LEFT JOIN personal pm ON a.id_medico_registro = pm.id
LEFT JOIN archivos_antecedentes aa ON a.id_archivo_adjunto = aa.id_archivo
WHERE a.activo = 1 AND (aa.id_archivo IS NULL OR aa.activo = 1);

-- 11. Crear procedimiento almacenado para limpiar archivos antiguos
DELIMITER $$
CREATE PROCEDURE `limpiar_archivos_antiguos`(
    IN `dias_antiguedad` INT DEFAULT 365,
    IN `solo_inactivos` BOOLEAN DEFAULT TRUE
)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE archivo_id INT;
    DECLARE archivo_ruta VARCHAR(500);
    
    DECLARE cursor_archivos CURSOR FOR 
        SELECT id_archivo, ruta_archivo 
        FROM archivos_antecedentes 
        WHERE fecha_subida < DATE_SUB(NOW(), INTERVAL dias_antiguedad DAY)
        AND (solo_inactivos = FALSE OR activo = 0);
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    START TRANSACTION;
    
    OPEN cursor_archivos;
    
    read_loop: LOOP
        FETCH cursor_archivos INTO archivo_id, archivo_ruta;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Marcar como eliminado en lugar de borrar físicamente
        UPDATE archivos_antecedentes 
        SET activo = 0, 
            fecha_eliminacion = NOW(),
            motivo_eliminacion = 'Limpieza automática por antigüedad'
        WHERE id_archivo = archivo_id;
        
        -- Log de la acción
        INSERT INTO auditoria (tabla_afectada, id_registro, accion, valores_nuevos, id_usuario, fecha_accion)
        VALUES ('archivos_antecedentes', archivo_id, 'UPDATE', 
                JSON_OBJECT('motivo', 'Limpieza automática', 'ruta', archivo_ruta), 
                0, NOW());
    END LOOP;
    
    CLOSE cursor_archivos;
    COMMIT;
    
    -- Retornar estadísticas
    SELECT 
        COUNT(*) as archivos_procesados,
        SUM(tamaño) as espacio_liberado
    FROM archivos_antecedentes 
    WHERE fecha_eliminacion >= DATE_SUB(NOW(), INTERVAL 1 HOUR);
    
END$$
DELIMITER ;

-- 12. Crear función para calcular estadísticas de antecedentes por paciente
DELIMITER $$
CREATE FUNCTION `estadisticas_antecedentes_paciente`(
    `p_id_paciente` INT
) RETURNS JSON
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE resultado JSON;
    
    SELECT JSON_OBJECT(
        'total_antecedentes', COUNT(*),
        'alta_relevancia', COUNT(CASE WHEN a.relevancia = 'alta' THEN 1 END),
        'con_archivos', COUNT(CASE WHEN a.archivo_adjunto IS NOT NULL THEN 1 END),
        'con_texto_extraido', COUNT(CASE WHEN a.texto_extraido IS NOT NULL THEN 1 END),
        'tipos_unicos', COUNT(DISTINCT a.tipo_antecedente),
        'primer_registro', MIN(a.creado_en),
        'ultimo_registro', MAX(a.actualizado_en),
        'tipos_detalle', JSON_ARRAYAGG(
            JSON_OBJECT(
                'tipo', a.tipo_antecedente,
                'cantidad', COUNT(*)
            )
        )
    ) INTO resultado
    FROM antecedentes a
    INNER JOIN casos_clinicos cc ON a.id_caso = cc.id_caso
    WHERE cc.id_paciente = p_id_paciente 
    AND a.activo = 1
    GROUP BY cc.id_paciente;
    
    RETURN COALESCE(resultado, JSON_OBJECT('total_antecedentes', 0));
END$$
DELIMITER ;

-- 13. Crear trigger para auditoría automática de antecedentes
DELIMITER $$
CREATE TRIGGER `tr_antecedentes_auditoria_insert` 
AFTER INSERT ON `antecedentes`
FOR EACH ROW 
BEGIN
    INSERT INTO auditoria (tabla_afectada, id_registro, accion, valores_nuevos, id_usuario)
    VALUES ('antecedentes', NEW.id_antecedente, 'INSERT', 
            JSON_OBJECT(
                'tipo_antecedente', NEW.tipo_antecedente,
                'descripcion', LEFT(NEW.descripcion, 100),
                'relevancia', NEW.relevancia,
                'id_caso', NEW.id_caso
            ), 
            NEW.id_medico_registro);
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER `tr_antecedentes_auditoria_update` 
AFTER UPDATE ON `antecedentes`
FOR EACH ROW 
BEGIN
    INSERT INTO auditoria (tabla_afectada, id_registro, accion, valores_anteriores, valores_nuevos, id_usuario)
    VALUES ('antecedentes', NEW.id_antecedente, 'UPDATE',
            JSON_OBJECT(
                'tipo_antecedente', OLD.tipo_antecedente,
                'descripcion', LEFT(OLD.descripcion, 100),
                'relevancia', OLD.relevancia
            ),
            JSON_OBJECT(
                'tipo_antecedente', NEW.tipo_antecedente,
                'descripcion', LEFT(NEW.descripcion, 100),
                'relevancia', NEW.relevancia
            ),
            NEW.id_medico_registro);
END$$
DELIMITER ;

-- 14. Crear trigger para limpiar tokens expirados automáticamente
DELIMITER $$
CREATE EVENT IF NOT EXISTS `ev_limpiar_tokens_expirados`
ON SCHEDULE EVERY 1 HOUR
DO
BEGIN
    -- Eliminar tokens expirados
    DELETE FROM tokens_descarga 
    WHERE fecha_expiracion < NOW();
    
    -- Log de limpieza
    INSERT INTO auditoria (tabla_afectada, id_registro, accion, valores_nuevos, id_usuario)
    VALUES ('tokens_descarga', 0, 'DELETE', 
            JSON_OBJECT('motivo', 'Limpieza automática de tokens expirados'), 0);
END$$
DELIMITER ;

-- 15. Crear índices de texto completo para búsquedas
ALTER TABLE `antecedentes` ADD FULLTEXT(`descripcion`, `texto_extraido`);

-- 16. Crear procedimiento para búsqueda avanzada de antecedentes
DELIMITER $$
CREATE PROCEDURE `buscar_antecedentes_avanzado`(
    IN `termino_busqueda` TEXT,
    IN `id_caso_filtro` INT,
    IN `tipo_filtro` VARCHAR(50),
    IN `relevancia_filtro` VARCHAR(10),
    IN `fecha_desde` DATE,
    IN `fecha_hasta` DATE,
    IN `con_archivos` BOOLEAN,
    IN `limite` INT DEFAULT 50,
    IN `offset_pagina` INT DEFAULT 0
)
BEGIN
    SET @sql = 'SELECT 
        a.id_antecedente,
        a.id_caso,
        cc.numero_caso,
        CONCAT(p.nombres, " ", p.apellidos) as paciente,
        a.tipo_antecedente,
        LEFT(a.descripcion, 200) as descripcion_corta,
        a.relevancia,
        a.fecha_evento,
        a.creado_en,
        CONCAT(pm.nombres, " ", pm.apellidos) as medico_registro,
        CASE WHEN a.archivo_adjunto IS NOT NULL THEN 1 ELSE 0 END as tiene_archivo,
        CASE WHEN a.texto_extraido IS NOT NULL THEN 1 ELSE 0 END as tiene_texto_extraido,
        MATCH(a.descripcion, a.texto_extraido) AGAINST(? IN NATURAL LANGUAGE MODE) as relevancia_busqueda
    FROM antecedentes a
    INNER JOIN casos_clinicos cc ON a.id_caso = cc.id_caso
    INNER JOIN pacientes p ON cc.id_paciente = p.id_paciente
    LEFT JOIN personal pm ON a.id_medico_registro = pm.id
    WHERE a.activo = 1';
    
    -- Agregar condiciones dinámicamente
    IF termino_busqueda IS NOT NULL AND termino_busqueda != '' THEN
        SET @sql = CONCAT(@sql, ' AND MATCH(a.descripcion, a.texto_extraido) AGAINST(? IN NATURAL LANGUAGE MODE)');
    END IF;
    
    IF id_caso_filtro IS NOT NULL THEN
        SET @sql = CONCAT(@sql, ' AND a.id_caso = ', id_caso_filtro);
    END IF;
    
    IF tipo_filtro IS NOT NULL AND tipo_filtro != '' THEN
        SET @sql = CONCAT(@sql, ' AND a.tipo_antecedente = "', tipo_filtro, '"');
    END IF;
    
    IF relevancia_filtro IS NOT NULL AND relevancia_filtro != '' THEN
        SET @sql = CONCAT(@sql, ' AND a.relevancia = "', relevancia_filtro, '"');
    END IF;
    
    IF fecha_desde IS NOT NULL THEN
        SET @sql = CONCAT(@sql, ' AND DATE(a.creado_en) >= "', fecha_desde, '"');
    END IF;
    
    IF fecha_hasta IS NOT NULL THEN
        SET @sql = CONCAT(@sql, ' AND DATE(a.creado_en) <= "', fecha_hasta, '"');
    END IF;
    
    IF con_archivos = TRUE THEN
        SET @sql = CONCAT(@sql, ' AND a.archivo_adjunto IS NOT NULL');
    END IF;
    
    -- Ordenar y limitar
    SET @sql = CONCAT(@sql, ' ORDER BY relevancia_busqueda DESC, a.relevancia DESC, a.creado_en DESC');
    SET @sql = CONCAT(@sql, ' LIMIT ', limite, ' OFFSET ', offset_pagina);
    
    PREPARE stmt FROM @sql;
    
    IF termino_busqueda IS NOT NULL AND termino_busqueda != '' THEN
        EXECUTE stmt USING termino_busqueda, termino_busqueda;
    ELSE
        EXECUTE stmt;
    END IF;
    
    DEALLOCATE PREPARE stmt;
END$$
DELIMITER ;

-- 17. Crear vista para dashboard de antecedentes
CREATE OR REPLACE VIEW `v_dashboard_antecedentes` AS
SELECT 
    'total_antecedentes' as metrica,
    COUNT(*) as valor,
    'Total de antecedentes registrados' as descripcion
FROM antecedentes WHERE activo = 1
UNION ALL
SELECT 
    'antecedentes_alta_relevancia' as metrica,
    COUNT(*) as valor,
    'Antecedentes de alta relevancia' as descripcion
FROM antecedentes WHERE activo = 1 AND relevancia = 'alta'
UNION ALL
SELECT 
    'antecedentes_con_archivos' as metrica,
    COUNT(*) as valor,
    'Antecedentes con archivos adjuntos' as descripcion
FROM antecedentes WHERE activo = 1 AND archivo_adjunto IS NOT NULL
UNION ALL
SELECT 
    'archivos_subidos' as metrica,
    COUNT(*) as valor,
    'Total de archivos subidos' as descripcion
FROM archivos_antecedentes WHERE activo = 1
UNION ALL
SELECT 
    'espacio_utilizado_mb' as metrica,
    ROUND(SUM(tamaño) / 1024 / 1024, 2) as valor,
    'Espacio utilizado en MB' as descripcion
FROM archivos_antecedentes WHERE activo = 1
UNION ALL
SELECT 
    'archivos_con_ocr' as metrica,
    COUNT(*) as valor,
    'Archivos con texto extraído (OCR)' as descripcion
FROM archivos_antecedentes WHERE activo = 1 AND texto_extraido IS NOT NULL;

-- 18. Insertar datos de configuración inicial
INSERT IGNORE INTO `personal` (nombres, apellidos, cedula, email, rol, especialidad, direccion, activo, contrasena) 
VALUES 
('Sistema', 'Automatizado', '0000000000', 'sistema@hospital.com', 'Admin', 'Sistema', 'Servidor', 1, '$2y$10$dummy_hash_for_system_user'),
('OCR', 'Processor', '0000000001', 'ocr@hospital.com', 'Sistema', 'OCR', 'Servidor', 1, '$2y$10$dummy_hash_for_ocr_system');

-- 19. Crear configuraciones del sistema
CREATE TABLE IF NOT EXISTS `configuraciones` (
    `id_config` int NOT NULL AUTO_INCREMENT,
    `clave` varchar(100) NOT NULL,
    `valor` text NOT NULL,
    `descripcion` text,
    `tipo` enum('string','number','boolean','json') DEFAULT 'string',
    `categoria` varchar(50) DEFAULT 'general',
    `modificable` tinyint(1) DEFAULT '1',
    `actualizado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_config`),
    UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Insertar configuraciones iniciales
INSERT IGNORE INTO `configuraciones` (clave, valor, descripcion, tipo, categoria) VALUES
('max_file_size', '10485760', 'Tamaño máximo de archivo en bytes (10MB)', 'number', 'archivos'),
('allowed_file_types', '["image/jpeg","image/png","image/gif","application/pdf","application/msword","application/vnd.openxmlformats-officedocument.wordprocessingml.document","text/plain"]', 'Tipos MIME permitidos', 'json', 'archivos'),
('ocr_enabled', 'true', 'Habilitar procesamiento OCR automático', 'boolean', 'ocr'),
('ocr_languages', 'spa+eng', 'Idiomas para OCR (formato Tesseract)', 'string', 'ocr'),
('auto_cleanup_days', '365', 'Días para limpieza automática de archivos', 'number', 'mantenimiento'),
('token_expiry_hours', '24', 'Horas de validez para tokens de descarga', 'number', 'seguridad');

-- 20. Activar el programador de eventos si no está activo
SET GLOBAL event_scheduler = ON;

-- Verificar que todas las tablas fueron creadas correctamente
SELECT 
    TABLE_NAME as tabla,
    TABLE_ROWS as filas,
    ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) as tamaño_mb
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'historiasmedicas' 
AND TABLE_NAME IN ('antecedentes', 'archivos_antecedentes', 'tokens_descarga', 'log_descargas', 'auditoria', 'alertas', 'configuraciones')
ORDER BY TABLE_NAME;