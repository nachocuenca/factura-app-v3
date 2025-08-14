# Documentación del proyecto

Guías y referencias para trabajar con **Factura-app V3**.

## Contenido
- [Instalación](INSTALL.md)
- [Esquema de base de datos](DATABASE.md)
- [Generación de PDF](PDF.md)
- [Tema / Estilo](THEME.md)

## Arquitectura rápida
- `public/` – router principal (`index.php`), login/logout y recursos estáticos.
- `includes/` – configuración, conexión PDO, sesión, autenticación, CSRF y helpers.
- `views/` – layout, sidebar y páginas (clientes, productos, facturas, gastos, dashboard, config).
- `uploads/` – adjuntos de gastos (fuera de `/public`, se sirve autenticado).
- `facturas/` – PDFs generados por usuario (`carpeta{usuario_id}`).

## Seguridad
- Sesiones seguras con `session_regenerate_id`, cookies `httponly`, `secure`, `samesite=strict`.
- Formularios críticos protegidos con tokens CSRF.
- Consultas preparadas con filtrado por `usuario_id`.
- Adjuntos y PDFs fuera de `/public` y acceso autenticado.

## Flujo de facturación
1. `facturas/nuevo.php` permite seleccionar productos y calcula totales.
2. `facturas/guardar.php` guarda cabecera y líneas en transacción.
3. `facturas/generar_pdf.php` genera el PDF y lo almacena en `facturas/carpeta{usuario_id}`.

Para más detalles, consulta cada archivo correspondiente dentro de `views/pages/facturas/`.
