# Instalación

## 1) Requisitos
- PHP 8.0+ con `pdo_mysql`, `mbstring`, `gd`.
- MySQL 5.7+ / MariaDB 10.3+.
- Composer (si usas mPDF vía composer).

## 2) Clonar y servidor
- Coloca el proyecto y apunta tu servidor a `public/` como raíz.

## 3) Base de datos
- Crea la BD (ej.: `factura-app-v3`).
- Ejecuta el SQL de `docs/DATABASE.md`.

## 4) Conexión
Edita `includes/conexion.php`:
```php
<?php
$DB_HOST='127.0.0.1'; $DB_NAME='factura-app-v3'; $DB_USER='root'; $DB_PASS='';
$pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",$DB_USER,$DB_PASS,[
  PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
]);
```

## 5) Directorios de escritura
- Crea las carpetas `facturas/` y `uploads/` en la raíz del proyecto.
- Asegura permisos de escritura para el servidor web.
- Versiona `facturas/` solo con `.gitkeep`; los PDFs generados se ignoran.

## 6) mPDF (PDF)
```bash
composer require mpdf/mpdf
```

## 7) Usuario admin
Genera un hash:
```php
<?php echo password_hash('admin123', PASSWORD_DEFAULT);
```
Inserta en `usuarios` con ese hash (ver `docs/DATABASE.md`).

## 8) Fondo y tema
- Pon tu imagen en `public/assets/img/app-bg.jpg`.
- Asegúrate de cargar `assets/css/theme-min.css` en el layout.

## 9) Acceso
`/public/login.php` → Dashboard → Sidebar.
