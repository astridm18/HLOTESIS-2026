<?php
// modulos/procesos/ordenes.php
require_once '../../config/forms/database.php';
require_once '../../config/forms/functions.php';

verificarSesion();

$id_caso = $_GET['caso'] ?? 0;
if (!$id_caso) {
    die('Caso no especificado');
}

// Obtener datos del caso
$stmt = $pdo->prepare("
    SELECT cc.numero_caso, CONCAT(p.nombres, ' ', p.apellidos) as nombre_paciente,
           cc.servicio_actual, cc.cama_actual
    FROM casos_clinicos cc
    INNER JOIN pacientes p ON cc.id_paciente = p.id_paciente
    WHERE cc.id_caso = ?
");
$stmt->execute([$id_caso]);
$caso = $stmt->fetch();

if (!$caso) {
    die('Caso no encontrado');
}

// OBTENER MÉDICOS DISPONIBLES para el selector de Médico que Ordena
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
    <title>Órdenes Médicas - <?= $caso['nombre_paciente'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    .orden-card {
        border-left: 4px solid #007bff;
        transition: all 0.3s ease;
    }

    .orden-card:hover {
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .orden-pendiente {
        border-left-color: #ffc107;
    }

    .orden-proceso {
        border-left-color: #17a2b8;
    }

    .orden-completada {
        border-left-color: #28a745;
    }

    .orden-cancelada {
        border-left-color: #dc3545;
    }

    .orden-urgente {
        background: linear-gradient(45deg, rgba(255, 193, 7, 0.1), rgba(255, 193, 7, 0.05));
        border: 2px solid #ffc107;
    }

    .quick-order-btn {
        margin: 2px;
        border-radius: 20px;
        font-size: 0.85em;
    }

    .orden-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2em;
        margin-right: 15px;
    }

    .medicamento-icon {
        background: #007bff;
    }

    .laboratorio-icon {
        background: #28a745;
    }

    .imagen-icon {
        background: #6f42c1;
    }

    .procedimiento-icon {
        background: #fd7e14;
    }

    .dieta-icon {
        background: #20c997;
    }

    .cuidados-icon {
        background: #e83e8c;
    }

    .interconsulta-icon {
        background: #6c757d;
    }

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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3><i class="fas fa-prescription me-2"></i>Órdenes Médicas</h3>
                <h5 class="text-muted"><?= $caso['nombre_paciente'] ?> - Caso: <?= $caso['numero_caso'] ?></h5>
                <small class="text-muted">
                    <i class="fas fa-map-marker-alt me-1"></i><?= $caso['servicio_actual'] ?>
                    <?= $caso['cama_actual'] ? ' - Cama: ' . $caso['cama_actual'] : '' ?>
                </small>
            </div>
            <a href="dashboard_procesos.php?caso=<?= $id_caso ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>
        <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'dirección', 'medicina'])): ?>
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-bolt me-2"></i>Órdenes Rápidas</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <h6 class="text-primary">Medicamentos</h6>
                                <button class="btn btn-outline-primary btn-sm quick-order-btn"
                                    onclick="ordenRapida('medicamento', 'Paracetamol 500mg VO c/8h')">Paracetamol</button>
                                <button class="btn btn-outline-primary btn-sm quick-order-btn"
                                    onclick="ordenRapida('medicamento', 'Omeprazol 20mg VO c/24h')">Omeprazol</button>
                                <button class="btn btn-outline-primary btn-sm quick-order-btn"
                                    onclick="ordenRapida('medicamento', 'Dipirona 500mg VO PRN fiebre')">Dipirona</button>
                                <button class="btn btn-outline-primary btn-sm quick-order-btn"
                                    onclick="ordenRapida('medicamento', 'SSN 0.9% 1000ml IV c/8h')">SSN</button>
                            </div>
                            <div class="col-md-3">
                                <h6 class="text-success">Laboratorios</h6>
                                <button class="btn btn-outline-success btn-sm quick-order-btn"
                                    onclick="ordenRapida('laboratorio', 'Hemograma completo')">Hemograma</button>
                                <button class="btn btn-outline-success btn-sm quick-order-btn"
                                    onclick="ordenRapida('laboratorio', 'Química sanguínea (glucosa, urea, creatinina)')">Química</button>
                                <button class="btn btn-outline-success btn-sm quick-order-btn"
                                    onclick="ordenRapida('laboratorio', 'Electrolitos (Na, K, Cl)')">Electrolitos</button>
                                <button class="btn btn-outline-success btn-sm quick-order-btn"
                                    onclick="ordenRapida('laboratorio', 'Orina completa')">Orina</button>
                            </div>
                            <div class="col-md-3">
                                <h6 class="text-purple">Imágenes</h6>
                                <button class="btn btn-outline-secondary btn-sm quick-order-btn"
                                    onclick="ordenRapida('imagen', 'Radiografía de tórax PA y lateral')">RX
                                    Tórax</button>
                                <button class="btn btn-outline-secondary btn-sm quick-order-btn"
                                    onclick="ordenRapida('imagen', 'Ecografía abdominal')">Eco Abdomen</button>
                                <button class="btn btn-outline-secondary btn-sm quick-order-btn"
                                    onclick="ordenRapida('imagen', 'TAC de abdomen simple')">TAC Abdomen</button>
                            </div>
                            <div class="col-md-3">
                                <h6 class="text-info">Cuidados</h6>
                                <button class="btn btn-outline-info btn-sm quick-order-btn"
                                    onclick="ordenRapida('cuidados', 'Control de signos vitales c/4h')">Signos
                                    c/4h</button>
                                <button class="btn btn-outline-info btn-sm quick-order-btn"
                                    onclick="ordenRapida('cuidados', 'Dieta blanda')">Dieta blanda</button>
                                <button class="btn btn-outline-info btn-sm quick-order-btn"
                                    onclick="ordenRapida('cuidados', 'Control de diuresis')">Control diuresis</button>
                                <button class="btn btn-outline-info btn-sm quick-order-btn"
                                    onclick="ordenRapida('cuidados', 'Reposo en cama')">Reposo</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'dirección', 'medicina'])): ?>
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-plus me-2"></i>Nueva Orden Médica</h5>
                    </div>
                    <div class="card-body">
                        <form id="formOrden">
                            <input type="hidden" name="id_caso" value="<?= $id_caso ?>">

                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-user-doctor text-primary me-2"></i>Médico que Ordena: *
                                </label>
                                <select class="form-control medico-selector" name="id_medico_ordena" required>
                                    <option value="">Seleccionar médico...</option>
                                    <?php foreach ($medicos as $medico): ?>
                                    <option value="<?= $medico['id'] ?>"
                                        data-especialidad="<?= $medico['especialidad'] ?>">
                                        Dr. <?= $medico['nombre_completo'] ?> - (<?= $medico['especialidad'] ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="medico-info" id="medicoOrdenaInfo"></small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tipo de Orden: *</label>
                                <select class="form-control" name="tipo_orden" required onchange="cambiarTipoOrden()">
                                    <option value="">Seleccionar</option>
                                    <option value="medicamento">💊 Medicamento</option>
                                    <option value="laboratorio">🧪 Laboratorio</option>
                                    <option value="imagen">📷 Imagen</option>
                                    <option value="procedimiento">🔧 Procedimiento</option>
                                    <option value="dieta">🍽️ Dieta</option>
                                    <option value="cuidados">👩‍⚕️ Cuidados de Enfermería</option>
                                    <option value="interconsulta">👨‍⚕️ Interconsulta</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Descripción de la Orden: *</label>
                                <textarea class="form-control" name="descripcion" rows="4"
                                    placeholder="Escriba la orden médica detallada..." required></textarea>
                                <small class="text-muted" id="ayudaDescripcion">
                                    Escriba la orden de forma clara y específica
                                </small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Instrucciones Especiales:</label>
                                <textarea class="form-control" name="instrucciones_especiales" rows="3"
                                    placeholder="Instrucciones adicionales, precauciones, observaciones..."></textarea>
                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="urgente" value="1">
                                        <label class="form-check-label text-warning">
                                            <i class="fas fa-exclamation-triangle"></i> Urgente
                                        </label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <select class="form-control form-control-sm" name="prioridad">
                                        <option value="rutina">Rutina</option>
                                        <option value="urgente">Urgente</option>
                                        <option value="stat">STAT</option>
                                    </select>
                                </div>
                            </div>

                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Enviar Orden
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="limpiarFormulario()">
                                    <i class="fas fa-eraser me-2"></i>Limpiar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h6><i class="fas fa-file-medical me-2"></i>Plantillas</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-1">
                            <button class="btn btn-outline-primary btn-sm" onclick="cargarPlantilla('preoperatorio')">
                                Pre-operatorio
                            </button>
                            <button class="btn btn-outline-success btn-sm" onclick="cargarPlantilla('postoperatorio')">
                                Post-operatorio
                            </button>
                            <button class="btn btn-outline-info btn-sm" onclick="cargarPlantilla('hipertension')">
                                Hipertensión
                            </button>
                            <button class="btn btn-outline-warning btn-sm" onclick="cargarPlantilla('diabetes')">
                                Diabetes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-list-ul me-2"></i>Órdenes Médicas</h5>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary active" onclick="filtrarOrdenes('todas')">
                                Todas <span class="badge bg-primary" id="badge-todas">0</span>
                            </button>
                            <button class="btn btn-outline-warning" onclick="filtrarOrdenes('pendiente')">
                                Pendientes <span class="badge bg-warning" id="badge-pendientes">0</span>
                            </button>
                            <button class="btn btn-outline-info" onclick="filtrarOrdenes('en_proceso')">
                                En Proceso <span class="badge bg-info" id="badge-proceso">0</span>
                            </button>
                            <button class="btn btn-outline-success" onclick="filtrarOrdenes('completada')">
                                Completadas <span class="badge bg-success" id="badge-completadas">0</span>
                            </button>
                        </div>
                    </div>
                    <div class="card-body" style="max-height: 700px; overflow-y: auto;">
                        <div id="listaOrdenes">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                                <p class="mt-2 text-muted">Cargando órdenes...</p>
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
    let ordenes = [];

    document.addEventListener('DOMContentLoaded', function() {
        cargarOrdenes();

        // MANEJAR CAMBIO DE MÉDICO QUE ORDENA
        const selectMedicoOrdena = document.querySelector('[name="id_medico_ordena"]');
        selectMedicoOrdena.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const especialidad = selectedOption.getAttribute('data-especialidad');
            const medicoInfo = document.getElementById('medicoOrdenaInfo');

            if (especialidad && this.value) {
                medicoInfo.textContent = `Especialidad: ${especialidad}`;
                medicoInfo.style.display = 'block';
            } else {
                medicoInfo.style.display = 'none';
            }
        });
    });

    // Cargar órdenes médicas
    function cargarOrdenes() {
        fetch(`listar_ordenes.php?caso=<?= $id_caso ?>`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ok') {
                    ordenes = data.data;
                    mostrarOrdenes(data.data);
                    actualizarContadores(data.data);
                } else {
                    document.getElementById('listaOrdenes').innerHTML =
                        '<div class="alert alert-warning">Error al cargar órdenes</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('listaOrdenes').innerHTML =
                    '<div class="alert alert-danger">Error de conexión</div>';
            });
    }

    // Mostrar órdenes
    function mostrarOrdenes(ordenes) {
        const container = document.getElementById('listaOrdenes');

        if (ordenes.length === 0) {
            container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-prescription fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No hay órdenes médicas registradas</p>
                        <p class="small text-muted">Complete el formulario para agregar la primera orden</p>
                    </div>
                `;
            return;
        }

        let html = '';
        ordenes.forEach((orden, index) => {
            const iconClass = getIconClass(orden.tipo_orden);
            const estadoClass = `orden-${orden.estado.replace('_', '-')}`;
            const urgentClass = orden.urgente == '1' ? 'orden-urgente' : '';

            html += `
                    <div class="orden-card card mb-3 ${estadoClass} ${urgentClass}" data-index="${index}">
                        <div class="card-body">
                            <div class="d-flex align-items-start">
                                <div class="orden-icon ${iconClass}">
                                    <i class="fas ${getIconType(orden.tipo_orden)}"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1">
                                                ${orden.tipo_orden.charAt(0).toUpperCase() + orden.tipo_orden.slice(1)}
                                                ${orden.urgente == '1' ? '<span class="badge bg-warning text-dark ms-2">URGENTE</span>' : ''}
                                            </h6>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>${orden.fecha_orden}
                                                <span class="ms-3">
                                                    <i class="fas fa-user-md me-1"></i>Dr. ${orden.medico}
                                                </span>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-${getEstadoColor(orden.estado)} mb-1">
                                                ${orden.estado.replace('_', ' ').toUpperCase()}
                                            </span>
                                            <br>
                                            <div class="btn-group btn-group-sm">
                                                ${orden.estado === 'pendiente' ? `
                                                 <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'dirección', 'medicina'])): ?>
                                                    <button class="btn btn-outline-info btn-sm" onclick="cambiarEstado(${orden.id_orden}, 'en_proceso')" title="Iniciar">
                                                        <i class="fas fa-play"></i>
                                                    </button>
                                                    <button class="btn btn-outline-success btn-sm" onclick="cambiarEstado(${orden.id_orden}, 'completada')" title="Completar">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger btn-sm" onclick="cancelarOrden(${orden.id_orden})" title="Cancelar">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                ` : ''}
                                                ${orden.estado === 'en_proceso' ? `
                                                    <button class="btn btn-outline-success btn-sm" onclick="cambiarEstado(${orden.id_orden}, 'completada')" title="Completar">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                       <?php endif; ?>
                                                ` : ''}
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="orden-descripcion">
                                        <p class="mb-2"><strong>Orden:</strong> ${orden.descripcion}</p>
                                        ${orden.instrucciones_especiales ? `
                                            <p class="mb-2 text-muted">
                                                <strong>Instrucciones:</strong> ${orden.instrucciones_especiales}
                                            </p>
                                        ` : ''}
                                    </div>
                                    
                                    ${orden.fecha_ejecucion ? `
                                        <div class="bg-light p-2 rounded mt-2">
                                            <small>
                                                <strong>Ejecutado:</strong> ${orden.fecha_ejecucion}
                                                ${orden.ejecutor ? ` por ${orden.ejecutor}` : ''}
                                            </small>
                                            ${orden.observaciones_ejecucion ? `<br><small><strong>Observaciones:</strong> ${orden.observaciones_ejecucion}</small>` : ''}
                                        </div>
                                    ` : ''}
                                    
                                    ${orden.fecha_cancelacion ? `
                                        <div class="bg-danger bg-opacity-10 p-2 rounded mt-2">
                                            <small class="text-danger">
                                                <strong>Cancelado:</strong> ${orden.fecha_cancelacion}
                                                ${orden.motivo_cancelacion ? `<br><strong>Motivo:</strong> ${orden.motivo_cancelacion}` : ''}
                                            </small>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
        });

        container.innerHTML = html;
    }

    // Funciones de utilidad para iconos y colores
    function getIconClass(tipo) {
        const classes = {
            'medicamento': 'medicamento-icon',
            'laboratorio': 'laboratorio-icon',
            'imagen': 'imagen-icon',
            'procedimiento': 'procedimiento-icon',
            'dieta': 'dieta-icon',
            'cuidados': 'cuidados-icon',
            'interconsulta': 'interconsulta-icon'
        };
        return classes[tipo] || 'medicamento-icon';
    }

    function getIconType(tipo) {
        const icons = {
            'medicamento': 'fa-pills',
            'laboratorio': 'fa-vial',
            'imagen': 'fa-x-ray',
            'procedimiento': 'fa-tools',
            'dieta': 'fa-utensils',
            'cuidados': 'fa-user-nurse',
            'interconsulta': 'fa-user-md'
        };
        return icons[tipo] || 'fa-pills';
    }

    function getEstadoColor(estado) {
        const colores = {
            'pendiente': 'warning',
            'en_proceso': 'info',
            'completada': 'success',
            'cancelada': 'danger'
        };
        return colores[estado] || 'secondary';
    }

    // Actualizar contadores
    function actualizarContadores(ordenes) {
        const contadores = {
            todas: ordenes.length,
            pendientes: ordenes.filter(o => o.estado === 'pendiente').length,
            proceso: ordenes.filter(o => o.estado === 'en_proceso').length,
            completadas: ordenes.filter(o => o.estado === 'completada').length
        };

        document.getElementById('badge-todas').textContent = contadores.todas;
        document.getElementById('badge-pendientes').textContent = contadores.pendientes;
        document.getElementById('badge-proceso').textContent = contadores.proceso;
        document.getElementById('badge-completadas').textContent = contadores.completadas;
    }

    // Filtrar órdenes
    function filtrarOrdenes(estado) {
        filtroActual = estado;

        let ordenesFiltradas = ordenes;
        if (estado !== 'todas') {
            ordenesFiltradas = ordenes.filter(orden => orden.estado === estado);
        }

        mostrarOrdenes(ordenesFiltradas);

        // Actualizar botones activos
        document.querySelectorAll('.btn-group button').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');
    }

    // Cambiar tipo de orden (para ayuda contextual)
    function cambiarTipoOrden() {
        const tipo = document.querySelector('[name="tipo_orden"]').value;
        const ayuda = document.getElementById('ayudaDescripcion');

        const ayudas = {
            'medicamento': 'Ej: Paracetamol 500mg VO cada 8 horas por dolor',
            'laboratorio': 'Ej: Hemograma completo, Química sanguínea (glucosa, urea, creatinina)',
            'imagen': 'Ej: Radiografía de tórax PA y lateral por disnea',
            'procedimiento': 'Ej: Cateterización vesical, Curación de herida quirúrgica',
            'dieta': 'Ej: Dieta blanda, NPO pre-cirugía, Dieta diabética 1800 cal',
            'cuidados': 'Ej: Control de signos vitales c/4h, Reposo en cama, Glicemia capilar c/6h',
            'interconsulta': 'Ej: Valoración por Cardiología por soplo sistólico'
        };

        ayuda.textContent = ayudas[tipo] || 'Escriba la orden de forma clara y específica';
    }

    // Orden rápida
    function ordenRapida(tipo, descripcion) {
        document.querySelector('[name="tipo_orden"]').value = tipo;
        document.querySelector('[name="descripcion"]').value = descripcion;
        cambiarTipoOrden();
    }

    // Plantillas de órdenes
    function cargarPlantilla(tipo) {
        const plantillas = {
            'preoperatorio': `NPO desde las 22:00 hrs
Enema evacuante si cirugía abdominal
Profilaxis antibiótica según protocolo
Consentimiento informado
Valoración pre-anestésica`,
            'postoperatorio': `Dieta progresiva según tolerancia
Control de signos vitales c/2h x 12h, luego c/4h
Control de herida operatoria
Deambulación precoz
Analgesia multimodal`,
            'hipertension': `Captopril 25mg VO c/8h
Control de PA c/6h
Dieta hiposódica < 2g/día
Reposo relativo
Glicemia capilar c/6h`,
            'diabetes': `Insulina NPH según esquema
Glicemia capilar c/6h
Dieta diabética 1800 cal
Control de cetonas en orina
Educación diabetológica`
        };

        if (plantillas[tipo]) {
            document.querySelector('[name="descripcion"]').value = plantillas[tipo];
        }
    }

    // Cambiar estado de orden
    function cambiarEstado(idOrden, nuevoEstado) {
        let observaciones = '';

        if (nuevoEstado === 'completada') {
            Swal.fire({
                title: 'Completar Orden',
                text: 'Observaciones de ejecución (opcional):',
                input: 'textarea',
                inputPlaceholder: 'Escriba observaciones sobre la ejecución...',
                showCancelButton: true,
                confirmButtonText: 'Completar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    observaciones = result.value || '';
                    ejecutarCambioEstado(idOrden, nuevoEstado, observaciones);
                }
            });
        } else {
            ejecutarCambioEstado(idOrden, nuevoEstado, observaciones);
        }
    }

    function ejecutarCambioEstado(idOrden, nuevoEstado, observaciones) {
        const formData = new FormData();
        formData.append('id_orden', idOrden);
        formData.append('estado', nuevoEstado);
        formData.append('observaciones_ejecucion', observaciones);

        fetch('cambiar_estado_orden.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ok') {
                    Swal.fire('Éxito', 'Estado actualizado correctamente', 'success');
                    cargarOrdenes();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
    }

    // Cancelar orden
    function cancelarOrden(idOrden) {
        Swal.fire({
            title: 'Cancelar Orden',
            text: 'Motivo de cancelación:',
            input: 'textarea',
            inputPlaceholder: 'Escriba el motivo de la cancelación...',
            inputValidator: (value) => {
                if (!value) {
                    return 'Debe especificar el motivo de cancelación';
                }
            },
            showCancelButton: true,
            confirmButtonText: 'Cancelar Orden',
            cancelButtonText: 'No cancelar',
            confirmButtonColor: '#dc3545'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('id_orden', idOrden);
                formData.append('estado', 'cancelada');
                formData.append('motivo_cancelacion', result.value);

                fetch('cambiar_estado_orden.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'ok') {
                            Swal.fire('Orden cancelada', 'La orden ha sido cancelada correctamente',
                                'success');
                            cargarOrdenes();
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    });
            }
        });
    }

    // Limpiar formulario
    function limpiarFormulario() {
        document.getElementById('formOrden').reset();
        document.getElementById('ayudaDescripcion').textContent = 'Escriba la orden de forma clara y específica';
        document.getElementById('medicoOrdenaInfo').style.display = 'none'; // Ocultar info del médico
    }

    // Enviar nueva orden
    document.getElementById('formOrden').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        // Validar Médico que Ordena
        const idMedicoOrdena = formData.get('id_medico_ordena');
        if (!idMedicoOrdena) {
            Swal.fire('Error', 'Debe seleccionar el Médico que Ordena', 'error');
            return;
        }

        // Validar que la descripción no esté vacía
        const descripcion = formData.get('descripcion').trim();
        if (!descripcion) {
            Swal.fire('Error', 'La descripción de la orden es obligatoria', 'error');
            return;
        }

        // Mostrar loading
        Swal.fire({
            title: 'Enviando orden...',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('guardar_orden.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();

                if (data.status === 'ok') {
                    Swal.fire({
                        title: 'Éxito',
                        text: 'Orden médica enviada correctamente',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });

                    this.reset();
                    cambiarTipoOrden(); // Reset ayuda
                    document.getElementById('medicoOrdenaInfo').style.display =
                        'none'; // Ocultar info del médico
                    cargarOrdenes();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Error:', error);
                Swal.fire('Error', 'Error de conexión al enviar la orden', 'error');
            });
    });

    // Auto-resize textarea
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
        // Ctrl + Enter para enviar orden
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('formOrden').dispatchEvent(new Event('submit'));
        }

        // Esc para limpiar formulario
        if (e.key === 'Escape') {
            limpiarFormulario();
        }
    });

    // Actualizar cada 2 minutos para órdenes en tiempo real
    setInterval(function() {
        cargarOrdenes();
    }, 120000); // 2 minutos
    </script>
</body>

</html>