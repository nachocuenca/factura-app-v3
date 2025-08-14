<?php
require_once __DIR__ . '/../../../includes/auth.php';
$sessionId = (int)$_SESSION['usuario_id'];
$isAdmin   = is_admin();

$ownerId = $sessionId;
$users = [];
if ($isAdmin) {
  $users = $pdo->query("SELECT id, COALESCE(nombre,email) AS nom FROM usuarios ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
  $ownerId = (int)($_GET['owner_id'] ?? $sessionId);
}
?>
<div class="card shadow-sm">
  <form class="card-body" method="post" action="index.php?p=productos-guardar">
    <?php if ($isAdmin): ?>
      <div class="mb-3">
        <label class="form-label">Propietario</label>
        <select class="form-select" name="owner_id">
          <?php foreach($users as $u): ?>
            <option value="<?= (int)$u['id'] ?>" <?= ((int)$u['id']===$ownerId?'selected':'') ?>>
              <?= (int)$u['id'] ?> · <?= htmlspecialchars($u['nom']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Referencia</label>
        <input type="text" name="referencia" class="form-control" placeholder="OPCIONAL">
      </div>
      <div class="col-md-8">
        <label class="form-label">Nombre</label>
        <input type="text" name="nombre" class="form-control" required>
      </div>
      <div class="col-12">
        <label class="form-label">Descripción</label>
        <textarea name="descripcion" class="form-control" rows="3" placeholder="OPCIONAL"></textarea>
      </div>
      <div class="col-md-3">
        <label class="form-label">Precio unitario (€)</label>
        <input type="number" step="0.01" name="precio_unitario" class="form-control" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">IVA (%)</label>
        <input type="number" step="0.01" name="iva_porcentaje" class="form-control" value="21.00">
      </div>
    </div>

    <div class="mt-4 d-flex gap-2">
      <button class="btn btn-primary">Guardar</button>
      <a class="btn btn-outline-secondary" href="index.php?p=productos-index">Cancelar</a>
    </div>
  </form>
</div>
