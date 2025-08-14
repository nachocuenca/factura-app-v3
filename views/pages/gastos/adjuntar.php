<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/../../../includes/auth.php';

$uid = (int)$_SESSION['usuario_id'];
$id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$st = $pdo->prepare("SELECT id, fecha, numero, categoria, archivo FROM gastos WHERE id=? AND usuario_id=?");
$st->execute([$id, $uid]);
$gasto = $st->fetch(PDO::FETCH_ASSOC);
if (!$gasto) {
  http_response_code(404);
  die('Gasto no encontrado o sin permisos');
}

if (!function_exists('ga_h')) {
  function ga_h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="m-0">Adjuntar archivo — Gasto #<?= (int)$gasto['id'] ?></h5>
  <a class="btn btn-outline-secondary" href="index.php?p=gastos-index">Volver</a>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <div class="mb-3">
      <div class="text-muted small">Fecha</div>
      <div><strong><?= ga_h($gasto['fecha'] ?: '') ?></strong></div>
    </div>
    <div class="mb-3">
      <div class="text-muted small">Número / Categoría</div>
      <div><strong><?= ga_h($gasto['numero'] ?: '—') ?></strong> · <?= ga_h($gasto['categoria'] ?: '—') ?></div>
    </div>

    <?php if (!empty($gasto['archivo'])): ?>
      <div class="mb-3">
        <div class="text-muted small mb-1">Adjunto actual</div>
        <?php if (preg_match('~\.(jpe?g|png|webp|heic|heif)$~i', $gasto['archivo'])): ?>
          <a href="<?= ga_h($gasto['archivo']) ?>" target="_blank">
            <img src="<?= ga_h($gasto['archivo']) ?>" alt="Adjunto actual" style="max-width: 320px; height:auto; border:1px solid #e5e7eb; border-radius:8px;">
          </a>
        <?php else: ?>
          <a href="<?= ga_h($gasto['archivo']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm">Abrir PDF actual</a>
        <?php endif; ?>
        <div class="mt-2">
          <a href="index.php?p=gastos-quitar-adjunto&id=<?= (int)$gasto['id'] ?>" class="btn btn-sm btn-outline-danger"
             onclick="return confirm('¿Quitar el adjunto actual?');">Quitar adjunto</a>
        </div>
      </div>
    <?php endif; ?>

    <form method="post" action="index.php?p=gastos-subir-adjunto" enctype="multipart/form-data" class="row g-3">
      <input type="hidden" name="id" value="<?= (int)$gasto['id'] ?>">
      <div class="col-md-8">
        <label class="form-label">Nuevo archivo (foto o PDF)</label>
        <input type="file" name="archivo" class="form-control" accept="image/*,application/pdf" capture="environment" required>
        <div class="form-text">JPG, PNG, WEBP, HEIC/HEIF o PDF. Máx. 15 MB.</div>
      </div>
      <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary">Subir y reemplazar</button>
        <a class="btn btn-outline-secondary" href="index.php?p=gastos-index">Cancelar</a>
      </div>
    </form>
  </div>
</div>
