<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/csrf.php';
$sessionId = (int)$_SESSION['usuario_id'];
$isAdmin   = is_admin();

$q      = trim($_GET['q'] ?? '');
$filtro = $_GET['estado'] ?? 'activos'; // activos|inactivos|todos

$params = [];
$sqlBase = "SELECT p.id, p.referencia, p.nombre, p.descripcion, p.precio_unitario,
                   p.iva_porcentaje, p.activo, p.usuario_id, u.nombre AS propietario
            FROM productos p
            LEFT JOIN usuarios u ON u.id = p.usuario_id ";

$where = $isAdmin ? "WHERE 1=1 " : "WHERE p.usuario_id = ? ";
if (!$isAdmin) $params[] = $sessionId;

if ($filtro === 'activos')      { $where .= "AND p.activo = 1 "; }
elseif ($filtro === 'inactivos'){ $where .= "AND p.activo = 0 "; }

if ($q !== '') {
  $where .= "AND (p.nombre LIKE ? OR p.referencia LIKE ? OR p.descripcion LIKE ?) ";
  $like = "%{$q}%";
  array_push($params, $like, $like, $like);
}

$sql = $sqlBase . $where . " ORDER BY p.nombre ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card shadow-sm">
  <div class="card-body">
    <form class="row g-2 mb-3" method="get" action="index.php">
      <input type="hidden" name="p" value="productos-index">
      <div class="col-lg-6 col-sm-8">
        <input class="form-control" type="search" name="q" placeholder="Buscar por nombre, referencia o descripción"
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
      <h5 class="m-0">Productos (<?= count($rows) ?>)</h5>
      <a class="btn btn-primary" href="index.php?p=productos-nuevo">Nuevo</a>
    </div>

    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead>
          <tr>
            <th>Referencia</th><th>Nombre</th><th>Precio</th><th>IVA %</th><th>Estado</th>
            <?php if ($isAdmin): ?><th>Propietario</th><?php endif; ?>
            <th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="<?= $isAdmin?7:6; ?>" class="text-center text-muted py-4">Sin resultados</td></tr>
          <?php endif; ?>
          <?php foreach($rows as $p): ?>
            <tr>
              <td><?= htmlspecialchars($p['referencia'] ?? '') ?></td>
              <td>
                <div class="fw-semibold"><?= htmlspecialchars($p['nombre']) ?></div>
                <?php if (!empty($p['descripcion'])): ?>
                  <div class="text-muted small"><?= htmlspecialchars($p['descripcion']) ?></div>
                <?php endif; ?>
              </td>
              <td><?= number_format((float)$p['precio_unitario'], 2, ',', '.') ?> €</td>
              <td><?= number_format((float)($p['iva_porcentaje'] ?? 21), 2, ',', '.') ?></td>
              <td>
                <?php if ((int)$p['activo'] === 1): ?>
                  <span class="badge bg-success">Activo</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Inactivo</span>
                <?php endif; ?>
              </td>
              <?php if ($isAdmin): ?>
                <td><?= htmlspecialchars($p['propietario'] ?: "Usuario #{$p['usuario_id']}") ?></td>
              <?php endif; ?>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-secondary" href="index.php?p=productos-editar&id=<?= (int)$p['id'] ?>">Editar</a>
                <?php if ((int)$p['activo'] === 1): ?>
                  <a class="btn btn-sm btn-outline-warning"
                     href="index.php?p=productos-estado&id=<?= (int)$p['id'] ?>&a=desactivar&csrf_token=<?= csrf_token() ?>"
                     onclick="return confirm('¿Desactivar este producto?');">Desactivar</a>
                <?php else: ?>
                  <a class="btn btn-sm btn-outline-success"
                     href="index.php?p=productos-estado&id=<?= (int)$p['id'] ?>&a=activar&csrf_token=<?= csrf_token() ?>"
                     onclick="return confirm('¿Activar este producto?');">Activar</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
