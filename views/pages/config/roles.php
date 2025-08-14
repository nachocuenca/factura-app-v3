<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/permissions.php';
if (!is_admin()) { http_response_code(403); exit('Acceso denegado'); }

$roles = $pdo->query("SELECT id, nombre FROM roles ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$usuarios = $pdo->query("SELECT id, COALESCE(nombre,email) AS nom, role_id FROM usuarios ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
$modulos = $pdo->query("SELECT id, nombre FROM modulos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'user-role') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $rid = (int)($_POST['role_id'] ?? 0);
        $st = $pdo->prepare("UPDATE usuarios SET role_id=? WHERE id=?");
        $st->execute([$rid, $uid]);
    }
    if (($_POST['action'] ?? '') === 'role-modules') {
        $rid = (int)($_POST['role_id'] ?? 0);
        $mods = array_map('intval', $_POST['modules'] ?? []);
        $pdo->prepare("DELETE FROM roles_modulos WHERE role_id=?")->execute([$rid]);
        $ins = $pdo->prepare("INSERT INTO roles_modulos (role_id, modulo_id) VALUES (?, ?)");
        foreach ($mods as $m) { $ins->execute([$rid, $m]); }
    }
    header('Location: index.php?p=config-roles');
    exit;
}
?>
<div class="card shadow-sm mb-4">
  <div class="card-body">
    <h5>Asignar rol a usuario</h5>
    <form method="post" class="row g-2">
      <input type="hidden" name="action" value="user-role">
      <div class="col-md-5">
        <select name="user_id" class="form-select">
          <?php foreach($usuarios as $u): ?>
            <option value="<?= h($u['id']) ?>"><?= h($u['nom']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-5">
        <select name="role_id" class="form-select">
          <?php foreach($roles as $r): ?>
            <option value="<?= h($r['id']) ?>"><?= h($r['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary w-100">Guardar</button>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <h5>Permisos por rol</h5>
    <?php foreach($roles as $r):
      $st = $pdo->prepare("SELECT modulo_id FROM roles_modulos WHERE role_id=?");
      $st->execute([$r['id']]);
      $current = array_column($st->fetchAll(PDO::FETCH_ASSOC), 'modulo_id');
    ?>
      <form method="post" class="border p-3 mb-3">
        <input type="hidden" name="action" value="role-modules">
        <input type="hidden" name="role_id" value="<?= h($r['id']) ?>">
        <h6 class="mb-3"><?= h($r['nombre']) ?></h6>
        <?php foreach($modulos as $m): ?>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" name="modules[]" value="<?= h($m['id']) ?>"<?= in_array($m['id'], $current) ? ' checked' : '' ?>>
            <label class="form-check-label"><?= h($m['nombre']) ?></label>
          </div>
        <?php endforeach; ?>
        <div class="mt-3">
          <button class="btn btn-secondary">Actualizar</button>
        </div>
      </form>
    <?php endforeach; ?>
  </div>
</div>
