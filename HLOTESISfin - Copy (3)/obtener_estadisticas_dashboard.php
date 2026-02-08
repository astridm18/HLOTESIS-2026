<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

include("conexion.php");

try {
    $estadisticas = [];

    // --- Nuevos parámetros para el filtro de ingresos ---
    $periodo = $_GET['periodo'] ?? '6M'; // Valor por defecto: 6 meses
    $fecha_inicio_custom = $_GET['fecha_inicio'] ?? null; // Fecha de inicio para rango personalizado
    $fecha_fin_custom = $_GET['fecha_fin'] ?? null;     // Fecha de fin para rango personalizado

    // Calcular las fechas de inicio y fin basadas en el período seleccionado
    $fecha_fin_calculada = date('Y-m-d'); // Siempre hasta hoy por defecto
    $fecha_inicio_calculada = date('Y-m-d'); // Inicializar

    switch ($periodo) {
        case '1M':
            $fecha_inicio_calculada = date('Y-m-d', strtotime('-1 month'));
            break;
        case '2M':
            $fecha_inicio_calculada = date('Y-m-d', strtotime('-2 months'));
            break;
        case '4M':
            $fecha_inicio_calculada = date('Y-m-d', strtotime('-4 months'));
            break;
        case '6M':
            $fecha_inicio_calculada = date('Y-m-d', strtotime('-6 months'));
            break;
        case '1Y':
            $fecha_inicio_calculada = date('Y-m-d', strtotime('-1 year'));
            break;
        case 'custom':
            // Si es personalizado, usar las fechas del frontend
            if ($fecha_inicio_custom && $fecha_fin_custom) {
                $fecha_inicio_calculada = $fecha_inicio_custom;
                $fecha_fin_calculada = $fecha_fin_custom;
            } else {
                // Fallback si no se envían fechas personalizadas válidas
                $fecha_inicio_calculada = date('Y-m-d', strtotime('-6 months'));
                $fecha_fin_calculada = date('Y-m-d');
            }
            break;
        default: // Por defecto 6 meses
            $fecha_inicio_calculada = date('Y-m-d', strtotime('-6 months'));
            break;
    }

    // 1. Total de pacientes
    $query_pacientes = "SELECT COUNT(*) as total FROM pacientes WHERE activo = 1";
    $result = $conn->query($query_pacientes);
    $estadisticas['total_pacientes'] = $result->fetch_assoc()['total'];

    // 2. Casos activos
    $query_casos_activos = "SELECT COUNT(*) as total FROM casos_clinicos WHERE estado_caso = 'activo'";
    $result = $conn->query($query_casos_activos);
    $estadisticas['casos_activos'] = $result->fetch_assoc()['total'];

    // 3. Ingresos hoy
    $query_ingresos_hoy = "SELECT COUNT(*) as total
                          FROM datos_ingreso di
                          INNER JOIN casos_clinicos cc ON di.id_caso = cc.id_caso
                          WHERE DATE(di.fecha_ingreso) = CURDATE()";
    $result = $conn->query($query_ingresos_hoy);
    $estadisticas['ingresos_hoy'] = $result->fetch_assoc()['total'];

    // 4. Casos urgentes (prioridad alta o crítica)
    $query_casos_urgentes = "SELECT COUNT(*) as total
                            FROM casos_clinicos
                            WHERE estado_caso = 'activo'
                            AND prioridad IN ('alta', 'critica')";
    $result = $conn->query($query_casos_urgentes);
    $estadisticas['casos_urgentes'] = $result->fetch_assoc()['total'];

    // 5. Ingresos por mes (dinámico según el filtro)
    // Usamos las fechas calculadas o recibidas para el filtro
    $query_ingresos_mes = "SELECT
                              DATE_FORMAT(di.fecha_ingreso, '%Y-%m') as periodo_agrupado,
                              DATE_FORMAT(di.fecha_ingreso, '%M %Y') as mes_anio_formato,
                              COUNT(*) as total
                          FROM datos_ingreso di
                          INNER JOIN casos_clinicos cc ON di.id_caso = cc.id_caso
                          WHERE di.fecha_ingreso BETWEEN ? AND ? + INTERVAL 1 DAY - INTERVAL 1 SECOND
                          GROUP BY periodo_agrupado, mes_anio_formato
                          ORDER BY periodo_agrupado ASC"; // Ordenar por YYYY-MM para asegurar el orden cronológico

    $stmt_ingresos_mes = $conn->prepare($query_ingresos_mes);
    // Vincula los parámetros
    $stmt_ingresos_mes->bind_param("ss", $fecha_inicio_calculada, $fecha_fin_calculada);
    $stmt_ingresos_mes->execute();
    $result_ingresos_mes = $stmt_ingresos_mes->get_result();

    $ingresos_por_mes_raw = [];
    while ($row = $result_ingresos_mes->fetch_assoc()) {
        $ingresos_por_mes_raw[$row['periodo_agrupado']] = [
            'mes' => $row['mes_anio_formato'],
            'total' => (int) $row['total']
        ];
    }
    $stmt_ingresos_mes->close();

    // Rellenar meses vacíos en el rango para una gráfica continua
    $ingresos_por_mes_final = [];
    $current_date_iterator = new DateTime($fecha_inicio_calculada);
    $end_date_iterator = new DateTime($fecha_fin_calculada);

    while ($current_date_iterator <= $end_date_iterator) {
        $period_key = $current_date_iterator->format('Y-m');
        $month_year_format = $current_date_iterator->format('F Y'); // Nombre del mes y año

        // Traducir nombre del mes si es necesario
        $month_names_es = [
            'January' => 'Enero',
            'February' => 'Febrero',
            'March' => 'Marzo',
            'April' => 'Abril',
            'May' => 'Mayo',
            'June' => 'Junio',
            'July' => 'Julio',
            'August' => 'Agosto',
            'September' => 'Septiembre',
            'October' => 'Octubre',
            'November' => 'Noviembre',
            'December' => 'Diciembre'
        ];
        $month_year_format = str_replace(array_keys($month_names_es), array_values($month_names_es), $month_year_format);

        if (isset($ingresos_por_mes_raw[$period_key])) {
            $ingresos_por_mes_final[] = $ingresos_por_mes_raw[$period_key];
        } else {
            $ingresos_por_mes_final[] = [
                'mes' => $month_year_format,
                'total' => 0
            ];
        }
        $current_date_iterator->modify('+1 month');
    }
    $estadisticas['ingresos_por_mes'] = $ingresos_por_mes_final;


    // 6. Distribución por servicio
    $query_servicios = "SELECT
                           COALESCE(servicio_actual, 'Sin asignar') as servicio,
                           COUNT(*) as total
                       FROM casos_clinicos
                       WHERE estado_caso = 'activo'
                       GROUP BY servicio_actual
                       ORDER BY total DESC";
    $result = $conn->query($query_servicios);
    $servicios = [];
    while ($row = $result->fetch_assoc()) {
        $servicios[] = [
            'servicio' => $row['servicio'],
            'total' => (int) $row['total']
        ];
    }
    $estadisticas['servicios'] = $servicios;

    // 7. Diagnósticos más frecuentes (basado en motivo de consulta)
    // Filtrar también por el rango de fechas actual si es apropiado para diagnósticos
    $query_diagnosticos = "SELECT
                              SUBSTRING(di.motivo_consulta, 1, 50) as diagnostico,
                              COUNT(*) as total
                          FROM datos_ingreso di
                          INNER JOIN casos_clinicos cc ON di.id_caso = cc.id_caso
                          WHERE di.motivo_consulta IS NOT NULL
                          AND di.motivo_consulta != ''
                          AND di.fecha_ingreso BETWEEN ? AND ? + INTERVAL 1 DAY - INTERVAL 1 SECOND
                          GROUP BY diagnostico
                          ORDER BY total DESC
                          LIMIT 5";

    $stmt_diagnosticos = $conn->prepare($query_diagnosticos);
    $stmt_diagnosticos->bind_param("ss", $fecha_inicio_calculada, $fecha_fin_calculada);
    $stmt_diagnosticos->execute();
    $result_diagnosticos = $stmt_diagnosticos->get_result();

    $diagnosticos = [];
    while ($row = $result_diagnosticos->fetch_assoc()) {
        $diagnosticos[] = [
            'diagnostico' => $row['diagnostico'],
            'total' => (int) $row['total']
        ];
    }
    $estadisticas['diagnosticos_frecuentes'] = $diagnosticos;
    $stmt_diagnosticos->close();


    echo json_encode([
        'status' => 'ok',
        'data' => $estadisticas
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
    ]);
}

$conn->close();
?>