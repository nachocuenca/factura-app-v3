# Factura-app V3

Aplicación ligera de facturación en PHP + MySQL con panel responsive (Bootstrap) y tema pastel minimalista.

## Características
- **Login seguro** con `password_verify`, regeneración de sesión y logout.
- **Dashboard** con KPIs: clientes, productos, facturado/cobrado, gastos y últimas facturas/clientes.
- **Clientes** (activar/desactivar), **Productos** y **Facturas** con:
  - Numeración automática **Serie + Número** (tokens `{yy}` / `{yyyy}`).
  - Líneas con **selector de productos**, descuentos e IVA.
  - Cálculo de totales e **IRPF**.
  - **PDF** (mPDF) con control de pertenencia por `usuario_id`.
- **Gastos** con subida de archivo (PDF/JPG/PNG/HEIC) y cálculo de IVA soportado.
- **Tema pastel minimal** + **fondo global** con “niebla”.
- **Sidebar y Topbar blancas** (limpio, usable en móvil).

## Requisitos
- PHP **8.0+** (extensiones: `pdo_mysql`, `mbstring`, `gd` para mPDF).
- MySQL 5.7+ / MariaDB 10.3+.
- Composer (si vas a instalar mPDF localmente).
- Servir `/public` como raíz pública (XAMPP/WAMP/Valet/Apache/Nginx).

## Estructura
```
factura-app-v3/
├─ public/                   # raíz pública
│  ├─ index.php              # router principal (p=?)
│  ├─ login.php              # login
│  ├─ logout.php             # logout
│  └─ assets/
│     ├─ css/theme-min.css   # tema pastel minimal
│     └─ img/app-bg.jpg      # fondo global
├─ views/
│  ├─ layout.php             # layout con topbar + sidebar
│  ├─ partials/sidebar.php   # menú lateral
│  └─ pages/
│     ├─ dashboard.php
│     ├─ clientes/…          # index/nuevo/editar/guardar/activar
│     ├─ productos/…         # index/nuevo/editar/guardar/activar
│     ├─ facturas/
│     │  ├─ nuevo.php        # selector de productos y numeración automática
│     │  ├─ guardar.php
│     │  └─ generar_pdf.php  # mPDF (control de pertenencia)
│     └─ gastos/…            # index/nuevo/guardar/archivo
├─ includes/
│  ├─ config.php             # config general
│  ├─ conexion.php           # PDO
│  └─ auth.php               # middleware de sesión
├─ facturas/                 # PDFs por usuario (carpeta{usuario_id})
├─ docs/                     # documentación adicional
├─ .gitignore
├─ LICENSE
├─ CHANGELOG.md
├─ SECURITY.md
└─ CONTRIBUTING.md
```

## Instalación (rápida)
1. Clona el repo y apunta tu vhost a `public/`.
2. Crea la BD (ej. `factura-app-v3`) y ejecuta el SQL de `docs/DATABASE.md`.
3. Ajusta credenciales en `includes/conexion.php`.
4. (Opcional) `composer require mpdf/mpdf` si usas mPDF local.
5. Accede a `/public/login.php`. Crea un usuario `admin` (ver `docs/DATABASE.md`).

## Numeración automática
- **Serie** en `usuarios.serie_facturas` (tokens):
  - `{yy}` → año corto (ej. **25**)
  - `{yyyy}` → año largo (ej. **2025**)
- **Número**: `MAX(numero)` dentro de la serie + 1 (en transacción + índice único).

## PDF
Genera y guarda en `facturas/carpeta{usuario_id}/factura_{SERIE}_{ID}.pdf`.  
Se comprueba **pertenencia de factura** y **cliente** al usuario.

## Tema / UI
- Fondo global `public/assets/img/app-bg.jpg` con niebla.
- Navbar y Sidebar siempre **blancas**.
- Estilo en `public/assets/css/theme-min.css`.

## Dependencias externas
- Bootstrap 5.3.3 se carga desde CDN con atributos `integrity` y `crossorigin`.
- Para actualizar o verificar la versión:
  1. Descarga el archivo correspondiente.
  2. Calcula el hash SRI:
     ```bash
     openssl dgst -sha384 -binary bootstrap.min.css | openssl base64 -A
     ```
  3. Sustituye el valor en `public/login.php`, `views/partials/head.php` y `views/layout.php`.
- También puedes alojar `bootstrap.min.css` y `bootstrap.bundle.min.js` en `public/assets/` y actualizar los enlaces para usar la copia local.

## Licencia
MIT – ver `LICENSE`.

## Contribuir
Lee `CONTRIBUTING.md`. Para vulnerabilidades: `SECURITY.md`.

## Documentación

La carpeta [docs/](docs) incluye guías detalladas:

- [README general](docs/README.md)
- [Instalación](docs/INSTALL.md)
- [Esquema de base de datos](docs/DATABASE.md)
- [PDF](docs/PDF.md)
- [Tema / Estilo](docs/THEME.md)
