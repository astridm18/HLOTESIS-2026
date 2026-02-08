<?php
// modulos/procesos/procedimientos.php
require_once '../../config/database.php';
require_once '../../config/functions.php';

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

// OBTENER MÉDICOS DISPONIBLES para el selector de Médico Principal y Asistente
$stmt = $pdo->prepare("
    SELECT id, CONCAT(nombres, ' ', apellidos) as nombre_completo, especialidad
    FROM personal 
    WHERE rol = 'Medicina' AND activo = 1
    ORDER BY nombres, apellidos
");
$stmt->execute();
$medicos = $stmt->fetchAll();

// OBTENER ENFERMEROS DISPONIBLES para el selector de Enfermero
$stmt = $pdo->prepare("
    SELECT id, CONCAT(nombres, ' ', apellidos) as nombre_completo
    FROM personal 
    WHERE rol = 'Enfermería' AND activo = 1
    ORDER BY nombres, apellidos
");
$stmt->execute();
$enfermeros = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procedimientos - <?= $caso['nombre_paciente'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    .medico-selector,
    .enfermero-selector {
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
            <h3><i class="fas fa-tools me-2"></i>Procedimientos - <?= $caso['nombre_paciente'] ?></h3>
            <a href="dashboard_procesos.php?caso=<?= $id_caso ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-plus me-2"></i>Registrar Procedimiento</h5>
                    </div>
                    <div class="card-body">
                        <form id="formProcedimiento">
                            <input type="hidden" name="id_caso" value="<?= $id_caso ?>">

                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-user-doctor text-primary me-2"></i>Médico Principal: *
                                </label>
                                <select class="form-control medico-selector" name="id_medico_principal" required>
                                    <option value="">Seleccionar médico...</option>
                                    <?php foreach ($medicos as $medico): ?>
                                    <option value="<?= $medico['id'] ?>"
                                        data-especialidad="<?= $medico['especialidad'] ?>">
                                        Dr. <?= $medico['nombre_completo'] ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="medico-info" id="medicoPrincipalInfo"></small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tipo de Procedimiento: *</label>
                                <select class="form-control" name="tipo_procedimiento" required>
                                    <option value="">Seleccionar</option>
                                    <option value="Punción lumbar">Punción lumbar</option>
                                    <option value="Biopsia">Biopsia</option>
                                    <option value="Drenaje">Drenaje</option>
                                    <option value="Cateterización">Cateterización</option>
                                    <option value="Intubación">Intubación</option>
                                    <option value="Cardioversión">Cardioversión</option>
                                    <option value="Paracentesis">Paracentesis</option>
                                    <option value="Toracentesis">Toracentesis</option>
                                    <option value="Sutura">Sutura</option>
                                    <option value="Curación">Curación</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Descripción: *</label>
                                <textarea class="form-control" name="descripcion" rows="3"
                                    placeholder="Descripción detallada del procedimiento..." required></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Indicaciones:</label>
                                <textarea class="form-control" name="indicaciones" rows="2"
                                    placeholder="Indicaciones médicas para el procedimiento..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Técnica Utilizada:</label>
                                <textarea class="form-control" name="tecnica" rows="3"
                                    placeholder="Descripción de la técnica empleada..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Material Utilizado:</label>
                                <textarea class="form-control" name="material_utilizado" rows="2"
                                    placeholder="Equipos y materiales empleados..."></textarea>
                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <label class="form-label">Duración (min):</label>
                                    <input type="number" class="form-control" name="duracion_minutos" min="1">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Anestesia:</label>
                                    <select class="form-control" name="anestesia">
                                        <option value="">Sin anestesia</option>
                                        <option value="Local">Local</option>
                                        <option value="Regional">Regional</option>
                                        <option value="Sedación">Sedación</option>
                                        <option value="General">General</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3 mt-3">
                                <label class="form-label">Médico Asistente:</label>
                                <select class="form-control" name="id_medico_asistente">
                                    <option value="">Seleccionar</option>
                                    <?php foreach ($medicos as $medico): ?>
                                    <option value="<?= $medico['id'] ?>">Dr. <?= $medico['nombre_completo'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Enfermero:</label>
                                <select class="form-control enfermero-selector" name="id_enfermero">
                                    <option value="">Seleccionar</option>
                                    <?php foreach ($enfermeros as $enfermero): ?>
                                    <option value="<?= $enfermero['id'] ?>"><?= $enfermero['nombre_completo'] ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save me-2"></i>Registrar Procedimiento
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-list me-2"></i>Procedimientos Realizados</h5>
                    </div>
                    <div class="card-body">
                        <div id="listaProcedimientos">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        cargarProcedimientos();
        // cargarPersonal() ya no es necesario llamarlo aquí si se imprimen directamente en PHP

        // MANEJAR CAMBIO DE MÉDICO PRINCIPAL
        const selectMedicoPrincipal = document.querySelector('[name="id_medico_principal"]');
        selectMedicoPrincipal.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const especialidad = selectedOption.getAttribute('data-especialidad');
            const medicoInfo = document.getElementById('medicoPrincipalInfo');

            if (especialidad && this.value) {
                medicoInfo.textContent = `Especialidad: ${especialidad}`;
                medicoInfo.style.display = 'block';
            } else {
                medicoInfo.style.display = 'none';
            }
        });
    });

    // Cargar procedimientos
    function cargarProcedimientos() {
        fetch('listar_procedimientos.php?caso=<?= $id_caso ?>')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ok') {
                    mostrarProcedimientos(data.data);
                } else {
                    document.getElementById('listaProcedimientos').innerHTML =
                        `<div class="alert alert-warning">Error: ${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('listaProcedimientos').innerHTML =
                    `<div class="alert alert-danger">Error de conexión: ${error.message}</div>`;
            });
    }

    // Mostrar procedimientos
    function mostrarProcedimientos(procedimientos) {
        const container = document.getElementById('listaProcedimientos');

        if (procedimientos.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-tools fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No hay procedimientos registrados</p>
                    <p class="small text-muted">Complete el formulario para registrar el primer procedimiento</p>
                </div>
            `;
            return;
        }

        let html = '';
        procedimientos.forEach(proc => {
            html += `
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="mb-0">
                                <i class="fas fa-tools me-2"></i>${proc.tipo_procedimiento}
                            </h6>
                            <small class="text-muted">${proc.fecha_procedimiento}</small>
                        </div>
                        
                        <p class="mb-2"><strong>Descripción:</strong> ${proc.descripcion}</p>
                        
                        ${proc.indicaciones ? `<p class="mb-2"><strong>Indicaciones:</strong> ${proc.indicaciones}</p>` : ''}
                        
                        ${proc.tecnica ? `<p class="mb-2"><strong>Técnica:</strong> ${proc.tecnica}</p>` : ''}
                        
                        ${proc.resultados ? `<p class="mb-2"><strong>Resultados:</strong> ${proc.resultados}</p>` : ''}
                        
                        ${proc.complicaciones ? `<p class="mb-2 text-warning"><strong>Complicaciones:</strong> ${proc.complicaciones}</p>` : ''}
                        
                        <div class="row mt-2">
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <strong>Médico Principal:</strong> Dr. ${proc.medico_principal}<br>
                                    ${proc.medico_asistente ? `<strong>Médico Asistente:</strong> Dr. ${proc.medico_asistente}<br>` : ''}
                                    ${proc.enfermero ? `<strong>Enfermero:</strong> ${proc.enfermero}` : ''}
                                </small>
                            </div>
                            <div class="col-md-6 text-end">
                                <small class="text-muted">
                                    ${proc.duracion_minutos ? `<strong>Duración:</strong> ${proc.duracion_minutos} min<br>` : ''}
                                    ${proc.anestesia ? `<strong>Anestesia:</strong> ${proc.anestesia}` : ''}
                                </small>
                            </div>
                        </div>
                    </div>
                `;
        });

        container.innerHTML = html;
    }

    // Enviar formulario
    document.getElementById('formProcedimiento').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        // Validar médico principal
        const idMedicoPrincipal = formData.get('id_medico_principal');
        if (!idMedicoPrincipal) {
            Swal.fire('Error', 'Debe seleccionar el Médico Principal', 'error');
            return;
        }

        fetch('guardar_procedimiento.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ok') {
                    Swal.fire('Éxito', 'Procedimiento registrado correctamente', 'success');
                    this.reset();
                    document.getElementById('medicoPrincipalInfo').style.display =
                    'none'; // Ocultar info del médico
                    cargarProcedimientos();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Error de conexión', 'error');
            });
    });
    </script>
</body>

</html>