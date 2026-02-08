<?php
// modulos/ingreso/crear_caso.php
require_once '../../config/database.php';
require_once '../../config/functions.php';

verificarSesion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = sanitizar($_POST);

    // Validaciones básicas
    $campos_requeridos = ['id_paciente', 'servicio_ingreso', 'fecha_ingreso', 'hora_ingreso', 'motivo_consulta', 'id_medico_ingreso', 'id_cama'];
    foreach ($campos_requeridos as $campo) {
        if (empty($datos[$campo])) {
            respuestaJSON('error', "El campo $campo es requerido");
        }
    }

    // ✅ VALIDACIÓN DE CASOS ACTIVOS
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as casos_activos
        FROM casos_clinicos
        WHERE id_paciente = ? AND estado_caso = 'activo'
    ");
    $stmt->execute([$datos['id_paciente']]);
    $casos_activos = $stmt->fetchColumn();

    if ($casos_activos > 0) {
        respuestaJSON('error', 'El paciente ya tiene un caso activo. No se puede crear otro.');
    }

    try {
        $pdo->beginTransaction();

        // ===== VALIDAR Y OBTENER INFORMACIÓN DE LA CAMA =====
        $stmt = $pdo->prepare("
            SELECT
                c.id_cama,
                c.numero_cama,
                c.sala,
                c.piso,
                c.tipo_cama,
                c.estado,
                c.id_caso_actual,
                -- Verificar si la sala de la cama coincide con el servicio de ingreso
                CASE
                    WHEN c.sala = ? THEN 1 -- Directly check if cama.sala matches servicio_ingreso
                    ELSE 0
                END as sala_correcta
            FROM camas c
            WHERE c.id_cama = ? AND c.activa = 1
        ");

        $servicio = $datos['servicio_ingreso'];
        // Pass servicio_ingreso twice: once for the CASE, once for id_cama filter
        $stmt->execute([$servicio, $datos['id_cama']]);
        $info_cama = $stmt->fetch();

        if (!$info_cama) {
            throw new Exception('La cama seleccionada no existe o no está activa.');
        }

        if ($info_cama['estado'] !== 'disponible') {
            throw new Exception('La cama seleccionada no está disponible.');
        }

        if ($info_cama['id_caso_actual']) {
            throw new Exception('La cama ya tiene un caso asignado.');
        }

        // This check will now correctly compare 'Emergencias' == 'Emergencias' (or 'UCI' == 'UCI', etc.)
        if (!$info_cama['sala_correcta']) {
            throw new Exception('La cama seleccionada no corresponde al servicio de ingreso.');
        }

        // ===== 1. CREAR CASO CLÍNICO =====
        $stmt = $pdo->prepare("
            INSERT INTO casos_clinicos
            (id_paciente, tipo_ingreso, servicio_ingreso, servicio_actual,
             cama_actual, sala_actual, piso, id_medico_responsable, prioridad,
             motivo_consulta, observaciones_generales, estado_caso, creado_en)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo', NOW())
        ");

        // Médico responsable: usar el proporcionado o el de ingreso como fallback
        $medico_responsable = !empty($datos['id_medico_responsable']) ?
            $datos['id_medico_responsable'] :
            $datos['id_medico_ingreso'];

        $stmt->execute([
            $datos['id_paciente'],
            $datos['tipo_ingreso'] ?? 'emergencia',
            $datos['servicio_ingreso'],
            $datos['servicio_actual'] ?? $datos['servicio_ingreso'],
            $info_cama['numero_cama'],  // Usar datos de la cama validada
            $info_cama['sala'],         // Usar datos de la cama validada
            $info_cama['piso'],         // Usar datos de la cama validada
            $medico_responsable,
            $datos['prioridad'] ?? 'media',
            $datos['motivo_consulta'],
            $datos['observaciones_generales'] ?? null
        ]);

        $id_caso = $pdo->lastInsertId();

        // ===== 2. ACTUALIZAR CAMA COMO OCUPADA =====
        $stmt = $pdo->prepare("
            UPDATE camas
            SET estado = 'ocupada',
                id_caso_actual = ?,
                fecha_asignacion = NOW(),
                observaciones = CONCAT(COALESCE(observaciones, ''),
                    IF(COALESCE(observaciones, '') = '', '', '\n'),
                    'Asignada a caso ID: ', ?, ' - ', NOW()),
                actualizado_en = NOW()
            WHERE id_cama = ?
        ");
        $stmt->execute([$id_caso, $id_caso, $datos['id_cama']]);

        // ===== 3. OBTENER NÚMERO DE CASO =====
        $stmt = $pdo->prepare("SELECT numero_caso FROM casos_clinicos WHERE id_caso = ?");
        $stmt->execute([$id_caso]);
        $numero_caso = $stmt->fetchColumn();

        // Si el trigger no funcionó, generar manualmente
        if (empty($numero_caso)) {
            $year = date('Y');
            $stmt = $pdo->prepare("
                SELECT COALESCE(MAX(CAST(SUBSTRING(numero_caso, 6) AS UNSIGNED)), 0) + 1 as siguiente
                FROM casos_clinicos
                WHERE numero_caso LIKE ? AND id_caso != ?
            ");
            $stmt->execute([$year . '-%', $id_caso]);
            $siguiente = $stmt->fetch();
            $numero_caso = $year . '-' . str_pad($siguiente['siguiente'], 6, '0', STR_PAD_LEFT);

            // Actualizar el registro
            $stmt = $pdo->prepare("UPDATE casos_clinicos SET numero_caso = ? WHERE id_caso = ?");
            $stmt->execute([$numero_caso, $id_caso]);
        }

        // ===== 4. DATOS DE INGRESO =====
        $telefono_acompanante = null;

        // Manejar teléfono del acompañante
        if (!empty($datos['cod_area_acompanante']) && !empty($datos['numero_acompanante'])) {
            $telefono_acompanante = $datos['cod_area_acompanante'] . '-' . $datos['numero_acompanante'];
        } elseif (!empty($datos['telefono_acompanante'])) {
            $telefono_acompanante = $datos['telefono_acompanante'];
        }

        $stmt = $pdo->prepare("
            INSERT INTO datos_ingreso
            (id_caso, fecha_ingreso, hora_ingreso, motivo_consulta, enfermedad_actual,
             sintomas_principales, tiempo_evolucion, dolor_escala, diagnostico_ingreso,
             impresion_clinica, via_ingreso, medio_transporte, acompanante,
             telefono_acompanante, parentesco_acompanante, firma_consentimiento,
             id_medico_ingreso, creado_en)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $id_caso,
            $datos['fecha_ingreso'],
            $datos['hora_ingreso'],
            $datos['motivo_consulta'],
            $datos['enfermedad_actual'] ?? null,
            $datos['sintomas_principales'] ?? null,
            $datos['tiempo_evolucion'] ?? null,
            !empty($datos['dolor_escala']) ? intval($datos['dolor_escala']) : null,
            $datos['diagnostico_ingreso'] ?? null,
            $datos['impresion_clinica'] ?? null,
            $datos['via_ingreso'] ?? 'emergencia',
            $datos['medio_transporte'] ?? null,
            $datos['acompanante'] ?? null,
            $telefono_acompanante,
            $datos['parentesco_acompanante'] ?? null,
            isset($datos['firma_consentimiento']) ? 1 : 0,
            $datos['id_medico_ingreso']
        ]);

        // ===== 5. SIGNOS VITALES INICIALES (si se proporcionan) =====
        if (!empty($datos['temperatura']) || !empty($datos['pulso']) || !empty($datos['presion_sistolica'])) {

            // Buscar un enfermero válido o usar el médico
            $id_enfermero = $datos['id_medico_ingreso']; // Por defecto usar el médico

            // Intentar encontrar un enfermero
            $stmt_enf = $pdo->prepare("
                SELECT id
                FROM personal
                WHERE (rol LIKE '%enferm%' OR rol = 'Enfermería')
                AND activo = 1
                LIMIT 1
            ");
            $stmt_enf->execute();
            $enfermero = $stmt_enf->fetch();
            if ($enfermero) {
                $id_enfermero = $enfermero['id'];
            }

            $stmt = $pdo->prepare("
                INSERT INTO signos_vitales_ingreso
                (id_caso, temperatura, pulso, presion_sistolica,
                 presion_diastolica, saturacion_oxigeno, peso, talla, circunferencia_abdominal,
                 glucemia, id_enfermero, fecha_toma, hora_toma)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), CURTIME())
            ");

            $stmt->execute([
                $id_caso,
                !empty($datos['temperatura']) ? floatval($datos['temperatura']) : null,
                !empty($datos['pulso']) ? intval($datos['pulso']) : null,

                !empty($datos['presion_sistolica']) ? intval($datos['presion_sistolica']) : null,
                !empty($datos['presion_diastolica']) ? intval($datos['presion_diastolica']) : null,
                !empty($datos['saturacion_oxigeno']) ? floatval($datos['saturacion_oxigeno']) : null,
                !empty($datos['peso']) ? floatval($datos['peso']) : null,
                !empty($datos['talla']) ? floatval($datos['talla']) : null,
                !empty($datos['circunferencia_abdominal']) ? floatval($datos['circunferencia_abdominal']) : null,
                !empty($datos['glucemia']) ? floatval($datos['glucemia']) : null,
                $id_enfermero
            ]);
        }

        // ===== 6. AGREGAR MÉDICOS RESPONSABLES =====
        $medico_ingreso_es_principal = true; // Asumimos que el médico de ingreso es principal por defecto

        // Si se selecciona un médico responsable diferente al de ingreso, ese será el principal
        if (!empty($datos['id_medico_responsable']) && $datos['id_medico_responsable'] != $datos['id_medico_ingreso']) {
            $stmt = $pdo->prepare("
                INSERT INTO medicos_responsables
                (id_caso, id_medico, rol_responsabilidad, fecha_asignacion)
                VALUES (?, ?, 'principal', NOW())
            ");
            $stmt->execute([$id_caso, $datos['id_medico_responsable']]);
            $medico_ingreso_es_principal = false; // Ya hay un principal asignado
        }

        // Determinar el rol para el médico de ingreso
        $rol_medico_ingreso = $medico_ingreso_es_principal ? 'principal' : 'colaborador';

        // Siempre agregar el médico de ingreso
        $stmt = $pdo->prepare("
            INSERT INTO medicos_responsables
            (id_caso, id_medico, rol_responsabilidad, fecha_asignacion)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$id_caso, $datos['id_medico_ingreso'], $rol_medico_ingreso]);

        // ===== 7. REGISTRAR AUDITORÍA =====
        registrarAuditoria($pdo, 'casos_clinicos', $id_caso, 'INSERT', null, [
            'numero_caso' => $numero_caso,
            'id_paciente' => $datos['id_paciente'],
            'servicio' => $datos['servicio_ingreso'],
            'cama' => $info_cama['numero_cama'],
            'sala' => $info_cama['sala'],
            'piso' => $info_cama['piso'],
            'medico_ingreso' => $datos['id_medico_ingreso']
        ]);

        // ===== 8. CONFIRMAR TRANSACCIÓN =====
        $pdo->commit();

        respuestaJSON('ok', 'Caso clínico creado exitosamente', [
            'id_caso' => $id_caso,
            'numero_caso' => $numero_caso,
            'cama_asignada' => $info_cama['numero_cama'],
            'sala' => $info_cama['sala'],
            'piso' => $info_cama['piso']
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error en crear_caso.php: " . $e->getMessage());

        // Si hay error, intentar liberar la cama si se había actualizado
        if (isset($id_caso) && isset($datos['id_cama'])) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE camas
                    SET estado = 'disponible',
                        id_caso_actual = NULL,
                        fecha_asignacion = NULL
                    WHERE id_cama = ? AND id_caso_actual = ?
                ");
                $stmt->execute([$datos['id_cama'], $id_caso]);
            } catch (Exception $e2) {
                // Ignorar error en rollback de cama
            }
        }

        respuestaJSON('error', 'Error al crear caso: ' . $e->getMessage());
    }
}
?>