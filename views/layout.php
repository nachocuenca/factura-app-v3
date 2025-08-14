<?php
require_once __DIR__ . '/../includes/helpers.php';
if (!isset($pageTitle)) $pageTitle = 'Panel';
$current = $_GET['p'] ?? 'dashboard'; // para resaltar activo
?>
<!doctype html>
<html lang="es">
<head>
    <?php require __DIR__ . '/partials/head.php'; ?>
    <title><?= h($pageTitle) ?> GL-APP</title>
</head>
<body>

<!-- Topbar -->
<nav class="navbar navbar-expand-lg topbar sticky-top" role="navigation">
  <div class="container-fluid position-relative">
    <button class="navbar-toggler me-2" type="button"
            data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas"
            aria-controls="sidebarOffcanvas" aria-label="Menú">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- placeholder para mantener alturas -->
    <a class="navbar-brand d-none d-lg-inline-block" href="index.php?p=dashboard" aria-label="Inicio"></a>

    <!-- Título centrado -->
    <div class="brand-center">
      <span class="brand-title">GL-App</span>
    </div>

    <!-- Menú derecho mínimo -->
    <div class="collapse navbar-collapse" id="topbarNav">
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <li class="nav-item"><a class="nav-link" href="index.php?p=config-index">Configuración</a></li>
      </ul>
    </div>
  </div>
</nav>


<!-- Layout: sidebar fijo (solo ≥lg) + contenido -->
<div class="d-lg-flex">
  <?php require __DIR__ . '/partials/sidebar.php'; ?>

  <main class="flex-grow-1 p-3 p-lg-4">
    <div class="container-fluid">
        <h1 class="h4 mb-3"><?= h($pageTitle) ?></h1>
      <?= $content ?? '' ?>
    </div>
  </main>
</div>

<!-- Bootstrap JS con fallback para abrir/cerrar offcanvas si falla el CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        onerror="!function(){document.querySelectorAll('[data-bs-toggle=offcanvas]').forEach(function(b){
          b.addEventListener('click',function(){
            var t=document.querySelector(b.getAttribute('data-bs-target'));
            if(t) t.classList.toggle('show');
          });
        });document.querySelectorAll('[data-bs-dismiss=offcanvas]').forEach(function(x){
          x.addEventListener('click', function(){ x.closest('.offcanvas')?.classList.remove('show'); });
        });}()"></script>
</body>
</html>
