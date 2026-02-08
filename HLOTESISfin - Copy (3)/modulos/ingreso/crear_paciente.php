<?php
// modulos/ingreso/crear_paciente.php
require_once '../../config/forms/database.php';
require_once '../../config/forms/functions.php';

verificarSesion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = sanitizar($_POST);

    // Validaciones básicas
    $campos_requeridos = ['nombres', 'apellidos', 'cedula', 'sexo'];
    foreach ($campos_requeridos as $campo) {
        if (empty($datos[$campo])) {
            respuestaJSON('error', "El campo $campo es requerido");
        }
    }

    // Validar cédula
    if (!validarCedulaVenezolana($datos['cedula'])) {
        respuestaJSON('error', 'La cédula ingresada no es válida');
    }

    // 📱 MANEJO MEJORADO DE TELÉFONO
    $telefono_completo = null;

    // Prioridad 1: Campos nuevos del formulario mejorado
    if (!empty($datos['cod_area_paciente']) && !empty($datos['numero_paciente'])) {
        $telefono_completo = $datos['cod_area_paciente'] . '-' . $datos['numero_paciente'];

        // Validar formato venezolano
        $telefono_sin_guion = $datos['cod_area_paciente'] . $datos['numero_paciente'];
        if (!validarTelefonoVenezolano($telefono_sin_guion)) {
            respuestaJSON('error', 'El número de teléfono ingresado no es válido');
        }
    }
    // Prioridad 2: Campo directo telefono (campo oculto)
    elseif (!empty($datos['telefono'])) {
        $telefono_completo = $datos['telefono'];

        // Validar si tiene el formato correcto
        $telefono_sin_guion = str_replace('-', '', $telefono_completo);
        if (!validarTelefonoVenezolano($telefono_sin_guion)) {
            respuestaJSON('error', 'El número de teléfono ingresado no es válido');
        }
    }
    // Prioridad 3: Campos legacy (compatibilidad)
    elseif (!empty($datos['cod_area']) && !empty($datos['numero'])) {
        $telefono_completo = $datos['cod_area'] . '-' . $datos['numero'];

        $telefono_sin_guion = $datos['cod_area'] . $datos['numero'];
        if (!validarTelefonoVenezolano($telefono_sin_guion)) {
            respuestaJSON('error', 'El número de teléfono ingresado no es válido');
        }
    }

    try {
        $pdo->beginTransaction();

        // Verificar si ya existe la cédula
        $stmt = $pdo->prepare("SELECT id_paciente FROM pacientes WHERE cedula = ?");
        $stmt->execute([$datos['cedula']]);
        if ($stmt->fetch()) {
            respuestaJSON('error', 'Ya existe un paciente con esta cédula');
        }

        // ✅ INSERTAR PACIENTE CON CAMPOS CORRECTOS
        $stmt = $pdo->prepare("
            INSERT INTO pacientes
            (nombres, apellidos, cedula, sexo, fecha_nacimiento, direccion,
             telefono, tipo_sangre, alergias_conocidas, seguro_medico, numero_seguro)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        // ⚠️ IMPORTANT: Limitar tipo_sangre a 15 caracteres
        $tipo_sangre = !empty($datos['tipo_sangre']) ? substr($datos['tipo_sangre'], 0, 15) : null;

        $stmt->execute([
            $datos['nombres'],
            $datos['apellidos'],
            $datos['cedula'],
            $datos['sexo'],
            $datos['fecha_nacimiento'] ?? null,
            $datos['direccion'] ?? null,
            $telefono_completo,
            $tipo_sangre,
            $datos['alergias_conocidas'] ?? null,
            $datos['seguro_medico'] ?? null,
            $datos['numero_seguro'] ?? null
        ]);

        $id_paciente = $pdo->lastInsertId();

        // Obtener el número de historia generado
        $stmt = $pdo->prepare("SELECT numero_historia FROM pacientes WHERE id_paciente = ?");
        $stmt->execute([$id_paciente]);
        $numero_historia = $stmt->fetchColumn();

        // Si el trigger no funcionó, generar manualmente
        if (empty($numero_historia)) {
            $stmt = $pdo->prepare("SELECT COALESCE(MAX(CAST(numero_historia AS UNSIGNED)), 0) + 1 as siguiente FROM pacientes WHERE id_paciente != ?");
            $stmt->execute([$id_paciente]);
            $siguiente = $stmt->fetch();
            $numero_historia = str_pad($siguiente['siguiente'], 6, '0', STR_PAD_LEFT);

            // Actualizar el registro
            $stmt = $pdo->prepare("UPDATE pacientes SET numero_historia = ? WHERE id_paciente = ?");
            $stmt->execute([$numero_historia, $id_paciente]);
        }

        // Registrar auditoría
        $datos_auditoria = $datos;
        if ($telefono_completo) {
            $datos_auditoria['telefono_completo'] = $telefono_completo;
        }
        registrarAuditoria($pdo, 'pacientes', $id_paciente, 'INSERT', null, $datos_auditoria);

        $pdo->commit();

        respuestaJSON('ok', 'Paciente creado exitosamente', [
            'id_paciente' => $id_paciente,
            'numero_historia' => $numero_historia,
            'telefono_completo' => $telefono_completo
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error en crear_paciente.php: " . $e->getMessage());
        respuestaJSON('error', 'Error al crear paciente: ' . $e->getMessage());
    }
}

// Función para validar cédula venezolana
function validarCedulaVenezolana($cedula)
{
    // Verificar formato con prefijo
    if (!preg_match('/^[VEPGJ]-/', $cedula)) {
        return false;
    }

    // Extraer parte numérica después del guión
    $numeric_cedula = preg_replace('/^[VEPGJ]-/', '', $cedula);
    $numeric_cedula = preg_replace('/[^0-9]/', '', $numeric_cedula);

    // Verificar longitud (7 u 8 dígitos)
    if (strlen($numeric_cedula) < 7 || strlen($numeric_cedula) > 8) {
        return false;
    }

    // Verificar que no sean todos números iguales
    if (preg_match('/^(\d)\1+$/', $numeric_cedula)) {
        return false;
    }

    // Verificar secuencias obvias
    $secuencias = ['1234567', '12345678', '7654321', '87654321'];
    if (in_array($numeric_cedula, $secuencias)) {
        return false;
    }

    // Rango válido
    $numero_cedula_int = intval($numeric_cedula);
    if ($numero_cedula_int < 1000000 || $numero_cedula_int > 99999999) {
        return false;
    }

    return true;
}

// Función para validar teléfono venezolano
function validarTelefonoVenezolano($telefono)
{
    // Limpiar el teléfono
    $telefono = preg_replace('/[^0-9]/', '', $telefono);

    // Debe tener 11 dígitos y empezar con 0
    if (strlen($telefono) !== 11 || !str_starts_with($telefono, '0')) {
        return false;
    }

    // Códigos de área válidos para Venezuela
    $codigos_validos = [
        '0412',
        '0414',
        '0416',
        '0424',
        '0426', // Celulares
        '0212',
        '0213',
        '0214',
        '0215',
        '0216',
        '0218', // Caracas
        '0241',
        '0242',
        '0243',
        '0244',
        '0245',
        '0246', // Valencia/Aragua
        '0251',
        '0252',
        '0253',
        '0254',
        '0255',
        '0258', // Carabobo/Yaracuy
        '0261',
        '0262',
        '0263',
        '0264',
        '0265',
        '0267', // Zulia
        '0271',
        '0272',
        '0273',
        '0274',
        '0275', // Barinas/Portuguesa
        '0281',
        '0282',
        '0283',
        '0284',
        '0285',
        '0286',
        '0287',
        '0288', // Anzoátegui/Sucre
        '0291',
        '0292',
        '0293',
        '0294',
        '0295' // Delta Amacuro/Monagas
    ];

    $codigo_area = substr($telefono, 0, 4);
    return in_array($codigo_area, $codigos_validos);
}
?>