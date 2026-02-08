<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Ingreso - Sistema de Historias Médicas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    .sidebar {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        color: white;
    }

    .sidebar .nav-link {
        color: rgba(255, 255, 255, 0.8);
        padding: 15px 20px;
        border-radius: 10px;
        margin: 5px 10px;
        transition: all 0.3s;
    }

    .sidebar .nav-link:hover,
    .sidebar .nav-link.active {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        transform: translateX(5px);
    }

    .main-content {
        background: #f8f9fa;
        min-height: 100vh;
    }

    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s;
    }

    .card:hover {
        transform: translateY(-5px);
    }

    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .stat-card-success {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }

    .stat-card-warning {
        background: linear-gradient(135deg, #fdbb2d 0%, #22c1c3 100%);
    }

    .stat-card-danger {
        background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
    }

    .btn-action {
        border-radius: 50px;
        padding: 12px 30px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .quick-action-card {
        cursor: pointer;
        transition: all 0.3s;
    }

    .quick-action-card:hover {
        transform: scale(1.05);
    }

    .navbar-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .content-section {
        display: none;
    }

    .content-section.active {
        display: block;
    }

    .table-responsive {
        border-radius: 15px;
        overflow: hidden;
    }

    .badge-custom {
        padding: 8px 12px;
        border-radius: 20px;
    }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-hospital-user me-2"></i>
                Dashboard de Ingreso
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user-md me-1"></i>
                    Dr. Sistema Temporal
                </span>
                <a class="nav-link" href="#" onclick="cerrarSesion()">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="p-3">
                    <h6 class="text-white mb-4">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        MENÚ PRINCIPAL
                    </h6>
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="#" onclick="mostrarSeccion('dashboard')">
                            <i class="fas fa-chart-pie me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="#" onclick="mostrarSeccion('nuevo-ingreso')">
                            <i class="fas fa-user-plus me-2"></i>Nuevo Ingreso
                        </a>
                        <a class="nav-link" href="#" onclick="mostrarSeccion('casos-activos')">
                            <i class="fas fa-procedures me-2"></i>Casos Activos
                        </a>
                        <a class="nav-link" href="#" onclick="mostrarSeccion('buscar-paciente')">
                            <i class="fas fa-search me-2"></i>Buscar Paciente
                        </a>
                        <a class="nav-link" href="#" onclick="mostrarSeccion('estadisticas')">
                            <i class="fas fa-chart-bar me-2"></i>Estadísticas
                        </a>
                        <a class="nav-link" href="#" onclick="mostrarSeccion('personal')">
                            <i class="fas fa-users me-2"></i>Personal Médico
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content p-4">

                <!-- Dashboard Section -->
                <div id="dashboard-section" class="content-section active">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-chart-pie me-2"></i>Dashboard General</h2>
                        <button class="btn btn-primary btn-action" onclick="actualizarDashboard()">
                            <i class="fas fa-sync-alt me-2"></i>Actualizar
                        </button>
                    </div>

                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-users fa-3x mb-3"></i>
                                    <h3 id="total-pacientes">-</h3>
                                    <p class="mb-0">Pacientes Totales</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card-success">
                                <div class="card-body text-center">
                                    <i class="fas fa-bed fa-3x mb-3"></i>
                                    <h3 id="casos-activos">-</h3>
                                    <p class="mb-0">Casos Activos</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card-warning">
                                <div class="card-body text-center">
                                    <i class="fas fa-calendar-day fa-3x mb-3"></i>
                                    <h3 id="ingresos-hoy">-</h3>
                                    <p class="mb-0">Ingresos Hoy</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card-danger">
                                <div class="card-body text-center">
                                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                                    <h3 id="casos-urgentes">-</h3>
                                    <p class="mb-0">Casos Urgentes</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h4><i class="fas fa-bolt me-2"></i>Acciones Rápidas</h4>
                        </div>
                        <div class="col-md-4">
                            <div class="card quick-action-card" onclick="mostrarSeccion('nuevo-ingreso')">
                                <div class="card-body text-center">
                                    <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
                                    <h5>Nuevo Ingreso</h5>
                                    <p class="text-muted">Registrar nuevo paciente</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card quick-action-card" onclick="mostrarSeccion('buscar-paciente')">
                                <div class="card-body text-center">
                                    <i class="fas fa-search fa-3x text-success mb-3"></i>
                                    <h5>Buscar Paciente</h5>
                                    <p class="text-muted">Encontrar paciente existente</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card quick-action-card" onclick="mostrarSeccion('casos-activos')">
                                <div class="card-body text-center">
                                    <i class="fas fa-list fa-3x text-warning mb-3"></i>
                                    <h5>Ver Casos Activos</h5>
                                    <p class="text-muted">Casos en tratamiento</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activities -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-clock me-2"></i>Últimos Ingresos</h5>
                                </div>
                                <div class="card-body">
                                    <div id="ultimos-ingresos">
                                        <div class="text-center text-muted">
                                            <i class="fas fa-spinner fa-spin"></i> Cargando...
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-chart-doughnut me-2"></i>Distribución por Servicio</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="serviciosChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Nuevo Ingreso Section -->
                <div id="nuevo-ingreso-section" class="content-section">
                    <h2><i class="fas fa-user-plus me-2"></i>Nuevo Ingreso de Paciente</h2>
                    <iframe src="formulario_ingreso.php" width="100%" height="800" frameborder="0"
                        style="border-radius: 15px;"></iframe>
                </div>

                <!-- Casos Activos Section -->
                <div id="casos-activos-section" class="content-section">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-procedures me-2"></i>Casos Activos</h2>
                        <div>
                            <button class="btn btn-outline-primary me-2" onclick="cargarCasosActivos()">
                                <i class="fas fa-sync-alt me-1"></i>Actualizar
                            </button>
                            <button class="btn btn-success" onclick="mostrarSeccion('nuevo-ingreso')">
                                <i class="fas fa-plus me-1"></i>Nuevo Caso
                            </button>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>N° Caso</th>
                                            <th>Paciente</th>
                                            <th>Cédula</th>
                                            <th>Fecha Ingreso</th>
                                            <th>Servicio</th>
                                            <th>Médico Responsable</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabla-casos-activos">
                                        <tr>
                                            <td colspan="8" class="text-center">
                                                <i class="fas fa-spinner fa-spin"></i> Cargando casos activos...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Buscar Paciente Section -->
                <div id="buscar-paciente-section" class="content-section">
                    <h2><i class="fas fa-search me-2"></i>Buscar Paciente</h2>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-search me-2"></i>Búsqueda</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Buscar por:</label>
                                        <select class="form-control" id="tipo-busqueda">
                                            <option value="cedula">Cédula</option>
                                            <option value="nombre">Nombre</option>
                                            <option value="historia">N° Historia</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Término de búsqueda:</label>
                                        <input type="text" class="form-control" id="termino-busqueda"
                                            placeholder="Ingrese término a buscar">
                                    </div>
                                    <button class="btn btn-primary w-100" onclick="buscarPacientes()">
                                        <i class="fas fa-search me-2"></i>Buscar
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-user me-2"></i>Resultados</h5>
                                </div>
                                <div class="card-body">
                                    <div id="resultados-busqueda">
                                        <p class="text-muted text-center">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Ingrese un término de búsqueda
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas Section -->
                <div id="estadisticas-section" class="content-section">
                    <h2><i class="fas fa-chart-bar me-2"></i>Estadísticas e Informes</h2>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-chart-line me-2"></i>Ingresos por Mes</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="ingresosMesChart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-chart-pie me-2"></i>Diagnósticos Frecuentes</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="diagnosticosChart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-file-excel me-2"></i>Generar Reportes</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <button class="btn btn-success w-100"
                                                onclick="generarReporte('ingresos-diarios')">
                                                <i class="fas fa-calendar-day me-2"></i>
                                                Ingresos Diarios
                                            </button>
                                        </div>
                                        <div class="col-md-3">
                                            <button class="btn btn-info w-100"
                                                onclick="generarReporte('pacientes-activos')">
                                                <i class="fas fa-users me-2"></i>
                                                Pacientes Activos
                                            </button>
                                        </div>
                                        <div class="col-md-3">
                                            <button class="btn btn-warning w-100"
                                                onclick="generarReporte('estadisticas-servicio')">
                                                <i class="fas fa-hospital me-2"></i>
                                                Por Servicio
                                            </button>
                                        </div>
                                        <div class="col-md-3">
                                            <button class="btn btn-danger w-100"
                                                onclick="generarReporte('casos-urgentes')">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                Casos Urgentes
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Personal Section -->
                <div id="personal-section" class="content-section">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-users me-2"></i>Personal Médico</h2>
                        <button class="btn btn-primary" onclick="cargarPersonal()">
                            <i class="fas fa-sync-alt me-2"></i>Actualizar
                        </button>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre Completo</th>
                                            <th>Cédula</th>
                                            <th>Rol</th>
                                            <th>Especialidad</th>
                                            <th>Estado</th>
                                            <th>Casos Asignados</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabla-personal">
                                        <tr>
                                            <td colspan="7" class="text-center">
                                                <i class="fas fa-spinner fa-spin"></i> Cargando personal...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Variables globales
    let serviciosChart, ingresosMesChart, diagnosticosChart;

    // Inicializar dashboard
    document.addEventListener('DOMContentLoaded', function() {
        actualizarDashboard();
        cargarCasosActivos();
        cargarPersonal();
        inicializarGraficos();
    });

    // Mostrar secciones
    function mostrarSeccion(seccion) {
        // Ocultar todas las secciones
        document.querySelectorAll('.content-section').forEach(section => {
            section.classList.remove('active');
        });

        // Mostrar la sección seleccionada
        document.getElementById(seccion + '-section').classList.add('active');

        // Actualizar nav activo
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        event.target.classList.add('active');
    }

    // Actualizar estadísticas del dashboard
    function actualizarDashboard() {
        // Simular datos - aquí harías llamadas AJAX reales
        document.getElementById('total-pacientes').textContent = '1,247';
        document.getElementById('casos-activos').textContent = '89';
        document.getElementById('ingresos-hoy').textContent = '12';
        document.getElementById('casos-urgentes').textContent = '5';

        cargarUltimosIngresos();
    }

    // Cargar últimos ingresos
    function cargarUltimosIngresos() {
        const container = document.getElementById('ultimos-ingresos');

        // Datos simulados - reemplazar con AJAX real
        const ingresos = [{
                paciente: 'Juan Pérez',
                hora: '14:30',
                servicio: 'Emergencias'
            },
            {
                paciente: 'María García',
                hora: '13:15',
                servicio: 'Medicina Interna'
            },
            {
                paciente: 'Carlos López',
                hora: '12:45',
                servicio: 'Cirugía'
            },
            {
                paciente: 'Ana Martínez',
                hora: '11:20',
                servicio: 'Pediatría'
            }
        ];

        let html = '';
        ingresos.forEach(ingreso => {
            html += `
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <strong>${ingreso.paciente}</strong><br>
                            <small class="text-muted">${ingreso.servicio}</small>
                        </div>
                        <span class="badge bg-primary">${ingreso.hora}</span>
                    </div>
                `;
        });

        container.innerHTML = html;
    }

    // Cargar casos activos
    function cargarCasosActivos() {
        const tbody = document.getElementById('tabla-casos-activos');

        // Datos simulados - reemplazar con AJAX real
        const casos = [{
                numero: '2025-000001',
                paciente: 'Juan Pérez',
                cedula: '1234567890',
                fecha: '06/06/2025',
                servicio: 'Emergencias',
                medico: 'Dr. Sistema',
                estado: 'Activo'
            },
            {
                numero: '2025-000002',
                paciente: 'María García',
                cedula: '0987654321',
                fecha: '05/06/2025',
                servicio: 'Medicina Interna',
                medico: 'Dr. Sistema',
                estado: 'Activo'
            }
        ];

        let html = '';
        casos.forEach(caso => {
            html += `
                    <tr>
                        <td><strong>${caso.numero}</strong></td>
                        <td>${caso.paciente}</td>
                        <td>${caso.cedula}</td>
                        <td>${caso.fecha}</td>
                        <td><span class="badge bg-info">${caso.servicio}</span></td>
                        <td>${caso.medico}</td>
                        <td><span class="badge badge-custom bg-success">${caso.estado}</span></td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="verCaso('${caso.numero}')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-success" onclick="editarCaso('${caso.numero}')">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                `;
        });

        tbody.innerHTML = html;
    }

    // Buscar pacientes
    function buscarPacientes() {
        const tipo = document.getElementById('tipo-busqueda').value;
        const termino = document.getElementById('termino-busqueda').value.trim();

        if (!termino) {
            Swal.fire('Error', 'Ingrese un término de búsqueda', 'warning');
            return;
        }

        const container = document.getElementById('resultados-busqueda');
        container.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Buscando...</div>';

        // Simular búsqueda - reemplazar con AJAX real
        setTimeout(() => {
            const resultados = [{
                    nombre: 'Juan Pérez',
                    cedula: '1234567890',
                    historia: '000001'
                },
                {
                    nombre: 'Juan Carlos',
                    cedula: '1234567891',
                    historia: '000002'
                }
            ];

            let html = '';
            if (resultados.length > 0) {
                resultados.forEach(resultado => {
                    html += `
                            <div class="border rounded p-3 mb-2">
                                <strong>${resultado.nombre}</strong><br>
                                <small class="text-muted">
                                    Cédula: ${resultado.cedula} | Historia: ${resultado.historia}
                                </small>
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-primary" onclick="verPaciente('${resultado.cedula}')">
                                        Ver Detalles
                                    </button>
                                </div>
                            </div>
                        `;
                });
            } else {
                html = '<p class="text-muted text-center">No se encontraron resultados</p>';
            }

            container.innerHTML = html;
        }, 1000);
    }

    // Cargar personal
    function cargarPersonal() {
        const tbody = document.getElementById('tabla-personal');

        // Datos simulados - reemplazar con AJAX real
        const personal = [{
            id: 1,
            nombre: 'Dr. Sistema Temporal',
            cedula: '0000000000',
            rol: 'Médico',
            especialidad: 'Medicina General',
            estado: 'Activo',
            casos: 15
        }];

        let html = '';
        personal.forEach(persona => {
            html += `
                    <tr>
                        <td>${persona.id}</td>
                        <td>${persona.nombre}</td>
                        <td>${persona.cedula}</td>
                        <td><span class="badge bg-primary">${persona.rol}</span></td>
                        <td>${persona.especialidad}</td>
                        <td><span class="badge bg-success">${persona.estado}</span></td>
                        <td><span class="badge bg-info">${persona.casos}</span></td>
                    </tr>
                `;
        });

        tbody.innerHTML = html;
    }

    // Inicializar gráficos
    function inicializarGraficos() {
        // Gráfico de servicios
        const ctx1 = document.getElementById('serviciosChart').getContext('2d');
        serviciosChart = new Chart(ctx1, {
            type: 'doughnut',
            data: {
                labels: ['Emergencias', 'Medicina Interna', 'Cirugía', 'Pediatría', 'UCI'],
                datasets: [{
                    data: [30, 25, 20, 15, 10],
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // dashboard_ingreso.js - Funciones JavaScript para el Dashboard de Ingreso

        // Continuar inicialización de gráficos
        function inicializarGraficos() {
            // Gráfico de servicios
            const ctx1 = document.getElementById('serviciosChart').getContext('2d');
            serviciosChart = new Chart(ctx1, {
                type: 'doughnut',
                data: {
                    labels: ['Emergencias', 'Medicina Interna', 'Cirugía', 'Pediatría', 'UCI'],
                    datasets: [{
                        data: [30, 25, 20, 15, 10],
                        backgroundColor: [
                            '#FF6384',
                            '#36A2EB',
                            '#FFCE56',
                            '#4BC0C0',
                            '#9966FF'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Gráfico de ingresos por mes
            const ctx2 = document.getElementById('ingresosMesChart');
            if (ctx2) {
                ingresosMesChart = new Chart(ctx2, {
                    type: 'line',
                    data: {
                        labels: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio'],
                        datasets: [{
                            label: 'Ingresos por Mes',
                            data: [65, 59, 80, 81, 56, 89],
                            borderColor: '#36A2EB',
                            backgroundColor: 'rgba(54, 162, 235, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            // Gráfico de diagnósticos frecuentes
            const ctx3 = document.getElementById('diagnosticosChart');
            if (ctx3) {
                diagnosticosChart = new Chart(ctx3, {
                    type: 'bar',
                    data: {
                        labels: ['Hipertensión', 'Diabetes', 'Neumonía', 'Gastritis', 'Fractura'],
                        datasets: [{
                            label: 'Frecuencia',
                            data: [45, 38, 25, 20, 15],
                            backgroundColor: [
                                '#FF6384',
                                '#36A2EB',
                                '#FFCE56',
                                '#4BC0C0',
                                '#9966FF'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        }

        // Funciones de navegación y acciones
        function verCaso(numeroCaso) {
            Swal.fire({
                title: 'Ver Caso',
                text: `Redirigiendo al caso ${numeroCaso}...`,
                icon: 'info',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                // Redirigir al módulo de procesos
                window.open(`../procesos/evoluciones.php?caso=${numeroCaso}`, '_blank');
            });
        }

        function editarCaso(numeroCaso) {
            Swal.fire({
                title: 'Editar Caso',
                text: `¿Desea editar el caso ${numeroCaso}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, editar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirigir a formulario de edición
                    window.location.href = `editar_caso.php?caso=${numeroCaso}`;
                }
            });
        }

        function verPaciente(cedula) {
            Swal.fire({
                title: 'Ver Paciente',
                text: `Cargando información del paciente con cédula ${cedula}...`,
                icon: 'info',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                // Aquí cargarías los detalles del paciente
                window.open(`../procesos/dashboard_procesos.php?cedula=${cedula}`, '_blank');
            });
        }

        function generarReporte(tipoReporte) {
            Swal.fire({
                title: 'Generando Reporte',
                text: 'Por favor espere...',
                icon: 'info',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Simular generación de reporte
            setTimeout(() => {
                Swal.fire({
                    title: 'Reporte Generado',
                    text: `El reporte de ${tipoReporte.replace('-', ' ')} ha sido generado exitosamente.`,
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: 'Descargar',
                    cancelButtonText: 'Cerrar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Aquí harías la descarga real del reporte
                        downloadReporte(tipoReporte);
                    }
                });
            }, 2000);
        }

        function downloadReporte(tipoReporte) {
            // Simular descarga de archivo
            const link = document.createElement('a');
            link.href = `generar_reporte.php?tipo=${tipoReporte}`;
            link.download = `reporte_${tipoReporte}_${new Date().toISOString().split('T')[0]}.xlsx`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function cerrarSesion() {
            Swal.fire({
                title: '¿Cerrar Sesión?',
                text: 'Se cerrará la sesión actual',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, cerrar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../auth/logout.php';
                }
            });
        }

        // Funciones AJAX reales (reemplazar las simuladas)
        function cargarEstadisticasReales() {
            fetch('obtener_estadisticas.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ok') {
                        document.getElementById('total-pacientes').textContent = data.data.total_pacientes;
                        document.getElementById('casos-activos').textContent = data.data.casos_activos;
                        document.getElementById('ingresos-hoy').textContent = data.data.ingresos_hoy;
                        document.getElementById('casos-urgentes').textContent = data.data.casos_urgentes;
                    }
                })
                .catch(error => {
                    console.error('Error cargando estadísticas:', error);
                });
        }

        function cargarCasosActivosReales() {
            fetch('listar_casos_activos.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ok') {
                        const tbody = document.getElementById('tabla-casos-activos');
                        let html = '';

                        if (data.data.length === 0) {
                            html =
                                '<tr><td colspan="8" class="text-center text-muted">No hay casos activos</td></tr>';
                        } else {
                            data.data.forEach(caso => {
                                const estadoClass = caso.estado === 'activo' ? 'bg-success' : 'bg-warning';
                                html += `
                        <tr>
                            <td><strong>${caso.numero_caso}</strong></td>
                            <td>${caso.nombre_paciente}</td>
                            <td>${caso.cedula}</td>
                            <td>${formatearFecha(caso.fecha_ingreso)}</td>
                            <td><span class="badge bg-info">${caso.servicio}</span></td>
                            <td>${caso.medico_responsable}</td>
                            <td><span class="badge ${estadoClass}">${caso.estado}</span></td>
                            <td>
                                <button class="btn btn-sm btn-primary me-1" onclick="verCaso('${caso.id_caso}')" title="Ver caso">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-success" onclick="editarCaso('${caso.id_caso}')" title="Editar caso">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                            });
                        }

                        tbody.innerHTML = html;
                    }
                })
                .catch(error => {
                    console.error('Error cargando casos activos:', error);
                    document.getElementById('tabla-casos-activos').innerHTML =
                        '<tr><td colspan="8" class="text-center text-danger">Error al cargar los casos</td></tr>';
                });
        }

        function cargarPersonalReal() {
            fetch('obtener_personal.php?tipo=todos')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ok') {
                        const tbody = document.getElementById('tabla-personal');
                        let html = '';

                        if (data.data.length === 0) {
                            html =
                                '<tr><td colspan="7" class="text-center text-muted">No hay personal registrado</td></tr>';
                        } else {
                            data.data.forEach(persona => {
                                const rolClass = persona.rol === 'medico' ? 'bg-primary' : 'bg-success';
                                html += `
                        <tr>
                            <td>${persona.id}</td>
                            <td>${persona.nombre_completo}</td>
                            <td>${persona.cedula}</td>
                            <td><span class="badge ${rolClass}">${persona.rol}</span></td>
                            <td>${persona.especialidad || 'No especificada'}</td>
                            <td><span class="badge bg-success">Activo</span></td>
                            <td><span class="badge bg-info">${persona.casos_asignados || 0}</span></td>
                        </tr>
                    `;
                            });
                        }

                        tbody.innerHTML = html;
                    }
                })
                .catch(error => {
                    console.error('Error cargando personal:', error);
                    document.getElementById('tabla-personal').innerHTML =
                        '<tr><td colspan="7" class="text-center text-danger">Error al cargar el personal</td></tr>';
                });
        }

        function buscarPacientesReal() {
            const tipo = document.getElementById('tipo-busqueda').value;
            const termino = document.getElementById('termino-busqueda').value.trim();

            if (!termino) {
                Swal.fire('Error', 'Ingrese un término de búsqueda', 'warning');
                return;
            }

            const container = document.getElementById('resultados-busqueda');
            container.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Buscando...</div>';

            fetch('buscar_pacientes.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `tipo=${tipo}&termino=${encodeURIComponent(termino)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ok') {
                        let html = '';

                        if (data.data.length === 0) {
                            html = '<p class="text-muted text-center">No se encontraron resultados</p>';
                        } else {
                            data.data.forEach(paciente => {
                                html += `
                        <div class="border rounded p-3 mb-2">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>${paciente.nombres} ${paciente.apellidos}</strong><br>
                                    <small class="text-muted">
                                        Cédula: ${paciente.cedula} | Historia: ${paciente.numero_historia}
                                    </small>
                                    ${paciente.telefono ? `<br><small class="text-muted">Tel: ${paciente.telefono}</small>` : ''}
                                </div>
                                <div>
                                    <button class="btn btn-sm btn-primary me-1" onclick="verPaciente('${paciente.cedula}')" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-success" onclick="nuevoIngresoPaciente('${paciente.id_paciente}')" title="Nuevo ingreso">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                            });
                        }

                        container.innerHTML = html;
                    } else {
                        container.innerHTML = `<p class="text-danger text-center">${data.message}</p>`;
                    }
                })
                .catch(error => {
                    console.error('Error en búsqueda:', error);
                    container.innerHTML = '<p class="text-danger text-center">Error al realizar la búsqueda</p>';
                });
        }

        function nuevoIngresoPaciente(idPaciente) {
            Swal.fire({
                title: 'Nuevo Ingreso',
                text: '¿Desea crear un nuevo ingreso para este paciente?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, crear ingreso',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirigir al formulario de ingreso con el ID del paciente
                    window.location.href = `formulario_ingreso.php?paciente=${idPaciente}`;
                }
            });
        }

        // Funciones de utilidad
        function formatearFecha(fecha) {
            if (!fecha) return '';
            const date = new Date(fecha);
            return date.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        }

        function formatearFechaHora(fechaHora) {
            if (!fechaHora) return '';
            const date = new Date(fechaHora);
            return date.toLocaleString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Funciones de actualización automática
        function iniciarActualizacionAutomatica() {
            // Actualizar estadísticas cada 5 minutos
            setInterval(() => {
                cargarEstadisticasReales();
            }, 300000);

            // Actualizar casos activos cada 2 minutos
            setInterval(() => {
                if (document.getElementById('casos-activos-section').classList.contains('active')) {
                    cargarCasosActivosReales();
                }
            }, 120000);
        }

        // Event listeners adicionales
        document.addEventListener('keydown', function(e) {
            // Atajos de teclado
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                mostrarSeccion('nuevo-ingreso');
            }

            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                mostrarSeccion('buscar-paciente');
                document.getElementById('termino-busqueda').focus();
            }
        });

        // Inicializar cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar tooltips de Bootstrap
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Iniciar actualizaciones automáticas
            iniciarActualizacionAutomatica();

            // Configurar búsqueda en tiempo real
            const terminoBusqueda = document.getElementById('termino-busqueda');
            if (terminoBusqueda) {
                let timeoutId;
                terminoBusqueda.addEventListener('input', function() {
                    clearTimeout(timeoutId);
                    timeoutId = setTimeout(() => {
                        if (this.value.length >= 3) {
                            buscarPacientesReal();
                        }
                    }, 500);
                });

                // Buscar al presionar Enter
                terminoBusqueda.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        buscarPacientesReal();
                    }
                });
            }
        });

        // Función para exportar datos
        function exportarDatos(formato, tipo) {
            const url = `exportar_datos.php?formato=${formato}&tipo=${tipo}`;

            Swal.fire({
                title: 'Exportando datos',
                text: 'Generando archivo...',
                icon: 'info',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(url)
                .then(response => response.blob())
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `${tipo}_${new Date().toISOString().split('T')[0]}.${formato}`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);

                    Swal.fire('Éxito', 'Archivo descargado correctamente', 'success');
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'No se pudo exportar los datos', 'error');
                });
        }

        // Funciones de notificaciones en tiempo real
        function iniciarNotificaciones() {
            // Simular notificaciones de nuevos ingresos
            setInterval(() => {
                if (Math.random() > 0.95) { // 5% de probabilidad cada minuto
                    mostrarNotificacion('Nuevo ingreso registrado', 'info');
                }
            }, 60000);
        }

        function mostrarNotificacion(mensaje, tipo = 'info') {
            // Crear notificación toast
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${tipo} border-0`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-info-circle me-2"></i>${mensaje}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;

            // Agregar al container de toasts (crear si no existe)
            let toastContainer = document.getElementById('toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.id = 'toast-container';
                toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
                document.body.appendChild(toastContainer);
            }

            toastContainer.appendChild(toast);

            // Mostrar toast
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();

            // Remover del DOM después de que se oculte
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }