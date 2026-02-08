<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.html");
    exit();
}
include("../../conexion.php");
// Función helper para manejar valores nulos

// Agregar este código al inicio de tu listar_personal.php, después de include("../../conexion.php");

// Obtener foto del usuario actual
$_SESSION['usuario_rol'] = $_SESSION['usuario_rol'] ?? 'Invitado'; // Rol por defecto si no se encuentra
$foto_usuario = null;
$avatar_url = "../../assets/img/default-avatar.png"; // Imagen por defecto

if (isset($_SESSION['usuario_id'])) {
    $query_foto = "SELECT foto FROM personal WHERE id = ?";
    $stmt_foto = $conn->prepare($query_foto);
    $stmt_foto->bind_param("i", $_SESSION['usuario_id']);
    $stmt_foto->execute();
    $result_foto = $stmt_foto->get_result();

    if ($usuario_data = $result_foto->fetch_assoc()) {
        $foto_usuario = $usuario_data['foto'];

        // Si tiene foto, verificar que el archivo existe
        if (!empty($foto_usuario)) {
            $ruta_foto_rel = "../../uploads/fotos_personal/" . $foto_usuario;
            $ruta_foto_abs = __DIR__ . "/../../uploads/fotos_personal/" . $foto_usuario;
            if (file_exists($ruta_foto_abs)) {
                $avatar_url = $ruta_foto_rel;
            }
        }
    }
    $stmt_foto->close();
}

function safe_escape($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Lista de Pacientes</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="../../assets/img/kaiadmin/logoHlo(2).ico" type="image/x-icon" />
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.1/css/dataTables.dataTables.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Fonts and icons -->
    <script src="../../assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
    WebFont.load({
        google: {
            families: ["Public Sans:300,400,500,600,700"]
        },
        custom: {
            families: [
                "Font Awesome 5 Solid",
                "Font Awesome 5 Regular",
                "Font Awesome 5 Brands",
                "simple-line-icons",
            ],
            urls: ["../../assets/css/fonts.min.css"],
        },
        active: function() {
            sessionStorage.fonts = true;
        },
    });
    </script>
    <style>
    /* Fondo blanco completo */
    #tablaPacientes_wrapper {
        background-color: #ffffff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
    }

    /* Estilo para la tabla */
    #tablaPacientes {
        border: none;
        background-color: white;
        font-size: 14px;
    }

    /* Encabezado */
    #tablaPacientes thead th {
        background-color: #10104c;
        color: white;
        border: none;
        text-align: center;
    }

    /* Celdas */
    #tablaPacientes tbody td {
        vertical-align: middle;
        border-bottom: 1px solid #eaeaea;
    }

    /* Botones */
    .dt-buttons .btn {
        background-color: #10104c !important;
        color: white !important;
        border-radius: 8px;
        margin-right: 8px;
        padding: 6px 12px;
        font-size: 13px;
        border: none;
    }

    .dataTables_filter input {
        border-radius: 8px;
        border: 1px solid #ced4da;
        padding: 6px 10px;
    }

    /* Search label */
    .dataTables_filter label {
        font-weight: bold;
        color: #10104c;
    }

    /* Historia médica - ALTURA AUTOMÁTICA SIN SCROLL */
    #historiaContainer .card {
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
    }

    #historiaContainer .card-header {
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }

    #historiaContainer iframe {
        border: none !important;
        overflow: hidden !important;
        min-height: 600px;
    }
    </style>

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

    <!-- CSS Files -->
    <link rel="stylesheet" href="../../assets/css/fonts.min.css" />
    <link rel="stylesheet" href="../../assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../../assets/css/plugins.min.css" />
    <link rel="stylesheet" href="../../assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="../../assets/css/demo.css" />
</head>

<body>
    <div class="wrapper">
        <!-- Modern Sidebar -->
        <!-- Sidebar CORREGIDO para listar_personal.php -->
        <div class="sidebar" data-background-color="white">
            <div class="sidebar-logo">
                <div class="logo-header d-flex justify-content-center align-items-center">
                    <a href="../../index.php" class="logo">
                        <img src="../../assets/img/kaiadmin/logohlo.png" alt="navbar brand" class="navbar-brand"
                            height="80" />
                    </a>
                    <div class="nav-toggle">
                        <button class="btn btn-toggle toggle-sidebar">
                            <i class="gg-menu-right"></i>
                        </button>
                        <button class="btn btn-toggle sidenav-toggler">
                            <i class="gg-menu-left"></i>
                        </button>
                    </div>
                    <button class="topbar-toggler more">
                        <i class="gg-more-vertical-alt"></i>
                    </button>
                </div>
            </div>
            <div class="sidebar-wrapper scrollbar scrollbar-inner">
                <div class="sidebar-content">
                    <ul class="nav nav-secondary">
                        <li class="nav-item active">
                            <a data-bs-toggle="collapse" href="#dashboard" class="collapsed" aria-expanded="false">
                                <i class="fas fa-home"></i>
                                <p>Dashboard</p>
                                <span class="caret"></span>
                            </a>
                            <div class="collapse show" id="dashboard">
                                <ul class="nav nav-collapse">
                                    <li>
                                        <a href="../../index.php">
                                            <span class="sub-item">Dashboard Principal</span>
                                        </a>
                                    </li>
                                    <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'dirección', 'medicina'])): ?>
                                    <li>
                                        <a href="../../index.php?seccion=nuevo-ingreso">
                                            <span class="sub-item">Nuevo Ingreso</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'dirección', 'enfermería', 'medicina'])): ?>
                                    <li>
                                        <a href="../../index.php?seccion=casos-activos">
                                            <span class="sub-item">Casos Activos</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </li>
                        <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'dirección'])): ?>
                        <li class="nav-section">
                            <span class="sidebar-mini-icon">
                                <i class="fa fa-ellipsis-h"></i>
                            </span>
                            <h4 class="text-section">Usuarios</h4>
                        </li>
                        <li class="nav-item">
                            <a data-bs-toggle="collapse" href="#forms">
                                <i class="fas fa-user"></i>
                                <p>Personal</p>
                                <span class="caret"></span>
                            </a>
                            <div class="collapse" id="forms">
                                <ul class="nav nav-collapse">
                                    <li>
                                        <a href="listar_personal.php">
                                            <span class="sub-item">Lista de Personal</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="registro_personal.php">
                                            <span class="sub-item">Registro de Personal</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <?php endif; ?>
                        <li class="nav-section">
                            <span class="sidebar-mini-icon">
                                <i class="fa fa-ellipsis-h"></i>
                            </span>
                            <h4 class="text-section">Gestión</h4>
                        </li>
                        <li class="nav-item">
                            <a data-bs-toggle="collapse" href="#pacientesMenu">
                                <i class="fas fa-procedures"></i>
                                <p>Pacientes</p>
                                <span class="caret"></span>
                            </a>
                            <div class="collapse" id="pacientesMenu">
                                <ul class="nav nav-collapse">
                                    <li>
                                        <a href="listar_pacientes.php">
                                            <span class="sub-item">Lista de Pacientes</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="../../index.php?seccion=buscar-paciente">
                                            <span class="sub-item">Buscar Pacientes</span>
                                        </a>
                                    </li>
                                    <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'dirección', 'medicina'])): ?>
                                    <li>
                                        <a href="../historias/historia_medica.php">
                                            <span class="sub-item">Historia Médica</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </li>
                        <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'dirección', 'estadística en salud'])): ?>
                        <li class="nav-section">
                            <span class="sidebar-mini-icon">
                                <i class="fa fa-ellipsis-h"></i>
                            </span>
                            <h4 class="text-section">Reportes</h4>
                        </li>
                        <li class="nav-item">
                            <a href="../../index.php?seccion=estadisticas">
                                <i class="fas fa-chart-bar"></i>
                                <p>Estadísticas</p>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        <!-- Main Panel -->
        <div class="main-panel">
            <div class="main-header">
                <div class="main-header-logo">
                    <!-- Logo Header -->
                    <div class="logo-header" data-background-color="white">
                        <a href="../../index.php" class="logo">
                            <img src="../../assets/img/kaiadmin/logo_light.svg" alt="navbar brand" class="navbar-brand" />
                        </a>
                        <div class="nav-toggle">
                            <button class="btn btn-toggle toggle-sidebar">
                                <i class="gg-menu-right" style="color: black;"></i>
                            </button>
                            <button class="btn btn-toggle sidenav-toggler">
                                <i class="gg-menu-left"></i>
                            </button>
                        </div>
                        <button class="topbar-toggler more">
                            <i class="gg-more-vertical-alt"></i>
                        </button>
                    </div>
                    <!-- End Logo Header -->
                </div>
                <!-- Navbar Header -->
                <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
                    <div class="container-fluid">
                        <img src="../../assets/img/IVSS.png" alt="navbar brand" class="navbar-brand" height="80" />
                        <h2> Hospital General Dr. Luis Ortega tipo III</h2>

                        <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                            <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'dirección'])): ?>
                            <!-- Quick Actions -->
                            <li class="nav-item topbar-icon dropdown hidden-caret">
                                <a class="nav-link" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                                    <i class="fas fa-layer-group"></i>
                                </a>
                                <div class="dropdown-menu quick-actions animated fadeIn">
                                    <div class="quick-actions-header">
                                        <span class="title mb-1">Acciones Rápidas</span>
                                        <span class="subtitle op-7">Atajos</span>
                                    </div>
                                    <div class="quick-actions-scroll scrollbar-outer">
                                        <div class="quick-actions-items">
                                            <div class="row m-0">
                                                <a class="col-6 col-md-4 p-0" href="#"
                                                    onclick="mostrarSeccion('nuevo-ingreso')">
                                                    <div class="quick-actions-item">
                                                        <div class="avatar-item bg-primary rounded-circle">
                                                            <i class="fas fa-user-plus"></i>
                                                        </div>
                                                        <span class="text">Nuevo Ingreso</span>
                                                    </div>
                                                </a>
                                                <a class="col-6 col-md-4 p-0" href="#"
                                                    onclick="mostrarSeccion('buscar-paciente')">
                                                    <div class="quick-actions-item">
                                                        <div class="avatar-item bg-success rounded-circle">
                                                            <i class="fas fa-search"></i>
                                                        </div>
                                                        <span class="text">Buscar</span>
                                                    </div>
                                                </a>
                                                <a class="col-6 col-md-4 p-0" href="#"
                                                    onclick="mostrarSeccion('casos-activos')">
                                                    <div class="quick-actions-item">
                                                        <div class="avatar-item bg-warning rounded-circle">
                                                            <i class="fas fa-list"></i>
                                                        </div>
                                                        <span class="text">Casos</span>
                                                    </div>
                                                </a>
                                                <a class="col-6 col-md-4 p-0" href="#"
                                                    onclick="mostrarSeccion('estadisticas')">
                                                    <div class="quick-actions-item">
                                                        <div class="avatar-item bg-info rounded-circle">
                                                            <i class="fas fa-chart-bar"></i>
                                                        </div>
                                                        <span class="text">Reportes</span>
                                                    </div>
                                                </a>
                                                <a class="col-6 col-md-4 p-0" href="listar_personal.php">
                                                    <div class="quick-actions-item">
                                                        <div class="avatar-item bg-secondary rounded-circle">
                                                            <i class="fas fa-users"></i>
                                                        </div><span class="text">Personal</span>
                                                    </div>
                                                </a>
                                                <a class="col-6 col-md-4 p-0" href="registro_personal.php">
                                                    <div class="quick-actions-item">
                                                        <div class="avatar-item bg-danger rounded-circle">
                                                            <i class="fas fa-user-plus"></i>
                                                        </div>
                                                        <span class="text">Registro</span>
                                                    </div>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <?php endif; ?>

                            <li class="nav-item topbar-user dropdown hidden-caret">
                                <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#"
                                    aria-expanded="false">
                                    <div class="avatar-sm">
                                        <img src="<?php echo htmlspecialchars($avatar_url); ?>"
                                            alt="Foto de <?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?>"
                                            class="avatar-img rounded-circle"
                                            style="width: 36px; height: 36px; object-fit: cover;"
                                            onerror="this.src='../../assets/img/default-avatar.png';" />
                                    </div>
                                    <span class="profile-username">
                                        <span class="op-7">Hola,</span>
                                        <span
                                            class="fw-bold"><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?></span>
                                    </span>
                                </a>
                                <ul class="dropdown-menu dropdown-user animated fadeIn">
                                    <div class="dropdown-user-scroll scrollbar-outer">
                                        <li>
                                            <div class="user-box">
                                                <div class="avatar-lg">
                                                    <img src="<?php echo htmlspecialchars($avatar_url); ?>"
                                                        alt="Foto de perfil de <?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?>"
                                                        class="avatar-img rounded"
                                                        style="width: 60px; height: 60px; object-fit: cover;"
                                                        onerror="this.src='../../assets/img/default-avatar.png';" />
                                                </div>
                                                <div class="u-text">
                                                    <h4><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?>
                                                    </h4>
                                                    <p class="text-muted">
                                                        <?php echo htmlspecialchars($_SESSION['usuario_rol'] ?? 'email@ejemplo.com'); ?>
                                                    </p>

                                                </div>
                                            </div>
                                        </li>
                                        <li>

                                            <div class="dropdown-divider"></div>
                                            <!-- ✅ NUEVO: Enlace de Ayuda -->
                                            <a class="dropdown-item" href="../../reportes/manual-usuario.pdf"
                                                target="_blank">
                                                <i class="fas fa-question-circle me-2 text-info"></i>Ayuda
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="../logout.php">
                                                <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                                            </a>
                                        </li>
                                    </div>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>
                <!-- End Navbar -->
            </div>

            <div class="container">
                <div class="page-inner">
                    <div class="page-header">
                        <h3 class="fw-bold mb-3">Pacientes Registrados</h3>
                        <ul class="breadcrumbs mb-3">
                            <li class="nav-home">
                                <a href="../../index.php">
                                    <i class="icon-home"></i>
                                </a>
                            </li>
                            <li class="separator">
                                <i class="icon-arrow-right"></i>
                            </li>
                            <li class="nav-item">
                                <a href="listar_pacientes.php">Pacientes</a>
                            </li>
                        </ul>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Lista de Pacientes</div>
                                </div>

                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="tablaPacientes" class="display responsive nowrap" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>N° Historia</th>
                                                    <th>Cédula</th>
                                                    <th>Nombres</th>
                                                    <th>Apellidos</th>
                                                    <th>Sexo</th>
                                                    <th>Fecha Nacimiento</th>
                                                    <th>Teléfono</th>
                                                    <th>Dirección</th>
                                                    <th>Fecha Registro</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Consulta para obtener pacientes
                                                $query = "SELECT id_paciente, numero_historia, cedula, nombres, apellidos, sexo, fecha_nacimiento, telefono, direccion, creado_en FROM pacientes ORDER BY creado_en DESC";
                                                $resultado = $conn->query($query);

                                                if ($resultado && $resultado->num_rows > 0) {
                                                    while ($row = $resultado->fetch_assoc()) {
                                                        // Escapar valores para evitar problemas
                                                        $nombres = safe_escape($row['nombres']);
                                                        $apellidos = safe_escape($row['apellidos']);
                                                        $direccion = safe_escape($row['direccion']);
                                                        $telefono = safe_escape($row['telefono']);

                                                        // Formatear fecha de nacimiento
                                                        $fecha_nacimiento = '';
                                                        if (!empty($row['fecha_nacimiento']) && $row['fecha_nacimiento'] != '0000-00-00') {
                                                            $fecha_nacimiento = date('d/m/Y', strtotime($row['fecha_nacimiento']));
                                                        } else {
                                                            $fecha_nacimiento = 'N/A';
                                                        }

                                                        // Formatear fecha de registro
                                                        $fecha_registro = date('d/m/Y H:i', strtotime($row['creado_en']));

                                                        // Formatear sexo
                                                        $sexo_display = '';
                                                        if ($row['sexo'] == 'M') {
                                                            $sexo_display = 'Masculino';
                                                        } elseif ($row['sexo'] == 'F') {
                                                            $sexo_display = 'Femenino';
                                                        } else {
                                                            $sexo_display = $row['sexo'];
                                                        }

                                                        // Verificar permisos para edición
                                                        $puede_editar = isset($_SESSION['usuario_id']) &&
                                                            isset($_SESSION['usuario_rol']) &&
                                                            in_array($_SESSION['usuario_rol'], ['administrador', 'dirección', 'enfermería', 'medicina']);

                                                        echo "<tr>
            <td><strong>{$row['numero_historia']}</strong></td>
            <td>{$row['cedula']}</td>
            <td>{$nombres}</td>
            <td>{$apellidos}</td>
            <td>{$sexo_display}</td>
            <td>{$fecha_nacimiento}</td>
            <td>{$telefono}</td>
            <td>{$direccion}</td>
            <td>{$fecha_registro}</td>
            <td>
                <button class='btn btn-sm btn-info ver-btn' data-id='{$row['id_paciente']}' title='Ver Historia Médica'>
                    <i class='fas fa-eye'></i>
                </button>";

                                                        // Mostrar botón de editar solo si tiene permisos
                                                        if ($puede_editar) {
                                                            echo "<button class='btn btn-sm btn-warning editar-btn'
                    data-id='{$row['id_paciente']}'
                    data-numero_historia='{$row['numero_historia']}'
                    data-cedula='{$row['cedula']}'
                    data-nombres='{$nombres}'
                    data-apellidos='{$apellidos}'
                    data-sexo='{$row['sexo']}'
                    data-fecha_nacimiento='{$row['fecha_nacimiento']}'
                    data-telefono='{$telefono}'
                    data-direccion='{$direccion}'
                    title='Editar Paciente'>
                <i class='fas fa-pen'></i>
            </button>";
                                                        } else {
                                                            // Botón deshabilitado para usuarios sin permisos
                                                            echo "<button class='btn btn-sm btn-secondary' disabled title='Sin permisos para editar'>
                <i class='fas fa-pen'></i>
            </button>";
                                                        }

                                                        echo "</td></tr>";
                                                    }
                                                } else {
                                                    echo "<tr>
        <td colspan='10' class='text-center'>No hay pacientes registrados</td>
    </tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MODAL DE EDICIÓN DE PACIENTE -->
                    <div class="modal fade" id="modalEditarPaciente" tabindex="-1" aria-labelledby="modalEditarLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <!-- ENCABEZADO -->
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title w-100 text-center" id="modalEditarLabel">Editar Paciente</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Cerrar"></button>
                                </div>

                                <!-- CUERPO -->
                                <div class="modal-body">
                                    <form id="formEditarPaciente">
                                        <input type="hidden" id="editarId" name="id_paciente">
                                        <div class="row">

                                            <!-- NÚMERO DE HISTORIA -->
                                            <div class="col-md-6 mb-3">
                                                <label for="editarNumeroHistoria" class="form-label">Número de
                                                    Historia</label>
                                                <input type="text" class="form-control" id="editarNumeroHistoria"
                                                    name="numero_historia" readonly style="background-color: #f8f9fa;">
                                            </div>

                                            <!-- CÉDULA -->
                                            <div class="col-md-6 mb-3">
                                                <label for="editarCedula" class="form-label">Cédula</label>
                                                <input type="text" class="form-control" id="editarCedula" name="cedula"
                                                    required>
                                            </div>

                                            <!-- NOMBRES -->
                                            <div class="col-md-6 mb-3">
                                                <label for="editarNombres" class="form-label">Nombres</label>
                                                <input type="text" class="form-control" id="editarNombres"
                                                    name="nombres" required>
                                            </div>

                                            <!-- APELLIDOS -->
                                            <div class="col-md-6 mb-3">
                                                <label for="editarApellidos" class="form-label">Apellidos</label>
                                                <input type="text" class="form-control" id="editarApellidos"
                                                    name="apellidos" required>
                                            </div>

                                            <!-- SEXO -->
                                            <div class="col-md-6 mb-3">
                                                <label for="editarSexo" class="form-label">Sexo</label>
                                                <select class="form-select" id="editarSexo" name="sexo" required>
                                                    <option value="">Seleccionar...</option>
                                                    <option value="M">Masculino</option>
                                                    <option value="F">Femenino</option>
                                                </select>
                                            </div>

                                            <!-- FECHA DE NACIMIENTO -->
                                            <div class="col-md-6 mb-3">
                                                <label for="editarFechaNacimiento" class="form-label">Fecha de
                                                    Nacimiento</label>
                                                <input type="date" class="form-control" id="editarFechaNacimiento"
                                                    name="fecha_nacimiento">
                                            </div>

                                            <!-- TELÉFONO -->
                                            <div class="col-md-6 mb-3">
                                                <label for="editarTelefono" class="form-label">Teléfono</label>
                                                <input type="tel" class="form-control" id="editarTelefono"
                                                    name="telefono">
                                            </div>

                                            <!-- DIRECCIÓN -->
                                            <div class="col-md-6 mb-3">
                                                <label for="editarDireccion" class="form-label">Dirección</label>
                                                <input type="text" class="form-control" id="editarDireccion"
                                                    name="direccion">
                                            </div>

                                        </div>
                                    </form>
                                </div>

                                <!-- PIE DEL MODAL -->
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Cancelar</button>
                                    <button type="button" class="btn btn-primary" id="guardarPaciente">Guardar
                                        cambios</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!--   Core JS Files   -->
                    <script src="../../assets/js/core/jquery-3.7.1.min.js"></script>
                    <script src="../../assets/js/core/popper.min.js"></script>
                    <script src="../../assets/js/core/bootstrap.min.js"></script>

                    <!-- jQuery Scrollbar -->
                    <script src="../../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

                    <!-- Chart JS -->
                    <script src="../../assets/js/plugin/chart.js/chart.min.js"></script>

                    <!-- jQuery Sparkline -->
                    <script src="../../assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js"></script>

                    <!-- Chart Circle -->
                    <script src="../../assets/js/plugin/chart-circle/circles.min.js"></script>

                    <!-- Datatables -->
                    <script src="../../assets/js/plugin/datatables/datatables.min.js"></script>

                    <!-- Bootstrap Notify -->
                    <script src="../../assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

                    <!-- jQuery Vector Maps -->
                    <script src="../../assets/js/plugin/jsvectormap/jsvectormap.min.js"></script>
                    <script src="../../assets/js/plugin/jsvectormap/world.js"></script>

                    <!-- Google Maps Plugin -->
                    <script src="../../assets/js/plugin/gmaps/gmaps.js"></script>

                    <!-- Sweet Alert -->
                    <script src="../../assets/js/plugin/sweetalert/sweetalert.min.js"></script>

                    <!-- Kaiadmin JS -->
                    <script src="../../assets/js/kaiadmin.min.js"></script>

                    <!-- Kaiadmin DEMO methods -->
                    <script src="../../assets/js/setting-demo2.js"></script>

                    <!-- DataTables JS + Botones -->
                    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
                    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
                    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
                    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
                    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

                    <!-- Inicializar DataTable con botones en español -->
                    <script>
                    $(document).ready(function() {
                        $('#tablaPacientes').DataTable({
                            dom: 'Bfrtip',
                            buttons: [{
                                    extend: 'copy',
                                    text: 'Copiar'
                                },
                                {
                                    extend: 'csv',
                                    text: 'CSV'
                                },
                                {
                                    extend: 'excel',
                                    text: 'Excel'
                                },
                                {
                                    extend: 'pdf',
                                    text: 'PDF'
                                },
                                {
                                    extend: 'print',
                                    text: 'Imprimir'
                                }
                            ],
                            responsive: true,
                            language: {
                                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json',
                                search: "Buscar:",
                                searchPlaceholder: "Buscar...",
                                // ✅ AGREGADAS: Traducciones específicas
                                info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                                infoEmpty: "Mostrando 0 a 0 de 0 registros",
                                infoFiltered: "(filtrado de _MAX_ registros totales)",
                                lengthMenu: "Mostrar _MENU_ registros por página",
                                zeroRecords: "No se encontraron registros coincidentes",
                                emptyTable: "No hay datos disponibles en la tabla",
                                paginate: {
                                    first: "Primero",
                                    last: "Último",
                                    next: "Siguiente",
                                    previous: "Anterior"
                                }
                            },
                            order: [
                                [8, 'desc']
                            ] // Ordenar por fecha de registro (columna 8) descendente
                        });
                    });
                    // Función para mostrar historia en iframe
                    function mostrarHistoriaEnIframe(url, paciente_nombre, tipo = 'caso') {
                        // Ocultar SOLO el contenido de la tabla, NO el sidebar ni navbar
                        $('.card').hide();
                        $('.page-header').hide();

                        // Determinar el título según el tipo
                        const titulo = tipo === 'historia' ? 'Historia Médica Completa' :
                            'Historia Médica - Caso Actual';

                        // Crear el contenedor TODO EN UN SOLO BLOQUE
                        const iframeContainer = `
        <div id="historiaContainer">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1">
                            <i class="fas fa-file-medical text-primary"></i> ${titulo}
                        </h4>
                       
                    </div>
                    <div class="btn-group">
                        <button id="imprimirHistoria" class="btn btn-info btn-md">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                        <button id="volverPacientes" class="btn btn-secondary btn-md">
                            <i class="fas fa-arrow-left"></i> Volver
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <iframe id="historiaIframe" 
                            src="${url}" 
                            width="100%" 
                            frameborder="0" 
                            scrolling="no"
                            style="border: none; display: block; overflow: hidden;">
                    </iframe>
                </div>
            </div>
        </div>
    `;

                        // Insertar el iframe DENTRO del page-inner
                        $('.page-inner').append(iframeContainer);

                        // Ajustar altura automáticamente cuando cargue el iframe
                        $('#historiaIframe').on('load', function() {
                            try {
                                const iframe = this;
                                const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                                const height = iframeDoc.body.scrollHeight;
                                $(iframe).height(height + 50);
                            } catch (e) {
                                $(this).height(800);
                            }
                        });

                        // Funcionalidad del botón imprimir
                        $('#imprimirHistoria').on('click', function() {
                            try {
                                const iframe = document.getElementById('historiaIframe');
                                const iframeWindow = iframe.contentWindow || iframe.contentDocument.defaultView;

                                if (iframeWindow) {
                                    iframeWindow.focus();
                                    iframeWindow.print();
                                } else {
                                    // Fallback: abrir en nueva ventana para imprimir
                                    const printWindow = window.open(url + '&print=1', '_blank',
                                        'width=800,height=600');
                                    printWindow.onload = function() {
                                        printWindow.print();
                                        printWindow.close();
                                    };
                                }
                            } catch (e) {
                                console.error('Error al imprimir:', e);
                                Swal.fire('Error', 'No se pudo imprimir la historia médica', 'error');
                            }
                        });

                        // Funcionalidad del botón volver
                        $('#volverPacientes').on('click', function() {
                            $('#historiaContainer').remove();
                            $('.page-header').show();
                            $('.card').show();
                        });
                    }

                    // ✅ SCRIPT PRINCIPAL: Ver historia médica con RESTRICCIONES DE ROL para listar_pacientes.php
                    $(document).on('click', '.ver-btn', function() {
                        const paciente_id = $(this).data('id');

                        // OBTENER ROL DEL USUARIO DESDE PHP
                        const userRole = '<?php echo $_SESSION['usuario_rol']; ?>';

                        // DEFINIR PERMISOS POR ROL
                        const rolePermissions = {
                            'administrador': {
                                historiaCompleta: true,
                                casoActual: true,
                                nuevoIngreso: true
                            },
                            'dirección': {
                                historiaCompleta: true,
                                casoActual: true,
                                nuevoIngreso: true
                            },
                            'medicina': {
                                historiaCompleta: true,
                                casoActual: true,
                                nuevoIngreso: true
                            },
                            'enfermería': {
                                historiaCompleta: false, // NO puede ver historia completa
                                casoActual: true, // SÍ puede ver caso actual
                                nuevoIngreso: false // NO puede crear ingresos
                            },
                            'estadística en salud': {
                                historiaCompleta: true, // SÍ puede ver historia completa
                                casoActual: false, // NO puede ver caso actual
                                nuevoIngreso: false // NO puede crear ingresos
                            }
                        };

                        const permissions = rolePermissions[userRole] || {
                            historiaCompleta: false,
                            casoActual: false,
                            nuevoIngreso: false
                        };

                        // Mostrar loading
                        Swal.fire({
                            title: 'Verificando casos del paciente...',
                            text: 'Por favor espere',
                            icon: 'info',
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            willOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Verificar casos del paciente
                        $.ajax({
                            url: '../historias/verificar_casos_cerrados.php',
                            method: 'GET',
                            data: {
                                paciente_id: paciente_id
                            },
                            dataType: 'json',
                            success: function(response) {
                                Swal.close();

                                if (response.success) {
                                    const tieneCasosCerrados = response.casos_cerrados > 0;
                                    const tieneCasosActivos = response.casos_activos > 0;

                                    // ========================================
                                    // ESCENARIO 1: CASOS CERRADOS + CASOS ACTIVOS
                                    // ========================================
                                    if (tieneCasosCerrados && tieneCasosActivos) {
                                        let buttonsHTML = '';
                                        let hasOptions = false;

                                        // Opción Historia Completa
                                        if (permissions.historiaCompleta) {
                                            buttonsHTML += `
                            <button type="button" class="btn btn-danger me-2" id="btnHistoriaCompleta">
                                <i class="fas fa-file-medical"></i> Historia Completa
                            </button>
                        `;
                                            hasOptions = true;
                                        }

                                        // Opción Caso Actual
                                        if (permissions.casoActual) {
                                            buttonsHTML += `
                            <button type="button" class="btn btn-primary me-2" id="btnCasoActual">
                                <i class="fas fa-eye"></i> Caso Actual
                            </button>
                        `;
                                            hasOptions = true;
                                        }

                                        // Opción Nuevo Ingreso
                                        if (permissions.nuevoIngreso) {
                                            buttonsHTML += `
                            <button type="button" class="btn btn-success me-2" id="btnNuevoIngreso">
                                <i class="fas fa-plus"></i> Nuevo Ingreso
                            </button>
                        `;
                                            hasOptions = true;
                                        }

                                        if (!hasOptions) {
                                            Swal.fire({
                                                title: '🔒 Sin Permisos',
                                                html: `
                                <div class="text-center">
                                    <i class="fas fa-user-slash fa-4x text-warning mb-3"></i>
                                    <p><strong>Paciente:</strong> ${response.paciente_nombre}</p>
                                    <p><strong>Su rol:</strong> <span class="badge bg-warning">${userRole}</span></p>
                                    <hr>
                                    <p>No tiene permisos para ver información de este paciente.</p>
                                </div>
                            `,
                                                icon: 'warning',
                                                confirmButtonColor: '#6c757d'
                                            });
                                            return;
                                        }

                                        Swal.fire({
                                            title: 'Opciones Disponibles',
                                            html: `
                            <div class="text-left">
                                <p><strong>Paciente:</strong> ${response.paciente_nombre}</p>
                                <p><strong>Casos cerrados:</strong> ${response.casos_cerrados}</p>
                                <p><strong>Casos activos:</strong> ${response.casos_activos}</p>
                                <p><strong>Su rol:</strong> <span class="badge bg-info">${userRole}</span></p>
                                <hr>
                                <p>Seleccione una opción:</p>
                                <div class="d-flex justify-content-center gap-2 mt-3 flex-wrap">
                                    ${buttonsHTML}
                                    <button type="button" class="btn btn-secondary" id="btnCancelar">
                                        <i class="fas fa-times"></i> Cancelar
                                    </button>
                                </div>
                            </div>
                        `,
                                            icon: 'question',
                                            showConfirmButton: false,
                                            showCancelButton: false,
                                            allowOutsideClick: true,
                                            allowEscapeKey: true,
                                            width: '600px',
                                            didRender: () => {
                                                // Event listeners para los botones
                                                $('#btnHistoriaCompleta').on('click',
                                                    function() {
                                                        Swal.close();
                                                        const urlHistoria =
                                                            `../historias/generar_historia_pdf.php?paciente_id=${paciente_id}&vista=iframe`;
                                                        mostrarHistoriaEnIframe(
                                                            urlHistoria,
                                                            response
                                                            .paciente_nombre,
                                                            'historia');
                                                    });

                                                $('#btnCasoActual').on('click',
                                                    function() {
                                                        Swal.close();
                                                        if (response
                                                            .caso_activo_id) {
                                                            const urlCaso =
                                                                `../procesos/dashboard_procesos.php?caso=${response.caso_activo_id}`;
                                                            mostrarHistoriaEnIframe(
                                                                urlCaso,
                                                                response
                                                                .paciente_nombre,
                                                                'caso');
                                                        } else {
                                                            Swal.fire('Error',
                                                                'No se pudo obtener el ID del caso activo',
                                                                'error');
                                                        }
                                                    });

                                                $('#btnNuevoIngreso').on('click',
                                                    function() {
                                                        Swal.close();
                                                        // Verificar permisos antes de redireccionar
                                                        if (!permissions
                                                            .nuevoIngreso) {
                                                            Swal.fire({
                                                                title: '🔒 Sin Permisos',
                                                                text: 'Su rol no puede crear nuevos ingresos',
                                                                icon: 'warning'
                                                            });
                                                            return;
                                                        }
                                                        window.location.href =
                                                            `modulos/ingreso/formulario_ingreso.php?paciente=${paciente_id}`;
                                                    });

                                                // Event listener para el botón Cancelar
                                                $('#btnCancelar').on('click',
                                                    function() {
                                                        Swal.close();
                                                    });
                                            }
                                        });
                                    }

                                    // ========================================
                                    // ESCENARIO 2: SOLO CASOS CERRADOS
                                    // ========================================
                                    else if (tieneCasosCerrados && !tieneCasosActivos) {
                                        if (permissions.historiaCompleta) {
                                            // Puede ver historia completa
                                            let denyButton = permissions.nuevoIngreso;
                                            let denyButtonText = permissions.nuevoIngreso ?
                                                '<i class="fas fa-plus"></i> Nuevo Ingreso' : '';

                                            Swal.fire({
                                                title: 'Historia Médica Disponible',
                                                html: `
                                <div class="text-left">
                                    <p><strong>Paciente:</strong> ${response.paciente_nombre}</p>
                                    <p><strong>Casos cerrados:</strong> ${response.casos_cerrados}</p>
                                    <p><strong>Su rol:</strong> <span class="badge bg-info">${userRole}</span></p>
                                    <hr>
                                    <p>Este paciente solo tiene casos cerrados.</p>
                                    <p>¿Qué desea hacer?</p>
                                </div>
                            `,
                                                icon: 'question',
                                                showCancelButton: true,
                                                showDenyButton: denyButton,
                                                confirmButtonText: '<i class="fas fa-file-medical"></i> Ver Historia Completa',
                                                denyButtonText: denyButtonText,
                                                cancelButtonText: 'Cancelar',
                                                confirmButtonColor: '#dc3545',
                                                denyButtonColor: '#28a745',
                                                cancelButtonColor: '#6c757d'
                                            }).then((result) => {
                                                if (result.isConfirmed) {
                                                    const urlHistoria =
                                                        `../historias/generar_historia_pdf.php?paciente_id=${paciente_id}&vista=iframe`;
                                                    mostrarHistoriaEnIframe(urlHistoria,
                                                        response.paciente_nombre,
                                                        'historia');
                                                } else if (result.isDenied && permissions
                                                    .nuevoIngreso) {
                                                    window.location.href =
                                                        `modulos/ingreso/formulario_ingreso.php?paciente=${paciente_id}`;
                                                }
                                            });
                                        } else {
                                            // NO puede ver historia completa (enfermería)
                                            if (permissions.nuevoIngreso) {
                                                Swal.fire({
                                                    title: 'Solo Nuevo Ingreso Disponible',
                                                    html: `
                                    <div class="text-left">
                                        <p><strong>Paciente:</strong> ${response.paciente_nombre}</p>
                                        <p><strong>Su rol:</strong> <span class="badge bg-warning">${userRole}</span></p>
                                        <hr>
                                        <p>Este paciente tiene casos cerrados, pero su rol no permite ver la historia completa.</p>
                                        <p><strong>Opciones disponibles:</strong> Solo puede crear un nuevo ingreso.</p>
                                        <div class="alert alert-info">
                                            <small><i class="fas fa-info-circle"></i> Su rol solo permite ver casos activos</small>
                                        </div>
                                    </div>
                                `,
                                                    icon: 'info',
                                                    showCancelButton: true,
                                                    confirmButtonText: '<i class="fas fa-plus"></i> Crear Nuevo Ingreso',
                                                    cancelButtonText: 'Cancelar',
                                                    confirmButtonColor: '#28a745'
                                                }).then((result) => {
                                                    if (result.isConfirmed) {
                                                        window.location.href =
                                                            `modulos/ingreso/formulario_ingreso.php?paciente=${paciente_id}`;
                                                    }
                                                });
                                            } else {
                                                // Sin ningún permiso
                                                Swal.fire({
                                                    title: '🔒 Sin Permisos',
                                                    html: `
                                    <div class="text-center">
                                        <i class="fas fa-lock fa-4x text-warning mb-3"></i>
                                        <p><strong>Paciente:</strong> ${response.paciente_nombre}</p>
                                        <p><strong>Su rol:</strong> <span class="badge bg-warning">${userRole}</span></p>
                                        <hr>
                                        <p>No tiene permisos para acceder a la información de este paciente.</p>
                                        <div class="alert alert-light">
                                            <small>El paciente solo tiene casos cerrados y su rol no permite ver historias completas.</small>
                                        </div>
                                    </div>
                                `,
                                                    icon: 'warning',
                                                    confirmButtonColor: '#6c757d'
                                                });
                                            }
                                        }
                                    }

                                    // ========================================
                                    // ESCENARIO 3: SOLO CASOS ACTIVOS
                                    // ========================================
                                    else if (!tieneCasosCerrados && tieneCasosActivos) {
                                        if (permissions.casoActual) {
                                            let denyButton = permissions.nuevoIngreso;
                                            let denyButtonText = permissions.nuevoIngreso ?
                                                '<i class="fas fa-plus"></i> Nuevo Ingreso' : '';

                                            Swal.fire({
                                                title: 'Solo Casos Activos',
                                                html: `
                                <div class="text-left">
                                    <p><strong>Paciente:</strong> ${response.paciente_nombre}</p>
                                    <p><strong>Casos activos:</strong> ${response.casos_activos}</p>
                                    <p><strong>Su rol:</strong> <span class="badge bg-info">${userRole}</span></p>
                                    <hr>
                                    <p>Este paciente solo tiene casos activos.</p>
                                    <p>¿Qué desea hacer?</p>
                                </div>
                            `,
                                                icon: 'info',
                                                showCancelButton: true,
                                                showDenyButton: denyButton,
                                                confirmButtonText: '<i class="fas fa-eye"></i> Ver Caso Actual',
                                                denyButtonText: denyButtonText,
                                                cancelButtonText: 'Cancelar',
                                                confirmButtonColor: '#007bff',
                                                denyButtonColor: '#28a745'
                                            }).then((result) => {
                                                if (result.isConfirmed) {
                                                    if (response.caso_activo_id) {
                                                        const urlCaso =
                                                            `modulos/procesos/dashboard_procesos.php?caso=${response.caso_activo_id}`;
                                                        mostrarHistoriaEnIframe(urlCaso,
                                                            response.paciente_nombre,
                                                            'caso');
                                                    } else {
                                                        Swal.fire('Error',
                                                            'No se pudo obtener el ID del caso activo',
                                                            'error');
                                                    }
                                                } else if (result.isDenied && permissions
                                                    .nuevoIngreso) {
                                                    window.location.href =
                                                        `modulos/ingreso/formulario_ingreso.php?paciente=${paciente_id}`;
                                                }
                                            });
                                        } else {
                                            // NO puede ver casos activos (estadística)
                                            Swal.fire({
                                                title: '🔒 Sin Permisos para Casos Activos',
                                                html: `
                                <div class="text-center">
                                    <i class="fas fa-chart-bar fa-4x text-info mb-3"></i>
                                    <p><strong>Paciente:</strong> ${response.paciente_nombre}</p>
                                    <p><strong>Su rol:</strong> <span class="badge bg-info">${userRole}</span></p>
                                    <hr>
                                    <p>Este paciente solo tiene casos activos.</p>
                                    <p>Su rol no permite ver casos activos, solo historias completas de casos cerrados.</p>
                                    <div class="alert alert-light">
                                        <small><i class="fas fa-info-circle"></i> Estadística en Salud solo puede acceder a historias médicas completas</small>
                                    </div>
                                </div>
                            `,
                                                icon: 'info',
                                                confirmButtonColor: '#6c757d'
                                            });
                                        }
                                    }

                                    // ========================================
                                    // ESCENARIO 4: SIN CASOS
                                    // ========================================
                                    else {
                                        if (permissions.nuevoIngreso) {
                                            Swal.fire({
                                                title: 'Sin Casos Registrados',
                                                html: `
                                <div class="text-left">
                                    <p><strong>Paciente:</strong> ${response.paciente_nombre}</p>
                                    <p><strong>Su rol:</strong> <span class="badge bg-info">${userRole}</span></p>
                                    <hr>
                                    <p>Este paciente no tiene casos médicos registrados.</p>
                                    <p>¿Desea crear un nuevo ingreso?</p>
                                </div>
                            `,
                                                icon: 'question',
                                                showCancelButton: true,
                                                confirmButtonText: '<i class="fas fa-plus"></i> Crear Nuevo Ingreso',
                                                cancelButtonText: 'Cancelar',
                                                confirmButtonColor: '#28a745'
                                            }).then((result) => {
                                                if (result.isConfirmed) {
                                                    window.location.href =
                                                        `modulos/ingreso/formulario_ingreso.php?paciente=${paciente_id}`;
                                                }
                                            });
                                        } else {
                                            // Sin permisos para crear ingresos
                                            Swal.fire({
                                                title: '🔒 Sin Casos ni Permisos',
                                                html: `
                                <div class="text-center">
                                    <i class="fas fa-user-slash fa-4x text-muted mb-3"></i>
                                    <p><strong>Paciente:</strong> ${response.paciente_nombre}</p>
                                    <p><strong>Su rol:</strong> <span class="badge bg-warning">${userRole}</span></p>
                                    <hr>
                                    <p>Este paciente no tiene casos registrados.</p>
                                    <p>Su rol no tiene permisos para crear nuevos ingresos.</p>
                                    <div class="alert alert-light">
                                        <small><i class="fas fa-info-circle"></i> Solo roles médicos pueden crear ingresos</small>
                                    </div>
                                </div>
                            `,
                                                icon: 'info',
                                                confirmButtonColor: '#6c757d'
                                            });
                                        }
                                    }

                                } else {
                                    Swal.fire('Error', response.message ||
                                        'No se pudo verificar el estado de los casos', 'error');
                                }
                            },
                            error: function(xhr, status, error) {
                                Swal.close();
                                console.error('Error AJAX:', error);
                                console.error('Response:', xhr.responseText);
                                Swal.fire('Error de conexión',
                                    'No se pudo conectar con el servidor', 'error');
                            }
                        });
                    });

                    // ✅ SCRIPT: Al hacer clic en el botón "Editar"
                    $(document).on('click', '.editar-btn', function() {
                        const id = $(this).data('id');
                        const numero_historia = $(this).data('numero_historia');
                        const cedula = $(this).data('cedula');
                        const nombres = $(this).data('nombres');
                        const apellidos = $(this).data('apellidos');
                        const sexo = $(this).data('sexo');
                        const fecha_nacimiento = $(this).data('fecha_nacimiento');
                        const telefono = $(this).data('telefono') || '';
                        const direccion = $(this).data('direccion') || '';

                        // Llenar todos los campos del formulario
                        $('#editarId').val(id);
                        $('#editarNumeroHistoria').val(numero_historia);
                        $('#editarCedula').val(cedula);
                        $('#editarNombres').val(nombres);
                        $('#editarApellidos').val(apellidos);
                        $('#editarSexo').val(sexo);
                        $('#editarFechaNacimiento').val(fecha_nacimiento);
                        $('#editarTelefono').val(telefono);
                        $('#editarDireccion').val(direccion);

                        // Mostrar el modal
                        $('#modalEditarPaciente').modal('show');
                    });

                    // ✅ SCRIPT: Guardar cambios al hacer clic en "Guardar"
                    $('#guardarPaciente').on('click', function() {
                        const id = $('#editarId').val();
                        const cedula = $('#editarCedula').val();
                        const nombres = $('#editarNombres').val();
                        const apellidos = $('#editarApellidos').val();
                        const sexo = $('#editarSexo').val();
                        const fecha_nacimiento = $('#editarFechaNacimiento').val();
                        const telefono = $('#editarTelefono').val();
                        const direccion = $('#editarDireccion').val();

                        // Validaciones básicas
                        if (!nombres || !apellidos || !cedula || !sexo) {
                            Swal.fire("❌ Error", "Por favor complete todos los campos obligatorios.", "error");
                            return;
                        }

                        const datos = new FormData();
                        datos.append('id_paciente', id);
                        datos.append('cedula', cedula);
                        datos.append('nombres', nombres);
                        datos.append('apellidos', apellidos);
                        datos.append('sexo', sexo);
                        datos.append('fecha_nacimiento', fecha_nacimiento);
                        datos.append('telefono', telefono);
                        datos.append('direccion', direccion);

                        fetch('actualizar_paciente.php', {
                                method: 'POST',
                                body: datos,
                            })
                            .then(res => res.text())
                            .then(response => {
                                if (response.trim() === 'ok') {
                                    Swal.fire("✅ Actualizado",
                                            "Los datos del paciente han sido actualizados correctamente.",
                                            "success")
                                        .then(() => location.reload());
                                } else {
                                    Swal.fire("❌ Error", "Ocurrió un error:\n" + response, "error");
                                }
                            })
                            .catch(() => {
                                Swal.fire("❌ Error de red", "No se pudo conectar con el servidor.",
                                    "error");
                            });
                    });

                    // Script para manejo de restricciones de sesión
                    document.addEventListener('DOMContentLoaded', function() {
                        // Variable con los permisos del usuario (generada por PHP)
                        const usuarioAutorizado =
                            <?php echo (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'dirección', 'medicina'])) ? 'true' : 'false'; ?>;

                        // Obtener todos los botones de editar
                        const editarBtns = document.querySelectorAll('.editar-btn');

                        // Aplicar restricciones a cada botón
                        editarBtns.forEach(btn => {
                            if (!usuarioAutorizado) {
                                // Cambiar apariencia del botón para usuarios sin permisos
                                btn.classList.remove('btn-warning');
                                btn.classList.add('btn-secondary');
                                btn.disabled = true;
                                btn.title = 'Sin permisos para editar';
                            }

                            // Event listener para el click
                            btn.addEventListener('click', function(e) {
                                if (!usuarioAutorizado) {
                                    e.preventDefault();
                                    e.stopPropagation();

                                    // Mostrar mensaje de error
                                    Swal.fire({
                                        icon: 'warning',
                                        title: 'Acceso Denegado',
                                        text: 'No tienes permisos para realizar esta acción',
                                        confirmButtonText: 'Entendido'
                                    });
                                    return false;
                                }

                                // Si tiene permisos, proceder con la edición
                                const datosElemento = {
                                    id: this.dataset.id,
                                    numero_historia: this.dataset.numero_historia,
                                    cedula: this.dataset.cedula,
                                    nombres: this.dataset.nombres,
                                    apellidos: this.dataset.apellidos,
                                    sexo: this.dataset.sexo,
                                    fecha_nacimiento: this.dataset.fecha_nacimiento,
                                    telefono: this.dataset.telefono,
                                    direccion: this.dataset.direccion
                                };

                                // Aquí va tu lógica para abrir el modal de edición
                                abrirModalEdicion(datosElemento);
                            });
                        });
                    });

                    // Función para abrir el modal de edición (ejemplo)
                    function abrirModalEdicion(datos) {
                        // Rellenar el formulario del modal con los datos
                        document.getElementById('edit_id_paciente').value = datos.id;
                        document.getElementById('edit_numero_historia').value = datos.numero_historia;
                        document.getElementById('edit_cedula').value = datos.cedula;
                        document.getElementById('edit_nombres').value = datos.nombres;
                        document.getElementById('edit_apellidos').value = datos.apellidos;
                        document.getElementById('edit_sexo').value = datos.sexo;
                        document.getElementById('edit_fecha_nacimiento').value = datos.fecha_nacimiento;
                        document.getElementById('edit_telefono').value = datos.telefono;
                        document.getElementById('edit_direccion').value = datos.direccion;

                        // Mostrar el modal
                        $('#editarPacienteModal').modal('show');
                    }
                    </script>


                    </script>

                    <!-- MODAL DE CONFIRMACIÓN DE CIERRE (Para agregar globalmente) -->
                    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow-lg">
                                <div class="modal-header bg-warning text-dark text-center border-0">
                                    <div class="w-100">
                                        <i class="fas fa-sign-out-alt fa-3x mb-3"></i>
                                        <h4 class="modal-title" id="logoutModalLabel">Cerrar Sesión</h4>
                                    </div>
                                </div>
                                <div class="modal-body text-center py-4">
                                    <div class="mb-3">
                                        <i class="fas fa-question-circle fa-4x text-warning mb-3"></i>
                                    </div>
                                    <h5 class="mb-3">¿Estás seguro que deseas cerrar sesión?</h5>
                                    <p class="text-muted mb-3">
                                        Se perderán los datos no guardados y tendrás que iniciar sesión nuevamente.
                                    </p>
                                    <div class="alert alert-light border">
                                        <i class="fas fa-user me-2"></i>
                                        <small>Sesión activa: <span
                                                class="fw-bold"><?php echo htmlspecialchars(($_SESSION['usuario_nombre'] ?? '') . ' ' . ($_SESSION['usuario_apellidos'] ?? '')); ?></span></small>
                                    </div>
                                </div>
                                <div class="modal-footer border-0 justify-content-center gap-3">
                                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                                        <i class="fas fa-times me-2"></i>Cancelar
                                    </button>
                                    <button type="button" class="btn btn-danger px-4" id="confirmLogout">
                                        <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Script para el modal de logout -->
                    <script>
                    // Cambiar el enlace de logout para abrir el modal
                    document.addEventListener('DOMContentLoaded', function() {
                        const logoutLink = document.querySelector('a[href="../logout.php"]');
                        if (logoutLink) {
                            logoutLink.setAttribute('href', '#');
                            logoutLink.setAttribute('data-bs-toggle', 'modal');
                            logoutLink.setAttribute('data-bs-target', '#logoutModal');
                        }

                        // Confirmar logout
                        document.getElementById('confirmLogout').addEventListener('click', function() {
                            window.location.href = '../../logout.php';
                        });
                    });
                    </script>

</body>

</html>