<?php
// views/pages/dashboard.php
require_once __DIR__ . '/../../includes/session.php';
secure_session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

$sessionId = (int)($_SESSION['usuario_id'] ?? 0);
$isAdmin   = is_admin();

// Owner (si eres admin puedes elegir; por defecto el logueado)
$ownerId = $sessionId;
if ($isAdmin && isset($_GET['owner_id']) && $_GET['owner_id'] !== '') {
  $ownerId = (int)$_GET['owner_id'];
}

// Helpers
function whereOwner($aliasCol = 'usuario_id', $ownerId = 0) { return [$aliasCol . ' = ?', [$ownerId]]; }
function scalar($pdo, $sql, $params = []) {
  $st = $pdo->prepare($sql);
  $st->execute($params);
  $v = $st->fetchColumn();
  return $v === null ? 0 : $v;
}
function nf($n){ return number_format((float)$n, 2, ',', '.'); }

// Rango del mes actual
$mesIni = date('Y-m-01');
$mesFin = date('Y-m-t');

// ===== KPIs base =====
list($wC, $pC) = whereOwner('usuario_id', $ownerId);
$k_clientes  = scalar($pdo, "SELECT COUNT(*) FROM clientes  WHERE $wC AND activo=1", $pC);
$k_productos = scalar($pdo, "SELECT COUNT(*) FROM productos WHERE $wC AND activo=1", $pC);

// ===== KPIs facturas =====
list($wF, $pF) = whereOwner('usuario_id', $ownerId);
$k_facturado_mes = scalar(
  $pdo,
  "SELECT COALESCE(SUM(total),0) FROM facturas WHERE $wF AND fecha BETWEEN ? AND ? AND estado IN ('emitida','pagada')",
  array_merge($pF, [$mesIni, $mesFin])
);
$k_cobrado_mes = scalar(
  $pdo,
  "SELECT COALESCE(SUM(total),0) FROM facturas WHERE $wF AND fecha BETWEEN ? AND ? AND estado='pagada'",
  array_merge($pF, [$mesIni, $mesFin])
);
$k_pendiente_mes = max(0, (float)$k_facturado_mes - (float)$k_cobrado_mes);
$k_num_facturas_mes = scalar(
  $pdo,
  "SELECT COUNT(*) FROM facturas WHERE $wF AND fecha BETWEEN ? AND ? AND estado IN ('emitida','pagada','borrador')",
  array_merge($pF, [$mesIni, $mesFin])
);

// ===== KPIs gastos (robusto) =====
// - Usa COALESCE(fecha, fecha_valor)
// - Fórmula directa: base + iva - base*irpf/100 (por si 'total' está NULL)
// - Cuenta estado IN ('finalizado','pagado')
list($wG, $pG) = whereOwner('usuario_id', $ownerId);
$dateExpr = "COALESCE(fecha, fecha_valor)";
$g_gastos_mes = scalar(
  $pdo,
  "SELECT COALESCE(SUM( base_imponible + IFNULL(iva,0) - base_imponible * IFNULL(irpf,0)/100 ),0)
     FROM gastos
    WHERE $wG
      AND $dateExpr BETWEEN ? AND ?
      AND estado IN ('finalizado','pagado')",
  array_merge($pG, [$mesIni, $mesFin])
);
$g_resultado_mes = (float)$k_cobrado_mes - (float)$g_gastos_mes;

// ===== Últimas facturas (JOIN con clientes) =====
list($wFjoin, $pFjoin) = whereOwner('f.usuario_id', $ownerId);
$sqlUltFact = "SELECT f.id, f.fecha, f.numero, f.serie, f.total, f.estado, c.nombre AS cliente
               FROM facturas f
               JOIN clientes c ON c.id=f.cliente_id AND c.usuario_id=f.usuario_id
               WHERE $wFjoin
               ORDER BY f.fecha DESC, f.id DESC
               LIMIT 8";
$stUF = $pdo->prepare($sqlUltFact);
$stUF->execute($pFjoin);
$ultFact = $stUF->fetchAll(PDO::FETCH_ASSOC);

// ===== Últimos clientes =====
$sqlUltCli = "SELECT id, nombre, fecha_creacion FROM clientes WHERE $wC ORDER BY fecha_creacion DESC, id DESC LIMIT 6";
$stUC = $pdo->prepare($sqlUltCli);
$stUC->execute($pC);
$ultCli = $stUC->fetchAll(PDO::FETCH_ASSOC);

// ===== Serie últimos 6 meses (emitidas+pagadas) =====
$serie = [];
for ($i = 5; $i >= 0; $i--) {
  $first = date('Y-m-01', strtotime("-$i months"));
  $last  = date('Y-m-t',  strtotime("-$i months"));
  $val = scalar(
    $pdo,
    "SELECT COALESCE(SUM(total),0) FROM facturas WHERE $wF AND fecha BETWEEN ? AND ? AND estado IN ('emitida','pagada')",
    array_merge($pF, [$first, $last])
  );
  $serie[] = ['label' => date('M', strtotime($first)), 'value' => (float)$val];
}
$maxVal = max(array_column($serie, 'value')) ?: 1.0;

// Selector admin
$users = [];
if ($isAdmin) {
  $users = $pdo->query("SELECT id, COALESCE(nombre,email) AS nom FROM usuarios ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
}

// Debug opcional
if (isset($_GET['debug'])) {
  echo "<pre style='background:#111;color:#0f0;padding:8px;border-radius:6px'>";
  echo "DEBUG DASHBOARD\n";
  echo "ownerId: {$ownerId}\n";
  echo "Clientes: {$k_clientes} | Productos: {$k_productos}\n";
  echo "Facturado: {$k_facturado_mes} | Cobrado: {$k_cobrado_mes} | Pendiente: {$k_pendiente_mes}\n";
  echo "Gastos (mes): {$g_gastos_mes} | Resultado: {$g_resultado_mes}\n";
  echo "</pre>";
}
?>
<style>
  .kpi-card .kpi { font-size:1.35rem; font-weight:800; }
  .kpi-card .sub { color:#6c757d; }
  .mini-bars { display:flex; gap:8px; align-items:flex-end; height:120px; padding:8px 6px; }
  .mini-bar { flex:1; display:flex; flex-direction:column; align-items:center; gap:6px; }
  .mini-bar .bar { width: 100%; border-radius:6px 6px 0 0; background:#e7f1ff; border:1px solid #cfe2ff; }
  .mini-bar .lbl { font-size:.8rem; color:#6c757d; }
  .badge-soft { border-radius:.5rem; padding:.2rem .5rem; font-weight:600; font-size:.8rem; }
  .badge-borrador { background:#f1f3f5; color:#495057; }
  .badge-emitida  { background:#e7f1ff; color:#0b5ed7; }
  .badge-pagada   { background:#dcfce7; color:#166534; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="m-0">Resumen</h5>
  <?php if ($isAdmin): ?>
    <form method="get" action="index.php" class="d-flex align-items-center gap-2">
      <input type="hidden" name="p" value="dashboard">
      <select class="form-select form-select-sm" name="owner_id" onchange="this.form.submit()">
        <?php foreach($users as $u): ?>
          <option value="<?= (int)$u['id'] ?>" <?= ($ownerId===(int)$u['id']?'selected':'') ?>>
            #<?= (int)$u['id'] ?> · <?= htmlspecialchars($u['nom']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <a class="btn btn-sm btn-outline-secondary" href="index.php?p=dashboard&owner_id=<?= (int)$sessionId ?>">Yo</a>
    </form>
  <?php endif; ?>
</div>

<div class="row g-3">
  <div class="col-xl-3 col-sm-6">
    <div class="card kpi-card shadow-sm"><div class="card-body">
      <div class="sub">Clientes activos</div>
      <div class="kpi"><?= (int)$k_clientes ?></div>
    </div></div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="card kpi-card shadow-sm"><div class="card-body">
      <div class="sub">Productos activos</div>
      <div class="kpi"><?= (int)$k_productos ?></div>
    </div></div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="card kpi-card shadow-sm"><div class="card-body">
      <div class="sub">Facturado (mes)</div>
      <div class="kpi"><?= nf($k_facturado_mes) ?> €</div>
      <div class="text-muted small"><?= (int)$k_num_facturas_mes ?> factura(s)</div>
    </div></div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="card kpi-card shadow-sm"><div class="card-body">
      <div class="sub">Cobrado / Pendiente (mes)</div>
      <div class="kpi"><?= nf($k_cobrado_mes) ?> €</div>
      <div class="text-muted small">Pendiente: <?= nf($k_pendiente_mes) ?> €</div>
    </div></div>
  </div>

  <div class="col-xl-3 col-sm-6">
    <div class="card kpi-card shadow-sm"><div class="card-body">
      <div class="sub">Gastos (mes)</div>
      <div class="kpi"><?= nf($g_gastos_mes) ?> €</div>
    </div></div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="card kpi-card shadow-sm"><div class="card-body">
      <div class="sub">Resultado (Cobrado − Gastos)</div>
      <div class="kpi"><?= nf($g_resultado_mes) ?> €</div>
    </div></div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-xl-6">
    <div class="card shadow-sm h-100"><div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="m-0">Facturación últimos 6 meses</h6>
        <span class="text-muted small">Importe emitido+pagado</span>
      </div>
      <div class="mini-bars">
        <?php $maxVal = $maxVal ?: 1; foreach ($serie as $pt):
          $h = max(6, (int)round(100 * ($pt['value'] / $maxVal))); ?>
          <div class="mini-bar">
            <div class="bar" style="height: <?= $h ?>%;"></div>
            <div class="lbl"><?= htmlspecialchars(ucfirst($pt['label'])) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="text-muted small">Máximo del periodo: <?= nf($maxVal) ?> €</div>
    </div></div>
  </div>

  <div class="col-xl-6">
    <div class="card shadow-sm h-100"><div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="m-0">Últimas facturas</h6>
        <a class="btn btn-sm btn-outline-secondary" href="index.php?p=facturas-index">Ver todas</a>
      </div>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead><tr>
            <th>Fecha</th><th>Número</th><th>Cliente</th>
            <th class="text-end">Total</th><th class="text-end">Estado</th><th></th>
          </tr></thead>
          <tbody>
            <?php if (!$ultFact): ?>
              <tr><td colspan="6" class="text-center text-muted py-3">Sin facturas</td></tr>
            <?php endif; ?>
            <?php foreach($ultFact as $f): ?>
              <tr>
                <td><?= htmlspecialchars($f['fecha']) ?></td>
                <td><?= htmlspecialchars(($f['serie']?:'A').'-'.$f['numero']) ?></td>
                <td><?= htmlspecialchars($f['cliente']) ?></td>
                <td class="text-end"><?= nf($f['total']) ?> €</td>
                <td class="text-end">
                  <?php $cls = $f['estado']==='pagada' ? 'badge-pagada' : ($f['estado']==='emitida' ? 'badge-emitida' : 'badge-borrador'); ?>
                  <span class="badge-soft <?= $cls ?>"><?= htmlspecialchars(ucfirst($f['estado'])) ?></span>
                </td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-primary" href="index.php?p=facturas-generarpdf&id=<?= (int)$f['id'] ?>">Abrir</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="mt-3 d-flex gap-2">
        <a class="btn btn-primary" href="index.php?p=facturas-nuevo">Nueva factura</a>
        <a class="btn btn-outline-secondary" href="index.php?p=clientes-index">Clientes</a>
        <a class="btn btn-outline-secondary" href="index.php?p=productos-index">Productos</a>
      </div>
    </div></div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-12">
    <div class="card shadow-sm"><div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="m-0">Últimos clientes</h6>
        <a class="btn btn-sm btn-outline-secondary" href="index.php?p=clientes-index">Ver todos</a>
      </div>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead><tr><th>Nombre</th><th>Alta</th><th></th></tr></thead>
          <tbody>
            <?php if (!$ultCli): ?>
              <tr><td colspan="3" class="text-center text-muted py-3">Sin clientes recientes</td></tr>
            <?php endif; ?>
            <?php foreach($ultCli as $c): ?>
              <tr>
                <td><?= htmlspecialchars($c['nombre']) ?></td>
                <td><?= htmlspecialchars($c['fecha_creacion'] ?: '') ?></td>
                <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="index.php?p=clientes-editar&id=<?= (int)$c['id'] ?>">Abrir</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <a class="btn btn-outline-primary" href="index.php?p=clientes-nuevo">Nuevo cliente</a>
    </div></div>
  </div>
</div>
