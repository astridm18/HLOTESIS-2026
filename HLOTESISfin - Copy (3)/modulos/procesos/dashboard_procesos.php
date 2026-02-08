<?php
// modulos/procesos/dashboard_procesos.php
require_once '../../config/forms/database.php';
require_once '../../config/forms/functions.php';

verificarSesion();

$id_caso = $_GET['caso'] ?? 0;
$_SESSION['usuario_rol'] = $_SESSION['usuario_rol'] ?? 'Invitado'; // Rol por defecto si no se encuentra

$stmt = $pdo->prepare("
    SELECT cc.*, p.numero_historia, CONCAT(p.nombres, ' ', p.apellidos) as nombre_paciente,
           p.cedula, TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) as edad,
           di.fecha_ingreso, di.hora_ingreso,
           -- ✅ COMBINAR fecha_ingreso + hora_ingreso para mostrar correctamente
           CONCAT(di.fecha_ingreso, ' ', di.hora_ingreso) as fecha_hora_ingreso,
           -- ✅ CALCULAR DÍAS DE ESTANCIA CORRECTAMENTE
           CASE 
               WHEN cc.estado_caso = 'cerrado' AND e.fecha_egreso IS NOT NULL THEN 
                   DATEDIFF(DATE(e.fecha_egreso), di.fecha_ingreso)
               WHEN cc.estado_caso = 'activo' THEN 
                   DATEDIFF(CURDATE(), di.fecha_ingreso)
               ELSE 0
           END as dias_estancia,
           di.motivo_consulta, di.diagnostico_ingreso,
           e.fecha_egreso, cc.estado_caso
    FROM casos_clinicos cc
    INNER JOIN pacientes p ON cc.id_paciente = p.id_paciente
    INNER JOIN datos_ingreso di ON cc.id_caso = di.id_caso
    LEFT JOIN egresos e ON cc.id_caso = e.id_caso  -- ✅ AGREGAR JOIN con egresos
    WHERE cc.id_caso = ?
");
$stmt->execute([$id_caso]);
$caso = $stmt->fetch();

if (!$caso) {
    die('Caso no encontrado');
}

// Obtener estadísticas
$stats = [];

// Evoluciones
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM evoluciones WHERE id_caso = ?");
$stmt->execute([$id_caso]);
$stats['evoluciones'] = $stmt->fetchColumn();

// Signos vitales últimas 24h
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM signos_vitales 
    WHERE id_caso = ? AND fecha_registro >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
");
$stmt->execute([$id_caso]);
$stats['signos_24h'] = $stmt->fetchColumn();

// Órdenes pendientes
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM ordenes_medicas 
    WHERE id_caso = ? AND estado = 'pendiente'
");
$stmt->execute([$id_caso]);
$stats['ordenes_pendientes'] = $stmt->fetchColumn();

// Medicamentos activos
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM prescripciones 
    WHERE id_caso = ? AND estado = 'activa'
");
$stmt->execute([$id_caso]);
$stats['medicamentos_activos'] = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Procesos - <?= $caso['nombre_paciente'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
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

        .editable-row {
            transition: all 0.3s;
        }

        .editable-row:hover {
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }

        .btn-group-sm .btn {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }

        .badge-status {
            font-size: 0.8rem;
        }

        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <div class="container-fluid mt-4">
        <!-- Header del paciente (MANTENER IGUAL) -->
        <div class="card bg-primary text-white mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h4><i class="fas fa-user-injured me-2"></i><?= $caso['nombre_paciente'] ?></h4>
                        <p class="mb-1">
                            <strong>Caso:</strong> <?= $caso['numero_caso'] ?> |
                            <strong>Historia:</strong> <?= $caso['numero_historia'] ?> |
                            <strong>Cédula:</strong> <?= $caso['cedula'] ?>
                        </p>
                        <p class="mb-0">
                            <strong>Edad:</strong> <?= $caso['edad'] ?> años |
                            <strong>Servicio:</strong> <?= $caso['servicio_actual'] ?> |
                            <strong>Días estancia:</strong> <?= $caso['dias_estancia'] ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="badge bg-<?= $caso['estado_caso'] === 'activo' ? 'success' : 'warning' ?> fs-6">
                            <?= ucfirst($caso['estado_caso']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas rápidas (MANTENER IGUAL) -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-notes-medical fa-2x mb-2"></i>
                        <h5><?= $stats['evoluciones'] ?></h5>
                        <small>Evoluciones</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-heartbeat fa-2x mb-2"></i>
                        <h5><?= $stats['signos_24h'] ?></h5>
                        <small>Signos (24h)</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-clipboard-list fa-2x mb-2"></i>
                        <h5><?= $stats['ordenes_pendientes'] ?></h5>
                        <small>Órdenes Pendientes</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-secondary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-pills fa-2x mb-2"></i>
                        <h5><?= $stats['medicamentos_activos'] ?></h5>
                        <small>Medicamentos</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Accesos rápidos (MANTENER IGUAL) -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-th-large me-2"></i>Módulos de Proceso</h5>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">
                            <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'dirección', 'medicina'])): ?>
                                <div class="col-md-4">
                                    <a href="evoluciones.php?caso=<?= $id_caso ?>"
                                        class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                                        <i class="fas fa-notes-medical fa-2x mb-2"></i>
                                        <span>Evoluciones</span>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <div class="col-md-4">
                                <a href="signos_vitales.php?caso=<?= $id_caso ?>"
                                    class="btn btn-outline-success w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                                    <i class="fas fa-heartbeat fa-2x mb-2"></i>
                                    <span>Signos Vitales</span>
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="ordenes.php?caso=<?= $id_caso ?>"
                                    class="btn btn-outline-warning w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                                    <i class="fas fa-prescription fa-2x mb-2"></i>
                                    <span>Órdenes</span>
                                </a>
                            </div>
                            <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'dirección', 'medicina'])): ?>
                                <div class="col-md-4">
                                    <a href="laboratorios.php?caso=<?= $id_caso ?>"
                                        class="btn btn-outline-info w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                                        <i class="fas fa-vial fa-2x mb-2"></i>
                                        <span>Laboratorios</span>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'dirección', 'medicina'])): ?>
                                <div class="col-md-4">
                                    <a href="imagenes.php?caso=<?= $id_caso ?>"
                                        class="btn btn-outline-secondary w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                                        <i class="fas fa-x-ray fa-2x mb-2"></i>
                                        <span>Imágenes</span>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'dirección', 'medicina'])): ?>
                                <div class="col-md-4">
                                    <a href="procedimientos.php?caso=<?= $id_caso ?>"
                                        class="btn btn-outline-dark w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                                        <i class="fas fa-tools fa-2x mb-2"></i>
                                        <span>Procedimientos</span>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'dirección', 'medicina'])): ?>
                                <div class="col-md-4">
                                    <a href="interconsultas.php?caso=<?= $id_caso ?>"
                                        class="btn btn-outline-danger w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                                        <i class="fas fa-user-md fa-2x mb-2"></i>
                                        <span>Interconsultas</span>
                                    </a>
                                </div>
                            <?php endif; ?>

                            <div class="col-md-4">
                                <a href="notas_enfermeria.php?caso=<?= $id_caso ?>"
                                    class="btn btn-outline-success w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                                    <i class="fas fa-user-nurse fa-2x mb-2"></i>
                                    <span>Enfermería</span>
                                </a>
                            </div>
                            <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'dirección', 'medicina'])): ?>

                                <div class="col-md-4">
                                    <a href="../egreso/formulario_egreso.php?caso=<?= $id_caso ?>"
                                        class="btn btn-outline-warning w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                                        <i class="fas fa-sign-out-alt fa-2x mb-2"></i>
                                        <span>Procesar Egreso</span>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Panel lateral (MANTENER IGUAL) -->
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-info-circle me-2"></i>Información del Caso</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Motivo de Consulta:</strong><br>
                            <small><?= $caso['motivo_consulta'] ?></small>
                        </p>

                        <p><strong>Diagnóstico de Ingreso:</strong><br>
                            <small><?= $caso['diagnostico_ingreso'] ?? 'No especificado' ?></small>
                        </p>

                        <p><strong>Fecha de Ingreso:</strong><br>
                            <small><?= formatearFecha($caso['fecha_hora_ingreso'], true) ?></small>
                        </p>

                        <p><strong>Ubicación:</strong><br>
                            <small><?= $caso['servicio_actual'] ?> -
                                <?= $caso['cama_actual'] ?? 'Sin asignar' ?></small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], [''])): ?>

            <!-- 🆕 NUEVA SECCIÓN: GESTIÓN INTEGRADA -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-tachometer-alt me-2"></i>Gestión Integrada del Caso</h5>
                            <small class="text-muted">Vista rápida y edición de registros</small>
                        </div>
                        <div class="card-body">
                            <!-- Navegación por Pestañas -->
                            <ul class="nav nav-pills mb-4" id="gestionTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="evoluciones-tab" data-bs-toggle="pill"
                                        data-bs-target="#evoluciones-integradas" type="button">
                                        <i class="fas fa-notes-medical me-2"></i>Evoluciones Recientes
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="signos-tab" data-bs-toggle="pill"
                                        data-bs-target="#signos-integrados" type="button">
                                        <i class="fas fa-heartbeat me-2"></i>Signos Vitales
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="laboratorios-tab" data-bs-toggle="pill"
                                        data-bs-target="#laboratorios-integrados" type="button">
                                        <i class="fas fa-vial me-2"></i>Laboratorios
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="ordenes-tab" data-bs-toggle="pill"
                                        data-bs-target="#ordenes-integradas" type="button">
                                        <i class="fas fa-prescription me-2"></i>Órdenes Médicas
                                    </button>
                                </li>

                            </ul>

                            <!-- Contenido de las Pestañas -->
                            <div class="tab-content" id="gestionTabsContent">

                                <!-- Evoluciones Integradas -->
                                <div class="tab-pane fade show active" id="evoluciones-integradas" role="tabpanel">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6><i class="fas fa-notes-medical me-2"></i>Últimas Evoluciones</h6>
                                        <button class="btn btn-primary btn-sm"
                                            onclick="window.open('evoluciones.php?caso=<?= $id_caso ?>', '_blank')">
                                            <i class="fas fa-plus me-1"></i>Nueva Evolución
                                        </button>
                                    </div>
                                    <div id="lista-evoluciones-integradas">
                                        <div class="text-center">
                                            <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                            <p class="text-muted mt-2">Cargando evoluciones...</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Signos Vitales Integrados -->
                                <div class="tab-pane fade" id="signos-integrados" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6><i class="fas fa-chart-line me-2"></i>Tendencias de Signos Vitales</h6>
                                                <button class="btn btn-success btn-sm"
                                                    onclick="window.open('signos_vitales.php?caso=<?= $id_caso ?>', '_blank')">
                                                    <i class="fas fa-plus me-1"></i>Registrar Signos
                                                </button>
                                            </div>
                                            <div class="chart-container">
                                                <canvas id="signosChart"></canvas>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <h6><i class="fas fa-list me-2"></i>Registros Recientes</h6>
                                            <div id="lista-signos-recientes">
                                                <div class="text-center">
                                                    <i class="fas fa-spinner fa-spin"></i>
                                                    <p class="text-muted mt-2">Cargando...</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Laboratorios Integrados -->
                                <div class="tab-pane fade" id="laboratorios-integrados" role="tabpanel">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6><i class="fas fa-vial me-2"></i>Exámenes de Laboratorio</h6>
                                        <button class="btn btn-info btn-sm"
                                            onclick="window.open('laboratorios.php?caso=<?= $id_caso ?>', '_blank')">
                                            <i class="fas fa-plus me-1"></i>Solicitar Examen
                                        </button>
                                    </div>
                                    <div id="lista-laboratorios-integrados">
                                        <div class="text-center">
                                            <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                            <p class="text-muted mt-2">Cargando exámenes...</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Órdenes Integradas -->
                                <div class="tab-pane fade" id="ordenes-integradas" role="tabpanel">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6><i class="fas fa-prescription me-2"></i>Órdenes Médicas</h6>
                                        <button class="btn btn-warning btn-sm"
                                            onclick="window.open('ordenes.php?caso=<?= $id_caso ?>', '_blank')">
                                            <i class="fas fa-plus me-1"></i>Nueva Orden
                                        </button>
                                    </div>
                                    <div id="lista-ordenes-integradas">
                                        <div class="text-center">
                                            <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                            <p class="text-muted mt-2">Cargando órdenes...</p>
                                        </div>
                                    </div>
                                </div>


                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <!-- Modal para Edición Rápida -->
    <div class="modal fade" id="modalEdicion" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloModalEdicion">Editar Registro</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="contenidoModalEdicion">
                    <!-- Contenido dinámico -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnGuardarEdicion">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const ID_CASO = <?= $id_caso ?>;
        let signosChart;

        // Inicializar al cargar la página
        document.addEventListener('DOMContentLoaded', function () {
            cargarEvolucionesIntegradas();
            inicializarGraficoSignos();

            // Configurar eventos de pestañas
            document.querySelectorAll('[data-bs-toggle="pill"]').forEach(tab => {
                tab.addEventListener('shown.bs.tab', function (event) {
                    const targetId = event.target.getAttribute('data-bs-target');
                    switch (targetId) {
                        case '#signos-integrados':
                            cargarSignosIntegrados();
                            break;
                        case '#laboratorios-integrados':
                            cargarLaboratoriosIntegrados();
                            break;
                        case '#ordenes-integradas':
                            cargarOrdenesIntegradas();
                            break;
                        case '#resumen-general':
                            cargarResumenGeneral();
                            break;
                    }
                });
            });
        });

        // ===== FUNCIONES DE CARGA =====

        function cargarEvolucionesIntegradas() {
            fetch(`listar_evoluciones.php?caso=${ID_CASO}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ok') {
                        mostrarEvolucionesIntegradas(data.data.slice(0, 5)); // Últimas 5
                    } else {
                        document.getElementById('lista-evoluciones-integradas').innerHTML =
                            '<div class="alert alert-info">No hay evoluciones registradas</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('lista-evoluciones-integradas').innerHTML =
                        '<div class="alert alert-danger">Error al cargar las evoluciones</div>';
                });
        }

        function mostrarEvolucionesIntegradas(evoluciones) {
            const container = document.getElementById('lista-evoluciones-integradas');

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
                    <div class="timeline-item editable-row">
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
                                <button class="btn btn-outline-danger" onclick="eliminarEvolucion(${evolucion.id_evolucion})" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Subjetivo:</strong>
                                <p class="text-muted">${(evolucion.subjetivo || 'No registrado').substring(0, 100)}${evolucion.subjetivo && evolucion.subjetivo.length > 100 ? '...' : ''}</p>
                            </div>
                            <div class="col-md-6">
                                <strong>Plan:</strong>
                                <p class="text-muted">${(evolucion.plan || 'No registrado').substring(0, 100)}${evolucion.plan && evolucion.plan.length > 100 ? '...' : ''}</p>
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
                        label: 'Pulso (FC)',
                        data: [],
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        yAxisID: 'y1'
                    },
                    {
                        label: 'PA Sistólica (mmHg)',
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
                            text: 'Últimas 48 horas'
                        },
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    }
                }
            });
        }

        function cargarSignosIntegrados() {
            fetch(`listar_signos.php?caso=${ID_CASO}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ok') {
                        actualizarGraficoSignos(data.data);
                        mostrarSignosRecientes(data.data.slice(0, 10)); // Últimos 10
                    }
                })
                .catch(error => console.error('Error cargando signos:', error));
        }

        function actualizarGraficoSignos(signos) {
            if (!signosChart || signos.length === 0) return;

            // Filtrar últimas 48 horas y tomar máximo 10 registros
            const ahora = new Date();
            const hace48h = new Date(ahora.getTime() - (48 * 60 * 60 * 1000));

            const signosRecientes = signos
                .filter(s => new Date(s.fecha_registro) >= hace48h)
                .slice(0, 10)
                .reverse();

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
                    <div class="border rounded p-2 mb-2 editable-row ${alertClass}">
                        <div class="d-flex justify-content-between align-items-start">
                            <small class="text-muted">${fecha}</small>
                            <div>
                                ${alertIcon}
                                <div class="btn-group btn-group-sm ms-2">
                                    <button class="btn btn-outline-warning btn-sm" onclick="editarSignos(${signo.id_signos})" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm" onclick="eliminarSignos(${signo.id_signos})" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
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

        function cargarLaboratoriosIntegrados() {
            fetch(`listar_laboratorios.php?caso=${ID_CASO}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ok') {
                        mostrarLaboratoriosIntegrados(data.data);
                    } else {
                        document.getElementById('lista-laboratorios-integrados').innerHTML =
                            '<div class="alert alert-info">No hay exámenes de laboratorio</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('lista-laboratorios-integrados').innerHTML =
                        '<div class="alert alert-danger">Error al cargar los exámenes</div>';
                });
        }

        function mostrarLaboratoriosIntegrados(laboratorios) {
            const container = document.getElementById('lista-laboratorios-integrados');

            if (laboratorios.length === 0) {
                container.innerHTML = '<div class="alert alert-info">No hay exámenes de laboratorio registrados</div>';
                return;
            }

            let html = '<div class="table-responsive"><table class="table table-hover">';
            html += `
                <thead class="table-dark">
                    <tr>
                        <th>Fecha</th>
                        <th>Examen</th>
                        <th>Estado</th>
                        <th>Resultado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
            `;

            laboratorios.forEach(lab => {
                const fechaSolicitud = new Date(lab.fecha_solicitud).toLocaleDateString('es-ES');

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
                    <tr class="editable-row">
                        <td>${fechaSolicitud}</td>
                        <td>
                            <strong>${lab.tipo_examen}</strong><br>
                            <small class="text-muted">${lab.parametro}</small>
                        </td>
                        <td><span class="badge bg-${estadoClass}">${estadoText}</span></td>
                        <td>
                            ${resultado}
                            ${lab.valor_referencia ? `<br><small class="text-muted">Ref: ${lab.valor_referencia}</small>` : ''}
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="verLaboratorio(${lab.id_laboratorio})" title="Ver detalle">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-warning" onclick="editarLaboratorio(${lab.id_laboratorio})" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                ${!lab.fecha_resultado ? `
                                    <button class="btn btn-outline-success" onclick="cargarResultado(${lab.id_laboratorio})" title="Cargar resultado">
                                        <i class="fas fa-upload"></i>
                                    </button>
                                ` : ''}
                                <button class="btn btn-outline-danger" onclick="eliminarLaboratorio(${lab.id_laboratorio})" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            html += '</tbody></table></div>';
            container.innerHTML = html;
        }

        function cargarOrdenesIntegradas() {
            fetch(`listar_ordenes.php?caso=${ID_CASO}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ok') {
                        mostrarOrdenesIntegradas(data.data);
                    } else {
                        document.getElementById('lista-ordenes-integradas').innerHTML =
                            '<div class="alert alert-info">No hay órdenes médicas</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('lista-ordenes-integradas').innerHTML =
                        '<div class="alert alert-danger">Error al cargar las órdenes</div>';
                });
        }

        function mostrarOrdenesIntegradas(ordenes) {
            const container = document.getElementById('lista-ordenes-integradas');

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
                        <th>Estado</th>
                        <th>Médico</th>
                        
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
                    <tr class="editable-row">
                        <td>${fecha}</td>
                        <td>
                            <i class="fas ${tipoIcon[orden.tipo_orden] || 'fa-file-medical'} me-1"></i>
                            ${orden.tipo_orden}
                            ${orden.urgente ? '<br><span class="badge bg-danger">URGENTE</span>' : ''}
                        </td>
                        <td>
                            ${orden.descripcion.length > 50 ? orden.descripcion.substring(0, 50) + '...' : orden.descripcion}
                        </td>
                        <td>
                            <span class="badge bg-${estadoClass[orden.estado] || 'secondary'}">
                                ${orden.estado.replace('_', ' ').toUpperCase()}
                            </span>
                        </td>
                        <td>${orden.medico}</td>
                        
                    </tr>
                `;
            });

            html += '</tbody></table></div>';
            container.innerHTML = html;
        }

        function cargarResumenGeneral() {
            // Cargar actividad del caso
            fetch(`obtener_resumen_caso.php?caso=${ID_CASO}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ok') {
                        mostrarResumenActividad(data.data.actividad);
                        mostrarResumenAlertas(data.data.alertas);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('resumen-actividad').innerHTML = 'Error al cargar resumen';
                    document.getElementById('resumen-alertas').innerHTML = 'Error al cargar alertas';
                });
        }

        function mostrarResumenActividad(actividad) {
            const container = document.getElementById('resumen-actividad');

            let html = `
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="text-primary">${actividad.evoluciones_hoy || 0}</h4>
                        <small>Evoluciones hoy</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-success">${actividad.signos_hoy || 0}</h4>
                        <small>Signos registrados</small>
                    </div>
                    <div class="col-6 mt-3">
                        <h4 class="text-warning">${actividad.ordenes_pendientes || 0}</h4>
                        <small>Órdenes pendientes</small>
                    </div>
                    <div class="col-6 mt-3">
                        <h4 class="text-info">${actividad.labs_pendientes || 0}</h4>
                        <small>Labs pendientes</small>
                    </div>
                </div>
                <hr>
                <p class="text-muted mb-0">
                    <i class="fas fa-clock me-1"></i>
                    Última actividad: ${actividad.ultima_actividad || 'No registrada'}
                </p>
            `;

            container.innerHTML = html;
        }

        function mostrarResumenAlertas(alertas) {
            const container = document.getElementById('resumen-alertas');

            if (!alertas || alertas.length === 0) {
                container.innerHTML = '<div class="alert alert-success">No hay alertas pendientes</div>';
                return;
            }

            let html = '';
            alertas.forEach(alerta => {
                let alertClass = {
                    'baja': 'info',
                    'media': 'warning',
                    'alta': 'danger'
                };

                html += `
                    <div class="alert alert-${alertClass[alerta.prioridad]} alert-dismissible">
                        <strong>${alerta.titulo}</strong><br>
                        <small>${alerta.mensaje}</small>
                        <button type="button" class="btn-close" onclick="marcarAlertaLeida(${alerta.id_alerta})"></button>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        // ===== FUNCIONES DE EDICIÓN =====

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
            window.open(`editar_evolucion.php?id=${idEvolucion}&caso=${ID_CASO}`, '_blank');
        }

        function eliminarEvolucion(idEvolucion) {
            Swal.fire({
                title: '¿Eliminar evolución?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('eliminar_evolucion.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id_evolucion=${idEvolucion}`
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'ok') {
                                Swal.fire('Eliminado', 'Evolución eliminada correctamente', 'success');
                                cargarEvolucionesIntegradas(); // Recargar la lista
                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        });
                }
            });
        }

        function editarSignos(idSignos) {
            window.open(`editar_signos.php?id=${idSignos}&caso=${ID_CASO}`, '_blank');
        }

        function eliminarSignos(idSignos) {
            Swal.fire({
                title: '¿Eliminar registro de signos vitales?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('eliminar_signos.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id_signos=${idSignos}`
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'ok') {
                                Swal.fire('Eliminado', 'Registro eliminado correctamente', 'success');
                                cargarSignosIntegrados(); // Recargar la lista
                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        });
                }
            });
        }

        function editarLaboratorio(idLaboratorio) {
            window.open(`editar_laboratorio.php?id=${idLaboratorio}&caso=${ID_CASO}`, '_blank');
        }

        function cargarResultado(idLaboratorio) {
            window.open(`cargar_resultado_lab.php?id=${idLaboratorio}&caso=${ID_CASO}`, '_blank');
        }

        function eliminarLaboratorio(idLaboratorio) {
            Swal.fire({
                title: '¿Eliminar orden de laboratorio?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('eliminar_laboratorio.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id_laboratorio=${idLaboratorio}`
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'ok') {
                                Swal.fire('Eliminado', 'Orden eliminada correctamente', 'success');
                                cargarLaboratoriosIntegrados(); // Recargar la lista
                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        });
                }
            });
        }

        function editarOrden(idOrden) {
            window.open(`editar_orden.php?id=${idOrden}&caso=${ID_CASO}`, '_blank');
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
                                cargarOrdenesIntegradas(); // Recargar la lista
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
                                cargarOrdenesIntegradas(); // Recargar la lista
                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        });
                }
            });
        }

        function verLaboratorio(idLaboratorio) {
            // Implementar vista detallada de laboratorio
            window.open(`ver_laboratorio.php?id=${idLaboratorio}`, '_blank');
        }

        function verOrden(idOrden) {
            // Implementar vista detallada de orden
            window.open(`ver_orden.php?id=${idOrden}`, '_blank');
        }

        function marcarAlertaLeida(idAlerta) {
            fetch('marcar_alerta_leida.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id_alerta=${idAlerta}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ok') {
                        cargarResumenGeneral(); // Recargar alertas
                    }
                });
        }

        // Auto-refresh cada 5 minutos para la pestaña activa
        setInterval(() => {
            const activeTab = document.querySelector('.nav-link.active').getAttribute('data-bs-target');
            switch (activeTab) {
                case '#evoluciones-integradas':
                    cargarEvolucionesIntegradas();
                    break;
                case '#signos-integrados':
                    cargarSignosIntegrados();
                    break;
                case '#laboratorios-integrados':
                    cargarLaboratoriosIntegrados();
                    break;
                case '#ordenes-integradas':
                    cargarOrdenesIntegradas();
                    break;
                case '#resumen-general':
                    cargarResumenGeneral();
                    break;
            }
        }, 300000); // 5 minutos
    </script>
</body>

</html>