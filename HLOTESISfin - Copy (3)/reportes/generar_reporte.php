<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.html");
    exit;
}

// Verificar si TCPDF está instalado
if (!file_exists('tcpdf/tcpdf.php')) {
    die('Error: TCPDF no está instalado. Por favor, descarga TCPDF y colócalo en la carpeta reportes/tcpdf/');
}

// Incluir la librería TCPDF
require_once('tcpdf/tcpdf.php');
include("../conexion.php");

$tipo_reporte = $_GET['tipo'] ?? '';

if (empty($tipo_reporte)) {
    die('Tipo de reporte no especificado');
}

// Extender TCPDF para personalizar header y footer
class HospitalPDF extends TCPDF
{
    // Header
    public function Header()
    {
        // NUEVO HEADER CON FORMATO INSTITUCIONAL VENEZOLANO

        // Logo del IVSS a la izquierda *(PNG)
        $logo_path = '../assets/img/IVSS.png';
        if (file_exists($logo_path)) {
            $this->Image($logo_path, 15, 10, 25, 20, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }

        // INFORMACIÓN INSTITUCIONAL CENTRADA
        // Posicionar el texto al lado del logo (desde X=45)
        $x_text = 45;
        $y_start = 10;

        // HOSPITAL CENTRAL "DR. LUIS ORTEGA" - ARRIBA
        $this->SetXY($x_text, $y_start);
        $this->SetFont('helvetica', 'B', 12);
        $this->SetTextColor(30, 58, 138);
        $this->Cell(150, 5, 'HOSPITAL CENTRAL "DR. LUIS ORTEGA"', 0, 1, 'C');

        // REPÚBLICA BOLIVARIANA DE VENEZUELA
        $this->SetXY($x_text, $y_start + 6);
        $this->SetFont('helvetica', 'B', 10);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(150, 4, 'REPÚBLICA BOLIVARIANA DE VENEZUELA', 0, 1, 'C');

        // MINISTERIO DEL PODER POPULAR PARA EL PROCESO SOCIAL DE TRABAJO
        $this->SetXY($x_text, $y_start + 11);
        $this->SetFont('helvetica', 'B', 9);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(150, 4, 'MINISTERIO DEL PODER POPULAR PARA EL PROCESO SOCIAL DE TRABAJO', 0, 1, 'C');

        // INSTITUTO VENEZOLANO DE LOS SEGUROS SOCIALES
        $this->SetXY($x_text, $y_start + 16);
        $this->SetFont('helvetica', 'B', 9);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(150, 4, 'INSTITUTO VENEZOLANO DE LOS SEGUROS SOCIALES', 0, 1, 'C');

        // DIRECCIÓN GENERAL DE SALUD
        $this->SetXY($x_text, $y_start + 21);
        $this->SetFont('helvetica', 'B', 9);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(150, 4, 'DIRECCIÓN GENERAL DE SALUD', 0, 1, 'C');

        // Información de contacto del hospital
        $this->SetXY($x_text, $y_start + 28);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(150, 3, 'RIF: G-20004076-9 | Teléfono: 0295-2640577', 0, 1, 'C');

        $this->SetXY($x_text, $y_start + 32);
        $this->Cell(150, 3, 'Dirección: Av. 4 de Mayo, Porlamar - Nueva Esparta - Venezuela', 0, 1, 'C');

        // Línea divisoria más abajo
        $this->SetLineWidth(0.8);
        $this->SetDrawColor(30, 58, 138);
        $this->Line(15, 50, 195, 50);

        $this->Ln(20);
    }

    // Footer
    public function Footer()
    {
        $this->SetY(-20);

        // Línea divisoria
        $this->SetLineWidth(0.5);
        $this->SetDrawColor(200, 200, 200);
        $this->Line(15, $this->GetY(), 195, $this->GetY());

        $this->Ln(3);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(100, 100, 100);

        // Configurar zona horaria de Venezuela
        date_default_timezone_set('America/Caracas');

        // Información del pie con formato institucional y hora de Venezuela

        $this->Cell(0, 4, 'Sistema de Historias Médicas VITALERT - Hospital Central "Dr. Luis Ortega"', 0, 0, 'L');
        $this->Cell(0, 4, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'R');
    }
}

try {
    // Crear nueva instancia de PDF
    $pdf = new HospitalPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Configuración del documento
    $pdf->SetCreator('Sistema VITALERT - Hospital Central "Dr. Luis Ortega"');
    $pdf->SetAuthor($_SESSION['usuario_nombre'] ?? 'Sistema IVSS');
    $pdf->SetTitle('Reporte Médico IVSS - ' . ucwords(str_replace('-', ' ', $tipo_reporte)));

    // Configurar márgenes (aumentar margen superior por el header más grande)
    $pdf->SetMargins(15, 60, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(15);

    // Auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 25);

    // Añadir página
    $pdf->AddPage();

    $titulo = '';
    $datos = [];
    $subtitulo = '';

    switch ($tipo_reporte) {
        case 'ingresos-diarios':
            $titulo = 'REPORTE DE INGRESOS DIARIOS';
            $subtitulo = 'Estadísticas de ingresos de los últimos 30 días';

            $query = "SELECT 
                        DATE(di.fecha_ingreso) as fecha,
                        COUNT(*) as total_ingresos,
                        SUM(CASE WHEN cc.prioridad = 'alta' OR cc.prioridad = 'critica' THEN 1 ELSE 0 END) as urgentes,
                        SUM(CASE WHEN cc.estado_caso = 'activo' THEN 1 ELSE 0 END) as activos
                      FROM datos_ingreso di
                      INNER JOIN casos_clinicos cc ON di.id_caso = cc.id_caso
                      WHERE di.fecha_ingreso >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                      GROUP BY DATE(di.fecha_ingreso)
                      ORDER BY DATE(di.fecha_ingreso) DESC";

            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $datos[] = [
                    date('d/m/Y', strtotime($row['fecha'])),
                    $row['total_ingresos'],
                    $row['urgentes'],
                    $row['activos']
                ];
            }

            $columnas = ['Fecha', 'Total Ingresos', 'Urgentes', 'Activos'];
            $anchos = [40, 40, 40, 40];
            break;

        case 'pacientes-activos':
            $titulo = 'REPORTE DE PACIENTES ACTIVOS';
            $subtitulo = 'Lista completa de pacientes con casos activos en el hospital';

            $query = "SELECT 
                        p.numero_historia,
                        CONCAT(p.nombres, ' ', p.apellidos) as nombre_completo,
                        p.cedula,
                        cc.numero_caso,
                        di.fecha_ingreso,
                        COALESCE(cc.servicio_actual, 'Sin asignar') as servicio_actual,
                        cc.prioridad,
                        COALESCE(CONCAT(pe.nombres, ' ', pe.apellidos), 'Sin asignar') as medico_responsable
                      FROM casos_clinicos cc
                      INNER JOIN pacientes p ON cc.id_paciente = p.id_paciente
                      INNER JOIN datos_ingreso di ON cc.id_caso = di.id_caso
                      LEFT JOIN personal pe ON cc.id_medico_responsable = pe.id
                      WHERE cc.estado_caso = 'activo'
                      ORDER BY di.fecha_ingreso DESC";

            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $datos[] = [
                    $row['numero_historia'],
                    substr($row['nombre_completo'], 0, 20),
                    'V-' . $row['cedula'],
                    $row['numero_caso'],
                    date('d/m/Y', strtotime($row['fecha_ingreso'])),
                    substr($row['servicio_actual'], 0, 15),
                    ucfirst($row['prioridad'])
                ];
            }

            $columnas = ['N° Historia', 'Paciente', 'Cédula', 'N° Caso', 'Fecha', 'Servicio', 'Prioridad'];
            $anchos = [25, 35, 25, 20, 25, 30, 20];
            break;

        case 'estadisticas-servicio':
            $titulo = 'ESTADÍSTICAS POR SERVICIO';
            $subtitulo = 'Resumen estadístico de casos por área médica (últimos 3 meses)';

            $query = "SELECT 
                        COALESCE(cc.servicio_actual, 'Sin asignar') as servicio,
                        COUNT(*) as total_casos,
                        SUM(CASE WHEN cc.estado_caso = 'activo' THEN 1 ELSE 0 END) as casos_activos,
                        SUM(CASE WHEN cc.prioridad = 'alta' OR cc.prioridad = 'critica' THEN 1 ELSE 0 END) as casos_urgentes,
                        ROUND(AVG(DATEDIFF(CURDATE(), di.fecha_ingreso)), 1) as promedio_dias
                      FROM casos_clinicos cc
                      INNER JOIN datos_ingreso di ON cc.id_caso = di.id_caso
                      WHERE di.fecha_ingreso >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
                      GROUP BY cc.servicio_actual
                      ORDER BY total_casos DESC";

            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $datos[] = [
                    substr($row['servicio'], 0, 25),
                    $row['total_casos'],
                    $row['casos_activos'],
                    $row['casos_urgentes'],

                ];
            }

            $columnas = ['Servicio', 'Total', 'Activos', 'Urgentes'];
            $anchos = [60, 25, 25, 25, 25];
            break;

        case 'casos-urgentes':
            $titulo = 'CASOS URGENTES ACTIVOS';
            $subtitulo = 'Lista de casos con prioridad alta o crítica que requieren atención inmediata';

            $query = "SELECT 
                        cc.numero_caso,
                        CONCAT(p.nombres, ' ', p.apellidos) as nombre_completo,
                        p.cedula,
                        di.fecha_ingreso,
                        COALESCE(cc.servicio_actual, 'Sin asignar') as servicio_actual,
                        cc.prioridad,
                        DATEDIFF(CURDATE(), di.fecha_ingreso) as dias_hospitalizacion,
                        COALESCE(CONCAT(pe.nombres, ' ', pe.apellidos), 'Sin asignar') as medico_responsable
                      FROM casos_clinicos cc
                      INNER JOIN pacientes p ON cc.id_paciente = p.id_paciente
                      INNER JOIN datos_ingreso di ON cc.id_caso = di.id_caso
                      LEFT JOIN personal pe ON cc.id_medico_responsable = pe.id
                      WHERE cc.estado_caso = 'activo' 
                      AND (cc.prioridad = 'alta' OR cc.prioridad = 'critica')
                      ORDER BY cc.prioridad DESC, di.fecha_ingreso ASC";

            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $datos[] = [
                    $row['numero_caso'],
                    substr($row['nombre_completo'], 0, 20),
                    'V-' . $row['cedula'],
                    date('d/m/Y', strtotime($row['fecha_ingreso'])),
                    substr($row['servicio_actual'], 0, 15),
                    strtoupper($row['prioridad']),
                    $row['dias_hospitalizacion'] . ' días'
                ];
            }

            $columnas = ['N° Caso', 'Paciente', 'Cédula', 'Fecha', 'Servicio', 'Prioridad', 'Días'];
            $anchos = [20, 35, 25, 25, 30, 20, 25];
            break;

        default:
            throw new Exception('Tipo de reporte no válido');
    }

    // Título del reporte con estilo institucional
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetTextColor(30, 58, 138);
    $pdf->Cell(0, 10, $titulo, 0, 1, 'C');

    // Subtítulo
    $pdf->SetFont('helvetica', '', 11);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 8, $subtitulo, 0, 1, 'C');

    $pdf->Ln(5);

    // Información del reporte
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(60, 60, 60);

    // Configurar zona horaria de Venezuela para toda la información de fecha
    date_default_timezone_set('America/Caracas');

    $pdf->Cell(0, 6, 'Fecha de generación: ' . date('d/m/Y H:i:s'), 0, 1, 'L');
    $usuario_info = ($_SESSION['usuario_nombre'] ?? 'Sistema VITALERT');
    $usuario_rol = ($_SESSION['usuario_rol'] ?? 'N/A');
    $pdf->Cell(0, 6, 'Generado por: ' . $usuario_info . ' - Rol: ' . $usuario_rol, 0, 1, 'L');
    $pdf->Cell(0, 6, 'Total de registros: ' . count($datos), 0, 1, 'L');

    $pdf->Ln(8);

    if (count($datos) > 0) {
        // Encabezados de la tabla
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(30, 58, 138);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(30, 58, 138);

        for ($i = 0; $i < count($columnas); $i++) {
            $pdf->Cell($anchos[$i], 8, $columnas[$i], 1, 0, 'C', 1);
        }
        $pdf->Ln();

        // Datos de la tabla
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(40, 40, 40);
        $pdf->SetFillColor(245, 245, 245);

        $fill = false;
        foreach ($datos as $fila) {
            for ($i = 0; $i < count($fila); $i++) {
                $pdf->Cell($anchos[$i], 6, $fila[$i], 1, 0, 'C', $fill);
            }
            $pdf->Ln();
            $fill = !$fill;
        }
    } else {
        $pdf->SetFont('helvetica', 'I', 12);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Cell(0, 20, 'No se encontraron datos para este reporte.', 0, 1, 'C');
    }

    // Agregar observaciones al final
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(30, 58, 138);
    $pdf->Cell(0, 6, 'OBSERVACIONES:', 0, 1, 'L');

    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(60, 60, 60);
    $observaciones = [
        '• Este reporte fue generado automáticamente por el Sistema VITALERT',
        '• Los datos mostrados corresponden a la información disponible al momento de la generación',
        '• Para consultas o aclaraciones, contacte al Departamento de Sistemas',
        '• Documento de uso interno exclusivo para personal autorizado del IVSS'
    ];

    foreach ($observaciones as $obs) {
        $pdf->Cell(0, 5, $obs, 0, 1, 'L');
    }

    // Generar el PDF
    $nombre_archivo = 'reporte_hospital_ortega_' . $tipo_reporte . '_' . date('Y-m-d_H-i-s') . '.pdf';
    // Crear directorio si no existe
    $directorio_reportes = __DIR__ . '/archivos_generados/';
    if (!file_exists($directorio_reportes)) {
        mkdir($directorio_reportes, 0755, true);
    }

    // Ruta completa del archivo
    $ruta_completa = $directorio_reportes . $nombre_archivo;

    // Guardar archivo en el servidor Y enviar descarga
    $pdf->Output($ruta_completa, 'F'); // 'F' para guardar en archivo
    $pdf->Output($nombre_archivo, 'D'); // 'D' para forzar descarga

    // Opcional: Log del archivo generado
    error_log("PDF generado y guardado: " . $ruta_completa);

} catch (Exception $e) {
    // En caso de error, mostrar mensajeF
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Error</title></head><body>';
    echo '<h1 style="color: red;">Error al generar reporte PDF</h1>';
    echo '<p><strong>Tipo de reporte:</strong> ' . htmlspecialchars($tipo_reporte) . '</p>';
    echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><strong>Fecha:</strong> ' . date('d/m/Y H:i:s') . '</p>';
    echo '<p><a href="../index.php">← Volver al dashboard</a></p>';
    echo '</body></html>';
}

if (isset($conn)) {
    $conn->close();
}
?>