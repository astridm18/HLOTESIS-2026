<?php
// modulos/procesos/laboratorios.php
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

// 🆕 OBTENER MÉDICOS DISPONIBLES
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
    <title>Laboratorios - <?= $caso['nombre_paciente'] ?></title>
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
            <h3><i class="fas fa-vial me-2"></i>Laboratorios - <?= $caso['nombre_paciente'] ?></h3>
            <a href="dashboard_procesos.php?caso=<?= $id_caso ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>

        <div class="row">
            <!-- Formulario solicitar examen -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-plus me-2"></i>Solicitar Examen</h5>
                    </div>
                    <div class="card-body">
                        <form id="formSolicitud">
                            <input type="hidden" name="id_caso" value="<?= $id_caso ?>">

                            <!-- 🆕 SELECTOR DE MÉDICO SOLICITANTE -->
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-user-md text-primary me-2"></i>Médico Solicitante: *
                                </label>
                                <select class="form-control medico-selector" name="id_medico_solicita" required>
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
                                <label class="form-label">Tipo de Examen: *</label>
                                <select class="form-control" name="tipo_examen" required>
                                    <option value="">Seleccionar</option>
                                    <option value="Hematología">Hematología</option>
                                    <option value="Bioquímica">Bioquímica</option>
                                    <option value="Microbiología">Microbiología</option>
                                    <option value="Inmunología">Inmunología</option>
                                    <option value="Orina">Orina</option>
                                    <option value="Heces">Heces</option>
                                    <option value="Gasometría">Gasometría</option>
                                    <option value="Coagulación">Coagulación</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Exámenes Específicos: *</label>
                                <textarea class="form-control" name="examenes_solicitados" rows="4"
                                    placeholder="Ej: Hemograma completo, Glucosa, Creatinina..." required></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Indicaciones Clínicas:</label>
                                <textarea class="form-control" name="indicaciones" rows="3"
                                    placeholder="Motivo clínico del examen..."></textarea>
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

            <!-- Lista de exámenes -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-list me-2"></i>Exámenes de Laboratorio</h5>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary active" onclick="filtrarLab('todos')">Todos</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="listaLaboratorios">
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
        cargarLaboratorios();

        // 🆕 MANEJAR CAMBIO DE MÉDICO
        const selectMedico = document.querySelector('[name="id_medico_solicita"]');
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

    // Cargar laboratorios
    function cargarLaboratorios() {
        const url = filtroActual === 'todos' ?
            'listar_laboratorios.php?caso=<?= $id_caso ?>' :
            `listar_laboratorios.php?caso=<?= $id_caso ?>&estado=${filtroActual}`;

        console.log('Cargando laboratorios desde:', url); // 🔧 DEBUG

        fetch(url)
            .then(response => {
                console.log('Response status:', response.status); // 🔧 DEBUG
                return response.json();
            })
            .then(data => {
                console.log('Datos recibidos:', data); // 🔧 DEBUG
                if (data.status === 'ok') {
                    mostrarLaboratorios(data.data);
                } else {
                    document.getElementById('listaLaboratorios').innerHTML =
                        `<div class="alert alert-warning">Error: ${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('listaLaboratorios').innerHTML =
                    `<div class="alert alert-danger">Error de conexión: ${error.message}</div>`;
            });
    }

    // Mostrar laboratorios
    function mostrarLaboratorios(laboratorios) {
        const container = document.getElementById('listaLaboratorios');

        console.log('Mostrando laboratorios:', laboratorios); // 🔧 DEBUG

        if (!laboratorios || laboratorios.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-vial fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No hay exámenes registrados</p>
                    <p class="small text-muted">Complete el formulario para solicitar el primer examen</p>
                </div>
            `;
            return;
        }

        let html = '';
        laboratorios.forEach(lab => {
            const estadoColor = {
                'solicitado': 'warning',
                'en_proceso': 'info',
                'informado': 'success',
                'entregado': 'secondary'
            };

            // Determinar el estado a mostrar (simplificado)
            let estadoMostrar = lab.fecha_resultado ? 'Con resultado' : 'Solicitado';
            let colorEstado = lab.fecha_resultado ? 'success' : 'warning';

            html += `
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="mb-1">
                                <i class="fas fa-vial me-2"></i>${lab.tipo_examen}
                                ${lab.urgente == 1 ? '<i class="fas fa-exclamation-triangle text-warning ms-2"></i>' : ''}
                            </h6>
                            <small class="text-muted">${lab.fecha_solicitud} - Dr. ${lab.medico_solicita || 'No especificado'}</small>
                        </div>
                        <span class="badge bg-${colorEstado}">${estadoMostrar}</span>
                    </div>
                    
                    <p class="mb-2"><strong>Exámenes:</strong> ${lab.parametro || lab.orden_descripcion || 'No especificado'}</p>
                    ${lab.indicaciones ? `<p class="mb-2 text-muted"><strong>Indicaciones:</strong> ${lab.indicaciones}</p>` : ''}
                    
                    ${lab.fecha_resultado ? `
                        <div class="bg-light p-2 rounded">
                            <strong>Resultado:</strong> ${lab.fecha_resultado}<br>
                            ${lab.estado ? `<span class="badge bg-${lab.estado === 'normal' ? 'success' : lab.estado === 'anormal' ? 'warning' : 'danger'}">${lab.estado}</span>` : ''}
                            ${lab.resultado ? `<br>${lab.resultado}` : ''}
                            ${lab.archivo_resultado ? `<br><a href="../../uploads/laboratorios/${lab.archivo_resultado}" target="_blank" class="btn btn-sm btn-outline-primary mt-1"><i class="fas fa-file-pdf"></i> Ver Resultado</a>` : ''}
                        </div>
                    ` : ''}
                </div>
            `;
        });

        container.innerHTML = html;
    }

    // Filtrar laboratorios
    function filtrarLab(estado) {
        filtroActual = estado;
        cargarLaboratorios();

        // Actualizar botones activos
        document.querySelectorAll('.btn-group button').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');
    }

    // Enviar solicitud - CORREGIDO
    document.getElementById('formSolicitud').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        // 🔧 DEBUG: Verificar datos antes de enviar
        console.log('Datos del formulario:');
        for (let [key, value] of formData.entries()) {
            console.log(key + ': ' + value);
        }

        // 🆕 VALIDAR MÉDICO SOLICITANTE
        const idMedico = formData.get('id_medico_solicita');
        if (!idMedico) {
            Swal.fire('Error', 'Debe seleccionar el médico solicitante', 'error');
            return;
        }

        // Validar campos requeridos
        const tipoExamen = formData.get('tipo_examen');
        const examenes = formData.get('examenes_solicitados').trim();

        if (!tipoExamen) {
            Swal.fire('Error', 'Debe seleccionar el tipo de examen', 'error');
            return;
        }

        if (!examenes) {
            Swal.fire('Error', 'Debe especificar los exámenes solicitados', 'error');
            return;
        }

        fetch('solicitar_laboratorio.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ok') {
                    Swal.fire('Éxito', 'Examen solicitado correctamente', 'success');
                    this.reset();
                    document.getElementById('medicoInfo').style.display = 'none';
                    cargarLaboratorios();
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