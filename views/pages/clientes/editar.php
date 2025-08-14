<?php
require_once __DIR__ . '/../../../includes/auth.php';
$sessionId = (int)$_SESSION['usuario_id'];
$isAdmin = is_admin();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

if ($isAdmin) {
  $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
  $stmt->execute([$id]);
} else {
  $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ? AND usuario_id = ?");
  $stmt->execute([$id, $sessionId]);
}
$c = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$c) { die('Cliente no encontrado o sin permisos'); }

$users = [];
if ($isAdmin) {
  $users = $pdo->query("SELECT id, COALESCE(nombre,email) AS nom FROM usuarios ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
}
?>
<div class="card shadow-sm">
  <form class="card-body" method="post" action="index.php?p=clientes-actualizar">
    <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">

    <?php if ($isAdmin): ?>
      <div class="mb-3">
        <label class="form-label">Propietario</label>
        <select class="form-select" name="owner_id">
          <?php foreach($users as $u): ?>
            <option value="<?= (int)$u['id'] ?>" <?= ((int)$u['id']===(int)$c['usuario_id']?'selected':'') ?>>
              <?= (int)$u['id'] ?> · <?= htmlspecialchars($u['nom']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Nombre</label>
        <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($c['nombre']) ?>" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">CIF/NIF</label>
        <input type="text" name="cif" class="form-control" value="<?= htmlspecialchars($c['cif']) ?>" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Teléfono</label>
        <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($c['telefono']) ?>">
      </div>
      <div class="col-12">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($c['email']) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Dirección</label>
        <input type="text" name="direccion" class="form-control" value="<?= htmlspecialchars($c['direccion']) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">CP</label>
        <input type="text" name="cp" class="form-control" value="<?= htmlspecialchars($c['cp']) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Localidad</label>
        <input type="text" name="localidad" class="form-control" value="<?= htmlspecialchars($c['localidad']) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Provincia</label>
        <input type="text" name="provincia" class="form-control" value="<?= htmlspecialchars($c['provincia']) ?>">
      </div>

      <div class="col-md-3">
        <label class="form-label d-block">Estado</label>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="activo" value="1" id="cliActivo" <?= ((int)$c['activo']===1?'checked':'') ?>>
          <label class="form-check-label" for="cliActivo">Activo</label>
        </div>
      </div>
    </div>

    <div class="mt-4 d-flex gap-2">
      <button class="btn btn-primary">Actualizar</button>
      <a class="btn btn-outline-secondary" href="index.php?p=clientes-index">Cancelar</a>
    </div>
  </form>
</div>
