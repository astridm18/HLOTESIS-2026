<?php
/**
 * Dashboard principal del sistema de historias médicas.
 * Requiere sesión activa; carga menú, estadísticas y secciones (nuevo ingreso, casos activos, buscar paciente, etc.).
 */
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.html");
    exit;
}

// Incluir conexión a la base de datos
include("conexion.php");

$foto_usuario = null;
$avatar_url = "assets/img/profile.jpg"; // Imagen por defecto
$_SESSION['usuario_rol'] = $_SESSION['usuario_rol'] ?? 'Invitado'; // Rol por defecto si no se encuentra

// Obtener foto y rol del usuario actual desde la base de datos
if (isset($_SESSION['usuario_id'])) {
    // Hemos combinado las consultas para eficiencia
    $query_usuario = "SELECT foto, rol FROM personal WHERE id = ?";
    $stmt_usuario = $conn->prepare($query_usuario);
    $stmt_usuario->bind_param("i", $_SESSION['usuario_id']);
    $stmt_usuario->execute();
    $result_usuario = $stmt_usuario->get_result();

    if ($usuario_data = $result_usuario->fetch_assoc()) {
        $foto_usuario = $usuario_data['foto'];

        // Almacenar el rol en la sesión para usarlo en todo el sitio
        $_SESSION['usuario_rol'] = strtolower($usuario_data['rol']); // Guardamos en minúsculas para facilitar la comparación

        // Si el usuario tiene una foto, verificar que el archivo existe
        if (!empty($foto_usuario)) {
            $ruta_foto_abs = __DIR__ . "/uploads/fotos_personal/" . $foto_usuario;
            $ruta_foto_rel = "uploads/fotos_personal/" . $foto_usuario;
            if (file_exists($ruta_foto_abs)) {
                $avatar_url = $ruta_foto_rel;
            }
        }
    }
    $stmt_usuario->close();
}

// DEBUG: Para ver qué está pasando
echo "";
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>HLO - ÁREA DE EMERGENCIAS</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/kaiadmin/logoHlo(2).ico" type="image/x-icon" />

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
            active: function () {
                sessionStorage.fonts = true;
            },
        });
    </script>

    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="assets/css/demo.css" />

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Responsive para tabla de casos activos */
        @media (max-width: 992px) {
            .table-responsive table {
                font-size: 0.9rem;
            }

            .badge {
                font-size: 0.75rem !important;
                padding: 0.3rem 0.5rem !important;
            }
        }

        @media (max-width: 768px) {

            .table th,
            .table td {
                padding: 0.5rem 0.3rem;
            }

            .btn-sm {
                padding: 0.25rem 0.4rem;
                font-size: 0.75rem;
            }

            .btn-sm i {
                font-size: 0.8rem;
            }
        }

        @media (max-width: 576px) {
            .table-responsive {
                font-size: 0.8rem;
            }

            .badge {
                font-size: 0.7rem !important;
                padding: 0.2rem 0.4rem !important;
            }
        }
    </style>
    <style>
        .modal-content {
            border-radius: 15px;
            overflow: hidden;
        }

        .modal-header {
            border-radius: 15px 15px 0 0;
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            padding: 1.5rem 2rem;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Efecto de hover en botones del modal */
        .modal .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }
    </style>
    <style>
        :root {
            --primary-blue: #1e3a8a;
            --secondary-blue: #3b82f6;
            --light-blue: #dbeafe;
            --dark-blue: #1e40af;
            --accent-blue: #60a5fa;
            --success-green: #10b981;
            --warning-orange: red;
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

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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

        .navbar-custom {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            box-shadow: 0 2px 10px rgba(30, 58, 138, 0.2);
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            margin-bottom: 25px;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        /* Stats Cards */
        .stat-card {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(30px, -30px);
        }

        .stat-card-success {
            background: linear-gradient(135deg, #059669 0%, var(--success-green) 100%);
        }

        .stat-card-warning {
            background: red;
        }

        .stat-card-danger {
            background: linear-gradient(135deg, #dc2626 0%, var(--danger-red) 100%);
        }

        .stat-card .card-body {
            padding: 25px;
            position: relative;
            z-index: 2;
        }

        .stat-card i {
            font-size: 2.5rem !important;
            margin-bottom: 15px;
            opacity: 0.9;
        }

        .stat-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-card p {
            opacity: 0.9;
            font-size: 0.95rem;
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

        .btn-info {
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            border: none;
            border-radius: 10px;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success-green), #059669);
            border: none;
            border-radius: 10px;
        }

        .btn-round {
            border-radius: 25px !important;
        }

        /* Quick Actions */
        .quick-action-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            height: 100%;
        }

        .quick-action-card:hover {
            transform: scale(1.05);
            border-color: var(--secondary-blue);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.2);
        }

        .quick-action-card .card-body {
            padding: 30px 20px;
        }

        .quick-action-card i {
            transition: all 0.3s ease;
        }

        .quick-action-card:hover i {
            transform: scale(1.1);
        }

        /* Charts */
        .chart-container {
            position: relative;
            height: 350px !important;
            width: 100%;
            max-height: 350px;
            overflow: hidden;
        }

        .chart-container-circular {
            position: relative;
            height: 280px !important;
            width: 100%;
            max-height: 280px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #serviciosChart,
        #diagnosticosChart {
            max-height: 280px !important;
            height: 280px !important;
            width: 100% !important;
        }

        #ingresosMesChart,
        #ingresosMesChartDetalle {
            max-height: 350px !important;
            height: 350px !important;
            width: 100% !important;
        }

        /* Content Sections */
        .content-section {
            display: none;
            animation: fadeIn 0.5s ease-in;
        }

        .content-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Page Header */
        .page-header {
            padding: 30px 0 20px;
        }

        .page-header h3 {
            color: var(--primary-blue);
            font-weight: 700;
            margin-bottom: 10px;
            font-size: 2rem;
        }

        .page-header h6 {
            color: #64748b;
            font-weight: 400;
        }

        /* Tables */
        .table th {
            background: var(--light-blue);
            color: var(--primary-blue);
            font-weight: 600;
            border: none;
            padding: 15px;
        }

        .table td {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: #f8fafc;
            transform: scale(1.01);
            transition: all 0.2s ease;
        }

        /* Badges */
        .badge-custom {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.85rem;
        }

        /* Search */
        .form-control {
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--secondary-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        /* Notifications */
        .notification {
            background: var(--danger-red);
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 11px;
            position: absolute;
            top: -5px;
            right: -5px;
        }

        /* User Info */
        .profile-pic {
            display: flex;
            align-items: center;
            padding: 5px 15px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .profile-pic:hover {
            background: rgba(59, 130, 246, 0.1);
        }

        .avatar-sm {
            width: 35px;
            height: 35px;
            margin-right: 10px;
        }

        .avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .stat-card h3 {
                font-size: 2rem;
            }

            .stat-card i {
                font-size: 2rem !important;
            }

            .chart-container {
                height: 300px !important;
            }

            .chart-container-circular {
                height: 250px !important;
            }
        }

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

            .stat-card .card-body {
                padding: 20px;
            }

            .quick-action-card .card-body {
                padding: 25px 15px;
            }

            .page-header h3 {
                font-size: 1.7rem;
            }
        }

        @media (max-width: 768px) {
            .stat-card h3 {
                font-size: 1.8rem;
            }

            .stat-card i {
                font-size: 1.8rem !important;
            }

            .quick-action-card .card-body {
                padding: 20px 10px;
            }

            .chart-container {
                height: 250px !important;
            }

            .chart-container-circular {
                height: 200px !important;
            }

            .page-header {
                padding: 20px 0 15px;
            }

            .page-header h3 {
                font-size: 1.5rem;
            }

            .btn-round {
                padding: 8px 15px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 576px) {
            .stat-card h3 {
                font-size: 1.5rem;
            }

            .stat-card i {
                font-size: 1.5rem !important;
            }

            .stat-card p {
                font-size: 0.85rem;
            }

            .card {
                margin-bottom: 20px;
            }

            .quick-action-card h5 {
                font-size: 1rem;
            }

            .quick-action-card p {
                font-size: 0.85rem;
            }
        }

        /* Loading Animation */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e5e7eb;
            border-top: 4px solid var(--secondary-blue);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }


        /* Solo mostrar el slider cuando estemos en dashboard */
        .hospital-slider-container {
            display: none;
            /* Oculto por defecto */
            width: 100%;
            margin-bottom: 30px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            padding: 3px;
        }

        /* Mostrar solo en dashboard */
        #dashboard-section.active .hospital-slider-container {
            display: block;
        }

        .hospital-slider-wrapper {
            position: relative;
            width: 100%;
            height: 300px;
            border-radius: 12px;
            overflow: hidden;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
        }

        .hospital-slider {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 0.8s ease-in-out;
            z-index: 1;
        }

        .slide.active {
            opacity: 1;
            z-index: 2;
        }

        .slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            transition: transform 8s ease-in-out;
        }

        .slide.active img {
            transform: scale(1.05);
        }

        /* Overlay para el contenido */
        .slide-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;

            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 5;
        }

        .slide-content {
            text-align: center;
            color: white;
            padding: 20px;
            animation: slideUp 0.8s ease-out;
            z-index: 6;
        }

        .slide-content h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .slide-content p {
            font-size: 1.1rem;
            opacity: 0.95;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
            margin: 0;
        }

        /* Navigation Dots */
        .slider-dots {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 6;
        }

        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .dot.active,
        .dot:hover {
            background: white;
            transform: scale(1.2);
            border-color: #1e3a8a;
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.8);
        }

        /* Navigation Arrows */
        .slider-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 100%;
            display: flex;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 6;
            pointer-events: none;
        }

        .nav-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            backdrop-filter: blur(10px);
            pointer-events: auto;
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: white;
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        /* Animations */
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hospital-slider-wrapper {
                height: 250px;
            }

            .slide-content h3 {
                font-size: 1.5rem;
            }

            .slide-content p {
                font-size: 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <div class="wrapper">
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
                                            <a href="#" onclick="mostrarSeccion('nuevo-ingreso')">
                                                <span class="sub-item">Nuevo Ingreso</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'dirección', 'enfermería', 'medicina'])): ?>
                                        <li>
                                            <a href="#" onclick="mostrarSeccion('casos-activos')">
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
                                            <a href="modulos/personal/listar_personal.php">
                                                <span class="sub-item">Lista de Personal</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="modulos/personal/registro_personal.php">
                                                <span class="sub-item">Registro de Personal</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="modulos/personal/personal_inactivo.php">
                                                <span class="sub-item">Personal Inactivo</span>
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
                                        <a href="modulos/personal/listar_pacientes.php">
                                            <span class="sub-item">Lista de Pacientes</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" onclick="mostrarSeccion('buscar-paciente')">
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
                                <a href="#" onclick="mostrarSeccion('estadisticas')">
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

                <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
                    <div class="container-fluid">
                        <img src="assets/img/IVSS.png" alt="navbar brand" class="navbar-brand" height="80" />
                        <h2> Hospital General Dr. Luis Ortega tipo III</h2>

                        <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                            <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'dirección'])): ?>
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
                                                    <a class="col-6 col-md-4 p-0" href="modulos/personal/listar_personal.php">
                                                        <div class="quick-actions-item">
                                                            <div class="avatar-item bg-secondary rounded-circle">
                                                                <i class="fas fa-users"></i>
                                                            </div><span class="text">Personal</span>
                                                        </div>
                                                    </a>
                                                    <a class="col-6 col-md-4 p-0" href="modulos/personal/registro_personal.php">
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
                                            <a class="dropdown-item" href="reportes/manual-usuario.pdf" target="_blank">
                                                <i class="fas fa-question-circle me-2 text-info"></i>Ayuda
                                            </a>
                                            <div class="dropdown-divider"></div>

                                            <a class="dropdown-item" href="logout.php">
                                                <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                                            </a </li>
                                    </div>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>

            <div class="container">
                <div class="page-inner">
                    <div id="dashboard-section" class="content-section active">
                        <div class="hospital-slider-container mb-4">
                            <div class="hospital-slider-wrapper">
                                <div class="hospital-slider">
                                    <div class="slide active">
                                        <img src="assets/img/slide1.jpeg"
                                            alt="Hospital Dr. Luis Ortega - Área de Emergencias"
                                            onerror="this.style.display='none'; this.parentElement.style.background='linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%)'">
                                        <div class="slide-overlay">
                                            <div class="slide-content">
                                                <h3>Hospital Dr. Luis Ortega</h3>
                                                <p>Área de Emergencias - Hospitalización de Traumatología</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="slide">
                                        <img src="assets/img/slide1.jpg" alt="Instalaciones Modernas del Hospital"
                                            onerror="this.style.display='none'; this.parentElement.style.background='linear-gradient(135deg, #059669 0%, #10b981 100%)'">
                                        <div class="slide-overlay">
                                            <div class="slide-content">
                                                <h3>Atención Médica Especializada</h3>
                                                <p>Tecnología de vanguardia al servicio de tu salud</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="slide">
                                        <img src="assets/img/slide1.webp" alt="Equipo Médico Profesional"
                                            onerror="this.style.display='none'; this.parentElement.style.background='linear-gradient(135deg, #d97706 0%, #f59e0b 100%)'">
                                        <div class="slide-overlay">
                                            <div class="slide-content">
                                                <h3>Equipo Médico Calificado</h3>
                                                <p>Personal altamente capacitado disponible 24/7</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="slider-dots">
                                    <span class="dot active" onclick="currentSlide(1)"></span>
                                    <span class="dot" onclick="currentSlide(2)"></span>
                                    <span class="dot" onclick="currentSlide(3)"></span>
                                </div>

                                <div class="slider-nav">
                                    <button class="nav-btn prev" onclick="changeSlide(-1)">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <button class="nav-btn next" onclick="changeSlide(1)">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
                            <div>
                                <h3 class="fw-bold mb-3">
                                    <i class="fas fa-chart-pie me-2" style="color: var(--primary-blue);"></i>
                                    Dashboard de Emergencias
                                </h3>
                                <h6 class="op-7 mb-2">Sistema de Historias Médicas - HLO</h6>
                            </div>
                            <div class="ms-md-auto py-2 py-md-0">

                                <button class="btn btn-info btn-round me-2" onclick="actualizarDashboard()">
                                    <i class="fas fa-sync-alt me-1"></i>Actualizar
                                </button>
                                <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'dirección', 'medicina'])): ?>
                                    <button class="btn btn-primary btn-round" onclick="mostrarSeccion('nuevo-ingreso')">
                                        <i class="fas fa-user-plus me-1"></i>Nuevo Ingreso
                                    </button> <?php endif; ?>
                            </div>
                        </div>

                        <div class="row dashboard-stats mb-4">
                            <div class="col-sm-6 col-lg-3">
                                <div class="card stat-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-users"></i>
                                        <h3 id="total-pacientes">-</h3>
                                        <p class="mb-0">Pacientes Totales</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div class="card stat-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-bed"></i>
                                        <h3 id="casos-activos">-</h3>
                                        <p class="mb-0">Casos Activos</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div class="card stat-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-calendar"></i>
                                        <h3 id="ingresos-hoy">-</h3>
                                        <p class="mb-0">Ingresos Hoy</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-6 col-lg-3">
                                <div class="card stat-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <h3 id="casos-urgentes">-</h3>
                                        <p class="mb-0">Casos Urgentes</p>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="row mb-4">
                            <div class="col-12">
                                <h4 class="mb-3" style="color: var(--primary-blue);">
                                    <i class="fas fa-bolt me-2"></i>Acciones Rápidas
                                </h4>
                            </div>
                            <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'dirección', 'medicina'])): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card quick-action-card" onclick="mostrarSeccion('nuevo-ingreso')">
                                        <div class="card-body text-center">
                                            <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
                                            <h5>Nuevo Ingreso</h5>
                                            <p class="text-muted">Registrar nuevo paciente</p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="col-md-4 mb-3">
                                <div class="card quick-action-card" onclick="mostrarSeccion('buscar-paciente')">
                                    <div class="card-body text-center">
                                        <i class="fas fa-search fa-3x text-success mb-3"></i>
                                        <h5>Buscar Paciente</h5>
                                        <p class="text-muted">Encontrar paciente existente</p>
                                    </div>
                                </div>
                            </div>
                            <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'dirección', 'medicina', 'enfermería'])): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card quick-action-card" onclick="mostrarSeccion('casos-activos')">
                                        <div class="card-body text-center">
                                            <i class="fas fa-list fa-3x text-warning mb-3"></i>
                                            <h5>Ver Casos Activos</h5>
                                            <p class="text-muted">Casos en tratamiento</p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="row">
                            <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'dirección', 'estadística en salud'])): ?>
                                <div class="col-lg-8 mb-4">
                                    <div class="card">
                                        <div
                                            class="card-header d-flex justify-content-between align-items-center flex-wrap">
                                            <h5 style="color: var(--primary-blue);">
                                                <i class="fas fa-chart-line me-2"></i>Índice de Ingresos
                                            </h5>
                                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                                <select class="form-control form-control-sm" id="periodoIngresosDashboard"
                                                    style="width: auto;">
                                                    <option value="1M">Último Mes</option>
                                                    <option value="2M">Últimos 2 Meses</option>
                                                    <option value="4M">Últimos 4 Meses</option>
                                                    <option value="6M" selected>Últimos 6 Meses</option>
                                                    <option value="1Y">Último Año</option>
                                                    <option value="custom">Rango Personalizado</option>
                                                </select>
                                                <input type="date" class="form-control form-control-sm"
                                                    id="fechaInicioIngresosDashboard" style="width: auto; display: none;">
                                                <input type="date" class="form-control form-control-sm"
                                                    id="fechaFinIngresosDashboard" style="width: auto; display: none;">
                                                <button class="btn btn-sm btn-primary" id="aplicarFiltroIngresosDashboard"
                                                    style="display: none;">
                                                    <i class="fas fa-filter"></i> Aplicar
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="chart-container">
                                                <canvas id="ingresosMesChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 style="color: var(--primary-blue);">
                                                <i class="fas fa-chart-doughnut me-2"></i>Distribución por Servicio
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="chart-container-circular">
                                                <canvas id="serviciosChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>


                    </div>

                    <div id="nuevo-ingreso-section" class="content-section">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h3 style="color: var(--primary-blue);">
                                <i class="fas fa-user-plus me-2"></i>Nuevo Ingreso de Paciente
                            </h3>
                            <button class="btn btn-secondary" onclick="mostrarSeccion('dashboard')">
                                <i class="fas fa-arrow-left me-1"></i>Volver al Dashboard
                            </button>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <iframe src="modulos/ingreso/formulario_ingreso.php" width="100%" height="800"
                                    frameborder="0" style="border-radius: 10px; border: 1px solid #e9ecef;">
                                </iframe>
                            </div>
                        </div>
                    </div>

                    <div id="casos-activos-section" class="content-section">
                        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                            <h3 style="color: var(--primary-blue);">
                                <i class="fas fa-procedures me-2"></i>Casos Activos
                            </h3>
                            <div class="d-flex flex-wrap gap-2">

                                <button class="btn btn-outline-primary" onclick="cargarCasosActivos()">
                                    <i class="fas fa-sync-alt me-1"></i>Actualizar
                                </button>
                                <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['administrador', 'dirección', 'medicina'])): ?>
                                    <button class="btn btn-primary" onclick="mostrarSeccion('nuevo-ingreso')">
                                        <i class="fas fa-plus me-1"></i>Nuevo Caso
                                    </button> <?php endif; ?>
                                <button class="btn btn-secondary" onclick="mostrarSeccion('dashboard')">
                                    <i class="fas fa-arrow-left me-1"></i>Dashboard
                                </button>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="tablaPersonal" class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>N° Caso</th>
                                                <th>Paciente</th>
                                                <th class="d-none d-md-table-cell">Cédula</th>
                                                <th class="d-none d-lg-table-cell">Fecha Ingreso</th>
                                                <th>Servicio</th>
                                                <th class="d-none d-xl-table-cell">Médico Responsable</th>
                                                <th>Estado</th>
                                                <th>Prioridad</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tabla-casos-activos">
                                            <tr>
                                                <td colspan="9" class="text-center">
                                                    <i class="fas fa-spinner fa-spin"></i> Cargando casos activos...
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="buscar-paciente-section" class="content-section">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h3 style="color: var(--primary-blue);">
                                <i class="fas fa-search me-2"></i>Buscar Paciente
                            </h3>
                            <button class="btn btn-secondary" onclick="mostrarSeccion('dashboard')">
                                <i class="fas fa-arrow-left me-1"></i>Volver al Dashboard
                            </button>
                        </div>

                        <div class="row">
                            <div class="col-lg-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 style="color: var(--primary-blue);">
                                            <i class="fas fa-search me-2"></i>Búsqueda
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Buscar por:</label>
                                            <select class="form-control" id="tipo-busqueda">
                                                <option value="cedula">Cédula</option>
                                                <option value="nombre">Nombre</option>
                                                <option value="historia">N° Historia</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Término de búsqueda:</label>
                                            <input type="text" class="form-control" id="termino-busqueda"
                                                placeholder="Ingrese término a buscar">
                                        </div>
                                        <button class="btn btn-primary w-100" onclick="buscarPacientes()">
                                            <i class="fas fa-search me-2"></i>Buscar
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 style="color: var(--primary-blue);">
                                            <i class="fas fa-user me-2"></i>Resultados
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="resultados-busqueda">
                                            <p class="text-muted text-center">
                                                <i class="fas fa-info-circle me-2"></i>
                                                Ingrese un término de búsqueda
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="estadisticas-section" class="content-section">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h3 style="color: var(--primary-blue);">
                                <i class="fas fa-chart-bar me-2"></i>Estadísticas e Informes
                            </h3>
                            <button class="btn btn-secondary" onclick="mostrarSeccion('dashboard')">
                                <i class="fas fa-arrow-left me-1"></i>Volver al Dashboard
                            </button>
                        </div>

                        <div class="row mb-4">
                            <div class="col-lg-6 mb-4">
                                <div class="card">
                                    <div
                                        class="card-header d-flex justify-content-between align-items-center flex-wrap">
                                        <h5 style="color: var(--primary-blue);">
                                            <i class="fas fa-chart-line me-2"></i>Índice de Ingresos
                                        </h5>
                                        <div class="d-flex flex-wrap gap-2 align-items-center">
                                            <select class="form-control form-control-sm" id="periodoIngresos"
                                                style="width: auto;">
                                                <option value="1M">Último Mes</option>
                                                <option value="2M">Últimos 2 Meses</option>
                                                <option value="4M">Últimos 4 Meses</option>
                                                <option value="6M" selected>Últimos 6 Meses</option>
                                                <option value="1Y">Último Año</option>
                                                <option value="custom">Rango Personalizado</option>
                                            </select>
                                            <input type="date" class="form-control form-control-sm"
                                                id="fechaInicioIngresos" style="width: auto; display: none;">
                                            <input type="date" class="form-control form-control-sm"
                                                id="fechaFinIngresos" style="width: auto; display: none;">
                                            <button class="btn btn-sm btn-primary" id="aplicarFiltroIngresos"
                                                style="display: none;">
                                                <i class="fas fa-filter"></i> Aplicar
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="ingresosMesChartDetalle"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 style="color: var(--primary-blue);">
                                            <i class="fas fa-chart-pie me-2"></i>Diagnósticos Frecuentes
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container-circular">
                                            <canvas id="diagnosticosChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 style="color: var(--primary-blue);">
                                            <i class="fas fa-file-excel me-2"></i>Generar Reportes
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3 mb-2">
                                                <button class="btn btn-success w-100"
                                                    onclick="generarReporte('ingresos-diarios')">
                                                    <i class="fas fa-calendar-day me-2"></i>
                                                    Ingresos Diarios
                                                </button>
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <button class="btn btn-info w-100"
                                                    onclick="generarReporte('pacientes-activos')">
                                                    <i class="fas fa-users me-2"></i>
                                                    Pacientes Activos
                                                </button>
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <button class="btn btn-warning w-100"
                                                    onclick="generarReporte('estadisticas-servicio')">
                                                    <i class="fas fa-hospital me-2"></i>
                                                    Por Servicio
                                                </button>
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <button class="btn btn-danger w-100"
                                                    onclick="generarReporte('casos-urgentes')">
                                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                                    Casos Urgentes
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="footer">
                <div class="container-fluid d-flex justify-content-between">
                    <nav class="pull-left">
                        <ul class="nav">
                            <li class="nav-item">
                                <a class="nav-link" href="index.php">VITALERT</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="reportes/manual-usuario.pdf">Ayuda</a>
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

        <div class="custom-template">
            <div class="title">Configuración</div>
            <div class="custom-content">
                <div class="switcher">
                    <div class="switch-block">
                        <h4>Logo Header</h4>
                        <div class="btnSwitch">
                            <button type="button" class="changeLogoHeaderColor" data-color="white"></button>
                            <button type="button" class="changeLogoHeaderColor" data-color="blue"></button>
                            <button type="button" class="changeLogoHeaderColor" data-color="purple"></button>
                            <button type="button" class="changeLogoHeaderColor" data-color="light-blue"></button>
                            <button type="button" class="changeLogoHeaderColor" data-color="green"></button>
                            <button type="button" class="changeLogoHeaderColor" data-color="orange"></button>
                            <button type="button" class="changeLogoHeaderColor" data-color="red"></button>
                            <button type="button" class="selected changeLogoHeaderColor" data-color="dark"></button>
                        </div>
                    </div>
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
                            <button type="button" class="selected changeTopBarColor" data-color="white"></button>
                            <br />
                            <button type="button" class="changeTopBarColor" data-color="dark2"></button>
                            <button type="button" class="changeTopBarColor" data-color="blue2"></button>
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
                            <button type="button" class="changeSideBarColor" data-color="white"></button>
                            <button type="button" class="selected changeSideBarColor" data-color="dark"></button>
                            <button type="button" class="changeSideBarColor" data-color="dark2"></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="assets/js/plugin/chart.js/chart.min.js"></script>
    <script src="assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js"></script>
    <script src="assets/js/plugin/chart-circle/circles.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

    <script src="assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>
    <script src="assets/js/plugin/jsvectormap/jsvectormap.min.js"></script>
    <script src="assets/js/plugin/jsvectormap/world.js"></script>
    <script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script>

    <script>
        // Hospital Slider JavaScript - VERSIÓN CORREGIDA
        let currentSlideIndex = 0;
        let slideInterval;
        let slides = [];
        let dots = [];
        let totalSlides = 0;
        let sliderInitialized = false;

        // FUNCIÓN PRINCIPAL para mostrar secciones
        function mostrarSeccion(seccion) {
            console.log(`Mostrando sección: ${seccion}`);

            // Ocultar todas las secciones
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });

            // Reset slider cuando salimos del dashboard
            if (seccion !== 'dashboard') {
                resetSlider();
            }

            // Mostrar la sección seleccionada
            const targetSection = document.getElementById(seccion + '-section');
            if (targetSection) {
                targetSection.classList.add('active');
            }

            // Actualizar nav activo
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });

            // Si vamos al dashboard, inicializar slider después de un momento
            if (seccion === 'dashboard') {
                setTimeout(() => {
                    console.log('Inicializando slider para dashboard');
                    initSlider();
                }, 300);
            }

            // Scroll to top
            const pageInner = document.querySelector('.page-inner');
            if (pageInner) {
                pageInner.scrollTop = 0;
            }
        }

        // Initialize slider solo cuando sea necesario
        function initSlider() {
            const dashboardSection = document.getElementById('dashboard-section');
            if (!dashboardSection || !dashboardSection.classList.contains('active') || sliderInitialized) {
                return;
            }

            slides = document.querySelectorAll('.slide');
            dots = document.querySelectorAll('.dot');
            totalSlides = slides.length;

            if (totalSlides === 0) {
                console.log('No se encontraron slides');
                return;
            }

            console.log(`Inicializando slider con ${totalSlides} slides`);

            sliderInitialized = true;
            showSlide(0);
            startAutoSlide();

            // Pause on hover
            const sliderContainer = document.querySelector('.hospital-slider-wrapper');
            if (sliderContainer) {
                sliderContainer.addEventListener('mouseenter', stopAutoSlide);
                sliderContainer.addEventListener('mouseleave', startAutoSlide);
            }
        }

        // Show specific slide
        function showSlide(index) {
            if (!sliderInitialized || totalSlides === 0) return;

            // Hide all slides
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));

            // Show current slide
            if (slides[index]) {
                slides[index].classList.add('active');
                dots[index].classList.add('active');
            }

            currentSlideIndex = index;
            console.log(`Mostrando slide ${index + 1}`);
        }

        // Change slide - FUNCIÓN PRINCIPAL PARA LAS FLECHAS
        function changeSlide(direction) {
            if (!sliderInitialized) {
                console.log('Slider no inicializado');
                return;
            }

            console.log(`Cambiando slide en dirección: ${direction}`);
            stopAutoSlide();

            currentSlideIndex += direction;

            if (currentSlideIndex >= totalSlides) {
                currentSlideIndex = 0;
            } else if (currentSlideIndex < 0) {
                currentSlideIndex = totalSlides - 1;
            }

            showSlide(currentSlideIndex);
            startAutoSlide();
        }

        // Go to specific slide - FUNCIÓN PARA LOS DOTS
        function currentSlide(index) {
            if (!sliderInitialized) {
                console.log('Slider no inicializado');
                return;
            }

            console.log(`Yendo al slide ${index}`);
            stopAutoSlide();
            showSlide(index - 1);
            startAutoSlide();
        }

        // Auto slide functionality
        function startAutoSlide() {
            if (!sliderInitialized) return;

            stopAutoSlide();
            slideInterval = setInterval(() => {
                changeSlide(1);
            }, 5000); // Change slide every 5 seconds
            console.log('Auto-slide iniciado');
        }

        function stopAutoSlide() {
            if (slideInterval) {
                clearInterval(slideInterval);
                slideInterval = null;
                console.log('Auto-slide detenido');
            }
        } // Reset slider when changing sections
        function resetSlider() {
            console.log('Reseteando slider');
            stopAutoSlide();
            sliderInitialized = false;
            currentSlideIndex = 0;
        }

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function () {
            console.log('DOM cargado, esperando para inicializar slider...');

            // Solo inicializar si estamos en dashboard por defecto
            setTimeout(() => {
                const dashboardSection = document.getElementById('dashboard-section');
                if (dashboardSection && dashboardSection.classList.contains('active')) {
                    console.log('Dashboard está activo, inicializando slider');
                    initSlider();
                }
            }, 1500);
        });

        // Handle page visibility changes
        document.addEventListener('visibilitychange', function () {
            if (!sliderInitialized) return;

            if (document.visibilityState === 'visible') {
                startAutoSlide();
            } else {
                stopAutoSlide();
            }
        });

        // Handle keyboard navigation
        document.addEventListener('keydown', function (e) {
            if (!sliderInitialized) return;

            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                changeSlide(-1);
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                changeSlide(1);
            }
        });

        // Handle image loading errors gracefully
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(() => {
                document.querySelectorAll('.slide img').forEach((img, index) => {
                    img.addEventListener('error', function () {
                        console.log(`Error loading image ${index + 1}:`, this.src);
                        // En caso de error, mostrar gradiente de fondo
                        this.style.display = 'none';
                        this.parentElement.style.background =
                            `linear-gradient(135deg, var(--primary-blue), var(--secondary-blue))`;
                    });

                    img.addEventListener('load', function () {
                        console.log(`Imagen ${index + 1} cargada correctamente`);
                    });
                });
            }, 1000);
        });

        console.log('🖼️ Hospital Slider script cargado completamente');
    </script>


    <script>
        // Hide loading overlay when page loads
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(function () {
                document.getElementById('loadingOverlay').style.display = 'none';
            }, 1000);
        });

        // Variables globales para instancias de Chart.js
        let serviciosChartInstance;
        let ingresosMesChartInstance;
        let diagnosticosChartInstance;
        let ingresosMesChartDetalleInstance;

        let datosGlobales = null; // Para almacenar los datos del dashboard

        // Paleta de colores azules para los gráficos
        const paletaAzules = [
            '#1e3a8a', // Azul principal
            '#3b82f6', // Azul secundario
            '#60a5fa', // Azul claro
            '#93c5fd', // Azul más claro
            '#dbeafe', // Azul muy claro
            '#1e40af', // Azul oscuro
            '#2563eb', // Azul medio
            '#3730a3', // Azul púrpura
            '#4338ca', // Azul violeta
            '#5b21b6' // Púrpura
        ];

        document.addEventListener('DOMContentLoaded', function () {
            // Inicializar y cargar datos del dashboard al cargar la página
            actualizarDashboard();
            cargarCasosActivos();
            setupIngresosFilters(); // Configurar los filtros de ingresos
            iniciarActualizacionAutomatica();
            configurarBusquedaRapida();

            // Mensaje de bienvenida
            setTimeout(() => {
                mostrarNotificacion('🏥 Sistema HLO cargado correctamente', 'success');
            }, 2000);
        });


        // Función principal para actualizar estadísticas del dashboard
        function actualizarDashboard() {
            // Mostrar loading en los contadores
            document.getElementById('total-pacientes').innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            document.getElementById('casos-activos').innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            document.getElementById('ingresos-hoy').innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            document.getElementById('casos-urgentes').innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            // Obtener datos reales del servidor
            fetch('api/dashboard/estadisticas.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ok') {
                        datosGlobales = data.data; // Almacenar datos globalmente

                        // Animar contadores
                        animateCountUp('total-pacientes', datosGlobales.total_pacientes);
                        animateCountUp('casos-activos', datosGlobales.casos_activos);
                        animateCountUp('ingresos-hoy', datosGlobales.ingresos_hoy);
                        animateCountUp('casos-urgentes', datosGlobales.casos_urgentes);

                        // Inicializar o actualizar gráficos
                        inicializarGraficos(); // Asegurarse de que los objetos Chart existan
                        actualizarGraficosConDatos(datosGlobales); // Llenar con datos

                        mostrarNotificacion('📊 Dashboard actualizado correctamente', 'success');
                    } else {
                        console.error('Error al cargar estadísticas:', data.message);
                        mostrarNotificacion('❌ Error al actualizar dashboard', 'error');
                        // Resetear contadores a 0
                        document.getElementById('total-pacientes').textContent = '0';
                        document.getElementById('casos-activos').textContent = '0';
                        document.getElementById('ingresos-hoy').textContent = '0';
                        document.getElementById('casos-urgentes').textContent = '0';
                    }
                })
                .catch(error => {
                    console.error('Error de conexión:', error);
                    mostrarNotificacion('❌ Error de conexión al cargar dashboard', 'error');
                    // Resetear contadores a 0
                    document.getElementById('total-pacientes').textContent = '0';
                    document.getElementById('casos-activos').textContent = '0';
                    document.getElementById('ingresos-hoy').textContent = '0';
                    document.getElementById('casos-urgentes').textContent = '0';
                });
        }

        // Función para actualizar gráficos con datos (se llama después de fetch)
        function actualizarGraficosConDatos(stats) {
            // Gráfico de servicios
            if (serviciosChartInstance && stats.servicios) {
                const labels = stats.servicios.map(s => s.servicio);
                const data = stats.servicios.map(s => s.total);
                const backgroundColors = labels.map((_, index) => paletaAzules[index % paletaAzules.length]);

                serviciosChartInstance.data.labels = labels;
                serviciosChartInstance.data.datasets[0].data = data;
                serviciosChartInstance.data.datasets[0].backgroundColor = backgroundColors;
                serviciosChartInstance.update('active');
            }

            // Gráfico de diagnósticos
            if (diagnosticosChartInstance && stats.diagnosticos_frecuentes) {
                const labels = stats.diagnosticos_frecuentes.map(d => d.diagnostico.length > 25 ? d.diagnostico.substring(0,
                    25) + '...' : d.diagnostico);
                const data = stats.diagnosticos_frecuentes.map(d => d.total);
                const backgroundColors = labels.map((_, index) => paletaAzules[index % paletaAzules.length]);

                diagnosticosChartInstance.data.labels = labels;
                diagnosticosChartInstance.data.datasets[0].data = data;
                diagnosticosChartInstance.data.datasets[0].backgroundColor = backgroundColors;
                diagnosticosChartInstance.update('active');
            }

            // Cargar datos para los gráficos de ingresos por mes (se llaman por separado para los filtros)
            cargarIndiceIngresosDashboard(); // Carga datos para ingresosMesChartInstance
            cargarIndiceIngresosStats(); // Carga datos para ingresosMesChartDetalleInstance
        }

        // Animación de contadores
        function animateCountUp(elementId, targetValue) {
            const element = document.getElementById(elementId);
            let currentValue = 0;
            const increment = targetValue / 50; // Ajusta la velocidad de la animación
            const timer = setInterval(() => {
                currentValue += increment;
                if (currentValue >= targetValue) {
                    currentValue = targetValue;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(currentValue).toLocaleString();
            }, 20);
        }
        // ✅ FUNCIÓN: Ver caso en iframe desde casos activos (REEMPLAZA LA FUNCIÓN ACTUAL)
        function verCaso(numeroCaso) {
            // Obtener datos del caso desde la fila de la tabla
            const fila = event.target.closest('tr');
            const pacienteNombre = fila.cells[1].textContent.trim();
            const servicio = fila.querySelector('.badge.bg-info').textContent.trim();

            // Ocultar el contenido actual
            $('.card').hide();
            $('.page-header').hide();

            // Crear el iframe container
            const iframeContainer = `
        <div id="casoContainer">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    
                    <div class="btn-group">
                        <button id="imprimirCaso" class="btn btn-info btn-md">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                         </div>
                          <div class="btn-group">
                        <button id="volverCasosActivos" class="btn btn-secondary btn-md">
                            <i class="fas fa-arrow-left"></i> Volver a Casos
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <iframe id="casoIframe" 
                            src="modulos/procesos/dashboard_procesos.php?caso=${numeroCaso}" 
                            width="100%" 
                            frameborder="0" 
                            scrolling="no"
                            style="border: none; display: block; overflow: hidden;">
                    </iframe>
                </div>
            </div>
        </div>
    `;

            // Insertar el iframe en el contenedor principal
            $('.page-inner').append(iframeContainer);

            // Ajustar altura automáticamente
            $('#casoIframe').on('load', function () {
                try {
                    const iframe = this;
                    const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                    const height = iframeDoc.body.scrollHeight;
                    $(iframe).height(height + 50);
                } catch (e) {
                    $(this).height(800); // Altura por defecto si hay error
                }
            });

            // Funcionalidad del botón imprimir
            $('#imprimirCaso').on('click', function () {
                try {
                    const iframe = document.getElementById('casoIframe');
                    const iframeWindow = iframe.contentWindow || iframe.contentDocument.defaultView;

                    if (iframeWindow) {
                        iframeWindow.focus();
                        iframeWindow.print();
                    } else {
                        // Fallback: abrir en nueva ventana
                        const printWindow = window.open(
                            `modulos/procesos/dashboard_procesos.php?caso=${numeroCaso}&print=1`,
                            '_blank',
                            'width=800,height=600'
                        );
                        printWindow.onload = function () {
                            printWindow.print();
                            printWindow.close();
                        };
                    }
                } catch (e) {
                    console.error('Error al imprimir:', e);
                    Swal.fire('Error', 'No se pudo imprimir el caso médico', 'error');
                }
            });

            // Funcionalidad del botón volver
            $('#volverCasosActivos').on('click', function () {
                $('#casoContainer').remove();
                $('.page-header').show();
                $('.card').show();
            });
        }



        function verPaciente(idPaciente) {
            Swal.fire({
                title: '👤 Ver Paciente',
                text: `Cargando información del paciente...`,
                icon: 'info',
                timer: 1500,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            }).then(() => {
                window.open(`modulos/procesos/dashboard_procesos.php?caso=${idPaciente}`, '_blank');
            });
        }

        function nuevoIngresoPaciente(idPaciente) {
            Swal.fire({
                title: '🏥 Nuevo Ingreso',
                text: '¿Desea crear un nuevo ingreso para este paciente?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, crear ingreso',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: 'var(--success-green)',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `historia_medica.php?paciente=${idPaciente}`;
                }
            });
        }
        // Cargar casos activos con datos reales
        function cargarCasosActivos() {
            const tbody = document.getElementById('tabla-casos-activos');
            const userRole = '<?php echo $_SESSION['usuario_rol']; ?>';

            const permissions = {
                'administrador': {
                    verCaso: true
                },
                'dirección': {
                    verCaso: true
                },
                'medicina': {
                    verCaso: true
                },
                'enfermería': {
                    verCaso: true
                },
                'estadística en salud': {
                    verCaso: false
                }
            };
            const userPermissions = permissions[userRole] || {
                verCaso: false
            };

            tbody.innerHTML =
                '<tr><td colspan="9" class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando casos activos...</td></tr>';

            fetch('api/dashboard/casos_activos.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ok') {
                        const casos = data.data;

                        if (casos.length === 0) {
                            tbody.innerHTML =
                                '<tr><td colspan="9" class="text-center text-muted">No hay casos activos en este momento.</td></tr>';
                            return;
                        }

                        if (!userPermissions.verCaso) {
                            tbody.innerHTML = `
                                <tr>
                                    <td colspan="9" class="text-center text-warning p-4">
                                        <i class="fas fa-lock fa-3x mb-3"></i>
                                        <p><strong>Acceso Restringido</strong></p>
                                        <p>Su rol <strong>${userRole}</strong> no tiene permisos para ver casos activos.</p>
                                        <small>Solo puede acceder a historias médicas completas de casos cerrados.</small>
                                    </td>
                                </tr>
                            `;
                            return;
                        }

                        let html = '';
                        casos.forEach(caso => {
                            const prioridadClass = caso.prioridad === 'critica' ? 'danger' : caso.prioridad ===
                                'alta' ? 'warning' : caso.prioridad === 'media' ? 'info' : 'success';
                            const estadoClass = caso.estado === 'activo' ? 'success' : 'warning';
                            const numeroCasoCompleto = caso.numero_caso || '';
                            const numeroCasoSecuencial = numeroCasoCompleto ? numeroCasoCompleto.split('-')[
                                1] || '' : '';

                            let botonesHTML = '';
                            if (userPermissions.verCaso) {
                                botonesHTML = `
                                 <button class="btn btn-sm btn-primary"
        onclick="verCaso('${numeroCasoSecuencial}')"
        title="Ver Caso ${numeroCasoSecuencial}">
    <i class="fas fa-eye"></i> Ver
</button>
                                `;
                            }

                            html += `
                                <tr>
                                    <td><strong style="color: var(--primary-blue);">${numeroCasoCompleto}</strong></td>
                                    <td>${caso.paciente}</td>
                                    <td class="d-none d-md-table-cell">${caso.cedula}</td>
                                    <td class="d-none d-lg-table-cell">${caso.fecha_ingreso}</td>
                                    <td><span class="badge bg-info">${caso.servicio}</span></td>
                                    <td class="d-none d-xl-table-cell"><small>${caso.medico}</small></td>
                                    <td><span class="badge bg-${estadoClass}">${caso.estado}</span></td>
                                    <td><span class="badge bg-${prioridadClass}">${caso.prioridad}</span></td>
                                    <td>${botonesHTML}</td>
                                </tr>
                            `;
                        });

                        tbody.innerHTML = html;

                    } else {
                        console.error('Error reportado por el servidor:', data.message);
                        tbody.innerHTML =
                            '<tr><td colspan="9" class="text-center text-danger">Error al cargar los datos de los casos.</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error en la petición fetch:', error);
                    tbody.innerHTML =
                        '<tr><td colspan="9" class="text-center text-danger">No se pudo conectar con el servidor para obtener los casos.</td></tr>';
                });
        }
        // ✅ FUNCIÓN: Mostrar historia en iframe (ADAPTADA PARA INDEX.PHP)
        function mostrarHistoriaEnIframe(url, paciente_nombre, tipo = 'caso') {
            // Ocultar SOLO el contenido de la sección buscar-paciente, NO el sidebar ni navbar
            $('#buscar-paciente-section .card').hide();

            // Determinar el título según el tipo
            const titulo = tipo === 'historia' ? 'Historia Médica Completa' : 'Historia Médica - Caso Actual';

            // Crear el contenedor TODO EN UN SOLO BLOQUE
            const iframeContainer = `
        <div id="historiaContainer">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1">
                            <i class="fas fa-file-medical text-primary"></i> ${titulo}
                        </h4>
                        <small class="text-muted">Paciente: ${paciente_nombre}</small>
                    </div>
                    <div class="btn-group">
                        <button id="imprimirHistoria" class="btn btn-info btn-md">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                        <button id="volverBusqueda" class="btn btn-secondary btn-md">
                            <i class="fas fa-arrow-left"></i> Volver a Búsqueda
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

            // Insertar el iframe DENTRO de la sección buscar-paciente
            $('#buscar-paciente-section').append(iframeContainer);

            // Ajustar altura automáticamente cuando cargue el iframe
            $('#historiaIframe').on('load', function () {
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
            $('#imprimirHistoria').on('click', function () {
                try {
                    const iframe = document.getElementById('historiaIframe');
                    const iframeWindow = iframe.contentWindow || iframe.contentDocument.defaultView;

                    if (iframeWindow) {
                        iframeWindow.focus();
                        iframeWindow.print();
                    } else {
                        // Fallback: abrir en nueva ventana para imprimir
                        const printWindow = window.open(url + '&print=1', '_blank', 'width=800,height=600');
                        printWindow.onload = function () {
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
            $('#volverBusqueda').on('click', function () {
                $('#historiaContainer').remove();
                $('#buscar-paciente-section .card').show();
            });
        }

        // ✅ FUNCIÓN: Buscar pacientes con permisos CORREGIDOS
        function buscarPacientes() {
            const tipo = document.getElementById('tipo-busqueda').value;
            const termino = document.getElementById('termino-busqueda').value.trim();

            if (!termino) {
                Swal.fire({
                    title: '⚠️ Advertencia',
                    text: 'Ingrese un término de búsqueda',
                    icon: 'warning',
                    confirmButtonColor: 'var(--warning-orange)'
                });
                return;
            }

            const container = document.getElementById('resultados-busqueda');
            container.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Buscando...</div>';

            const formData = new FormData();
            formData.append('tipo', tipo);
            formData.append('termino', termino);

            fetch('api/dashboard/buscar_pacientes.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ok') {
                        const pacientes = data.data;

                        if (pacientes.length === 0) {
                            container.innerHTML = `
                        <div class="text-center text-muted p-4">
                            <i class="fas fa-search fa-3x mb-3" style="color: var(--light-blue);"></i>
                            <p>No se encontraron resultados para "${termino}"</p>
                            <small>Intente con otros términos de búsqueda</small>
                        </div>
                    `;
                            return;
                        }

                        // OBTENER ROL DEL USUARIO DESDE PHP
                        const userRole = '<?php echo $_SESSION['usuario_rol']; ?>';

                        // DEFINIR PERMISOS POR ROL - CORREGIDOS
                        const permissions = {
                            'administrador': {
                                verHistoria: true,
                                nuevoIngreso: true
                            },
                            'dirección': {
                                verHistoria: true,
                                nuevoIngreso: true
                            },
                            'medicina': {
                                verHistoria: true,
                                nuevoIngreso: true
                            },
                            'enfermería': {
                                verHistoria: true, // SÍ puede ver (solo casos actuales)
                                nuevoIngreso: false // NO puede crear nuevos ingresos
                            },
                            'estadística en salud': {
                                verHistoria: true, // SÍ puede ver (solo historias completas)
                                nuevoIngreso: false // NO puede crear nuevos ingresos
                            }
                        };

                        const currentUserPermissions = permissions[userRole] || {
                            verHistoria: false,
                            nuevoIngreso: false
                        };

                        let html = '';
                        pacientes.forEach(paciente => {
                            const casosActivosText = paciente.casos_activos > 0 ?
                                `<span class="badge bg-warning">${paciente.casos_activos} caso(s) activo(s)</span>` :
                                '<span class="badge bg-success">Sin casos activos</span>';

                            // GENERAR BOTONES SEGÚN PERMISOS CORREGIDOS
                            let botonesHTML = '';

                            // Botón Ver Historia (solo si tiene permiso)
                            if (currentUserPermissions.verHistoria) {
                                let buttonText = '';
                                let buttonTitle = '';

                                if (userRole === 'enfermería') {
                                    buttonText = '<i class="fas fa-eye me-1"></i>Ver Caso';
                                    buttonTitle = 'Ver Caso Actual (Solo casos activos)';
                                } else if (userRole === 'estadística en salud') {
                                    buttonText = '<i class="fas fa-file-medical me-1"></i>Ver Historia';
                                    buttonTitle = 'Ver Historia Completa (Solo casos cerrados)';
                                } else {
                                    buttonText = '<i class="fas fa-eye me-1"></i>Ver Historia';
                                    buttonTitle = 'Ver Historia Médica';
                                }

                                botonesHTML += `
                            <button class="btn btn-sm btn-primary ver-historia-btn" 
                                    data-id="${paciente.id_paciente}"
                                    data-role="${userRole}"
                                    title="${buttonTitle}">
                                ${buttonText}
                            </button>
                        `;
                            }

                            // Botón Nuevo Ingreso (solo si tiene permiso)
                            if (currentUserPermissions.nuevoIngreso) {
                                botonesHTML += `
                            <button class="btn btn-sm btn-success" 
                                    onclick="nuevoIngresoPaciente('${paciente.id_paciente}')" 
                                    title="Nuevo ingreso">
                                <i class="fas fa-plus me-1"></i>Nuevo Ingreso
                            </button>
                        `;
                            }

                            // Si no tiene permisos, mostrar mensaje
                            if (!currentUserPermissions.verHistoria && !currentUserPermissions.nuevoIngreso) {
                                botonesHTML = `
                            <small class="text-muted">
                                <i class="fas fa-lock me-1"></i>Sin permisos
                            </small>
                        `;
                            }

                            // Badge con información del rol para debugging
                            const roleInfo = `
                        <div class="mt-2">
                            <small class="text-info">
                                <i class="fas fa-user-tag me-1"></i>Acceso: ${userRole}
                            </small>
                        </div>
                    `;

                            html += `
                        <div class="border rounded p-3 mb-3" style="border-color: var(--light-blue) !important;">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong style="color: var(--primary-blue);">${paciente.nombre}</strong>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-id-card me-1"></i>Cédula: ${paciente.cedula} | 
                                        <i class="fas fa-file-medical me-1"></i>Historia: ${paciente.numero_historia}
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-phone me-1"></i>${paciente.telefono} | 
                                        <i class="fas fa-birthday-cake me-1"></i>${paciente.edad}
                                    </small>
                                    <br>
                                    <small class="text-info">
                                        <i class="fas fa-calendar me-1"></i>Última visita: ${paciente.ultima_visita}
                                    </small>
                                    <br>
                                    ${casosActivosText}
                                    ${roleInfo}
                                </div>
                                <div class="d-flex flex-column gap-1">
                                    ${botonesHTML}
                                </div>
                            </div>
                        </div>
                    `;
                        });

                        container.innerHTML = html;
                    } else {
                        container.innerHTML = `
                    <div class="text-center text-danger p-4">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                        <p>Error en la búsqueda: ${data.message}</p>
                    </div>
                `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    container.innerHTML = `
                <div class="text-center text-danger p-4">
                    <i class="fas fa-wifi fa-3x mb-3"></i>
                    <p>Error de conexión</p>
                    <small>Verifique su conexión a internet</small>
                </div>
            `;
                });
        }

        // ✅ LÓGICA COMPLETA: Event Listener Ver Historia con TODOS los escenarios
        $(document).on('click', '.ver-historia-btn', function () {
            const paciente_id = $(this).data('id');
            const userRole = $(this).data('role');

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
                url: 'modulos/historias/verificar_casos_cerrados.php',
                method: 'GET',
                data: {
                    paciente_id: paciente_id
                },
                dataType: 'json',
                success: function (response) {
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
                                    <div class="alert alert-light">
                                        <small>El paciente tiene casos cerrados y activos, pero su rol no permite acceder a ninguna opción.</small>
                                    </div>
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
                                    $('#btnHistoriaCompleta').on('click', function () {
                                        Swal.close();
                                        const urlHistoria =
                                            `modulos/historias/generar_historia_pdf.php?paciente_id=${paciente_id}&vista=iframe`;
                                        mostrarHistoriaEnIframe(urlHistoria,
                                            response.paciente_nombre, 'historia'
                                        );
                                    });

                                    $('#btnCasoActual').on('click', function () {
                                        Swal.close();
                                        if (response.caso_activo_id) {
                                            const urlCaso =
                                                `modulos/procesos/dashboard_procesos.php?caso=${response.caso_activo_id}`;
                                            mostrarHistoriaEnIframe(urlCaso,
                                                response.paciente_nombre, 'caso'
                                            );
                                        } else {
                                            Swal.fire('Error',
                                                'No se pudo obtener el ID del caso activo',
                                                'error');
                                        }
                                    });

                                    $('#btnNuevoIngreso').on('click', function () {
                                        Swal.close();
                                        nuevoIngresoPaciente(paciente_id);
                                    });

                                    // Event listener para el botón Cancelar - CORREGIDO
                                    $('#btnCancelar').on('click', function () {
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
                                let denyButton = false;
                                let denyButtonText = '';

                                if (permissions.nuevoIngreso) {
                                    denyButton = true;
                                    denyButtonText = '<i class="fas fa-plus"></i> Nuevo Ingreso';
                                }

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
                                            `modulos/historias/generar_historia_pdf.php?paciente_id=${paciente_id}&vista=iframe`;
                                        mostrarHistoriaEnIframe(urlHistoria, response
                                            .paciente_nombre, 'historia');
                                    } else if (result.isDenied) {
                                        nuevoIngresoPaciente(paciente_id);
                                    }
                                });
                            } else {
                                // NO puede ver historia completa (caso enfermería)
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
                                            nuevoIngresoPaciente(paciente_id);
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
                                            <small>El paciente solo tiene casos cerrados y su rol no permite ver historias completas ni crear ingresos.</small>
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
                                let denyButton = false;
                                let denyButtonText = '';

                                if (permissions.nuevoIngreso) {
                                    denyButton = true;
                                    denyButtonText = '<i class="fas fa-plus"></i> Nuevo Ingreso';
                                }

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
                                            mostrarHistoriaEnIframe(urlCaso, response
                                                .paciente_nombre, 'caso');
                                        } else {
                                            Swal.fire('Error',
                                                'No se pudo obtener el ID del caso activo',
                                                'error');
                                        }
                                    } else if (result.isDenied) {
                                        nuevoIngresoPaciente(paciente_id);
                                    }
                                });
                            } else {
                                // NO puede ver casos activos (caso estadística)
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
                                        nuevoIngresoPaciente(paciente_id);
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
                error: function (xhr, status, error) {
                    Swal.close();
                    console.error('Error AJAX:', error);
                    Swal.fire('Error de conexión', 'No se pudo conectar con el servidor', 'error');
                }
            });
        });

        // ✅ FUNCIÓN: Nuevo ingreso con verificación de permisos CORREGIDA
        function nuevoIngresoPaciente(idPaciente) {
            // Obtener rol del usuario
            const userRole = '<?php echo $_SESSION['usuario_rol']; ?>';

            // Roles que pueden crear nuevos ingresos - CORREGIDO
            const rolesPermitidos = ['administrador', 'dirección', 'medicina'];

            if (!rolesPermitidos.includes(userRole)) {
                Swal.fire({
                    title: '🔒 Sin Permisos para Crear Ingresos',
                    html: `
                <div class="text-center">
                    <i class="fas fa-user-times fa-4x text-warning mb-3"></i>
                    <p>Su rol <strong>${userRole}</strong> no tiene permisos para crear nuevos ingresos.</p>
                    <div class="alert alert-light">
                        <small><strong>Roles autorizados para crear ingresos:</strong><br>
                        • Administrador<br>
                        • Dirección<br>
                        • Medicina</small>
                    </div>
                    <div class="alert alert-info">
                        <small><i class="fas fa-info-circle"></i> 
                        ${userRole === 'enfermería' ? 'Enfermería puede ver casos activos pero no crear nuevos ingresos.' :
                            userRole === 'estadística en salud' ? 'Estadística puede ver historias completas pero no crear ingresos.' :
                                'Su rol tiene permisos limitados en el sistema.'}
                        </small>
                    </div>
                </div>
            `,
                    icon: 'warning',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#6c757d'
                });
                return;
            }

            Swal.fire({
                title: '🏥 Nuevo Ingreso',
                html: `
            <div class="text-left">
                <p>¿Desea crear un nuevo ingreso para este paciente?</p>
                <div class="alert alert-info">
                    <small><i class="fas fa-user-tag me-2"></i><strong>Autorizado por:</strong> ${userRole}</small>
                </div>
                <div class="alert alert-warning">
                    <small><i class="fas fa-exclamation-triangle me-2"></i><strong>Nota:</strong> Se creará un nuevo caso médico para el paciente.</small>
                </div>
            </div>
        `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, crear ingreso',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: 'var(--success-green)',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar loading antes de redireccionar
                    Swal.fire({
                        title: 'Redirigiendo...',
                        text: 'Preparando formulario de ingreso',
                        icon: 'info',
                        timer: 1500,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    }).then(() => {
                        window.location.href =
                            `modulos/ingreso/formulario_ingreso.php?paciente=${idPaciente}`;
                    });
                }
            });
        }
        // --- Funciones de Gráficos (Chart.js) ---

        // Función para inicializar todos los gráficos Chart.js
        function inicializarGraficos() {
            // Destruir instancias existentes para evitar duplicados y errores
            if (serviciosChartInstance) serviciosChartInstance.destroy();
            if (ingresosMesChartInstance) ingresosMesChartInstance.destroy();
            if (diagnosticosChartInstance) diagnosticosChartInstance.destroy();
            if (ingresosMesChartDetalleInstance) ingresosMesChartDetalleInstance.destroy();

            // Configuración base responsive para todos los gráficos
            const baseResponsiveConfig = {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                },
                // Añadir devicePixelRatio para mayor nitidez
                devicePixelRatio: window.devicePixelRatio || 1
            };

            // Gráfico de servicios (doughnut)
            const ctx1 = document.getElementById('serviciosChart');
            if (ctx1) {
                serviciosChartInstance = new Chart(ctx1, {
                    type: 'doughnut',
                    data: {
                        labels: ['Cargando...'],
                        datasets: [{
                            data: [1],
                            backgroundColor: [paletaAzules[0]],
                            borderWidth: 3,
                            borderColor: '#ffffff',
                            hoverBorderWidth: 5,
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        ...baseResponsiveConfig,
                        cutout: '60%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 15,
                                    font: {
                                        size: window.innerWidth <= 768 ? 10 : 12
                                    },
                                    boxWidth: window.innerWidth <= 768 ? 12 : 15,
                                    usePointStyle: true
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(30, 58, 138, 0.9)',
                                titleColor: 'white',
                                bodyColor: 'white',
                                borderColor: paletaAzules[1],
                                borderWidth: 2,
                                cornerRadius: 8,
                                padding: 12,
                                titleFont: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                bodyFont: {
                                    size: 13
                                },
                                callbacks: {
                                    label: function (context) {
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((context.parsed * 100) / total).toFixed(1);
                                        return `${context.label}: ${context.parsed} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Gráfico de ingresos por mes (línea - dashboard principal)
            const ctx2 = document.getElementById('ingresosMesChart');
            if (ctx2) {
                ingresosMesChartInstance = new Chart(ctx2, {
                    type: 'line',
                    data: {
                        labels: ['Cargando...'],
                        datasets: [{
                            label: 'Ingresos',
                            data: [0],
                            borderColor: paletaAzules[0],
                            backgroundColor: `${paletaAzules[0]}20`,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: paletaAzules[1],
                            pointBorderColor: paletaAzules[0],
                            pointRadius: window.innerWidth <= 768 ? 4 : 6,
                            pointHoverRadius: window.innerWidth <= 768 ? 6 : 8,
                            borderWidth: 3,
                            pointBorderWidth: 2
                        }]
                    },
                    options: {
                        ...baseResponsiveConfig,
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(30, 58, 138, 0.1)',
                                    lineWidth: 1
                                },
                                ticks: {
                                    font: {
                                        size: window.innerWidth <= 768 ? 10 : 12
                                    },
                                    color: '#64748b'
                                }
                            },
                            x: {
                                grid: {
                                    color: 'rgba(30, 58, 138, 0.05)',
                                    lineWidth: 1
                                },
                                ticks: {
                                    font: {
                                        size: window.innerWidth <= 768 ? 10 : 12
                                    },
                                    color: '#64748b',
                                    maxRotation: window.innerWidth <= 768 ? 45 : 0
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }, // Oculta la leyenda
                            tooltip: {
                                backgroundColor: 'rgba(30, 58, 138, 0.9)',
                                titleColor: 'white',
                                bodyColor: 'white',
                                cornerRadius: 8,
                                padding: 12,
                                callbacks: {
                                    label: function (context) {
                                        return `Cantidad de pacientes: ${context.parsed}`; // Personaliza el texto del tooltip
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Gráfico detallado de ingresos (barra - sección estadísticas)
            const ctx3 = document.getElementById('ingresosMesChartDetalle');
            if (ctx3) {
                ingresosMesChartDetalleInstance = new Chart(ctx3, {
                    type: 'bar',
                    data: {
                        labels: ['Cargando...'],
                        datasets: [{
                            label: 'Ingresos',
                            data: [0],
                            backgroundColor: paletaAzules[0],
                            borderColor: paletaAzules[0],
                            borderWidth: 2,
                            borderRadius: 6,
                            borderSkipped: false,
                            hoverBackgroundColor: paletaAzules[1],
                            hoverBorderColor: paletaAzules[1]
                        }]
                    },
                    options: {
                        ...baseResponsiveConfig,
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(30, 58, 138, 0.1)'
                                },
                                ticks: {
                                    font: {
                                        size: window.innerWidth <= 768 ? 10 : 12
                                    },
                                    color: '#64748b'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        size: window.innerWidth <= 768 ? 10 : 12
                                    },
                                    color: '#64748b',
                                    maxRotation: window.innerWidth <= 768 ? 45 : 0
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }, // Oculta la leyenda
                            tooltip: {
                                backgroundColor: 'rgba(30, 58, 138, 0.9)',
                                titleColor: 'white',
                                bodyColor: 'white',
                                cornerRadius: 8,
                                padding: 12,
                                callbacks: {
                                    label: function (context) {
                                        return `Cantidad de pacientes: ${context.parsed}`; // Personaliza el texto del tooltip
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Gráfico de diagnósticos frecuentes (pie)
            const ctx4 = document.getElementById('diagnosticosChart');
            if (ctx4) {
                diagnosticosChartInstance = new Chart(ctx4, {
                    type: 'pie',
                    data: {
                        labels: ['Cargando...'],
                        datasets: [{
                            data: [1],
                            backgroundColor: [paletaAzules[0]],
                            borderWidth: 2,
                            borderColor: '#ffffff',
                            hoverOffset: 8
                        }]
                    },
                    options: {
                        ...baseResponsiveConfig,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: window.innerWidth <= 768 ? 8 : 12,
                                    font: {
                                        size: window.innerWidth <= 768 ? 9 : 11
                                    },
                                    boxWidth: window.innerWidth <= 768 ? 10 : 12,
                                    usePointStyle: true
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(30, 58, 138, 0.9)',
                                titleColor: 'white',
                                bodyColor: 'white',
                                borderColor: paletaAzules[1],
                                borderWidth: 2,
                                cornerRadius: 8,
                                padding: 12,
                                callbacks: {
                                    label: function (context) {
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((context.parsed * 100) / total).toFixed(1);
                                        return `${context.label}: ${context.parsed} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            console.log('📊 Gráficos inicializados con responsive y paleta azul');
        }

        // Función para actualizar los datos de un gráfico de línea/barra específico
        function updateChartWithData(chartInstance, dataArray) {
            if (!chartInstance) return;

            const labels = dataArray.map(item => item.mes || item.diagnostico); // Generaliza para mes o diagnóstico
            const dataValues = dataArray.map(item => item.total);

            chartInstance.data.labels = labels;
            chartInstance.data.datasets[0].data = dataValues;

            // Si es un gráfico de pie/doughnut, actualizar colores
            if (chartInstance.config.type === 'doughnut' || chartInstance.config.type === 'pie') {
                const backgroundColors = labels.map((_, index) => paletaAzules[index % paletaAzules.length]);
                chartInstance.data.datasets[0].backgroundColor = backgroundColors;
            } else if (chartInstance.config.type === 'bar') {
                const backgroundColors = labels.map((_, index) => paletaAzules[index % paletaAzules.length]);
                chartInstance.data.datasets[0].backgroundColor = backgroundColors;
            }

            chartInstance.update('active');
        }


        // --- Lógica para el filtro de Ingresos ---

        // Función para inicializar los selectores de período
        function setupIngresosFilters() {
            // Selectores de la sección de estadísticas
            const periodoSelect = document.getElementById('periodoIngresos');
            const fechaInicioInput = document.getElementById('fechaInicioIngresos');
            const fechaFinInput = document.getElementById('fechaFinIngresos');
            const aplicarFiltroBtn = document.getElementById('aplicarFiltroIngresos');

            // Selectores del dashboard principal
            const periodoSelectDashboard = document.getElementById('periodoIngresosDashboard');
            const fechaInicioInputDashboard = document.getElementById('fechaInicioIngresosDashboard');
            const fechaFinInputDashboard = document.getElementById('fechaFinIngresosDashboard');
            const aplicarFiltroBtnDashboard = document.getElementById('aplicarFiltroIngresosDashboard');

            // Listener para el selector de período en Estadísticas
            if (periodoSelect) {
                periodoSelect.addEventListener('change', function () {
                    toggleDatePickers('estadisticas');
                    if (this.value !== 'custom') {
                        cargarIndiceIngresosStats(); // Cargar automáticamente cuando no es personalizado
                    }
                });
            }

            // Listener para el botón Aplicar en Estadísticas
            if (aplicarFiltroBtn) {
                aplicarFiltroBtn.addEventListener('click', function () {
                    if (periodoSelect.value === 'custom') {
                        if (!fechaInicioInput.value || !fechaFinInput.value) {
                            Swal.fire('Advertencia',
                                'Debe seleccionar una fecha de inicio y fin para el rango personalizado.',
                                'warning');
                            return;
                        }
                        if (new Date(fechaInicioInput.value) > new Date(fechaFinInput.value)) {
                            Swal.fire('Advertencia', 'La fecha de inicio no puede ser posterior a la fecha de fin.',
                                'warning');
                            return;
                        }
                        cargarIndiceIngresosStats();
                    }
                });
            }

            // Listener para el selector de período en Dashboard
            if (periodoSelectDashboard) {
                periodoSelectDashboard.addEventListener('change', function () {
                    toggleDatePickers('dashboard');
                    if (this.value !== 'custom') {
                        cargarIndiceIngresosDashboard(); // Cargar automáticamente cuando no es personalizado
                    }
                });
            }

            // Listener para el botón Aplicar en Dashboard
            if (aplicarFiltroBtnDashboard) {
                aplicarFiltroBtnDashboard.addEventListener('click', function () {
                    if (periodoSelectDashboard.value === 'custom') {
                        if (!fechaInicioInputDashboard.value || !fechaFinInputDashboard.value) {
                            Swal.fire('Advertencia',
                                'Debe seleccionar una fecha de inicio y fin para el rango personalizado.',
                                'warning');
                            return;
                        }
                        if (new Date(fechaInicioInputDashboard.value) > new Date(fechaFinInputDashboard.value)) {
                            Swal.fire('Advertencia', 'La fecha de inicio no puede ser posterior a la fecha de fin.',
                                'warning');
                            return;
                        }
                        cargarIndiceIngresosDashboard();
                    }
                });
            }

            // Establecer fechas por defecto para el rango personalizado (hoy - 6 meses)
            const today = new Date();
            const sixMonthsAgo = new Date();
            sixMonthsAgo.setMonth(today.getMonth() - 6);

            const formatDate = (date) => date.toISOString().split('T')[0];

            if (fechaInicioInput) fechaInicioInput.value = formatDate(sixMonthsAgo);
            if (fechaFinInput) fechaFinInput.value = formatDate(today);
            if (fechaInicioInputDashboard) fechaInicioInputDashboard.value = formatDate(sixMonthsAgo);
            if (fechaFinInputDashboard) fechaFinInputDashboard.value = formatDate(today);


            // Asegurar que la visibilidad inicial sea correcta
            toggleDatePickers('estadisticas');
            toggleDatePickers('dashboard');
        }

        // Función para controlar la visibilidad de los campos de fecha
        function toggleDatePickers(context) {
            const periodoSelect = document.getElementById(`periodoIngresos${context === 'dashboard' ? 'Dashboard' : ''}`);
            const fechaInicioInput = document.getElementById(
                `fechaInicioIngresos${context === 'dashboard' ? 'Dashboard' : ''}`);
            const fechaFinInput = document.getElementById(`fechaFinIngresos${context === 'dashboard' ? 'Dashboard' : ''}`);
            const aplicarFiltroBtn = document.getElementById(
                `aplicarFiltroIngresos${context === 'dashboard' ? 'Dashboard' : ''}`);

            if (periodoSelect && fechaInicioInput && fechaFinInput && aplicarFiltroBtn) {
                if (periodoSelect.value === 'custom') {
                    fechaInicioInput.style.display = 'block';
                    fechaFinInput.style.display = 'block';
                    aplicarFiltroBtn.style.display = 'block';
                } else {
                    fechaInicioInput.style.display = 'none';
                    fechaFinInput.style.display = 'none';
                    aplicarFiltroBtn.style.display = 'none';
                    // No limpiar los valores de fecha, solo ocultarlos
                }
            }
        }

        // Función para cargar los datos del gráfico de ingresos del dashboard principal
        function cargarIndiceIngresosDashboard() {
            const periodoSelect = document.getElementById('periodoIngresosDashboard');
            const fechaInicioInput = document.getElementById('fechaInicioIngresosDashboard');
            const fechaFinInput = document.getElementById('fechaFinIngresosDashboard');

            let params = `?periodo=${periodoSelect.value}`;
            if (periodoSelect.value === 'custom') {
                params += `&fecha_inicio=${fechaInicioInput.value}&fecha_fin=${fechaFinInput.value}`;
            }

            fetch(`api/dashboard/estadisticas.php${params}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ok' && ingresosMesChartInstance) {
                        updateChartWithData(ingresosMesChartInstance, data.data.ingresos_por_mes);
                    } else {
                        console.error('Error al cargar datos de ingresos del dashboard:', data.message);
                        if (ingresosMesChartInstance) {
                            ingresosMesChartInstance.data.labels = ['Sin datos'];
                            ingresosMesChartInstance.data.datasets[0].data = [0];
                            ingresosMesChartInstance.update('active');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error de conexión al cargar ingresos del dashboard:', error);
                    if (ingresosMesChartInstance) {
                        ingresosMesChartInstance.data.labels = ['Error'];
                        ingresosMesChartInstance.data.datasets[0].data = [0];
                        ingresosMesChartInstance.update('active');
                    }
                });
        }

        // Función para cargar los datos del gráfico de ingresos de la sección de estadísticas
        function cargarIndiceIngresosStats() {
            const periodoSelect = document.getElementById('periodoIngresos');
            const fechaInicioInput = document.getElementById('fechaInicioIngresos');
            const fechaFinInput = document.getElementById('fechaFinIngresos');

            let params = `?periodo=${periodoSelect.value}`;
            if (periodoSelect.value === 'custom') {
                params += `&fecha_inicio=${fechaInicioInput.value}&fecha_fin=${fechaFinInput.value}`;
            }

            fetch(`api/dashboard/estadisticas.php${params}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ok' && ingresosMesChartDetalleInstance) {
                        updateChartWithData(ingresosMesChartDetalleInstance, data.data.ingresos_por_mes);
                    } else {
                        console.error('Error al cargar datos de ingresos de estadísticas:', data.message);
                        if (ingresosMesChartDetalleInstance) {
                            ingresosMesChartDetalleInstance.data.labels = ['Sin datos'];
                            ingresosMesChartDetalleInstance.data.datasets[0].data = [0];
                            ingresosMesChartDetalleInstance.update('active');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error de conexión al cargar ingresos de estadísticas:', error);
                    if (ingresosMesChartDetalleInstance) {
                        ingresosMesChartDetalleInstance.data.labels = ['Error'];
                        ingresosMesChartDetalleInstance.data.datasets[0].data = [0];
                        ingresosMesChartDetalleInstance.update('active');
                    }
                });
        }

        // Función para inicializar los gráficos cuando el DOM esté listo
        // y para asegurarse de que los datos se carguen después de la inicialización.
        document.addEventListener('DOMContentLoaded', function () {
            inicializarGraficos(); // Crear las instancias de Chart.js
            actualizarDashboard(); // Esto hará un fetch inicial y llamará a actualizarGraficosConDatos
            cargarCasosActivos(); // Cargar la tabla de casos activos
            setupIngresosFilters(); // Configurar los selectores y listeners
        });


        // Mejorar el resize handler
        window.addEventListener('resize', function () {
            // Debounce resize
            clearTimeout(window.resizeTimer);
            window.resizeTimer = setTimeout(() => {
                console.log('🔄 Redimensionando gráficos...');

                // Reinicializar gráficos con nuevos tamaños (destruye y crea de nuevo)
                // Esto es importante para el responsive completo de Chart.js
                inicializarGraficos();

                // Si tenemos datos globales, volver a aplicarlos para re-renderizar
                if (datosGlobales) {
                    actualizarGraficosConDatos(datosGlobales);
                } else {
                    // Si no hay datos aún, cargarlos de nuevo
                    actualizarDashboard();
                }

            }, 250);
        });

        // CSS adicional para mejorar responsive
        const styleElement = document.createElement('style');
        styleElement.textContent = `
/* Responsive mejorado para gráficos */
.chart-container {
    position: relative !important;
    height: 350px !important;
    width: 100% !important;
    max-height: 350px !important;
    overflow: hidden !important;
}

.chart-container-circular {
    position: relative !important;
    height: 280px !important;
    width: 100% !important;
    max-height: 280px !important;
    overflow: hidden !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

/* Responsive breakpoints */
@media (max-width: 1200px) {
    .chart-container {
        height: 300px !important;
        max-height: 300px !important;
    }
    .chart-container-circular {
        height: 250px !important;
        max-height: 250px !important;
    }
}

@media (max-width: 768px) {
    .chart-container {
        height: 250px !important;
        max-height: 250px !important;
    }
    .chart-container-circular {
        height: 200px !important;
        max-height: 200px !important;
    }
}

@media (max-width: 576px) {
    .chart-container {
        height: 220px !important;
        max-height: 220px !important;
    }
    .chart-container-circular {
        height: 180px !important;
        max-height: 180px !important;
    }
}

/* Asegurar que los canvas sean responsive */
#serviciosChart,
#diagnosticosChart,
#ingresosMesChart,
#ingresosMesChartDetalle {
    max-width: 100% !important;
    height: auto !important;
}
`;
        document.head.appendChild(styleElement);

        console.log('🎨 Paleta azul y responsive aplicados a los gráficos');
    </script>

    <script>
        // Detectar parámetros GET y mostrar sección correspondiente
        document.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);
            const seccion = urlParams.get('seccion');

            if (seccion) {
                // Ejecutar la función mostrarSeccion con el parámetro
                mostrarSeccion(seccion);

                // Opcional: limpiar la URL después de cargar la sección
                // window.history.replaceState({}, document.title, window.location.pathname);
            }
        });
    </script>

    <div class="modal fade" id="welcomeModal" tabindex="-1" aria-labelledby="welcomeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white text-center border-0">
                    <div class="w-100">
                        <i class="fas fa-user-check fa-3x mb-3"></i>
                        <h4 class="modal-title" id="welcomeModalLabel">¡Bienvenido al Sistema!</h4>
                    </div>
                </div>
                <div class="modal-body text-center py-4">
                    <div class="mb-3">
                        <img src="assets/img/kaiadmin/logohlo.png" alt="HLO Logo" width="80" class="mb-3">
                    </div>
                    <h5 class="text-primary mb-3">
                        Hola, <span
                            id="welcomeUserName"><?php echo $_SESSION['usuario_nombre'] . ' ' . $_SESSION['usuario_apellidos']; ?></span>
                    </h5>
                    <p class="text-muted mb-3">
                        <i class="fas fa-envelope me-2"></i>
                        <span id="welcomeUserEmail"><?php echo $_SESSION['usuario_email']; ?></span>
                    </p>
                    <p class="text-muted mb-3">
                        <i class="fas fa-user-tag me-2"></i>
                        Rol: <span class="badge bg-info"><?php echo ucfirst($_SESSION['usuario_rol']); ?></span>
                    </p>
                    <div class="alert alert-light border">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        <small>Área de Emergencias - Hospital Dr. Luis Ortega</small>
                    </div>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">
                        <i class="fas fa-arrow-right me-2"></i>Continuar
                    </button>
                </div>
            </div>
        </div>
    </div>

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
                                class="fw-bold"><?php echo $_SESSION['usuario_nombre'] . ' ' . $_SESSION['usuario_apellidos']; ?></span></small>
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // MODAL DE BIENVENIDA
            // Mostrar modal de bienvenida solo si es primera vez en la sesión
            <?php if (isset($_SESSION['usuario_id']) && !isset($_SESSION['welcome_shown'])): ?>
                const welcomeModal = new bootstrap.Modal(document.getElementById('welcomeModal'));
                welcomeModal.show();

                // Marcar que ya se mostró la bienvenida en esta sesión
                <?php $_SESSION['welcome_shown'] = true; ?>
            <?php endif; ?>

            // MODAL DE CIERRE DE SESIÓN
            // Interceptar todos los enlaces de logout
            const logoutLinks = document.querySelectorAll('a[href*="logout"], .logout-btn, [data-logout]');

            logoutLinks.forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();

                    // Mostrar modal de confirmación
                    const logoutModal = new bootstrap.Modal(document.getElementById(
                        'logoutModal'));
                    logoutModal.show();
                });
            });

            // Confirmar logout
            document.getElementById('confirmLogout').addEventListener('click', function () {
                // Mostrar loading
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Cerrando...';
                this.disabled = true;

                // Simular pequeño delay para UX
                setTimeout(() => {
                    window.location.href = 'logout.php';
                }, 1000);
            });

            // Auto-logout por inactividad (opcional)
            let inactivityTimer;
            const INACTIVITY_TIME = 30 * 60 * 1000; // 30 minutos

            function resetInactivityTimer() {
                clearTimeout(inactivityTimer);
                inactivityTimer = setTimeout(() => {
                    alert('⏰ Sesión expirada por inactividad');
                    window.location.href = 'logout.php';
                }, INACTIVITY_TIME);
            }

            // Detectar actividad del usuario
            ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'].forEach(event => {
                document.addEventListener(event, resetInactivityTimer, true);
            });

            resetInactivityTimer(); // Iniciar el timer
        });

        function generarReporte(tipoReporte) {
            Swal.fire({
                title: '📊 Generando Reporte',
                text: 'Por favor espere...',
                icon: 'info',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Simular generación de reporte
            setTimeout(() => {
                Swal.fire({
                    title: '✅ Reporte Generado',
                    text: `El reporte de ${tipoReporte.replace('-', ' ')} ha sido generado exitosamente.`,
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: '📥 Descargar',
                    cancelButtonText: 'Cerrar',
                    confirmButtonColor: 'var(--success-green)',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        downloadReporte(tipoReporte);
                    }
                });
            }, 2000);
        }

        function downloadReporte(tipoReporte) {
            // Abrir en nueva ventana para descargar PDF
            window.open(`reportes/generar_reporte.php?tipo=${tipoReporte}`, '_blank');

            mostrarNotificacion('📥 Generando reporte PDF...', 'success');
        }

        function generarReporte(tipoReporte) {
            Swal.fire({
                title: '📊 Generando Reporte PDF',
                text: 'Por favor espere...',
                icon: 'info',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Simular tiempo de procesamiento
            setTimeout(() => {
                Swal.fire({
                    title: '✅ Reporte Generado',
                    text: `El reporte PDF de ${tipoReporte.replace('-', ' ')} está listo para descargar.`,
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: '📥 Descargar PDF',
                    cancelButtonText: 'Cerrar',
                    confirmButtonColor: 'var(--success-green)',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        downloadReporte(tipoReporte);
                    }
                });
            }, 2000);
        }
    </script>
</body>

</html>