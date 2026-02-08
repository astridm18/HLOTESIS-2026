<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.html");
    exit();
}
include("../../conexion.php");

// Obtener foto del usuario actual
$foto_usuario = null;
$avatar_url = "../../assets/img/default-avatar.png";

if (isset($_SESSION['usuario_id'])) {
    $query_foto = "SELECT foto FROM personal WHERE id = ?";
    $stmt_foto = $conn->prepare($query_foto);
    $stmt_foto->bind_param("i", $_SESSION['usuario_id']);
    $stmt_foto->execute();
    $result_foto = $stmt_foto->get_result();

    if ($usuario_data = $result_foto->fetch_assoc()) {
        $foto_usuario = $usuario_data['foto'];
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
    <title>Personal Inactivo</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="../../assets/img/kaiadmin/logoHlo(2).ico" type="image/x-icon" />

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Fonts and icons -->
    <script src="../../assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
    WebFont.load({
        google: {
            families: ["Public Sans:300,400,500,600,700"]
        },
        custom: {
            families: ["Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands",
                "simple-line-icons"
            ],
            urls: ["../../assets/css/fonts.min.css"],
        },
        active: function() {
            sessionStorage.fonts = true;
        },
    });
    </script>

    <style>
    /* Estilos personalizados para personal inactivo */
    #tablaPersonalInactivo_wrapper {
        background-color: #ffffff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        border-left: 5px solid #e74c3c;
    }

    #tablaPersonalInactivo {
        border: none;
        background-color: white;
        font-size: 14px;
    }

    #tablaPersonalInactivo thead th {
        background-color: #10104c;
        color: white;
        border: none;
        text-align: center;
    }

    #tablaPersonalInactivo tbody td {
        vertical-align: middle;
        border-bottom: 1px solid #eaeaea;
        opacity: 0.8;
    }

    .dt-buttons .btn {
        background-color: #e74c3c !important;
        color: white !important;
        border-radius: 8px;
        margin-right: 8px;
        padding: 6px 12px;
        font-size: 13px;
        border: none;
    }

    .badge-inactive {
        background-color: #e74c3c;
        color: white;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
    }

    .card-header-inactive {
        color: white;
    }
    </style>

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
                </div>
                <!-- Navbar Header (igual que el original) -->
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
                                                        alt="Foto de perfil" class="avatar-img rounded"
                                                        style="width: 60px; height: 60px; object-fit: cover;" />
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
            </div>

            <div class="container">
                <div class="page-inner">
                    <div class="page-header">
                        <h3 class="fw-bold mb-3">
                            <i class=""></i>Personal Registrado
                        </h3>
                        <ul class="breadcrumbs mb-3">
                            <li class="nav-home">
                                <a href="../../index.php"><i class="icon-home"></i></a>
                            </li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="listar_personal.php">Personal</a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="personal_inactivo.php">Personal Inactivo</a></li>
                        </ul>
                    </div>

                    <!-- Navegación entre Personal Activo e Inactivo -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="btn-group" role="group">
                                <a href="listar_personal.php" class="btn btn-outline-success">
                                    <i class="fas fa-user-check me-1"></i>Personal Activo
                                </a>
                                <a href="personal_inactivo.php" class="btn btn-danger">
                                    <i class="fas fa-user-slash me-1"></i>Personal Inactivo
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header card-header-inactive">
                                    <div class="d-flex align-items-center">
                                        <div class="card-title">
                                            <i class="fas fa-user-times me-2"></i>Lista de Personal Inactivo
                                        </div>
                                        <div class="ms-auto">
                                            <span class="badge badge-inactive">
                                                <i class="fas fa-exclamation-triangle me-1"></i>Inactivos
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-body">
                                    <?php
                                    // Contar personal inactivo
                                    $count_query = "SELECT COUNT(*) as total FROM personal WHERE activo = 0";
                                    $count_result = $conn->query($count_query);
                                    $total_inactivos = $count_result->fetch_assoc()['total'];
                                    ?>

                                    <?php if ($total_inactivos == 0): ?>
                                    <div class="alert alert-success text-center">
                                        <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                                        <h4>¡Excelente!</h4>
                                        <p class="mb-0">No hay personal desactivado en el sistema.</p>
                                        <a href="listar_personal.php" class="btn btn-success mt-2">
                                            <i class="fas fa-users me-1"></i>Ver Personal Activo
                                        </a>
                                    </div>
                                    <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong><?php echo $total_inactivos; ?></strong> miembro(s) del personal
                                        desactivado(s).
                                        Puede reactivarlos cuando sea necesario.
                                    </div>

                                    <div class="table-responsive">
                                        <table id="tablaPersonalInactivo" class="display responsive nowrap"
                                            style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>Foto</th>
                                                    <th>Nombres</th>
                                                    <th>Apellidos</th>
                                                    <th>Cédula</th>
                                                    <th>Email</th>
                                                    <th>Rol</th>
                                                    <th>Especialidad</th>
                                                    <th>Estado</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                    // ✅ CONSULTA: Solo personal inactivo
                                                    $query = "SELECT id, nombres, apellidos, cedula, email, cod_area, numero, rol, foto, especialidad, direccion FROM personal WHERE activo = 0 ORDER BY apellidos, nombres";
                                                    $resultado = $conn->query($query);

                                                    while ($row = $resultado->fetch_assoc()) {
                                                        // Mostrar foto o placeholder
                                                        if (!empty($row['foto'])) {
                                                            $ruta_foto_rel = "../../uploads/fotos_personal/" . $row['foto']; $ruta_foto_abs = __DIR__ . "/../../uploads/fotos_personal/" . $row['foto']; if (file_exists($ruta_foto_abs)) { $imagenHTML = "$ruta_foto_rel";
                                                            } else {
                                                                $imagenHTML = "<div style='width:50px;height:50px;background:#f8f9fa;border:2px solid #e74c3c;border-radius:50%;display:flex;align-items:center;justify-content:center;opacity:0.7;'>
                                                                    <i class='fas fa-user text-muted'></i>
                                                                </div>";
                                                            }
                                                        } else {
                                                            $imagenHTML = "<div style='width:50px;height:50px;background:#f8f9fa;border:2px solid #e74c3c;border-radius:50%;display:flex;align-items:center;justify-content:center;opacity:0.7;'>
                                                                <i class='fas fa-user text-muted'></i>
                                                            </div>";
                                                        }

                                                        // Escapar datos
                                                        $nombres = safe_escape($row['nombres']);
                                                        $apellidos = safe_escape($row['apellidos']);
                                                        $email = safe_escape($row['email']);
                                                        $especialidad = safe_escape($row['especialidad']);
                                                        $rol = safe_escape($row['rol']);

                                                        echo "<tr>
                                                            <td>{$imagenHTML}</td>
                                                            <td>{$nombres}</td>
                                                            <td>{$apellidos}</td>
                                                            <td>{$row['cedula']}</td>
                                                            <td>{$email}</td>
                                                            <td><span class='badge bg-secondary'>{$rol}</span></td>
                                                            <td>{$especialidad}</td>
                                                            <td><span class='badge badge-inactive'><i class='fas fa-times me-1'></i>Inactivo</span></td>
                                                            <td>
                                                                <button class='btn btn-sm btn-success reactivar-btn'
                                                                    data-id='{$row['id']}'
                                                                    data-nombres='{$nombres}'
                                                                    data-apellidos='{$apellidos}'
                                                                    data-rol='{$rol}'
                                                                    data-especialidad='{$especialidad}'
                                                                    title='Reactivar personal'>
                                                                    <i class='fas fa-user-check'></i> Reactivar
                                                                </button>
                                                            </td>
                                                        </tr>";
                                                    }
                                                    ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- JavaScript Libraries -->
                    <script src="../../assets/js/core/jquery-3.7.1.min.js"></script>
                    <script src="../../assets/js/core/popper.min.js"></script>
                    <script src="../../assets/js/core/bootstrap.min.js"></script>
                    <script src="../../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
                    <script src="../../assets/js/plugin/datatables/datatables.min.js"></script>
                    <script src="../../assets/js/kaiadmin.min.js"></script>

                    <!-- DataTables JS -->
                    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
                    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
                    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
                    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
                    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

                    <script>
                    $(document).ready(function() {
                        // Inicializar DataTable para personal inactivo
                        $('#tablaPersonalInactivo').DataTable({
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
                        });

                        // ✅ SCRIPT: Reactivar personal
                        $(document).on('click', '.reactivar-btn', function() {
                            const id = $(this).data('id');
                            const nombres = $(this).data('nombres');
                            const apellidos = $(this).data('apellidos');
                            const rol = $(this).data('rol');
                            const especialidad = $(this).data('especialidad');

                            Swal.fire({
                                title: '¿Reactivar Personal?',
                                html: `
                                    <div class="text-start">
                                        <p>¿Desea <strong>reactivar</strong> a:</p>
                                        <div class="alert alert-info">
                                            <i class="fas fa-user-circle"></i> <strong>${nombres} ${apellidos}</strong><br>
                                            <small><i class="fas fa-briefcase"></i> Rol: ${rol}</small><br>
                                            <small><i class="fas fa-stethoscope"></i> Especialidad: ${especialidad}</small>
                                        </div>
                                        <div class="alert alert-success">
                                            <strong>✅ Al reactivar:</strong><br>
                                            • Podrá acceder nuevamente al sistema<br>
                                            • Aparecerá en las listas de personal activo<br>
                                            • Podrá ser asignado a nuevos casos<br>
                                            • Mantendrá todo su historial previo
                                        </div>
                                    </div>
                                `,
                                icon: 'question',
                                showCancelButton: true,
                                confirmButtonColor: '#27ae60',
                                cancelButtonColor: '#6c757d',
                                confirmButtonText: '<i class="fas fa-user-check"></i> Sí, Reactivar',
                                cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
                                width: '500px'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Mostrar loading
                                    Swal.fire({
                                        title: '<i class="fas fa-user-check"></i> Reactivando Personal...',
                                        html: `<p>Reactivando a <strong>${nombres} ${apellidos}</strong></p>`,
                                        allowOutsideClick: false,
                                        didOpen: () => {
                                            Swal.showLoading();
                                        }
                                    });

                                    // Redirigir a script de reactivación
                                    setTimeout(() => {
                                        window.location.href =
                                            `reactivar_personal.php?id=${id}`;
                                    }, 1000);
                                }
                            });
                        });
                    });
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