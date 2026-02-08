<?php
// session_temporal.php - SOLO PARA DESARROLLO (APP_ENV = 'dev')
require_once __DIR__ . '/../../../config/app.php';
if (!defined('APP_ENV') || APP_ENV !== 'dev') {
    header('Location: ../../../login.html');
    exit;
}
session_start();

// Datos temporales del usuario para testing
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_nombre'] = 'Usuario';
$_SESSION['usuario_apellido'] = 'Temporal';
$_SESSION['usuario_rol'] = 'Medicina';
$_SESSION['usuario_especialidad'] = 'Medicina General';
$_SESSION['usuario_cedula'] = '1234567890';
$_SESSION['usuario_email'] = 'temporal@test.com';

echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Sesión Temporal Activada</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head>";
echo "<body>";
echo "<div class='container mt-5'>";
echo "<div class='alert alert-success'>";
echo "<h4><i class='fas fa-check-circle'></i> Sesión Temporal Activada</h4>";
echo "<p>Se ha creado una sesión temporal para testing con los siguientes datos:</p>";
echo "<ul>";
echo "<li><strong>ID:</strong> " . $_SESSION['usuario_id'] . "</li>";
echo "<li><strong>Nombre:</strong> " . $_SESSION['usuario_nombre'] . " " . $_SESSION['usuario_apellido'] . "</li>";
echo "<li><strong>Rol:</strong> " . $_SESSION['usuario_rol'] . "</li>";
echo "<li><strong>Especialidad:</strong> " . $_SESSION['usuario_especialidad'] . "</li>";
echo "<li><strong>Cédula:</strong> " . $_SESSION['usuario_cedula'] . "</li>";
echo "</ul>";
echo "</div>";

echo "<div class='card mt-4'>";
echo "<div class='card-header'>";
echo "<h5>Enlaces Rápidos</h5>";
echo "</div>";
echo "<div class='card-body'>";
echo "<div class='row'>";
echo "<div class='col-md-4'>";
echo "<h6>Módulo Ingreso</h6>";
echo "<a href='formulario_ingreso.php' class='btn btn-primary btn-sm mb-2 d-block'>Formulario de Ingreso</a>";
echo "<a href='modulos/ingreso/listar_casos_activos.php' class='btn btn-outline-primary btn-sm mb-2 d-block'>Casos Activos</a>";
echo "</div>";
echo "<div class='col-md-4'>";
echo "<h6>Módulo Procesos</h6>";
echo "<a href='modulos/procesos/evoluciones.php?caso=1' class='btn btn-success btn-sm mb-2 d-block'>Evoluciones</a>";
echo "<a href='modulos/procesos/signos_vitales.php?caso=1' class='btn btn-outline-success btn-sm mb-2 d-block'>Signos Vitales</a>";
echo "<a href='modulos/procesos/ordenes.php?caso=1' class='btn btn-outline-success btn-sm mb-2 d-block'>Órdenes Médicas</a>";
echo "</div>";
echo "<div class='col-md-4'>";
echo "<h6>Módulo Egreso</h6>";
echo "<a href='modulos/egreso/formulario_egreso.php?caso=1' class='btn btn-warning btn-sm mb-2 d-block'>Formulario Egreso</a>";
echo "<a href='modulos/egreso/lista_egresos.php' class='btn btn-outline-warning btn-sm mb-2 d-block'>Lista de Egresos</a>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<div class='alert alert-warning mt-4'>";
echo "<h6><i class='fas fa-exclamation-triangle'></i> Advertencia</h6>";
echo "<p class='mb-0'>Esta es una sesión temporal solo para desarrollo. En producción debes implementar un sistema de login completo.</p>";
echo "</div>";

echo "<div class='mt-4'>";
echo "<button class='btn btn-danger' onclick='cerrarSesion()'>Cerrar Sesión Temporal</button>";
echo "</div>";

echo "</div>";
echo "<script>";
echo "function cerrarSesion() {";
echo "  if(confirm('¿Cerrar la sesión temporal?')) {";
echo "    window.location.href = 'cerrar_session.php';";
echo "  }";
echo "}";
echo "</script>";
echo "</body>";
echo "</html>";
?>