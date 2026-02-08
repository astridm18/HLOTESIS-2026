<?php
// config/campos_bd.php - Configuración de mapeo de campos entre formulario y base de datos

// Mapeo de campos para tabla casos_clinicos
$campos_casos_clinicos = [
    // Campos básicos
    'id_paciente' => 'id_paciente',
    'tipo_ingreso' => 'tipo_ingreso',
    'servicio_ingreso' => 'servicio_ingreso',
    'servicio_actual' => 'servicio_actual', // usar servicio_ingreso si no se especifica
    'cama_actual' => 'cama_actual',
    'sala_actual' => 'sala_actual',
    'piso' => 'piso',
    'id_medico_responsable' => 'id_medico_responsable',
    'prioridad' => 'prioridad',
    'motivo_consulta' => 'motivo_consulta', // NUEVO CAMPO
    'observaciones_generales' => 'observaciones_generales'
];

// Mapeo de campos para tabla datos_ingreso  
$campos_datos_ingreso = [
    // Campos básicos requeridos
    'id_caso' => 'id_caso',
    'fecha_ingreso' => 'fecha_ingreso',
    'hora_ingreso' => 'hora_ingreso',
    'motivo_consulta' => 'motivo_consulta',
    'id_medico_ingreso' => 'id_medico_ingreso',

    // Campos opcionales de la consulta
    'enfermedad_actual' => 'enfermedad_actual',
    'sintomas_principales' => 'sintomas_principales',
    'tiempo_evolucion' => 'tiempo_evolucion',
    'dolor_escala' => 'dolor_escala',
    'diagnostico_ingreso' => 'diagnostico_ingreso',
    'impresion_clinica' => 'impresion_clinica',

    // Campos de logística
    'via_ingreso' => 'via_ingreso',
    'medio_transporte' => 'medio_transporte',

    // Campos del acompañante
    'acompanante' => 'acompanante',
    'telefono_acompanante' => 'telefono_acompanante',
    'parentesco_acompanante' => 'parentesco_acompanante',
    'firma_consentimiento' => 'firma_consentimiento'
];

// Mapeo de campos para tabla signos_vitales_ingreso
$campos_signos_vitales_ingreso = [
    'id_caso' => 'id_caso',
    'temperatura' => 'temperatura',
    'pulso' => 'pulso',
    'frecuencia_respiratoria' => 'frecuencia_respiratoria',
    'presion_sistolica' => 'presion_sistolica',
    'presion_diastolica' => 'presion_diastolica',
    'saturacion_oxigeno' => 'saturacion_oxigeno',
    'peso' => 'peso',
    'talla' => 'talla',
    'circunferencia_abdominal' => 'circunferencia_abdominal',
    'glucemia' => 'glucemia',
    'id_enfermero' => 'id_enfermero'
];

// Campos requeridos por tabla
$campos_requeridos = [
    'casos_clinicos' => [
        'id_paciente',
        'servicio_ingreso',
        'motivo_consulta',
        'id_medico_responsable'
    ],
    'datos_ingreso' => [
        'id_caso',
        'fecha_ingreso',
        'hora_ingreso',
        'motivo_consulta',
        'id_medico_ingreso'
    ],
    'pacientes' => [
        'nombres',
        'apellidos',
        'cedula',
        'sexo'
    ]
];

// Valores por defecto
$valores_por_defecto = [
    'casos_clinicos' => [
        'tipo_ingreso' => 'emergencia',
        'prioridad' => 'media',
        'estado_caso' => 'activo'
    ],
    'datos_ingreso' => [
        'via_ingreso' => 'emergencia',
        'firma_consentimiento' => 0
    ]
];

// Validaciones de tipo de dato
$validaciones_tipo = [
    'dolor_escala' => 'integer',
    'temperatura' => 'float',
    'pulso' => 'integer',
    'frecuencia_respiratoria' => 'integer',
    'presion_sistolica' => 'integer',
    'presion_diastolica' => 'integer',
    'saturacion_oxigeno' => 'float',
    'peso' => 'float',
    'talla' => 'float',
    'circunferencia_abdominal' => 'float',
    'glucemia' => 'float',
    'firma_consentimiento' => 'boolean'
];

// Rangos válidos para campos numéricos
$rangos_validacion = [
    'dolor_escala' => ['min' => 0, 'max' => 10],
    'temperatura' => ['min' => 30, 'max' => 45],
    'pulso' => ['min' => 30, 'max' => 200],
    'frecuencia_respiratoria' => ['min' => 8, 'max' => 60],
    'presion_sistolica' => ['min' => 60, 'max' => 300],
    'presion_diastolica' => ['min' => 30, 'max' => 150],
    'saturacion_oxigeno' => ['min' => 50, 'max' => 100],
    'peso' => ['min' => 0.5, 'max' => 300],
    'talla' => ['min' => 30, 'max' => 250],
    'glucemia' => ['min' => 30, 'max' => 600]
];

// Función para validar datos antes de insertar
function validarDatosFormulario($datos, $tabla)
{
    global $campos_requeridos, $validaciones_tipo, $rangos_validacion;

    $errores = [];

    // Validar campos requeridos
    if (isset($campos_requeridos[$tabla])) {
        foreach ($campos_requeridos[$tabla] as $campo) {
            if (empty($datos[$campo])) {
                $errores[] = "El campo $campo es requerido para $tabla";
            }
        }
    }

    // Validar tipos de datos
    foreach ($datos as $campo => $valor) {
        if (isset($validaciones_tipo[$campo]) && !empty($valor)) {
            switch ($validaciones_tipo[$campo]) {
                case 'integer':
                    if (!is_numeric($valor) || !ctype_digit(strval($valor))) {
                        $errores[] = "El campo $campo debe ser un número entero";
                    }
                    break;
                case 'float':
                    if (!is_numeric($valor)) {
                        $errores[] = "El campo $campo debe ser un número";
                    }
                    break;
                case 'boolean':
                    if (!in_array($valor, [0, 1, '0', '1', true, false])) {
                        $errores[] = "El campo $campo debe ser verdadero o falso";
                    }
                    break;
            }
        }

        // Validar rangos
        if (isset($rangos_validacion[$campo]) && is_numeric($valor)) {
            $rango = $rangos_validacion[$campo];
            if ($valor < $rango['min'] || $valor > $rango['max']) {
                $errores[] = "El campo $campo debe estar entre {$rango['min']} y {$rango['max']}";
            }
        }
    }

    return $errores;
}

// Función para limpiar y preparar datos
function prepararDatosParaBD($datos, $tabla)
{
    global $valores_por_defecto, $validaciones_tipo;

    $datos_limpios = [];

    // Aplicar valores por defecto
    if (isset($valores_por_defecto[$tabla])) {
        foreach ($valores_por_defecto[$tabla] as $campo => $valor_defecto) {
            if (empty($datos[$campo])) {
                $datos[$campo] = $valor_defecto;
            }
        }
    }

    // Limpiar y convertir tipos
    foreach ($datos as $campo => $valor) {
        if (isset($validaciones_tipo[$campo])) {
            switch ($validaciones_tipo[$campo]) {
                case 'integer':
                    $datos_limpios[$campo] = !empty($valor) ? intval($valor) : null;
                    break;
                case 'float':
                    $datos_limpios[$campo] = !empty($valor) ? floatval($valor) : null;
                    break;
                case 'boolean':
                    $datos_limpios[$campo] = !empty($valor) ? 1 : 0;
                    break;
                default:
                    $datos_limpios[$campo] = !empty($valor) ? trim($valor) : null;
            }
        } else {
            $datos_limpios[$campo] = !empty($valor) ? trim($valor) : null;
        }
    }

    return $datos_limpios;
}

// Función para generar consulta SQL de inserción
function generarConsultaInsercion($tabla, $datos)
{
    $campos = array_keys($datos);
    $placeholders = array_fill(0, count($campos), '?');

    $sql = "INSERT INTO $tabla (" . implode(', ', $campos) . ") VALUES (" . implode(', ', $placeholders) . ")";

    return $sql;
}

// Función para obtener datos completos para historia médica
function obtenerDatosCompletosCaso($id_caso, $pdo)
{
    $sql = "
        SELECT 
            -- Datos del caso
            cc.*,
            -- Datos del paciente
            p.numero_historia, p.nombres, p.apellidos, p.cedula, p.sexo, 
            p.fecha_nacimiento, p.telefono, p.direccion, p.tipo_sangre,
            p.alergias_conocidas, p.seguro_medico, p.numero_seguro,
            TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) as edad,
            
            -- Datos de ingreso
            di.fecha_ingreso, di.hora_ingreso, di.enfermedad_actual, 
            di.sintomas_principales, di.tiempo_evolucion, di.dolor_escala,
            di.diagnostico_ingreso, di.impresion_clinica, di.via_ingreso,
            di.medio_transporte, di.acompanante, di.telefono_acompanante,
            di.parentesco_acompanante, di.firma_consentimiento,
            
            -- Motivo de consulta (priorizar casos_clinicos)
            COALESCE(cc.motivo_consulta, di.motivo_consulta) as motivo_consulta_completo,
            
            -- Datos del médico responsable  
            mp.nombres as medico_nombres, mp.apellidos as medico_apellidos,
            mp.especialidad as medico_especialidad,
            
            -- Días de estancia
            CASE 
                WHEN cc.estado_caso = 'cerrado' AND cc.fecha_cierre IS NOT NULL 
                THEN DATEDIFF(DATE(cc.fecha_cierre), di.fecha_ingreso)
                ELSE DATEDIFF(CURDATE(), di.fecha_ingreso)
            END as dias_estancia
            
        FROM casos_clinicos cc
        INNER JOIN pacientes p ON cc.id_paciente = p.id_paciente  
        LEFT JOIN datos_ingreso di ON cc.id_caso = di.id_caso
        LEFT JOIN personal mp ON cc.id_medico_responsable = mp.id
        WHERE cc.id_caso = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_caso]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>