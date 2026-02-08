<?php
// modulos/procesos/interconsultas.php
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

// OBTENER MÉDICOS DISPONIBLES para el selector de Médico Solicitante y Consultor
$stmt = $pdo->prepare("
    SELECT id, CONCAT(nombres, ' ', apellidos) as nombre_completo, especialidad
    FROM personal 
    WHERE rol = 'Medicina' AND activo = 1
    ORDER BY nombres, apellidos
");
$stmt->execute();
$medicos = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interconsultas - <?= $caso['nombre_paciente'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3><i class="fas fa-user-md me-2"></i>Interconsultas - <?= $caso['nombre_paciente'] ?></h3>
            <a href="dashboard_procesos.php?caso=<?= $id_caso ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-plus me-2"></i>Solicitar Interconsulta</h5>
                    </div>
                    <div class="card-body">
                        <form id="formInterconsulta">
                            <input type="hidden" name="id_caso" value="<?= $id_caso ?>">

                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-user-doctor text-primary me-2"></i>Médico Solicitante: *
                                </label>
                                <select class="form-control medico-selector" name="id_medico_solicitante" required>
                                    <option value="">Seleccionar médico...</option>
                                    <?php foreach ($medicos as $medico): ?>
                                    <option value="<?= $medico['id'] ?>"
                                        data-especialidad="<?= $medico['especialidad'] ?>">
                                        Dr. <?= $medico['nombre_completo'] ?> - (<?= $medico['especialidad'] ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="medico-info" id="medicoSolicitanteInfo"></small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Servicio Solicitante: *</label>
                                <input type="text" class="form-control" name="servicio_solicitante"
                                    placeholder="Ej: Medicina Interna" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Especialidad/Servicio Consultado: *</label>
                                <input type="text" class="form-control" name="especialidad"
                                    placeholder="Ej: Cardiología" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Motivo de la Consulta: *</label>
                                <textarea class="form-control" name="motivo_consulta" rows="4"
                                    placeholder="Detalle el motivo de la interconsulta..." required></textarea>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="urgente" value="1">
                                    <label class="form-check-label">
                                        <i class="fas fa-exclamation-triangle text-warning"></i> Urgente
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane me-2"></i>Enviar Solicitud
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-list me-2"></i>Interconsultas Realizadas</h5>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary active"
                                onclick="filtrarInterconsultas('todos')">Todas</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="listaInterconsultas">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let filtroActual = 'todos';

    document.addEventListener('DOMContentLoaded', function() {
        cargarInterconsultas();

        // MANEJAR CAMBIO DE MÉDICO SOLICITANTE
        const selectMedicoSolicitante = document.querySelector('[name="id_medico_solicitante"]');
        selectMedicoSolicitante.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const especialidad = selectedOption.getAttribute('data-especialidad');
            const medicoInfo = document.getElementById('medicoSolicitanteInfo');

            if (especialidad && this.value) {
                medicoInfo.textContent = `Especialidad: ${especialidad}`;
                medicoInfo.style.display = 'block';
            } else {
                medicoInfo.style.display = 'none';
            }
        });
    });

    // Cargar interconsultas
    function cargarInterconsultas() {
        // La URL siempre cargará 'todos' los resultados si no hay un filtro específico
        const url = 'listar_interconsultas.php?caso=<?= $id_caso ?>&estado=todos';

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ok') {
                    mostrarInterconsultas(data.data);
                } else {
                    document.getElementById('listaInterconsultas').innerHTML =
                        `<div class="alert alert-warning">Error: ${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('listaInterconsultas').innerHTML =
                    `<div class="alert alert-danger">Error de conexión: ${error.message}</div>`;
            });
    }

    // Mostrar interconsultas
    function mostrarInterconsultas(interconsultas) {
        const container = document.getElementById('listaInterconsultas');

        if (!interconsultas || interconsultas.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-user-md fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No hay interconsultas registradas</p>
                    <p class="small text-muted">Complete el formulario para solicitar una interconsulta</p>
                </div>
            `;
            return;
        }

        let html = '';
        interconsultas.forEach(ic => {
            const estadoColor = {
                'solicitada': 'warning',
                'en_revision': 'info',
                'respondida': 'success',
                'cerrada': 'secondary'
            };

            html += `
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="mb-1">
                                <i class="fas fa-user-md me-2"></i>Interconsulta a ${ic.especialidad}
                                ${ic.urgente == 1 ? '<i class="fas fa-exclamation-triangle text-danger ms-2"></i>' : ''}
                            </h6>
                            <small class="text-muted">${ic.fecha_solicitud} - Solicitada por Dr. ${ic.medico_solicitante}</small>
                        </div>
                        <span class="badge bg-${estadoColor[ic.estado] || 'secondary'}">${ic.estado.replace('_', ' ').toUpperCase()}</span>
                    </div>
                    
                    <p class="mb-2"><strong>Motivo:</strong> ${ic.motivo_consulta}</p>
                    
                    ${ic.fecha_respuesta ? `
                        <div class="bg-light p-2 rounded mt-2">
                            <strong>Respuesta (Dr. ${ic.medico_consultor || 'N/A'} - ${ic.fecha_respuesta}):</strong><br>
                            ${ic.respuesta ? `<p class="mb-1">${ic.respuesta}</p>` : ''}
                            ${ic.recomendaciones ? `<p class="mb-1"><strong>Recomendaciones:</strong> ${ic.recomendaciones}</p>` : ''}
                        </div>
                    ` : ''}

                    </div>
            `;
        });

        container.innerHTML = html;
    }

    // Esta función `filtrarInterconsultas` ya no es necesaria con un solo botón, pero la mantengo por si acaso.
    function filtrarInterconsultas(estado) {
        filtroActual = estado; // Siempre será 'todos' ahora
        cargarInterconsultas();

        // Actualizar botones activos
        document.querySelectorAll('.btn-group button').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');
    }

    // Enviar solicitud de interconsulta
    document.getElementById('formInterconsulta').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        // Validar médico solicitante
        const idMedicoSolicitante = formData.get(
        'id_medico_solicitante'); // Corregido: usar 'id_medico_solicitante'
        if (!idMedicoSolicitante) {
            Swal.fire('Error', 'Debe seleccionar el Médico Solicitante', 'error');
            return;
        }

        fetch('solicitar_interconsulta.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ok') {
                    Swal.fire('Éxito', 'Interconsulta solicitada correctamente', 'success');
                    this.reset();
                    document.getElementById('medicoSolicitanteInfo').style.display = 'none';
                    cargarInterconsultas();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Error de conexión', 'error');
            });
    });

    // La función `verInterconsulta` se ha eliminado del HTML, por lo que ya no es necesaria.
    </script>
</body>

</html>