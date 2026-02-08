<?php
// modulos/egreso/generar_resumen_egreso_pdf.php
require_once '../../config/forms/database.php';
require_once '../../config/forms/functions.php';

// ✅ RUTA CORREGIDA PARA TCPDF
require_once __DIR__ . '/../../reportes/tcpdf/tcpdf.php';

verificarSesion();

// ✅ SUPRIMIR WARNINGS DEPRECADOS DE PHP 8.1+
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);

// Encabezado para respuesta JSON
header('Content-Type: application/json');

$id_caso = $_GET['caso'] ?? 0;
if (!$id_caso) {
    echo json_encode(['status' => 'error', 'message' => 'ID de caso no especificado.']);
    exit();
}

try {
    // Obtener información del egreso y paciente directamente de la BD
    $stmt = $pdo->prepare("
        SELECT 
            e.*, 
            cc.numero_caso, 
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
        echo json_encode(['status' => 'error', 'message' => 'Egreso no encontrado o no procesado definitivamente para el caso ID: ' . $id_caso]);
        exit();
    }

    // --- Configuración y generación del PDF con TCPDF ---
    class MYPDF extends TCPDF
    {
        public function Header()
        {
            $this->SetFont('helvetica', 'B', 12);
            $this->Cell(0, 15, 'RESUMEN DE EGRESO HOSPITALARIO', 0, false, 'C', 0, '', 0, false, 'M', 'M');
            $this->SetFont('helvetica', '', 8);
            $this->Cell(0, 10, 'Fecha de Generacion: ' . date('d/m/Y H:i:s'), 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $this->Ln();
            $this->SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
            $this->Line(10, $this->GetY(), $this->GetPageWidth() - 10, $this->GetY());
            $this->Ln(5);
        }

        public function Footer()
        {
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            $this->Cell(0, 10, 'Pagina ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
            $this->Ln(5);
            $this->SetFont('helvetica', 'I', 6);
            $this->Cell(0, 5, 'Documento generado por el Sistema de Historias Medicas HLO v2.0', 0, 0, 'C');
        }
    }

    // Crear nuevo documento PDF
    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // set document information
    $pdf->SetCreator('Sistema HLO');
    $pdf->SetAuthor('Sistema HLO');
    $pdf->SetTitle('Resumen de Egreso - ' . $egreso['nombre_paciente']);
    $pdf->SetSubject('Resumen de Egreso Hospitalario');

    // set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // set font
    $pdf->SetFont('helvetica', '', 10);

    // add a page
    $pdf->AddPage();

    // ✅ CONTENIDO DEL PDF SIN utf8_decode (ya no es necesario en versiones modernas)
    $html = '
    <h3 style="color:#007bff;">INFORMACION DEL PACIENTE</h3>
    <table border="1" cellpadding="5" cellspacing="0" style="width:100%;">
        <tr>
            <td style="width:25%; background-color:#e3f2fd;"><strong>Nombre:</strong></td>
            <td style="width:75%;">' . htmlspecialchars($egreso['nombre_paciente']) . '</td>
        </tr>
        <tr>
            <td style="width:25%; background-color:#e3f2fd;"><strong>Historia No:</strong></td>
            <td style="width:25%;">' . htmlspecialchars($egreso['numero_historia']) . '</td>
            <td style="width:25%; background-color:#e3f2fd;"><strong>Cedula:</strong></td>
            <td style="width:25%;">' . htmlspecialchars($egreso['cedula']) . '</td>
        </tr>
        <tr>
            <td style="width:25%; background-color:#e3f2fd;"><strong>Caso No:</strong></td>
            <td style="width:75%;">' . htmlspecialchars($egreso['numero_caso']) . '</td>
        </tr>
    </table><br>

    <h3 style="color:#28a745;">DATOS DEL EGRESO</h3>
    <table border="1" cellpadding="5" cellspacing="0" style="width:100%;">
        <tr>
            <td style="width:35%; background-color:#e8f5e8;"><strong>Fecha y Hora de Egreso:</strong></td>
            <td style="width:65%;">' . date('d/m/Y H:i', strtotime($egreso['fecha_egreso'])) . '</td>
        </tr>
        <tr>
            <td style="width:35%; background-color:#e8f5e8;"><strong>Tipo de Egreso:</strong></td>
            <td style="width:65%;">' . ucwords(str_replace('_', ' ', $egreso['tipo_egreso'])) . '</td>
        </tr>
        <tr>
            <td style="width:35%; background-color:#e8f5e8;"><strong>Estado al Egreso:</strong></td>
            <td style="width:65%;">' . ucwords(str_replace('_', ' ', $egreso['estado_egreso'])) . '</td>
        </tr>
        <tr>
            <td style="width:35%; background-color:#e8f5e8;"><strong>Dias de Estancia:</strong></td>
            <td style="width:65%;">' . $egreso['dias_estancia_calculados'] . ' dias</td>
        </tr>
    </table><br>

    <h3 style="color:#17a2b8;">RESUMEN CLINICO</h3>
    <table border="1" cellpadding="5" cellspacing="0" style="width:100%;">
        <tr>
            <td style="width:100%; background-color:#e0f7fa;"><strong>Diagnostico Principal de Egreso:</strong></td>
        </tr>
        <tr>
            <td style="width:100%;">' . nl2br(htmlspecialchars($egreso['diagnostico_principal_egreso'])) . '</td>
        </tr>';

    if (!empty($egreso['diagnosticos_secundarios'])) {
        $html .= '
        <tr>
            <td style="width:100%; background-color:#e0f7fa;"><strong>Diagnosticos Secundarios:</strong></td>
        </tr>
        <tr>
            <td style="width:100%;">' . nl2br(htmlspecialchars($egreso['diagnosticos_secundarios'])) . '</td>
        </tr>';
    }

    if (!empty($egreso['prescripciones_egreso'])) {
        $html .= '
        <tr>
            <td style="width:100%; background-color:#e0f7fa;"><strong>Medicamentos al Egreso:</strong></td>
        </tr>
        <tr>
            <td style="width:100%;">' . nl2br(htmlspecialchars($egreso['prescripciones_egreso'])) . '</td>
        </tr>';
    }

    if (!empty($egreso['recomendaciones_generales'])) {
        $html .= '
        <tr>
            <td style="width:100%; background-color:#e0f7fa;"><strong>Recomendaciones Generales:</strong></td>
        </tr>
        <tr>
            <td style="width:100%;">' . nl2br(htmlspecialchars($egreso['recomendaciones_generales'])) . '</td>
        </tr>';
    }

    if (!empty($egreso['control_seguimiento'])) {
        $html .= '
        <tr>
            <td style="width:100%; background-color:#e0f7fa;"><strong>Control y Citas:</strong></td>
        </tr>
        <tr>
            <td style="width:100%;">' . nl2br(htmlspecialchars($egreso['control_seguimiento'])) . '</td>
        </tr>';
    }

    if (!empty($egreso['signos_alarma'])) {
        $html .= '
        <tr>
            <td style="width:100%; background-color:#e0f7fa;"><strong>Signos de Alarma:</strong></td>
        </tr>
        <tr>
            <td style="width:100%;">' . nl2br(htmlspecialchars($egreso['signos_alarma'])) . '</td>
        </tr>';
    }

    if (!empty($egreso['observaciones_finales'])) {
        $html .= '
        <tr>
            <td style="width:100%; background-color:#e0f7fa;"><strong>Observaciones Finales:</strong></td>
        </tr>
        <tr>
            <td style="width:100%;">' . nl2br(htmlspecialchars($egreso['observaciones_finales'])) . '</td>
        </tr>';
    }

    $html .= '</table><br>

    <h3 style="color:#6c757d;">MEDICO RESPONSABLE DEL EGRESO</h3>
    <table border="1" cellpadding="5" cellspacing="0" style="width:100%;">
        <tr>
            <td style="width:35%; background-color:#f2f2f2;"><strong>Nombre del Medico:</strong></td>
            <td style="width:65%;">Dr(a). ' . htmlspecialchars($egreso['nombre_medico_egreso']) . '</td>
        </tr>
        <tr>
            <td style="width:35%; background-color:#f2f2f2;"><strong>Especialidad:</strong></td>
            <td style="width:65%;">' . htmlspecialchars($egreso['especialidad_medico_egreso']) . '</td>
        </tr>
        <tr>
            <td style="width:35%; background-color:#f2f2f2;"><strong>Cedula/Registro:</strong></td>
            <td style="width:65%;">' . htmlspecialchars($egreso['registro_medico_cedula']) . '</td>
        </tr>
    </table>';

    $pdf->writeHTML($html, true, false, true, false, '');

    // ✅ GUARDAR EL PDF CON RUTA ABSOLUTA CORREGIDA
    $upload_dir = __DIR__ . '/../../uploads/resumen/';

    // Crear directorio si no existe
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $filename = 'Resumen_Egreso_' . $egreso['cedula'] . '_' . date('Ymd_His') . '.pdf';
    $filepath = $upload_dir . $filename;

    // ✅ Output the PDF to a file usando ruta absoluta
    $pdf->Output($filepath, 'F');

    // ✅ VERIFICAR QUE EL ARCHIVO SE CREÓ CORRECTAMENTE
    if (!file_exists($filepath)) {
        throw new Exception('Error: No se pudo crear el archivo PDF en: ' . $filepath);
    }

    // ✅ URL DE DESCARGA CORREGIDA
    // Desde: modulos/egreso/ hacia: uploads/resumen/
    $download_url = '../../uploads/resumen/' . $filename;

    echo json_encode([
        'status' => 'ok',
        'message' => 'PDF generado y guardado exitosamente.',
        'filename' => $filename,
        'download_url' => $download_url,
        'filepath' => $filepath, // Para debug
        'filesize' => filesize($filepath) // Para verificar
    ]);

} catch (Exception $e) {
    error_log("Error al generar resumen PDF de egreso: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Error interno: ' . $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ]);
}
?>