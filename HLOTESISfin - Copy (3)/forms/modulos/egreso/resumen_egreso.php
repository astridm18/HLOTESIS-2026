<?php
// modulos/egreso/resumen_egreso.php
require_once '../../config/database.php';
require_once '../../config/functions.php';

verificarSesion();

$id_caso = $_GET['caso'] ?? 0;
if (!$id_caso) {
    die('Caso no especificado');
}

// CONSULTA CORREGIDA - Obtener datos completos del caso
$stmt = $pdo->prepare("
    SELECT 
        e.*, 
        cc.numero_caso, 
        cc.id_paciente,
        CONCAT(p.nombres, ' ', p.apellidos) as nombre_paciente,
        p.numero_historia, 
        p.cedula, 
        TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, e.fecha_egreso) as edad_al_egreso,
        di.fecha_ingreso, 
        DATEDIFF(DATE(e.fecha_egreso), di.fecha_ingreso) as dias_estancia_calculados,
        cc.servicio_actual,
        CONCAT(me.nombres, ' ', me.apellidos) as nombre_medico_egreso,
        me.especialidad as especialidad_medico_egreso,
        me.cedula as registro_medico_cedula
    FROM egresos e
    INNER JOIN casos_clinicos cc ON e.id_caso = cc.id_caso
    INNER JOIN pacientes p ON cc.id_paciente = p.id_paciente
    INNER JOIN datos_ingreso di ON cc.id_caso = di.id_caso
    LEFT JOIN personal me ON e.id_medico_egreso = me.id
    WHERE e.id_caso = ? AND e.estado_egreso_sistema = 'definitivo'
");
$stmt->execute([$id_caso]);
$egreso = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$egreso) {
    die('Egreso no encontrado o no procesado definitivamente para el caso ID: ' . $id_caso);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Egreso Completado - <?= $egreso['nombre_paciente'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    .success-header {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 30px;
        border-radius: 15px;
        text-align: center;
        margin-bottom: 30px;
    }

    .check-animation {
        font-size: 80px;
        animation: bounce 1s ease-in-out;
    }

    @keyframes bounce {

        0%,
        20%,
        50%,
        80%,
        100% {
            transform: translateY(0);
        }

        40% {
            transform: translateY(-30px);
        }

        60% {
            transform: translateY(-15px);
        }
    }

    .info-box {
        background: #f8f9fa;
        border-left: 5px solid #28a745;
        padding: 20px;
        margin: 15px 0;
        border-radius: 0 10px 10px 0;
    }

    .btn-action {
        margin: 10px;
        padding: 15px 30px;
        border-radius: 25px;
        transition: all 0.3s ease;
    }

    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }
    </style>
</head>

<body>
    <div class="container mt-4">
        <div class="success-header">
            <div class="check-animation">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2 class="mt-3">¡Egreso Procesado Exitosamente!</h2>
            <p class="mb-0">El paciente ha sido egresado y el caso ha sido cerrado</p>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="info-box">
                    <h5><i class="fas fa-user me-2 text-primary"></i>Información del Paciente</h5>
                    <p class="mb-1"><strong>Nombre:</strong> <?= $egreso['nombre_paciente'] ?></p>
                    <p class="mb-1"><strong>Historia:</strong> <?= $egreso['numero_historia'] ?></p>
                    <p class="mb-1"><strong>Cédula:</strong> <?= $egreso['cedula'] ?></p>
                    <p class="mb-0"><strong>Caso:</strong> <?= $egreso['numero_caso'] ?></p>
                </div>
            </div>

            <div class="col-md-6">
                <div class="info-box">
                    <h5><i class="fas fa-calendar me-2 text-success"></i>Datos del Egreso</h5>
                    <p class="mb-1"><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($egreso['fecha_egreso'])) ?>
                    </p>
                    <p class="mb-1"><strong>Tipo:</strong> <?= ucwords(str_replace('_', ' ', $egreso['tipo_egreso'])) ?>
                    </p>
                    <p class="mb-1"><strong>Estado:</strong>
                        <?= ucwords(str_replace('_', ' ', $egreso['estado_egreso'])) ?></p>
                    <p class="mb-0"><strong>Días de estancia:</strong> <?= $egreso['dias_estancia_calculados'] ?> días
                    </p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="info-box">
                    <h5><i class="fas fa-stethoscope me-2 text-info"></i>Resumen Clínico</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Diagnóstico Principal:</strong></p>
                            <p class="bg-light p-2 rounded">
                                <?= nl2br(htmlspecialchars($egreso['diagnostico_principal_egreso'])) ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Médico Responsable:</strong></p>
                            <p class="bg-light p-2 rounded">
                                Dr(a). <?= $egreso['nombre_medico_egreso'] ?><br>
                                <small>Especialidad: <?= $egreso['especialidad_medico_egreso'] ?></small><br>
                                <small>Cédula/Registro: <?= $egreso['registro_medico_cedula'] ?></small>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center">
            <h4 class="mb-4">¿Qué desea hacer ahora?</h4>

            <div class="mb-3">
                <button class="btn btn-info btn-action" onclick="imprimirHistoria()">
                    <i class="fas fa-file-alt me-2"></i>Imprimir Historia Completa
                </button>

                <button class="btn btn-primary btn-action" onclick="generarYGuardarResumenPDF()">
                    <i class="fas fa-save me-2"></i>Generar y Guardar Resumen
                </button>
            </div>

            <div class="mb-3">
                <a href="../ingreso/formulario_ingreso.php" class="btn btn-warning btn-action">
                    <i class="fas fa-user-plus me-2"></i>Nuevo Ingreso
                </a>

                <a href="../../../index.php" class="btn btn-dark btn-action">
                    <i class="fas fa-home me-2"></i>Menú Principal
                </a>
            </div>
        </div>
    </div>





    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🎯 Datos disponibles:');
        console.log('  - ID Caso:', <?= $egreso['id_caso'] ?>);
        console.log('  - ID Paciente:', <?= $egreso['id_paciente'] ?>);
        console.log('  - Nombre:', '<?= $egreso['nombre_paciente'] ?>');

        Swal.fire({
            title: '¡Egreso procesado exitosamente!',
            text: 'El caso ha sido cerrado y el paciente egresado del sistema.',
            icon: 'success',
            timer: 3000,
            showConfirmButton: false
        });
    });

    // FUNCIÓN CORREGIDA PARA IMPRIMIR HISTORIA (PARÁMETRO CORREGIDO)
    function imprimirHistoria() {
        console.log('✅ ID del paciente confirmado:', <?= $egreso['id_paciente'] ?>);

        // CORREGIDO: Usar paciente_id (como Pacientes-list) en lugar de id_paciente
        const url = '../../generar_historia_pdf.php?paciente_id=<?= $egreso['id_paciente'] ?>';

        console.log('🚀 Abriendo URL:', url);

        // Abrir directamente
        window.open(url, '_blank');
    }

    // FUNCIÓN MEJORADA PARA GENERAR RESUMEN PDF CON DEBUG
    function generarYGuardarResumenPDF() {
        Swal.fire({
            title: 'Generando Resumen PDF...',
            html: 'Esto puede tardar unos segundos. Por favor espere.',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });

        const url = 'generar_resumen_egreso_pdf.php?caso=<?= $egreso['id_caso'] ?>';
        console.log('🚀 Generando resumen PDF desde:', url);

        fetch(url)
            .then(response => {
                console.log('📡 Response status:', response.status);
                console.log('📡 Response headers:', response.headers);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                return response.text();
            })
            .then(text => {
                console.log('📄 Respuesta COMPLETA del servidor:');
                console.log('📄 Longitud:', text.length);
                console.log('📄 Primeros 500 caracteres:', text.substring(0, 500));
                console.log('📄 Últimos 500 caracteres:', text.substring(text.length - 500));

                Swal.close();

                // Intentar detectar si es HTML en lugar de JSON
                if (text.trim().startsWith('<!DOCTYPE') || text.trim().startsWith('<html')) {
                    Swal.fire({
                        title: 'Error: Respuesta HTML',
                        html: `
                        <p>El servidor devolvió HTML en lugar de JSON.</p>
                        <p>Esto indica un error de PHP.</p>
                        <details>
                            <summary>Ver respuesta completa (primeros 1000 caracteres)</summary>
                            <pre style="text-align: left; font-size: 10px; max-height: 200px; overflow-y: auto;">${text.substring(0, 1000)}</pre>
                        </details>
                    `,
                        icon: 'error'
                    });
                    return;
                }

                try {
                    const data = JSON.parse(text);
                    console.log('✅ Respuesta parseada:', data);

                    if (data.status === 'ok') {
                        Swal.fire({
                            title: '¡PDF Generado y Guardado!',
                            html: `El resumen de egreso ha sido guardado en el servidor como:<br>
                               <strong>${data.filename}</strong><br><br>
                               ¿Desea descargarlo ahora?`,
                            icon: 'success',
                            showCancelButton: true,
                            confirmButtonText: '<i class="fas fa-download me-2"></i>Descargar PDF',
                            cancelButtonText: 'Cerrar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                console.log('📥 Descargando desde:', data.download_url);
                                window.open(data.download_url, '_blank');
                            }
                        });
                    } else {
                        Swal.fire('Error', 'No se pudo generar el resumen PDF: ' + data.message, 'error');
                    }
                } catch (e) {
                    console.error('❌ Error parsing JSON:', e);
                    console.error('❌ Texto que causó el error:', text);

                    Swal.fire({
                        title: 'Error de Formato',
                        html: `
                        <p><strong>No se pudo interpretar la respuesta como JSON.</strong></p>
                        <p><strong>Error:</strong> ${e.message}</p>
                        <hr>
                        <p><strong>Respuesta del servidor (primeros 500 caracteres):</strong></p>
                        <pre style="text-align: left; font-size: 10px; max-height: 150px; overflow-y: auto; background: #f8f9fa; padding: 10px;">${text.substring(0, 500)}</pre>
                        <hr>
                        <p><small>Revisa la consola del navegador (F12) para más detalles</small></p>
                    `,
                        icon: 'error',
                        width: '600px'
                    });
                }
            })
            .catch(error => {
                Swal.close();
                console.error('❌ Error al generar resumen PDF:', error);
                Swal.fire('Error', 'Ocurrió un error al intentar generar el resumen PDF: ' + error.message,
                    'error');
            });
    }

    // FUNCIÓN PARA PROBAR DIRECTAMENTE EL ARCHIVO PHP
    function testGenerarResumenDirecto() {
        const url = 'generar_resumen_egreso_pdf.php?caso=<?= $egreso['id_caso'] ?>';
        console.log('🧪 Abriendo directamente:', url);

        Swal.fire({
            title: '🧪 Test Directo',
            html: `
            <p>Se abrirá directamente:</p>
            <code>${url}</code>
            <br><br>
            <p>Esto te mostrará exactamente qué devuelve el archivo PHP.</p>
        `,
            icon: 'info',
            confirmButtonText: '🚀 Abrir'
        }).then((result) => {
            if (result.isConfirmed) {
                window.open(url, '_blank');
            }
        });
    }
    </script>

</body>

</html>