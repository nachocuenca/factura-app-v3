<?php
require_once __DIR__ . '/../../includes/session.php';
secure_session_start();
require_once __DIR__ . '/../../includes/conexion.php'; // para leer el logo desde BD

// Activo en el menú
function li_active($key, $current){ return $current === $key ? ' active' : ''; }

// URL absoluta hacia /public (p.ej. /factura-app-v3/public)
function base_public_path(): string {
  $dir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
  // Si estamos ya en /public (index.php), dirname = /factura-app-v3/public
  // Si alguna vista se renderiza con otra profundidad, sigue funcionando.
  return $dir;
}

// URL completa a logout.php dentro de /public
function logout_url(): string {
  return base_public_path() . '/logout.php';
}

// Intenta obtener la URL del logo del usuario
function current_user_logo_url(PDO $pdo): ?string {
  $uid = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;

  // 1) si ya viene en sesión, úsalo
  if (!empty($_SESSION['usuario_logo'])) {
    $src = (string)$_SESSION['usuario_logo'];
    // si es relativa (uploads/...), prefija /public
    if (preg_match('~^https?://~i', $src)) return $src;
    return base_public_path() . '/' . ltrim($src, '/');
  }

  // 2) si no, intenta leer de BD
  if ($uid > 0) {
    $st = $pdo->prepare("SELECT logo FROM usuarios WHERE id=? LIMIT 1");
    $st->execute([$uid]);
    $logo = $st->fetchColumn();
    if ($logo) {
      $_SESSION['usuario_logo'] = $logo; // cache en sesión
      if (preg_match('~^https?://~i', $logo)) return $logo;
      return base_public_path() . '/' . ltrim($logo, '/');
    }
  }
  return null;
}

function render_menu($current){
  $logoutHref = logout_url();
  global $pdo;
  $logoUrl = current_user_logo_url($pdo);
  ?>
  <!-- Cabecera/Logo -->
  <div class="text-center mb-3">
    <?php if ($logoUrl): ?>
      <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo" style="max-width: 150px; height:auto; object-fit:contain;">
    <?php else: ?>
      <div class="fw-bold">Factura-app V3</div>
    <?php endif; ?>
  </div>

  <div class="list-group list-group-flush">
    <a class="list-group-item list-group-item-action<?= li_active('dashboard', $current) ?>" href="index.php?p=dashboard">
      <span class="me-2">🏠</span> Dashboard
    </a>

    <div class="mt-3 mb-1 small text-uppercase text-muted px-3">Ventas</div>
    <a class="list-group-item list-group-item-action<?= li_active('clientes-index', $current) ?>" href="index.php?p=clientes-index">
      <span class="me-2">👤</span> Clientes
    </a>
    <a class="list-group-item list-group-item-action<?= li_active('clientes-nuevo', $current) ?>" href="index.php?p=clientes-nuevo">
      <span class="me-2">➕</span> Nuevo cliente
    </a>

    <a class="list-group-item list-group-item-action<?= li_active('productos-index', $current) ?>" href="index.php?p=productos-index">
      <span class="me-2">📦</span> Productos
    </a>
    <a class="list-group-item list-group-item-action<?= li_active('productos-nuevo', $current) ?>" href="index.php?p=productos-nuevo">
      <span class="me-2">➕</span> Nuevo producto
    </a>

    <a class="list-group-item list-group-item-action<?= li_active('facturas-index', $current) ?>" href="index.php?p=facturas-index">
      <span class="me-2">📄</span> Facturas
    </a>
    <a class="list-group-item list-group-item-action<?= li_active('facturas-nuevo', $current) ?>" href="index.php?p=facturas-nuevo">
      <span class="me-2">➕</span> Nueva factura
    </a>

    <div class="mt-3 mb-1 small text-uppercase text-muted px-3">Gastos</div>
    <a class="list-group-item list-group-item-action<?= li_active('gastos-index', $current) ?>" href="index.php?p=gastos-index">
      <span class="me-2">💳</span> Gastos
    </a>
    <a class="list-group-item list-group-item-action<?= li_active('gastos-nuevo', $current) ?>" href="index.php?p=gastos-nuevo">
      <span class="me-2">➕</span> Nuevo gasto
    </a>

    <div class="mt-3 mb-1 small text-uppercase text-muted px-3">Sistema</div>
    <a class="list-group-item list-group-item-action<?= li_active('config-index', $current) ?>" href="index.php?p=config-index">
      <span class="me-2">⚙️</span> Configuración
    </a>

    <hr class="my-2">
    <div class="d-grid px-2 pb-2">
      <a href="<?= htmlspecialchars($logoutHref) ?>" class="btn btn-danger">
        ⎋ Cerrar sesión
      </a>
    </div>
  </div>
  <?php
}
?>

<!-- Sidebar fijo (solo escritorio) -->
<aside class="d-none d-lg-block sidebar-desktop p-3">
  <?php render_menu($current); ?>
</aside>

<!-- Offcanvas para móvil -->
<div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="sidebarLabel">Menú</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
  </div>
  <div class="offcanvas-body p-2">
    <?php render_menu($current); ?>
  </div>
</div>
