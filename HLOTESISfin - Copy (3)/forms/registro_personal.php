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
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Registro Personal</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="../assets/img/kaiadmin/logoHlo(2).ico" type="image/x-icon" />
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

    <!-- CSS Files -->
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../assets/css/plugins.min.css" />
    <link rel="stylesheet" href="../assets/css/kaiadmin.min.css" />

    <!-- CSS Just for demo purpose, don't include it in your project -->
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
                                                        <?php echo htmlspecialchars($_SESSION['rol'] ?? 'email@ejemplo.com'); ?>
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
                        <h3 class="fw-bold mb-3">Registro Personal</h3>
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
                                <a href="registro_personal.php">Registro</a>
                            </li>
                        </ul>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Registro Personal - ADMIN</div>
                                </div>

                                <!-- FORMULARIO REORGANIZADO -->
                                <div class="card-body">
                                    <form action="procesar_registro_personal.php" method="POST" enctype="multipart/form-data">
                                        <div class="row justify-content-center">

                                            <!-- FOTO -->
                                            <div class="col-12 mb-4 text-center">
                                                <label class="mb-2 d-block">Foto Carnet</label>
                                                <label for="foto" id="fotoPreview"
                                                    class="d-inline-block bg-light border border-dark rounded-circle overflow-hidden"
                                                    style="width:90px;height:90px;cursor:pointer;">
                                                    <i class="fa fa-user fa-5x text-primary d-flex align-items-center justify-content-center"
                                                        style="width:100%;height:100%;display:flex;"></i>
                                                </label>
                                                <div>
                                                    <label for="foto"
                                                        style="font-size:50px !important; cursor:pointer; color:#10104c;">+</label>
                                                </div>
                                                <input type="file" accept="image/*" id="foto" name="foto"
                                                    style="display: none;" onchange="mostrarFoto(event)" />
                                            </div>

                                            <!-- FILA 1: NOMBRES Y APELLIDOS -->
                                            <div class="col-md-6 mb-3">
                                                <label>Nombres</label>
                                                <input type="text" name="nombres" class="form-control"
                                                    placeholder="Ingrese nombres" required />
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label>Apellidos</label>
                                                <input type="text" name="apellidos" class="form-control"
                                                    placeholder="Ingrese apellidos" required />
                                            </div>

                                            <!-- FILA 2: CÉDULA Y CORREO - MODIFICADA -->
                                            <div class="col-md-6 mb-3">
                                                <label>Cédula de Identidad</label>
                                                <div class="input-group">
                                                    <select class="form-select" name="tipo_cedula"
                                                        style="max-width: 80px;">
                                                        <option value="V-">V-</option>
                                                        <option value="E-">E-</option>
                                                        <option value="P-">P-</option>
                                                    </select>
                                                    <input type="text" name="cedula" class="form-control"
                                                        placeholder="Ingrese número" required />
                                                </div>
                                                <small class="text-muted">
                                                    <i class="fas fa-info-circle"></i>
                                                    V: Venezolano | E: Extranjero | P: Pasaporte
                                                </small>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label>Correo Electrónico</label>
                                                <input type="email" name="email" class="form-control"
                                                    placeholder="correo@ejemplo.com" required />
                                            </div>

                                            <!-- FILA 3: TELÉFONO Y DIRECCIÓN -->
                                            <div class="col-md-6 mb-3">
                                                <label>Teléfono</label>
                                                <div class="input-group">
                                                    <select class="form-select" name="cod_area"
                                                        style="max-width: 100px;">
                                                        <option value="0414">0414</option>
                                                        <option value="0424">0424</option>
                                                        <option value="0412">0412</option>
                                                        <option value="0416">0416</option>
                                                        <option value="0426">0426</option>
                                                    </select>
                                                    <input type="text" name="numero" class="form-control"
                                                        placeholder="Número" maxlength="7" required />
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label>Dirección</label>
                                                <input type="text" name="direccion" class="form-control"
                                                    placeholder="Dirección completa" required />
                                            </div>

                                            <!-- FILA 4: ROL Y ESPECIALIDAD - ACTUALIZADOS -->
                                            <div class="col-md-6 mb-3">
                                                <label>Rol</label>
                                                <select class="form-select" name="rol" required>
                                                    <option value="" selected disabled>Seleccione rol</option>
                                                    <option value="Medicina">Medicina</option>
                                                    <option value="Enfermería">Enfermería</option>
                                                    <option value="Dirección">Dirección</option>
                                                    <option value="Estadística en Salud">Estadística en Salud</option>
                                                    <option value="Administrador">Administrador</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label>Especialidad</label>
                                                <select class="form-select" name="especialidad" required>
                                                    <option value="" selected disabled>Seleccione especialidad</option>
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

                                            <!-- FILA 5: CONTRASEÑAS -->
                                            <div class="col-md-6 mb-3">
                                                <label>Contraseña</label>
                                                <div class="position-relative">
                                                    <input type="password" name="contrasena" id="contrasena"
                                                        class="form-control" placeholder="********" required
                                                        style="padding-right: 40px;" />
                                                    <i class="far fa-eye position-absolute" id="togglePassword"
                                                        style="right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #6c757d;"></i>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label>Confirmar Contraseña</label>
                                                <div class="position-relative">
                                                    <input type="password" name="confirmar_contrasena"
                                                        id="confirmar_contrasena" class="form-control"
                                                        placeholder="********" required style="padding-right: 40px;" />
                                                    <i class="far fa-eye position-absolute" id="toggleConfirmPassword"
                                                        style="right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #6c757d;"></i>
                                                </div>
                                                <div id="password-match-message" class="mt-1"></div>
                                            </div>

                                            <!-- BOTONES -->
                                            <div class="col-12 text-center mt-4">
                                                <button type="submit" class="btn btn-primary btn-lg">
                                                    <i class="fas fa-user-plus"></i> Registrar Personal
                                                </button>
                                                <button type="reset" class="btn btn-secondary btn-lg ms-2">
                                                    <i class="fas fa-undo"></i> Limpiar
                                                </button>
                                            </div>

                                        </div>
                                    </form>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom template -->
        <div class="custom-template">
            <div class="title">Settings</div>
            <div class="custom-content">
                <div class="switcher">

                    <div class="switch-block">
                        <h4>Navbar Header</h4>
                        <div class="btnSwitch">
                            <button type="button" class="changeTopBarColor" data-color="dark"></button>
                            <button type="button" class="changeTopBarColor" data-color="blue"></button>
                            <button type="button" class="changeTopBarColor" data-color="purple"></button>
                            <button type="button" class="changeTopBarColor" data-color="light-blue"></button>
                            <button type="button" class="changeTopBarColor" data-color="green"></button>
                            <button type="button" class="changeTopBarColor" data-color="orange"></button>
                            <button type="button" class="changeTopBarColor" data-color="red"></button>
                            <button type="button" class="changeTopBarColor" data-color="white"></button>
                            <br />
                            <button type="button" class="changeTopBarColor" data-color="dark2"></button>
                            <button type="button" class="selected changeTopBarColor" data-color="blue2"></button>
                            <button type="button" class="changeTopBarColor" data-color="purple2"></button>
                            <button type="button" class="changeTopBarColor" data-color="light-blue2"></button>
                            <button type="button" class="changeTopBarColor" data-color="green2"></button>
                            <button type="button" class="changeTopBarColor" data-color="orange2"></button>
                            <button type="button" class="changeTopBarColor" data-color="red2"></button>
                        </div>
                    </div>
                    <div class="switch-block">
                        <h4>Sidebar</h4>
                        <div class="btnSwitch">
                            <button type="button" class="selected changeSideBarColor" data-color="white"></button>
                            <button type="button" class="changeSideBarColor" data-color="dark"></button>
                            <button type="button" class="changeSideBarColor" data-color="dark2"></button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="custom-toggle">
                <i class="icon-settings"></i>
            </div>
        </div>
        <!-- End Custom template -->
    </div>

    <script>
    function mostrarFoto(event) {
        const input = event.target;
        const preview = document.getElementById("fotoPreview");

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" class="img-fluid rounded-circle" 
                                  style="width:100%;height:100%;object-fit:cover;" />`;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Validación adicional del formulario
    document.querySelector('form').addEventListener('submit', function(e) {
        const cedula = document.querySelector('input[name="cedula"]').value;
        const numero = document.querySelector('input[name="numero"]').value;

        // Validar cédula (solo números, 7-8 dígitos)
        if (!/^\d{7,8}$/.test(cedula)) {
            e.preventDefault();
            Swal.fire({
                title: '❌ Error',
                text: 'La cédula debe tener entre 7 y 8 dígitos',
                icon: 'error'
            });
            return;
        }

        // Validar número de teléfono (7 dígitos)
        if (!/^\d{7}$/.test(numero)) {
            e.preventDefault();
            Swal.fire({
                title: '❌ Error',
                text: 'El número de teléfono debe tener exactamente 7 dígitos',
                icon: 'error'
            });
            return;
        }
    });

    // Permitir solo números en cédula y teléfono
    document.querySelector('input[name="cedula"]').addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    document.querySelector('input[name="numero"]').addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
    </script>

    <script>
    // Validación en tiempo real - solo verificar que coincidan
    document.getElementById('confirmar_contrasena').addEventListener('input', function() {
        checkPasswordMatch();
    });

    document.getElementById('contrasena').addEventListener('input', function() {
        checkPasswordMatch();
    });

    // Verificar que las contraseñas coincidan
    function checkPasswordMatch() {
        const password = document.getElementById('contrasena').value;
        const confirmPassword = document.getElementById('confirmar_contrasena').value;
        const messageDiv = document.getElementById('password-match-message');

        if (confirmPassword === '') {
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

    // Validación antes de enviar el formulario
    document.querySelector('form').addEventListener('submit', function(e) {
        const password = document.getElementById('contrasena').value;
        const confirmPassword = document.getElementById('confirmar_contrasena').value;

        // Validar que las contraseñas coincidan
        if (password !== confirmPassword) {
            e.preventDefault();
            Swal.fire({
                title: '❌ Error',
                text: 'Las contraseñas no coinciden',
                icon: 'error'
            });
            return;
        }

        // Validar longitud mínima (cambiar de 4 a 8)
        if (password.length < 8) {
            e.preventDefault();
            Swal.fire({
                title: '❌ Error',
                text: 'La contraseña debe tener al menos 8 caracteres',
                icon: 'error'
            });
            return;
        }
    });
    </script>

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

    <!-- Kaiadmin DEMO methods, don't include it in your project! -->
    <script src="../assets/js/setting-demo2.js"></script>

    <script>
    // Función para mostrar/ocultar contraseñas
    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordInput = document.getElementById('contrasena');
        const eyeIcon = document.getElementById('eyeIcon1');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            eyeIcon.classList.remove('fa-eye-slash');
            eyeIcon.classList.add('fa-eye');
        }
    });

    document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
        const confirmPasswordInput = document.getElementById('confirmar_contrasena');
        const eyeIcon = document.getElementById('eyeIcon2');

        if (confirmPasswordInput.type === 'password') {
            confirmPasswordInput.type = 'text';
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
        } else {
            confirmPasswordInput.type = 'password';
            eyeIcon.classList.remove('fa-eye-slash');
            eyeIcon.classList.add('fa-eye');
        }
    });
    </script>

    </script>

    <!-- MODAL DE CONFIRMACIÓN DE CIERRE (Para agregar globalmente) -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
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