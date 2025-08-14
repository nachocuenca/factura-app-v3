<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/csrf.php';
require_once __DIR__ . '/../../../includes/helpers.php';
$sessionId = (int)$_SESSION['usuario_id'];
$isAdmin = is_admin();

$ownerId = $sessionId;
$users = [];
if ($isAdmin) {
  $users = $pdo->query("SELECT id, COALESCE(nombre,email) AS nom FROM usuarios ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
  $ownerId = (int)($_GET['owner_id'] ?? $sessionId);
}
?>
<div class="card shadow-sm">
  <form class="card-body" method="post" action="index.php?p=clientes-guardar">
    <?php csrf_field(); ?>
    <?php if ($isAdmin): ?>
      <div class="mb-3">
        <label class="form-label">Propietario</label>
        <select class="form-select" name="owner_id">
          <?php foreach($users as $u): ?>
            <option value="<?= (int)$u['id'] ?>" <?= ((int)$u['id']===$ownerId?'selected':'') ?>>
              <?= (int)$u['id'] ?> · <?= h($u['nom']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Nombre</label>
        <input type="text" name="nombre" class="form-control" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">CIF/NIF</label>
        <input type="text" name="cif" class="form-control" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Teléfono</label>
        <input type="text" name="telefono" class="form-control">
      </div>
      <div class="col-12">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control">
      </div>
      <div class="col-md-6">
        <label class="form-label">Dirección</label>
        <input type="text" name="direccion" class="form-control">
      </div>
      <div class="col-md-2">
        <label class="form-label">CP</label>
        <input type="text" name="cp" class="form-control">
      </div>
      <div class="col-md-2">
        <label class="form-label">Localidad</label>
        <input type="text" name="localidad" class="form-control">
      </div>
      <div class="col-md-2">
        <label class="form-label">Provincia</label>
        <input type="text" name="provincia" class="form-control">
      </div>
    </div>

    <div class="mt-4 d-flex gap-2">
      <button class="btn btn-primary">Guardar</button>
      <a class="btn btn-outline-secondary" href="index.php?p=clientes-index">Cancelar</a>
    </div>
  </form>
</div>
