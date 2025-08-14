# Tema / Estilo

- CSS principal: `public/assets/css/theme-min.css`.
- Fondo global con “niebla”: `public/assets/img/app-bg.jpg`.
- Topbar/Sidebar **blancas**; tarjetas y tablas con sombras suaves.
- Título centrado en topbar: “GL-App”.

## Cambiar color de acento
Edita en `:root`:
```css
--accent-50:  #eef3ff;
--accent-500: #6e8ef5;
```

## Logo en login y sidebar
- `usuarios.logo` debe apuntar a una ruta bajo `/public` (ej. `uploads/logos/u1/logo.png`).
- El sidebar lo carga de sesión o BD automáticamente.
