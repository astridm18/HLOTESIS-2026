<?php
session_start();
header('Content-Type: application/json');

// Verificar autenticación del usuario
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

// Incluir conexión a la base de datos
include("conexion.php"); // Asegúrate de que esta ruta sea correcta

$response = ['status' => 'error', 'message' => ''];

try {
    // Validar rol del usuario para acceso a estadísticas de ingresos
    if (!in_array($_SESSION['usuario_rol'], ['administrador', 'dirección', 'estadística en salud'])) {
        $response['message'] = 'Acceso denegado. Su rol no tiene permisos para ver estadísticas de ingresos.';
        echo json_encode($response);
        exit;
    }

    // Obtener parámetros de la solicitud GET
    $period = $_GET['period'] ?? 'monthly'; // 'monthly', 'quarterly', 'semiannually', 'annually', 'custom'
    $year = $_GET['year'] ?? date('Y'); // Año actual por defecto
    $month = $_GET['month'] ?? date('Y-m'); // Mes actual por defecto (YYYY-MM)
    $startDate = $_GET['start_date'] ?? null; // Fecha de inicio para rango personalizado (YYYY-MM-DD)
    $endDate = $_GET['end_date'] ?? null; // Fecha de fin para rango personalizado (YYYY-MM-DD)

    $query = "";
    $params = [];
    $types = "";
    $results = [];

    switch ($period) {
        case 'monthly':
            // Agrupar por día para un mes específico
            $currentYear = substr($month, 0, 4);
            $currentMonth = substr($month, 5, 2);
            $query = "SELECT DATE_FORMAT(di.fecha_ingreso, '%Y-%m-%d') AS label, COUNT(*) AS total
                      FROM datos_ingreso di
                      INNER JOIN casos_clinicos cc ON di.id_caso = cc.id_caso
                      WHERE YEAR(di.fecha_ingreso) = ? AND MONTH(di.fecha_ingreso) = ?
                      GROUP BY label
                      ORDER BY label ASC";
            $params = [$currentYear, $currentMonth];
            $types = "ii";
            break;

        case 'quarterly':
            // Agrupar por mes para un trimestre de un año específico
            $query = "SELECT DATE_FORMAT(di.fecha_ingreso, '%Y-%m') AS label, COUNT(*) AS total
                      FROM datos_ingreso di
                      INNER JOIN casos_clinicos cc ON di.id_caso = cc.id_caso
                      WHERE YEAR(di.fecha_ingreso) = ? AND QUARTER(di.fecha_ingreso) = ?
                      GROUP BY label
                      ORDER BY label ASC";
            // Asume que necesitas un parámetro 'quarter' si el frontend lo envía, o calcularlo.
            // Por simplicidad, si solo se da un año, se mostrarán todos los meses de ese año y se etiquetarán por trimestre en el frontend.
            // Si quieres filtrar por un trimestre específico (ej. Q1 2024), el frontend debe enviar `&quarter=1`.
            // Para este ejemplo, solo usaremos el año y agruparemos por trimestre, mostrando los meses.
            // Modificación: Si el frontend envía un trimestre (e.g., `?period=quarterly&year=2024&quarter=1`), se podría usar.
            // Por ahora, mostrará todos los meses del año y dependerá del frontend para mostrarlo por trimestres.
            // Para obtener un trimestre específico, se necesitaría un selector de trimestre en el HTML y pasarlo aquí.
            // Ejemplo para un trimestre específico (requiere `quarter` en GET):
            /*
            $quarter = $_GET['quarter'] ?? 1; // Default to Q1 if not provided
            $query = "SELECT DATE_FORMAT(di.fecha_ingreso, '%Y-%m-%d') AS label, COUNT(*) AS total
                      FROM datos_ingreso di
                      INNER JOIN casos_clinicos cc ON di.id_caso = cc.id_caso
                      WHERE YEAR(di.fecha_ingreso) = ? AND QUARTER(di.fecha_ingreso) = ?
                      GROUP BY label
                      ORDER BY label ASC";
            $params = [$year, $quarter];
            $types = "ii";
            */
            // Versión simple: agrupar por mes para todo el año y el frontend puede interpretar
            $query = "SELECT DATE_FORMAT(di.fecha_ingreso, '%Y-%m') AS label, COUNT(*) AS total
                      FROM datos_ingreso di
                      INNER JOIN casos_clinicos cc ON di.id_caso = cc.id_caso
                      WHERE YEAR(di.fecha_ingreso) = ?
                      GROUP BY label
                      ORDER BY label ASC";
            $params = [$year];
            $types = "i";
            break;

        case 'semiannually':
            // Agrupar por mes para un semestre de un año específico
            $query = "SELECT DATE_FORMAT(di.fecha_ingreso, '%Y-%m') AS label, COUNT(*) AS total
                      FROM datos_ingreso di
                      INNER JOIN casos_clinicos cc ON di.id_caso = cc.id_caso
                      WHERE YEAR(di.fecha_ingreso) = ?
                      GROUP BY label
                      ORDER BY label ASC";
            // Similar al trimestral, si se necesita un semestre específico, se debería enviar en el frontend.
            // Por ahora, mostrará todos los meses del año.
            $params = [$year];
            $types = "i";
            break;

        case 'annually':
            // Agrupar por mes para un año completo (los últimos 12 meses si es el año actual, o los 12 meses de un año pasado)
            $query = "SELECT DATE_FORMAT(di.fecha_ingreso, '%Y-%m') AS label, COUNT(*) AS total
                      FROM datos_ingreso di
                      INNER JOIN casos_clinicos cc ON di.id_caso = cc.id_caso
                      WHERE YEAR(di.fecha_ingreso) = ?
                      GROUP BY label
                      ORDER BY label ASC";
            $params = [$year];
            $types = "i";
            break;

        case 'custom':
            if (empty($startDate) || empty($endDate)) {
                $response['message'] = 'Fechas de inicio y fin son requeridas para el rango personalizado.';
                echo json_encode($response);
                exit;
            }

            // Determinar si agrupar por día o por mes basado en el rango de fechas
            $diff = date_diff(new DateTime($startDate), new DateTime($endDate));
            $days = $diff->days;

            if ($days <= 90) { // Menos o igual a 3 meses, agrupar por día
                $query = "SELECT DATE_FORMAT(di.fecha_ingreso, '%Y-%m-%d') AS label, COUNT(*) AS total
                          FROM datos_ingreso di
                          INNER JOIN casos_clinicos cc ON di.id_caso = cc.id_caso
                          WHERE di.fecha_ingreso BETWEEN ? AND ?
                          GROUP BY label
                          ORDER BY label ASC";
                $params = [$startDate, $endDate];
                $types = "ss";
            } else { // Más de 3 meses, agrupar por mes
                $query = "SELECT DATE_FORMAT(di.fecha_ingreso, '%Y-%m') AS label, COUNT(*) AS total
                          FROM datos_ingreso di
                          INNER JOIN casos_clinicos cc ON di.id_caso = cc.id_caso
                          WHERE di.fecha_ingreso BETWEEN ? AND ?
                          GROUP BY label
                          ORDER BY label ASC";
                $params = [$startDate, $endDate];
                $types = "ss";
            }
            break;

        default:
            $response['message'] = 'Período de tiempo no válido.';
            echo json_encode($response);
            exit;
    }

    // Preparar y ejecutar la consulta
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $results[] = [
            'label' => $row['label'],
            'total' => (int) $row['total']
        ];
    }

    $stmt->close();
    $conn->close();

    $response['status'] = 'ok';
    $response['data'] = $results;

} catch (Exception $e) {
    $response['message'] = 'Error en el servidor: ' . $e->getMessage();
}

echo json_encode($response);
?>