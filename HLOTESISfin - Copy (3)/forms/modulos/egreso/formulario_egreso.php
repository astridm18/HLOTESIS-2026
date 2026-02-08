<?php
// modulos/egreso/formulario_egreso.php
require_once '../../config/database.php';
require_once '../../config/functions.php';

verificarSesion();

$id_caso = $_GET['caso'] ?? 0;
if (!$id_caso) {
    die('Caso no especificado');
}

// CONSULTA MEJORADA - Obtener datos completos del caso
$stmt = $pdo->prepare("
    SELECT cc.*, cc.id_paciente, p.numero_historia, CONCAT(p.nombres, ' ', p.apellidos) as nombre_paciente,
           p.cedula, p.sexo, p.fecha_nacimiento, p.telefono, p.direccion, p.tipo_sangre,
           p.alergias_conocidas, p.seguro_medico, p.numero_seguro,
           TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) as edad,
           di.fecha_ingreso, di.hora_ingreso, di.enfermedad_actual, di.sintomas_principales,
           di.tiempo_evolucion, di.dolor_escala, di.diagnostico_ingreso, di.impresion_clinica,
           di.via_ingreso, di.medio_transporte, di.acompanante, di.telefono_acompanante,
           di.parentesco_acompanante, di.firma_consentimiento,
           -- Usar motivo_consulta de casos_clinicos si existe, sino de datos_ingreso
           COALESCE(cc.motivo_consulta, di.motivo_consulta) as motivo_consulta_display,
           DATEDIFF(CURDATE(), di.fecha_ingreso) as dias_estancia,
           -- Datos del médico responsable
           mp.nombres as medico_nombres, mp.apellidos as medico_apellidos, 
           mp.especialidad as medico_especialidad,
           -- Datos del médico de ingreso
           mi.nombres as medico_ingreso_nombres, mi.apellidos as medico_ingreso_apellidos,
           mi.especialidad as medico_ingreso_especialidad
    FROM casos_clinicos cc
    INNER JOIN pacientes p ON cc.id_paciente = p.id_paciente
    INNER JOIN datos_ingreso di ON cc.id_caso = di.id_caso
    LEFT JOIN personal mp ON cc.id_medico_responsable = mp.id
    LEFT JOIN personal mi ON di.id_medico_ingreso = mi.id
    WHERE cc.id_caso = ? AND cc.estado_caso = 'activo'
");
$stmt->execute([$id_caso]);
$caso = $stmt->fetch();

if (!$caso) {
    die('Caso no encontrado o ya egresado');
}

// Obtener o crear borrador de egreso
$stmt = $pdo->prepare("SELECT * FROM egresos WHERE id_caso = ?");
$stmt->execute([$id_caso]);
$egreso_existente = $stmt->fetch();

// Obtener médicos disponibles para el selector de Médico de Egreso
$stmt = $pdo->prepare("
    SELECT id, CONCAT(nombres, ' ', apellidos) as nombre_completo, especialidad
    FROM personal 
    WHERE rol = 'Medicina' AND activo = 1
    ORDER BY nombres, apellidos
");
$stmt->execute();
$medicos_egreso = $stmt->fetchAll();

// Obtener signos vitales del ingreso
$stmt = $pdo->prepare("
    SELECT temperatura, pulso, frecuencia_respiratoria, presion_sistolica, 
           presion_diastolica, saturacion_oxigeno, peso, talla, imc,
           circunferencia_abdominal, glucemia, fecha_registro
    FROM signos_vitales_ingreso 
    WHERE id_caso = ? 
    ORDER BY fecha_registro DESC 
    LIMIT 1
");
$stmt->execute([$id_caso]);
$signos_ingreso = $stmt->fetch();

// Obtener último control de signos vitales
$stmt = $pdo->prepare("
    SELECT temperatura, pulso, frecuencia_respiratoria, presion_sistolica, 
           presion_diastolica, saturacion_oxigeno, dolor, glucemia, diuresis,
           evacuaciones, fecha_registro, turno, observaciones,
           p.nombres as enfermero_nombres, p.apellidos as enfermero_apellidos
    FROM signos_vitales sv
    LEFT JOIN personal p ON sv.id_enfermero = p.id
    WHERE sv.id_caso = ? 
    ORDER BY sv.fecha_registro DESC 
    LIMIT 1
");
$stmt->execute([$id_caso]);
$ultimo_signo = $stmt->fetch();

// Obtener antecedentes del caso
$stmt = $pdo->prepare("
    SELECT tipo_antecedente, descripcion, fecha_evento, relevancia, archivo_adjunto
    FROM antecedentes 
    WHERE id_caso = ? AND activo = 1
    ORDER BY relevancia DESC, tipo_antecedente
");
$stmt->execute([$id_caso]);
$antecedentes = $stmt->fetchAll();

// Obtener evoluciones médicas
$stmt = $pdo->prepare("
    SELECT e.fecha_evolucion, e.tipo_evolucion, e.subjetivo, e.objetivo, 
           e.evaluacion, e.plan, e.estado_paciente,
           p.nombres as medico_nombres, p.apellidos as medico_apellidos,
           p.especialidad as medico_especialidad
    FROM evoluciones e
    LEFT JOIN personal p ON e.id_medico = p.id
    WHERE e.id_caso = ?
    ORDER BY e.fecha_evolucion DESC
    LIMIT 3
");
$stmt->execute([$id_caso]);
$evoluciones = $stmt->fetchAll();

// Obtener laboratorios recientes
$stmt = $pdo->prepare("
    SELECT l.fecha_solicitud, l.fecha_resultado, l.tipo_examen, l.parametro,
           l.resultado, l.valor_numerico, l.unidad, l.valor_referencia, l.estado,
           l.observaciones
    FROM laboratorios l
    WHERE l.id_caso = ?
    ORDER BY l.fecha_solicitud DESC
    LIMIT 5
");
$stmt->execute([$id_caso]);
$laboratorios = $stmt->fetchAll();

$mensaje_flash = ''; // Usaremos esta variable para mensajes de éxito/error de guardado
$egreso_procesado_exitoso = false; // Bandera para redireccionar

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Validar el ID del médico de egreso
        $id_medico_egreso_form = $_POST['id_medico_egreso'] ?? null;
        if (empty($id_medico_egreso_form)) {
            throw new Exception("El campo 'Médico de Egreso' es requerido.");
        }
        $stmt_medico_egreso = $pdo->prepare("
            SELECT id, CONCAT(nombres, ' ', apellidos) as nombre_completo, especialidad 
            FROM personal 
            WHERE id = ? AND rol = 'Medicina' AND activo = 1
        ");
        $stmt_medico_egreso->execute([$id_medico_egreso_form]);
        $medico_egreso_data = $stmt_medico_egreso->fetch();

        if (!$medico_egreso_data) {
            throw new Exception("El Médico de Egreso seleccionado no es válido o no está activo.");
        }

        $datos_egreso_form = [
            'id_caso' => $id_caso,
            'fecha_egreso' => $_POST['fecha_egreso'],
            'tipo_egreso' => $_POST['tipo_egreso'],
            'estado_egreso' => $_POST['estado_egreso'],
            'diagnostico_principal_egreso' => $_POST['diagnostico_principal_egreso'],
            'diagnosticos_secundarios' => $_POST['diagnosticos_secundarios'] ?? null,
            'recomendaciones_generales' => $_POST['recomendaciones_generales'] ?? null,
            'prescripciones_egreso' => $_POST['prescripciones_egreso'] ?? null,
            'control_seguimiento' => $_POST['control_seguimiento'] ?? null,
            'signos_alarma' => $_POST['signos_alarma'] ?? null,
            'observaciones_finales' => $_POST['observaciones_finales'] ?? null,
            'id_medico_egreso' => $id_medico_egreso_form, // Usar el ID del selector
            'id_usuario_egreso' => $_SESSION['usuario_id'],
            'estado_egreso_sistema' => ($_POST['accion'] === 'egreso_definitivo') ? 'definitivo' : 'borrador'
        ];

        if ($egreso_existente) {
            // Actualizar egreso existente
            $sql = "UPDATE egresos SET 
                    fecha_egreso = ?, tipo_egreso = ?, estado_egreso = ?,
                    diagnostico_principal_egreso = ?, diagnosticos_secundarios = ?,
                    recomendaciones_generales = ?, prescripciones_egreso = ?,
                    control_seguimiento = ?, signos_alarma = ?, observaciones_finales = ?,
                    id_medico_egreso = ?, id_usuario_egreso = ?, estado_egreso_sistema = ?
                    WHERE id_caso = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $datos_egreso_form['fecha_egreso'],
                $datos_egreso_form['tipo_egreso'],
                $datos_egreso_form['estado_egreso'],
                $datos_egreso_form['diagnostico_principal_egreso'],
                $datos_egreso_form['diagnosticos_secundarios'],
                $datos_egreso_form['recomendaciones_generales'],
                $datos_egreso_form['prescripciones_egreso'],
                $datos_egreso_form['control_seguimiento'],
                $datos_egreso_form['signos_alarma'],
                $datos_egreso_form['observaciones_finales'],
                $datos_egreso_form['id_medico_egreso'],
                $datos_egreso_form['id_usuario_egreso'],
                $datos_egreso_form['estado_egreso_sistema'],
                $id_caso
            ]);
            $id_egreso_audit = $egreso_existente['id_egreso'];
            registrarAuditoria($pdo, 'egresos', $id_egreso_audit, 'UPDATE', null, $datos_egreso_form);
        } else {
            // Insertar nuevo egreso
            $sql = "INSERT INTO egresos (id_caso, fecha_egreso, tipo_egreso, estado_egreso,
                    diagnostico_principal_egreso, diagnosticos_secundarios, recomendaciones_generales,
                    prescripciones_egreso, control_seguimiento, signos_alarma, observaciones_finales,
                    id_medico_egreso, id_usuario_egreso, estado_egreso_sistema)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $datos_egreso_form['id_caso'],
                $datos_egreso_form['fecha_egreso'],
                $datos_egreso_form['tipo_egreso'],
                $datos_egreso_form['estado_egreso'],
                $datos_egreso_form['diagnostico_principal_egreso'],
                $datos_egreso_form['diagnosticos_secundarios'],
                $datos_egreso_form['recomendaciones_generales'],
                $datos_egreso_form['prescripciones_egreso'],
                $datos_egreso_form['control_seguimiento'],
                $datos_egreso_form['signos_alarma'],
                $datos_egreso_form['observaciones_finales'],
                $datos_egreso_form['id_medico_egreso'],
                $datos_egreso_form['id_usuario_egreso'],
                $datos_egreso_form['estado_egreso_sistema']
            ]);
            $id_egreso_audit = $pdo->lastInsertId();
            registrarAuditoria($pdo, 'egresos', $id_egreso_audit, 'INSERT', null, $datos_egreso_form);
        }

        // Si es egreso definitivo
        if ($_POST['accion'] === 'egreso_definitivo') {
            // Cerrar caso
            $stmt = $pdo->prepare("UPDATE casos_clinicos SET estado_caso = 'cerrado', fecha_cierre = ? WHERE id_caso = ?");
            $stmt->execute([$datos_egreso_form['fecha_egreso'], $id_caso]);
            registrarAuditoria($pdo, 'casos_clinicos', $id_caso, 'UPDATE', ['estado_caso_anterior' => $caso['estado_caso']], ['estado_caso' => 'cerrado']);

            $pdo->commit();
            $egreso_procesado_exitoso = true;
            // Redirigir a la página de resumen
            header("Location: resumen_egreso.php?caso=" . $id_caso);
            exit();
        } else {
            // Solo guardar borrador
            $pdo->commit();
            $mensaje_flash = 'borrador';
            // Recargar datos del egreso existente para reflejar los cambios en el formulario
            $stmt = $pdo->prepare("SELECT * FROM egresos WHERE id_caso = ?");
            $stmt->execute([$id_caso]);
            $egreso_existente = $stmt->fetch();
        }

    } catch (Exception $e) {
        $pdo->rollback();
        $mensaje_flash = 'error: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesar Egreso - <?= $caso['nombre_paciente'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    .success-alert {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 30px;
        border-radius: 15px;
        text-align: center;
        margin-bottom: 30px;
        animation: slideIn 0.5s ease-out;
    }

    .check-animation {
        font-size: 60px;
        animation: bounce 1s ease-in-out;
    }

    @keyframes bounce {

        0%,
        20%,
        50%,
        80%,
        100% {
            transform: translateY(0);
        }

        40% {
            transform: translateY(-20px);
        }

        60% {
            transform: translateY(-10px);
        }
    }

    @keyframes slideIn {
        from {
            transform: translateY(-50px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .print-section {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        padding: 20px;
        margin: 20px 0;
        border-radius: 10px;
    }

    .info-box {
        background: #f8f9fa;
        border-left: 4px solid #007bff;
        padding: 15px;
        margin: 10px 0;
        border-radius: 0 8px 8px 0;
    }

    .evolution-item {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .vitals-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 15px;
        margin: 15px 0;
    }

    .vital-card {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 10px;
        text-align: center;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .vital-value {
        font-size: 1.2em;
        font-weight: bold;
        color: #007bff;
    }

    .vital-label {
        font-size: 0.85em;
        color: #6c757d;
        margin-top: 5px;
    }

    .antecedente-badge {
        display: inline-block;
        padding: 4px 8px;
        margin: 2px;
        border-radius: 12px;
        font-size: 0.8em;
        font-weight: 500;
    }

    .badge-personal {
        background: #e3f2fd;
        color: #1976d2;
    }

    .badge-familiar {
        background: #f3e5f5;
        color: #7b1fa2;
    }

    .badge-quirurgico {
        background: #fff3e0;
        color: #f57c00;
    }

    .badge-farmacologico {
        background: #e8f5e8;
        color: #388e3c;
    }

    .badge-alergico {
        background: #ffebee;
        color: #d32f2f;
    }

    @media print {
        .no-print {
            display: none !important;
        }

        .print-section {
            border: 1px solid #000;
            page-break-inside: avoid;
        }
    }

    .timeline-item {
        border-left: 3px solid #007bff;
        padding-left: 15px;
        margin-bottom: 15px;
        position: relative;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: -6px;
        top: 5px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #007bff;
    }

    .lab-result {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 12px;
        margin: 5px 0;
        border-radius: 6px;
        background: #f8f9fa;
    }

    .lab-normal {
        border-left: 4px solid #28a745;
    }

    .lab-anormal {
        border-left: 4px solid #ffc107;
    }

    .lab-critico {
        border-left: 4px solid #dc3545;
    }

    .medico-selector {
        border: 2px solid #007bff;
        border-radius: 8px;
        background: #f8f9ff;
    }

    .medico-info {
        font-size: 0.85em;
        color: #6c757d;
    }
    </style>
</head>

<body>
    <div class="container-fluid mt-4">
        <?php if ($mensaje_flash == 'borrador'): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-save me-2"></i>Borrador guardado exitosamente
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php elseif (strpos($mensaje_flash, 'error') === 0): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i><?= substr($mensaje_flash, 7) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card bg-primary text-white mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h4><i class="fas fa-user-injured me-2"></i><?= $caso['nombre_paciente'] ?></h4>
                        <p class="mb-0">
                            <strong>Historia:</strong> <?= $caso['numero_historia'] ?> |
                            <strong>Caso:</strong> <?= $caso['numero_caso'] ?> |
                            <strong>Cédula:</strong> <?= $caso['cedula'] ?> |
                            <strong>Edad:</strong> <?= $caso['edad'] ?> años |
                            <strong>Sexo:</strong> <?= $caso['sexo'] == 'M' ? 'Masculino' : 'Femenino' ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <h5><i class="fas fa-calendar-alt me-1"></i><?= $caso['dias_estancia'] ?> días</h5>
                        <small>Días de estancia</small>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5><i class="fas fa-diagnoses me-2"></i>Información de Ingreso y Estado Actual</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><strong>Motivo de Consulta de Ingreso:</strong></h6>
                            <div class="info-box">
                                <i class="fas fa-info-circle me-2"></i>
                                <?= htmlspecialchars($caso['motivo_consulta_display']) ?>
                            </div>

                            <?php if (!empty($caso['enfermedad_actual'])): ?>
                            <h6 class="mt-3"><strong>Enfermedad Actual:</strong></h6>
                            <div class="info-box">
                                <i class="fas fa-notes-medical me-2"></i>
                                <?= htmlspecialchars($caso['enfermedad_actual']) ?>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($caso['sintomas_principales']) || !empty($caso['tiempo_evolucion']) || !empty($caso['dolor_escala'])): ?>
                            <div class="mt-3">
                                <h6><strong>Información Adicional:</strong></h6>
                                <div class="info-box">
                                    <?php if (!empty($caso['sintomas_principales'])): ?>
                                    <p class="mb-2"><strong>Síntomas:</strong>
                                        <?= htmlspecialchars($caso['sintomas_principales']) ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($caso['tiempo_evolucion'])): ?>
                                    <p class="mb-2"><strong>Tiempo evolución:</strong>
                                        <?= htmlspecialchars($caso['tiempo_evolucion']) ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($caso['dolor_escala'])): ?>
                                    <p class="mb-0"><strong>Dolor inicial:</strong>
                                        <?= htmlspecialchars($caso['dolor_escala']) ?>/10</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($caso['diagnostico_ingreso'])): ?>
                            <h6 class="mt-3"><strong>Diagnóstico de Ingreso:</strong></h6>
                            <div class="alert alert-success">
                                <i class="fas fa-stethoscope me-2"></i>
                                <?= htmlspecialchars($caso['diagnostico_ingreso']) ?>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-warning mt-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <small>No se registró diagnóstico específico al ingreso</small>
                            </div>
                            <?php endif; ?>

                            <div class="mt-3">
                                <h6><strong>Equipo Médico:</strong></h6>
                                <div class="info-box">
                                    <?php if (!empty($caso['medico_nombres'])): ?>
                                    <p class="mb-1"><strong>Médico Responsable:</strong>
                                        Dr(a).
                                        <?= htmlspecialchars($caso['medico_nombres'] . ' ' . $caso['medico_apellidos']) ?>
                                        <?php if (!empty($caso['medico_especialidad'])): ?>
                                        <br><small
                                            class="text-muted"><?= htmlspecialchars($caso['medico_especialidad']) ?></small>
                                        <?php endif; ?>
                                    </p>
                                    <?php endif; ?>
                                    <?php if (!empty($caso['medico_ingreso_nombres']) && $caso['medico_ingreso_nombres'] != $caso['medico_nombres']): ?>
                                    <p class="mb-0"><strong>Médico de Ingreso:</strong>
                                        Dr(a).
                                        <?= htmlspecialchars($caso['medico_ingreso_nombres'] . ' ' . $caso['medico_ingreso_apellidos']) ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <?php if (!empty($signos_ingreso)): ?>
                            <h6><strong>Signos Vitales de Ingreso:</strong></h6>
                            <div class="vitals-grid">
                                <?php if ($signos_ingreso['temperatura']): ?>
                                <div class="vital-card">
                                    <div class="vital-value"><?= $signos_ingreso['temperatura'] ?>°C</div>
                                    <div class="vital-label">Temperatura</div>
                                </div>
                                <?php endif; ?>
                                <?php if ($signos_ingreso['pulso']): ?>
                                <div class="vital-card">
                                    <div class="vital-value"><?= $signos_ingreso['pulso'] ?></div>
                                    <div class="vital-label">Pulso (lpm)</div>
                                </div>
                                <?php endif; ?>
                                <?php if ($signos_ingreso['presion_sistolica'] && $signos_ingreso['presion_diastolica']): ?>
                                <div class="vital-card">
                                    <div class="vital-value">
                                        <?= $signos_ingreso['presion_sistolica'] ?>/<?= $signos_ingreso['presion_diastolica'] ?>
                                    </div>
                                    <div class="vital-label">PA (mmHg)</div>
                                </div>
                                <?php endif; ?>
                                <?php if ($signos_ingreso['saturacion_oxigeno']): ?>
                                <div class="vital-card">
                                    <div class="vital-value"><?= $signos_ingreso['saturacion_oxigeno'] ?>%</div>
                                    <div class="vital-label">Sat. O2</div>
                                </div>
                                <?php endif; ?>
                                <?php if ($signos_ingreso['peso']): ?>
                                <div class="vital-card">
                                    <div class="vital-value"><?= $signos_ingreso['peso'] ?> kg</div>
                                    <div class="vital-label">Peso</div>
                                </div>
                                <?php endif; ?>
                                <?php if ($signos_ingreso['imc']): ?>
                                <div class="vital-card">
                                    <div class="vital-value"><?= number_format($signos_ingreso['imc'], 1) ?></div>
                                    <div class="vital-label">IMC</div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($ultimo_signo)): ?>
                            <h6 class="mt-3"><strong>Último Control de Signos Vitales:</strong></h6>
                            <div class="alert alert-warning">
                                <small><strong>Fecha:</strong>
                                    <?= date('d/m/Y H:i', strtotime($ultimo_signo['fecha_registro'])) ?>
                                    (Turno: <?= ucfirst($ultimo_signo['turno']) ?>)</small>
                                <div class="vitals-grid mt-2">
                                    <?php if ($ultimo_signo['temperatura']): ?>
                                    <div class="vital-card">
                                        <div class="vital-value"><?= $ultimo_signo['temperatura'] ?>°C</div>
                                        <div class="vital-label">Temp.</div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($ultimo_signo['pulso']): ?>
                                    <div class="vital-card">
                                        <div class="vital-value"><?= $ultimo_signo['pulso'] ?></div>
                                        <div class="vital-label">Pulso</div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($ultimo_signo['presion_sistolica'] && $ultimo_signo['presion_diastolica']): ?>
                                    <div class="vital-card">
                                        <div class="vital-value">
                                            <?= $ultimo_signo['presion_sistolica'] ?>/<?= $ultimo_signo['presion_diastolica'] ?>
                                        </div>
                                        <div class="vital-label">PA</div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($ultimo_signo['saturacion_oxigeno']): ?>
                                    <div class="vital-card">
                                        <div class="vital-value"><?= $ultimo_signo['saturacion_oxigeno'] ?>%</div>
                                        <div class="vital-label">Sat. O2</div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($ultimo_signo['dolor'] !== null): ?>
                                    <div class="vital-card">
                                        <div class="vital-value"><?= $ultimo_signo['dolor'] ?>/10</div>
                                        <div class="vital-label">Dolor</div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($ultimo_signo['observaciones'])): ?>
                                <small class="d-block mt-2"><strong>Observaciones:</strong>
                                    <?= htmlspecialchars($ultimo_signo['observaciones']) ?></small>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <div class="mt-3">
                                <h6><strong>Resumen del Caso:</strong></h6>
                                <div class="info-box">
                                    <div class="row">
                                        <div class="col-6">
                                            <small><strong>Servicio:</strong>
                                                <?= htmlspecialchars($caso['servicio_actual']) ?></small><br>
                                            <small><strong>Tipo ingreso:</strong>
                                                <?= ucfirst(str_replace('_', ' ', $caso['tipo_ingreso'])) ?></small><br>
                                            <small><strong>Prioridad:</strong>
                                                <?= ucfirst($caso['prioridad']) ?></small>
                                        </div>
                                        <div class="col-6">
                                            <small><strong>Vía ingreso:</strong>
                                                <?= ucfirst(str_replace('_', ' ', $caso['via_ingreso'] ?? 'emergencia')) ?></small><br>
                                            <?php if (!empty($caso['cama_actual'])): ?>
                                            <small><strong>Cama:</strong>
                                                <?= htmlspecialchars($caso['cama_actual']) ?></small><br>
                                            <?php endif; ?>
                                            <?php if (!empty($caso['sala_actual'])): ?>
                                            <small><strong>Sala:</strong>
                                                <?= htmlspecialchars($caso['sala_actual']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($caso['acompanante'])): ?>
                            <div class="mt-3">
                                <h6><strong>Información del Acompañante:</strong></h6>
                                <div class="info-box">
                                    <small>
                                        <strong>Nombre:</strong> <?= htmlspecialchars($caso['acompanante']) ?><br>
                                        <?php if (!empty($caso['parentesco_acompanante'])): ?>
                                        <strong>Parentesco:</strong>
                                        <?= htmlspecialchars($caso['parentesco_acompanante']) ?><br>
                                        <?php endif; ?>
                                        <?php if (!empty($caso['telefono_acompanante'])): ?>
                                        <strong>Teléfono:</strong>
                                        <?= htmlspecialchars($caso['telefono_acompanante']) ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($antecedentes)): ?>
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5><i class="fas fa-history me-2"></i>Antecedentes Médicos</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($antecedentes as $ant): ?>
                        <div class="col-md-6 mb-3">
                            <div class="antecedente-badge badge-<?= $ant['tipo_antecedente'] ?>">
                                <?= ucfirst(str_replace('_', ' ', $ant['tipo_antecedente'])) ?>
                                <?php if ($ant['relevancia'] == 'alta'): ?>
                                <i class="fas fa-exclamation-triangle text-danger ms-1"></i>
                                <?php endif; ?>
                            </div>
                            <p class="mb-1 mt-2">
                                <?= htmlspecialchars(substr($ant['descripcion'], 0, 150)) ?>
                                <?php if (strlen($ant['descripcion']) > 150)
                                                echo '...'; ?>
                            </p>
                            <?php if (!empty($ant['fecha_evento'])): ?>
                            <small class="text-muted">
                                <i
                                    class="fas fa-calendar me-1"></i><?= date('d/m/Y', strtotime($ant['fecha_evento'])) ?>
                            </small>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($evoluciones)): ?>
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5><i class="fas fa-notes-medical me-2"></i>Evoluciones Médicas Recientes</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($evoluciones as $evo): ?>
                    <div class="timeline-item">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="mb-0">
                                <?= date('d/m/Y H:i', strtotime($evo['fecha_evolucion'])) ?> -
                                <?= ucfirst($evo['tipo_evolucion']) ?>
                            </h6>
                            <span
                                class="badge bg-<?= $evo['estado_paciente'] == 'mejorado' ? 'success' : ($evo['estado_paciente'] == 'critico' ? 'danger' : 'info') ?>">
                                <?= ucfirst(str_replace('_', ' ', $evo['estado_paciente'])) ?>
                            </span>
                        </div>
                        <p class="mb-1">
                            <strong>Dr(a).
                                <?= htmlspecialchars($evo['medico_nombres'] . ' ' . $evo['medico_apellidos']) ?></strong>
                            <?php if (!empty($evo['medico_especialidad'])): ?>
                            <small class="text-muted">- <?= htmlspecialchars($evo['medico_especialidad']) ?></small>
                            <?php endif; ?>
                        </p>

                        <?php if (!empty($evo['subjetivo'])): ?>
                        <p class="mb-1"><strong>S:</strong>
                            <?= htmlspecialchars(substr($evo['subjetivo'], 0, 200)) ?>
                            <?= strlen($evo['subjetivo']) > 200 ? '...' : '' ?>
                        </p>
                        <?php endif; ?>

                        <?php if (!empty($evo['objetivo'])): ?>
                        <p class="mb-1"><strong>O:</strong>
                            <?= htmlspecialchars(substr($evo['objetivo'], 0, 200)) ?>
                            <?= strlen($evo['objetivo']) > 200 ? '...' : '' ?>
                        </p>
                        <?php endif; ?>

                        <?php if (!empty($evo['evaluacion'])): ?>
                        <p class="mb-1"><strong>A:</strong>
                            <?= htmlspecialchars(substr($evo['evaluacion'], 0, 200)) ?>
                            <?= strlen($evo['evaluacion']) > 200 ? '...' : '' ?>
                        </p>
                        <?php endif; ?>

                        <?php if (!empty($evo['plan'])): ?>
                        <p class="mb-0"><strong>P:</strong>
                            <?= htmlspecialchars(substr($evo['plan'], 0, 200)) ?>
                            <?= strlen($evo['plan']) > 200 ? '...' : '' ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($laboratorios)): ?>
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5><i class="fas fa-flask me-2"></i>Laboratorios Recientes</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($laboratorios as $lab): ?>
                    <div class="lab-result lab-<?= $lab['estado'] ?? 'normal' ?>">
                        <div>
                            <strong><?= htmlspecialchars($lab['tipo_examen']) ?> -
                                <?= htmlspecialchars($lab['parametro']) ?></strong>
                            <small class="d-block text-muted">
                                Solicitado: <?= date('d/m/Y', strtotime($lab['fecha_solicitud'])) ?>
                                <?php if ($lab['fecha_resultado']): ?>
                                | Resultado: <?= date('d/m/Y', strtotime($lab['fecha_resultado'])) ?>
                                <?php endif; ?>
                            </small>
                        </div>
                        <div class="text-end">
                            <strong>
                                <?= htmlspecialchars($lab['resultado'] ?: ($lab['valor_numerico'] . ' ' . $lab['unidad'])) ?>
                            </strong>
                            <?php if ($lab['valor_referencia']): ?>
                            <small class="d-block text-muted">Ref:
                                <?= htmlspecialchars($lab['valor_referencia']) ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5><i class="fas fa-calendar-check me-2"></i>Datos de Egreso</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label"><strong>Fecha y Hora de Egreso:</strong></label>
                            <input type="datetime-local" class="form-control" name="fecha_egreso"
                                value="<?= $egreso_existente['fecha_egreso'] ?? date('Y-m-d\TH:i') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><strong>Tipo de Egreso:</strong></label>
                            <select class="form-select" name="tipo_egreso" required>
                                <option value="">Seleccionar...</option>
                                <option value="alta_medica"
                                    <?= ($egreso_existente['tipo_egreso'] ?? '') === 'alta_medica' ? 'selected' : '' ?>>
                                    Alta Médica</option>
                                <option value="alta_voluntaria"
                                    <?= ($egreso_existente['tipo_egreso'] ?? '') === 'alta_voluntaria' ? 'selected' : '' ?>>
                                    Alta Voluntaria</option>
                                <option value="traslado"
                                    <?= ($egreso_existente['tipo_egreso'] ?? '') === 'traslado' ? 'selected' : '' ?>>
                                    Traslado</option>
                                <option value="defuncion"
                                    <?= ($egreso_existente['tipo_egreso'] ?? '') === 'defuncion' ? 'selected' : '' ?>>
                                    Defunción</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><strong>Estado al Egreso:</strong></label>
                            <select class="form-select" name="estado_egreso" required>
                                <option value="">Seleccionar...</option>
                                <option value="curado"
                                    <?= ($egreso_existente['estado_egreso'] ?? '') === 'curado' ? 'selected' : '' ?>>
                                    Curado</option>
                                <option value="mejorado"
                                    <?= ($egreso_existente['estado_egreso'] ?? '') === 'mejorado' ? 'selected' : '' ?>>
                                    Mejorado</option>
                                <option value="sin_cambios"
                                    <?= ($egreso_existente['estado_egreso'] ?? '') === 'sin_cambios' ? 'selected' : '' ?>>
                                    Sin Cambios</option>
                                <option value="empeorado"
                                    <?= ($egreso_existente['estado_egreso'] ?? '') === 'empeorado' ? 'selected' : '' ?>>
                                    Empeorado</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5><i class="fas fa-diagnoses me-2"></i>Diagnósticos de Egreso</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <label class="form-label"><strong>Diagnóstico Principal de Egreso:</strong></label>
                            <textarea class="form-control" name="diagnostico_principal_egreso" rows="4"
                                placeholder="Escriba el diagnóstico principal con el que egresa el paciente..."
                                required><?= $egreso_existente['diagnostico_principal_egreso'] ?? '' ?></textarea>
                            <small class="form-text text-muted mt-2">
                                <i class="fas fa-lightbulb me-1"></i>
                                Basado en: <?= htmlspecialchars($caso['motivo_consulta_display']) ?>
                            </small>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <label class="form-label"><strong>Diagnósticos Secundarios:</strong></label>
                            <textarea class="form-control" name="diagnosticos_secundarios" rows="3"
                                placeholder="Otros diagnósticos o condiciones encontradas durante la hospitalización..."><?= $egreso_existente['diagnosticos_secundarios'] ?? '' ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5><i class="fas fa-pills me-2"></i>Tratamiento y Recomendaciones</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label"><strong>Medicamentos al Egreso:</strong></label>
                            <textarea class="form-control" name="prescripciones_egreso" rows="5"
                                placeholder="Medicamentos que debe continuar tomando el paciente:&#10;&#10;• Paracetamol 500mg cada 8 horas por 5 días&#10;• Omeprazol 20mg en ayunas por 15 días&#10;• etc..."><?= $egreso_existente['prescripciones_egreso'] ?? '' ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Recomendaciones Generales:</strong></label>
                            <textarea class="form-control" name="recomendaciones_generales" rows="5"
                                placeholder="Cuidados en casa, dieta, actividades:&#10;&#10;• Reposo relativo por 48 horas&#10;• Dieta blanda, abundantes líquidos&#10;• Evitar esfuerzos físicos&#10;• etc..."><?= $egreso_existente['recomendaciones_generales'] ?? '' ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5><i class="fas fa-clipboard-list me-2"></i>Control y Seguimiento</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label"><strong>Control y Citas:</strong></label>
                            <textarea class="form-control" name="control_seguimiento" rows="4"
                                placeholder="Cuándo debe volver a control, especialistas:&#10;&#10;• Control por consulta externa en 7 días&#10;• Control por especialista en 2 semanas&#10;• etc..."><?= $egreso_existente['control_seguimiento'] ?? '' ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Signos de Alarma:</strong></label>
                            <textarea class="form-control" name="signos_alarma" rows="4"
                                placeholder="Síntomas por los que debe consultar urgente:&#10;&#10;• Fiebre mayor a 38.5°C&#10;• Dolor intenso no controlado&#10;• Vómitos persistentes&#10;• etc..."><?= $egreso_existente['signos_alarma'] ?? '' ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <h5><i class="fas fa-comment-medical me-2"></i>Observaciones Finales</h5>
                </div>
                <div class="card-body">
                    <textarea class="form-control" name="observaciones_finales" rows="3"
                        placeholder="Cualquier observación adicional sobre el caso, evolución, pronóstico, etc..."><?= $egreso_existente['observaciones_finales'] ?? '' ?></textarea>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5><i class="fas fa-user-md me-2"></i>Médico Responsable del Egreso</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-user-doctor text-primary me-2"></i>Médico de Egreso: *
                        </label>
                        <select class="form-control medico-selector" name="id_medico_egreso" required>
                            <option value="">Seleccionar médico...</option>
                            <?php foreach ($medicos_egreso as $medico): ?>
                            <option value="<?= $medico['id'] ?>" data-especialidad="<?= $medico['especialidad'] ?>"
                                <?= ( ($egreso_existente['id_medico_egreso'] ?? '') == $medico['id'] ) ? 'selected' : ( (!isset($egreso_existente['id_medico_egreso']) && $_SESSION['usuario_id'] == $medico['id']) ? 'selected' : '' ) ?>>
                                Dr. <?= $medico['nombre_completo'] ?> - (<?= $medico['especialidad'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="medico-info" id="medicoEgresoInfo">
                            <?php 
                                // Mostrar especialidad si ya hay un médico seleccionado al cargar la página
                                if (isset($egreso_existente['id_medico_egreso']) && $egreso_existente['id_medico_egreso']) {
                                    $selected_medico = array_filter($medicos_egreso, fn($m) => $m['id'] == $egreso_existente['id_medico_egreso']);
                                    if (!empty($selected_medico)) {
                                        echo "Especialidad: " . current($selected_medico)['especialidad'];
                                    }
                                } else if (isset($_SESSION['usuario_id'])) {
                                    $selected_medico = array_filter($medicos_egreso, fn($m) => $m['id'] == $_SESSION['usuario_id']);
                                    if (!empty($selected_medico)) {
                                        echo "Especialidad: " . current($selected_medico)['especialidad'];
                                    }
                                }
                            ?>
                        </small>
                    </div>
                </div>
            </div>

            <div class="text-center mb-4">
                <button type="button" class="btn btn-secondary me-2" onclick="history.back()">
                    <i class="fas fa-arrow-left me-1"></i>Volver
                </button>

                <button type="submit" name="accion" value="guardar_borrador" class="btn btn-warning me-2">
                    <i class="fas fa-save me-1"></i>Guardar Borrador
                </button>

                <button type="submit" name="accion" value="egreso_definitivo" class="btn btn-success"
                    onclick="return confirmarEgreso()">
                    <i class="fas fa-check me-1"></i>Procesar Egreso Definitivo
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Manejar cambio de Médico de Egreso
        const selectMedicoEgreso = document.querySelector('[name="id_medico_egreso"]');
        const medicoEgresoInfo = document.getElementById('medicoEgresoInfo');

        // Función para actualizar la información de especialidad
        function updateMedicoEgresoInfo() {
            const selectedOption = selectMedicoEgreso.options[selectMedicoEgreso.selectedIndex];
            const especialidad = selectedOption.getAttribute('data-especialidad');
            if (especialidad && selectMedicoEgreso.value) {
                medicoEgresoInfo.textContent = `Especialidad: ${especialidad}`;
                medicoEgresoInfo.style.display = 'block';
            } else {
                medicoEgresoInfo.style.display = 'none';
            }
        }

        // Llamar al cargar para inicializar si hay un valor pre-seleccionado
        updateMedicoEgresoInfo();

        // Añadir listener para cambios
        selectMedicoEgreso.addEventListener('change', updateMedicoEgresoInfo);

        // Auto-resize textarea
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });
            // Ajustar altura inicial al cargar la página
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    });

    function confirmarEgreso() {
        // La validación de campos requeridos se hace aquí antes de mostrar el Swal final
        const requeridos = [
            'fecha_egreso',
            'tipo_egreso',
            'estado_egreso',
            'diagnostico_principal_egreso',
            'id_medico_egreso' // Ahora validamos el selector
        ];

        let errores = [];
        requeridos.forEach(campo => {
            const elemento = document.querySelector(`[name="${campo}"]`);
            if (elemento && !elemento.value.trim()) {
                // Traducir nombre de campo para el mensaje
                let nombreCampo = campo.replace(/_/g, ' ').replace('id medico', 'Médico').replace(
                    'diagnostico principal', 'Diagnóstico Principal').toUpperCase();
                errores.push(nombreCampo);
            }
        });

        if (errores.length > 0) {
            Swal.fire({
                title: 'Campos requeridos',
                html: `Complete los siguientes campos obligatorios:<br><br><strong>${errores.join('<br>')}</strong>`,
                icon: 'error'
            });
            return false; // Previene el envío del formulario
        }

        // Si todos los campos requeridos están llenos, procede con la confirmación de SweetAlert
        return Swal.fire({
            title: '¿Procesar egreso definitivo?',
            html: `
                <div class="text-start">
                    <p class="mb-3">Esta acción:</p>
                    <ul class="text-muted">
                        <li>Cerrará definitivamente el caso</li>
                        <li>No se podrá modificar posteriormente</li>
                        <li>Se generará el reporte final</li>
                    </ul>
                    <div class="alert alert-warning">
                        <strong>¡Atención!</strong> Verifique que todos los datos estén completos y correctos.
                    </div>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '✅ Sí, procesar egreso',
            cancelButtonText: '❌ Cancelar',
            customClass: {
                popup: 'swal-wide'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                Swal.fire({
                    title: 'Procesando egreso...',
                    html: 'Por favor espere mientras se procesa el egreso del paciente',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                return true; // Permite el envío del formulario
            }
            return false; // Previene el envío del formulario
        });
    }

    // Auto-guardar borrador cada 2 minutos
    setInterval(function() {
        const form = document.querySelector('form');
        const formData = new FormData(form);
        formData.append('accion', 'guardar_borrador');

        // Excluir el botón de submit para evitar validaciones
        const submitButton = document.activeElement;
        if (submitButton && (submitButton.tagName === 'BUTTON' || submitButton.type === 'submit')) {
            formData.delete(submitButton.name);
        }

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        }).then(response => {
            if (response.ok) {
                console.log('Borrador guardado automáticamente');
            } else {
                console.log('Error de respuesta al guardar borrador automáticamente');
            }
        }).catch(error => {
            console.error('Error guardando borrador:', error);
        });
    }, 120000); // 2 minutos

    // Shortcuts del teclado (si los tenías, asegúrate de que no entren en conflicto)
    document.addEventListener('keydown', function(e) {
        // Ctrl + S para guardar borrador
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            document.querySelector('button[name="accion"][value="guardar_borrador"]').click();
        }
    });

    // Agregar estilos CSS adicionales
    const style = document.createElement('style');
    style.textContent = `
        .swal-wide {
            width: 600px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
        .timeline-item:last-child {
            border-left: 3px solid transparent; /* Para que la última línea no se vea extendida */
        }
    `;
    document.head.appendChild(style);
    </script>

</body>

</html>