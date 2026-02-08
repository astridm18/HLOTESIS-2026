# HLOTESIS — Sistema de Historias Médicas

Sistema web para la gestión de historias clínicas hospitalarias, desarrollado en PHP.

---

## Requisitos

| Componente | Versión recomendada |
|------------|---------------------|
| PHP        | 7.4 o superior      |
| MySQL / MariaDB | 5.7+ / 10.4+   |
| Servidor web | Apache (Laragon, XAMPP, etc.) |
| Navegador  | Chrome, Firefox, Edge (versiones modernas) |

---

## Instalación

### 1. Clonar o copiar el proyecto

```bash
git clone <repositorio> HLOTESISfin
# o copiar manualmente la carpeta
```

### 2. Configurar credenciales de base de datos

1. Ir a la carpeta `config/`
2. Copiar `config.local.php.example` a `config.local.php`
3. Editar `config.local.php` con las credenciales de tu entorno:

```php
$db_host = 'localhost';
$db_name = 'historiasmedicas';
$db_user = 'root';
$db_pass = '';
$app_env = 'dev'; // 'dev' para desarrollo, 'prod' para producción
```

> **Importante:** `config.local.php` está en `.gitignore` y NO se sube al repositorio.

### 3. Importar la base de datos

Importar el archivo SQL de estructura en tu gestor de base de datos (phpMyAdmin, HeidiSQL, etc.):

```
-- Archivo: (pendiente documentar ubicación del dump)
```

### 4. Configurar servidor web

- **Laragon:** Coloca la carpeta en `C:\laragon\www\` y accede a `http://hlotesis.test` o `http://localhost/HLOTESISfin`
- **XAMPP:** Coloca en `htdocs/` y accede a `http://localhost/HLOTESISfin`

### 5. Acceder al sistema

Abrir en el navegador:
```
http://localhost/HLOTESISfin/login.html
```

---

## Estructura del proyecto

```
HLOTESISfin/
├── config/              # Configuración (app.php, config.local.php, roles.php)
├── includes/            # Helpers y conexión BD (db.php, auth.php, helpers.php)
├── forms/
│   ├── config/          # PDO, funciones compartidas (database.php, functions.php)
│   └── modulos/
│       ├── ingreso/     # Formularios de ingreso de pacientes
│       ├── egreso/      # Formularios de egreso
│       └── procesos/    # Evoluciones, órdenes, signos vitales, etc.
├── reportes/            # Generación de PDF con TCPDF
├── assets/              # CSS, JS, imágenes
├── conexion.php         # Conexión MySQLi principal
├── login.html / login.php
└── index.php            # Dashboard principal
```

Para más detalle, ver [`docs/ESTRUCTURA.md`](../docs/ESTRUCTURA.md).

---

## Documentación

| Documento | Descripción |
|-----------|-------------|
| [PLAN_FASES.md](../docs/PLAN_FASES.md) | Roadmap de optimización del proyecto |
| [ESTRUCTURA.md](../docs/ESTRUCTURA.md) | Mapa completo de carpetas y archivos |
| [CONVENCIONES.md](../docs/CONVENCIONES.md) | Reglas de nomenclatura y estilo |
| [DECISIONES.md](../docs/DECISIONES.md) | Registro de decisiones arquitectónicas (ADR) |
| [ESCALABILIDAD_Y_BUENAS_PRACTICAS.md](../docs/ESCALABILIDAD_Y_BUENAS_PRACTICAS.md) | Guía de buenas prácticas aplicadas |

---

## Entorno de desarrollo vs producción

El sistema usa la variable `APP_ENV` (definida en `config.local.php`) para diferenciar entornos:

- **`dev`**: Permite herramientas de desarrollo como `session_temporal.php` para pruebas sin login.
- **`prod`**: Bloquea herramientas de desarrollo; solo acceso autenticado.

---

## Seguridad

- Credenciales de BD fuera del código (en `config.local.php`, no versionado).
- Consultas con prepared statements (PDO y MySQLi).
- Validación de sesión con `verificarSesion()`.
- Control de roles con `verificarRol()` y `config/roles.php`.
- Escape de salida HTML con `htmlspecialchars()`.

---

## Contribuir

1. Leer [`docs/CONVENCIONES.md`](../docs/CONVENCIONES.md) antes de hacer cambios.
2. Seguir nomenclatura `snake_case` para archivos y funciones PHP.
3. Documentar decisiones importantes en `docs/DECISIONES.md`.
4. Probar cambios en entorno `dev` antes de pasar a `prod`.

---

## Licencia

Proyecto interno. Consultar con el equipo responsable para términos de uso.

---

*Generado como parte del proceso de optimización — Fase 5.*
