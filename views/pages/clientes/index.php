<?php
require_once __DIR__ . '/../../../includes/auth.php';
$sessionId = (int)$_SESSION['usuario_id'];
$isAdmin   = is_admin();

$q = trim($_GET['q'] ?? '');
$filtro = $_GET['estado'] ?? 'activos'; // activos|inactivos|todos

$params = [];
$sqlBase = "SELECT c.id, c.nombre, c.cif, c.email, c.telefono, c.usuario_id, c.activo,
                   u.nombre AS propietario
            FROM clientes c
            LEFT JOIN usuarios u ON u.id = c.usuario_id ";

$where = $isAdmin ? "WHERE 1=1 " : "WHERE c.usuario_id = ? ";
if (!$isAdmin) $params[] = $sessionId;

if ($filtro === 'activos')   { $where .= "AND c.activo = 1 "; }
elseif ($filtro === 'inactivos') { $where .= "AND c.activo = 0 "; }

if ($q !== '') {
  $where .= "AND (c.nombre LIKE ? OR c.cif LIKE ? OR c.email LIKE ? OR c.telefono LIKE ?) ";
  $like = "%{$q}%";
  array_push($params, $like, $like, $like, $like);
}

$sql = $sqlBase . $where . " ORDER BY c.nombre ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card shadow-sm">
  <div class="card-body">
    <form class="row g-2 mb-3" method="get" action="index.php">
      <input type="hidden" name="p" value="clientes-index">
      <div class="col-lg-6 col-sm-8">
        <input class="form-control" type="search" name="q" placeholder="Buscar por nombre, CIF, email o teléfono"
               value="<?= htmlspecialchars($q) ?>">
      </div>
      <div class="col-lg-3 col-sm-4">
        <select class="form-select" name="estado">
          <option value="activos"   <?= $filtro==='activos'?'selected':''; ?>>Sólo activos</option>
          <option value="inactivos" <?= $filtro==='inactivos'?'selected':''; ?>>Sólo inactivos</option>
          <option value="todos"     <?= $filtro==='todos'?'selected':''; ?>>Todos</option>
        </select>
      </div>
      <div class="col-lg-3 col-sm-12 d-grid">
        <button class="btn btn-primary">Filtrar</button>
      </div>
    </form>

    <div class="d-flex justify-content-between align-items-center mb-2">
      <h5 class="m-0">Clientes (<?= count($rows) ?>)</h5>
      <a class="btn btn-primary" href="index.php?p=clientes-nuevo">Nuevo</a>
    </div>

    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead>
          <tr>
            <th>Nombre</th><th>CIF</th><th>Email</th><th>Teléfono</th><th>Estado</th>
            <?php if ($isAdmin): ?><th>Propietario</th><?php endif; ?>
            <th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="<?= $isAdmin?7:6; ?>" class="text-center text-muted py-4">Sin resultados</td></tr>
          <?php endif; ?>
          <?php foreach($rows as $c): ?>
            <tr>
              <td><?= htmlspecialchars($c['nombre']) ?></td>
              <td><?= htmlspecialchars($c['cif']) ?></td>
              <td><?= htmlspecialchars($c['email']) ?></td>
              <td><?= htmlspecialchars($c['telefono']) ?></td>
              <td>
                <?php if ((int)$c['activo'] === 1): ?>
                  <span class="badge bg-success">Activo</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Inactivo</span>
                <?php endif; ?>
              </td>
              <?php if ($isAdmin): ?>
                <td><?= htmlspecialchars($c['propietario'] ?: "Usuario #{$c['usuario_id']}") ?></td>
              <?php endif; ?>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-secondary" href="index.php?p=clientes-editar&id=<?= (int)$c['id'] ?>">Editar</a>
                <?php if ((int)$c['activo'] === 1): ?>
                  <a class="btn btn-sm btn-outline-warning"
                     href="index.php?p=clientes-estado&id=<?= (int)$c['id'] ?>&a=desactivar"
                     onclick="return confirm('¿Desactivar este cliente?');">Desactivar</a>
                <?php else: ?>
                  <a class="btn btn-sm btn-outline-success"
                     href="index.php?p=clientes-estado&id=<?= (int)$c['id'] ?>&a=activar"
                     onclick="return confirm('¿Activar este cliente?');">Activar</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
