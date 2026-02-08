<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.html");
    exit();
}
include("../conexion.php");
// Función helper para manejar valores nulos

// Agregar este código al inicio de tu listar_personal.php, después de include("../conexion.php");

// Obtener foto del usuario actual
$foto_usuario = null;
$avatar_url = "assets/img/default-avatar.png"; // Imagen por defecto

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
            $ruta_foto = "uploads/fotos_personal/" . $foto_usuario;
            if (file_exists($ruta_foto)) {
                $avatar_url = $ruta_foto;
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
    <title>Lista de Personal </title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="../assets/img/kaiadmin/logoHlo(2).ico" type="image/x-icon" />
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.1/css/dataTables.dataTables.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Fonts and icons -->
    <script src="../assets/js/plugin/webfont/webfont.min.js"></script>
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
            urls: ["../assets/css/fonts.min.css"],
        },
        active: function() {
            sessionStorage.fonts = true;
        },
    });
    </script>
    <style>
    /* Fondo blanco completo */
    #tablaPersonal_wrapper {
        background-color: #ffffff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
    }

    /* Estilo para la tabla */
    #tablaPersonal {
        border: none;
        background-color: white;
        font-size: 14px;
    }

    /* Encabezado */
    #tablaPersonal thead th {
        background-color: #10104c;
        color: white;
        border: none;
        text-align: center;
    }

    /* Celdas */
    #tablaPersonal tbody td {
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
    </style>

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

    <!-- CSS Files -->
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../assets/css/plugins.min.css" />
    <link rel="stylesheet" href="../assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />
</head>

<body>
    <div class="wrapper">
        <!-- Modern Sidebar -->
        <!-- Sidebar CORREGIDO para listar_personal.php -->
        <div class="sidebar" data-background-color="white">
            <div class="sidebar-logo">
                <div class="logo-header d-flex justify-content-center align-items-center">
                    <a href="../index.php" class="logo">
                        <img src="../assets/img/kaiadmin/logohlo.png" alt="navbar brand" class="navbar-brand"
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
                                        <a href="../index.php">
                                            <span class="sub-item">Dashboard Principal</span>
                                        </a>
                                    </li>
                                    <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'dirección', 'medicina'])): ?>
                                    <li>
                                        <a href="../index.php?seccion=nuevo-ingreso">
                                            <span class="sub-item">Nuevo Ingreso</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'dirección', 'enfermería', 'medicina'])): ?>
                                    <li>
                                        <a href="../index.php?seccion=casos-activos">
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
                                        <a href="../index.php?seccion=buscar-paciente">
                                            <span class="sub-item">Buscar Pacientes</span>
                                        </a>
                                    </li>
                                    <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'dirección', 'medicina'])): ?>
                                    <li>
                                        <a href="../historia_medica.php">
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
                            <a href="../index.php?seccion=estadisticas">
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
                        <a href="../index.php" class="logo">
                            <img src="../assets/img/kaiadmin/logo_light.svg" alt="navbar brand" class="navbar-brand" />
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
                        <img src="../assets/img/IVSS.png" alt="navbar brand" class="navbar-brand" height="80" />
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
                                            onerror="this.src='assets/img/default-avatar.png';" />
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
                                                        onerror="this.src='assets/img/default-avatar.png';" />
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
                                            <a class="dropdown-item" href="../reportes/manual-usuario.pdf"
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

                        <h3 class="fw-bold mb-3">Personal Registrado</h3>
                        <ul class="breadcrumbs mb-3">
                            <li class="nav-home">
                                <a href="../index.php">
                                    <i class="icon-home"></i>
                                </a>
                            </li>
                            <li class="separator">
                                <i class="icon-arrow-right"></i>
                            </li>
                            <li class="nav-item">
                                <a href="listar_personal.php">Personal</a>
                            </li>

                        </ul>

                    </div>
                    <!-- AGREGA ESTO DESPUÉS DEL PAGE-HEADER EN listar_personal.php -->

                    <!-- Navegación entre Personal Activo e Inactivo -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="btn-group" role="group">
                                <a href="listar_personal.php" class="btn btn-success">
                                    <i class="fas fa-user-check me-1"></i>Personal Activo
                                    <?php
                                    $count_activo = $conn->query("SELECT COUNT(*) as total FROM personal WHERE activo = 1")->fetch_assoc()['total'];
                                    echo "<span class='badge bg-light text-dark ms-1'>{$count_activo}</span>";
                                    ?>
                                </a>
                                <a href="Personal-inactivo.php" class="btn btn-outline-danger">
                                    <i class="fas fa-user-slash me-1"></i>Personal Inactivo
                                    <?php
                                    $count_inactivo = $conn->query("SELECT COUNT(*) as total FROM personal WHERE activo = 0")->fetch_assoc()['total'];
                                    if ($count_inactivo > 0) {
                                        echo "<span class='badge bg-danger ms-1'>{$count_inactivo}</span>";
                                    }
                                    ?>
                                </a>
                            </div>

                            <!-- Información adicional -->

                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">
                                        <i class="fas fa-user"></i> Lista de Personal Activo
                                    </div>

                                </div>

                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="tablaPersonal" class="display responsive nowrap" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>Foto</th>
                                                    <th>Nombres</th>
                                                    <th>Apellidos</th>
                                                    <th>Cédula</th>
                                                    <th>Email</th>
                                                    <th>Teléfono</th>
                                                    <th>Dirección</th>
                                                    <th>Rol</th>
                                                    <th>Especialidad</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // ✅ CONSULTA MODIFICADA: Solo personal activo
                                                $query = "SELECT id, nombres, apellidos, cedula, email, cod_area, numero, rol, foto, especialidad, direccion FROM personal WHERE activo = 1";
                                                $resultado = $conn->query($query);

                                                while ($row = $resultado->fetch_assoc()) {

                                                    // CÓDIGO CORREGIDO PARA MOSTRAR FOTOS
                                                    if (!empty($row['foto'])) {
                                                        $ruta_foto = "uploads/fotos_personal/" . $row['foto'];
                                                        if (file_exists($ruta_foto)) {
                                                            $imagenHTML = "<img src='{$ruta_foto}' style='width:50px;height:50px;object-fit:cover;border-radius:50%;border:2px solid #10104c;'>";
                                                        } else {
                                                            $imagenHTML = "<div style='width:50px;height:50px;background:#f8f9fa;border:2px solid #ddd;border-radius:50%;display:flex;align-items:center;justify-content:center;'>
            <i class='fas fa-user text-muted'></i>
        </div>";
                                                        }
                                                    } else {
                                                        $imagenHTML = "<div style='width:50px;height:50px;background:#f8f9fa;border:2px solid #ddd;border-radius:50%;display:flex;align-items:center;justify-content:center;'>
        <i class='fas fa-user text-muted'></i>
    </div>";
                                                    }

                                                    // Escapar comillas para evitar problemas en JavaScript
                                                    $nombres = safe_escape($row['nombres']);
                                                    $apellidos = safe_escape($row['apellidos']);
                                                    $email = safe_escape($row['email']);
                                                    $especialidad = safe_escape($row['especialidad']);
                                                    $direccion = safe_escape($row['direccion']);
                                                    $foto = safe_escape($row['foto']);
                                                    $cod_area = safe_escape($row['cod_area']);
                                                    $numero = safe_escape($row['numero']);

                                                    // Manejar teléfono
                                                    $telefono_display = '';
                                                    if (!empty($row['cod_area']) && !empty($row['numero'])) {
                                                        $telefono_display = $row['cod_area'] . '-' . $row['numero'];
                                                    } elseif (!empty($row['numero'])) {
                                                        $telefono_display = $row['numero'];
                                                    } else {
                                                        $telefono_display = 'N/A';
                                                    }

                                                    echo "<tr>
    <td>$imagenHTML</td>
    <td>{$nombres}</td>
    <td>{$apellidos}</td>
    <td>{$row['cedula']}</td>
    <td>{$email}</td>
    <td>{$telefono_display}</td>
    <td>{$direccion}</td>
    <td>{$row['rol']}</td>
    <td>{$especialidad}</td>
    <td>
       <button class='btn btn-sm btn-warning editar-btn'
            data-id='{$row['id']}'
            data-nombres='{$nombres}'
            data-apellidos='{$apellidos}'
            data-cedula='{$row['cedula']}'
            data-email='{$email}'
            data-cod_area='{$cod_area}'
            data-numero='{$numero}'
            data-rol='{$row['rol']}'
            data-especialidad='{$especialidad}'
            data-direccion='{$direccion}'
            data-foto='{$foto}'
            title='Editar personal'>
            <i class='fas fa-pen'></i>
        </button>
        
        <button class='btn btn-sm btn-danger eliminar-btn' 
            data-id='{$row['id']}'
            data-nombres='{$nombres}'
            data-apellidos='{$apellidos}'
            data-rol='{$row['rol']}'
            data-especialidad='{$especialidad}'
            title='Desactivar personal'>
            <i class='fas fa-user-slash'></i>
        </button>
    </td>
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
                    <!-- MODAL DE EDICIÓN DE PERSONAL - ACTUALIZADO -->
                    <div class="modal fade" id="modalEditarPersonal" tabindex="-1" aria-labelledby="modalEditarLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <!-- ENCABEZADO -->
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title w-100 text-center" id="modalEditarLabel">Editar Personal</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Cerrar"></button>
                                </div>

                                <!-- CUERPO -->
                                <div class="modal-body">
                                    <form id="formEditarPersonal" enctype="multipart/form-data">
                                        <input type="hidden" id="editarId" name="id">
                                        <input type="hidden" id="fotoActual" name="foto_actual">
                                        <div class="row justify-content-center">

                                            <!-- FOTO CARNET -->
                                            <div class="col-12 mb-4 text-center">
                                                <label class="mb-2 d-block fw-bold">Foto Carnet</label>
                                                <label for="editarFoto" id="fotoPreview"
                                                    class="d-inline-block bg-light border border-dark rounded-circle overflow-hidden"
                                                    style="width:90px;height:90px;cursor:pointer;">
                                                    <i class="fa fa-user fa-5x text-primary d-flex align-items-center justify-content-center"
                                                        style="width:100%;height:100%;display:flex;"></i>
                                                </label>
                                                <div>
                                                    <label for="editarFoto"
                                                        style="font-size:50px !important; cursor:pointer; color:#10104c;">+</label>
                                                </div>
                                                <input type="file" accept="image/*" id="editarFoto" name="foto"
                                                    style="display: none;" onchange="mostrarFoto(event)" />
                                            </div>

                                            <!-- FILA 1: NOMBRES Y APELLIDOS -->
                                            <div class="col-md-6 mb-3">
                                                <label for="editarNombres" class="form-label">Nombres</label>
                                                <input type="text" class="form-control" id="editarNombres"
                                                    name="nombres" placeholder="Ingrese nombres" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="editarApellidos" class="form-label">Apellidos</label>
                                                <input type="text" class="form-control" id="editarApellidos"
                                                    name="apellidos" placeholder="Ingrese apellidos" required>
                                            </div>

                                            <!-- FILA 2: CÉDULA Y CORREO -->
                                            <div class="col-md-6 mb-3">
                                                <label for="editarCedula" class="form-label">Cédula de Identidad</label>
                                                <div class="input-group">

                                                    <input type="text" class="form-control" id="editarCedula"
                                                        name="cedula" placeholder="Ingrese número" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="editarEmail" class="form-label">Correo Electrónico</label>
                                                <input type="email" class="form-control" id="editarEmail" name="email"
                                                    placeholder="correo@ejemplo.com" required>
                                            </div>

                                            <!-- FILA 3: TELÉFONO Y DIRECCIÓN -->
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Teléfono</label>
                                                <div class="input-group">
                                                    <select class="form-select" id="editarCodArea" name="cod_area"
                                                        style="max-width: 100px;">
                                                        <option value="">Cod</option>
                                                        <option value="0414">0414</option>
                                                        <option value="0424">0424</option>
                                                        <option value="0412">0412</option>
                                                        <option value="0416">0416</option>
                                                        <option value="0426">0426</option>
                                                    </select>
                                                    <input type="text" class="form-control" id="editarTelefono"
                                                        name="numero" placeholder="Número" maxlength="7" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="editarDireccion" class="form-label">Dirección</label>
                                                <input type="text" class="form-control" id="editarDireccion"
                                                    name="direccion" placeholder="Dirección completa" required>
                                            </div>

                                            <!-- FILA 4: ROL Y ESPECIALIDAD -->
                                            <div class="col-md-6 mb-3">
                                                <label for="editarRol" class="form-label">Rol</label>
                                                <select class="form-select" id="editarRol" name="rol" required>
                                                    <option value="" disabled>Seleccione rol</option>
                                                    <option value="Medicina">Medicina</option>
                                                    <option value="Enfermería">Enfermería</option>
                                                    <option value="Dirección">Dirección</option>

                                                    <option value="Estadística en Salud">Estadística en Salud</option>
                                                    <option value="Administrador">Administrador</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="editarEspecialidad" class="form-label">Especialidad</label>
                                                <select class="form-select" id="editarEspecialidad" name="especialidad"
                                                    required>
                                                    <option value="" disabled>Seleccione especialidad</option>
                                                    <option value="Médico cirujano">Médico cirujano</option>
                                                    <option value="Médico traumatólogo">Médico traumatólogo</option>
                                                    <option value="Médico interno">Médico interno</option>
                                                    <option value="Médico residente">Médico residente</option>
                                                    <option value="Enfermera Obstétrica">Enfermera Obstétrica</option>
                                                    <option value="Enfermera shock">Enfermera shock</option>
                                                    <option value="Enfermería Nebulización">Enfermería Nebulización
                                                    </option>
                                                    <option value="Medicina General">Médico General</option>

                                                    <option value="Cardiología">Cardiología</option>
                                                    <option value="Neurología">Neurología</option>
                                                    <option value="Pediatría">Pediatría</option>
                                                    <option value="Ginecología">Ginecología</option>
                                                    <option value="Traumatología">Traumatología</option>
                                                    <option value="Enfermería General">Enfermería General</option>
                                                    <option value="Enfermería de Emergencias">Enfermería de Emergencias
                                                    </option>
                                                    <option value="Enfermería Quirúrgica">Enfermería Quirúrgica</option>
                                                    <option value="Administración">Administración</option>
                                                    <option value="Estadística en Salud">Estadística en Salud</option>
                                                    <option value="Dirección Médica">Dirección Médica</option>
                                                    <option value="Otra">Otra</option>
                                                </select>
                                            </div>

                                            <!-- FILA 5: CONTRASEÑA (OPCIONAL) -->
                                            <div class="col-md-6 mb-3">
                                                <label for="editarContrasena" class="form-label">Contraseña
                                                    (opcional)</label>
                                                <input type="password" class="form-control" id="editarContrasena"
                                                    name="contrasena" placeholder="********">
                                                <small class="text-muted">Deje en blanco para mantener la contraseña
                                                    actual</small>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="editarConfirmarContrasena" class="form-label">Confirmar
                                                    Contraseña</label>
                                                <input type="password" class="form-control"
                                                    id="editarConfirmarContrasena" name="confirmar_contrasena"
                                                    placeholder="********">
                                                <div id="edit-password-match-message" class="mt-1"></div>
                                            </div>

                                        </div>
                                    </form>
                                </div>

                                <!-- PIE DEL MODAL -->
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        <i class="fas fa-times"></i> Cancelar
                                    </button>
                                    <button type="button" class="btn btn-primary" id="guardarPersonal">
                                        <i class="fas fa-save"></i> Guardar cambios
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!--   Core JS Files   -->
                    <script src="../assets/js/core/jquery-3.7.1.min.js"></script>
                    <script src="../assets/js/core/popper.min.js"></script>
                    <script src="../assets/js/core/bootstrap.min.js"></script>

                    <!-- jQuery Scrollbar -->
                    <script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

                    <!-- Chart JS -->
                    <script src="../assets/js/plugin/chart.js/chart.min.js"></script>

                    <!-- jQuery Sparkline -->
                    <script src="../assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js"></script>

                    <!-- Chart Circle -->
                    <script src="../assets/js/plugin/chart-circle/circles.min.js"></script>

                    <!-- Datatables -->
                    <script src="../assets/js/plugin/datatables/datatables.min.js"></script>

                    <!-- Bootstrap Notify -->
                    <script src="../assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

                    <!-- jQuery Vector Maps -->
                    <script src="../assets/js/plugin/jsvectormap/jsvectormap.min.js"></script>
                    <script src="../assets/js/plugin/jsvectormap/world.js"></script>

                    <!-- Google Maps Plugin -->
                    <script src="../assets/js/plugin/gmaps/gmaps.js"></script>

                    <!-- Sweet Alert -->
                    <script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>

                    <!-- Kaiadmin JS -->
                    <script src="../assets/js/kaiadmin.min.js"></script>

                    <!-- Kaiadmin DEMO methods -->
                    <script src="../assets/js/setting-demo2.js"></script>

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
                        $('#tablaPersonal').DataTable({
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
                    });

                    // ✅ FUNCIÓN: Mostrar preview de foto
                    function mostrarFoto(event) {
                        const input = event.target;
                        const preview = document.getElementById("fotoPreview");

                        if (input.files && input.files[0]) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                preview.innerHTML = `<img src="${e.target.result}" 
              style="width:100%;height:100%;object-fit:cover;" 
              class="rounded-circle" />`;
                            };
                            reader.readAsDataURL(input.files[0]);
                        }
                    }

                    // ✅ VALIDACIÓN DE CONTRASEÑAS EN TIEMPO REAL (MODAL EDITAR)
                    document.getElementById('editarConfirmarContrasena').addEventListener('input', function() {
                        checkEditPasswordMatch();
                    });

                    document.getElementById('editarContrasena').addEventListener('input', function() {
                        checkEditPasswordMatch();
                    });

                    // Verificar que las contraseñas coincidan en edición
                    function checkEditPasswordMatch() {
                        const password = document.getElementById('editarContrasena').value;
                        const confirmPassword = document.getElementById('editarConfirmarContrasena').value;
                        const messageDiv = document.getElementById('edit-password-match-message');

                        if (confirmPassword === '' && password === '') {
                            messageDiv.innerHTML = '';
                            return;
                        }

                        if (password === confirmPassword) {
                            messageDiv.innerHTML =
                                '<small class="text-success"><i class="fas fa-check"></i> Las contraseñas coinciden</small>';
                        } else {
                            messageDiv.innerHTML =
                                '<small class="text-danger"><i class="fas fa-times"></i> Las contraseñas no coinciden</small>';
                        }
                    }

                    // ✅ VALIDACIÓN SOLO NÚMEROS EN CÉDULA Y TELÉFONO
                    document.getElementById('editarCedula').addEventListener('input', function(e) {
                        this.value = this.value.replace(/[^0-9]/g, '');
                    });

                    document.getElementById('editarTelefono').addEventListener('input', function(e) {
                        this.value = this.value.replace(/[^0-9]/g, '');
                    });

                    // ✅ SCRIPT: Al hacer clic en el botón "Editar"
                    $(document).on('click', '.editar-btn', function() {
                        const id = $(this).data('id');
                        const nombres = $(this).data('nombres');
                        const apellidos = $(this).data('apellidos');
                        const cedula = $(this).data('cedula');
                        const email = $(this).data('email');
                        const cod_area = $(this).data('cod_area') || '';
                        const numero = $(this).data('numero') || '';
                        const rol = $(this).data('rol');
                        const foto = $(this).data('foto') || '';
                        const especialidad = $(this).data('especialidad') || '';
                        const direccion = $(this).data('direccion') || '';

                        // Llenar todos los campos del formulario
                        $('#editarId').val(id);
                        $('#editarNombres').val(nombres);
                        $('#editarApellidos').val(apellidos);
                        $('#editarCedula').val(cedula);
                        $('#editarEmail').val(email);
                        $('#editarCodArea').val(cod_area);
                        $('#editarTelefono').val(numero);
                        $('#editarRol').val(rol);
                        $('#editarEspecialidad').val(especialidad);
                        $('#editarDireccion').val(direccion);
                        $('#fotoActual').val(foto);

                        // Limpiar contraseñas
                        $('#editarContrasena').val('');
                        $('#editarConfirmarContrasena').val('');
                        $('#edit-password-match-message').html('');

                        // Mostrar foto actual o placeholder
                        if (foto && foto !== "" && foto !== "null") {
                            $('#fotoPreview').html(
                                `<img src="uploads/fotos_personal/${foto}" class="rounded-circle" style="width:100%;height:100%;object-fit:cover;" />`
                            );
                        } else {
                            $('#fotoPreview').html(
                                `<i class="fa fa-user fa-5x text-primary d-flex align-items-center justify-content-center" style="width:100%;height:100%;display:flex;"></i>`
                            );
                        }

                        // Mostrar el modal
                        $('#modalEditarPersonal').modal('show');
                    });

                    // ✅ SCRIPT: Guardar cambios al hacer clic en "Guardar"
                    $('#guardarPersonal').on('click', function() {
                        const id = $('#editarId').val();
                        const nombres = $('#editarNombres').val();
                        const apellidos = $('#editarApellidos').val();
                        const cedula = $('#editarCedula').val();
                        const email = $('#editarEmail').val();
                        const cod_area = $('#editarCodArea').val();
                        const numero = $('#editarTelefono').val();
                        const rol = $('#editarRol').val();
                        const especialidad = $('#editarEspecialidad').val();
                        const direccion = $('#editarDireccion').val();
                        const contrasena = $('#editarContrasena').val();
                        const confirmarContrasena = $('#editarConfirmarContrasena').val();
                        const foto = $('#editarFoto')[0].files[0];
                        const fotoActual = $('#fotoActual').val();

                        // Validaciones básicas
                        if (!nombres || !apellidos || !cedula || !email || !rol || !especialidad || !
                            direccion) {
                            Swal.fire("❌ Error", "Por favor complete todos los campos obligatorios.", "error");
                            return;
                        }

                        // Validar cédula (7-8 dígitos)
                        // Limpiar espacios y caracteres extra
                        const cedulaLimpia = cedula.trim().replace(/[^0-9]/g, '');

                        // Validar cédula (7-8 dígitos)
                        if (!/^\d{7,8}$/.test(cedulaLimpia)) {
                            Swal.fire("❌ Error", "La cédula debe tener entre 7 y 8 dígitos", "error");
                            return;
                        }

                        // Validar teléfono (7 dígitos)
                        if (numero && !/^\d{7}$/.test(numero)) {
                            Swal.fire("❌ Error", "El número de teléfono debe tener exactamente 7 dígitos",
                                "error");
                            return;
                        }

                        // Validar contraseñas si se proporcionaron
                        if (contrasena !== '' || confirmarContrasena !== '') {
                            if (contrasena !== confirmarContrasena) {
                                Swal.fire("❌ Error", "Las contraseñas no coinciden", "error");
                                return;
                            }
                            if (contrasena.length < 4) {
                                Swal.fire("❌ Error", "La contraseña debe tener al menos 4 caracteres", "error");
                                return;
                            }
                        }

                        const datos = new FormData();
                        datos.append('id', id);
                        datos.append('nombres', nombres);
                        datos.append('apellidos', apellidos);
                        datos.append('cedula', cedulaLimpia);
                        datos.append('email', email);
                        datos.append('cod_area', cod_area);
                        datos.append('numero', numero);
                        datos.append('rol', rol);
                        datos.append('especialidad', especialidad);
                        datos.append('direccion', direccion);
                        if (contrasena !== '') {
                            datos.append('contrasena', contrasena);
                        }
                        datos.append('foto_actual', fotoActual);
                        if (foto) {
                            datos.append('foto', foto);
                        }

                        // Mostrar loading
                        Swal.fire({
                            title: 'Actualizando...',
                            text: 'Por favor espere',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        fetch('actualizar_personal.php', {
                                method: 'POST',
                                body: datos,
                            })
                            .then(res => res.text())
                            .then(response => {
                                if (response.trim() === 'ok') {
                                    Swal.fire("✅ Actualizado",
                                            "Los datos han sido actualizados correctamente.", "success")
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

                    // ✅ SCRIPT: Confirmar y desactivar personal (versión actualizada)
                    $(document).on('click', '.eliminar-btn', function() {
                        const id = $(this).data('id');
                        const nombres = $(this).data('nombres') || 'Nombre no disponible';
                        const apellidos = $(this).data('apellidos') || 'Apellido no disponible';
                        const rol = $(this).data('rol') || 'Rol no disponible';
                        const especialidad = $(this).data('especialidad') || 'Especialidad no disponible';

                        Swal.fire({
                            title: '¿Desactivar Personal?',
                            html: `
            <div class="text-start">
                <p>¿Desea <strong>desactivar</strong> a:</p>
                <div class="alert alert-info">
                    <i class="fas fa-user-circle"></i> <strong>${nombres} ${apellidos}</strong><br>
                    <small><i class="fas fa-briefcase"></i> Rol: ${rol}</small><br>
                    <small><i class="fas fa-stethoscope"></i> Especialidad: ${especialidad}</small>
                </div>
                <div class="alert alert-warning">
                    <strong>⚠️ Importante - Desactivación (Soft Delete):</strong><br>
                    • Se verificarán las dependencias médicas activas<br>
                    • Si tiene casos activos, no se podrá desactivar<br>
                    • Los registros históricos se mantienen intactos<br>
                    • No podrá acceder al sistema hasta ser reactivado<br>
                    • Puede ser reactivado cuando sea necesario
                </div>
                <div class="alert alert-success">
                    <strong>✅ Ventajas del Soft Delete:</strong><br>
                    • Preserva la integridad de los datos<br>
                    • Mantiene el historial médico completo<br>
                    • Permite auditorías futuras<br>
                    • Reversible en caso necesario
                </div>
            </div>
        `,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#f39c12', // Color naranja en lugar de rojo
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: '<i class="fas fa-user-slash"></i> Proceder con Desactivación',
                            cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
                            showLoaderOnConfirm: true,
                            width: '600px',
                            customClass: {
                                popup: 'swal2-popup-custom'
                            },
                            preConfirm: () => {
                                return new Promise((resolve) => {
                                    // Simular tiempo de procesamiento
                                    setTimeout(() => {
                                        resolve();
                                    }, 1200);
                                });
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Mostrar alerta de procesamiento con información más específica
                                Swal.fire({
                                    title: '<i class="fas fa-cogs"></i> Verificando Dependencias...',
                                    html: `
                    <div class="text-center">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p><strong>Analizando registros médicos de:</strong></p>
                        <div class="alert alert-light">
                            <strong>${nombres} ${apellidos}</strong><br>
                            <small>${rol} - ${especialidad}</small>
                        </div>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex align-items-center">
                                <i class="fas fa-hospital-user me-2 text-info"></i>
                                Casos clínicos activos...
                            </div>
                            <div class="list-group-item d-flex align-items-center">
                                <i class="fas fa-prescription-bottle-alt me-2 text-warning"></i>
                                Órdenes médicas pendientes...
                            </div>
                            <div class="list-group-item d-flex align-items-center">
                                <i class="fas fa-user-md me-2 text-success"></i>
                                Interconsultas activas...
                            </div>
                            <div class="list-group-item d-flex align-items-center">
                                <i class="fas fa-shield-alt me-2 text-danger"></i>
                                Permisos de administración...
                            </div>
                        </div>
                    </div>
                `,
                                    allowOutsideClick: false,
                                    showConfirmButton: false,
                                    width: '500px',
                                    didOpen: () => {
                                        // Simular progreso de verificación
                                        const items = document.querySelectorAll(
                                            '.list-group-item i');
                                        items.forEach((item, index) => {
                                            setTimeout(() => {
                                                item.className = item
                                                    .className.replace(
                                                        /text-\w+/,
                                                        'text-success');
                                                item.innerHTML =
                                                    '<i class="fas fa-check"></i>';
                                            }, (index + 1) * 300);
                                        });
                                    }
                                });

                                // Redirigir después del análisis visual
                                setTimeout(() => {
                                    window.location.href = `eliminar_personal.php?id=${id}`;
                                }, 2000);
                            }
                        });
                    });

                    // ✅ OPCIONAL: Añadir CSS personalizado para mejorar la apariencia
                    const style = document.createElement('style');
                    style.textContent = `
    .swal2-popup-custom {
        font-family: 'Public Sans', sans-serif;
    }
    
    .swal2-popup-custom .alert {
        border-radius: 8px;
        margin-bottom: 15px;
    }
    
    .swal2-popup-custom .alert-info {
        background-color: #e7f3ff;
        border-color: #b3d9ff;
        color: #004085;
    }
    
    .swal2-popup-custom .alert-warning {
        background-color: #fff8e1;
        border-color: #ffecb3;
        color: #8a6d3b;
    }
    
    .swal2-popup-custom .alert-success {
        background-color: #e8f5e8;
        border-color: #c3e6c3;
        color: #3c763d;
    }
    
    .list-group-item {
        border: none;
        padding: 8px 15px;
        background-color: transparent;
    }
    
    .spinner-border {
        width: 2rem;
        height: 2rem;
    }
`;
                    document.head.appendChild(style);
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
                            window.location.href = '../logout.php';
                        });
                    });
                    </script>

</body>

</html>