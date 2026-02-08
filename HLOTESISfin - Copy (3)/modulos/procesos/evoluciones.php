<?php
// modulos/procesos/evoluciones.php
require_once '../../config/forms/database.php';
require_once '../../config/forms/functions.php';

verificarSesion();

// Obtener información del caso
$id_caso = $_GET['caso'] ?? 0;
if (!$id_caso) {
    die('Caso no especificado');
}

// Obtener datos del caso
$stmt = $pdo->prepare("
    SELECT cc.*, p.numero_historia, CONCAT(p.nombres, ' ', p.apellidos) as nombre_paciente,
           p.cedula, TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) as edad,
           di.fecha_ingreso, DATEDIFF(CURDATE(), di.fecha_ingreso) as dias_estancia,
           di.motivo_consulta, di.diagnostico_ingreso
    FROM casos_clinicos cc
    INNER JOIN pacientes p ON cc.id_paciente = p.id_paciente
    INNER JOIN datos_ingreso di ON cc.id_caso = di.id_caso
    WHERE cc.id_caso = ?
");
$stmt->execute([$id_caso]);
$caso = $stmt->fetch();

if (!$caso) {
    die('Caso no encontrado');
}

// 🆕 OBTENER MÉDICOS DISPONIBLES
$stmt = $pdo->prepare("
    SELECT id, CONCAT(nombres, ' ', apellidos) as nombre_completo, especialidad
    FROM personal 
    WHERE rol = 'Medicina' AND activo = 1
    ORDER BY nombres, apellidos
");
$stmt->execute();
$medicos = $stmt->fetchAll();

// Obtener últimos signos vitales
$stmt = $pdo->prepare("
    SELECT temperatura, pulso, presion_sistolica, presion_diastolica, 
           saturacion_oxigeno, DATE_FORMAT(fecha_registro, '%d/%m %H:%i') as fecha
    FROM signos_vitales 
    WHERE id_caso = ? 
    ORDER BY fecha_registro DESC 
    LIMIT 1
");
$stmt->execute([$id_caso]);
$ultimos_signos = $stmt->fetch();

// Obtener medicamentos activos
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM prescripciones 
    WHERE id_caso = ? AND estado = 'activa'
");
$stmt->execute([$id_caso]);
$medicamentos_activos = $stmt->fetchColumn();

// Obtener órdenes pendientes
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM ordenes_medicas 
    WHERE id_caso = ? AND estado = 'pendiente'
");
$stmt->execute([$id_caso]);
$ordenes_pendientes = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evoluciones - Caso <?= $caso['numero_caso'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    .patient-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 15px;
        color: white;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    .stats-card {
        transition: transform 0.2s;
        border: none;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    }

    .evolution-card {
        border-left: 4px solid #667eea;
        margin-bottom: 15px;
        transition: all 0.3s ease;
    }

    .evolution-card:hover {
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transform: translateX(5px);
    }

    .soap-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 10px;
        margin: 5px 0;
    }

    .vital-signs-widget {
        background: linear-gradient(45deg, #667eea, #764ba2);
        color: white;
        border-radius: 10px;
        padding: 15px;
    }

    .quick-actions {
        position: sticky;
        top: 20px;
    }

    .form-evolution {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    /* 🆕 ESTILOS PARA SELECTOR DE MÉDICO */
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

<body class="bg-light">
    <div class="container-fluid mt-4">
        <!-- Header del paciente -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3><i class="fas fa-notes-medical me-2"></i>Evoluciones - <?= $caso['nombre_paciente'] ?></h3>
            <a href="dashboard_procesos.php?caso=<?= $id_caso ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>

        <!-- Header del paciente -->
        <div class="patient-header mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4><i class="fas fa-user-injured me-2"></i><?= $caso['nombre_paciente'] ?></h4>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1">
                                    <strong>Caso:</strong> <?= $caso['numero_caso'] ?> |
                                    <strong>Historia:</strong> <?= $caso['numero_historia'] ?>
                                </p>
                                <p class="mb-1">
                                    <strong>Cédula:</strong> <?= $caso['cedula'] ?> |
                                    <strong>Edad:</strong> <?= $caso['edad'] ?> años
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1">
                                    <strong>Servicio:</strong> <?= $caso['servicio_actual'] ?> |
                                    <strong>Días estancia:</strong> <?= $caso['dias_estancia'] ?>
                                </p>
                                <p class="mb-0">
                                    <strong>Ingreso:</strong> <?= formatearFecha($caso['fecha_ingreso']) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <span
                            class="badge bg-<?= $caso['estado_caso'] === 'activo' ? 'success' : 'warning' ?> fs-6 mb-2">
                            <?= ucfirst($caso['estado_caso']) ?>
                        </span>
                        <br>
                        <div class="btn-group">
                            <button class="btn btn-light btn-sm"
                                onclick="location.href='signos_vitales.php?caso=<?= $id_caso ?>'">
                                <i class="fas fa-heartbeat"></i> Signos Vitales
                            </button>
                            <button class="btn btn-light btn-sm"
                                onclick="location.href='ordenes.php?caso=<?= $id_caso ?>'">
                                <i class="fas fa-prescription"></i> Órdenes
                            </button>
                            <button class="btn btn-warning btn-sm"
                                onclick="location.href='../egreso/formulario_egreso.php?caso=<?= $id_caso ?>'">
                                <i class="fas fa-sign-out-alt"></i> Egreso
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas rápidas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-pills fa-2x mb-2"></i>
                        <h4><?= $medicamentos_activos ?></h4>
                        <small>Medicamentos Activos</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-clipboard-list fa-2x mb-2"></i>
                        <h4><?= $ordenes_pendientes ?></h4>
                        <small>Órdenes Pendientes</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <?php if ($ultimos_signos): ?>
                <div class="vital-signs-widget">
                    <h6><i class="fas fa-heartbeat me-2"></i>Últimos Signos Vitales (<?= $ultimos_signos['fecha'] ?>)
                    </h6>
                    <div class="row text-center">
                        <div class="col">
                            <small>Temp</small><br>
                            <strong><?= $ultimos_signos['temperatura'] ?? '-' ?>°C</strong>
                        </div>
                        <div class="col">
                            <small>Pulso</small><br>
                            <strong><?= $ultimos_signos['pulso'] ?? '-' ?> lpm</strong>
                        </div>
                        <div class="col">
                            <small>PA</small><br>
                            <strong><?= ($ultimos_signos['presion_sistolica'] && $ultimos_signos['presion_diastolica']) ?
                                    $ultimos_signos['presion_sistolica'] . '/' . $ultimos_signos['presion_diastolica'] : '-' ?></strong>
                        </div>
                        <div class="col">
                            <small>SatO₂</small><br>
                            <strong><?= $ultimos_signos['saturacion_oxigeno'] ?? '-' ?>%</strong>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="card stats-card bg-secondary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-heartbeat fa-2x mb-2"></i>
                        <h6>Sin signos vitales registrados</h6>
                        <a href="signos_vitales.php?caso=<?= $id_caso ?>" class="btn btn-light btn-sm">
                            Registrar Signos
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <!-- Lista de evoluciones -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-notes-medical me-2"></i>Evoluciones Médicas</h5>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary active"
                                onclick="filtrarEvoluciones('todas')">Todas</button>
                            <button class="btn btn-outline-info" onclick="filtrarEvoluciones('diaria')">Diarias</button>
                            <button class="btn btn-outline-warning"
                                onclick="filtrarEvoluciones('urgente')">Urgentes</button>
                            <button class="btn btn-outline-success"
                                onclick="filtrarEvoluciones('quirurgica')">Quirúrgicas</button>
                        </div>
                    </div>
                    <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                        <div id="listaEvoluciones">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información del caso -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6><i class="fas fa-info-circle me-2"></i>Información del Caso</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Motivo de Consulta:</strong><br>
                                    <small class="text-muted"><?= $caso['motivo_consulta'] ?></small>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Diagnóstico de Ingreso:</strong><br>
                                    <small
                                        class="text-muted"><?= $caso['diagnostico_ingreso'] ?? 'No especificado' ?></small>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulario nueva evolución - CORREGIDO -->
            <div class="col-md-4">
                <div class="quick-actions">
                    <div class="card form-evolution">
                        <div class="card-header bg-primary text-white">
                            <h5><i class="fas fa-plus me-2"></i>Nueva Evolución</h5>
                        </div>
                        <div class="card-body">
                            <form id="formEvolucion">
                                <input type="hidden" name="id_caso" value="<?= $id_caso ?>">

                                <!-- 🆕 SELECTOR DE MÉDICO RESPONSABLE -->
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-user-md text-primary me-2"></i>Médico Responsable: *
                                    </label>
                                    <select class="form-control medico-selector" name="id_medico" required>
                                        <option value="">Seleccionar médico...</option>
                                        <?php foreach ($medicos as $medico): ?>
                                        <option value="<?= $medico['id'] ?>"
                                            data-especialidad="<?= $medico['especialidad'] ?>">
                                            Dr. <?= $medico['nombre_completo'] ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="medico-info" id="medicoInfo"></small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Tipo de Evolución: *</label>
                                    <select class="form-control" name="tipo_evolucion" required>
                                        <option value="diaria">Diaria</option>
                                        <option value="interconsulta">Interconsulta</option>
                                        <option value="especializada">Especializada</option>
                                        <option value="urgente">Urgente</option>
                                        <option value="quirurgica">Quirúrgica</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Subjetivo (S):</label>
                                    <textarea class="form-control" name="subjetivo" rows="3"
                                        placeholder="Lo que refiere el paciente..."></textarea>
                                    <small class="text-muted">Síntomas, quejas, cómo se siente el paciente</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Objetivo (O):</label>
                                    <textarea class="form-control" name="objetivo" rows="3"
                                        placeholder="Hallazgos del examen..."></textarea>
                                    <small class="text-muted">Signos vitales, examen físico, laboratorios</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Evaluación (A):</label>
                                    <textarea class="form-control" name="evaluacion" rows="3"
                                        placeholder="Análisis e interpretación..."></textarea>
                                    <small class="text-muted">Diagnóstico, impresión clínica, análisis</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Plan (P): *</label>
                                    <textarea class="form-control" name="plan" rows="4"
                                        placeholder="Plan de tratamiento..." required></textarea>
                                    <small class="text-muted">Tratamiento, órdenes, seguimiento</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Estado del paciente:</label>
                                    <select class="form-control" name="estado_paciente">
                                        <option value="estable">Estable</option>
                                        <option value="mejorado">Mejorado</option>
                                        <option value="sin_cambios">Sin cambios</option>
                                        <option value="empeorado">Empeorado</option>
                                        <option value="critico">Crítico</option>
                                    </select>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Guardar Evolución
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary"
                                        onclick="limpiarFormulario()">
                                        <i class="fas fa-eraser me-2"></i>Limpiar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Accesos rápidos -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6><i class="fas fa-bolt me-2"></i>Accesos Rápidos</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="laboratorios.php?caso=<?= $id_caso ?>" class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-vial me-2"></i>Laboratorios
                                </a>
                                <a href="imagenes.php?caso=<?= $id_caso ?>" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-x-ray me-2"></i>Imágenes
                                </a>
                                <a href="procedimientos.php?caso=<?= $id_caso ?>" class="btn btn-outline-dark btn-sm">
                                    <i class="fas fa-tools me-2"></i>Procedimientos
                                </a>
                                <a href="interconsultas.php?caso=<?= $id_caso ?>" class="btn btn-outline-danger btn-sm">
                                    <i class="fas fa-user-md me-2"></i>Interconsultas
                                </a>
                                <a href="notas_enfermeria.php?caso=<?= $id_caso ?>"
                                    class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-user-nurse me-2"></i>Enfermería
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let filtroActual = 'todas';

    // Cargar evoluciones al iniciar
    document.addEventListener('DOMContentLoaded', function() {
        cargarEvoluciones();

        // 🆕 MANEJAR CAMBIO DE MÉDICO
        const selectMedico = document.querySelector('[name="id_medico"]');
        selectMedico.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const especialidad = selectedOption.getAttribute('data-especialidad');
            const medicoInfo = document.getElementById('medicoInfo');

            if (especialidad && this.value) {
                medicoInfo.textContent = `Especialidad: ${especialidad}`;
                medicoInfo.style.display = 'block';
            } else {
                medicoInfo.style.display = 'none';
            }
        });
    });

    // Cargar lista de evoluciones
    function cargarEvoluciones() {
        const url = filtroActual === 'todas' ?
            'listar_evoluciones.php?caso=<?= $id_caso ?>' :
            `listar_evoluciones.php?caso=<?= $id_caso ?>&tipo=${filtroActual}`;

        console.log('Cargando evoluciones desde:', url); // 🔧 DEBUG

        fetch(url)
            .then(response => {
                console.log('Response status:', response.status); // 🔧 DEBUG
                return response.json();
            })
            .then(data => {
                console.log('Datos recibidos:', data); // 🔧 DEBUG
                if (data.status === 'ok') {
                    mostrarEvoluciones(data.data);
                } else {
                    document.getElementById('listaEvoluciones').innerHTML =
                        `<div class="alert alert-warning">Error: ${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('listaEvoluciones').innerHTML =
                    `<div class="alert alert-danger">Error de conexión: ${error.message}</div>`;
            });
    }

    // Mostrar evoluciones en el DOM
    function mostrarEvoluciones(evoluciones) {
        const container = document.getElementById('listaEvoluciones');

        console.log('Mostrando evoluciones:', evoluciones); // 🔧 DEBUG

        if (!evoluciones || evoluciones.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-notes-medical fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No hay evoluciones registradas</p>
                    <p class="small text-muted">Complete el formulario de la derecha para agregar la primera evolución</p>
                </div>
            `;
            return;
        }

        let html = '';
        evoluciones.forEach((evol, index) => {
            const tipoColors = {
                'diaria': 'primary',
                'interconsulta': 'info',
                'especializada': 'success',
                'urgente': 'warning',
                'quirurgica': 'danger'
            };

            const estadoColors = {
                'estable': 'success',
                'mejorado': 'success',
                'sin_cambios': 'warning',
                'empeorado': 'danger',
                'critico': 'danger'
            };

            html += `
                <div class="evolution-card card mb-3" data-index="${index}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h6 class="mb-1">
                                    <span class="badge bg-${tipoColors[evol.tipo_evolucion] || 'secondary'}">${evol.tipo_evolucion}</span>
                                    <span class="ms-2">${evol.fecha_evolucion}</span>
                                </h6>
                                <small class="text-muted">
                                    <i class="fas fa-user-md me-1"></i>Dr. ${evol.medico}
                                    ${evol.especialidad ? `<span class="badge bg-light text-dark ms-2">${evol.especialidad}</span>` : ''}
                                    ${evol.validado == 1 ? '<i class="fas fa-check-circle text-success ms-2" title="Validado"></i>' : ''}
                                </small>
                            </div>
                            <span class="badge bg-${estadoColors[evol.estado_paciente] || 'secondary'}">
                                ${evol.estado_paciente}
                            </span>
                        </div>
                        
                        ${evol.subjetivo ? `
                            <div class="soap-section">
                                <strong class="text-primary">S (Subjetivo):</strong>
                                <p class="mb-0 mt-1">${evol.subjetivo}</p>
                            </div>
                        ` : ''}
                        
                        ${evol.objetivo ? `
                            <div class="soap-section">
                                <strong class="text-info">O (Objetivo):</strong>
                                <p class="mb-0 mt-1">${evol.objetivo}</p>
                            </div>
                        ` : ''}
                        
                        ${evol.evaluacion ? `
                            <div class="soap-section">
                                <strong class="text-success">A (Evaluación):</strong>
                                <p class="mb-0 mt-1">${evol.evaluacion}</p>
                            </div>
                        ` : ''}
                        
                        ${evol.plan ? `
                            <div class="soap-section">
                                <strong class="text-warning">P (Plan):</strong>
                                <p class="mb-0 mt-1">${evol.plan}</p>
                            </div>
                        ` : ''}
                        
                        <div class="text-end mt-2">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>Registrado: ${evol.fecha_evolucion}
                            </small>
                        </div>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    // Filtrar evoluciones
    function filtrarEvoluciones(tipo) {
        filtroActual = tipo;
        cargarEvoluciones();

        // Actualizar botones activos
        document.querySelectorAll('.btn-group button').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');
    }

    // Limpiar formulario
    function limpiarFormulario() {
        document.getElementById('formEvolucion').reset();
        document.querySelector('[name="tipo_evolucion"]').value = 'diaria';
        document.querySelector('[name="estado_paciente"]').value = 'estable';
        document.getElementById('medicoInfo').style.display = 'none';
    }

    // Enviar nueva evolución - CORREGIDO
    document.getElementById('formEvolucion').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        // 🔧 DEBUG: Verificar datos antes de enviar
        console.log('Datos del formulario:');
        for (let [key, value] of formData.entries()) {
            console.log(key + ': ' + value);
        }

        // 🆕 VALIDAR MÉDICO RESPONSABLE
        const idMedico = formData.get('id_medico');
        if (!idMedico) {
            Swal.fire('Error', 'Debe seleccionar el médico responsable', 'error');
            return;
        }

        // Validar que al menos el plan esté lleno
        const plan = formData.get('plan').trim();
        if (!plan) {
            Swal.fire('Error', 'El plan de tratamiento es obligatorio', 'error');
            return;
        }

        // Mostrar loading
        Swal.fire({
            title: 'Guardando evolución...',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('guardar_evolucion.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();

                if (data.status === 'ok') {
                    Swal.fire({
                        title: 'Éxito',
                        text: 'Evolución guardada correctamente',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });

                    this.reset();
                    document.querySelector('[name="tipo_evolucion"]').value = 'diaria';
                    document.querySelector('[name="estado_paciente"]').value = 'estable';
                    document.getElementById('medicoInfo').style.display = 'none';
                    cargarEvoluciones();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Error:', error);
                Swal.fire('Error', 'Error de conexión al guardar la evolución', 'error');
            });
    });

    // Auto-resize textareas
    document.addEventListener('DOMContentLoaded', function() {
        const textareas = document.querySelectorAll('textarea');
        textareas.forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });
        });
    });

    // Shortcuts del teclado
    document.addEventListener('keydown', function(e) {
        // Ctrl + Enter para guardar evolución
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('formEvolucion').dispatchEvent(new Event('submit'));
        }

        // Esc para limpiar formulario
        if (e.key === 'Escape') {
            limpiarFormulario();
        }
    });
    </script>
</body>

</html>