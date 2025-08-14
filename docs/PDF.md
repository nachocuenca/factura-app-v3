# PDF de facturas (mPDF)

## Requisitos
```bash
composer require mpdf/mpdf
```

## Generación
- Archivo: `views/pages/facturas/generar_pdf.php`.
- Guarda en: `facturas/carpeta{usuario_id}/factura_{SERIE}_{ID}.pdf`.
- Control de acceso:
  - La factura debe existir con `id` + `usuario_id` de la sesión.
  - El `cliente_id` de la factura debe pertenecer al `usuario_id` de sesión.

## Tipografías y logo
- Puedes cargar fuentes desde `assets/fonts` (ver ejemplo con Abadi en el script).
- Logo del usuario desde `usuarios.logo` (se muestra si existe y es accesible).
