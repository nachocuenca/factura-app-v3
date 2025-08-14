# Seguridad

- Contraseñas: almacenar únicamente hashes con `password_hash()`.
- Sesión: regeneración en login, logout que elimina cookie y destruye sesión.
- CSRF: implementado en login. Recomendado añadirlo a formularios sensibles.
- Acceso a PDF y datos: consultar siempre por `usuario_id` en las SELECT.
- Directorio público: servir sólo `public/` como docroot.
- Archivos subidos: validar extensión/MIME y mover a rutas fuera de `views/`/`includes/`.

## Reporte de vulnerabilidades
Envía un email a **security@tu-dominio.tld** con pasos de reproducción. Evita crear issues públicos con PoC sensible.
