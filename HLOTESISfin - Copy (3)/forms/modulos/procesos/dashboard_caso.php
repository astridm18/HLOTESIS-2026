<?php
// modulos/procesos/dashboard_caso.php
require_once '../../config/database.php';
require_once '../../config/functions.php';

verificarSesion();

$id_caso = $_GET['caso'] ?? 0;
if (!$id_caso) {
    header('Location: dashboard_procesos.php');
    exit();
}

// Obtener información completa del caso
try {
    $stmt = $pdo->prepare("
        SELECT 
            cc.*,
            p.numero_historia,
            p.nombres,
            p.apellidos,
            p.cedula,
            p.sexo,
            p.fecha_nacimiento,
            p.telefono,
            p.direccion,
            p.tipo_sangre,
            p.alergias_conocidas,
            di.fecha_ingreso,
            di.hora_ingreso,
            di.motivo_consulta,
            di.enfermedad_actual,
            di.via_ingreso,
            CONCAT(mp.nombres, ' ', mp.apellidos) as medico_responsable,
            mp.especialidad as especialidad_responsable,
            DATEDIFF(CURDATE(), di.fecha_ingreso) as dias_estancia
        FROM casos_clinicos cc
        INNER JOIN pacientes p ON cc.id_paciente = p.id_paciente
        INNER JOIN datos_ingreso di ON cc.id_caso = di.id_caso
        LEFT JOIN personal mp ON cc.id_medico_responsable = mp.id
        WHERE cc.id_caso = ?
    ");

    $stmt->execute([$id_caso]);
    $caso = $stmt->fetch();

    if (!$caso) {
        die('Caso no encontrado');
    }

} catch (Exception $e) {
    die('Error al cargar el caso: ' . $e->getMessage());
}

// Calcular edad del paciente
$edad = calcularEdad($caso['fecha_nacimiento']);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caso <?= $caso['numero_caso'] ?> - <?= $caso['nombres'] ?> <?= $caso['apellidos'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    .patient-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .status-badge {
        font-size: 0.9rem;
        padding: 8px 15px;
        border-radius: 20px;
    }

    .quick-action-card {
        border: none;
        border-radius: 15px;
        transition: all 0.3s;
        cursor: pointer;
        height: 120px;
    }

    .quick-action-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .metric-card {
        background: white;
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        border-left: 4px solid #007bff;
    }

    .nav-pills .nav-link {
        border-radius: 25px;
        margin: 0 5px;
        transition: all 0.3s;
    }

    .nav-pills .nav-link.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .timeline-item {
        border-left: 3px solid #e9ecef;
        padding-left: 20px;
        margin-bottom: 20px;
        position: relative;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: -8px;
        top: 0;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #007bff;
    }

    .chart-container {
        position: relative;
        height: 300px;
        margin: 20px 0;
    }

    .alert-item {
        border-left: 4px solid #dc3545;
        background: #fff5f5;
    }

    .btn-floating {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        font-size: 24px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        z-index: 1000;
    }
    </style>
</head>

<body class="bg-light">

    <!-- Header del Paciente -->
    <div class="container-fluid mt-3">
        <div class="patient-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <div class="me-4">
                            <i class="fas fa-user-circle fa-4x"></i>
                        </div>
                        <div>
                            <h2 class="mb-1"><?= $caso['nombres'] ?> <?= $caso['apellidos'] ?></h2>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><i class="fas fa-id-card me-2"></i>Cédula: <?= $caso['cedula'] ?>
                                    </p>
                                    <p class="mb-1"><i class="fas fa-file-medical me-2"></i>Historia:
                                        <?= $caso['numero_historia'] ?></p>
                                    <p class="mb-0"><i class="fas fa-birthday-cake me-2"></i>Edad: <?= $edad ?> años
                                        (<?= $caso['sexo'] === 'M' ? 'Masculino' : 'Femenino' ?>)</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><i
                                            class="fas fa-phone me-2"></i><?= $caso['telefono'] ?: 'No registrado' ?>
                                    </p>
                                    <p class="mb-1"><i class="fas fa-tint me-2"></i>Tipo sangre:
                                        <?= $caso['tipo_sangre'] ?: 'No registrado' ?></p>
                                    <p class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Alergias:
                                        <?= $caso['alergias_conocidas'] ?: 'Ninguna conocida' ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="mb-2">
                        <span class="status-badge bg-<?= $caso['estado_caso'] === 'activo' ? 'success' : 'warning' ?>">
                            <i class="fas fa-circle me-1"></i><?= ucfirst($caso['estado_caso']) ?>
                        </span>
                    </div>
                    <h4 class="mb-1">Caso <?= $caso['numero_caso'] ?></h4>
                    <p class="mb-1">Días de estancia: <strong><?= $caso['dias_estancia'] ?></strong></p>
                    <p class="mb-0">Servicio: <strong><?= $caso['servicio_actual'] ?></strong></p>
                </div>
            </div>
        </div>

        <!-- Métricas Rápidas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted">Última Evolución</h6>
                            <h4 id="ultima-evolucion">-</h4>
                        </div>
                        <i class="fas fa-notes-medical fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted">Signos Vitales</h6>
                            <h4 id="ultimos-signos">-</h4>
                        </div>
                        <i class="fas fa-heartbeat fa-2x text-success"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted">Órdenes Activas</h6>
                            <h4 id="ordenes-activas">-</h4>
                        </div>
                        <i class="fas fa-prescription fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted">Pendientes</h6>
                            <h4 id="examenes-pendientes">-</h4>
                        </div>
                        <i class="fas fa-clock fa-2x text-danger"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acciones Rápidas -->
        <div class="row mb-4">
            <div class="col-12">
                <h5><i class="fas fa-bolt me-2"></i>Acciones Rápidas</h5>
            </div>
            <div class="col-md-2">
                <div class="card quick-action-card text-center" onclick="nuevaEvolucion()">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <i class="fas fa-plus-circle fa-2x text-primary mb-2"></i>
                        <span>Nueva Evolución</span>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card quick-action-card text-center" onclick="registrarSignos()">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <i class="fas fa-heartbeat fa-2x text-success mb-2"></i>
                        <span>Signos Vitales</span>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card quick-action-card text-center" onclick="solicitarLaboratorio()">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <i class="fas fa-vial fa-2x text-info mb-2"></i>
                        <span>Laboratorio</span>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card quick-action-card text-center" onclick="solicitarImagen()">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <i class="fas fa-x-ray fa-2x text-secondary mb-2"></i>
                        <span>Imagen</span>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card quick-action-card text-center" onclick="interconsulta()">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <i class="fas fa-user-md fa-2x text-warning mb-2"></i>
                        <span>Interconsulta</span>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card quick-action-card text-center" onclick="procesarEgreso()">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <i class="fas fa-sign-out-alt fa-2x text-danger mb-2"></i>
                        <span>Egreso</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navegación por Pestañas -->
        <ul class="nav nav-pills mb-4" id="casoTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="evoluciones-tab" data-bs-toggle="pill" data-bs-target="#evoluciones"
                    type="button">
                    <i class="fas fa-notes-medical me-2"></i>Evoluciones
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="signos-tab" data-bs-toggle="pill" data-bs-target="#signos" type="button">
                    <i class="fas fa-heartbeat me-2"></i>Signos Vitales
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="laboratorios-tab" data-bs-toggle="pill" data-bs-target="#laboratorios"
                    type="button">
                    <i class="fas fa-vial me-2"></i>Laboratorios
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="imagenes-tab" data-bs-toggle="pill" data-bs-target="#imagenes"
                    type="button">
                    <i class="fas fa-x-ray me-2"></i>Imágenes
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="ordenes-tab" data-bs-toggle="pill" data-bs-target="#ordenes" type="button">
                    <i class="fas fa-prescription me-2"></i>Órdenes
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="antecedentes-tab" data-bs-toggle="pill" data-bs-target="#antecedentes"
                    type="button">
                    <i class="fas fa-history me-2"></i>Antecedentes
                </button>
            </li>
        </ul>

        <!-- Contenido de las Pestañas -->
        <div class="tab-content" id="casoTabsContent">

            <!-- Evoluciones -->
            <div class="tab-pane fade show active" id="evoluciones" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-notes-medical me-2"></i>Evoluciones del Paciente</h5>
                        <button class="btn btn-primary" onclick="nuevaEvolucion()">
                            <i class="fas fa-plus me-2"></i>Nueva Evolución
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="lista-evoluciones">
                            <div class="text-center">
                                <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                <p class="text-muted mt-2">Cargando evoluciones...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Signos Vitales -->
            <div class="tab-pane fade" id="signos" role="tabpanel">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-line me-2"></i>Tendencias de Signos Vitales</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="signosChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between">
                                <h5><i class="fas fa-heartbeat me-2"></i>Registros Recientes</h5>
                                <button class="btn btn-sm btn-success" onclick="registrarSignos()">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="lista-signos-recientes">
                                    <div class="text-center">
                                        <i class="fas fa-spinner fa-spin"></i>
                                        <p class="text-muted mt-2">Cargando...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Laboratorios -->
            <div class="tab-pane fade" id="laboratorios" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-vial me-2"></i>Exámenes de Laboratorio</h5>
                        <button class="btn btn-primary" onclick="solicitarLaboratorio()">
                            <i class="fas fa-plus me-2"></i>Solicitar Examen
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="lista-laboratorios">
                            <div class="text-center">
                                <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                <p class="text-muted mt-2">Cargando exámenes...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Imágenes -->
            <div class="tab-pane fade" id="imagenes" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-x-ray me-2"></i>Estudios de Imagen</h5>
                        <button class="btn btn-primary" onclick="solicitarImagen()">
                            <i class="fas fa-plus me-2"></i>Solicitar Estudio
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="lista-imagenes">
                            <div class="text-center">
                                <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                <p class="text-muted mt-2">Cargando estudios...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Órdenes Médicas -->
            <div class="tab-pane fade" id="ordenes" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-prescription me-2"></i>Órdenes Médicas</h5>
                    </div>
                    <div class="card-body">
                        <div id="lista-ordenes">
                            <div class="text-center">
                                <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                <p class="text-muted mt-2">Cargando órdenes...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Antecedentes -->
            <div class="tab-pane fade" id="antecedentes" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-history me-2"></i>Antecedentes Médicos</h5>
                    </div>
                    <div class="card-body">
                        <div id="lista-antecedentes-caso">
                            <div class="text-center">
                                <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                <p class="text-muted mt-2">Cargando antecedentes...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botón Flotante de Regreso -->
    <button class="btn btn-floating" onclick="window.location.href='dashboard_procesos.php'"
        title="Volver al Dashboard">
        <i class="fas fa-arrow-left"></i>
    </button>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    const ID_CASO = <?= $id_caso ?>;
    let signosChart;

    // Inicializar al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        cargarMetricasRapidas();
        cargarEvoluciones();
        inicializarGraficoSignos();

        // Configurar eventos de pestañas
        document.querySelectorAll('[data-bs-toggle="pill"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', function(event) {
                const targetId = event.target.getAttribute('data-bs-target');
                switch (targetId) {
                    case '#signos':
                        cargarSignosVitales();
                        break;
                    case '#laboratorios':
                        cargarLaboratorios();
                        break;
                    case '#imagenes':
                        cargarImagenes();
                        break;
                    case '#ordenes':
                        cargarOrdenes();
                        break;
                    case '#antecedentes':
                        cargarAntecedentes();
                        break;
                }
            });
        });
    });

    // ===== FUNCIONES DE CARGA DE DATOS =====

    function cargarMetricasRapidas() {
        // Última evolución
        fetch(`obtener_metricas_caso.php?caso=${ID_CASO}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ok') {
                    document.getElementById('ultima-evolucion').textContent = data.data.ultima_evolucion ||
                        'Sin registro';
                    document.getElementById('ultimos-signos').textContent = data.data.ultimos_signos ||
                        'Sin registro';
                    document.getElementById('ordenes-activas').textContent = data.data.ordenes_activas || '0';
                    document.getElementById('examenes-pendientes').textContent = data.data.examenes_pendientes ||
                        '0';
                }
            })
            .catch(error => console.error('Error cargando métricas:', error));
    }

    function cargarEvoluciones() {
        fetch(`listar_evoluciones.php?caso=${ID_CASO}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ok') {
                    mostrarEvoluciones(data.data);
                } else {
                    document.getElementById('lista-evoluciones').innerHTML =
                        '<div class="alert alert-info">No hay evoluciones registradas</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('lista-evoluciones').innerHTML =
                    '<div class="alert alert-danger">Error al cargar las evoluciones</div>';
            });
    }

    function mostrarEvoluciones(evoluciones) {
        const container = document.getElementById('lista-evoluciones');

        if (evoluciones.length === 0) {
            container.innerHTML = '<div class="alert alert-info">No hay evoluciones registradas</div>';
            return;
        }

        let html = '';
        evoluciones.forEach(evolucion => {
            const fechaFormateada = new Date(evolucion.fecha_evolucion).toLocaleString('es-ES');
            const tipoClass = {
                'diaria': 'primary',
                'interconsulta': 'warning',
                'especializada': 'info',
                'urgente': 'danger',
                'quirurgica': 'dark'
            };

            html += `
                    <div class="timeline-item">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="mb-1">
                                    <span class="badge bg-${tipoClass[evolucion.tipo_evolucion] || 'secondary'}">${evolucion.tipo_evolucion}</span>
                                    ${fechaFormateada}
                                </h6>
                                <small class="text-muted">Dr. ${evolucion.medico} - ${evolucion.especialidad || 'Medicina General'}</small>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="verEvolucion(${evolucion.id_evolucion})" title="Ver completa">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-warning" onclick="editarEvolucion(${evolucion.id_evolucion})" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Subjetivo:</strong>
                                <p class="text-muted">${evolucion.subjetivo || 'No registrado'}</p>
                            </div>
                            <div class="col-md-6">
                                <strong>Objetivo:</strong>
                                <p class="text-muted">${evolucion.objetivo || 'No registrado'}</p>
                            </div>
                            <div class="col-md-6">
                                <strong>Evaluación:</strong>
                                <p class="text-muted">${evolucion.evaluacion || 'No registrado'}</p>
                            </div>
                            <div class="col-md-6">
                                <strong>Plan:</strong>
                                <p class="text-muted">${evolucion.plan || 'No registrado'}</p>
                            </div>
                        </div>
                        
                        <div class="mt-2">
                            <span class="badge bg-${evolucion.estado_paciente === 'estable' ? 'success' : evolucion.estado_paciente === 'critico' ? 'danger' : 'warning'}">
                                ${evolucion.estado_paciente}
                            </span>
                            ${evolucion.validado ? '<span class="badge bg-success ms-2"><i class="fas fa-check"></i> Validado</span>' : ''}
                        </div>
                    </div>
                `;
        });

        container.innerHTML = html;
    }

    function cargarSignosVitales() {
        fetch(`listar_signos.php?caso=${ID_CASO}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ok') {
                    actualizarGraficoSignos(data.data);
                    mostrarSignosRecientes(data.data.slice(0, 5)); // Últimos 5
                }
            })
            .catch(error => console.error('Error cargando signos:', error));
    }

    function inicializarGraficoSignos() {
        const ctx = document.getElementById('signosChart');
        if (!ctx) return;

        signosChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                        label: 'Temperatura (°C)',
                        data: [],
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        yAxisID: 'y'
                    },
                    {
                        label: 'Pulso (lpm)',
                        data: [],
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        yAxisID: 'y1'
                    },
                    {
                        label: 'Sistólica (mmHg)',
                        data: [],
                        borderColor: 'rgb(255, 205, 86)',
                        backgroundColor: 'rgba(255, 205, 86, 0.2)',
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Fecha/Hora'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Temperatura (°C)'
                        },
                        min: 35,
                        max: 42
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Pulso/PA (lpm/mmHg)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                        min: 40,
                        max: 200
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Evolución de Signos Vitales'
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    }
                }
            }
        });
    }

    function actualizarGraficoSignos(signos) {
        if (!signosChart || signos.length === 0) return;

        // Tomar los últimos 10 registros
        const signosRecientes = signos.slice(0, 10).reverse();

        const labels = signosRecientes.map(s => {
            const fecha = new Date(s.fecha_registro);
            return fecha.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        });

        const temperaturas = signosRecientes.map(s => s.temperatura ? parseFloat(s.temperatura) : null);
        const pulsos = signosRecientes.map(s => s.pulso ? parseInt(s.pulso) : null);
        const sistolicas = signosRecientes.map(s => s.presion_sistolica ? parseInt(s.presion_sistolica) : null);

        signosChart.data.labels = labels;
        signosChart.data.datasets[0].data = temperaturas;
        signosChart.data.datasets[1].data = pulsos;
        signosChart.data.datasets[2].data = sistolicas;

        signosChart.update();
    }

    function mostrarSignosRecientes(signos) {
        const container = document.getElementById('lista-signos-recientes');

        if (signos.length === 0) {
            container.innerHTML = '<div class="alert alert-info">No hay registros recientes</div>';
            return;
        }

        let html = '';
        signos.forEach(signo => {
            const fecha = new Date(signo.fecha_registro).toLocaleString('es-ES');
            const pa = signo.presion_sistolica && signo.presion_diastolica ?
                `${signo.presion_sistolica}/${signo.presion_diastolica}` : '-';

            // Alertas por valores críticos
            let alertClass = '';
            let alertIcon = '';
            if (signo.temperatura && (parseFloat(signo.temperatura) > 38.5 || parseFloat(signo.temperatura) <
                    35.5)) {
                alertClass = 'border-warning';
                alertIcon = '<i class="fas fa-exclamation-triangle text-warning"></i>';
            }
            if (signo.saturacion_oxigeno && parseFloat(signo.saturacion_oxigeno) < 90) {
                alertClass = 'border-danger';
                alertIcon = '<i class="fas fa-exclamation-circle text-danger"></i>';
            }

            html += `
                    <div class="border rounded p-2 mb-2 ${alertClass}">
                        <div class="d-flex justify-content-between align-items-start">
                            <small class="text-muted">${fecha}</small>
                            ${alertIcon}
                        </div>
                        <div class="row text-sm">
                            <div class="col-6">
                                <strong>T°:</strong> ${signo.temperatura || '-'}°C<br>
                                <strong>PA:</strong> ${pa}
                            </div>
                            <div class="col-6">
                                <strong>FC:</strong> ${signo.pulso || '-'} lpm<br>
                                <strong>SatO2:</strong> ${signo.saturacion_oxigeno || '-'}%
                            </div>
                        </div>
                        <small class="badge bg-secondary">${signo.turno}</small>
                    </div>
                `;
        });

        container.innerHTML = html;
    }

    function cargarLaboratorios() {
        fetch(`listar_laboratorios.php?caso=${ID_CASO}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ok') {
                    mostrarLaboratorios(data.data);
                } else {
                    document.getElementById('lista-laboratorios').innerHTML =
                        '<div class="alert alert-info">No hay exámenes de laboratorio</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('lista-laboratorios').innerHTML =
                    '<div class="alert alert-danger">Error al cargar los exámenes</div>';
            });
    }

    function mostrarLaboratorios(laboratorios) {
        const container = document.getElementById('lista-laboratorios');

        if (laboratorios.length === 0) {
            container.innerHTML = '<div class="alert alert-info">No hay exámenes de laboratorio registrados</div>';
            return;
        }

        let html = '<div class="table-responsive"><table class="table table-hover">';
        html += `
                <thead class="table-dark">
                    <tr>
                        <th>Fecha Solicitud</th>
                        <th>Tipo Examen</th>
                        <th>Parámetro</th>
                        <th>Resultado</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
            `;

        laboratorios.forEach(lab => {
            const fechaSolicitud = new Date(lab.fecha_solicitud).toLocaleDateString('es-ES');
            const fechaResultado = lab.fecha_resultado ? new Date(lab.fecha_resultado).toLocaleDateString(
                'es-ES') : '-';

            let estadoClass = 'secondary';
            let estadoText = 'Pendiente';
            if (lab.fecha_resultado) {
                if (lab.estado === 'normal') {
                    estadoClass = 'success';
                    estadoText = 'Normal';
                } else if (lab.estado === 'anormal') {
                    estadoClass = 'warning';
                    estadoText = 'Anormal';
                } else if (lab.estado === 'critico') {
                    estadoClass = 'danger';
                    estadoText = 'Crítico';
                } else {
                    estadoClass = 'info';
                    estadoText = 'Completado';
                }
            }

            const resultado = lab.resultado ||
                (lab.valor_numerico ? `${lab.valor_numerico} ${lab.unidad || ''}` : '-');

            html += `
                    <tr>
                        <td>${fechaSolicitud}</td>
                        <td>${lab.tipo_examen}</td>
                        <td>${lab.parametro}</td>
                        <td>
                            ${resultado}
                            ${lab.valor_referencia ? `<br><small class="text-muted">Ref: ${lab.valor_referencia}</small>` : ''}
                        </td>
                        <td><span class="badge bg-${estadoClass}">${estadoText}</span></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="verLaboratorio(${lab.id_laboratorio})" title="Ver detalle">
                                    <i class="fas fa-eye"></i>
                                </button>
                                ${!lab.fecha_resultado ? `
                                    <button class="btn btn-outline-success" onclick="cargarResultado(${lab.id_laboratorio})" title="Cargar resultado">
                                        <i class="fas fa-upload"></i>
                                    </button>
                                ` : ''}
                            </div>
                        </td>
                    </tr>
                `;
        });

        html += '</tbody></table></div>';
        container.innerHTML = html;
    }

    function cargarImagenes() {
        fetch(`listar_imagenes.php?caso=${ID_CASO}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ok') {
                    mostrarImagenes(data.data);
                } else {
                    document.getElementById('lista-imagenes').innerHTML =
                        '<div class="alert alert-info">No hay estudios de imagen</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('lista-imagenes').innerHTML =
                    '<div class="alert alert-danger">Error al cargar los estudios</div>';
            });
    }

    function mostrarImagenes(imagenes) {
        const container = document.getElementById('lista-imagenes');

        if (imagenes.length === 0) {
            container.innerHTML = '<div class="alert alert-info">No hay estudios de imagen registrados</div>';
            return;
        }

        let html = '';
        imagenes.forEach(imagen => {
            const fechaSolicitud = new Date(imagen.fecha_solicitud).toLocaleDateString('es-ES');
            const fechaEstudio = imagen.fecha_estudio ? new Date(imagen.fecha_estudio).toLocaleDateString(
                'es-ES') : '-';

            let estadoClass = {
                'solicitado': 'secondary',
                'en_proceso': 'warning',
                'informado': 'success',
                'entregado': 'info'
            };

            html += `
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h6 class="card-title">${imagen.tipo_estudio}</h6>
                                    <p class="card-text">
                                        <strong>Región:</strong> ${imagen.region_anatomica || 'No especificada'}<br>
                                        <strong>Técnica:</strong> ${imagen.tecnica || 'No especificada'}<br>
                                        <strong>Contraste:</strong> ${imagen.contraste ? 'Sí' : 'No'}<br>
                                        <strong>Fecha solicitud:</strong> ${fechaSolicitud}<br>
                                        <strong>Fecha estudio:</strong> ${fechaEstudio}
                                    </p>
                                    ${imagen.hallazgos ? `
                                        <p class="card-text">
                                            <strong>Hallazgos:</strong><br>
                                            <small>${imagen.hallazgos}</small>
                                        </p>
                                    ` : ''}
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="badge bg-${estadoClass[imagen.estado] || 'secondary'} mb-2">
                                        ${imagen.estado.replace('_', ' ').toUpperCase()}
                                    </span><br>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" onclick="verImagen(${imagen.id_imagen})" title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        ${imagen.archivo_imagen ? `
                                            <button class="btn btn-outline-success" onclick="descargarImagen('${imagen.archivo_imagen}')" title="Descargar">
                                                <i class="fas fa-download"></i>
                                            </button>
                                        ` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
        });

        container.innerHTML = html;
    }

    function cargarOrdenes() {
        fetch(`listar_ordenes.php?caso=${ID_CASO}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ok') {
                    mostrarOrdenes(data.data);
                } else {
                    document.getElementById('lista-ordenes').innerHTML =
                        '<div class="alert alert-info">No hay órdenes médicas</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('lista-ordenes').innerHTML =
                    '<div class="alert alert-danger">Error al cargar las órdenes</div>';
            });
    }

    function mostrarOrdenes(ordenes) {
        const container = document.getElementById('lista-ordenes');

        if (ordenes.length === 0) {
            container.innerHTML = '<div class="alert alert-info">No hay órdenes médicas registradas</div>';
            return;
        }

        let html = '<div class="table-responsive"><table class="table table-hover">';
        html += `
                <thead class="table-dark">
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Urgente</th>
                        <th>Estado</th>
                        <th>Médico</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
            `;

        ordenes.forEach(orden => {
            const fecha = new Date(orden.fecha_orden).toLocaleDateString('es-ES');

            let estadoClass = {
                'pendiente': 'warning',
                'en_proceso': 'info',
                'completada': 'success',
                'cancelada': 'danger'
            };

            let tipoIcon = {
                'medicamento': 'fa-pills',
                'laboratorio': 'fa-vial',
                'imagen': 'fa-x-ray',
                'procedimiento': 'fa-procedures',
                'dieta': 'fa-utensils',
                'cuidados': 'fa-hand-holding-medical',
                'interconsulta': 'fa-user-md'
            };

            html += `
                    <tr>
                        <td>${fecha}</td>
                        <td>
                            <i class="fas ${tipoIcon[orden.tipo_orden] || 'fa-file-medical'} me-1"></i>
                            ${orden.tipo_orden}
                        </td>
                        <td>${orden.descripcion}</td>
                        <td>
                            ${orden.urgente ? '<span class="badge bg-danger">URGENTE</span>' : '-'}
                        </td>
                        <td>
                            <span class="badge bg-${estadoClass[orden.estado] || 'secondary'}">
                                ${orden.estado.replace('_', ' ').toUpperCase()}
                            </span>
                        </td>
                        <td>${orden.medico}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="verOrden(${orden.id_orden})" title="Ver detalle">
                                    <i class="fas fa-eye"></i>
                                </button>
                                ${orden.estado === 'pendiente' ? `
                                    <button class="btn btn-outline-success" onclick="ejecutarOrden(${orden.id_orden})" title="Ejecutar">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="cancelarOrden(${orden.id_orden})" title="Cancelar">
                                        <i class="fas fa-times"></i>
                                    </button>
                                ` : ''}
                            </div>
                        </td>
                    </tr>
                `;
        });

        html += '</tbody></table></div>';
        container.innerHTML = html;
    }

    function cargarAntecedentes() {
        fetch(`../ingreso/listar_antecedentes.php?paciente=<?= $caso['id_paciente'] ?>`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ok') {
                    mostrarAntecedentes(data.data);
                } else {
                    document.getElementById('lista-antecedentes-caso').innerHTML =
                        '<div class="alert alert-info">No hay antecedentes registrados</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('lista-antecedentes-caso').innerHTML =
                    '<div class="alert alert-danger">Error al cargar los antecedentes</div>';
            });
    }

    function mostrarAntecedentes(antecedentes) {
        const container = document.getElementById('lista-antecedentes-caso');

        if (antecedentes.length === 0) {
            container.innerHTML = '<div class="alert alert-info">No hay antecedentes registrados</div>';
            return;
        }

        let html = '';
        antecedentes.forEach(ant => {
            const fechaRegistro = new Date(ant.fecha_registro).toLocaleDateString('es-ES');
            const fechaEvento = ant.fecha_evento ? new Date(ant.fecha_evento).toLocaleDateString('es-ES') : '-';

            let relevanciaClass = {
                'baja': 'secondary',
                'media': 'primary',
                'alta': 'danger'
            };

            let tipoLabels = {
                'personal': 'Personal',
                'familiar': 'Familiar',
                'quirurgico': 'Quirúrgico',
                'farmacologico': 'Farmacológico',
                'alergico': 'Alérgico',
                'social': 'Social',
                'gineco_obstetrico': 'Gineco-obstétrico'
            };

            html += `
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="badge bg-${relevanciaClass[ant.relevancia]}">${tipoLabels[ant.tipo_antecedente]}</span>
                                    <span class="badge bg-outline-${relevanciaClass[ant.relevancia]} text-${relevanciaClass[ant.relevancia]} border border-${relevanciaClass[ant.relevancia]} ms-2">
                                        ${ant.relevancia.toUpperCase()}
                                    </span>
                                </div>
                                <small class="text-muted">
                                    ${ant.numero_caso ? `Caso: ${ant.numero_caso}` : ''} | Registrado: ${fechaRegistro}
                                </small>
                            </div>
                            
                            <p class="card-text">${ant.descripcion}</p>
                            
                            ${ant.fecha_evento ? `<p class="text-muted mb-0"><strong>Fecha del evento:</strong> ${fechaEvento}</p>` : ''}
                            ${ant.archivo_adjunto ? `<p class="text-info mb-0"><i class="fas fa-paperclip me-1"></i>Archivo adjunto disponible</p>` : ''}
                        </div>
                    </div>
                `;
        });

        container.innerHTML = html;
    }

    // ===== FUNCIONES DE ACCIONES RÁPIDAS =====

    function nuevaEvolucion() {
        window.location.href = `evoluciones.php?caso=${ID_CASO}`;
    }

    function registrarSignos() {
        window.location.href = `signos_vitales.php?caso=${ID_CASO}`;
    }

    function solicitarLaboratorio() {
        window.location.href = `solicitar_laboratorio.php?caso=${ID_CASO}`;
    }

    function solicitarImagen() {
        window.location.href = `solicitar_imagen.php?caso=${ID_CASO}`;
    }

    function interconsulta() {
        window.location.href = `solicitar_interconsulta.php?caso=${ID_CASO}`;
    }

    function procesarEgreso() {
        Swal.fire({
            title: '¿Procesar Egreso?',
            text: 'Se iniciará el proceso de egreso del paciente',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, procesar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `formulario_egreso.php?caso=${ID_CASO}`;
            }
        });
    }

    // ===== FUNCIONES DE VISUALIZACIÓN =====

    function verEvolucion(idEvolucion) {
        fetch(`obtener_evolucion.php?id=${idEvolucion}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ok') {
                    const evolucion = data.data;
                    const fecha = new Date(evolucion.fecha_evolucion).toLocaleString('es-ES');

                    Swal.fire({
                        title: `Evolución - ${fecha}`,
                        html: `
                                <div class="text-start">
                                    <div class="mb-3">
                                        <strong>Tipo:</strong> ${evolucion.tipo_evolucion}<br>
                                        <strong>Médico:</strong> Dr. ${evolucion.medico}<br>
                                        <strong>Estado del paciente:</strong> ${evolucion.estado_paciente}
                                    </div>
                                    <div class="mb-3">
                                        <strong>Subjetivo:</strong><br>
                                        ${evolucion.subjetivo || 'No registrado'}
                                    </div>
                                    <div class="mb-3">
                                        <strong>Objetivo:</strong><br>
                                        ${evolucion.objetivo || 'No registrado'}
                                    </div>
                                    <div class="mb-3">
                                        <strong>Evaluación:</strong><br>
                                        ${evolucion.evaluacion || 'No registrado'}
                                    </div>
                                    <div class="mb-3">
                                        <strong>Plan:</strong><br>
                                        ${evolucion.plan || 'No registrado'}
                                    </div>
                                </div>
                            `,
                        width: '80%',
                        showCancelButton: true,
                        confirmButtonText: 'Editar',
                        cancelButtonText: 'Cerrar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            editarEvolucion(idEvolucion);
                        }
                    });
                }
            })
            .catch(error => {
                Swal.fire('Error', 'No se pudo cargar la evolución', 'error');
            });
    }

    function editarEvolucion(idEvolucion) {
        window.location.href = `editar_evolucion.php?id=${idEvolucion}&caso=${ID_CASO}`;
    }

    function verLaboratorio(idLaboratorio) {
        // Implementar visualización detallada del laboratorio
        console.log('Ver laboratorio:', idLaboratorio);
    }

    function cargarResultado(idLaboratorio) {
        // Implementar carga de resultado de laboratorio
        console.log('Cargar resultado:', idLaboratorio);
    }

    function verImagen(idImagen) {
        // Implementar visualización de imagen
        console.log('Ver imagen:', idImagen);
    }

    function descargarImagen(archivo) {
        window.open(`descargar_imagen.php?archivo=${archivo}`, '_blank');
    }

    function verOrden(idOrden) {
        // Implementar visualización de orden
        console.log('Ver orden:', idOrden);
    }

    function ejecutarOrden(idOrden) {
        Swal.fire({
            title: 'Ejecutar Orden',
            text: '¿Marcar esta orden como ejecutada?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, ejecutar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('ejecutar_orden.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id_orden=${idOrden}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'ok') {
                            Swal.fire('Éxito', 'Orden ejecutada correctamente', 'success');
                            cargarOrdenes(); // Recargar la lista
                            cargarMetricasRapidas(); // Actualizar métricas
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    });
            }
        });
    }

    function cancelarOrden(idOrden) {
        Swal.fire({
            title: 'Cancelar Orden',
            input: 'textarea',
            inputLabel: 'Motivo de cancelación',
            inputPlaceholder: 'Ingrese el motivo...',
            showCancelButton: true,
            confirmButtonText: 'Cancelar Orden',
            cancelButtonText: 'Cerrar'
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                fetch('cancelar_orden.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id_orden=${idOrden}&motivo=${encodeURIComponent(result.value)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'ok') {
                            Swal.fire('Éxito', 'Orden cancelada correctamente', 'success');
                            cargarOrdenes(); // Recargar la lista
                            cargarMetricasRapidas(); // Actualizar métricas
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    });
            }
        });
    }

    // ===== FUNCIONES DE UTILIDAD =====

    function actualizarEstadoCaso(nuevoEstado) {
        Swal.fire({
            title: '¿Cambiar estado del caso?',
            text: `El caso cambiará a estado: ${nuevoEstado}`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, cambiar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('actualizar_estado_caso.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id_caso=${ID_CASO}&estado=${nuevoEstado}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'ok') {
                            Swal.fire('Éxito', 'Estado actualizado correctamente', 'success');
                            location.reload(); // Recargar la página para mostrar cambios
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    });
            }
        });
    }

    function imprimirResumen() {
        window.open(`imprimir_caso.php?caso=${ID_CASO}`, '_blank');
    }

    function exportarCaso() {
        Swal.fire({
            title: 'Exportar Caso',
            text: '¿En qué formato desea exportar?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'PDF',
            cancelButtonText: 'Excel',
            showDenyButton: true,
            denyButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.open(`exportar_caso.php?caso=${ID_CASO}&formato=pdf`, '_blank');
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                window.open(`exportar_caso.php?caso=${ID_CASO}&formato=excel`, '_blank');
            }
        });
    }

    // Auto-refresh cada 5 minutos para métricas
    setInterval(() => {
        cargarMetricasRapidas();
    }, 300000);
    </script>
</body>

</html>