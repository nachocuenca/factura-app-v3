<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/csrf.php';
require_once __DIR__ . '/../../../includes/helpers.php';
$sessionId = (int)$_SESSION['usuario_id'];
$isAdmin   = is_admin();

$q       = trim($_GET['q'] ?? '');
$estado  = $_GET['estado'] ?? 'todos'; // todos|borrador|emitida|pagada
$fini    = $_GET['fini'] ?? '';
$ffin    = $_GET['ffin'] ?? '';

$params = [];
$sql = "SELECT f.id, f.fecha, f.numero, f.serie, f.base_imponible, f.total, f.estado,
               c.nombre AS cliente
        FROM facturas f
        JOIN clientes c ON c.id = f.cliente_id AND c.usuario_id = f.usuario_id ";

$where = $isAdmin ? "WHERE 1=1 " : "WHERE f.usuario_id = ? ";
if (!$isAdmin) $params[] = $sessionId;

if (in_array($estado, ['borrador','emitida','pagada'], true)) {
  $where .= "AND f.estado = ? ";
  $params[] = $estado;
}
if ($fini !== '') { $where .= "AND f.fecha >= ? "; $params[] = $fini; }
if ($ffin !== '') { $where .= "AND f.fecha <= ? "; $params[] = $ffin; }

if ($q !== '') {
  $where .= "AND (f.numero LIKE ? OR c.nombre LIKE ?) ";
  $like = "%{$q}%";
  array_push($params, $like, $like);
}

$sql .= $where . " ORDER BY f.fecha DESC, f.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card shadow-sm">
  <div class="card-body">
    <form class="row g-2 mb-3" method="get" action="index.php">
      <input type="hidden" name="p" value="facturas-index">
      <div class="col-lg-4 col-sm-6">
          <input class="form-control" type="search" name="q" placeholder="Buscar por nº o cliente" value="<?= h($q) ?>">
      </div>
      <div class="col-lg-2 col-6">
        <select class="form-select" name="estado">
          <option value="todos"   <?= $estado==='todos'?'selected':''; ?>>Todos</option>
          <option value="borrador"<?= $estado==='borrador'?'selected':''; ?>>Borrador</option>
          <option value="emitida" <?= $estado==='emitida'?'selected':''; ?>>Emitida</option>
          <option value="pagada"  <?= $estado==='pagada'?'selected':''; ?>>Pagada</option>
        </select>
      </div>
        <div class="col-lg-2 col-6"><input class="form-control" type="date" name="fini" value="<?= h($fini) ?>"></div>
        <div class="col-lg-2 col-6"><input class="form-control" type="date" name="ffin" value="<?= h($ffin) ?>"></div>
      <div class="col-lg-2 col-6 d-grid"><button class="btn btn-primary">Filtrar</button></div>
    </form>

    <div class="d-flex justify-content-between align-items-center mb-2">
      <h5 class="m-0">Facturas (<?= count($rows) ?>)</h5>
      <a class="btn btn-primary" href="index.php?p=facturas-nuevo">Nueva</a>
    </div>

    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead>
          <tr>
            <th>Fecha</th><th>Número</th><th>Cliente</th><th class="text-end">Base</th><th class="text-end">Total</th><th>Estado</th><th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Sin resultados</td></tr>
          <?php endif; ?>
          <?php foreach($rows as $f): ?>
            <tr>
                <td><?= h($f['fecha']) ?></td>
                <td><?= h(($f['serie'] ?: 'A') . '-' . $f['numero']) ?></td>
                <td><?= h($f['cliente']) ?></td>
              <td class="text-end"><?= number_format((float)$f['base_imponible'], 2, ',', '.') ?> €</td>
              <td class="text-end"><?= number_format((float)$f['total'], 2, ',', '.') ?> €</td>
              <td>
                <?php if ($f['estado']==='pagada'): ?>
                  <span class="badge bg-success">Pagada</span>
                <?php elseif ($f['estado']==='emitida'): ?>
                  <span class="badge bg-primary">Emitida</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Borrador</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-secondary" href="index.php?p=facturas-generarpdf&id=<?= (int)$f['id'] ?>">Ver</a>
                <div class="btn-group">
                  <a class="btn btn-sm btn-outline-warning dropdown-toggle" data-bs-toggle="dropdown" href="#">Estado</a>
                  <ul class="dropdown-menu dropdown-menu-end">
                      <li><form method="post" action="index.php?p=facturas-estado" class="m-0">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
        <input type="hidden" name="e" value="borrador">
        <button type="submit" class="dropdown-item">Borrador</button>
      </form></li>
                      <li><form method="post" action="index.php?p=facturas-estado" class="m-0">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
        <input type="hidden" name="e" value="emitida">
        <button type="submit" class="dropdown-item">Emitida</button>
      </form></li>
                      <li><form method="post" action="index.php?p=facturas-estado" class="m-0">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
        <input type="hidden" name="e" value="pagada">
        <button type="submit" class="dropdown-item">Pagada</button>
      </form></li>
                  </ul>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>
