<?php
// modulos/procesos/guardar_signos.php
require_once '../../config/forms/database.php';
require_once '../../config/forms/functions.php';

verificarSesion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = sanitizar($_POST);

    // 🆕 VALIDAR CAMPOS REQUERIDOS
    $campos_requeridos = ['id_caso', 'id_enfermero'];
    foreach ($campos_requeridos as $campo) {
        if (empty($datos[$campo])) {
            respuestaJSON('error', "El campo $campo es requerido");
        }
    }

    // 🆕 VALIDAR QUE LA ENFERMERA EXISTE Y ESTÁ ACTIVA
    try {
        $stmt = $pdo->prepare("
            SELECT id, CONCAT(nombres, ' ', apellidos) as nombre_completo 
            FROM personal 
            WHERE id = ? AND rol = 'Enfermería' AND activo = 1
        ");
        $stmt->execute([$datos['id_enfermero']]);
        $enfermera = $stmt->fetch();

        if (!$enfermera) {
            respuestaJSON('error', 'La enfermera seleccionada no es válida o no está activa');
        }
    } catch (Exception $e) {
        respuestaJSON('error', 'Error al validar enfermera: ' . $e->getMessage());
    }

    // Validar que al menos un signo vital esté presente
    $signos_presentes = false;
    $signos_vitales = ['temperatura', 'pulso', 'presion_sistolica', 'presion_diastolica', 'saturacion_oxigeno', 'dolor', 'glucemia', 'diuresis', 'evacuaciones'];

    foreach ($signos_vitales as $signo) {
        if (!empty($datos[$signo])) {
            $signos_presentes = true;
            break;
        }
    }

    if (!$signos_presentes) {
        respuestaJSON('error', 'Debe ingresar al menos un signo vital');
    }

    try {
        $pdo->beginTransaction();

        // Validaciones adicionales
        $errores = [];

        // Validar temperatura
        if (!empty($datos['temperatura'])) {
            $temp = floatval($datos['temperatura']);
            if ($temp < 30 || $temp > 45) {
                $errores[] = "Temperatura fuera de rango válido (30-45°C)";
            }
        }

        // Validar pulso
        if (!empty($datos['pulso'])) {
            $pulso = intval($datos['pulso']);
            if ($pulso < 30 || $pulso > 250) {
                $errores[] = "Pulso fuera de rango válido (30-250 lpm)";
            }
        }



        // Validar presión arterial
        if (!empty($datos['presion_sistolica']) && !empty($datos['presion_diastolica'])) {
            $sistolica = intval($datos['presion_sistolica']);
            $diastolica = intval($datos['presion_diastolica']);

            if ($sistolica <= $diastolica) {
                $errores[] = "La presión sistólica debe ser mayor que la diastólica";
            }

            if ($sistolica < 60 || $sistolica > 300) {
                $errores[] = "Presión sistólica fuera de rango válido (60-300 mmHg)";
            }

            if ($diastolica < 30 || $diastolica > 200) {
                $errores[] = "Presión diastólica fuera de rango válido (30-200 mmHg)";
            }
        }

        // Validar saturación de oxígeno
        if (!empty($datos['saturacion_oxigeno'])) {
            $sat = floatval($datos['saturacion_oxigeno']);
            if ($sat < 70 || $sat > 100) {
                $errores[] = "Saturación de oxígeno fuera de rango válido (70-100%)";
            }
        }

        // Validar dolor
        if (!empty($datos['dolor'])) {
            $dolor = intval($datos['dolor']);
            if ($dolor < 0 || $dolor > 10) {
                $errores[] = "Escala de dolor debe estar entre 0 y 10";
            }
        }

        // Validar glucemia
        if (!empty($datos['glucemia'])) {
            $glucemia = floatval($datos['glucemia']);
            if ($glucemia < 20 || $glucemia > 800) {
                $errores[] = "Glucemia fuera de rango válido (20-800 mg/dl)";
            }
        }

        // Si hay errores de validación, retornar
        if (!empty($errores)) {
            respuestaJSON('error', implode('. ', $errores));
        }

        // 🆕 INSERTAR SIGNOS VITALES CON ENFERMERA VALIDADA
        $stmt = $pdo->prepare("
    INSERT INTO signos_vitales 
    (id_caso, turno, temperatura, pulso,
     presion_sistolica, presion_diastolica, saturacion_oxigeno, dolor,
     glucemia, diuresis, evacuaciones, observaciones, id_enfermero)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

        $stmt->execute([
            $datos['id_caso'],
            $datos['turno'] ?? 'mañana',
            !empty($datos['temperatura']) ? floatval($datos['temperatura']) : null,
            !empty($datos['pulso']) ? intval($datos['pulso']) : null,

            !empty($datos['presion_sistolica']) ? intval($datos['presion_sistolica']) : null,
            !empty($datos['presion_diastolica']) ? intval($datos['presion_diastolica']) : null,
            !empty($datos['saturacion_oxigeno']) ? floatval($datos['saturacion_oxigeno']) : null,
            !empty($datos['dolor']) ? intval($datos['dolor']) : null,
            !empty($datos['glucemia']) ? floatval($datos['glucemia']) : null,
            !empty($datos['diuresis']) ? floatval($datos['diuresis']) : null,
            !empty($datos['evacuaciones']) ? intval($datos['evacuaciones']) : null,
            $datos['observaciones'] ?? null,
            $datos['id_enfermero']  // 🆕 USAR LA ENFERMERA SELECCIONADA
        ]);

        $id_signos = $pdo->lastInsertId();

        // Verificar valores críticos y crear alertas si es necesario
        $alertas_criticas = [];

        // Temperatura crítica
        if (!empty($datos['temperatura'])) {
            $temp = floatval($datos['temperatura']);
            if ($temp >= 39.0) {
                $alertas_criticas[] = "Fiebre alta: {$temp}°C";
            } elseif ($temp <= 35.0) {
                $alertas_criticas[] = "Hipotermia: {$temp}°C";
            }
        }

        // Saturación crítica
        if (!empty($datos['saturacion_oxigeno'])) {
            $sat = floatval($datos['saturacion_oxigeno']);
            if ($sat < 90) {
                $alertas_criticas[] = "Saturación crítica: {$sat}%";
            }
        }

        // Presión arterial crítica
        if (!empty($datos['presion_sistolica']) && !empty($datos['presion_diastolica'])) {
            $sistolica = intval($datos['presion_sistolica']);
            $diastolica = intval($datos['presion_diastolica']);

            if ($sistolica >= 180 || $diastolica >= 110) {
                $alertas_criticas[] = "Crisis hipertensiva: {$sistolica}/{$diastolica} mmHg";
            } elseif ($sistolica <= 90 || $diastolica <= 60) {
                $alertas_criticas[] = "Hipotensión: {$sistolica}/{$diastolica} mmHg";
            }
        }

        // Pulso crítico
        if (!empty($datos['pulso'])) {
            $pulso = intval($datos['pulso']);
            if ($pulso >= 120) {
                $alertas_criticas[] = "Taquicardia: {$pulso} lpm";
            } elseif ($pulso <= 50) {
                $alertas_criticas[] = "Bradicardia: {$pulso} lpm";
            }
        }

        // Dolor severo
        if (!empty($datos['dolor'])) {
            $dolor = intval($datos['dolor']);
            if ($dolor >= 8) {
                $alertas_criticas[] = "Dolor severo: {$dolor}/10";
            }
        }

        // Crear alertas para médicos si hay valores críticos
        if (!empty($alertas_criticas)) {
            // Obtener médicos responsables del caso
            $stmt = $pdo->prepare("
                SELECT DISTINCT mr.id_medico 
                FROM medicos_responsables mr 
                WHERE mr.id_caso = ? AND mr.activo = 1
            ");
            $stmt->execute([$datos['id_caso']]);
            $medicos = $stmt->fetchAll();

            $mensaje_alerta = "Signos vitales críticos detectados:\n" . implode("\n", $alertas_criticas);

            foreach ($medicos as $medico) {
                if (function_exists('crearAlerta')) {
                    crearAlerta(
                        $pdo,
                        $datos['id_caso'],
                        'critica',
                        'Signos Vitales Críticos',
                        $mensaje_alerta,
                        $medico['id_medico'],
                        'critica'
                    );
                }
            }
        }

        // 🆕 REGISTRAR AUDITORÍA CON INFORMACIÓN COMPLETA
        registrarAuditoria($pdo, 'signos_vitales', $id_signos, 'INSERT', null, [
            'caso' => $datos['id_caso'],
            'enfermera' => $enfermera['nombre_completo'],
            'turno' => $datos['turno']
        ]);

        $pdo->commit();

        $respuesta = [
            'status' => 'ok',
            'message' => 'Signos vitales guardados exitosamente',
            'id_signos' => $id_signos,
            'enfermera' => $enfermera['nombre_completo']
        ];

        // Incluir alertas en la respuesta si las hay
        if (!empty($alertas_criticas)) {
            $respuesta['alertas_criticas'] = $alertas_criticas;
        }

        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        respuestaJSON('error', 'Error al guardar signos vitales: ' . $e->getMessage());
    }
} else {
    respuestaJSON('error', 'Método no permitido');
}
?>