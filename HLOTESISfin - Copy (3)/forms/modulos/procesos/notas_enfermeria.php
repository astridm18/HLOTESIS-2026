<?php
// modulos/procesos/notas_enfermeria.php
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
    <title>Notas de Enfermería - <?= $caso['nombre_paciente'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            <h3><i class="fas fa-notes-medical me-2"></i>Notas de Enfermería - <?= $caso['nombre_paciente'] ?></h3>
            <a href="dashboard_procesos.php?caso=<?= $id_caso ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>

        <div class="row">
            <!-- Formulario nueva nota -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-plus me-2"></i>Nueva Nota</h5>
                    </div>
                    <div class="card-body">
                        <form id="formNota">
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

                            <div class="mb-3">
                                <label class="form-label">Turno:</label>
                                <select class="form-control" name="turno" required>
                                    <option value="mañana">Mañana (6:00-14:00)</option>
                                    <option value="tarde">Tarde (14:00-22:00)</option>
                                    <option value="noche">Noche (22:00-6:00)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tipo de Nota:</label>
                                <select class="form-control" name="tipo_nota" required>
                                    <option value="evolucion">Evolución</option>
                                    <option value="medicamentos">Medicamentos</option>
                                    <option value="procedimientos">Procedimientos</option>
                                    <option value="observaciones">Observaciones</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Contenido: *</label>
                                <textarea class="form-control" name="contenido" rows="5"
                                    placeholder="Describe las observaciones, cuidados realizados, estado del paciente..."
                                    required></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Cuidados Realizados:</label>
                                <textarea class="form-control" name="cuidados_realizados" rows="3"
                                    placeholder="Higiene, cambio de posición, curaciones, etc."></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Medicamentos Administrados:</label>
                                <textarea class="form-control" name="medicamentos_administrados" rows="3"
                                    placeholder="Medicamentos dados en este turno..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Reacciones Adversas:</label>
                                <textarea class="form-control" name="reacciones_adversas" rows="2"
                                    placeholder="Reacciones o efectos adversos observados..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Estado del Paciente:</label>
                                <select class="form-control" name="estado_paciente">
                                    <option value="Estable">Estable</option>
                                    <option value="Tranquilo">Tranquilo</option>
                                    <option value="Ansioso">Ansioso</option>
                                    <option value="Doloroso">Con dolor</option>
                                    <option value="Inquieto">Inquieto</option>
                                    <option value="Somnoliento">Somnoliento</option>
                                    <option value="Colaborador">Colaborador</option>
                                    <option value="No colaborador">No colaborador</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-save me-2"></i>Guardar Nota
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Lista de notas -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-list me-2"></i>Registro de Enfermería</h5>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary active"
                                onclick="filtrarNotas('todas')">Todas</button>
                            <button class="btn btn-outline-info" onclick="filtrarNotas('mañana')">Mañana</button>
                            <button class="btn btn-outline-warning" onclick="filtrarNotas('tarde')">Tarde</button>
                            <button class="btn btn-outline-dark" onclick="filtrarNotas('noche')">Noche</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="listaNotas">
                            <div class="text-center">
                                <div class="spinner-border text-success" role="status">
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
    let filtroActual = 'todas';

    document.addEventListener('DOMContentLoaded', function() {
        cargarNotas();

        // Detectar turno actual
        const hora = new Date().getHours();
        let turno = 'mañana';
        if (hora >= 14 && hora < 22) turno = 'tarde';
        else if (hora >= 22 || hora < 6) turno = 'noche';

        document.querySelector('[name="turno"]').value = turno;

        // 🆕 MANEJAR CAMBIO DE ENFERMERA
        const selectEnfermera = document.querySelector('[name="id_enfermero"]');
        selectEnfermera.addEventListener('change', function() {
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

    // Cargar notas
    function cargarNotas() {
        const url = filtroActual === 'todas' ?
            'listar_notas_enfermeria.php?caso=<?= $id_caso ?>' :
            `listar_notas_enfermeria.php?caso=<?= $id_caso ?>&turno=${filtroActual}`;

        console.log('Cargando notas desde:', url); // 🔧 DEBUG

        fetch(url)
            .then(response => {
                console.log('Response status:', response.status); // 🔧 DEBUG
                return response.json();
            })
            .then(data => {
                console.log('Datos recibidos:', data); // 🔧 DEBUG
                if (data.status === 'ok') {
                    mostrarNotas(data.data);
                } else {
                    document.getElementById('listaNotas').innerHTML =
                        `<div class="alert alert-warning">Error: ${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('listaNotas').innerHTML =
                    `<div class="alert alert-danger">Error de conexión: ${error.message}</div>`;
            });
    }

    // Mostrar notas
    function mostrarNotas(notas) {
        const container = document.getElementById('listaNotas');

        console.log('Mostrando notas:', notas); // 🔧 DEBUG

        if (!notas || notas.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-notes-medical fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No hay notas registradas</p>
                    <p class="small text-muted">Complete el formulario para agregar la primera nota</p>
                </div>
            `;
            return;
        }

        let html = '';
        notas.forEach(nota => {
            const turnoColor = {
                'mañana': 'info',
                'tarde': 'warning',
                'noche': 'dark'
            };

            const tipoIcon = {
                'evolucion': 'notes-medical',
                'medicamentos': 'pills',
                'procedimientos': 'tools',
                'observaciones': 'eye'
            };

            html += `
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="mb-1">
                                <i class="fas fa-${tipoIcon[nota.tipo_nota] || 'clipboard'} me-2"></i>
                                ${nota.tipo_nota.charAt(0).toUpperCase() + nota.tipo_nota.slice(1)}
                                <span class="badge bg-${turnoColor[nota.turno]} ms-2">${nota.turno}</span>
                            </h6>
                            <small class="text-muted">${nota.fecha_nota} - ${nota.enfermero}</small>
                        </div>
                        ${nota.estado_paciente ? `<span class="badge bg-secondary">${nota.estado_paciente}</span>` : ''}
                    </div>
                    
                    <p class="mb-2">${nota.contenido}</p>
                    
                    ${nota.cuidados_realizados ? `
                        <div class="bg-light p-2 rounded mb-2">
                            <strong>Cuidados:</strong> ${nota.cuidados_realizados}
                        </div>
                    ` : ''}
                    
                    ${nota.medicamentos_administrados ? `
                        <div class="bg-info bg-opacity-10 p-2 rounded mb-2">
                            <strong>Medicamentos:</strong> ${nota.medicamentos_administrados}
                        </div>
                    ` : ''}
                    
                    ${nota.reacciones_adversas ? `
                        <div class="bg-warning bg-opacity-10 p-2 rounded">
                            <strong>⚠️ Reacciones:</strong> ${nota.reacciones_adversas}
                        </div>
                    ` : ''}
                </div>
            `;
        });

        container.innerHTML = html;
    }

    // Filtrar notas
    function filtrarNotas(turno) {
        filtroActual = turno === 'todas' ? 'todas' : turno;
        cargarNotas();

        // Actualizar botones activos
        document.querySelectorAll('.btn-group button').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');
    }

    // Enviar formulario - CORREGIDO
    document.getElementById('formNota').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        // 🔧 DEBUG: Verificar datos antes de enviar
        console.log('Datos del formulario:');
        for (let [key, value] of formData.entries()) {
            console.log(key + ': ' + value);
        }

        // 🆕 VALIDAR ENFERMERA RESPONSABLE
        const idEnfermero = formData.get('id_enfermero');
        if (!idEnfermero) {
            Swal.fire('Error', 'Debe seleccionar la enfermera responsable', 'error');
            return;
        }

        // Validar contenido
        const contenido = formData.get('contenido').trim();
        if (!contenido) {
            Swal.fire('Error', 'El contenido de la nota es obligatorio', 'error');
            return;
        }

        fetch('guardar_nota_enfermeria.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ok') {
                    Swal.fire('Éxito', 'Nota guardada correctamente', 'success');
                    this.reset();
                    cargarNotas();

                    // Resetear turno actual
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
                console.error('Error:', error);
                Swal.fire('Error', 'Error de conexión', 'error');
            });
    });
    </script>
</body>

</html>