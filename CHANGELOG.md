# Changelog

## [3.0.0] – 2025-08-14
### Added
- Tema pastel minimal (topbar/sidebar blancas, fondo global con niebla).
- Login seguro (`password_verify`, CSRF, regeneración de sesión).
- Dashboard con KPIs y mini-gráfica de 6 meses.
- Clientes/Productos con activar/desactivar.
- Facturas con numeración automática (tokens `{yy}` / `{yyyy}`) y selector de productos.
- PDF de factura (mPDF) con control de pertenencia.
- Gastos con subida de archivos y sumatorio en dashboard.
### Changed
- Router único en `public/index.php` (`p=?`).
### Fixed
- Evitado conflicto de IDs cliente/usuario en facturas.
