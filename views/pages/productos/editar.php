<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/csrf.php';
require_once __DIR__ . '/../../../includes/helpers.php';
$sessionId = (int)$_SESSION['usuario_id'];
$isAdmin   = is_admin();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

if ($isAdmin) {
  $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
  $stmt->execute([$id]);
} else {
  $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ? AND usuario_id = ?");
  $stmt->execute([$id, $sessionId]);
}
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) { die('Producto no encontrado o sin permisos'); }

$users = [];
if ($isAdmin) {
  $users = $pdo->query("SELECT id, COALESCE(nombre,email) AS nom FROM usuarios ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
}
?>
<div class="card shadow-sm">
  <form class="card-body" method="post" action="index.php?p=productos-actualizar">
    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
    <?php csrf_field(); ?>

    <?php if ($isAdmin): ?>
      <div class="mb-3">
        <label class="form-label">Propietario</label>
        <select class="form-select" name="owner_id">
          <?php foreach($users as $u): ?>
            <option value="<?= (int)$u['id'] ?>" <?= ((int)$u['id']===(int)$p['usuario_id']?'selected':'') ?>>
              <?= (int)$u['id'] ?> · <?= h($u['nom']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Referencia</label>
        <input type="text" name="referencia" class="form-control" value="<?= h($p['referencia'] ?? '') ?>">
      </div>
      <div class="col-md-8">
        <label class="form-label">Nombre</label>
        <input type="text" name="nombre" class="form-control" value="<?= h($p['nombre']) ?>" required>
      </div>
      <div class="col-12">
        <label class="form-label">Descripción</label>
        <textarea name="descripcion" class="form-control" rows="3"><?= h($p['descripcion'] ?? '') ?></textarea>
      </div>
      <div class="col-md-3">
        <label class="form-label">Precio unitario (€)</label>
        <input type="number" step="0.01" name="precio_unitario" class="form-control" value="<?= number_format((float)$p['precio_unitario'],2,'.','') ?>" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">IVA (%)</label>
        <input type="number" step="0.01" name="iva_porcentaje" class="form-control" value="<?= number_format((float)($p['iva_porcentaje'] ?? 21),2,'.','') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label d-block">Estado</label>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="activo" value="1" id="prdActivo" <?= ((int)$p['activo']===1?'checked':'') ?>>
          <label class="form-check-label" for="prdActivo">Activo</label>
        </div>
      </div>
    </div>

    <div class="mt-4 d-flex gap-2">
      <button class="btn btn-primary">Actualizar</button>
      <a class="btn btn-outline-secondary" href="index.php?p=productos-index">Cancelar</a>
    </div>
  </form>
</div>
