<?php
// modulos/procesos/imagenes.php
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

// OBTENER MÉDICOS DISPONIBLES
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
    <title>Estudios de Imagen - <?= $caso['nombre_paciente'] ?></title>
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
            <h3><i class="fas fa-x-ray me-2"></i>Estudios de Imagen - <?= $caso['nombre_paciente'] ?></h3>
            <a href="dashboard_procesos.php?caso=<?= $id_caso ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-plus me-2"></i>Solicitar Estudio</h5>
                    </div>
                    <div class="card-body">
                        <form id="formEstudio">
                            <input type="hidden" name="id_caso" value="<?= $id_caso ?>">

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
                                <label class="form-label">Tipo de Estudio: *</label>
                                <select class="form-control" name="tipo_estudio" required>
                                    <option value="">Seleccionar</option>
                                    <option value="Radiografía">Radiografía</option>
                                    <option value="Ecografía">Ecografía</option>
                                    <option value="Tomografía">Tomografía (TAC)</option>
                                    <option value="Resonancia">Resonancia Magnética</option>
                                    <option value="Mamografía">Mamografía</option>
                                    <option value="Densitometría">Densitometría</option>
                                    <option value="Angiografía">Angiografía</option>
                                    <option value="Fluoroscopia">Fluoroscopia</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Región Anatómica: *</label>
                                <input type="text" class="form-control" name="region_anatomica"
                                    placeholder="Ej: Tórax PA y lateral" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Técnica Específica:</label>
                                <input type="text" class="form-control" name="tecnica"
                                    placeholder="Ej: Simple, con contraste">
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="contraste" value="1">
                                    <label class="form-check-label">
                                        Requiere contraste
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Indicación Clínica: *</label>
                                <textarea class="form-control" name="indicacion_clinica" rows="3"
                                    placeholder="Motivo del estudio..." required></textarea>
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
                        <h5><i class="fas fa-list me-2"></i>Estudios de Imagen</h5>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary active"
                                onclick="filtrarImagenes('todos')">Todos</button>
                            <button class="btn btn-outline-warning"
                                onclick="filtrarImagenes('solicitado')">Pendientes</button>
                            <button class="btn btn-outline-success"
                                onclick="filtrarImagenes('informado')">Informados</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="listaImagenes">
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
        cargarImagenes();

        // MANEJAR CAMBIO DE MÉDICO
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

    // Cargar estudios de imagen
    function cargarImagenes() {
        fetch(`listar_imagenes.php?caso=<?= $id_caso ?>&estado=${filtroActual}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ok') {
                    mostrarImagenes(data.data);
                } else {
                    document.getElementById('listaImagenes').innerHTML =
                        `<div class="alert alert-warning">Error: ${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('listaImagenes').innerHTML =
                    `<div class="alert alert-danger">Error de conexión: ${error.message}</div>`;
            });
    }

    // Mostrar estudios
    function mostrarImagenes(imagenes) {
        const container = document.getElementById('listaImagenes');

        if (imagenes.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-x-ray fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No hay estudios de imagen registrados</p>
                    <p class="small text-muted">Complete el formulario para solicitar el primer estudio</p>
                </div>
            `;
            return;
        }

        let html = '';
        imagenes.forEach(img => {
            const estadoColor = {
                'solicitado': 'warning',
                'en_proceso': 'info',
                'informado': 'success',
                'entregado': 'secondary'
            };

            // Determinar el estado a mostrar
            let estadoMostrar = img.estado === 'solicitado' ? 'Solicitado' :
                img.estado === 'en_proceso' ? 'En Proceso' :
                img.estado === 'informado' ? 'Informado' :
                'Desconocido';
            let colorEstado = estadoColor[img.estado] || 'secondary';

            html += `
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="mb-1">
                                    <i class="fas fa-x-ray me-2"></i>${img.tipo_estudio} - ${img.region_anatomica}
                                    ${img.urgente == 1 ? '<i class="fas fa-exclamation-triangle text-warning ms-2"></i>' : ''}
                                    ${img.contraste == 1 ? '<i class="fas fa-tint text-info ms-2" title="Con contraste"></i>' : ''}
                                </h6>
                                <small class="text-muted">${img.fecha_solicitud} - Dr. ${img.medico_solicita || 'No especificado'}</small>
                            </div>
                            <span class="badge bg-${colorEstado}">${estadoMostrar}</span>
                        </div>
                        
                        <p class="mb-2"><strong>Indicación:</strong> ${img.indicacion_clinica}</p>
                        ${img.tecnica ? `<p class="mb-2 text-muted"><strong>Técnica:</strong> ${img.tecnica}</p>` : ''}
                        
                        ${img.estado === 'informado' ? `
                            <div class="bg-light p-2 rounded mt-2">
                                <h6>Resultados:</h6>
                                ${img.hallazgos ? `<p class="mb-1"><strong>Hallazgos:</strong> ${img.hallazgos}</p>` : ''}
                                ${img.impresion_diagnostica ? `<p class="mb-1"><strong>Impresión:</strong> ${img.impresion_diagnostica}</p>` : ''}
                                ${img.archivo_imagen || img.archivo_reporte ? `
                                    <div class="mt-2">
                                        ${img.archivo_imagen ? `<a href="../../uploads/imagenes/${img.archivo_imagen}" target="_blank" class="btn btn-sm btn-outline-primary me-1"><i class="fas fa-image"></i> Ver Imagen</a>` : ''}
                                        ${img.archivo_reporte ? `<a href="../../uploads/imagenes/${img.archivo_reporte}" target="_blank" class="btn btn-sm btn-outline-success"><i class="fas fa-file-pdf"></i> Ver Reporte</a>` : ''}
                                    </div>
                                ` : ''}
                            </div>
                        ` : ''}
                    </div>
                `;
        });

        container.innerHTML = html;
    }

    // Filtrar imágenes
    function filtrarImagenes(estado) {
        filtroActual = estado; // Ahora el filtro por estado se maneja directamente en listar_imagenes.php
        cargarImagenes();

        // Actualizar botones activos
        document.querySelectorAll('.btn-group button').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');
    }

    // Enviar solicitud
    document.getElementById('formEstudio').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        // Validar médico solicitante
        const idMedico = formData.get('id_medico_solicita');
        if (!idMedico) {
            Swal.fire('Error', 'Debe seleccionar el médico solicitante', 'error');
            return;
        }

        fetch('solicitar_imagen.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ok') {
                    Swal.fire('Éxito', 'Estudio solicitado correctamente', 'success');
                    this.reset();
                    document.getElementById('medicoInfo').style.display = 'none'; // Ocultar info del médico
                    cargarImagenes();
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