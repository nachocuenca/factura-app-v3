<?php
// views/pages/gastos/index.php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/csrf.php';
require_once __DIR__ . '/../../../includes/helpers.php';

$uid = (int)$_SESSION['usuario_id'];

// ===== Filtros =====
$y   = isset($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');
$m   = isset($_GET['m']) ? (int)$_GET['m'] : (int)date('m');
$cat = isset($_GET['cat']) ? trim($_GET['cat']) : '';
$est = $_GET['estado'] ?? ''; // '', 'borrador','finalizado','pagado'

$mes   = str_pad((string)$m, 2, '0', STR_PAD_LEFT);
$desde = "$y-$mes-01";
$hasta = date('Y-m-t', strtotime($desde));

// Categorías del usuario (texto libre)
$stCat = $pdo->prepare("SELECT DISTINCT categoria 
                        FROM gastos 
                        WHERE usuario_id=? AND categoria IS NOT NULL AND categoria<>'' 
                        ORDER BY categoria");
$stCat->execute([$uid]);
$categorias = $stCat->fetchAll(PDO::FETCH_COLUMN);

// WHERE + params
$where  = ["usuario_id = ?", "fecha BETWEEN ? AND ?"];
$params = [$uid, $desde, $hasta];
if ($cat !== '') { $where[] = "categoria = ?"; $params[] = $cat; }
if (in_array($est, ['borrador','finalizado','pagado'], true)) { $where[] = "estado = ?"; $params[] = $est; }
$whereSql = implode(' AND ', $where);

// Totales del periodo
$stTot = $pdo->prepare("SELECT 
  COALESCE(SUM(base_imponible),0) AS s_base,
  COALESCE(SUM(iva),0)            AS s_iva,
  COALESCE(SUM(total),0)          AS s_total
  FROM gastos
  WHERE $whereSql");
$stTot->execute($params);
$tot = $stTot->fetch(PDO::FETCH_ASSOC) ?: ['s_base'=>0,'s_iva'=>0,'s_total'=>0];

// Listado
$stL = $pdo->prepare("SELECT 
    id, fecha, numero, categoria, base_imponible, tipo_iva, iva, irpf, total, estado, soportado_deducible, archivo
  FROM gastos
  WHERE $whereSql
  ORDER BY fecha DESC, id DESC");
$stL->execute($params);
$rows = $stL->fetchAll(PDO::FETCH_ASSOC);

// Helper números
function nf($n){ return number_format((float)$n, 2, ',', '.'); }
?>
<style>
  .badge-soft { border-radius:.5rem; padding:.2rem .5rem; font-weight:600; font-size:.8rem; }
  .b-borr { background:#f1f3f5; color:#495057; }
  .b-fin  { background:#e7f1ff; color:#0b5ed7; }
  .b-pag  { background:#dcfce7; color:#166534; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="m-0">Gastos</h5>
  <a class="btn btn-primary" href="index.php?p=gastos-nuevo">Nuevo gasto</a>
</div>

<form class="row g-2 mb-3" method="get" action="index.php">
  <input type="hidden" name="p" value="gastos-index">
  <div class="col-auto">
    <label class="form-label">Año</label>
    <input type="number" name="y" class="form-control" value="<?= h((string)$y) ?>" style="width:110px">
  </div>
  <div class="col-auto">
    <label class="form-label">Mes</label>
    <select name="m" class="form-select">
      <?php for($i=1;$i<=12;$i++): ?>
        <option value="<?= $i ?>" <?= $i===$m?'selected':'' ?>><?= str_pad((string)$i,2,'0',STR_PAD_LEFT) ?></option>
      <?php endfor; ?>
    </select>
  </div>
  <div class="col-auto">
    <label class="form-label">Categoría</label>
    <select name="cat" class="form-select">
      <option value="">(todas)</option>
      <?php foreach($categorias as $c): ?>
        <option value="<?= h($c) ?>" <?= $c===$cat?'selected':'' ?>><?= h($c) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto">
    <label class="form-label">Estado</label>
    <select name="estado" class="form-select">
      <option value="" <?= $est===''?'selected':''?>>(todos)</option>
      <option value="borrador"   <?= $est==='borrador'?'selected':'' ?>>Borrador</option>
      <option value="finalizado" <?= $est==='finalizado'?'selected':'' ?>>Finalizado</option>
      <option value="pagado"     <?= $est==='pagado'?'selected':'' ?>>Pagado</option>
    </select>
  </div>
  <div class="col-auto align-self-end">
    <button class="btn btn-outline-secondary">Filtrar</button>
  </div>
</form>

<div class="row g-3 mb-2">
  <div class="col-md-4">
    <div class="card shadow-sm"><div class="card-body">
      <div class="text-muted">Base imponible</div>
      <div class="h5 m-0"><?= nf($tot['s_base']) ?> €</div>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card shadow-sm"><div class="card-body">
      <div class="text-muted">IVA (cuota)</div>
      <div class="h5 m-0"><?= nf($tot['s_iva']) ?> €</div>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card shadow-sm"><div class="card-body">
      <div class="text-muted">Total</div>
      <div class="h5 m-0"><?= nf($tot['s_total']) ?> €</div>
    </div></div>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Número</th>
            <th>Categoría</th>
            <th class="text-end">Base</th>
            <th class="text-end">IVA %</th>
            <th class="text-end">IVA €</th>
            <th class="text-end">IRPF %</th>
            <th class="text-end">Total</th>
            <th class="text-center">Adjunto</th>
            <th class="text-center">Deducible</th>
            <th class="text-end">Estado</th>
            <th class="text-end" style="min-width:260px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="12" class="text-center text-muted py-3">Sin gastos en el periodo</td></tr>
          <?php endif; ?>

          <?php foreach($rows as $r): ?>
            <tr>
              <td><?= h($r['fecha'] ?? '') ?></td>
              <td><?= h($r['numero'] ?? '') ?></td>
              <td><?= h($r['categoria'] ?? '') ?></td>
              <td class="text-end"><?= nf($r['base_imponible']) ?> €</td>
              <td class="text-end"><?= nf($r['tipo_iva']) ?></td>
              <td class="text-end"><?= nf($r['iva']) ?> €</td>
              <td class="text-end"><?= nf($r['irpf'] ?? 0) ?></td>
              <td class="text-end"><?= nf($r['total'] ?? 0) ?> €</td>
              <td class="text-center">
                <?php if (!empty($r['archivo'])): ?>
                  <!-- Enlace seguro vía controlador -->
                  <a href="index.php?p=gastos-archivo&id=<?= (int)$r['id'] ?>" target="_blank" title="Ver adjunto">📎 Ver</a>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
              <td class="text-center"><?= (int)$r['soportado_deducible'] ? '✔️' : '—' ?></td>
              <td class="text-end">
                <?php
                  $cls = $r['estado']==='pagado' ? 'b-pag' : ($r['estado']==='finalizado' ? 'b-fin' : 'b-borr');
                ?>
                <span class="badge-soft <?= $cls ?>"><?= h(ucfirst($r['estado'])) ?></span>
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <!-- Adjuntar/Reemplazar archivo -->
                  <a class="btn btn-outline-dark" href="index.php?p=gastos-adjuntar&id=<?= (int)$r['id'] ?>">Adjuntar</a>

                  <!-- Acciones de estado -->
                  <?php if ($r['estado']!=='pagado'): ?>
                      <form method="post" action="index.php?p=gastos-estado" class="d-inline">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <input type="hidden" name="to" value="pagado">
                        <button class="btn btn-outline-success" onclick="return confirm('¿Marcar como pagado?');">Pagado</button>
                      </form>
                  <?php endif; ?>
                  <?php if ($r['estado']!=='finalizado'): ?>
                      <form method="post" action="index.php?p=gastos-estado" class="d-inline">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <input type="hidden" name="to" value="finalizado">
                        <button class="btn btn-outline-primary" onclick="return confirm('¿Marcar como finalizado?');">Finalizar</button>
                      </form>
                  <?php endif; ?>
                  <?php if ($r['estado']!=='borrador'): ?>
                      <form method="post" action="index.php?p=gastos-estado" class="d-inline">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <input type="hidden" name="to" value="borrador">
                        <button class="btn btn-outline-secondary" onclick="return confirm('¿Marcar como borrador?');">Borrador</button>
                      </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>

        </tbody>
      </table>
    </div>
  </div>
</div>
