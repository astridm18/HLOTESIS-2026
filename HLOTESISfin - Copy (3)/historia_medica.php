<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.html");
    exit;
}

// Incluir conexión a la base de datos
include("conexion.php");

// Obtener foto del usuario actual
$foto_usuario = null;
$avatar_url = "assets/img/profile.jpg"; // Imagen por defecto

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
            $ruta_foto = "forms/uploads/fotos_personal/" . $foto_usuario;
            if (file_exists($ruta_foto)) {
                $avatar_url = $ruta_foto;
            }
        }
    }
    $stmt_foto->close();
}

// DEBUG: Para ver qué está pasando
echo "<!-- DEBUG INFO:";
echo "\nUsuario ID: " . ($_SESSION['usuario_id'] ?? 'NO SET');
echo "\nFoto en BD: " . ($foto_usuario ?? 'NO SET');
echo "\nRuta final: " . $avatar_url;
echo "\nArchivo existe: " . (file_exists($avatar_url) ? 'SÍ' : 'NO');
echo "\n-->";
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>HLO - HISTORIA MÉDICA</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/kaiadmin/logoHlo(2).ico" type="image/x-icon" />

    <!-- Fonts and icons -->
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
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
            urls: ["assets/css/fonts.min.css"],
        },
        active: function() {
            sessionStorage.fonts = true;
        },
    });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="assets/css/demo.css" />

    <!-- External libraries -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
    :root {
        --primary-blue: #1e3a8a;
        --secondary-blue: #3b82f6;
        --light-blue: #dbeafe;
        --dark-blue: #1e40af;
        --accent-blue: #60a5fa;
        --success-green: #10b981;
        --warning-orange: #f59e0b;
        --danger-red: #ef4444;
        --sidebar-width: 280px;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        color: #1a202c;
    }

    /* Sidebar Styles */
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: var(--sidebar-width);
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
        color: white;
        transition: all 0.3s ease;

        z-index: 1000;
        box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
    }

    .sidebar .nav-link {
        color: rgba(255, 255, 255, 0.9);
        transition: all 0.3s ease;
        border-radius: 8px;
        margin: 2px 8px;
        padding: 12px 15px;
        display: flex;
        align-items: center;
    }

    .sidebar .nav-link:hover,
    .sidebar .nav-link.active {
        background: rgba(255, 255, 255, 0.15);
        color: white;
        transform: translateX(5px);
    }

    .sidebar .nav-link i {
        width: 20px;
        margin-right: 12px;
        font-size: 16px;
    }

    /* Main Content */
    .main-content {
        margin-left: var(--sidebar-width);
        min-height: 100vh;
        transition: all 0.3s ease;
        background: transparent;
    }

    /* Top Navigation */
    .main-header {
        background: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 0;
    }

    /* Cards */
    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        margin-bottom: 25px;
    }

    /* Buttons */
    .btn-primary {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
        border: none;
        border-radius: 10px;
        transition: all 0.3s ease;
        padding: 10px 20px;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, var(--dark-blue) 0%, var(--primary-blue) 100%);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(30, 58, 138, 0.3);
    }

    .btn-secondary {
        background: linear-gradient(135deg, #6c757d, #5a6268);
        border: none;
        border-radius: 10px;
        transition: all 0.3s ease;
    }

    /* Responsive Design */
    @media (max-width: 992px) {
        .main-content {
            margin-left: 0;
        }

        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.show {
            transform: translateX(0);
        }
    }
    </style>
</head>

<body>
    <div class="wrapper">
        <!-- Modern Sidebar -->
        <div class="sidebar" data-background-color="white">
            <div class="sidebar-logo">
                <div class="logo-header d-flex justify-content-center align-items-center">
                    <a href="index.php" class="logo">
                        <img src="assets/img/kaiadmin/logohlo.png" alt="navbar brand" class="navbar-brand"
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
                                        <a href="#" onclick="mostrarSeccion('dashboard')" class="active">
                                            <span class="sub-item">Dashboard Principal</span>
                                        </a>
                                    </li>
                                    <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'dirección', 'medicina'])): ?>
                                    <li>
                                        <a href="index.php?seccion=nuevo-ingreso">
                                            <span class="sub-item">Nuevo Ingreso</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'dirección', 'enfermería', 'medicina'])): ?>
                                    <li>
                                        <a href="index.php?seccion=casos-activos">
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
                                        <a href="forms/listar_personal.php">
                                            <span class="sub-item">Lista de Personal</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="forms/registro_personal.php">
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
                                        <a href="forms/listar_pacientes.php">
                                            <span class="sub-item">Lista de Pacientes</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="index.php?seccion=buscar-paciente">
                                            <span class="sub-item">Buscar Pacientes</span>
                                        </a>
                                    </li>
                                    <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'dirección', 'medicina'])): ?>
                                    <li>
                                        <a href="historia_medica.php">
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
                            <a href="index.php?seccion=estadisticas">
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
            <!-- Modern Header -->
            <div class="main-header">
                <div class="main-header-logo">
                    <div class="logo-header" data-background-color="white">
                        <a href="index.php" class="logo">
                            <img src="assets/img/kaiadmin/logo_light.svg" alt="navbar brand" class="navbar-brand"
                                height="20" />
                        </a>
                        <div class="nav-toggle">
                            <button class="btn btn-toggle toggle-sidebar">
                                <i class="gg-menu-right" style="color: white;"></i>
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

                <!-- Navbar Header -->
                <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
                    <div class="container-fluid">
                        <img src="assets/img/IVSS.png" alt="navbar brand" class="navbar-brand" height="80" />
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
                                                <a class="col-6 col-md-4 p-0" href="forms/listar_personal.php">
                                                    <div class="quick-actions-item">
                                                        <div class="avatar-item bg-secondary rounded-circle">
                                                            <i class="fas fa-users"></i>
                                                        </div><span class="text">Personal</span>
                                                    </div>
                                                </a>
                                                <a class="col-6 col-md-4 p-0" href="forms/registro_personal.php">
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

                            <!-- User Profile -->
                            <li class="nav-item topbar-user dropdown hidden-caret">
                                <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#"
                                    aria-expanded="false">
                                    <div class="avatar-sm">
                                        <img src="<?php echo htmlspecialchars($avatar_url); ?>"
                                            alt="Foto de <?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?>"
                                            class="avatar-img rounded-circle"
                                            style="width: 36px; height: 36px; object-fit: cover; border: 2px solid #ddd;"
                                            onerror="this.src='assets/img/profile.jpg'; console.log('Error loading image: <?php echo $avatar_url; ?>');" />
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
                                                        style="width: 60px; height: 60px; object-fit: cover; border: 2px solid #ddd;"
                                                        onerror="this.src='assets/img/profile.jpg';" />
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
                                            <a class="dropdown-item" href="reportes/manual-usuario.pdf" target="_blank">
                                                <i class="fas fa-question-circle me-2 text-info"></i>Ayuda
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="logout.php">
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

            <!-- Container Principal -->
            <div class="container">
                <div class="page-inner">
                    <!-- Encabezado de la página -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 style="color: var(--primary-blue);">
                            <i class="fas fa-file-medical me-2"></i>Historia Médica - Nuevo Ingreso
                        </h3>
                        <button class="btn btn-secondary" onclick="window.location.href='index.php'">
                            <i class="fas fa-arrow-left me-1"></i>Volver al Dashboard
                        </button>
                    </div>

                    <!-- Formulario de Ingreso -->
                    <div class="card">
                        <div class="card-body">
                            <iframe src="forms/modulos/ingreso/formulario_ingreso.php" width="100%" height="800"
                                frameborder="0" style="border-radius: 10px; border: 1px solid #e9ecef;">
                            </iframe>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <footer class="footer">
                <div class="container-fluid d-flex justify-content-between">
                    <nav class="pull-left">
                        <ul class="nav">
                            <li class="nav-item">
                                <a class="nav-link" href="#">Sistema HLO</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#">Ayuda</a>
                            </li>
                        </ul>
                    </nav>
                    <div class="copyright">
                        2025, desarrollado por
                        <a href="#" style="color: var(--primary-blue);">Astrid Marquina</a>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script>

    <script>
    // Mobile menu handler
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 992) {
            const sidebar = document.querySelector('.sidebar');
            const toggleButton = e.target.closest('.toggle-sidebar');

            if (toggleButton) {
                sidebar.classList.toggle('show');
            } else if (!e.target.closest('.sidebar') && sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        }
    });
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
        const logoutLink = document.querySelector('a[href="logout.php"]');
        if (logoutLink) {
            logoutLink.setAttribute('href', '#');
            logoutLink.setAttribute('data-bs-toggle', 'modal');
            logoutLink.setAttribute('data-bs-target', '#logoutModal');
        }

        // Confirmar logout
        document.getElementById('confirmLogout').addEventListener('click', function() {
            window.location.href = 'logout.php';
        });
    });
    </script>

</body>

</html>