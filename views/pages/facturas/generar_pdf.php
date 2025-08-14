<?php
// views/pages/facturas/generar_pdf.php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/helpers.php';
$sessionId = (int)$_SESSION['usuario_id'];
$isAdmin   = is_admin();

$factura_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

// Cargar factura + cliente (control de permisos)
if ($isAdmin) {
  $q = $pdo->prepare("SELECT f.*, c.nombre AS cliente, c.cif, c.direccion, c.cp, c.localidad, c.provincia, c.email
                      FROM facturas f
                      JOIN clientes c ON c.id=f.cliente_id AND c.usuario_id=f.usuario_id
                      WHERE f.id=?");
  $q->execute([$factura_id]);
} else {
  $q = $pdo->prepare("SELECT f.*, c.nombre AS cliente, c.cif, c.direccion, c.cp, c.localidad, c.provincia, c.email
                      FROM facturas f
                      JOIN clientes c ON c.id=f.cliente_id AND c.usuario_id=f.usuario_id
                      WHERE f.id=? AND f.usuario_id=?");
  $q->execute([$factura_id, $sessionId]);
}
$f = $q->fetch(PDO::FETCH_ASSOC);
if (!$f) { die('Factura no encontrada o sin permisos'); }

$ownerId = (int)$f['usuario_id'];

// Preferencias/datos del emisor (usuario propietario de la factura)
$us = $pdo->prepare("SELECT nombre, cif, direccion, cp, localidad, provincia, telefono, email, logo, color_primario, fuente_factura, pie_factura, notas_factura
                     FROM usuarios WHERE id=?");
$us->execute([$ownerId]);
$pref = $us->fetch(PDO::FETCH_ASSOC) ?: [];
$color = $pref['color_primario'] ?: '#000000';
$font  = $pref['fuente_factura'] ?: 'system-ui';
$logo  = !empty($pref['logo']) ? $pref['logo'] : null; // guardamos rutas tipo "uploads/logos/xxx.png" (relativas a /public)

// Líneas de factura
$ls = $pdo->prepare("SELECT * FROM factura_productos WHERE factura_id=? AND usuario_id=? ORDER BY id");
$ls->execute([$factura_id, $ownerId]);
$lineas = $ls->fetchAll(PDO::FETCH_ASSOC);

// Totales + desglose por tipo de IVA
$base=0; $ivaTot=0;
$desglose = []; // [iva%] => ['base'=>, 'iva'=>]
foreach ($lineas as $l){
  $sub   = (float)$l['subtotal'];
  $ivaPc = (float)$l['iva_porcentaje'];
  $base += $sub;
  $ivaCuota = round($sub * $ivaPc / 100, 2);
  $ivaTot  += $ivaCuota;

  if (!isset($desglose[$ivaPc])) $desglose[$ivaPc] = ['base'=>0.0, 'iva'=>0.0];
  $desglose[$ivaPc]['base'] += $sub;
  $desglose[$ivaPc]['iva']  += $ivaCuota;
}
$irpfPercent = $f['irpf'] !== null ? (float)$f['irpf'] : 0.0;
$irpfTot     = round($base * $irpfPercent / 100, 2);
$totalSrv    = round($base + $ivaTot - $irpfTot, 2);

function nf($n){ return number_format((float)$n, 2, ',', '.'); }
// Badge por estado
$badgeClass = 'secondary';
if ($f['estado']==='emitida') $badgeClass='primary';
if ($f['estado']==='pagada')  $badgeClass='success';
?>
<style>
:root{
  --pri: <?= h($color) ?>;
  --ff: <?= h($font) ?>, system-ui, -apple-system, "Segoe UI", Roboto, Arial, "Helvetica Neue", sans-serif;
}
*{ box-sizing:border-box; }
body{ background:#f6f7f9; }
.inv-wrap { max-width: 920px; margin: 0 auto; background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; font-family:var(--ff); }
.inv-head { display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; padding:20px 24px; border-bottom: 3px solid var(--pri); }
.inv-id   { font-size:1.35rem; font-weight:800; color: var(--pri); letter-spacing:.3px; }
.inv-meta { text-align:right; color:#555; font-size:.92rem; }
.inv-meta .emisor { margin-top:8px; }
.logo-box img { max-height:64px; object-fit:contain; }

.badge { display:inline-block; padding:.25rem .55rem; border-radius:.5rem; font-size:.82rem; font-weight:600; }
.badge.secondary{ background:#e9ecef; color:#444; }
.badge.primary{ background: color-mix(in srgb, var(--pri) 18%, white); color: var(--pri); }
.badge.success{ background:#dcfce7; color:#166534; }

.grid-two { display:grid; grid-template-columns: 1fr 1fr; gap:16px; padding:16px 24px 0; }
.card-soft { border:1px solid #eef0f3; border-radius:10px; padding:12px 14px; background:#fafafa; }

.table-inv { width:100%; border-collapse: collapse; margin-top:16px; }
.table-inv th, .table-inv td { border-bottom:1px solid #eee; padding:.6rem .6rem; }
.table-inv thead th { background:#f8f9fa; text-align:left; color:#555; font-size:.9rem; }
.t-right { text-align:right; }

.totals { margin: 16px 24px 0 auto; display:grid; gap:.5rem; max-width:420px; }
.total-card { border:1px solid #e5e7eb; border-radius:10px; padding: .85rem 1rem; background:#f8f9fa; }
.total-row { display:flex; justify-content:space-between; }
.total-row b{ font-weight:700; }

.note { margin:14px 24px 0; font-size:.95rem; color:#444; white-space:pre-wrap; }
.footer { margin:14px 24px 20px; font-size:.95rem; color:#222; font-weight:600; }

.actions { display:flex; gap:.5rem; padding:16px 24px 20px; }
@media print {
  .actions, nav, footer { display:none !important; }
  body{ background:#fff; }
  .inv-wrap { border:0; border-radius:0; }
}
</style>

<div class="inv-wrap">
  <!-- CABECERA -->
  <div class="inv-head">
    <div class="logo-box">
      <?php if ($logo): ?>
        <img src="<?= h($logo) ?>" alt="Logo">
      <?php else: ?>
        <div style="font-weight:800; color:var(--pri); font-size:1.1rem; letter-spacing:.5px;">
          <?= h($pref['nombre'] ?: 'Mi empresa') ?>
        </div>
      <?php endif; ?>
    </div>
    <div style="text-align:right;">
      <div class="inv-id">Factura <?= h(($f['serie'] ?: 'A').'-'.$f['numero']) ?></div>
      <div class="text-muted">Fecha: <?= h($f['fecha']) ?></div>
      <div class="mt-1"><span class="badge <?= $badgeClass ?>"><?= h(ucfirst($f['estado'])) ?></span></div>
    </div>
  </div>

  <!-- EMISOR / CLIENTE -->
  <div class="grid-two">
    <div class="card-soft">
      <div style="font-weight:700; color:#222; margin-bottom:6px;">Emisor</div>
      <div><?= h($pref['nombre'] ?: '') ?></div>
      <?php if(!empty($pref['cif'])): ?><div class="text-muted">CIF: <?= h($pref['cif']) ?></div><?php endif; ?>
      <?php if(!empty($pref['direccion'])): ?><div class="text-muted"><?= h($pref['direccion']) ?></div><?php endif; ?>
      <div class="text-muted">
        <?= h(trim(($pref['cp']?:'').' '.($pref['localidad']?:'').' '.($pref['provincia']?:''))) ?>
      </div>
      <?php if(!empty($pref['telefono'])): ?><div class="text-muted">Tel: <?= h($pref['telefono']) ?></div><?php endif; ?>
      <?php if(!empty($pref['email'])): ?><div class="text-muted"><?= h($pref['email']) ?></div><?php endif; ?>
    </div>

    <div class="card-soft">
      <div style="font-weight:700; color:#222; margin-bottom:6px;">Cliente</div>
      <div><?= h($f['cliente']) ?></div>
      <?php if(!empty($f['cif'])): ?><div class="text-muted">CIF: <?= h($f['cif']) ?></div><?php endif; ?>
      <?php if(!empty($f['direccion'])): ?><div class="text-muted"><?= h($f['direccion']) ?></div><?php endif; ?>
      <div class="text-muted">
        <?= h(trim(($f['cp']?:'').' '.($f['localidad']?:'').' '.($f['provincia']?:''))) ?>
      </div>
      <?php if(!empty($f['email'])): ?><div class="text-muted"><?= h($f['email']) ?></div><?php endif; ?>
    </div>
  </div>

  <!-- LÍNEAS -->
  <div class="table-responsive" style="padding:0 24px;">
    <table class="table-inv">
      <thead>
        <tr>
          <th>Descripción</th>
          <th class="t-right">Cant.</th>
          <th class="t-right">Precio</th>
          <th class="t-right">IVA %</th>
          <th class="t-right">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($lineas as $l): ?>
          <tr>
            <td><?= h($l['nombre']) ?></td>
            <td class="t-right"><?= nf($l['cantidad']) ?></td>
            <td class="t-right"><?= nf($l['precio_unitario']) ?> €</td>
            <td class="t-right"><?= nf($l['iva_porcentaje']) ?></td>
            <td class="t-right"><?= nf($l['subtotal']) ?> €</td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- DESGLOSE IVA (si hay varios tipos) -->
  <?php if (count($desglose) > 1): ?>
    <div class="table-responsive" style="max-width:560px; margin: 6px 24px 0 auto;">
      <table class="table-inv">
        <thead>
          <tr><th>Tipo IVA</th><th class="t-right">Base</th><th class="t-right">Cuota IVA</th></tr>
        </thead>
        <tbody>
        <?php ksort($desglose, SORT_NUMERIC); foreach($desglose as $pct => $d): ?>
          <tr>
            <td><?= nf($pct) ?> %</td>
            <td class="t-right"><?= nf($d['base']) ?> €</td>
            <td class="t-right"><?= nf($d['iva']) ?> €</td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <!-- TOTALES -->
  <div class="totals">
    <div class="total-card">
      <div class="total-row"><span>Base imponible</span><b><?= nf($base) ?> €</b></div>
      <div class="total-row"><span>IVA total</span><b><?= nf($ivaTot) ?> €</b></div>
      <div class="total-row"><span>IRPF (<?= nf($irpfPercent) ?>%)</span><b><?= nf($irpfTot) ?> €</b></div>
      <hr class="my-2" style="border:none;border-top:1px solid #e5e7eb">
      <div class="total-row" style="font-size:1.12rem; color:var(--pri);"><span>Total</span><b><?= nf($totalSrv) ?> €</b></div>
    </div>
  </div>

  <!-- Notas / Pie -->
  <?php if (!empty($pref['notas_factura'])): ?>
    <div class="note"><?= nl2br(h($pref['notas_factura'])) ?></div>
  <?php endif; ?>
  <?php if (!empty($pref['pie_factura'])): ?>
    <div class="footer"><?= nl2br(h($pref['pie_factura'])) ?></div>
  <?php endif; ?>

  <!-- ACCIONES -->
  <div class="actions">
    <button class="btn btn-primary" onclick="window.print()">Imprimir / Guardar PDF</button>
    <a class="btn btn-outline-secondary" href="index.php?p=facturas-index">Volver</a>
  </div>
</div>
