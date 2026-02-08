<?php
// modulos/procesos/signos_vitales.php
require_once '../../config/forms/database.php';
require_once '../../config/forms/functions.php';

verificarSesion();

$id_caso = $_GET['caso'] ?? 0;
if (!$id_caso) {
    die('Caso no especificado');
}

// Obtener datos del caso
$stmt = $pdo->prepare("
    SELECT cc.numero_caso, CONCAT(p.nombres, ' ', p.apellidos) as nombre_paciente
    FROM casos_clinicos cc
    INNER JOIN pacientes p ON cc.id_paciente = p.id_paciente
    WHERE cc.id_caso = ?
");
$stmt->execute([$id_caso]);
$caso = $stmt->fetch();

// 🆕 OBTENER ENFERMERAS DISPONIBLES
$stmt = $pdo->prepare("
    SELECT id, CONCAT(nombres, ' ', apellidos) as nombre_completo, especialidad
    FROM personal 
    WHERE rol = 'Enfermería' AND activo = 1
    ORDER BY nombres, apellidos
");
$stmt->execute();
$enfermeras = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signos Vitales - <?= $caso['nombre_paciente'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .enfermera-selector {
            border: 2px solid #28a745;
            border-radius: 8px;
            background: #f8fff8;
        }

        .enfermera-info {
            font-size: 0.85em;
            color: #6c757d;
        }
    </style>
</head>

<body>
    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3><i class="fas fa-heartbeat me-2"></i>Signos Vitales - <?= $caso['nombre_paciente'] ?></h3>
            <a href="dashboard_procesos.php?caso=<?= $id_caso ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>

        <div class="row">
            <!-- Formulario de signos vitales -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-plus me-2"></i>Registrar Signos</h5>
                    </div>
                    <div class="card-body">
                        <form id="formSignos">
                            <input type="hidden" name="id_caso" value="<?= $id_caso ?>">

                            <!-- 🆕 SELECTOR DE ENFERMERA RESPONSABLE -->
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-user-nurse text-success me-2"></i>Enfermera Responsable: *
                                </label>
                                <select class="form-control enfermera-selector" name="id_enfermero" required>
                                    <option value="">Seleccionar enfermera...</option>
                                    <?php foreach ($enfermeras as $enfermera): ?>
                                        <option value="<?= $enfermera['id'] ?>"
                                            data-especialidad="<?= $enfermera['especialidad'] ?>">
                                            Enf. <?= $enfermera['nombre_completo'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="enfermera-info" id="enfermeraInfo"></small>
                            </div>

                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Turno:</label>
                                    <select class="form-control" name="turno" required>
                                        <option value="mañana">Mañana</option>
                                        <option value="tarde">Tarde</option>
                                        <option value="noche">Noche</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Temperatura (°C):</label>
                                    <input type="number" class="form-control" name="temperatura" step="0.1" min="35"
                                        max="42">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Pulso (FC):</label>
                                    <input type="number" class="form-control" name="pulso" min="40" max="200">
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Sistólica:</label>
                                    <input type="number" class="form-control" name="presion_sistolica" min="70"
                                        max="250">
                                </div>
                            </div>

                            <div class="row">

                                <div class="col-6 mb-3">
                                    <label class="form-label">Diastólica:</label>
                                    <input type="number" class="form-control" name="presion_diastolica" min="40"
                                        max="150">
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">SatO2 (%):</label>
                                    <input type="number" class="form-control" name="saturacion_oxigeno" step="0.1"
                                        min="70" max="100">
                                </div>
                            </div>

                            <div class="row">

                                <div class="col-6 mb-3">
                                    <label class="form-label">Dolor (1-10):</label>
                                    <input type="number" class="form-control" name="dolor" min="0" max="10">
                                </div>

                                <div class="col-6 mb-3">
                                    <label class="form-label">Observaciones:</label>
                                    <textarea class="form-control" name="observaciones" min="0" max="10"></textarea>
                                </div>
                            </div>


                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save me-2"></i>Guardar Signos
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tabla de signos vitales -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-table me-2"></i>Registro de Signos Vitales</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm" id="tablaSignos">
                                <thead>
                                    <tr>
                                        <th>Fecha/Hora</th>
                                        <th>Turno</th>
                                        <th>T°</th>
                                        <th>Pulso</th>
                                        <th>PA</th>
                                        <th>SatO2</th>
                                        <th>Dolor</th>
                                        <th>Enfermero</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Se carga dinámicamente -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Gráfico de tendencias -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-line me-2"></i>Tendencias</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="graficoSignos" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let chartSignos;

        document.addEventListener('DOMContentLoaded', function () {
            cargarSignosVitales();
            inicializarGrafico();

            // Detectar turno actual
            const hora = new Date().getHours();
            let turno = 'mañana';
            if (hora >= 14 && hora < 22) turno = 'tarde';
            else if (hora >= 22 || hora < 6) turno = 'noche';

            document.querySelector('[name="turno"]').value = turno;

            // 🆕 MANEJAR CAMBIO DE ENFERMERA
            const selectEnfermera = document.querySelector('[name="id_enfermero"]');
            selectEnfermera.addEventListener('change', function () {
                const selectedOption = this.options[this.selectedIndex];
                const especialidad = selectedOption.getAttribute('data-especialidad');
                const enfermeraInfo = document.getElementById('enfermeraInfo');

                if (especialidad && this.value) {
                    enfermeraInfo.textContent = `Especialidad: ${especialidad}`;
                    enfermeraInfo.style.display = 'block';
                } else {
                    enfermeraInfo.style.display = 'none';
                }
            });
        });

        // Cargar signos vitales
        function cargarSignosVitales() {
            fetch('listar_signos.php?caso=<?= $id_caso ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ok') {
                        mostrarTablaSignos(data.data);
                        actualizarGrafico(data.data);
                    }
                });
        }

        // Mostrar tabla de signos
        function mostrarTablaSignos(signos) {
            const tbody = document.querySelector('#tablaSignos tbody');

            if (signos.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No hay registros</td></tr>';
                return;
            }

            let html = '';
            signos.forEach(signo => {
                const pa = signo.presion_sistolica && signo.presion_diastolica ?
                    `${signo.presion_sistolica}/${signo.presion_diastolica}` : '-';

                html += `
                <tr>
                    <td>${signo.fecha_registro}</td>
                    <td><span class="badge bg-secondary">${signo.turno}</span></td>
                    <td>${signo.temperatura || '-'}</td>
                    <td>${signo.pulso || '-'}</td>
                    <td>${pa}</td>
                    <td>${signo.saturacion_oxigeno || '-'}</td>
                    <td>${signo.dolor || '-'}</td>
                    <td>${signo.enfermero}</td>
                </tr>
            `;
            });

            tbody.innerHTML = html;
        }

        // Inicializar gráfico
        function inicializarGrafico() {
            const ctx = document.getElementById('graficoSignos').getContext('2d');
            chartSignos = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Temperatura (°C)',
                        data: [],
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        yAxisID: 'y',
                        tension: 0.4
                    },
                    {
                        label: 'Pulso (FC)',
                        data: [],
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        yAxisID: 'y1',
                        tension: 0.4
                    },
                    {
                        label: 'Sistólica (mmHg)',
                        data: [],
                        borderColor: 'rgb(255, 205, 86)',
                        backgroundColor: 'rgba(255, 205, 86, 0.2)',
                        yAxisID: 'y1',
                        tension: 0.4
                    },
                    {
                        label: 'SatO2 (%)',
                        data: [],
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        yAxisID: 'y2',
                        tension: 0.4
                    }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
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
                        },
                        y2: {
                            type: 'linear',
                            display: false,
                            min: 85,
                            max: 100
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Tendencia de Signos Vitales'
                        },
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    }
                }
            });
        }

        // Actualizar gráfico con nuevos datos
        function actualizarGrafico(signos) {
            if (!chartSignos || signos.length === 0) return;

            // Tomar solo los últimos 20 registros para el gráfico
            const signosRecientes = signos.slice(0, 20).reverse();

            const labels = signosRecientes.map(s => {
                const fecha = new Date(s.fecha_registro.replace(/(\d{2})\/(\d{2})\/(\d{4}) (\d{2}):(\d{2})/,
                    '$3-$2-$1T$4:$5'));
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
            const saturaciones = signosRecientes.map(s => s.saturacion_oxigeno ? parseFloat(s.saturacion_oxigeno) : null);

            chartSignos.data.labels = labels;
            chartSignos.data.datasets[0].data = temperaturas;
            chartSignos.data.datasets[1].data = pulsos;
            chartSignos.data.datasets[2].data = sistolicas;
            chartSignos.data.datasets[3].data = saturaciones;

            chartSignos.update();
        }

        // Enviar formulario - CORREGIDO
        document.getElementById('formSignos').addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);

            // 🆕 VALIDAR ENFERMERA RESPONSABLE
            const idEnfermero = formData.get('id_enfermero');
            if (!idEnfermero) {
                Swal.fire('Error', 'Debe seleccionar la enfermera responsable', 'error');
                return;
            }

            // Verificar valores críticos antes de enviar
            const datos = Object.fromEntries(formData);
            verificarValoresCriticos(datos);

            fetch('guardar_signos.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ok') {
                        Swal.fire('Éxito', 'Signos vitales guardados', 'success');
                        this.reset();
                        cargarSignosVitales();

                        // Resetear el turno al actual
                        const hora = new Date().getHours();
                        let turno = 'mañana';
                        if (hora >= 14 && hora < 22) turno = 'tarde';
                        else if (hora >= 22 || hora < 6) turno = 'noche';
                        document.querySelector('[name="turno"]').value = turno;
                        document.getElementById('enfermeraInfo').style.display = 'none';
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    Swal.fire('Error', 'Error de conexión', 'error');
                });
        });

        // Función para mostrar alertas de valores críticos
        function verificarValoresCriticos(datos) {
            const alertas = [];

            if (datos.temperatura) {
                const temp = parseFloat(datos.temperatura);
                if (temp >= 38.5) alertas.push('Fiebre alta detectada');
                if (temp <= 35.5) alertas.push('Hipotermia detectada');
            }

            if (datos.saturacion_oxigeno) {
                const sat = parseFloat(datos.saturacion_oxigeno);
                if (sat < 90) alertas.push('Saturación de oxígeno crítica');
            }

            if (datos.presion_sistolica && datos.presion_diastolica) {
                const sis = parseInt(datos.presion_sistolica);
                const dia = parseInt(datos.presion_diastolica);
                if (sis >= 180 || dia >= 110) alertas.push('Crisis hipertensiva');
                if (sis <= 90 || dia <= 60) alertas.push('Hipotensión detectada');
            }

            if (datos.pulso) {
                const pulso = parseInt(datos.pulso);
                if (pulso >= 120) alertas.push('Taquicardia detectada');
                if (pulso <= 50) alertas.push('Bradicardia detectada');
            }

            if (alertas.length > 0) {
                Swal.fire({
                    title: 'Valores Críticos Detectados',
                    html: alertas.map(alerta => `• ${alerta}`).join('<br>'),
                    icon: 'warning',
                    confirmButtonText: 'Entendido'
                });
            }
        }
    </script>
</body>

</html>