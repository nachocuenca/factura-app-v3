# Contribuir

## Flujo
1. Crea una rama desde `main`: `feat/…` o `fix/…`.
2. Commits claros (convencional): `feat(facturas): …`, `fix(gastos): …`.
3. PR con descripción y pasos de prueba.

## Estilo
- PHP 8.x, PDO con consultas preparadas.
- HTML semántico y Bootstrap 5.
- CSS en `public/assets/css/theme-min.css` (evitar inline).

## Tests manuales mínimos
- Login/logout.
- Alta/edición/activación de clientes y productos.
- Crear factura con selector de productos y numeración.
- Generar PDF y descargarlo.
- Crear gasto con archivo.
- Dashboard muestra sums correctas.

## Issues
Incluye versión de PHP y pasos exactos para reproducir.
