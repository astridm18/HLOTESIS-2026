<?php
// Incluir sesión temporal para esarrollo

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso de Pacientes - Sistema Médico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Tesseract.js para OCR -->
    <script src="https://unpkg.com/tesseract.js@v5.0.0/dist/tesseract.min.js"></script>

    <style>
    .section-card {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .section-header {
        background: linear-gradient(135deg, #0d6efd, #0b5ed7);
        color: white;
        padding: 15px 20px;
        border-radius: 7px 7px 0 0;
        cursor: pointer;
    }

    .section-content {
        padding: 20px;
        display: none;
    }

    .section-content.active {
        display: block;
    }

    #numero_historia {
        background-color: #f8f9fa !important;
        color: #495057;
        font-weight: 500;
    }

    #numero_historia:focus {
        background-color: #f8f9fa !important;
        box-shadow: none;
    }

    #btnRefreshHistoria {
        border-left: none;
    }

    #btnRefreshHistoria:hover {
        background-color: #e9ecef;
        color: #0d6efd;
    }

    .campo-requerido {
        color: #dc3545;
    }

    .antecedente-item {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        background: #f8f9fa;
    }

    .antecedente-actions {
        display: flex;
        gap: 10px;
    }

    .file-upload-area {
        border: 2px dashed #ccc;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        background: #fafafa;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .file-upload-area:hover {
        border-color: #0d6efd;
        background: #f0f8ff;
    }

    .file-upload-area.dragover {
        border-color: #0d6efd;
        background: #e3f2fd;
    }

    .preview-container {
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 10px;
        margin-top: 10px;
    }

    .file-preview {
        display: flex;
        align-items: center;
        padding: 8px;
        border: 1px solid #e0e0e0;
        border-radius: 5px;
        margin-bottom: 8px;
        background: white;
    }

    .file-preview img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 4px;
        margin-right: 10px;
    }

    .file-preview .file-icon {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f0f0f0;
        border-radius: 4px;
        margin-right: 10px;
        font-size: 20px;
        color: #666;
    }

    .extracted-text {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 8px;
        padding: 15px;
        margin-top: 10px;
    }

    .ocr-loading {
        display: none;
        text-align: center;
        padding: 20px;
    }

    .progress-container {
        margin-top: 10px;
    }

    .correcciones-sugeridas {
        background: #d1ecf1;
        border: 1px solid #bee5eb;
        border-radius: 8px;
        padding: 10px;
        margin-top: 10px;
    }

    .text-sm {
        font-size: 0.875rem;
    }
    </style>
    <style>
    .cama-card {
        border: 2px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
        min-height: 40px;
    }

    .cama-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .cama-card.disponible {
        border-color: #28a745;
        background: #f8fff9;
    }

    .cama-card.disponible:hover {
        border-color: #20c997;
        background: #e8f5e8;
    }

    .cama-card.ocupada {
        border-color: #dc3545;
        background: #fff5f5;
        cursor: not-allowed;
        opacity: 0.6;
    }

    .cama-card.mantenimiento {
        border-color: #ffc107;
        background: #fffdf5;
        cursor: not-allowed;
        opacity: 0.6;
    }

    .cama-card.seleccionada {
        border-color: #007bff !important;
        background: #e7f3ff !important;
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
    }

    .cama-numero {
        font-size: 24px;
        font-weight: bold;
        color: #495057;
    }

    .cama-info {
        font-size: 12px;
        color: #6c757d;
    }
    </style>

</head>

<body>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h2><i class="fas fa-user-plus me-2"></i>Ingreso de Pacientes</h2>
                <hr>

                <!-- Buscar Paciente -->
                <div class="section-card">
                    <div class="section-header" onclick="toggleSection('buscar')">
                        <i class="fas fa-search me-2"></i>Buscar Paciente Existente
                    </div>
                    <div class="section-content active" id="buscar-content">
                        <div class="row align-items-end">
                            <div class="col-md-5">
                                <label class="form-label">Cédula del Paciente:</label>
                                <div class="input-group">
                                    <select class="form-select" id="tipo_cedula_buscar" style="max-width: 80px;">
                                        <option value="">-</option>
                                        <option value="V">V-</option>
                                        <option value="E">E-</option>
                                        <option value="P">P-</option>
                                    </select>
                                    <input type="text" class="form-control" id="cedula_numero_buscar"
                                        placeholder="12345678" maxlength="8">
                                    <input type="hidden" id="cedula_buscar">
                                    <button type="button" class="btn btn-primary" onclick="buscarPaciente()">
                                        <i class="fas fa-search me-2"></i>Buscar
                                    </button>
                                </div>
                                <small class="text-muted">Seleccione tipo y número de cédula</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulario Principal -->
                <form id="formIngreso">
                    <input type="hidden" id="id_paciente" name="id_paciente">

                    <!-- Datos del Paciente -->
                    <div class="section-card">
                        <div class="section-header" onclick="toggleSection('paciente')">
                            <i class="fas fa-user me-2"></i>Datos del Paciente
                        </div>
                        <div class="section-content" id="paciente-content">
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="form-label">Número de Historia:</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="numero_historia" readonly
                                            placeholder="Se generará automáticamente">
                                        <button type="button" class="btn btn-outline-secondary"
                                            onclick="refrescarNumeroHistoria()" title="Refrescar número de historia"
                                            id="btnRefreshHistoria">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Este número se asigna automáticamente</small>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Cédula: <span class="campo-requerido">*</span></label>
                                    <div class="input-group">
                                        <select class="form-select" id="tipo_cedula" name="tipo_cedula"
                                            style="max-width: 80px;" required>
                                            <option value="">-</option>
                                            <option value="V">V-</option>
                                            <option value="E">E-</option>
                                            <option value="P">P-</option>
                                        </select>
                                        <input type="text" class="form-control" id="cedula_numero" name="cedula_numero"
                                            placeholder="12345678" maxlength="8" required>
                                        <input type="hidden" id="cedula" name="cedula">
                                    </div>
                                    <small class="text-muted">Ejemplo: V-12345678</small>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Nombres: <span class="campo-requerido">*</span></label>
                                    <input type="text" class="form-control" id="nombres" name="nombres" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Apellidos: <span class="campo-requerido">*</span></label>
                                    <input type="text" class="form-control" id="apellidos" name="apellidos" required>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-2">
                                    <label class="form-label">Sexo: <span class="campo-requerido">*</span></label>
                                    <select class="form-control" id="sexo" name="sexo" required>
                                        <option value="">Seleccionar</option>
                                        <option value="M">Masculino</option>
                                        <option value="F">Femenino</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Fecha Nacimiento:</label>
                                    <input type="date" class="form-control" id="fecha_nacimiento"
                                        name="fecha_nacimiento">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Teléfono:</label>
                                    <div class="input-group">
                                        <select class="form-select" id="cod_area_paciente" style="max-width: 90px;">
                                            <option value="0414">0414</option>
                                            <option value="0424">0424</option>
                                            <option value="0412">0412</option>
                                            <option value="0416">0416</option>
                                            <option value="0426">0426</option>
                                        </select>
                                        <input type="text" id="numero_paciente" class="form-control"
                                            placeholder="1234567" maxlength="7" />
                                        <input type="hidden" id="telefono" name="telefono">
                                    </div>
                                    <small class="text-muted">Formato: 0414-1234567</small>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Dirección:</label>
                                    <input type="text" class="form-control" id="direccion" name="direccion">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Tipo de Sangre:</label>
                                    <select class="form-control" id="tipo_sangre" name="tipo_sangre">
                                        <option value="">Seleccionar</option>
                                        <option value="A+">A+</option>
                                        <option value="A-">A-</option>
                                        <option value="B+">B+</option>
                                        <option value="B-">B-</option>
                                        <option value="AB+">AB+</option>
                                        <option value="AB-">AB-</option>
                                        <option value="O+">O+</option>
                                        <option value="O-">O-</option>
                                        <option value="Desconocida">Desconocida</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Seguro Médico:</label>
                                    <input type="text" class="form-control" id="seguro_medico" name="seguro_medico"
                                        placeholder="Nombre del seguro médico">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Número de Seguro: <small
                                            class="text-muted">(Opcional)</small></label>
                                    <input type="text" class="form-control" id="numero_seguro" name="numero_seguro"
                                        placeholder="Número de póliza">
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <label class="form-label">Alergias Conocidas:</label>
                                    <textarea class="form-control" id="alergias_conocidas" name="alergias_conocidas"
                                        rows="2" placeholder="Medicamentos, alimentos u otras sustancias..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Datos de Ingreso -->
                    <div class="section-card">
                        <div class="section-header" onclick="toggleSection('ingreso')">
                            <i class="fas fa-hospital me-2"></i>Datos de Ingreso
                        </div>
                        <div class="section-content" id="ingreso-content">
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="form-label">Fecha Ingreso: <span
                                            class="campo-requerido">*</span></label>
                                    <input type="date" class="form-control" id="fecha_ingreso" name="fecha_ingreso"
                                        required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Hora Ingreso: <span
                                            class="campo-requerido">*</span></label>
                                    <input type="time" class="form-control" id="hora_ingreso" name="hora_ingreso"
                                        required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Servicio: <span class="campo-requerido">*</span></label>
                                    <select class="form-control" id="servicio_ingreso" name="servicio_ingreso" required>
                                        <option value="">Seleccionar</option>
                                        <option value="Emergencias">Emergencias</option>
                                        <option value="Medicina Interna">Medicina Interna</option>
                                        <option value="Cirugía">Cirugía</option>
                                        <option value="Pediatría">Pediatría</option>
                                        <option value="Ginecología">Ginecología</option>
                                        <option value="UCI">UCI</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Tipo Ingreso:</label>
                                    <select class="form-control" id="tipo_ingreso" name="tipo_ingreso">
                                        <option value="emergencia">Emergencia</option>
                                        <option value="programado">Programado</option>
                                        <option value="referencia">Referencia</option>
                                        <option value="traslado">Traslado</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Médico de Ingreso: <span
                                            class="campo-requerido">*</span></label>
                                    <select class="form-control" id="id_medico_ingreso" name="id_medico_ingreso"
                                        required>
                                        <option value="">Seleccionar médico...</option>
                                    </select>
                                    <small class="text-muted">Médico que realiza el ingreso</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Médico Responsable:</label>
                                    <select class="form-control" id="id_medico_responsable"
                                        name="id_medico_responsable">
                                        <option value="">Seleccionar médico responsable...</option>
                                    </select>
                                    <small class="text-muted">Médico responsable del caso (opcional)</small>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <label class="form-label">Motivo de Consulta: <span
                                            class="campo-requerido">*</span></label>
                                    <textarea class="form-control" id="motivo_consulta" name="motivo_consulta" rows="3"
                                        required
                                        placeholder="Describa el motivo de la consulta, deben ser al menos 10 caracteres..."></textarea>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <label class="form-label">Enfermedad Actual:</label>
                                    <textarea class="form-control" id="enfermedad_actual" name="enfermedad_actual"
                                        rows="2" placeholder="Descripción de la enfermedad actual..."></textarea>
                                </div>
                            </div>

                            <!-- NUEVOS CAMPOS PARA DATOS_INGRESO -->
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Síntomas Principales:</label>
                                    <textarea class="form-control" id="sintomas_principales" name="sintomas_principales"
                                        rows="2" placeholder="Síntomas principales que presenta..."></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tiempo de Evolución:</label>
                                    <input type="text" class="form-control" id="tiempo_evolucion"
                                        name="tiempo_evolucion" placeholder="Ej: 3 días, 2 semanas...">
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-3">
                                    <label class="form-label">Escala de Dolor (0-10):</label>
                                    <input type="number" class="form-control" id="dolor_escala" name="dolor_escala"
                                        min="0" max="10" placeholder="0-10">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Vía de Ingreso:</label>
                                    <select class="form-control" id="via_ingreso" name="via_ingreso">
                                        <option value="emergencia">Emergencia</option>
                                        <option value="consulta_externa">Consulta Externa</option>
                                        <option value="referencia">Referencia</option>
                                        <option value="traslado">Traslado</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Medio de Transporte:</label>
                                    <input type="text" class="form-control" id="medio_transporte"
                                        name="medio_transporte" placeholder="Ambulancia, vehículo particular, etc.">
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <label class="form-label">Acompañante:</label>
                                    <input type="text" class="form-control" id="acompanante" name="acompanante"
                                        placeholder="Nombre del acompañante">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Teléfono Acompañante:</label>
                                    <div class="input-group">
                                        <select class="form-select" id="cod_area_acompanante" style="max-width: 90px;">
                                            <option value="0414">0414</option>
                                            <option value="0424">0424</option>
                                            <option value="0412">0412</option>
                                            <option value="0416">0416</option>
                                            <option value="0426">0426</option>
                                        </select>
                                        <input type="text" id="numero_acompanante" class="form-control"
                                            placeholder="1234567" maxlength="7" />
                                        <input type="hidden" id="telefono_acompanante" name="telefono_acompanante">
                                    </div>
                                    <small class="text-muted">Opcional</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Parentesco:</label>
                                    <input type="text" class="form-control" id="parentesco_acompanante"
                                        name="parentesco_acompanante" placeholder="Madre, hijo, esposo, etc.">
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Prediagnóstico de Ingreso:</label>
                                    <textarea class="form-control" id="diagnostico_ingreso" name="diagnostico_ingreso"
                                        rows="2"
                                        placeholder="Diagnóstico inicial o impresión diagnóstica..."></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Impresión Clínica:</label>
                                    <textarea class="form-control" id="impresion_clinica" name="impresion_clinica"
                                        rows="2" placeholder="Impresión clínica del médico..."></textarea>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-3">
                                    <label class="form-label">Prioridad del Caso:</label>
                                    <select class="form-control" id="prioridad" name="prioridad">
                                        <option value="media">Media</option>
                                        <option value="baja">Baja</option>
                                        <option value="alta">Alta</option>
                                        <option value="critica">Crítica</option>
                                    </select>
                                </div>
                                <!-- En la sección de Datos de Ingreso, cambiar el campo de cama por esto: -->
                                <!-- SIMPLIFICADO: Solo cama -->
                                <div class="col-md-6">
                                    <label class="form-label">Cama Asignada: <span
                                            class="campo-requerido">*</span></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="cama_seleccionada" readonly
                                            placeholder="No asignada" style="background: #f8f9fa;">
                                        <button type="button" class="btn btn-outline-primary"
                                            onclick="abrirModalCamas()">
                                            <i class="fas fa-bed me-1"></i>Seleccionar Cama
                                        </button>
                                    </div>
                                    <input type="hidden" id="id_cama" name="id_cama">
                                    <small class="text-muted">La sala y piso se asignarán automáticamente según la cama
                                        seleccionada</small>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="firma_consentimiento"
                                            name="firma_consentimiento" value="1">
                                        <label class="form-check-label" for="firma_consentimiento">
                                            El paciente o familiar firmó consentimiento informado
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <label class="form-label">Observaciones Generales:</label>
                                    <textarea class="form-control" id="observaciones_generales"
                                        name="observaciones_generales" rows="2"
                                        placeholder="Observaciones adicionales del caso..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Signos Vitales Iniciales -->
                    <div class="section-card">
                        <div class="section-header" onclick="toggleSection('signos')">
                            <i class="fas fa-heartbeat me-2"></i>Signos Vitales Iniciales (Opcional)
                        </div>
                        <div class="section-content" id="signos-content">
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="form-label">Temperatura (°C):</label>
                                    <input type="number" step="0.1" class="form-control" id="temperatura"
                                        name="temperatura" placeholder="36.5">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Pulso (lpm):</label>
                                    <input type="number" class="form-control" id="pulso" name="pulso" placeholder="70">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Presión Sistólica:</label>
                                    <input type="number" class="form-control" id="presion_sistolica"
                                        name="presion_sistolica" placeholder="120">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Presión Diastólica:</label>
                                    <input type="number" class="form-control" id="presion_diastolica"
                                        name="presion_diastolica" placeholder="80">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Sat. O2 (%):</label>
                                    <input type="number" class="form-control" id="saturacion_oxigeno"
                                        name="saturacion_oxigeno" placeholder="98">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Peso (kg):</label>
                                    <input type="number" step="0.1" class="form-control" id="peso" name="peso"
                                        placeholder="70.0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Antecedentes Médicos -->
                    <div class="section-card">
                        <div class="section-header" onclick="toggleSection('antecedentes')">
                            <i class="fas fa-history me-2"></i>Antecedentes Médicos
                        </div>
                        <div class="section-content" id="antecedentes-content">
                            <div class="row mb-3">
                                <div class="col-12">
                                    <button type="button" class="btn btn-success" onclick="mostrarModalAntecedente()">
                                        <i class="fas fa-plus me-2"></i>Agregar Antecedente
                                    </button>
                                </div>
                            </div>
                            <div id="lista-antecedentes">
                                <!-- Aquí se mostrarán los antecedentes agregados -->
                            </div>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="row mt-4">
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save me-2"></i>Guardar Ingreso
                            </button>
                            <button type="button" class="btn btn-secondary btn-lg ms-2" onclick="limpiarFormulario()">
                                <i class="fas fa-eraser me-2"></i>Limpiar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para mostrar camas -->
    <div class="modal fade" id="modalCamas" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-bed me-2"></i>Seleccionar Cama Disponible
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Filtrar por Servicio:</label>
                            <select class="form-control" id="filtro_servicio" onchange="filtrarCamas()">
                                <option value="">Todos los servicios</option>
                                <option value="Emergencias">Emergencias</option>
                                <option value="UCI">UCI</option>
                                <option value="Medicina Interna">Medicina Interna</option>
                                <option value="Cirugía">Cirugía</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado:</label>
                            <div class="d-flex gap-3 align-items-center mt-2">
                                <span><i class="fas fa-circle text-success me-1"></i>Disponible</span>
                                <span><i class="fas fa-circle text-danger me-1"></i>Ocupada</span>
                                <span><i class="fas fa-circle text-warning me-1"></i>Mantenimiento</span>
                            </div>
                        </div>
                    </div>

                    <div id="loading-camas" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2">Cargando camas disponibles...</p>
                    </div>

                    <div id="grid-camas" class="row g-3" style="display: none;">
                        <!-- Las camas se cargan dinámicamente aquí -->
                    </div>

                    <div id="no-camas" class="alert alert-info text-center" style="display: none;">
                        <i class="fas fa-info-circle me-2"></i>
                        No hay camas disponibles en este momento
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btn-confirmar-cama" disabled
                        onclick="confirmarSeleccionCama()">
                        <i class="fas fa-check me-1"></i>Confirmar Selección
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- Modal para Antecedentes -->
    <div class="modal fade" id="modalAntecedente" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-history me-2"></i>Agregar Antecedente Médico
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formAntecedente">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Tipo de Antecedente: <span
                                        class="campo-requerido">*</span></label>
                                <select class="form-control" id="tipo_antecedente" name="tipo_antecedente" required>
                                    <option value="">Seleccionar tipo</option>
                                    <option value="personal">Personal</option>
                                    <option value="familiar">Familiar</option>
                                    <option value="quirurgico">Quirúrgico</option>
                                    <option value="farmacologico">Farmacológico</option>
                                    <option value="alergico">Alérgico</option>
                                    <option value="social">Social</option>
                                    <option value="gineco_obstetrico">Gineco-obstétrico</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Relevancia:</label>
                                <select class="form-control" id="relevancia" name="relevancia">
                                    <option value="media">Media</option>
                                    <option value="baja">Baja</option>
                                    <option value="alta">Alta</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Fecha del Evento:</label>
                                <input type="date" class="form-control" id="fecha_evento" name="fecha_evento">
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <label class="form-label">Descripción: <span class="campo-requerido">*</span></label>
                                <textarea class="form-control" id="descripcion_antecedente" name="descripcion" rows="4"
                                    required placeholder="Describa detalladamente el antecedente..."></textarea>
                            </div>
                        </div>

                        <!-- Área de subida de archivos -->
                        <div class="row mt-3">
                            <div class="col-12">
                                <label class="form-label">Archivos Adjuntos (Opcional):</label>
                                <div class="file-upload-area" id="fileUploadArea"
                                    onclick="document.getElementById('archivo_antecedente').click()">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                    <p class="mb-0">Haga clic aquí para seleccionar archivos</p>
                                    <p class="text-muted small">o arrastre y suelte los archivos</p>
                                    <p class="text-muted small">Formatos: JPG, PNG, PDF, DOC, DOCX (máx. 10MB)</p>
                                </div>
                                <input type="file" id="archivo_antecedente" name="archivo"
                                    accept="image/*,application/pdf,.doc,.docx,.txt" style="display: none"
                                    onchange="manejarArchivo(this)">

                                <!-- Vista previa de archivos -->
                                <div id="preview-container" class="preview-container" style="display: none;">
                                    <h6>Archivo seleccionado:</h6>
                                    <div id="file-preview"></div>
                                </div>

                                <!-- OCR Loading -->
                                <div id="ocr-loading" class="ocr-loading">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Procesando...</span>
                                    </div>
                                    <p class="mt-2">Extrayendo texto del documento...</p>
                                    <div class="progress progress-container">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated"
                                            role="progressbar" style="width: 0%"></div>
                                    </div>
                                </div>

                                <!-- Texto extraído -->
                                <div id="extracted-text" class="extracted-text" style="display: none;">
                                    <h6><i class="fas fa-eye me-2"></i>Texto extraído del documento:</h6>
                                    <div id="extracted-content"></div>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-primary"
                                            onclick="usarTextoExtraido()">
                                            <i class="fas fa-copy me-1"></i>Usar este texto
                                        </button>
                                        <button type="button" class="btn btn-sm btn-secondary"
                                            onclick="editarTextoExtraido()">
                                            <i class="fas fa-edit me-1"></i>Editar texto
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarAntecedente()">
                        <i class="fas fa-save me-2"></i>Guardar Antecedente
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let pacienteExiste = false;
    let antecedentesTemporales = [];
    let archivoActual = null;
    let textoExtraido = '';
    // ===== FUNCIONES BÁSICAS PARA CÉDULA CON PREFIJOS =====

    // Función para combinar tipo y número de cédula
    function combinarCedula(tipoCedula, numeroCedula) {
        if (!tipoCedula || !numeroCedula) return '';
        return tipoCedula + '-' + numeroCedula;
    }

    // Función para actualizar cédula de búsqueda
    function actualizarCedulaBuscar() {
        const tipoCedula = document.getElementById('tipo_cedula_buscar').value;
        const numeroCedula = document.getElementById('cedula_numero_buscar').value;
        const cedulaCompleta = combinarCedula(tipoCedula, numeroCedula);

        document.getElementById('cedula_buscar').value = cedulaCompleta;
    }

    // Validación básica del número de cédula
    function validarSoloNumerosCedula(input) {
        input.addEventListener('input', function() {
            // Solo permitir números
            this.value = this.value.replace(/[^0-9]/g, '');

            // Limitar a 8 dígitos
            if (this.value.length > 8) {
                this.value = this.value.substring(0, 8);
            }

            // Actualizar campo oculto
            actualizarCedulaBuscar();
        });
    }

    // Agregar a la inicialización existente
    document.addEventListener('DOMContentLoaded', function() {
        // ... tu código existente ...

        // AGREGAR ESTAS LÍNEAS NUEVAS:

        // Configurar validación para número de cédula en búsqueda
        const numeroCedulaBuscar = document.getElementById('cedula_numero_buscar');
        if (numeroCedulaBuscar) {
            validarSoloNumerosCedula(numeroCedulaBuscar);
        }

        // Listener para cambios en tipo de cédula de búsqueda
        const tipoCedulaBuscar = document.getElementById('tipo_cedula_buscar');
        if (tipoCedulaBuscar) {
            tipoCedulaBuscar.addEventListener('change', actualizarCedulaBuscar);
        }
    });
    // ===== FUNCIONES ADICIONALES PARA FORMULARIO PRINCIPAL =====

    // Función para actualizar cédula completa (formulario principal)
    function actualizarCedulaCompleta() {
        const tipoCedula = document.getElementById('tipo_cedula').value;
        const numeroCedula = document.getElementById('cedula_numero').value;
        const cedulaCompleta = combinarCedula(tipoCedula, numeroCedula);

        document.getElementById('cedula').value = cedulaCompleta;
    }

    // Función para separar cédula completa
    function separarCedula(cedulaCompleta) {
        if (!cedulaCompleta) return {
            tipo: '',
            numero: ''
        };

        const partes = cedulaCompleta.split('-');
        if (partes.length === 2) {
            return {
                tipo: partes[0],
                numero: partes[1]
            };
        }

        // Si no tiene guión, asumir que es solo el número
        return {
            tipo: '',
            numero: cedulaCompleta.replace(/[^0-9]/g, '')
        };
    }

    // ACTUALIZAR la función DOMContentLoaded existente
    // BUSCA donde dice "document.addEventListener('DOMContentLoaded', function () {"
    // y AGREGA estas líneas dentro:

    // Configurar validación para número de cédula en formulario principal
    const numeroCedula = document.getElementById('cedula_numero');
    if (numeroCedula) {
        numeroCedula.addEventListener('input', function() {
            // Solo permitir números
            this.value = this.value.replace(/[^0-9]/g, '');

            // Limitar a 8 dígitos
            if (this.value.length > 8) {
                this.value = this.value.substring(0, 8);
            }

            // Actualizar campo oculto
            actualizarCedulaCompleta();
        });
    }

    // Listener para cambios en tipo de cédula del formulario
    const tipoCedula = document.getElementById('tipo_cedula');
    if (tipoCedula) {
        tipoCedula.addEventListener('change', actualizarCedulaCompleta);
    }

    // ===== CONFIGURACIÓN OCR =====
    const configuracionOCR = {
        idiomas: 'spa+eng',
        opciones: {
            preserve_interword_spaces: '1',
            tessedit_char_whitelist: 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 .,;:!?-()[]{}ñÑáéíóúÁÉÍÓÚ',
            tessedit_pageseg_mode: '1'
        }
    };

    // ===== INICIALIZACIÓN =====
    document.addEventListener('DOMContentLoaded', function() {
        const now = new Date();
        document.getElementById('fecha_ingreso').value = now.toISOString().split('T')[0];
        document.getElementById('hora_ingreso').value = now.toTimeString().split(':').slice(0, 2).join(':');

        cargarProximoNumero();
        cargarMedicos();
        configurarDragAndDrop();
    });

    // ===== FUNCIONES BÁSICAS =====
    function toggleSection(sectionId) {
        const content = document.getElementById(sectionId + '-content');
        content.classList.toggle('active');
    }

    function cargarMedicos() {
        fetch('obtener_personal.php?tipo=medico')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ok') {
                    const selectIngreso = document.getElementById('id_medico_ingreso');
                    const selectResponsable = document.getElementById('id_medico_responsable');

                    selectIngreso.innerHTML = '<option value="">Seleccionar médico...</option>';
                    selectResponsable.innerHTML = '<option value="">Seleccionar médico responsable...</option>';

                    data.data.forEach(medico => {
                        const option1 = new Option(
                            `Dr. ${medico.nombre_completo} - ${medico.especialidad || 'General'}`,
                            medico.id
                        );
                        const option2 = new Option(
                            `Dr. ${medico.nombre_completo} - ${medico.especialidad || 'General'}`,
                            medico.id
                        );

                        selectIngreso.add(option1);
                        selectResponsable.add(option2);
                    });
                }
            })
            .catch(error => {
                console.error('Error cargando médicos:', error);
                Swal.fire('Advertencia', 'No se pudieron cargar los médicos.', 'warning');
            });
    }

    function cargarProximoNumero() {
        fetch('obtener_proximo_numero.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ok') {
                    document.getElementById('numero_historia').value = data.data.numero_historia;
                }
            })
            .catch(error => {
                console.log('Error obteniendo próximo número:', error);
                document.getElementById('numero_historia').placeholder = 'Se generará automáticamente';
            });
    }

    function refrescarNumeroHistoria() {
        if (!pacienteExiste) {
            cargarProximoNumero();
            Swal.fire({
                title: 'Número actualizado',
                text: 'Se ha cargado el próximo número disponible',
                icon: 'info',
                timer: 2000,
                showConfirmButton: false
            });
        }
    }

    // ===== BÚSQUEDA DE PACIENTES CON VALIDACIÓN =====
    function buscarPaciente() {
        const tipoCedula = document.getElementById('tipo_cedula_buscar').value;
        const numeroCedula = document.getElementById('cedula_numero_buscar').value.trim();

        if (!tipoCedula || !numeroCedula) {
            Swal.fire('Error', 'Debe seleccionar el tipo de cédula e ingresar el número', 'warning');
            return;
        }

        const cedula = combinarCedula(tipoCedula, numeroCedula);

        // Mostrar loading
        Swal.fire({
            title: 'Buscando paciente...',
            html: 'Verificando casos activos...',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('buscar_paciente.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'cedula=' + encodeURIComponent(cedula)
            })
            .then(response => response.json())
            .then(data => {
                Swal.close(); // Cerrar loading

                if (data.status === 'existe') {
                    // PACIENTE EXISTE Y NO TIENE CASOS ACTIVOS - PERMITIR
                    cargarDatosPaciente(data.data);
                    pacienteExiste = true;
                    Swal.fire({
                        title: '✅ Paciente encontrado',
                        text: 'Datos cargados correctamente. Sin casos activos.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });

                } else if (data.status === 'paciente_con_caso_activo') {
                    // PACIENTE EXISTE PERO TIENE CASOS ACTIVOS - NO PERMITIR
                    mostrarPacienteConCasoActivo(data.data);

                } else if (data.status === 'no_existe') {
                    // PACIENTE NO EXISTE - PERMITIR CREAR
                    // Cargar los datos separados para nuevo paciente
                    document.getElementById('tipo_cedula').value = tipoCedula;
                    document.getElementById('cedula_numero').value = numeroCedula;
                    actualizarCedulaCompleta();

                    pacienteExiste = false;
                    cargarProximoNumero();
                    toggleSection('paciente');
                    Swal.fire({
                        title: '👤 Paciente nuevo',
                        text: 'Complete los datos del nuevo paciente',
                        icon: 'info',
                        timer: 2000,
                        showConfirmButton: false
                    });

                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Error:', error);
                Swal.fire('Error', 'Error de conexión al buscar paciente', 'error');
            });
    }

    // ===== NUEVA FUNCIÓN: Mostrar paciente con caso activo =====
    function mostrarPacienteConCasoActivo(data) {
        const paciente = data.paciente;
        const casosActivos = data.casos_activos;

        // Construir información de casos activos
        let htmlCasos = '';
        casosActivos.forEach(caso => {
            const prioridadClass = {
                'baja': 'success',
                'media': 'info',
                'alta': 'warning',
                'critica': 'danger'
            };

            htmlCasos += `
            <div class="border rounded p-3 mb-2 bg-light">
                <div class="row">
                    <div class="col-md-8">
                        <h6 class="mb-1">
                            <span class="badge bg-primary">${caso.numero_caso}</span>
                            <span class="badge bg-${prioridadClass[caso.prioridad]} ms-1">${caso.prioridad.toUpperCase()}</span>
                        </h6>
                        <p class="mb-1"><strong>Motivo:</strong> ${caso.motivo_consulta}</p>
                        <p class="mb-1"><strong>Servicio:</strong> ${caso.servicio_actual || 'Sin asignar'}</p>
                        <p class="mb-0"><strong>Médico:</strong> ${caso.medico_responsable || 'Sin asignar'}</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <small class="text-muted">Ingreso: ${formatearFecha(caso.fecha_ingreso)}</small><br>
                        <button class="btn btn-sm btn-outline-primary mt-1" onclick="verCasoActivo('${caso.id_caso}')">
                            <i class="fas fa-eye me-1"></i>Ver Caso
                        </button>
                    </div>
                </div>
            </div>
        `;
        });

        Swal.fire({
            title: '⚠️ Paciente con Caso Activo',
            html: `
            <div class="text-start">
                <div class="alert alert-warning mb-3">
                    <h6 class="alert-heading">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No se puede crear un nuevo ingreso
                    </h6>
                    <p class="mb-0">
                        El paciente <strong>${paciente.nombres} ${paciente.apellidos}</strong> 
                        (C.I.: ${paciente.cedula}) ya tiene <strong>${data.cantidad_casos} caso(s) activo(s)</strong>.
                    </p>
                </div>
                
                <h6>Casos Activos:</h6>
                ${htmlCasos}
                
                <div class="alert alert-info mt-3">
                    <h6 class="alert-heading">¿Qué puede hacer?</h6>
                    <ul class="mb-0">
                        <li>Ver y gestionar el caso activo actual</li>
                        <li>Cerrar el caso activo si corresponde</li>
                        <li>Contactar al médico responsable</li>
                    </ul>
                </div>
            </div>
        `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '👁️ Ver Caso Principal',
            cancelButtonText: '🔍 Buscar Otro Paciente',
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            width: '700px',
            customClass: {
                popup: 'text-start'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Ir al caso más reciente
                verCasoActivo(casosActivos[0].id_caso);
            } else {
                // Limpiar búsqueda para buscar otro paciente
                document.getElementById('cedula_buscar').value = '';
                document.getElementById('cedula_buscar').focus();
            }
        });
    }

    // ===== NUEVA FUNCIÓN: Ver caso activo =====
    function verCasoActivo(idCaso) {
        // Ocultar el formulario actual
        document.querySelector('.container-fluid').style.display = 'none';

        // Crear contenedor para el iframe que ocupe toda la pantalla
        const iframeContainer = document.createElement('div');
        iframeContainer.id = 'caso-activo-container';
        iframeContainer.innerHTML = `
        <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999; background: white;">
            <div style="padding: 10px; background: #f8f9fa; border-bottom: 1px solid #dee2e6; display: flex; justify-content: space-between; align-items: center;">
                <h5 style="margin: 0;">
                    <i class="fas fa-eye me-2"></i>Dashboard del Caso
                </h5>
                <button type="button" class="btn btn-secondary" onclick="cerrarCasoActivo()">
                    <i class="fas fa-times me-2"></i>Volver al Formulario
                </button>
            </div>
            <iframe 
                src="../procesos/dashboard_procesos.php?caso=${idCaso}" 
                style="width: 100%; height: calc(100vh - 60px); border: none;" 
                frameborder="0">
            </iframe>
        </div>
    `;

        // Agregar al body
        document.body.appendChild(iframeContainer);
    }

    // ===== FUNCIÓN PARA CERRAR EL CASO ACTIVO =====
    function cerrarCasoActivo() {
        // Remover el contenedor del iframe
        const container = document.getElementById('caso-activo-container');
        if (container) {
            container.remove();
        }

        // Mostrar de nuevo el formulario
        document.querySelector('.container-fluid').style.display = 'block';
    }
    // ===== FUNCIÓN AUXILIAR: Formatear fecha =====
    function formatearFecha(fecha) {
        if (!fecha) return 'N/A';
        const date = new Date(fecha);
        return date.toLocaleDateString('es-ES');
    }

    function cargarDatosPaciente(paciente) {
        // ===== SEPARAR Y CARGAR CÉDULA =====
        const cedulaSeparada = separarCedula(paciente.cedula);

        // Cargar campos de cédula separados
        document.getElementById('tipo_cedula').value = cedulaSeparada.tipo;
        document.getElementById('cedula_numero').value = cedulaSeparada.numero;

        // Actualizar campo oculto
        actualizarCedulaCompleta();

        // ===== CARGAR RESTO DE CAMPOS =====
        document.getElementById('id_paciente').value = paciente.id_paciente;
        document.getElementById('numero_historia').value = paciente.numero_historia;
        document.getElementById('nombres').value = paciente.nombres;
        document.getElementById('apellidos').value = paciente.apellidos;
        document.getElementById('sexo').value = paciente.sexo;
        document.getElementById('fecha_nacimiento').value = paciente.fecha_nacimiento;
        document.getElementById('telefono').value = paciente.telefono || '';
        // 📱 AGREGAR: Separar teléfono si existe
        if (paciente.telefono) {
            const partes = paciente.telefono.split('-');
            if (partes.length === 2) {
                document.getElementById('cod_area_paciente').value = partes[0];
                document.getElementById('numero_paciente').value = partes[1];
            }
        }
        document.getElementById('direccion').value = paciente.direccion || '';
        document.getElementById('tipo_sangre').value = paciente.tipo_sangre || '';
        document.getElementById('seguro_medico').value = paciente.seguro_medico || '';
        document.getElementById('numero_seguro').value = paciente.numero_seguro || '';
        document.getElementById('alergias_conocidas').value = paciente.alergias_conocidas || '';

        // ===== DESHABILITAR CAMPOS DEL PACIENTE EXISTENTE =====
        document.getElementById('tipo_cedula').disabled = true;
        document.getElementById('cedula_numero').readOnly = true;
        document.getElementById('nombres').readOnly = true;
        document.getElementById('apellidos').readOnly = true;
        document.getElementById('sexo').disabled = true;

        // Mostrar sección de ingreso
        toggleSection('ingreso');

        console.log('Datos cargados:', {
            cedula_completa: paciente.cedula,
            tipo: cedulaSeparada.tipo,
            numero: cedulaSeparada.numero
        });
    }

    // ===== DRAG AND DROP =====
    function configurarDragAndDrop() {
        const uploadArea = document.getElementById('fileUploadArea');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, () => uploadArea.classList.add('dragover'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, () => uploadArea.classList.remove('dragover'), false);
        });

        uploadArea.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;

            if (files.length > 0) {
                const fileInput = document.getElementById('archivo_antecedente');
                fileInput.files = files;
                manejarArchivo(fileInput);
            }
        }
    }

    // ===== ANTECEDENTES =====
    function mostrarModalAntecedente() {
        const modal = new bootstrap.Modal(document.getElementById('modalAntecedente'));
        limpiarFormularioAntecedente();
        modal.show();
    }

    function limpiarFormularioAntecedente() {
        document.getElementById('formAntecedente').reset();
        document.getElementById('preview-container').style.display = 'none';
        document.getElementById('extracted-text').style.display = 'none';
        document.getElementById('ocr-loading').style.display = 'none';
        archivoActual = null;
        textoExtraido = '';
    }

    // ===== MANEJO DE ARCHIVOS =====
    function manejarArchivo(input) {
        const file = input.files[0];
        if (!file) return;

        archivoActual = file;
        mostrarVistaPrevia(file);

        if (file.type.startsWith('image/')) {
            procesarOCRMejorado(file).then(textoExtraido => {
                if (textoExtraido) {
                    const datosEstructurados = extraerDatosEstructurados(textoExtraido);
                    console.log('Datos estructurados extraídos:', datosEstructurados);
                    autoLlenarCampos(datosEstructurados);
                }
            });
        } else if (file.type === 'application/pdf') {
            procesarPDF(file);
        }
    }

    function mostrarVistaPrevia(file) {
        const previewContainer = document.getElementById('preview-container');
        const filePreview = document.getElementById('file-preview');

        previewContainer.style.display = 'block';

        let preview = '';
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview = `
                        <div class="file-preview">
                            <img src="${e.target.result}" alt="Preview">
                            <div class="flex-grow-1">
                                <strong>${file.name}</strong><br>
                                <small class="text-muted">${formatFileSize(file.size)} - ${file.type}</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-danger" onclick="eliminarArchivo()">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                filePreview.innerHTML = preview;
            };
            reader.readAsDataURL(file);
        } else {
            const icon = getFileIcon(file.type);
            preview = `
                    <div class="file-preview">
                        <div class="file-icon">
                            <i class="${icon}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <strong>${file.name}</strong><br>
                            <small class="text-muted">${formatFileSize(file.size)} - ${file.type}</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-danger" onclick="eliminarArchivo()">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
            filePreview.innerHTML = preview;
        }
    }

    function eliminarArchivo() {
        document.getElementById('archivo_antecedente').value = '';
        document.getElementById('preview-container').style.display = 'none';
        document.getElementById('extracted-text').style.display = 'none';
        document.getElementById('ocr-loading').style.display = 'none';
        archivoActual = null;
        textoExtraido = '';
    }

    function getFileIcon(mimeType) {
        if (mimeType.includes('pdf')) return 'fas fa-file-pdf text-danger';
        if (mimeType.includes('word')) return 'fas fa-file-word text-primary';
        if (mimeType.includes('text')) return 'fas fa-file-alt text-secondary';
        return 'fas fa-file text-muted';
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // ===== OCR AVANZADO =====
    async function procesarOCRMejorado(archivo) {
        const loadingElement = document.getElementById('ocr-loading');
        const progressBar = document.querySelector('.progress-bar');

        try {
            loadingElement.style.display = 'block';
            progressBar.style.width = '0%';

            const loadingText = loadingElement.querySelector('p');
            loadingText.textContent = 'Inicializando OCR...';

            // Verificar si Tesseract está disponible
            if (typeof Tesseract !== 'undefined') {
                // Crear worker de Tesseract
                const worker = await Tesseract.createWorker(configuracionOCR.idiomas, 1, {
                    logger: m => {
                        if (m.status) {
                            loadingText.textContent = `${m.status}: ${Math.round(m.progress * 100)}%`;
                            progressBar.style.width = (m.progress * 100) + '%';
                        }
                    }
                });

                // Configurar parámetros
                await worker.setParameters(configuracionOCR.opciones);

                // Procesar imagen
                loadingText.textContent = 'Analizando texto en la imagen...';
                const result = await worker.recognize(archivo);

                // Limpiar worker
                await worker.terminate();

                const textoExtraido = result.data.text.trim();
                const confianza = result.data.confidence;

                progressBar.style.width = '100%';
                setTimeout(() => {
                    loadingElement.style.display = 'none';
                }, 500);

                if (textoExtraido && confianza > 30) {
                    const textoLimpio = limpiarTextoOCR(textoExtraido);
                    mostrarTextoExtraidoMejorado(textoLimpio, {
                        confianza: Math.round(confianza),
                        palabras_detectadas: result.data.words ? result.data.words.length : 0
                    });
                    return textoLimpio;
                } else {
                    throw new Error('No se pudo extraer texto con suficiente confianza');
                }
            } else {
                throw new Error('Tesseract no está disponible');
            }

        } catch (error) {
            loadingElement.style.display = 'none';
            console.error('Error en OCR:', error);

            // Fallback con simulación
            const textoSimulado = "Texto extraído simulado del documento médico. OCR no disponible.";
            mostrarTextoExtraido(textoSimulado);

            return textoSimulado;
        }
    }

    function limpiarTextoOCR(texto) {
        let textoLimpio = texto
            .replace(/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/g, '')
            .replace(/\s+/g, ' ')
            .replace(/\s+([.,;:!?])/g, '$1')
            .replace(/([.,;:!?])\s*([.,;:!?])/g, '$1')
            .replace(/([0-9])\s+([0-9])/g, '$1$2')
            .split('\n')
            .filter(linea => linea.trim().length > 2)
            .join('\n');

        // Correcciones específicas médicas
        const correccionesMedicas = {
            'paci ente': 'paciente',
            'medi camento': 'medicamento',
            'diag nóstico': 'diagnóstico',
            'trata miento': 'tratamiento',
            'pre sión': 'presión',
            'temper atura': 'temperatura',
            'fre cuencia': 'frecuencia',
            'respir atoria': 'respiratoria',
            'hiper tensión': 'hipertensión',
            'diabe tes': 'diabetes',
            'melli tus': 'mellitus'
        };

        Object.keys(correccionesMedicas).forEach(error => {
            const regex = new RegExp(error, 'gi');
            textoLimpio = textoLimpio.replace(regex, correccionesMedicas[error]);
        });

        return textoLimpio.trim();
    }

    function mostrarTextoExtraidoMejorado(texto, metadatos = {}) {
        textoExtraido = texto;

        const extractedContent = document.getElementById('extracted-content');
        const extractedTextElement = document.getElementById('extracted-text');

        let contenidoHTML = `
                <div class="border p-2 rounded bg-white mb-3" style="max-height: 200px; overflow-y: auto;">
                    ${texto.replace(/\n/g, '<br>')}
                </div>
            `;

        if (metadatos.confianza) {
            contenidoHTML += `
                    <div class="row text-sm">
                        <div class="col-md-4">
                            <small class="text-muted">
                                <i class="fas fa-chart-line me-1"></i>
                                Confianza: ${metadatos.confianza}%
                            </small>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">
                                <i class="fas fa-font me-1"></i>
                                Palabras: ${metadatos.palabras_detectadas || 'N/A'}
                            </small>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                Caracteres: ${texto.length}
                            </small>
                        </div>
                    </div>
                `;
        }

        extractedContent.innerHTML = contenidoHTML;
        extractedTextElement.style.display = 'block';

        extractedTextElement.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
        });
    }

    function mostrarTextoExtraido(texto) {
        textoExtraido = texto;
        document.getElementById('extracted-content').innerHTML =
            `<div class="border p-2 rounded bg-white" style="max-height: 200px; overflow-y: auto;">${texto}</div>`;
        document.getElementById('extracted-text').style.display = 'block';
    }

    function procesarPDF(file) {
        document.getElementById('ocr-loading').style.display = 'block';

        setTimeout(() => {
            document.getElementById('ocr-loading').style.display = 'none';
            const textoSimulado = "Texto extraído del documento PDF (simulado)...";
            mostrarTextoExtraido(textoSimulado);
        }, 2000);
    }

    // ===== EXTRACCIÓN DE DATOS ESTRUCTURADOS =====
    function extraerDatosEstructurados(texto) {
        const datos = {};

        const patrones = {
            presion_arterial: /(?:PA|TA|Presión|Tensión)[\s:]*(\d{2,3}\/\d{2,3})\s*(?:mmHg)?/gi,
            temperatura: /(?:T°|Temperatura)[\s:]*(\d{1,2}(?:\.\d+)?)\s*(?:°C|grados)?/gi,
            frecuencia_cardiaca: /(?:FC|Frecuencia Cardíaca|Pulso)[\s:]*(\d{2,3})\s*(?:bpm|lpm|x\')?/gi,
            saturacion_oxigeno: /(?:SatO2|SpO2|Saturación)[\s:]*(\d{1,3})%?/gi,
            peso: /(?:Peso)[\s:]*(\d{1,3}(?:\.\d+)?)\s*(?:kg|kilos)?/gi,
            fecha: /(\d{1,2}\/\d{1,2}\/\d{2,4})/g
        };

        Object.keys(patrones).forEach(clave => {
            const matches = [...texto.matchAll(patrones[clave])];
            if (matches.length > 0) {
                datos[clave] = matches.map(match => match[1]);
            }
        });

        return datos;
    }

    function autoLlenarCampos(datosEstructurados) {
        if (datosEstructurados.fecha && datosEstructurados.fecha.length > 0) {
            const fechaEvento = document.getElementById('fecha_evento');
            if (fechaEvento && !fechaEvento.value) {
                const fechaDetectada = datosEstructurados.fecha[0];
                const fechaConvertida = convertirFecha(fechaDetectada);
                if (fechaConvertida) {
                    fechaEvento.value = fechaConvertida;
                }
            }
        }

        if (Object.keys(datosEstructurados).some(key => ['presion_arterial', 'temperatura', 'frecuencia_cardiaca']
                .includes(key))) {

            Swal.fire({
                title: 'Datos detectados',
                text: 'Se detectaron signos vitales en el documento. ¿Desea copiarlos a los campos?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, copiar',
                cancelButtonText: 'No'
            }).then((result) => {
                if (result.isConfirmed) {
                    copiarSignosVitales(datosEstructurados);
                }
            });
        }
    }

    function convertirFecha(fechaString) {
        const formatos = [
            /(\d{1,2})\/(\d{1,2})\/(\d{4})/,
            /(\d{1,2})\/(\d{1,2})\/(\d{2})/,
            /(\d{4})-(\d{1,2})-(\d{1,2})/,
        ];

        for (const formato of formatos) {
            const match = fechaString.match(formato);
            if (match) {
                if (formato.source.includes('(\\d{4})')) {
                    return `${match[1]}-${match[2].padStart(2, '0')}-${match[3].padStart(2, '0')}`;
                } else {
                    let año = match[3];
                    if (año.length === 2) {
                        año = '20' + año;
                    }
                    return `${año}-${match[2].padStart(2, '0')}-${match[1].padStart(2, '0')}`;
                }
            }
        }

        return null;
    }

    function copiarSignosVitales(datos) {
        if (datos.presion_arterial) {
            const [sistolica, diastolica] = datos.presion_arterial[0].split('/');
            const campoSistolica = document.getElementById('presion_sistolica');
            const campoDiastolica = document.getElementById('presion_diastolica');

            if (campoSistolica && !campoSistolica.value) campoSistolica.value = sistolica;
            if (campoDiastolica && !campoDiastolica.value) campoDiastolica.value = diastolica;
        }

        if (datos.temperatura) {
            const campo = document.getElementById('temperatura');
            if (campo && !campo.value) {
                campo.value = datos.temperatura[0].replace(/[^\d.]/g, '');
            }
        }

        if (datos.frecuencia_cardiaca) {
            const campo = document.getElementById('pulso');
            if (campo && !campo.value) {
                campo.value = datos.frecuencia_cardiaca[0].replace(/[^\d]/g, '');
            }
        }

        if (datos.peso) {
            const campo = document.getElementById('peso');
            if (campo && !campo.value) {
                campo.value = datos.peso[0].replace(/[^\d.]/g, '');
            }
        }
    }

    // ===== FUNCIONES DE TEXTO EXTRAÍDO =====
    function usarTextoExtraido() {
        if (textoExtraido) {
            document.getElementById('descripcion_antecedente').value = textoExtraido;
            Swal.fire({
                title: 'Texto copiado',
                text: 'El texto extraído se ha copiado al campo de descripción',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        }
    }

    function editarTextoExtraido() {
        if (textoExtraido) {
            Swal.fire({
                title: 'Editar texto extraído',
                html: `<textarea id="texto-editable" class="form-control" rows="10">${textoExtraido}</textarea>`,
                showCancelButton: true,
                confirmButtonText: 'Guardar cambios',
                cancelButtonText: 'Cancelar',
                preConfirm: () => {
                    return document.getElementById('texto-editable').value;
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    textoExtraido = result.value;
                    mostrarTextoExtraido(textoExtraido);
                }
            });
        }
    }

    // ===== GUARDAR ANTECEDENTE =====
    function guardarAntecedente() {
        const form = document.getElementById('formAntecedente');
        const formData = new FormData(form);

        if (!formData.get('tipo_antecedente')) {
            Swal.fire('Error', 'Seleccione el tipo de antecedente', 'error');
            return;
        }

        if (!formData.get('descripcion')) {
            Swal.fire('Error', 'Ingrese la descripción del antecedente', 'error');
            return;
        }

        if (textoExtraido) {
            formData.append('texto_extraido', textoExtraido);
        }

        const antecedente = {
            id: Date.now(),
            tipo_antecedente: formData.get('tipo_antecedente'),
            descripcion: formData.get('descripcion'),
            fecha_evento: formData.get('fecha_evento'),
            relevancia: formData.get('relevancia'),
            archivo: archivoActual,
            texto_extraido: textoExtraido
        };

        antecedentesTemporales.push(antecedente);
        actualizarListaAntecedentes();

        const modal = bootstrap.Modal.getInstance(document.getElementById('modalAntecedente'));
        modal.hide();

        Swal.fire({
            title: 'Antecedente agregado',
            text: 'El antecedente se ha agregado correctamente',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    }

    // ===== LISTA DE ANTECEDENTES =====
    function actualizarListaAntecedentes() {
        const lista = document.getElementById('lista-antecedentes');

        if (antecedentesTemporales.length === 0) {
            lista.innerHTML = '<p class="text-muted">No se han agregado antecedentes</p>';
            return;
        }

        let html = '';
        antecedentesTemporales.forEach((ant, index) => {
            const tipoLabel = {
                'personal': 'Personal',
                'familiar': 'Familiar',
                'quirurgico': 'Quirúrgico',
                'farmacologico': 'Farmacológico',
                'alergico': 'Alérgico',
                'social': 'Social',
                'gineco_obstetrico': 'Gineco-obstétrico'
            };

            const relevanciaClass = {
                'baja': 'secondary',
                'media': 'primary',
                'alta': 'danger'
            };

            html += `
                    <div class="antecedente-item" data-index="${index}">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="mb-1">
                                    <span class="badge bg-${relevanciaClass[ant.relevancia]}">${tipoLabel[ant.tipo_antecedente]}</span>
                                    ${ant.fecha_evento ? `<small class="text-muted ms-2">${formatearFecha(ant.fecha_evento)}</small>` : ''}
                                </h6>
                                <span class="badge bg-outline-${relevanciaClass[ant.relevancia]} text-${relevanciaClass[ant.relevancia]} border border-${relevanciaClass[ant.relevancia]}">
                                    Relevancia: ${ant.relevancia.charAt(0).toUpperCase() + ant.relevancia.slice(1)}
                                </span>
                            </div>
                            <div class="antecedente-actions">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="editarAntecedente(${index})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarAntecedente(${index})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <p class="mb-2">${ant.descripcion}</p>
                        ${ant.archivo ? `<small class="text-info"><i class="fas fa-paperclip me-1"></i>Archivo adjunto: ${ant.archivo.name}</small>` : ''}
                        ${ant.texto_extraido ? `<br><small class="text-success"><i class="fas fa-eye me-1"></i>Texto extraído disponible</small>` : ''}
                    </div>
                `;
        });

        lista.innerHTML = html;
    }

    function editarAntecedente(index) {
        const ant = antecedentesTemporales[index];

        document.getElementById('tipo_antecedente').value = ant.tipo_antecedente;
        document.getElementById('descripcion_antecedente').value = ant.descripcion;
        document.getElementById('fecha_evento').value = ant.fecha_evento || '';
        document.getElementById('relevancia').value = ant.relevancia;

        if (ant.archivo) {
            archivoActual = ant.archivo;
            mostrarVistaPrevia(ant.archivo);
        }

        if (ant.texto_extraido) {
            textoExtraido = ant.texto_extraido;
            mostrarTextoExtraido(ant.texto_extraido);
        }

        document.querySelector('#modalAntecedente .btn-primary').onclick = function() {
            actualizarAntecedente(index);
        };

        const modal = new bootstrap.Modal(document.getElementById('modalAntecedente'));
        modal.show();
    }

    function actualizarAntecedente(index) {
        const form = document.getElementById('formAntecedente');
        const formData = new FormData(form);

        if (!formData.get('tipo_antecedente')) {
            Swal.fire('Error', 'Seleccione el tipo de antecedente', 'error');
            return;
        }

        if (!formData.get('descripcion')) {
            Swal.fire('Error', 'Ingrese la descripción del antecedente', 'error');
            return;
        }

        antecedentesTemporales[index] = {
            ...antecedentesTemporales[index],
            tipo_antecedente: formData.get('tipo_antecedente'),
            descripcion: formData.get('descripcion'),
            fecha_evento: formData.get('fecha_evento'),
            relevancia: formData.get('relevancia'),
            archivo: archivoActual,
            texto_extraido: textoExtraido
        };

        actualizarListaAntecedentes();

        document.querySelector('#modalAntecedente .btn-primary').onclick = guardarAntecedente;

        const modal = bootstrap.Modal.getInstance(document.getElementById('modalAntecedente'));
        modal.hide();

        Swal.fire({
            title: 'Antecedente actualizado',
            text: 'Los cambios se han guardado correctamente',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    }

    function eliminarAntecedente(index) {
        Swal.fire({
            title: '¿Eliminar antecedente?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                antecedentesTemporales.splice(index, 1);
                actualizarListaAntecedentes();

                Swal.fire({
                    title: 'Eliminado',
                    text: 'El antecedente ha sido eliminado',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });
    }

    function formatearFecha(fecha) {
        if (!fecha) return '';
        const date = new Date(fecha);
        return date.toLocaleDateString('es-ES');
    }

    // ===== ENVÍO DEL FORMULARIO =====
    function validarFormulario() {
        const medicoIngreso = document.getElementById('id_medico_ingreso').value;

        if (!medicoIngreso) {
            Swal.fire('Error', 'Debe seleccionar un médico de ingreso', 'error');
            return false;
        }

        return true;
    }

    document.getElementById('formIngreso').addEventListener('submit', function(e) {
        e.preventDefault();

        // ✅ PROTECCIÓN CONTRA DOBLE CLIC
        const submitBtn = document.querySelector('button[type="submit"]');
        if (submitBtn.disabled) {
            return; // Ya se está procesando
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';

        if (!validarFormulario()) {
            // Re-habilitar botón si hay errores
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Guardar Ingreso';
            return;
        }

        const formData = new FormData(this);

        if (!pacienteExiste) {
            crearPaciente(formData);
        } else {
            crearCaso(formData);
        }
    });

    function crearPaciente(formData) {
        fetch('crear_paciente.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ok') {
                    document.getElementById('id_paciente').value = data.data.id_paciente;
                    formData.set('id_paciente', data.data.id_paciente);
                    crearCaso(formData);
                } else {
                    // ✅ AGREGAR: Re-habilitar botón en caso de error
                    const submitBtn = document.querySelector('button[type="submit"]');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Guardar Ingreso';

                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                // ✅ AGREGAR: Re-habilitar botón en caso de error
                const submitBtn = document.querySelector('button[type="submit"]');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Guardar Ingreso';

                Swal.fire('Error', 'Error de conexión', 'error');
            });
    }

    function crearCaso(formData) {
        fetch('crear_caso.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ok') {
                    if (antecedentesTemporales.length > 0) {
                        guardarAntecedentesEnCaso(data.data.id_caso, data.data.numero_caso);
                    } else {
                        mostrarExitoCreacion(data.data.numero_caso, data.data.id_caso);
                    }
                } else {
                    // ✅ AGREGAR: Re-habilitar botón en caso de error
                    const submitBtn = document.querySelector('button[type="submit"]');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Guardar Ingreso';

                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                // ✅ AGREGAR: Re-habilitar botón en caso de error
                const submitBtn = document.querySelector('button[type="submit"]');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Guardar Ingreso';

                Swal.fire('Error', 'Error de conexión', 'error');
            });
    }

    function guardarAntecedentesEnCaso(idCaso, numeroCaso) {
        let antecedentesGuardados = 0;
        const totalAntecedentes = antecedentesTemporales.length;

        if (totalAntecedentes === 0) {
            mostrarExitoCreacion(numeroCaso, idCaso);
            return;
        }

        Swal.fire({
            title: 'Guardando antecedentes...',
            html: `<div class="progress"><div class="progress-bar" style="width: 0%"></div></div>`,
            allowOutsideClick: false,
            showConfirmButton: false
        });

        antecedentesTemporales.forEach((antecedente, index) => {
            const formData = new FormData();
            formData.append('id_caso', idCaso);
            formData.append('tipo_antecedente', antecedente.tipo_antecedente);
            formData.append('descripcion', antecedente.descripcion);
            formData.append('fecha_evento', antecedente.fecha_evento || '');
            formData.append('relevancia', antecedente.relevancia);

            if (antecedente.archivo) {
                formData.append('archivo', antecedente.archivo);
            }

            if (antecedente.texto_extraido) {
                formData.append('texto_extraido', antecedente.texto_extraido);
            }

            fetch('agregar_antecedente.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    antecedentesGuardados++;

                    const progreso = (antecedentesGuardados / totalAntecedentes) * 100;
                    const progressBar = document.querySelector('.progress-bar');
                    if (progressBar) {
                        progressBar.style.width = progreso + '%';
                    }

                    if (antecedentesGuardados === totalAntecedentes) {
                        setTimeout(() => {
                            mostrarExitoCreacion(numeroCaso, idCaso);
                        }, 500);
                    }
                })
                .catch(error => {
                    console.error('Error guardando antecedente:', error);
                    antecedentesGuardados++;

                    if (antecedentesGuardados === totalAntecedentes) {
                        setTimeout(() => {
                            mostrarExitoCreacion(numeroCaso, idCaso);
                        }, 500);
                    }
                });
        });
    }

    function mostrarExitoCreacion(numeroCaso, idCaso) {
        Swal.fire({
            title: 'Éxito',
            text: `Ingreso registrado correctamente. Caso N°: ${numeroCaso}${antecedentesTemporales.length > 0 ? ` con ${antecedentesTemporales.length} antecedente(s)` : ''}`,
            icon: 'success',
            confirmButtonText: 'Ir al caso'
        }).then(() => {
            // Redirigir al dashboard del caso
            window.location.href = `../procesos/dashboard_procesos.php?caso=${idCaso}`;
        });
    }

    function limpiarFormulario() {
        document.getElementById('formIngreso').reset();
        // Limpiar campos de cédula de búsqueda
        document.getElementById('tipo_cedula_buscar').value = '';
        document.getElementById('cedula_numero_buscar').value = '';
        document.getElementById('cedula_buscar').value = '';

        // Limpiar campos de cédula del formulario
        document.getElementById('tipo_cedula').value = '';
        document.getElementById('cedula_numero').value = '';
        document.getElementById('cedula').value = '';
        pacienteExiste = false;
        antecedentesTemporales = [];

        // Rehabilitar campos
        // Rehabilitar campos
        document.getElementById('tipo_cedula').disabled = false;
        document.getElementById('cedula_numero').readOnly = false;
        document.getElementById('nombres').readOnly = false;
        document.getElementById('apellidos').readOnly = false;
        document.getElementById('sexo').disabled = false;

        // Limpiar lista de antecedentes
        actualizarListaAntecedentes();

        // Recargar número de historia y fecha/hora
        cargarProximoNumero();
        const now = new Date();
        document.getElementById('fecha_ingreso').value = now.toISOString().split('T')[0];
        document.getElementById('hora_ingreso').value = now.toTimeString().split(':').slice(0, 2).join(':');
    }

    // ===== FUNCIONES ADICIONALES DE UTILIDAD =====

    // Función para validar cédula venezolana mejorada
    function validarCedulaVenezolana(cedula) {
        // Limpiar la cédula (quitar espacios, guiones, etc.)
        cedula = cedula.toString().replace(/[^0-9]/g, '');

        // Verificar longitud (debe tener entre 7 y 8 dígitos)
        if (cedula.length < 7 || cedula.length > 8) {
            return false;
        }

        // Verificar que no sean todos números iguales
        if (/^(\d)\1+$/.test(cedula)) {
            return false;
        }

        // Verificar que no sea una secuencia obvia
        const secuencias = ['1234567', '12345678', '7654321', '87654321'];
        if (secuencias.includes(cedula)) {
            return false;
        }

        // Cédulas venezolanas válidas están en el rango de 1,000,000 a 99,999,999
        const numerocedula = parseInt(cedula);
        if (numerocedula < 1000000 || numerocedula > 99999999) {
            return false;
        }

        return true;
    }


    // Función para validar fecha de nacimiento
    function validarFechaNacimiento(fecha) {
        if (!fecha) return {
            valido: true,
            mensaje: ''
        };

        const fechaNacimiento = new Date(fecha);
        const hoy = new Date();
        const edad = Math.floor((hoy - fechaNacimiento) / (365.25 * 24 * 60 * 60 * 1000));

        // Verificar que no sea fecha futura
        if (fechaNacimiento > hoy) {
            return {
                valido: false,
                mensaje: 'La fecha de nacimiento no puede ser futura'
            };
        }

        // Verificar edad razonable (0 a 150 años)
        if (edad < 0) {
            return {
                valido: false,
                mensaje: 'La fecha de nacimiento no puede ser futura'
            };
        }

        if (edad > 150) {
            return {
                valido: false,
                mensaje: 'La fecha de nacimiento parece incorrecta (edad mayor a 150 años)'
            };
        }

        // Verificar fechas muy antiguas (antes de 1900)
        if (fechaNacimiento.getFullYear() < 1900) {
            return {
                valido: false,
                mensaje: 'La fecha de nacimiento no puede ser anterior a 1900'
            };
        }

        return {
            valido: true,
            mensaje: '',
            edad: edad
        };
    }

    // Función para validar teléfono venezolano
    function validarTelefonoVenezolano(telefono) {
        // Limpiar el teléfono
        telefono = telefono.replace(/[^0-9]/g, '');

        // Debe tener 11 dígitos y empezar con 0
        if (telefono.length !== 11 || !telefono.startsWith('0')) {
            return false;
        }

        // Códigos de área válidos para Venezuela
        const codigosValidos = [
            '0412', '0414', '0416', '0424', '0426', // Celulares
            '0212', '0213', '0214', '0215', '0216', '0218', // Caracas
            '0241', '0242', '0243', '0244', '0245', '0246', // Valencia/Aragua
            '0251', '0252', '0253', '0254', '0255', '0258', // Carabobo/Yaracuy
            '0261', '0262', '0263', '0264', '0265', '0267', // Zulia
            '0271', '0272', '0273', '0274', '0275', // Barinas/Portuguesa
            '0281', '0282', '0283', '0284', '0285', '0286', '0287', '0288', // Anzoátegui/Sucre
            '0291', '0292', '0293', '0294', '0295' // Delta Amacuro/Monagas
        ];

        const codigoArea = telefono.substring(0, 4);
        return codigosValidos.includes(codigoArea);
    }

    // Función para formatear teléfono venezolano
    function formatearTelefonoVenezolano(telefono) {
        // Limpiar el teléfono
        telefono = telefono.replace(/[^0-9]/g, '');

        if (telefono.length === 11) {
            // Formato: 0414-123-4567
            return telefono.replace(/(\d{4})(\d{3})(\d{4})/, '$1-$2-$3');
        } else if (telefono.length === 10 && !telefono.startsWith('0')) {
            // Si no tiene el 0 inicial, agregarlo
            telefono = '0' + telefono;
            return telefono.replace(/(\d{4})(\d{3})(\d{4})/, '$1-$2-$3');
        }

        return telefono;
    }

    // Auto-expansión de textareas
    function autoExpand(element) {
        element.style.height = 'auto';
        element.style.height = element.scrollHeight + 'px';
    }

    document.querySelectorAll('textarea').forEach(textarea => {
        textarea.addEventListener('input', function() {
            autoExpand(this);
        });
    });

    // Contador de caracteres para descripciones
    document.getElementById('descripcion_antecedente').addEventListener('input', function() {
        const maxLength = 5000;
        const currentLength = this.value.length;
        const porcentaje = (currentLength / maxLength) * 100;

        // Crear o actualizar contador si no existe
        let contador = document.getElementById('contador-caracteres');
        if (!contador) {
            contador = document.createElement('small');
            contador.id = 'contador-caracteres';
            contador.className = 'text-muted d-block mt-1';
            this.parentNode.appendChild(contador);
        }

        contador.textContent = `${currentLength}/${maxLength} caracteres`;

        if (porcentaje > 90) {
            contador.className = 'text-danger d-block mt-1';
        } else if (porcentaje > 70) {
            contador.className = 'text-warning d-block mt-1';
        } else {
            contador.className = 'text-muted d-block mt-1';
        }
    });

    // Función para detectar cambios no guardados
    let formModificado = false;

    function marcarFormularioModificado() {
        formModificado = true;
    }

    document.getElementById('formIngreso').addEventListener('input', marcarFormularioModificado);
    document.getElementById('formIngreso').addEventListener('change', marcarFormularioModificado);

    window.addEventListener('beforeunload', function(e) {
        if (formModificado && !confirm(
                '¿Está seguro de que desea salir? Los cambios no guardados se perderán.')) {
            e.preventDefault();
            e.returnValue = '';
            return '';
        }
    });

    // Resetear flag cuando se guarda exitosamente
    function resetearFormularioModificado() {
        formModificado = false;
    }

    // Función mejorada para guardar borrador (evitar datos inválidos)
    function guardarBorradorMejorado() {
        if (!formModificado) return;

        const datosFormulario = {
            paciente: {
                cedula: document.getElementById('cedula').value,
                nombres: document.getElementById('nombres').value,
                apellidos: document.getElementById('apellidos').value,
                sexo: document.getElementById('sexo').value,
                fecha_nacimiento: document.getElementById('fecha_nacimiento').value,
                telefono: document.getElementById('telefono').value,
                direccion: document.getElementById('direccion').value
            },
            ingreso: {
                fecha_ingreso: document.getElementById('fecha_ingreso').value,
                hora_ingreso: document.getElementById('hora_ingreso').value,
                servicio_ingreso: document.getElementById('servicio_ingreso').value,
                tipo_ingreso: document.getElementById('tipo_ingreso').value,
                id_medico_ingreso: document.getElementById('id_medico_ingreso').value,
                id_medico_responsable: document.getElementById('id_medico_responsable').value,
                motivo_consulta: document.getElementById('motivo_consulta').value,
                enfermedad_actual: document.getElementById('enfermedad_actual').value
            },
            signos_vitales: {
                temperatura: document.getElementById('temperatura').value,
                pulso: document.getElementById('pulso').value,
                presion_sistolica: document.getElementById('presion_sistolica').value,
                presion_diastolica: document.getElementById('presion_diastolica').value,
                saturacion_oxigeno: document.getElementById('saturacion_oxigeno').value,
                peso: document.getElementById('peso').value
            },
            antecedentes: antecedentesTemporales.map(ant => ({
                tipo_antecedente: ant.tipo_antecedente,
                descripcion: ant.descripcion,
                fecha_evento: ant.fecha_evento,
                relevancia: ant.relevancia,
                texto_extraido: ant.texto_extraido
            })),
            timestamp: new Date().toISOString(),
            version: '2.0' // Versión del borrador para compatibilidad
        };

        // Solo guardar si hay datos significativos
        const tieneDatos = datosFormulario.paciente.cedula ||
            datosFormulario.paciente.nombres ||
            datosFormulario.ingreso.motivo_consulta;

        if (tieneDatos) {
            localStorage.setItem('borrador_ingreso_v2', JSON.stringify(datosFormulario));
            console.log('Borrador guardado automáticamente (v2.0)');
        }
    }

    // Función mejorada para cargar borrador
    function cargarBorradorMejorado() {
        const borrador = localStorage.getItem('borrador_ingreso_v2') || localStorage.getItem('borrador_ingreso');
        if (!borrador) return false;

        try {
            const datos = JSON.parse(borrador);
            const fechaBorrador = new Date(datos.timestamp);
            const ahora = new Date();
            const horasTranscurridas = (ahora - fechaBorrador) / (1000 * 60 * 60);

            // Solo cargar si el borrador es de menos de 24 horas
            if (horasTranscurridas > 24) {
                localStorage.removeItem('borrador_ingreso_v2');
                localStorage.removeItem('borrador_ingreso');
                return false;
            }

            Swal.fire({
                title: '📋 Borrador encontrado',
                html: `
                <div class="text-start">
                    <p>Se encontró un borrador guardado hace <strong>${Math.round(horasTranscurridas)} horas</strong>.</p>
                    <div class="alert alert-info">
                        <small>
                            <strong>Datos del borrador:</strong><br>
                            • Paciente: ${datos.paciente.nombres || 'Sin nombre'} ${datos.paciente.apellidos || ''}<br>
                            • Cédula: ${datos.paciente.cedula || 'Sin cédula'}<br>
                            • Antecedentes: ${datos.antecedentes?.length || 0}
                        </small>
                    </div>
                </div>
            `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '✅ Sí, cargar borrador',
                cancelButtonText: '🗑️ No, empezar nuevo',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#dc3545'
            }).then((result) => {
                if (result.isConfirmed) {
                    cargarDatosBorrador(datos);
                    Swal.fire({
                        title: '✅ Borrador cargado',
                        text: 'Se han restaurado los datos del borrador',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    formModificado = true;
                } else {
                    limpiarBorradoresAntiguos();
                }
            });

            return true;
        } catch (error) {
            console.error('Error cargando borrador:', error);
            limpiarBorradoresAntiguos();
            return false;
        }
    }

    function cargarDatosBorrador(datos) {
        // Cargar datos del paciente con validación
        Object.keys(datos.paciente).forEach(campo => {
            const elemento = document.getElementById(campo);
            if (elemento && datos.paciente[campo]) {
                elemento.value = datos.paciente[campo];

                // Aplicar formateo específico
                if (campo === 'telefono') {
                    elemento.value = formatearTelefonoVenezolano(datos.paciente[campo]);
                }
            }
        });

        // Cargar datos de ingreso
        Object.keys(datos.ingreso).forEach(campo => {
            const elemento = document.getElementById(campo);
            if (elemento && datos.ingreso[campo]) {
                elemento.value = datos.ingreso[campo];
            }
        });

        // Cargar signos vitales
        Object.keys(datos.signos_vitales).forEach(campo => {
            const elemento = document.getElementById(campo);
            if (elemento && datos.signos_vitales[campo]) {
                elemento.value = datos.signos_vitales[campo];
            }
        });

        // Cargar antecedentes
        antecedentesTemporales = datos.antecedentes || [];
        actualizarListaAntecedentes();

        // Expandir secciones con datos
        if (datos.paciente.nombres || datos.paciente.cedula) {
            toggleSection('paciente');
        }
        if (datos.ingreso.motivo_consulta) {
            toggleSection('ingreso');
        }
        if (datos.antecedentes && datos.antecedentes.length > 0) {
            toggleSection('antecedentes');
        }
    }

    function limpiarBorradoresAntiguos() {
        localStorage.removeItem('borrador_ingreso_v2');
        localStorage.removeItem('borrador_ingreso');
    }

    // ===== VALIDACIÓN COMPLETA DEL FORMULARIO CON CAMA OBLIGATORIA =====

    function validarFormularioCompleto() {
        const errores = [];

        // Validar cédula
        const cedula = document.getElementById('cedula').value.trim();
        if (!cedula) {
            errores.push('La cédula es obligatoria');
        } else if (!validarCedulaVenezolana(cedula)) {
            errores.push('La cédula no es válida');
        }

        // Validar nombres
        const nombres = document.getElementById('nombres').value.trim();
        if (!nombres) {
            errores.push('Los nombres son obligatorios');
        } else if (nombres.length < 2) {
            errores.push('Los nombres deben tener al menos 2 caracteres');
        }

        // Validar apellidos
        const apellidos = document.getElementById('apellidos').value.trim();
        if (!apellidos) {
            errores.push('Los apellidos son obligatorios');
        } else if (apellidos.length < 2) {
            errores.push('Los apellidos deben tener al menos 2 caracteres');
        }

        // Validar sexo
        const sexo = document.getElementById('sexo').value;
        if (!sexo) {
            errores.push('Debe seleccionar el sexo');
        }

        // Validar servicio de ingreso
        const servicioIngreso = document.getElementById('servicio_ingreso').value;
        if (!servicioIngreso) {
            errores.push('Debe seleccionar el servicio de ingreso');
        }

        // ✅ VALIDAR CAMA SELECCIONADA (NUEVO - OBLIGATORIO)
        const camaSeleccionada = document.getElementById('id_cama_seleccionada').value;
        if (!camaSeleccionada) {
            errores.push('Debe seleccionar una cama para el paciente');
        }

        // Validar médico de ingreso
        const medicoIngreso = document.getElementById('id_medico_ingreso').value;
        if (!medicoIngreso) {
            errores.push('Debe seleccionar un médico de ingreso');
        }

        // Validar motivo de consulta
        const motivoConsulta = document.getElementById('motivo_consulta').value.trim();
        if (!motivoConsulta) {
            errores.push('El motivo de consulta es obligatorio');
        } else if (motivoConsulta.length < 10) {
            errores.push('El motivo de consulta debe ser más descriptivo (mínimo 10 caracteres)');
        }

        // Validar fecha de ingreso
        const fechaIngreso = document.getElementById('fecha_ingreso').value;
        if (!fechaIngreso) {
            errores.push('La fecha de ingreso es obligatoria');
        } else {
            const fecha = new Date(fechaIngreso);
            const hoy = new Date();
            const unaSemanaAtras = new Date(hoy.getTime() - (7 * 24 * 60 * 60 * 1000));

            if (fecha > hoy) {
                errores.push('La fecha de ingreso no puede ser futura');
            } else if (fecha < unaSemanaAtras) {
                errores.push('La fecha de ingreso no puede ser mayor a 7 días atrás');
            }
        }

        // Validar hora de ingreso
        const horaIngreso = document.getElementById('hora_ingreso').value;
        if (!horaIngreso) {
            errores.push('La hora de ingreso es obligatoria');
        }

        // Validar teléfono si está presente
        const telefono = document.getElementById('telefono').value.trim();
        if (telefono && !validarTelefonoVenezolano(telefono)) {
            errores.push('El teléfono del paciente no es válido');
        }

        // Validar fecha de nacimiento si está presente
        const fechaNacimiento = document.getElementById('fecha_nacimiento').value;
        if (fechaNacimiento) {
            const validacion = validarFechaNacimiento(fechaNacimiento);
            if (!validacion.valido) {
                errores.push('La fecha de nacimiento no es válida: ' + validacion.mensaje);
            }
        }

        // Validar teléfono del acompañante si se proporciona
        const telefonoAcompanante = document.getElementById('telefono_acompanante').value.trim();
        if (telefonoAcompanante && !validarTelefonoVenezolano(telefonoAcompanante)) {
            errores.push('El teléfono del acompañante no es válido');
        }

        // Validar que si hay acompañante, tenga al menos nombre
        const acompanante = document.getElementById('acompanante').value.trim();
        const parentesco = document.getElementById('parentesco_acompanante').value.trim();
        if ((parentesco || telefonoAcompanante) && !acompanante) {
            errores.push('Si proporciona datos del acompañante, debe incluir el nombre');
        }

        // Validar coherencia de signos vitales
        const sistolica = document.getElementById('presion_sistolica').value;
        const diastolica = document.getElementById('presion_diastolica').value;
        if (sistolica && diastolica) {
            if (parseInt(sistolica) <= parseInt(diastolica)) {
                errores.push('La presión sistólica debe ser mayor que la diastólica');
            }
        }

        // ✅ VALIDAR COHERENCIA CAMA-SERVICIO
        if (camaSeleccionada && servicioIngreso) {
            const salaSeleccionada = document.getElementById('sala_seleccionada').value;
            if (salaSeleccionada && salaSeleccionada !== servicioIngreso) {
                errores.push(
                    `La cama seleccionada pertenece a ${salaSeleccionada}, pero el servicio es ${servicioIngreso}`);
            }
        }

        return errores;
    }

    // ===== VALIDACIÓN ESPECÍFICA PARA CAMA =====
    function validarCamaObligatoria() {
        const camaSeleccionada = document.getElementById('id_cama_seleccionada').value;
        const servicioIngreso = document.getElementById('servicio_ingreso').value;

        if (!camaSeleccionada) {
            Swal.fire({
                title: '🛏️ Cama requerida',
                html: `
                <div class="text-center">
                    <div class="mb-3">
                        <i class="fas fa-bed fa-3x text-primary"></i>
                    </div>
                    <p>Debe seleccionar una cama antes de continuar con el ingreso.</p>
                    ${servicioIngreso ? `<p class="text-muted">Servicio: <strong>${servicioIngreso}</strong></p>` : ''}
                </div>
            `,
                icon: 'warning',
                confirmButtonText: '🛏️ Seleccionar Cama',
                showCancelButton: true,
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#007bff',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    abrirModalCamas();
                }
            });
            return false;
        }

        return true;
    }

    // ===== MOSTRAR ERRORES DE VALIDACIÓN MEJORADO =====
    function mostrarErroresValidacion(errores) {
        // Agrupar errores por categoría
        const erroresPorCategoria = {
            'Datos del Paciente': [],
            'Datos de Ingreso': [],
            'Cama y Ubicación': [],
            'Signos Vitales': [],
            'Acompañante': []
        };

        errores.forEach(error => {
            if (error.includes('cédula') || error.includes('nombres') || error.includes('apellidos') ||
                error.includes('sexo') || error.includes('teléfono del paciente') || error.includes(
                    'fecha de nacimiento')) {
                erroresPorCategoria['Datos del Paciente'].push(error);
            } else if (error.includes('servicio') || error.includes('médico') || error.includes('motivo') ||
                error.includes('fecha de ingreso') || error.includes('hora')) {
                erroresPorCategoria['Datos de Ingreso'].push(error);
            } else if (error.includes('cama') || error.includes('sala') || error.includes('servicio')) {
                erroresPorCategoria['Cama y Ubicación'].push(error);
            } else if (error.includes('presión') || error.includes('signos')) {
                erroresPorCategoria['Signos Vitales'].push(error);
            } else if (error.includes('acompañante')) {
                erroresPorCategoria['Acompañante'].push(error);
            } else {
                erroresPorCategoria['Datos de Ingreso'].push(error);
            }
        });

        let htmlErrores = '';
        Object.keys(erroresPorCategoria).forEach(categoria => {
            if (erroresPorCategoria[categoria].length > 0) {
                htmlErrores += `
                <div class="mb-3">
                    <h6 class="text-danger mb-2">
                        <i class="fas fa-exclamation-circle me-1"></i>${categoria}
                    </h6>
                    <ul class="text-start text-danger mb-0">
                        ${erroresPorCategoria[categoria].map(error => `<li>${error}</li>`).join('')}
                    </ul>
                </div>
            `;
            }
        });

        Swal.fire({
            title: '❌ Errores en el formulario',
            html: `
            <div class="text-start">
                <p class="text-center mb-3">Por favor corrija los siguientes errores antes de continuar:</p>
                ${htmlErrores}
            </div>
        `,
            icon: 'error',
            confirmButtonText: 'Entendido',
            width: '600px',
            customClass: {
                popup: 'text-start'
            }
        });
    }

    // ===== ACTUALIZAR EL EVENTO DE ENVÍO DEL FORMULARIO =====
    document.addEventListener('DOMContentLoaded', function() {
        const formulario = document.getElementById('formIngreso');
        if (formulario) {
            // Remover listener anterior si existe
            formulario.removeEventListener('submit', enviarFormulario);

            // Agregar nuevo listener
            formulario.addEventListener('submit', function(e) {
                e.preventDefault();

                // ✅ PROTECCIÓN CONTRA DOBLE CLIC
                const submitBtn = document.querySelector('button[type="submit"]');
                if (submitBtn.disabled) {
                    return; // Ya se está procesando
                }

                // Validar formulario completo
                const errores = validarFormularioCompleto();

                if (errores.length > 0) {
                    mostrarErroresValidacion(errores);
                    return;
                }

                // Validación adicional de cama
                if (!validarCamaObligatoria()) {
                    return;
                }

                // Deshabilitar botón y mostrar loading
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';

                // Marcar que el formulario se está enviando
                window.formularioEnviandose = true;

                const formData = new FormData(this);

                if (!pacienteExiste) {
                    crearPaciente(formData);
                } else {
                    crearCaso(formData);
                }
            });
        }
    });

    // ===== FUNCIÓN PARA REACTIVAR BOTÓN EN CASO DE ERROR =====
    function reactivarBotonEnvio() {
        const submitBtn = document.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Guardar Ingreso';
        }
        window.formularioEnviandose = false;
    }

    // ===== ACTUALIZAR FUNCIONES DE CREACIÓN PARA MANEJAR ERRORES =====
    function crearPaciente(formData) {
        fetch('crear_paciente.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ok') {
                    document.getElementById('id_paciente').value = data.data.id_paciente;
                    formData.set('id_paciente', data.data.id_paciente);
                    crearCaso(formData);
                } else {
                    reactivarBotonEnvio();
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                reactivarBotonEnvio();
                console.error('Error:', error);
                Swal.fire('Error', 'Error de conexión al crear paciente', 'error');
            });
    }

    function crearCaso(formData) {
        fetch('crear_caso.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ok') {
                    // Limpiar progreso guardado
                    if (typeof limpiarProgresoGuardado === 'function') {
                        limpiarProgresoGuardado();
                    }

                    if (antecedentesTemporales.length > 0) {
                        guardarAntecedentesEnCaso(data.data.id_caso, data.data.numero_caso);
                    } else {
                        mostrarExitoCreacion(data.data.numero_caso, data.data.id_caso);
                    }
                } else {
                    reactivarBotonEnvio();
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                reactivarBotonEnvio();
                console.error('Error:', error);
                Swal.fire('Error', 'Error de conexión al crear caso', 'error');
            });
    }

    // ===== VALIDACIÓN EN TIEMPO REAL DE COHERENCIA CAMA-SERVICIO =====
    document.addEventListener('DOMContentLoaded', function() {
        const servicioIngreso = document.getElementById('servicio_ingreso');
        const camaSeleccionada = document.getElementById('id_cama_seleccionada');

        if (servicioIngreso) {
            servicioIngreso.addEventListener('change', function() {
                // Si hay una cama seleccionada, verificar coherencia
                const salaSeleccionada = document.getElementById('sala_seleccionada').value;

                if (camaSeleccionada.value && salaSeleccionada && salaSeleccionada !== this.value) {
                    Swal.fire({
                        title: '⚠️ Inconsistencia detectada',
                        html: `
                        <div class="text-center">
                            <p>La cama seleccionada pertenece a <strong>${salaSeleccionada}</strong>, pero cambió el servicio a <strong>${this.value}</strong>.</p>
                            <p>¿Qué desea hacer?</p>
                        </div>
                    `,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: '🛏️ Seleccionar nueva cama',
                        cancelButtonText: '↩️ Mantener servicio anterior',
                        confirmButtonColor: '#007bff',
                        cancelButtonColor: '#6c757d'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Limpiar cama seleccionada y abrir modal
                            limpiarCamaSeleccionada();
                            abrirModalCamas();
                        } else {
                            // Revertir servicio
                            this.value = salaSeleccionada;
                        }
                    });
                }
            });
        }
    });

    // ===== FUNCIÓN PARA LIMPIAR CAMA SELECCIONADA =====
    function limpiarCamaSeleccionada() {
        document.getElementById('cama_seleccionada').value = '';
        document.getElementById('id_cama_seleccionada').value = '';
        document.getElementById('sala_seleccionada').value = '';
        document.getElementById('piso_seleccionado').value = '';

        // Limpiar campos visibles también
        const camaActual = document.getElementById('cama_actual');
        const salaActual = document.getElementById('sala_actual');
        const pisoField = document.getElementById('piso');

        if (camaActual) camaActual.value = '';
        if (salaActual) salaActual.value = '';
        if (pisoField) pisoField.value = '';
    }

    // ===== REEMPLAZAR FUNCIONES ANTERIORES =====

    // Reemplazar la función de guardar borrador anterior
    setInterval(guardarBorradorMejorado, 30000);

    // Reemplazar la carga de borrador en DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(cargarBorradorMejorado, 1000);
    });

    // Mejorar la validación del formulario antes del envío
    document.getElementById('formIngreso').addEventListener('submit', function(e) {
        e.preventDefault();

        const errores = validarFormularioCompleto();

        if (errores.length > 0) {
            Swal.fire({
                title: '❌ Errores en el formulario',
                html: `
                <div class="text-start">
                    <p>Por favor corrija los siguientes errores:</p>
                    <ul class="text-danger">
                        ${errores.map(error => `<li>${error}</li>`).join('')}
                    </ul>
                </div>
            `,
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
            return;
        }

        const formData = new FormData(this);

        if (!pacienteExiste) {
            crearPaciente(formData);
        } else {
            crearCaso(formData);
        }
    });

    // Limpiar borrador cuando se guarde exitosamente
    function limpiarBorradorExitoso() {
        limpiarBorradoresAntiguos();
        resetearFormularioModificado();
    }

    // Funciones de accesibilidad

    // Navegación con teclado entre secciones
    document.addEventListener('keydown', function(e) {
        if (e.altKey) {
            switch (e.key) {
                case '1':
                    e.preventDefault();
                    toggleSection('buscar');
                    break;
                case '2':
                    e.preventDefault();
                    toggleSection('paciente');
                    break;
                case '3':
                    e.preventDefault();
                    toggleSection('ingreso');
                    break;
                case '4':
                    e.preventDefault();
                    toggleSection('signos');
                    break;
                case '5':
                    e.preventDefault();
                    toggleSection('antecedentes');
                    break;
            }
        }
    });

    // Agregar tooltips informativos
    function agregarTooltips() {
        const tooltips = {
            'numero_historia': 'Este número se genera automáticamente al crear el paciente',
            'cedula': 'Ingrese la cédula de identidad sin guiones ni espacios',
            'id_medico_ingreso': 'Médico que está realizando actualmente el ingreso',
            'id_medico_responsable': 'Médico que será responsable del caso (opcional)',
            'motivo_consulta': 'Razón principal por la cual el paciente acude al hospital',
            'enfermedad_actual': 'Descripción detallada de la condición actual del paciente'
        };

        Object.keys(tooltips).forEach(id => {
            const elemento = document.getElementById(id);
            if (elemento) {
                elemento.title = tooltips[id];
                elemento.setAttribute('data-bs-toggle', 'tooltip');
                elemento.setAttribute('data-bs-placement', 'top');
            }
        });

        // Inicializar tooltips de Bootstrap
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Inicializar tooltips después de cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(agregarTooltips, 500);
    });

    // Validaciones adicionales para formulario de ingreso

    // Validación del teléfono del acompañante
    document.getElementById('telefono_acompanante').addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value.length > 11) {
            this.value = this.value.substring(0, 11);
        }
    });

    // Validación de escala de dolor
    document.getElementById('dolor_escala').addEventListener('input', function() {
        const valor = parseInt(this.value);
        if (valor < 0) this.value = 0;
        if (valor > 10) this.value = 10;
    });

    // Validación de signos vitales
    function validarSignosVitales() {
        const validaciones = {
            temperatura: {
                min: 30,
                max: 45,
                nombre: 'Temperatura'
            },
            pulso: {
                min: 30,
                max: 200,
                nombre: 'Pulso'
            },
            frecuencia_respiratoria: {
                min: 8,
                max: 60,
                nombre: 'Frecuencia Respiratoria'
            },
            presion_sistolica: {
                min: 60,
                max: 300,
                nombre: 'Presión Sistólica'
            },
            presion_diastolica: {
                min: 30,
                max: 150,
                nombre: 'Presión Diastólica'
            },
            saturacion_oxigeno: {
                min: 50,
                max: 100,
                nombre: 'Saturación de Oxígeno'
            },
            peso: {
                min: 0.5,
                max: 300,
                nombre: 'Peso'
            },
            talla: {
                min: 30,
                max: 250,
                nombre: 'Talla'
            },
            glucemia: {
                min: 30,
                max: 600,
                nombre: 'Glucemia'
            }
        };

        Object.keys(validaciones).forEach(campo => {
            const elemento = document.getElementById(campo);
            if (elemento) {
                elemento.addEventListener('blur', function() {
                    const valor = parseFloat(this.value);
                    const validacion = validaciones[campo];

                    if (valor && (valor < validacion.min || valor > validacion.max)) {
                        Swal.fire({
                            title: 'Valor fuera de rango',
                            text: `${validacion.nombre}: valor normal entre ${validacion.min} y ${validacion.max}`,
                            icon: 'warning',
                            confirmButtonText: 'Entendido'
                        });
                        this.focus();
                    }
                });
            }
        });
    }

    // Validación mejorada del formulario completo
    function validarFormularioCompletoMejorado() {
        const errores = [];

        // Validaciones existentes...
        const cedula = document.getElementById('cedula').value.trim();
        if (!cedula) {
            errores.push('La cédula es obligatoria');
        } else if (!validarCedulaVenezolana(cedula)) {
            errores.push('La cédula no es válida');
        }

        const nombres = document.getElementById('nombres').value.trim();
        if (!nombres) {
            errores.push('Los nombres son obligatorios');
        } else if (nombres.length < 2) {
            errores.push('Los nombres deben tener al menos 2 caracteres');
        }

        const apellidos = document.getElementById('apellidos').value.trim();
        if (!apellidos) {
            errores.push('Los apellidos son obligatorios');
        } else if (apellidos.length < 2) {
            errores.push('Los apellidos deben tener al menos 2 caracteres');
        }

        const sexo = document.getElementById('sexo').value;
        if (!sexo) {
            errores.push('Debe seleccionar el sexo');
        }

        const medicoIngreso = document.getElementById('id_medico_ingreso').value;
        if (!medicoIngreso) {
            errores.push('Debe seleccionar un médico de ingreso');
        }

        const motivoConsulta = document.getElementById('motivo_consulta').value.trim();
        if (!motivoConsulta) {
            errores.push('El motivo de consulta es obligatorio');
        } else if (motivoConsulta.length < 10) {
            errores.push('El motivo de consulta debe ser más descriptivo (mínimo 10 caracteres)');
        }

        // Nuevas validaciones
        const fechaIngreso = document.getElementById('fecha_ingreso').value;
        if (fechaIngreso) {
            const fecha = new Date(fechaIngreso);
            const hoy = new Date();
            const unaSemanaAtras = new Date(hoy.getTime() - (7 * 24 * 60 * 60 * 1000));

            if (fecha > hoy) {
                errores.push('La fecha de ingreso no puede ser futura');
            } else if (fecha < unaSemanaAtras) {
                errores.push('La fecha de ingreso no puede ser mayor a 7 días atrás');
            }
        }

        // Validar teléfono del acompañante si se proporciona
        const telefonoAcompanante = document.getElementById('telefono_acompanante').value.trim();
        if (telefonoAcompanante && !validarTelefonoVenezolano(telefonoAcompanante)) {
            errores.push('El teléfono del acompañante no es válido');
        }

        // Validar que si hay acompañante, tenga al menos nombre
        const acompanante = document.getElementById('acompanante').value.trim();
        const parentesco = document.getElementById('parentesco_acompanante').value.trim();
        if ((parentesco || telefonoAcompanante) && !acompanante) {
            errores.push('Si proporciona datos del acompañante, debe incluir el nombre');
        }

        // Validar coherencia de signos vitales
        const sistolica = document.getElementById('presion_sistolica').value;
        const diastolica = document.getElementById('presion_diastolica').value;
        if (sistolica && diastolica) {
            if (parseInt(sistolica) <= parseInt(diastolica)) {
                errores.push('La presión sistólica debe ser mayor que la diastólica');
            }
        }

        return errores;
    }

    // Autocompletar campos relacionados
    function configurarAutocompletado() {
        // Si se selecciona emergencia como vía de ingreso, sugerir tipo emergencia
        document.getElementById('via_ingreso').addEventListener('change', function() {
            if (this.value === 'emergencia') {
                document.getElementById('tipo_ingreso').value = 'emergencia';
            }
        });

        // Si se pone prioridad crítica o alta, verificar que es emergencia
        document.getElementById('prioridad').addEventListener('change', function() {
            if (this.value === 'critica' || this.value === 'alta') {
                const tipoIngreso = document.getElementById('tipo_ingreso');
                if (tipoIngreso.value !== 'emergencia') {
                    Swal.fire({
                        title: 'Sugerencia',
                        text: 'Para casos de prioridad crítica/alta se recomienda tipo de ingreso "Emergencia"',
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: 'Cambiar a Emergencia',
                        cancelButtonText: 'Mantener actual'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            tipoIngreso.value = 'emergencia';
                            document.getElementById('via_ingreso').value = 'emergencia';
                        }
                    });
                }
            }
        });

        // Autocompletar médico responsable si no se ha seleccionado
        document.getElementById('id_medico_ingreso').addEventListener('change', function() {
            const medicoResponsable = document.getElementById('id_medico_responsable');
            if (!medicoResponsable.value && this.value) {
                medicoResponsable.value = this.value;
            }
        });
    }

    // Función para prellenar campos comunes
    function prellenarCamposComunes() {
        // Prellenar fecha y hora actual
        const ahora = new Date();
        document.getElementById('fecha_ingreso').value = ahora.toISOString().split('T')[0];
        document.getElementById('hora_ingreso').value = ahora.toTimeString().split(':').slice(0, 2).join(':');

        // Prellenar servicio más común
        document.getElementById('servicio_ingreso').value = 'Emergencias';
        document.getElementById('via_ingreso').value = 'emergencia';
    }

    // Función para mostrar ayuda contextual
    function mostrarAyudaContextual() {
        const ayudas = {
            'motivo_consulta': 'Describa la razón principal por la cual el paciente acude al hospital. Ej: "Dolor abdominal de 2 días de evolución"',
            'enfermedad_actual': 'Describa la evolución y características de la enfermedad actual del paciente',
            'sintomas_principales': 'Liste los síntomas más relevantes que presenta el paciente',
            'tiempo_evolucion': 'Indique desde cuándo comenzaron los síntomas. Ej: "3 días", "2 semanas"',
            'diagnostico_ingreso': 'Diagnóstico inicial o impresión diagnóstica al momento del ingreso',
            'impresion_clinica': 'Evaluación clínica inicial del médico tratante'
        };

        Object.keys(ayudas).forEach(campo => {
            const elemento = document.getElementById(campo);
            if (elemento) {
                elemento.addEventListener('focus', function() {
                    // Mostrar tooltip con ayuda
                    const tooltip = document.createElement('div');
                    tooltip.className = 'tooltip-ayuda';
                    tooltip.innerHTML =
                        `<small><i class="fas fa-info-circle"></i> ${ayudas[campo]}</small>`;
                    tooltip.style.cssText = `
                    position: absolute;
                    background: #333;
                    color: white;
                    padding: 8px 12px;
                    border-radius: 4px;
                    font-size: 12px;
                    z-index: 1000;
                    max-width: 250px;
                    margin-top: -35px;
                    margin-left: 10px;
                `;

                    // Remover tooltips existentes
                    document.querySelectorAll('.tooltip-ayuda').forEach(t => t.remove());

                    // Agregar nuevo tooltip
                    elemento.parentNode.style.position = 'relative';
                    elemento.parentNode.appendChild(tooltip);

                    // Remover tooltip después de 5 segundos
                    setTimeout(() => {
                        if (tooltip.parentNode) {
                            tooltip.remove();
                        }
                    }, 5000);
                });

                elemento.addEventListener('blur', function() {
                    // Remover tooltip al perder foco
                    setTimeout(() => {
                        document.querySelectorAll('.tooltip-ayuda').forEach(t => t.remove());
                    }, 500);
                });
            }
        });
    }

    // Función para calcular y mostrar estadísticas en tiempo real
    function calcularEstadisticasEnTiempoReal() {
        // Calcular IMC automáticamente
        const peso = document.getElementById('peso');
        const talla = document.getElementById('talla');

        function calcularIMC() {
            if (peso.value && talla.value) {
                const pesoVal = parseFloat(peso.value);
                const tallaVal = parseFloat(talla.value) / 100; // convertir cm a metros

                if (pesoVal > 0 && tallaVal > 0) {
                    const imc = pesoVal / (tallaVal * tallaVal);

                    // Mostrar IMC calculado
                    let imcDisplay = document.getElementById('imc-display');
                    if (!imcDisplay) {
                        imcDisplay = document.createElement('small');
                        imcDisplay.id = 'imc-display';
                        imcDisplay.className = 'text-info d-block mt-1';
                        talla.parentNode.appendChild(imcDisplay);
                    }

                    let categoria = '';
                    let color = '';
                    if (imc < 18.5) {
                        categoria = 'Bajo peso';
                        color = '#ffc107';
                    } else if (imc < 25) {
                        categoria = 'Normal';
                        color = '#28a745';
                    } else if (imc < 30) {
                        categoria = 'Sobrepeso';
                        color = '#fd7e14';
                    } else {
                        categoria = 'Obesidad';
                        color = '#dc3545';
                    }

                    imcDisplay.innerHTML =
                        `<i class="fas fa-calculator me-1"></i>IMC: ${imc.toFixed(1)} - ${categoria}`;
                    imcDisplay.style.color = color;
                }
            }
        }

        if (peso && talla) {
            peso.addEventListener('input', calcularIMC);
            talla.addEventListener('input', calcularIMC);
        }

        // Calcular edad automáticamente
        const fechaNacimiento = document.getElementById('fecha_nacimiento');
        if (fechaNacimiento) {
            fechaNacimiento.addEventListener('change', function() {
                if (this.value) {
                    const nacimiento = new Date(this.value);
                    const hoy = new Date();
                    const edad = Math.floor((hoy - nacimiento) / (365.25 * 24 * 60 * 60 * 1000));

                    let edadDisplay = document.getElementById('edad-display');
                    if (!edadDisplay) {
                        edadDisplay = document.createElement('small');
                        edadDisplay.id = 'edad-display';
                        edadDisplay.className = 'text-primary d-block mt-1';
                        this.parentNode.appendChild(edadDisplay);
                    }

                    if (edad >= 0 && edad <= 150) {
                        edadDisplay.innerHTML = `<i class="fas fa-birthday-cake me-1"></i>Edad: ${edad} años`;
                    } else {
                        edadDisplay.innerHTML = '<span class="text-danger">Fecha de nacimiento inválida</span>';
                    }
                }
            });
        }
    }

    // Función para formatear campos automáticamente
    function configurarFormateoAutomatico() {
        // Formatear teléfonos
        const telefonos = ['telefono', 'telefono_acompanante'];
        telefonos.forEach(id => {
            const elemento = document.getElementById(id);
            if (elemento) {
                elemento.addEventListener('blur', function() {
                    if (this.value) {
                        this.value = formatearTelefonoVenezolano(this.value);
                    }
                });
            }
        });

        // Capitalizar nombres
        const nombres = ['nombres', 'apellidos', 'acompanante'];
        nombres.forEach(id => {
            const elemento = document.getElementById(id);
            if (elemento) {
                elemento.addEventListener('blur', function() {
                    if (this.value) {
                        this.value = this.value
                            .toLowerCase()
                            .split(' ')
                            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                            .join(' ');
                    }
                });
            }
        });

        // Formatear texto de direcciones
        const direcciones = ['direccion'];
        direcciones.forEach(id => {
            const elemento = document.getElementById(id);
            if (elemento) {
                elemento.addEventListener('blur', function() {
                    if (this.value) {
                        // Capitalizar primera letra de cada oración
                        this.value = this.value
                            .toLowerCase()
                            .replace(/(^|\.\s+)([a-z])/g, (match, p1, p2) => p1 + p2.toUpperCase());
                    }
                });
            }
        });
    }

    // Función para guardar progreso del formulario
    function guardarProgresoFormulario() {
        const campos = [
            'cedula', 'nombres', 'apellidos', 'sexo', 'fecha_nacimiento', 'telefono', 'direccion',
            'fecha_ingreso', 'hora_ingreso', 'servicio_ingreso', 'tipo_ingreso', 'motivo_consulta',
            'enfermedad_actual', 'sintomas_principales', 'tiempo_evolucion', 'dolor_escala',
            'diagnostico_ingreso', 'impresion_clinica', 'via_ingreso', 'medio_transporte',
            'acompanante', 'telefono_acompanante', 'parentesco_acompanante', 'prioridad',
            'cama_actual', 'sala_actual', 'piso', 'observaciones_generales'
        ];

        const progreso = {};
        let tieneProgreso = false;

        campos.forEach(campo => {
            const elemento = document.getElementById(campo);
            if (elemento && elemento.value.trim()) {
                progreso[campo] = elemento.value.trim();
                tieneProgreso = true;
            }
        });

        if (tieneProgreso) {
            progreso.timestamp = new Date().toISOString();
            localStorage.setItem('progreso_ingreso_detallado', JSON.stringify(progreso));
        }
    }

    // Función para cargar progreso guardado
    function cargarProgresoGuardado() {
        const progreso = localStorage.getItem('progreso_ingreso_detallado');
        if (progreso) {
            try {
                const datos = JSON.parse(progreso);
                const timestamp = new Date(datos.timestamp);
                const ahora = new Date();
                const horasTranscurridas = (ahora - timestamp) / (1000 * 60 * 60);

                // Solo cargar si es reciente (menos de 2 horas)
                if (horasTranscurridas < 2) {
                    Object.keys(datos).forEach(campo => {
                        if (campo !== 'timestamp') {
                            const elemento = document.getElementById(campo);
                            if (elemento && !elemento.value) {
                                elemento.value = datos[campo];
                            }
                        }
                    });

                    // Mostrar notificación
                    const toast = document.createElement('div');
                    toast.className = 'toast-notification';
                    toast.innerHTML = `
                    <div class="alert alert-info alert-dismissible">
                        <i class="fas fa-info-circle me-2"></i>
                        Se restauró el progreso guardado automáticamente
                        <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
                    </div>
                `;
                    toast.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                    max-width: 350px;
                `;
                    document.body.appendChild(toast);

                    // Remover después de 5 segundos
                    setTimeout(() => {
                        if (toast.parentNode) {
                            toast.remove();
                        }
                    }, 5000);
                } else {
                    // Limpiar progreso antiguo
                    localStorage.removeItem('progreso_ingreso_detallado');
                }
            } catch (error) {
                console.error('Error cargando progreso:', error);
                localStorage.removeItem('progreso_ingreso_detallado');
            }
        }
    }

    // Función para limpiar progreso guardado
    function limpiarProgresoGuardado() {
        localStorage.removeItem('progreso_ingreso_detallado');
    }

    // Inicializar todas las funciones cuando se carga el documento
    document.addEventListener('DOMContentLoaded', function() {
        prellenarCamposComunes();
        validarSignosVitales();
        configurarAutocompletado();
        mostrarAyudaContextual();
        calcularEstadisticasEnTiempoReal();
        configurarFormateoAutomatico();

        // Cargar progreso guardado después de un breve delay
        setTimeout(cargarProgresoGuardado, 1000);

        // Guardar progreso cada 30 segundos
        setInterval(guardarProgresoFormulario, 30000);

        // Guardar progreso cuando se cambia cualquier campo
        const formulario = document.getElementById('formIngreso');
        if (formulario) {
            formulario.addEventListener('input', function() {
                // Debounce para no guardar en cada tecla
                clearTimeout(window.guardarTimeout);
                window.guardarTimeout = setTimeout(guardarProgresoFormulario, 2000);
            });
        }
    });

    // Limpiar progreso cuando se envía exitosamente el formulario
    window.addEventListener('beforeunload', function() {
        // Solo guardar si el formulario no se está enviando
        if (!window.formularioEnviandose) {
            guardarProgresoFormulario();
        }
    });

    // Función mejorada de validación que reemplaza la anterior
    function validarFormularioCompleto() {
        return validarFormularioCompletoMejorado();
    }

    // Marcar cuando el formulario se está enviando para no guardar progreso
    document.getElementById('formIngreso').addEventListener('submit', function() {
        window.formularioEnviandose = true;
        limpiarProgresoGuardado();
    });
    </script>
    <script>
    // ===== SISTEMA DE CAMAS SIMPLIFICADO =====
    let camaSeleccionadaTemp = null;

    function abrirModalCamas() {
        const servicioIngreso = document.getElementById('servicio_ingreso').value;

        if (!servicioIngreso) {
            Swal.fire({
                title: 'Servicio requerido',
                text: 'Debe seleccionar primero el servicio de ingreso',
                icon: 'warning'
            });
            document.getElementById('servicio_ingreso').focus();
            return;
        }

        const modal = new bootstrap.Modal(document.getElementById('modalCamas'));
        modal.show();

        cargarCamasDisponibles(servicioIngreso);
    }

    function cargarCamasDisponibles(servicio) {
        document.getElementById('loading-camas').style.display = 'block';
        document.getElementById('grid-camas').style.display = 'none';

        fetch(`obtener_camas.php?disponibles=1&servicio=${encodeURIComponent(servicio)}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('loading-camas').style.display = 'none';

                if (data.status === 'ok' && data.data.length > 0) {
                    mostrarCamas(data.data);
                } else {
                    document.getElementById('no-camas').style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('loading-camas').style.display = 'none';
                Swal.fire('Error', 'No se pudieron cargar las camas', 'error');
            });
    }

    function mostrarCamas(camas) {
        const grid = document.getElementById('grid-camas');
        grid.innerHTML = '';

        camas.forEach(cama => {
            const div = document.createElement('div');
            div.className = 'col-md-3';
            div.innerHTML = `
            <div class="cama-card disponible" 
                 onclick="seleccionarCama(${cama.id_cama}, '${cama.numero_cama}', '${cama.sala}')"
                 data-cama-id="${cama.id_cama}">
                <div class="text-center">
                    <div class="cama-numero">
                        <i class="fas fa-bed"></i> ${cama.numero_cama}
                    </div>
                    <div class="cama-info mt-2">
                        <strong>${cama.sala}</strong><br>
                        Piso ${cama.piso}
                    </div>
                    <span class="badge bg-success mt-2">Disponible</span>
                </div>
            </div>
        `;
            grid.appendChild(div);
        });

        document.getElementById('grid-camas').style.display = 'flex';
        document.getElementById('no-camas').style.display = 'none';
    }

    function seleccionarCama(idCama, numeroCama, sala) {
        // Limpiar selección anterior
        document.querySelectorAll('.cama-card').forEach(card => {
            card.classList.remove('seleccionada');
        });

        // Marcar nueva selección
        const camaElement = document.querySelector(`[data-cama-id="${idCama}"]`);
        if (camaElement) {
            camaElement.classList.add('seleccionada');
        }

        camaSeleccionadaTemp = {
            id: idCama,
            numero: numeroCama,
            sala: sala
        };

        document.getElementById('btn-confirmar-cama').disabled = false;
    }

    function confirmarSeleccionCama() {
        if (!camaSeleccionadaTemp) {
            Swal.fire('Error', 'No hay cama seleccionada', 'error');
            return;
        }

        // Actualizar formulario
        document.getElementById('cama_seleccionada').value =
            `Cama ${camaSeleccionadaTemp.numero} - ${camaSeleccionadaTemp.sala}`;
        document.getElementById('id_cama').value = camaSeleccionadaTemp.id;

        // Cerrar modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalCamas'));
        modal.hide();

        Swal.fire({
            title: 'Cama Asignada',
            text: `Se asignó la cama ${camaSeleccionadaTemp.numero}`,
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });

        camaSeleccionadaTemp = null;
    }

    function filtrarCamas() {
        const servicio = document.getElementById('filtro_servicio').value ||
            document.getElementById('servicio_ingreso').value;

        if (servicio) {
            cargarCamasDisponibles(servicio);
        }
    }

    // 📱 FUNCIÓN PARA COMBINAR TELÉFONOS
    document.addEventListener('DOMContentLoaded', function() {

        // 🔧 TELÉFONO DEL PACIENTE
        const codAreaPaciente = document.getElementById('cod_area_paciente');
        const numeroPaciente = document.getElementById('numero_paciente');

        if (codAreaPaciente && numeroPaciente) {
            // Solo números en el campo número
            numeroPaciente.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 7) {
                    this.value = this.value.substring(0, 7);
                }
                combinarTelefonoPaciente();
            });

            // Cuando cambie el código de área
            codAreaPaciente.addEventListener('change', combinarTelefonoPaciente);
        }

        // 🔧 TELÉFONO DEL ACOMPAÑANTE
        const codAreaAcompanante = document.getElementById('cod_area_acompanante');
        const numeroAcompanante = document.getElementById('numero_acompanante');

        if (codAreaAcompanante && numeroAcompanante) {
            // Solo números en el campo número
            numeroAcompanante.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 7) {
                    this.value = this.value.substring(0, 7);
                }
                combinarTelefonoAcompanante();
            });

            // Cuando cambie el código de área
            codAreaAcompanante.addEventListener('change', combinarTelefonoAcompanante);
        }
    });

    // 📱 FUNCIÓN: Combinar teléfono del paciente
    function combinarTelefonoPaciente() {
        const codArea = document.getElementById('cod_area_paciente').value;
        const numero = document.getElementById('numero_paciente').value;
        const telefonoCompleto = document.getElementById('telefono');

        if (codArea && numero) {
            telefonoCompleto.value = codArea + '-' + numero;
        } else {
            telefonoCompleto.value = '';
        }
    }

    // 📱 FUNCIÓN: Combinar teléfono del acompañante
    function combinarTelefonoAcompanante() {
        const codArea = document.getElementById('cod_area_acompanante').value;
        const numero = document.getElementById('numero_acompanante').value;
        const telefonoCompleto = document.getElementById('telefono_acompanante');

        if (codArea && numero) {
            telefonoCompleto.value = codArea + '-' + numero;
        } else {
            telefonoCompleto.value = '';
        }
    }
    </script>
</body>

</html>