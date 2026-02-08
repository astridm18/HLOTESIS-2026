<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.html");
    exit();
}

require_once('../conexion.php');

// ✅ VERIFICAR SI TCPDF ESTÁ DISPONIBLE
$tcpdf_path = '../reportes/tcpdf/tcpdf.php';
if (!file_exists($tcpdf_path)) {
    echo '<script>alert("Error: TCPDF no encontrado en: ' . $tcpdf_path . '"); window.close();</script>';
    exit();
}

// Verificar que se recibió el ID del paciente
$paciente_id = 0;
if (isset($_GET['paciente_id']) && !empty($_GET['paciente_id'])) {
    $paciente_id = intval($_GET['paciente_id']);
} elseif (isset($_GET['paciente']) && !empty($_GET['paciente'])) {
    $paciente_id = intval($_GET['paciente']);
}

if ($paciente_id <= 0) {
    echo '<script>alert("ID de paciente no válido: ' . ($paciente_id) . '"); window.close();</script>';
    exit();
}

// ✅ NUEVA VERIFICACIÓN PARA PDF DIRECTO
$view_pdf = isset($_GET['view']) && $_GET['view'] == '1';

// ✅ VERIFICAR SI SE REQUIERE GUARDAR PDF
$save_pdf = isset($_GET['save']) && $_GET['save'] == '1';

// Verificar que el paciente tenga al menos un caso cerrado
$query_casos_cerrados = "SELECT COUNT(*) as casos_cerrados FROM casos_clinicos WHERE id_paciente = ? AND estado_caso = 'cerrado'";
$stmt = $conn->prepare($query_casos_cerrados);
$stmt->bind_param("i", $paciente_id);
$stmt->execute();
$result = $stmt->get_result();
$casos_data = $result->fetch_assoc();

if ($casos_data['casos_cerrados'] == 0) {
    echo '<script>alert("Este paciente no tiene casos cerrados. No se puede generar la historia médica."); window.close();</script>';
    exit();
}

// Obtener datos del paciente con TODOS los campos
$query_paciente = "SELECT * FROM pacientes WHERE id_paciente = ?";
$stmt = $conn->prepare($query_paciente);
$stmt->bind_param("i", $paciente_id);
$stmt->execute();
$paciente = $stmt->get_result()->fetch_assoc();

if (!$paciente) {
    echo '<script>alert("Paciente no encontrado"); window.close();</script>';
    exit();
}

// Función para escapar HTML
function safe_html($value)
{
    return htmlspecialchars($value ?? 'No especificado', ENT_QUOTES, 'UTF-8');
}

// Función para formatear fecha
function format_date($date, $format = 'd/m/Y')
{
    if (empty($date) || $date == '0000-00-00')
        return 'No especificada';
    return date($format, strtotime($date));
}

// Función para formatear datetime
function format_datetime($datetime, $format = 'd/m/Y H:i')
{
    if (empty($datetime) || $datetime == '0000-00-00 00:00:00')
        return 'No especificada';
    return date($format, strtotime($datetime));
}

// ✅ FUNCIÓN PARA GENERAR CONTENIDO HTML DEL PDF CON ESTILO INSTITUCIONAL
function generarContenidoHistoriaPDF($paciente, $conn, $paciente_id)
{
    // Configurar zona horaria de Venezuela
    date_default_timezone_set('America/Caracas');

    // ✅ TÍTULO INSTITUCIONAL
    $html = '<h2 style="color: #1e3a8a; text-align: center; margin-top: 10px; font-size: 18px;">HISTORIA MÉDICA COMPLETA</h2>';
    $html .= '<h3 style="text-align: center; color: #374151; margin: 8px 0;">' . safe_html($paciente['nombres'] . ' ' . $paciente['apellidos']) . '</h3>';
    $html .= '<p style="text-align: center; color: #6b7280; margin: 5px 0;"><strong>Historia N°:</strong> ' . safe_html($paciente['numero_historia']) . ' | <strong>Cédula:</strong>' . safe_html($paciente['cedula']) . '</p>';

    // ✅ INFORMACIÓN DE GENERACIÓN CON FORMATO INSTITUCIONAL
    $html .= '<div style="background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; margin: 15px 0; border-radius: 5px;">
        <h4 style="color: #1e3a8a; margin: 0 0 8px 0; font-size: 12px;">INFORMACIÓN DEL DOCUMENTO</h4>
        <table style="width: 100%; font-size: 9px; color: #4b5563;">
            <tr>
                <td style="width: 20%;"><strong>Fecha generación:</strong></td>
                <td style="width: 30%;">' . date('d/m/Y H:i:s') . ' (Venezuela)</td>
                <td style="width: 15%;"><strong>Generado por:</strong></td>
                <td style="width: 35%;">' . safe_html($_SESSION['usuario_nombre'] ?? 'Sistema VITALERT') . '</td>
            </tr>
            <tr>
                <td><strong>Sistema:</strong></td>
                <td>VITALERT v2.0 - IVSS</td>
                <td><strong>Institución:</strong></td>
                <td>Hospital Central "Dr. Luis Ortega"</td>
            </tr>
        </table>
    </div>';

    // DATOS PERSONALES CON ESTILO INSTITUCIONAL
    $html .= '<h3 style="color: #1e3a8a; background-color: #f0f8ff; padding: 8px; border-left: 4px solid #1e3a8a; margin: 20px 0 10px 0;">1. DATOS PERSONALES DEL PACIENTE</h3>';
    $html .= '<table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse; border-color: #d1d5db;">
        <tr>
            <td style="background-color: #f9fafb; width: 25%; font-weight: bold; color: #374151;">N° Historia:</td>
            <td style="width: 25%; color: #111827;">' . safe_html($paciente['numero_historia']) . '</td>
            <td style="background-color: #f9fafb; width: 25%; font-weight: bold; color: #374151;">Cédula:</td>
            <td style="width: 25%; color: #111827;">' . safe_html($paciente['cedula']) . '</td>
        </tr>
        <tr>
            <td style="background-color: #f9fafb; font-weight: bold; color: #374151;">Nombres:</td>
            <td style="color: #111827;">' . safe_html($paciente['nombres']) . '</td>
            <td style="background-color: #f9fafb; font-weight: bold; color: #374151;">Apellidos:</td>
            <td style="color: #111827;">' . safe_html($paciente['apellidos']) . '</td>
        </tr>
        <tr>
            <td style="background-color: #f9fafb; font-weight: bold; color: #374151;">Sexo:</td>
            <td style="color: #111827;">' . ($paciente['sexo'] == 'M' ? 'Masculino' : ($paciente['sexo'] == 'F' ? 'Femenino' : 'No especificado')) . '</td>
            <td style="background-color: #f9fafb; font-weight: bold; color: #374151;">Fecha Nacimiento:</td>
            <td style="color: #111827;">' . format_date($paciente['fecha_nacimiento']) . '</td>
        </tr>
        <tr>
            <td style="background-color: #f9fafb; font-weight: bold; color: #374151;">Teléfono:</td>
            <td style="color: #111827;">' . safe_html($paciente['telefono']) . '</td>
            <td style="background-color: #f9fafb; font-weight: bold; color: #374151;">Tipo Sangre:</td>
            <td style="color: #111827;">' . safe_html($paciente['tipo_sangre']) . '</td>
        </tr>
        <tr>
            <td style="background-color: #f9fafb; font-weight: bold; color: #374151;">Dirección:</td>
            <td colspan="3" style="color: #111827;">' . safe_html($paciente['direccion']) . '</td>
        </tr>
        <tr>
            <td style="background-color: #f9fafb; font-weight: bold; color: #374151;">Seguro IVSS:</td>
            <td style="color: #111827;">' . safe_html($paciente['seguro_medico']) . '</td>
            <td style="background-color: #f9fafb; font-weight: bold; color: #374151;">N° Afiliación:</td>
            <td style="color: #111827;">' . safe_html($paciente['numero_seguro']) . '</td>
        </tr>
    </table><br>';

    // CASOS CLÍNICOS CON ESTILO INSTITUCIONAL
    $html .= '<h3 style="color: #1e3a8a; background-color: #f0f8ff; padding: 8px; border-left: 4px solid #1e3a8a; margin: 20px 0 10px 0;">2. CASOS CLÍNICOS CERRADOS</h3>';

    // Obtener casos cerrados
    $query_casos = "
        SELECT cc.*, di.*, e.*, 
               mp.nombres as medico_nombres, mp.apellidos as medico_apellidos,
               mp.especialidad as medico_especialidad,
               me.nombres as medico_egreso_nombres, me.apellidos as medico_egreso_apellidos,
               me.especialidad as medico_egreso_especialidad, me.cedula as medico_egreso_cedula
        FROM casos_clinicos cc
        LEFT JOIN datos_ingreso di ON cc.id_caso = di.id_caso
        LEFT JOIN egresos e ON cc.id_caso = e.id_caso
        LEFT JOIN personal mp ON cc.id_medico_responsable = mp.id
        LEFT JOIN personal me ON e.id_medico_egreso = me.id
        WHERE cc.id_paciente = ? AND cc.estado_caso = 'cerrado'
        ORDER BY cc.fecha_apertura ASC
    ";

    $stmt = $conn->prepare($query_casos);
    $stmt->bind_param("i", $paciente_id);
    $stmt->execute();
    $casos = $stmt->get_result();

    $caso_numero = 1;
    while ($caso = $casos->fetch_assoc()) {
        // Calcular días de estancia
        $dias_estancia = 'No calculado';
        if (!empty($caso['fecha_ingreso']) && !empty($caso['fecha_egreso'])) {
            $fecha_ing = new DateTime($caso['fecha_ingreso']);
            $fecha_egr = new DateTime($caso['fecha_egreso']);
            $dias_estancia = $fecha_ing->diff($fecha_egr)->days;
        }

        $html .= '<h4 style="background-color: #e0f2fe; padding: 12px; border-left: 4px solid #1e3a8a; color: #1e3a8a; margin: 15px 0 10px 0;">CASO #' . $caso_numero . ' - ' . safe_html($caso['numero_caso']) . '</h4>';

        // Información básica del caso con colores institucionales
        $html .= '<table border="1" cellpadding="6" cellspacing="0" style="width: 100%; border-collapse: collapse; border-color: #d1d5db;">
            <tr style="background-color: #f8fafc;">
                <td style="width: 25%; font-weight: bold; color: #374151;">Fecha Ingreso:</td>
                <td style="width: 25%; color: #111827;">' . format_datetime($caso['fecha_ingreso'] . ' ' . $caso['hora_ingreso']) . '</td>
                <td style="width: 25%; font-weight: bold; color: #374151;">Fecha Egreso:</td>
                <td style="width: 25%; color: #111827;">' . format_datetime($caso['fecha_egreso']) . '</td>
            </tr>
            <tr>
                <td style="font-weight: bold; color: #374151;">Días de Estancia:</td>
                <td style="color: #111827;">' . $dias_estancia . ' días</td>
                <td style="font-weight: bold; color: #374151;">Tipo Egreso:</td>
                <td style="color: #111827;">' . ucfirst(str_replace('_', ' ', safe_html($caso['tipo_egreso']))) . '</td>
            </tr>
            <tr>
                <td style="font-weight: bold; color: #374151;">Estado al Egreso:</td>
                <td style="color: #111827;">' . ucfirst(str_replace('_', ' ', safe_html($caso['estado_egreso']))) . '</td>
                <td style="font-weight: bold; color: #374151;">Servicio:</td>
                <td style="color: #111827;">' . safe_html($caso['servicio_actual']) . '</td>
            </tr>
        </table><br>';

        // Motivo de consulta
        if (!empty($caso['motivo_consulta'])) {
            $html .= '<div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 10px; margin: 10px 0;">
                <strong style="color: #92400e;">Motivo de Consulta:</strong><br>' . nl2br(safe_html($caso['motivo_consulta'])) . '
            </div>';
        }

        // Diagnósticos
        $html .= '<table border="1" cellpadding="6" cellspacing="0" style="width: 100%; border-collapse: collapse; border-color: #d1d5db;">
            <tr>
                <td style="background-color: #f9fafb; width: 30%; font-weight: bold; color: #374151;">Diagnóstico de Ingreso:</td>
                <td style="color: #111827;">' . safe_html($caso['diagnostico_ingreso']) . '</td>
            </tr>
            <tr>
                <td style="background-color: #f9fafb; font-weight: bold; color: #374151;">Diagnóstico Principal de Egreso:</td>
                <td style="color: #111827;">' . safe_html($caso['diagnostico_principal_egreso']) . '</td>
            </tr>';

        if (!empty($caso['diagnosticos_secundarios'])) {
            $html .= '<tr>
                <td style="background-color: #f9fafb; font-weight: bold; color: #374151;">Diagnósticos Secundarios:</td>
                <td style="color: #111827;">' . nl2br(safe_html($caso['diagnosticos_secundarios'])) . '</td>
            </tr>';
        }

        $html .= '</table><br>';

        // Recomendaciones de egreso
        if (!empty($caso['recomendaciones_generales'])) {
            $html .= '<div style="background-color: #dcfdf7; border-left: 4px solid #10b981; padding: 10px; margin: 10px 0;">
                <strong style="color: #065f46;"> Recomendaciones Generales:</strong><br>' . nl2br(safe_html($caso['recomendaciones_generales'])) . '
            </div>';
        }

        if (!empty($caso['prescripciones_egreso'])) {
            $html .= '<div style="background-color: #dcfdf7; border-left: 4px solid #10b981; padding: 10px; margin: 10px 0;">
                <strong style="color: #065f46;">Medicamentos al Egreso:</strong><br>' . nl2br(safe_html($caso['prescripciones_egreso'])) . '
            </div>';
        }

        if (!empty($caso['signos_alarma'])) {
            $html .= '<div style="background-color: #fee2e2; border-left: 4px solid #dc2626; padding: 10px; margin: 10px 0;">
                <strong style="color: #991b1b;"> Signos de Alarma:</strong><br>' . nl2br(safe_html($caso['signos_alarma'])) . '
            </div>';
        }

        // Médico de egreso
        if (!empty($caso['medico_egreso_nombres'])) {
            $html .= '<table border="1" cellpadding="6" cellspacing="0" style="width: 100%; border-collapse: collapse; border-color: #d1d5db;">
                <tr>
                    <td style="background-color: #f9fafb; width: 30%; font-weight: bold; color: #374151;">Médico de Egreso:</td>
                    <td style="color: #111827;">Dr(a). ' . safe_html($caso['medico_egreso_nombres'] . ' ' . $caso['medico_egreso_apellidos']) . '</td>
                </tr>';

            if (!empty($caso['medico_egreso_especialidad'])) {
                $html .= '<tr>
                    <td style="background-color: #f9fafb; font-weight: bold; color: #374151;">Especialidad:</td>
                    <td style="color: #111827;">' . safe_html($caso['medico_egreso_especialidad']) . '</td>
                </tr>';
            }

            $html .= '</table><br>';
        }

        $caso_numero++;

        // Salto de página si no es el último caso
        if ($caso_numero <= mysqli_num_rows($casos)) {
            $html .= '<br pagebreak="true">';
        }
    }

    // INFORMACIÓN FINAL INSTITUCIONAL
    $html .= '<br><div style="margin-top: 25px; padding: 15px; background-color: #f8fafc; border: 2px solid #1e3a8a; border-radius: 5px;">
        <h4 style="color: #1e3a8a; margin-top: 0;">INFORMACIÓN INSTITUCIONAL</h4>
        <table style="width: 100%; font-size: 10px; color: #374151;">
            <tr>
                <td style="width: 25%;"><strong>Documento generado:</strong></td>
                <td style="width: 35%;">' . date('d/m/Y H:i:s') . ' (Hora de Venezuela)</td>
                <td style="width: 15%;"><strong>Validez:</strong></td>
                <td style="width: 25%;">Documento oficial IVSS</td>
            </tr>
            <tr>
                <td><strong>Sistema:</strong></td>
                <td>VITALERT v2.0 - Instituto Venezolano de los Seguros Sociales</td>
                <td><strong>RIF:</strong></td>
                <td>G-20004076-9</td>
            </tr>
            <tr>
                <td><strong>Institución:</strong></td>
                <td colspan="3">Hospital Central "Dr. Luis Ortega" - Av. 4 de Mayo, Porlamar - Nueva Esparta - Venezuela</td>
            </tr>
        </table>
    </div>';

    return $html;
}

// ✅ NUEVA FUNCIÓN PARA MOSTRAR PDF DIRECTO CON ENCABEZADO INSTITUCIONAL
function mostrarPDFDirecto($paciente, $conn, $paciente_id)
{
    global $tcpdf_path;

    try {
        // Configurar zona horaria de Venezuela ANTES de cualquier operación
        date_default_timezone_set('America/Caracas');

        // Incluir TCPDF
        require_once($tcpdf_path);

        // Suprimir warnings deprecados
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);

        // ✅ CREAR CLASE PDF PERSONALIZADA CON ENCABEZADO INSTITUCIONAL
        class HistoriaPDF extends TCPDF
        {
            public function Header()
            {
                // ✅ NUEVO HEADER CON FORMATO INSTITUCIONAL VENEZOLANO
                date_default_timezone_set('America/Caracas');

                // Logo del IVSS a la izquierda (PNG)
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

            public function Footer()
            {
                // Configurar zona horaria de Venezuela
                date_default_timezone_set('America/Caracas');

                $this->SetY(-20);

                // Línea divisoria
                $this->SetLineWidth(0.5);
                $this->SetDrawColor(200, 200, 200);
                $this->Line(15, $this->GetY(), 195, $this->GetY());

                $this->Ln(3);
                $this->SetFont('helvetica', '', 8);
                $this->SetTextColor(100, 100, 100);

                // Información del pie con formato institucional y hora de Venezuela
                $this->Cell(0, 4, 'Sistema de Historias Médicas VITALERT - Hospital Central "Dr. Luis Ortega"', 0, 0, 'L');
                $this->Cell(0, 4, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'R');

                $this->Ln(4);
                $this->Cell(0, 4, 'Generado el: ' . date('d/m/Y H:i:s') . ' (Hora de Venezuela)', 0, 0, 'C');
            }
        }

        // Crear instancia PDF
        $pdf = new HistoriaPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // ✅ CONFIGURAR DOCUMENTO CON INFORMACIÓN INSTITUCIONAL
        $pdf->SetCreator('Sistema VITALERT - Hospital Central "Dr. Luis Ortega"');
        $pdf->SetAuthor('Sistema IVSS - República Bolivariana de Venezuela');
        $pdf->SetTitle('Historia Médica IVSS - ' . $paciente['nombres'] . ' ' . $paciente['apellidos']);
        $pdf->SetSubject('Historia Médica Completa - IVSS');
        $pdf->SetKeywords('Historia, Médica, IVSS, ' . $paciente['cedula']);

        // ✅ CONFIGURAR MÁRGENES (aumentar margen superior por el header más grande)
        $pdf->SetMargins(15, 60, 15);  // Aumentado de 15 a 60 el margen superior
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(25);     // Aumentado para el footer
        $pdf->SetAutoPageBreak(TRUE, 30);

        // Añadir página
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);

        // ✅ GENERAR CONTENIDO HTML PARA PDF
        $html_content = generarContenidoHistoriaPDF($paciente, $conn, $paciente_id);

        // Escribir HTML al PDF
        $pdf->writeHTML($html_content, true, false, true, false, '');

        // ✅ MOSTRAR PDF DIRECTAMENTE EN EL NAVEGADOR
        $filename = 'Historia_Medica_IVSS_' . $paciente['cedula'] . '_' . date('Ymd_His') . '.pdf';

        // Configurar headers para mostrar en navegador
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        // Mostrar PDF en navegador
        $pdf->Output($filename, 'I'); // 'I' = Inline (mostrar en navegador)
        exit();

    } catch (Exception $e) {
        echo '<script>alert("❌ Error generando PDF: ' . addslashes($e->getMessage()) . '"); window.close();</script>';
        exit();
    }
}

// ✅ VERIFICAR SI SE SOLICITA VER PDF DIRECTO
if ($view_pdf) {
    mostrarPDFDirecto($paciente, $conn, $paciente_id);
}

// ✅ SI SE REQUIERE GUARDAR PDF CON ENCABEZADO INSTITUCIONAL
if ($save_pdf) {
    try {
        // Configurar zona horaria de Venezuela ANTES de cualquier operación
        date_default_timezone_set('America/Caracas');

        // Incluir TCPDF
        require_once($tcpdf_path);

        // Suprimir warnings deprecados
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);

        // ✅ CREAR CLASE PDF PERSONALIZADA CON ENCABEZADO INSTITUCIONAL
        class HistoriaPDF extends TCPDF
        {
            public function Header()
            {
                // ✅ NUEVO HEADER CON FORMATO INSTITUCIONAL VENEZOLANO
                date_default_timezone_set('America/Caracas');

                // Logo del IVSS a la izquierda (PNG)
                $logo_path = '../assets/img/IVSS.png';
                if (file_exists($logo_path)) {
                    $this->Image($logo_path, 15, 10, 25, 20, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
                }

                // INFORMACIÓN INSTITUCIONAL CENTRADA
                $x_text = 45;
                $y_start = 10;

                // HOSPITAL CENTRAL "DR. LUIS ORTEGA"
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

            public function Footer()
            {
                // Configurar zona horaria de Venezuela
                date_default_timezone_set('America/Caracas');

                $this->SetY(-20);

                // Línea divisoria
                $this->SetLineWidth(0.5);
                $this->SetDrawColor(200, 200, 200);
                $this->Line(15, $this->GetY(), 195, $this->GetY());

                $this->Ln(3);
                $this->SetFont('helvetica', '', 8);
                $this->SetTextColor(100, 100, 100);

                // Información del pie con formato institucional y hora de Venezuela
                $this->Cell(0, 4, 'Sistema de Historias Médicas VITALERT - Hospital Central "Dr. Luis Ortega"', 0, 0, 'L');
                $this->Cell(0, 4, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'R');

                $this->Ln(4);
                $this->Cell(0, 4, 'Generado el: ' . date('d/m/Y H:i:s') . ' (Hora de Venezuela)', 0, 0, 'C');
            }
        }

        // Crear instancia PDF
        $pdf = new HistoriaPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // ✅ CONFIGURAR DOCUMENTO CON INFORMACIÓN INSTITUCIONAL
        $pdf->SetCreator('Sistema VITALERT - Hospital Central "Dr. Luis Ortega"');
        $pdf->SetAuthor('Sistema IVSS - República Bolivariana de Venezuela');
        $pdf->SetTitle('Historia Médica IVSS - ' . $paciente['nombres'] . ' ' . $paciente['apellidos']);
        $pdf->SetSubject('Historia Médica Completa - IVSS');
        $pdf->SetKeywords('Historia, Médica, IVSS, ' . $paciente['cedula']);

        // ✅ CONFIGURAR MÁRGENES (aumentar margen superior por el header más grande)
        $pdf->SetMargins(15, 60, 15);  // Aumentado de 15 a 60 el margen superior
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(25);     // Aumentado para el footer
        $pdf->SetAutoPageBreak(TRUE, 30);

        // Añadir página
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);

        // ✅ GENERAR CONTENIDO HTML PARA PDF
        $html_content = generarContenidoHistoriaPDF($paciente, $conn, $paciente_id);

        // Escribir HTML al PDF
        $pdf->writeHTML($html_content, true, false, true, false, '');

        // ✅ GUARDAR PDF EN LA CARPETA ESPECIFICADA
        $upload_dir = 'C:/laragon/www/HLOTESISfin/forms/uploads/historias/';

        // Crear directorio si no existe
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Nombre del archivo con formato institucional
        $filename = 'Historia_Medica_IVSS_' . $paciente['cedula'] . '_' . date('Ymd_His') . '.pdf';
        $filepath = $upload_dir . $filename;

        // Guardar archivo
        $pdf->Output($filepath, 'F');

        // Verificar que se guardó correctamente
        if (file_exists($filepath)) {
            echo '<script>
                alert("✅ Historia médica IVSS guardada exitosamente\\n\\nArchivo: ' . $filename . '\\nUbicación: ' . $filepath . '\\nTamaño: ' . round(filesize($filepath) / 1024, 2) . ' KB\\n\\nDocumento oficial del Instituto Venezolano de los Seguros Sociales");
                window.close();
            </script>';
        } else {
            echo '<script>alert("❌ Error: No se pudo guardar el archivo PDF"); window.close();</script>';
        }
        exit();

    } catch (Exception $e) {
        echo '<script>alert("❌ Error generando PDF: ' . addslashes($e->getMessage()) . '"); window.close();</script>';
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historia Médica IVSS - <?php echo safe_html($paciente['nombres'] . ' ' . $paciente['apellidos']); ?></title>
    <style>
    @media print {
        body {
            margin: 0;
        }

        .no-print {
            display: none !important;
        }

        .page-break {
            page-break-before: always;
        }
    }

    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        line-height: 1.4;
        margin: 20px;
        color: #333;
        background-color: #f8fafc;
    }

    .header {
        text-align: center;
        border-bottom: 3px solid #1e3a8a;
        padding-bottom: 20px;
        margin-bottom: 30px;
        background: linear-gradient(135deg, #f0f8ff, #e0f2fe);
        padding: 20px;
        border-radius: 10px;
    }

    .header h1 {
        color: #1e3a8a;
        margin: 0;
        font-size: 24px;
    }

    .header h2 {
        color: #374151;
        margin: 5px 0;
        font-size: 18px;
    }

    .header .institution-info {
        background-color: #1e3a8a;
        color: white;
        padding: 10px;
        margin: 10px 0;
        border-radius: 5px;
        font-size: 11px;
    }

    .section {
        margin-bottom: 25px;
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .section-title {
        background-color: #1e3a8a;
        color: white;
        padding: 12px 15px;
        font-size: 14px;
        font-weight: bold;
        margin: -20px -20px 20px -20px;
        border-radius: 8px 8px 0 0;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .data-table th,
    .data-table td {
        border: 1px solid #d1d5db;
        padding: 12px;
        text-align: left;
    }

    .data-table th {
        background-color: #f9fafb;
        font-weight: bold;
        color: #374151;
    }

    .case-header {
        background: linear-gradient(135deg, #e0f2fe, #bfdbfe);
        padding: 15px;
        border-left: 4px solid #1e3a8a;
        margin: 20px 0 15px 0;
        font-weight: bold;
        font-size: 14px;
        color: #1e3a8a;
        border-radius: 5px;
    }

    .subsection {
        margin: 15px 0;
        padding: 15px;
        background-color: #f8fafc;
        border-radius: 5px;
        border-left: 3px solid #6b7280;
    }

    .subsection-title {
        font-weight: bold;
        color: #1e3a8a;
        margin-bottom: 8px;
        font-size: 13px;
    }

    .evolution-item {
        border-left: 3px solid #10b981;
        padding: 15px;
        margin-bottom: 15px;
        background-color: #f0fdf4;
        border-radius: 5px;
    }

    .evolution-header {
        font-weight: bold;
        color: #065f46;
        margin-bottom: 8px;
        padding-bottom: 5px;
        border-bottom: 1px solid #d1fae5;
    }

    .vital-signs-table {
        font-size: 10px;
    }

    .btn-action {
        background-color: #1e3a8a;
        color: white;
        padding: 15px 25px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        margin: 10px 5px;
        text-decoration: none;
        display: inline-block;
        font-weight: bold;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(30, 58, 138, 0.3);
    }

    .btn-action:hover {
        background-color: #1e40af;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(30, 58, 138, 0.4);
    }

    .btn-success {
        background-color: #059669;
    }

    .btn-success:hover {
        background-color: #047857;
    }

    .btn-close {
        background-color: #dc2626;
    }

    .btn-close:hover {
        background-color: #b91c1c;
    }

    .btn-view {
        background-color: #0891b2;
    }

    .btn-view:hover {
        background-color: #0e7490;
    }

    .actions-container {
        text-align: center;
        margin-bottom: 30px;
        padding: 25px;
        background: linear-gradient(135deg, #1e3a8a, #3b82f6);
        border-radius: 15px;
        color: white;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .actions-container h3 {
        color: white;
        margin-bottom: 20px;
        font-size: 18px;
    }

    .diagnostic-box {
        background-color: #fef3c7;
        border: 1px solid #f59e0b;
        border-left: 4px solid #d97706;
        padding: 15px;
        margin: 15px 0;
        border-radius: 5px;
    }

    .recommendation-box {
        background-color: #dcfdf7;
        border: 1px solid #10b981;
        border-left: 4px solid #059669;
        padding: 15px;
        margin: 15px 0;
        border-radius: 5px;
    }

    .alert-box {
        background-color: #fee2e2;
        border: 1px solid #f87171;
        border-left: 4px solid #dc2626;
        padding: 15px;
        margin: 15px 0;
        border-radius: 5px;
    }

    .list-item {
        margin: 8px 0;
        padding: 8px 0 8px 25px;
        position: relative;
        background-color: #f9fafb;
        border-radius: 3px;
    }

    .list-item::before {
        content: "•";
        color: #1e3a8a;
        font-weight: bold;
        position: absolute;
        left: 8px;
    }

    .ivss-badge {
        background: linear-gradient(45deg, #1e3a8a, #3b82f6);
        color: white;
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 11px;
        font-weight: bold;
        display: inline-block;
        margin: 5px 0;
    }
    </style>
</head>

<body>
    <!-- ✅ BOTONES DE ACCIÓN CON ESTILO INSTITUCIONAL -->
    <div class="no-print ">



        <!-- ✅ Ver PDF en nueva pestaña sin guardar -->
        <a href="?paciente_id=<?php echo $paciente_id; ?>&view=1" class="btn-action btn-view" target="_blank">
            👁️ Ver PDF Institucional
        </a>

        <!-- Guardar PDF en servidor -->
        <a href="?paciente_id=<?php echo $paciente_id; ?>&save=1" class="btn-action btn-success">
            💾 Guardar Historia IVSS
        </a>

        <!-- Imprimir página actual -->
        <a href="javascript:void(0)" onclick="window.print()" class="btn-action">
            🖨️ Imprimir
        </a>


    </div>

    <!-- Encabezado Institucional -->
    <div class="header">
        <div class="institution-info">
            <strong>REPÚBLICA BOLIVARIANA DE VENEZUELA</strong><br>
            MINISTERIO DEL PODER POPULAR PARA EL PROCESO SOCIAL DE TRABAJO<br>
            INSTITUTO VENEZOLANO DE LOS SEGUROS SOCIALES<br>
            RIF: G-20004076-9
        </div>
        <h1>HISTORIA MÉDICA COMPLETA</h1>
        <h2><?php echo safe_html($paciente['nombres'] . ' ' . $paciente['apellidos']); ?></h2>
        <p><strong>Historia N°:</strong> <?php echo safe_html($paciente['numero_historia']); ?> |
            <strong>Cédula:</strong> <?php echo safe_html($paciente['cedula']); ?>
        </p>
        <div class="ivss-badge">Sistema VITALERT - Hospital Central "Dr. Luis Ortega"</div>
    </div>

    <!-- SECCIÓN 1: DATOS PERSONALES COMPLETOS -->
    <div class="section">
        <div class="section-title">📋 1. DATOS PERSONALES DEL AFILIADO IVSS</div>

        <table class="data-table">
            <tr>
                <th width="20%">N° Historia</th>
                <td width="30%"><?php echo safe_html($paciente['numero_historia']); ?></td>
                <th width="20%">Cédula</th>
                <td width="30%"><?php echo safe_html($paciente['cedula']); ?></td>
            </tr>
            <tr>
                <th>Nombres</th>
                <td><?php echo safe_html($paciente['nombres']); ?></td>
                <th>Apellidos</th>
                <td><?php echo safe_html($paciente['apellidos']); ?></td>
            </tr>
            <tr>
                <th>Sexo</th>
                <td><?php echo $paciente['sexo'] == 'M' ? 'Masculino' : ($paciente['sexo'] == 'F' ? 'Femenino' : 'No especificado'); ?>
                </td>
                <th>Fecha Nacimiento</th>
                <td><?php echo format_date($paciente['fecha_nacimiento']); ?></td>
            </tr>
            <tr>
                <th>Teléfono</th>
                <td><?php echo safe_html($paciente['telefono']); ?></td>
                <th>Tipo Sangre</th>
                <td><?php echo safe_html($paciente['tipo_sangre']); ?></td>
            </tr>
            <tr>
                <th>Dirección</th>
                <td colspan="3"><?php echo safe_html($paciente['direccion']); ?></td>
            </tr>
            <tr>
                <th>Alergias Conocidas</th>
                <td colspan="3"><?php echo safe_html($paciente['alergias_conocidas']); ?></td>
            </tr>
            <tr>
                <th>Seguro IVSS</th>
                <td><?php echo safe_html($paciente['seguro_medico']); ?></td>
                <th>N° Afiliación</th>
                <td><?php echo safe_html($paciente['numero_seguro']); ?></td>
            </tr>
        </table>
    </div>

    <!-- SECCIÓN 2: CASOS CLÍNICOS CERRADOS -->
    <div class="section">
        <div class="section-title">🏥 2. CASOS CLÍNICOS CERRADOS</div>

        <?php
        // ✅ Obtener todos los casos cerrados del paciente
        $query_casos = "
            SELECT cc.*, di.*, e.*, 
                   mp.nombres as medico_nombres, mp.apellidos as medico_apellidos,
                   mp.especialidad as medico_especialidad,
                   me.nombres as medico_egreso_nombres, me.apellidos as medico_egreso_apellidos,
                   me.especialidad as medico_egreso_especialidad, me.cedula as medico_egreso_cedula
            FROM casos_clinicos cc
            LEFT JOIN datos_ingreso di ON cc.id_caso = di.id_caso
            LEFT JOIN egresos e ON cc.id_caso = e.id_caso
            LEFT JOIN personal mp ON cc.id_medico_responsable = mp.id
            LEFT JOIN personal me ON e.id_medico_egreso = me.id
            WHERE cc.id_paciente = ? AND cc.estado_caso = 'cerrado'
            ORDER BY cc.fecha_apertura ASC
        ";

        $stmt = $conn->prepare($query_casos);
        $stmt->bind_param("i", $paciente_id);
        $stmt->execute();
        $casos = $stmt->get_result();

        $caso_numero = 1;
        while ($caso = $casos->fetch_assoc()) {
            // Calcular días de estancia
            $dias_estancia = 'No calculado';
            if (!empty($caso['fecha_ingreso']) && !empty($caso['fecha_egreso'])) {
                $fecha_ing = new DateTime($caso['fecha_ingreso']);
                $fecha_egr = new DateTime($caso['fecha_egreso']);
                $dias_estancia = $fecha_ing->diff($fecha_egr)->days;
            }
            ?>
        <div class="case-header">
            📋 CASO #<?php echo $caso_numero; ?> - <?php echo safe_html($caso['numero_caso']); ?>
            <span class="ivss-badge">IVSS</span>
        </div>

        <!-- Información básica del caso -->
        <table class="data-table">
            <tr style="background-color: #f8fafc;">
                <th width="20%">Fecha Ingreso</th>
                <td width="30%"><?php echo format_datetime($caso['fecha_ingreso'] . ' ' . $caso['hora_ingreso']); ?>
                </td>
                <th width="20%">Fecha Egreso</th>
                <td width="30%"><?php echo format_datetime($caso['fecha_egreso']); ?></td>
            </tr>
            <tr>
                <th>Días de Estancia</th>
                <td><strong><?php echo $dias_estancia; ?> días</strong></td>
                <th>Tipo Egreso</th>
                <td><?php echo ucfirst(str_replace('_', ' ', safe_html($caso['tipo_egreso']))); ?></td>
            </tr>
            <tr>
                <th>Estado al Egreso</th>
                <td><?php echo ucfirst(str_replace('_', ' ', safe_html($caso['estado_egreso']))); ?></td>
                <th>Servicio</th>
                <td><?php echo safe_html($caso['servicio_actual']); ?></td>
            </tr>
            <tr>
                <th>Médico Responsable</th>
                <td colspan="3">
                    Dr(a). <?php echo safe_html($caso['medico_nombres'] . ' ' . $caso['medico_apellidos']); ?>
                    <?php if (!empty($caso['medico_especialidad'])): ?>
                    - <em><?php echo safe_html($caso['medico_especialidad']); ?></em>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <!-- Motivo de consulta y diagnósticos -->
        <?php if (!empty($caso['motivo_consulta'])): ?>
        <div class="diagnostic-box">
            <strong>📝 Motivo de Consulta:</strong><br>
            <?php echo nl2br(safe_html($caso['motivo_consulta'])); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($caso['enfermedad_actual'])): ?>
        <div class="diagnostic-box">
            <strong>🔍 Enfermedad Actual:</strong><br>
            <?php echo nl2br(safe_html($caso['enfermedad_actual'])); ?>
        </div>
        <?php endif; ?>

        <table class="data-table">
            <tr>
                <th width="30%">Diagnóstico de Ingreso</th>
                <td><?php echo safe_html($caso['diagnostico_ingreso']); ?></td>
            </tr>
            <tr>
                <th>Diagnóstico Principal de Egreso</th>
                <td><strong><?php echo safe_html($caso['diagnostico_principal_egreso']); ?></strong></td>
            </tr>
            <?php if (!empty($caso['diagnosticos_secundarios'])): ?>
            <tr>
                <th>Diagnósticos Secundarios</th>
                <td><?php echo nl2br(safe_html($caso['diagnosticos_secundarios'])); ?></td>
            </tr>
            <?php endif; ?>
        </table>

        <!-- Recomendaciones de egreso -->
        <?php if (!empty($caso['recomendaciones_generales'])): ?>
        <div class="recommendation-box">
            <strong> Recomendaciones Generales:</strong><br>
            <?php echo nl2br(safe_html($caso['recomendaciones_generales'])); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($caso['prescripciones_egreso'])): ?>
        <div class="recommendation-box">
            <strong> Medicamentos al Egreso:</strong><br>
            <?php echo nl2br(safe_html($caso['prescripciones_egreso'])); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($caso['control_seguimiento'])): ?>
        <div class="recommendation-box">
            <strong> Control y Seguimiento:</strong><br>
            <?php echo nl2br(safe_html($caso['control_seguimiento'])); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($caso['signos_alarma'])): ?>
        <div class="alert-box">
            <strong> Signos de Alarma:</strong><br>
            <?php echo nl2br(safe_html($caso['signos_alarma'])); ?>
        </div>
        <?php endif; ?>

        <!-- Información médica del egreso -->
        <?php if (!empty($caso['medico_egreso_nombres'])): ?>
        <table class="data-table">
            <tr>
                <th width="30%">Médico de Egreso</th>
                <td>Dr(a).
                    <?php echo safe_html($caso['medico_egreso_nombres'] . ' ' . $caso['medico_egreso_apellidos']); ?>
                </td>
            </tr>
            <?php if (!empty($caso['medico_egreso_especialidad'])): ?>
            <tr>
                <th>Especialidad</th>
                <td><?php echo safe_html($caso['medico_egreso_especialidad']); ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($caso['medico_egreso_cedula'])): ?>
            <tr>
                <th>Cédula/Registro</th>
                <td><?php echo safe_html($caso['medico_egreso_cedula']); ?></td>
            </tr>
            <?php endif; ?>
        </table>
        <?php endif; ?>

        <?php if (!empty($caso['observaciones_finales'])): ?>
        <div class="diagnostic-box">
            <strong>📌 Observaciones Finales:</strong><br>
            <?php echo nl2br(safe_html($caso['observaciones_finales'])); ?>
        </div>
        <?php endif; ?>

        <?php
            // ANTECEDENTES para este caso
            $query_antecedentes = "SELECT * FROM antecedentes WHERE id_caso = ? AND activo = 1 ORDER BY tipo_antecedente, fecha_evento DESC";
            $stmt_ant = $conn->prepare($query_antecedentes);
            $stmt_ant->bind_param("i", $caso['id_caso']);
            $stmt_ant->execute();
            $antecedentes = $stmt_ant->get_result();

            if ($antecedentes->num_rows > 0):
                ?>
        <div class="subsection">
            <div class="subsection-title">📋 Antecedentes Médicos</div>
            <?php while ($ant = $antecedentes->fetch_assoc()): ?>
            <div class="list-item">
                <strong><?php echo ucfirst(str_replace('_', ' ', $ant['tipo_antecedente'])); ?>:</strong>
                <?php echo safe_html($ant['descripcion']); ?>
                <?php if (!empty($ant['fecha_evento'])): ?>
                <em>(<?php echo format_date($ant['fecha_evento']); ?>)</em>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>

        <?php
            // SIGNOS VITALES DE INGRESO
            $query_signos_ingreso = "
                SELECT svi.*, p.nombres, p.apellidos
                FROM signos_vitales_ingreso svi
                LEFT JOIN personal p ON svi.id_enfermero = p.id
                WHERE svi.id_caso = ?
                ORDER BY svi.fecha_registro ASC
            ";
            $stmt_signos_ing = $conn->prepare($query_signos_ingreso);
            $stmt_signos_ing->bind_param("i", $caso['id_caso']);
            $stmt_signos_ing->execute();
            $signos_ingreso = $stmt_signos_ing->get_result();

            if ($signos_ingreso->num_rows > 0):
                ?>
        <div class="subsection">
            <div class="subsection-title">📊 Signos Vitales de Ingreso</div>
            <table class="data-table vital-signs-table">
                <tr style="background-color: #e0f2fe;">
                    <th>Fecha/Hora</th>
                    <th>Temp.</th>
                    <th>Pulso</th>

                    <th>Presión Arterial</th>
                    <th>SatO2</th>
                    <th>Peso</th>

                </tr>
                <?php while ($signo_ing = $signos_ingreso->fetch_assoc()): ?>
                <tr>
                    <td><?php echo format_datetime($signo_ing['fecha_registro']); ?></td>
                    <td><?php echo safe_html($signo_ing['temperatura']); ?>°C</td>
                    <td><?php echo safe_html($signo_ing['pulso']); ?> lpm</td>

                    <td><?php echo safe_html($signo_ing['presion_sistolica'] . '/' . $signo_ing['presion_diastolica']); ?>
                        mmHg</td>
                    <td><?php echo safe_html($signo_ing['saturacion_oxigeno']); ?>%</td>
                    <td><?php echo safe_html($signo_ing['peso']); ?> kg</td>

                </tr>
                <?php endwhile; ?>
            </table>
        </div>
        <?php endif; ?>

        <?php
            // EVOLUCIONES para este caso
            $query_evoluciones = "
                SELECT e.*, p.nombres, p.apellidos, p.especialidad 
                FROM evoluciones e
                LEFT JOIN personal p ON e.id_medico = p.id
                WHERE e.id_caso = ? 
                ORDER BY e.fecha_evolucion ASC
            ";
            $stmt_evo = $conn->prepare($query_evoluciones);
            $stmt_evo->bind_param("i", $caso['id_caso']);
            $stmt_evo->execute();
            $evoluciones = $stmt_evo->get_result();

            if ($evoluciones->num_rows > 0):
                ?>
        <div class="subsection">
            <div class="subsection-title">📝 Evoluciones Médicas</div>
            <?php while ($evo = $evoluciones->fetch_assoc()): ?>
            <div class="evolution-item">
                <div class="evolution-header">
                    📅 <?php echo format_datetime($evo['fecha_evolucion']); ?> |
                    👨‍⚕️ Dr(a). <?php echo safe_html($evo['nombres'] . ' ' . $evo['apellidos']); ?>
                    <?php if (!empty($evo['especialidad'])): ?>
                    - <em><?php echo safe_html($evo['especialidad']); ?></em>
                    <?php endif; ?>
                </div>

                <?php if (!empty($evo['subjetivo'])): ?>
                <p><strong>📝 Subjetivo:</strong><br><?php echo nl2br(safe_html($evo['subjetivo'])); ?></p>
                <?php endif; ?>

                <?php if (!empty($evo['objetivo'])): ?>
                <p><strong>🔍 Objetivo:</strong><br><?php echo nl2br(safe_html($evo['objetivo'])); ?></p>
                <?php endif; ?>

                <?php if (!empty($evo['evaluacion'])): ?>
                <p><strong>📊 Evaluación:</strong><br><?php echo nl2br(safe_html($evo['evaluacion'])); ?></p>
                <?php endif; ?>

                <?php if (!empty($evo['plan'])): ?>
                <p><strong>📋 Plan:</strong><br><?php echo nl2br(safe_html($evo['plan'])); ?></p>
                <?php endif; ?>

                <p><strong>📈 Estado del Paciente:</strong>
                    <span
                        style="color: <?php echo $evo['estado_paciente'] == 'critico' ? '#dc2626' : ($evo['estado_paciente'] == 'mejorado' ? '#059669' : '#0891b2'); ?>; font-weight: bold;">
                        <?php echo ucfirst(str_replace('_', ' ', safe_html($evo['estado_paciente']))); ?>
                    </span>
                </p>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>

        <?php
            $caso_numero++;

            // Nueva página para el siguiente caso si no es el último
            if ($caso_numero <= $casos_data['casos_cerrados']) {
                echo '<div class="page-break"></div>';
            }
        }
        ?>
    </div>

    <!-- SECCIÓN FINAL: RESUMEN ESTADÍSTICO -->
    <div class="page-break"></div>
    <div class="section">
        <div class="section-title">📊 3. RESUMEN ESTADÍSTICO DE HISTORIA MÉDICA</div>

        <?php
        // ✅ Obtener estadísticas generales usando tabla 'egresos'
        $query_stats = "
            SELECT 
                COUNT(*) as total_casos,
                AVG(DATEDIFF(DATE(e.fecha_egreso), di.fecha_ingreso)) as promedio_estancia,
                MIN(di.fecha_ingreso) as primera_consulta,
                MAX(DATE(e.fecha_egreso)) as ultima_consulta,
                SUM(DATEDIFF(DATE(e.fecha_egreso), di.fecha_ingreso)) as total_dias_hospitalizacion
            FROM casos_clinicos cc
            LEFT JOIN datos_ingreso di ON cc.id_caso = di.id_caso
            LEFT JOIN egresos e ON cc.id_caso = e.id_caso
            WHERE cc.id_paciente = ? AND cc.estado_caso = 'cerrado'
        ";

        $stmt_stats = $conn->prepare($query_stats);
        $stmt_stats->bind_param("i", $paciente_id);
        $stmt_stats->execute();
        $stats = $stmt_stats->get_result()->fetch_assoc();

        $promedio_estancia = round($stats['promedio_estancia'], 1);
        $primera_consulta = format_date($stats['primera_consulta']);
        $ultima_consulta = format_date($stats['ultima_consulta']);
        ?>

        <table class="data-table">
            <tr style="background-color: #e0f2fe;">
                <th colspan="2">📈 Resumen Estadístico de Historia Médica IVSS</th>
            </tr>
            <tr>
                <td width="50%"><strong>📋 Total de casos cerrados:</strong></td>
                <td><span class="ivss-badge"><?php echo $stats['total_casos']; ?> casos</span></td>
            </tr>
            <tr>
                <td><strong>🏥 Total días de hospitalización:</strong></td>
                <td><strong><?php echo $stats['total_dias_hospitalizacion']; ?> días</strong></td>
            </tr>
            <tr>
                <td><strong>📊 Promedio de días por estancia:</strong></td>
                <td><strong><?php echo $promedio_estancia; ?> días</strong></td>
            </tr>
            <tr>
                <td><strong>📅 Primera consulta registrada:</strong></td>
                <td><?php echo $primera_consulta; ?></td>
            </tr>
            <tr>
                <td><strong>📅 Última consulta registrada:</strong></td>
                <td><?php echo $ultima_consulta; ?></td>
            </tr>
        </table>

        <!-- Información Institucional Final -->
        <div
            style="margin-top: 30px; padding: 20px; background: linear-gradient(135deg, #1e3a8a, #3b82f6); color: white; border-radius: 10px;">
            <h4 style="color: white; margin-top: 0; text-align: center;">🏛️ INFORMACIÓN INSTITUCIONAL IVSS</h4>
            <table style="width: 100%; color: white; font-size: 11px;">
                <tr>
                    <td style="width: 25%;"><strong>📅 Documento generado:</strong></td>
                    <td style="width: 35%;"><?php
                    // Configurar zona horaria de Venezuela
                    date_default_timezone_set('America/Caracas');
                    echo date('d/m/Y H:i:s');
                    ?> (Hora de Venezuela)</td>
                    <td style="width: 15%;"><strong>🔒 Validez:</strong></td>
                    <td style="width: 25%;">Documento oficial IVSS</td>
                </tr>
                <tr>
                    <td><strong>💻 Sistema:</strong></td>
                    <td>VITALERT v2.0 - Instituto Venezolano de los Seguros Sociales</td>
                    <td><strong>📋 RIF:</strong></td>
                    <td>G-20004076-9</td>
                </tr>
                <tr>
                    <td><strong>🏥 Institución:</strong></td>
                    <td colspan="3">Hospital Central "Dr. Luis Ortega" - Av. 4 de Mayo, Porlamar - Nueva Esparta -
                        Venezuela</td>
                </tr>
                <tr>
                    <td><strong>📞 Contacto:</strong></td>
                    <td>Teléfono: 0295-2640577</td>
                    <td><strong>👤 Generado por:</strong></td>
                    <td><?php echo safe_html($_SESSION['usuario_nombre'] ?? 'Sistema VITALERT'); ?></td>
                </tr>
            </table>

            <div
                style="text-align: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.3);">
                <strong>REPÚBLICA BOLIVARIANA DE VENEZUELA</strong><br>
                <em>Ministerio del Poder Popular para el Proceso Social de Trabajo</em><br>
                <em>Instituto Venezolano de los Seguros Sociales</em>
            </div>
        </div>
    </div>

    <!-- Footer final institucional -->
    <div class="no-print"
        style="margin-top: 40px; border-top: 3px solid #1e3a8a; padding-top: 20px; text-align: center; background: linear-gradient(135deg, #f8fafc, #e0f2fe); padding: 20px; border-radius: 10px;">

        <div class="ivss-badge" style="font-size: 14px; margin-bottom: 10px;">
            INSTITUTO VENEZOLANO DE LOS SEGUROS SOCIALES
        </div>

        <p style="color: #1e3a8a; font-weight: bold; font-size: 16px; margin: 10px 0;">
            HISTORIA MÉDICA COMPLETA IVSS
        </p>

        <p style="color: #374151; font-size: 14px; margin: 8px 0;">
            <strong>Paciente:</strong> <?php echo safe_html($paciente['nombres'] . ' ' . $paciente['apellidos']); ?> |
            <strong>Historia N°:</strong> <?php echo safe_html($paciente['numero_historia']); ?> |
            <strong>Cédula:</strong> <?php echo safe_html($paciente['cedula']); ?>
        </p>

        <p style="color: #6b7280; font-size: 12px; margin: 8px 0;">
            Documento generado el <?php echo date('d/m/Y H:i:s'); ?> (Hora de Venezuela) | Sistema VITALERT v2.0
        </p>

        <p style="font-size: 11px; color: #9ca3af; margin: 10px 0;">
            <strong>Hospital Central "Dr. Luis Ortega"</strong> - Porlamar, Nueva Esparta<br>
            Incluye: <?php echo $stats['total_casos']; ?> caso(s) cerrado(s) |
            <?php echo $stats['total_dias_hospitalizacion']; ?> días total de hospitalización |
            Promedio: <?php echo $promedio_estancia; ?> días por estancia
        </p>

        <div
            style="margin-top: 15px; padding: 10px; background-color: #1e3a8a; color: white; border-radius: 5px; font-size: 10px;">
            <strong>REPÚBLICA BOLIVARIANA DE VENEZUELA</strong><br>
            Ministerio del Poder Popular para el Proceso Social de Trabajo<br>
            Instituto Venezolano de los Seguros Sociales - RIF: G-20004076-9
        </div>
    </div>

    <script>
    // Configurar zona horaria de Venezuela para JavaScript
    // Auto-mensaje informativo al cargar
    window.onload = function() {
        setTimeout(function() {
            console.log('🏥 Historia médica IVSS cargada correctamente');
            console.log('👁️ Para ver PDF institucional: Hacer clic en "Ver PDF Institucional"');
            console.log('💾 Para guardar: Hacer clic en "Guardar Historia IVSS"');
            console.log('🖨️ Para imprimir: Usar Ctrl+P o botón Imprimir');
            console.log('🇻🇪 Sistema VITALERT - Hospital Central "Dr. Luis Ortega"');
        }, 1000);

        // Mostrar información de Venezuela
        const venezuelaTime = new Date().toLocaleString("es-VE", {
            timeZone: "America/Caracas",
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        console.log('🕒 Hora actual de Venezuela: ' + venezuelaTime);
    };

    // Detectar después de imprimir para cerrar ventana
    window.onafterprint = function() {
        if (confirm('¿Desea cerrar esta ventana de Historia Médica IVSS?')) {
            window.close();
        }
    };

    // ✅ FUNCIÓN MEJORADA PARA ABRIR PDF INSTITUCIONAL EN NUEVA PESTAÑA
    function abrirPDFInstitucional() {
        // Mostrar mensaje de carga institucional
        var loading = document.createElement('div');
        loading.innerHTML = `
            <div style="text-align: center;">
                <div style="background: #1e3a8a; color: white; padding: 5px 10px; border-radius: 5px; margin-bottom: 10px; font-size: 12px;">
                    IVSS - República Bolivariana de Venezuela
                </div>
                <div>⏳ Generando Historia Médica IVSS...</div>
                <div style="font-size: 11px; color: #666; margin-top: 5px;">
                    Hospital Central "Dr. Luis Ortega"
                </div>
            </div>
        `;
        loading.style.cssText =
            'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 25px; border: 3px solid #1e3a8a; border-radius: 15px; z-index: 9999; font-size: 16px; box-shadow: 0 8px 16px rgba(30,58,138,0.3); min-width: 300px;';
        document.body.appendChild(loading);

        // Abrir PDF después de un momento
        setTimeout(function() {
            window.open('?paciente_id=<?php echo $paciente_id; ?>&view=1', '_blank');
            document.body.removeChild(loading);
        }, 800);
    }

    // Función para mostrar confirmación de guardado institucional
    function confirmarGuardadoIVSS() {
        return confirm(
            '🏥 INSTITUTO VENEZOLANO DE LOS SEGUROS SOCIALES\\n\\n' +
            '¿Está seguro de que desea guardar la historia médica institucional?\\n\\n' +
            '📁 Se guardará en: C:\\laragon\\www\\HLOTESISfin\\forms\\uploads\\historias\\\\n' +
            '🇻🇪 Documento oficial del IVSS\\n' +
            '🏥 Hospital Central "Dr. Luis Ortega"'
        );
    }

    // Función para mostrar información del sistema
    function mostrarInfoSistema() {
        alert(
            '🏛️ SISTEMA DE HISTORIAS MÉDICAS VITALERT\\n\\n' +
            '🏥 Hospital Central "Dr. Luis Ortega"\\n' +
            '🇻🇪 República Bolivariana de Venezuela\\n' +
            '📋 Instituto Venezolano de los Seguros Sociales\\n\\n' +
            '📞 Teléfono: 0295-2640577\\n' +
            '📍 Av. 4 de Mayo, Porlamar - Nueva Esparta\\n' +
            '📋 RIF: G-20004076-9\\n\\n' +
            '💻 Sistema VITALERT v2.0 - IVSS'
        );
    }

    // Agregar evento para mostrar información del sistema
    document.addEventListener('DOMContentLoaded', function() {
        // Agregar tooltip a elementos IVSS
        const badges = document.querySelectorAll('.ivss-badge');
        badges.forEach(badge => {
            badge.title =
                'Instituto Venezolano de los Seguros Sociales - República Bolivariana de Venezuela';
            badge.style.cursor = 'help';
        });
    });

    // Función para validar antes de cerrar
    window.addEventListener('beforeunload', function(e) {
        // Solo mostrar advertencia si hay cambios no guardados
        // (Esta función se puede personalizar según necesidades)
    });
    </script>

</body>

</html>

<?php
// Configurar zona horaria de Venezuela para el cierre
date_default_timezone_set('America/Caracas');

// Cerrar conexión
$conn->close();
?>