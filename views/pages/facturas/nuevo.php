<?php
// views/pages/facturas/nuevo.php
require_once __DIR__ . '/../../../includes/auth.php'; // sesión + $pdo

$uid = (int)$_SESSION['usuario_id'];

/* Helpers */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function resolver_serie(?string $cfgSerie, string $fecha): string {
  $ts = strtotime($fecha ?: date('Y-m-d'));
  $yy = date('y', $ts);
  $yyyy = date('Y', $ts);
  if ($cfgSerie) {
    $serie = str_replace(['{yy}','{yyyy}'], [$yy, $yyyy], $cfgSerie);
    return trim($serie) !== '' ? $serie : $yy;
  }
  return $yy;
}

/* Datos base */
$fecha = $_POST['fecha'] ?? date('Y-m-d');
$cfgSerie = $_SESSION['serie_facturas'] ?? null;
$serieSugerida = resolver_serie($cfgSerie, $fecha);

$stMax = $pdo->prepare("SELECT MAX(CAST(numero AS UNSIGNED)) FROM facturas WHERE usuario_id=? AND serie=?");
$stMax->execute([$uid, $serieSugerida]);
$maxActual = (int)$stMax->fetchColumn();
$inicioCfg = (int)($_SESSION['inicio_serie_facturas'] ?? 1);
$numeroSugerido = $maxActual > 0 ? $maxActual + 1 : max(1, $inicioCfg);

/* Clientes */
$stCli = $pdo->prepare("SELECT id, nombre, cif FROM clientes WHERE usuario_id=? AND activo=1 ORDER BY nombre");
$stCli->execute([$uid]);
$clientes = $stCli->fetchAll(PDO::FETCH_ASSOC);

/* Productos (para el selector) */
$stProd = $pdo->prepare("SELECT id, referencia, nombre, precio_unitario, iva_porcentaje FROM productos WHERE usuario_id=? AND activo=1 ORDER BY nombre");
$stProd->execute([$uid]);
$productos = $stProd->fetchAll(PDO::FETCH_ASSOC);
?>
<style>
  .prod-search{ border-radius:10px; }
  .prod-row{ cursor:pointer; }
  .prod-row:hover{ background:#f6f9ff; }
  .cell-actions{ white-space:nowrap; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="m-0">Nueva factura</h5>
  <a class="btn btn-outline-secondary" href="index.php?p=facturas-index">Volver</a>
</div>

<form method="post" action="index.php?p=facturas-guardar" id="formFactura" class="row g-3">
  <div class="col-md-3">
    <label class="form-label">Fecha</label>
    <input type="date" name="fecha" id="f_fecha" class="form-control" value="<?= h($fecha) ?>">
  </div>

  <div class="col-md-3">
    <label class="form-label">Serie</label>
    <input type="text" class="form-control" id="f_serie_preview" value="<?= h($serieSugerida) ?>" readonly>
    <input type="hidden" name="serie" id="f_serie" value="<?= h($serieSugerida) ?>">
    <div class="form-text">Se asigna automáticamente según configuración/año.</div>
  </div>

  <div class="col-md-3">
    <label class="form-label">Número</label>
    <input type="text" class="form-control" id="f_numero_preview" value="<?= h($numeroSugerido) ?>" readonly>
    <input type="hidden" name="numero" id="f_numero" value="<?= h($numeroSugerido) ?>">
    <div class="form-text">Siguiente disponible en la serie.</div>
  </div>

  <div class="col-md-3">
    <label class="form-label">Cliente</label>
    <select name="cliente_id" id="f_cliente" class="form-select" required>
      <option value="">— Selecciona —</option>
      <?php foreach($clientes as $c): ?>
        <option value="<?= (int)$c['id'] ?>"><?= h($c['nombre']) ?><?= $c['cif'] ? ' · '.h($c['cif']) : '' ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="m-0">Líneas</h6>
          <button class="btn btn-sm btn-primary" type="button" id="btnAddLinea">Añadir línea</button>
        </div>
        <div class="table-responsive">
          <table class="table table-sm align-middle" id="tablaLineas">
            <thead>
            <tr>
              <th style="width:34%">Descripción</th>
              <th class="text-end" style="width:10%">Cant.</th>
              <th class="text-end" style="width:14%">Precio</th>
              <th class="text-end" style="width:10%">Dto %</th>
              <th class="text-end" style="width:10%">IVA %</th>
              <th class="text-end" style="width:14%">Subtotal</th>
              <th class="text-end" style="width:8%"></th>
            </tr>
            </thead>
            <tbody id="lineasBody">
              <!-- Fila inicial -->
              <tr data-idx="1">
                <td>
                  <div class="input-group input-group-sm">
                    <input type="hidden" name="producto_id[]" value="">
                    <input type="text" name="producto_nombre[]" class="form-control" placeholder="Descripción / producto">
                    <button class="btn btn-outline-secondary btnPick" type="button"
                            data-bs-toggle="modal" data-bs-target="#modalProductos" title="Elegir de productos">🔎</button>
                    <button class="btn btn-outline-secondary btnClearProd" type="button" title="Limpiar producto">✖</button>
                  </div>
                </td>
                <td><input type="number" name="cantidad[]" class="form-control form-control-sm text-end ln-cant" value="1" min="0" step="0.01"></td>
                <td><input type="number" name="precio[]" class="form-control form-control-sm text-end ln-precio" value="0" min="0" step="0.01"></td>
                <td><input type="number" name="descuento[]" class="form-control form-control-sm text-end ln-dto" value="0" min="0" step="0.01"></td>
                <td><input type="number" name="iva[]" class="form-control form-control-sm text-end ln-iva" value="21" min="0" step="0.01"></td>
                <td class="text-end"><span class="ln-subtotal">0,00 €</span></td>
                <td class="text-end cell-actions"><button class="btn btn-sm btn-outline-danger btnDel" type="button">✕</button></td>
              </tr>
            </tbody>
            <tfoot>
            <tr>
              <th colspan="5" class="text-end">Base imponible</th>
              <th class="text-end"><span id="t_base">0,00 €</span></th>
              <th></th>
            </tr>
            <tr>
              <th colspan="5" class="text-end">IVA total</th>
              <th class="text-end"><span id="t_iva">0,00 €</span></th>
              <th></th>
            </tr>
            <tr>
              <th colspan="3"></th>
              <th class="text-end">IRPF %</th>
              <th class="text-end" style="max-width:90px">
                <input type="number" name="irpf" id="f_irpf" class="form-control form-control-sm text-end" value="0" min="0" step="0.01">
              </th>
              <th class="text-end"><span id="t_irpf">0,00 €</span></th>
              <th></th>
            </tr>
            <tr>
              <th colspan="5" class="text-end">Total</th>
              <th class="text-end"><span id="t_total">0,00 €</span></th>
              <th></th>
            </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Totales para el POST -->
  <input type="hidden" name="suma_subtotales" id="h_base" value="0">
  <input type="hidden" name="suma_iva" id="h_iva" value="0">
  <input type="hidden" name="total" id="h_total" value="0">

  <div class="col-12 d-flex gap-2">
    <button class="btn btn-primary">Guardar factura</button>
    <a class="btn btn-outline-secondary" href="index.php?p=facturas-index">Cancelar</a>
  </div>
</form>

<!-- Modal selector de productos -->
<div class="modal fade" id="modalProductos" tabindex="-1" aria-labelledby="modalProductosLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title" id="modalProductosLabel">Seleccionar producto</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <input type="text" id="prodBuscar" class="form-control prod-search mb-2" placeholder="Buscar por nombre o referencia...">
        <div class="table-responsive">
          <table class="table table-sm align-middle" id="tablaProductos">
            <thead>
              <tr>
                <th style="width:25%">Referencia</th>
                <th>Nombre</th>
                <th class="text-end" style="width:15%">Precio</th>
                <th class="text-end" style="width:12%">IVA %</th>
                <th class="text-end" style="width:12%"></th>
              </tr>
            </thead>
            <tbody id="prodBody"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <small class="text-muted">Tip: haz clic en una fila para añadirla a la línea seleccionada.</small>
      </div>
    </div>
  </div>
</div>

<script>
/* ==== Datos de productos (pre-cargados) ==== */
const PRODUCTOS = <?= json_encode($productos, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;

/* ==== Numeración vista previa al cambiar fecha (serie: año corto) ==== */
const fFecha  = document.getElementById('f_fecha');
const fSerieV = document.getElementById('f_serie_preview');
const fSerie  = document.getElementById('f_serie');
const fNumV   = document.getElementById('f_numero_preview');
const fNum    = document.getElementById('f_numero');
fFecha?.addEventListener('change', () => {
  const d = new Date(fFecha.value || new Date());
  if (isNaN(d)) return;
  const yy = String(d.getFullYear()).slice(-2);
  fSerieV.value = yy;
  fSerie.value  = yy;
  fNumV.value   = '…';
  fNum.value    = '';
});

/* ==== Totales ==== */
const body  = document.getElementById('lineasBody');
const tBase = document.getElementById('t_base');
const tIVA  = document.getElementById('t_iva');
const tIRPF = document.getElementById('t_irpf');
const tTot  = document.getElementById('t_total');
const hBase = document.getElementById('h_base');
const hIva  = document.getElementById('h_iva');
const hTot  = document.getElementById('h_total');
const fIrpf = document.getElementById('f_irpf');

function nf(n){ return (n||0).toLocaleString('es-ES', {minimumFractionDigits:2, maximumFractionDigits:2}); }
function recalc(){
  let base = 0, iva = 0;
  body.querySelectorAll('tr').forEach(tr => {
    const q   = parseFloat(tr.querySelector('.ln-cant')?.value || '0');
    const p   = parseFloat(tr.querySelector('.ln-precio')?.value || '0');
    const dto = parseFloat(tr.querySelector('.ln-dto')?.value || '0');
    const iv  = parseFloat(tr.querySelector('.ln-iva')?.value || '0');
    const sub = q * p * (1 - (dto/100));
    const subIva = sub * (iv/100);
    tr.querySelector('.ln-subtotal').textContent = nf(sub) + ' €';
    base += sub; iva += subIva;
  });
  const irpfPct = parseFloat(fIrpf?.value || '0');
  const irpf    = base * (irpfPct/100);
  const total   = base + iva - irpf;
  tBase.textContent = nf(base) + ' €';
  tIVA.textContent  = nf(iva)  + ' €';
  tIRPF.textContent = nf(irpf) + ' €';
  tTot.textContent  = nf(total)+ ' €';
  hBase.value = base.toFixed(2);
  hIva.value  = iva.toFixed(2);
  hTot.value  = total.toFixed(2);
}
body.addEventListener('input', e => {
  if (e.target.matches('.ln-cant,.ln-precio,.ln-dto,.ln-iva')) recalc();
});
fIrpf?.addEventListener('input', recalc);

/* ==== Añadir/eliminar líneas ==== */
let lineIdx = 1;
document.getElementById('btnAddLinea')?.addEventListener('click', () => {
  lineIdx++;
  const tr = document.createElement('tr');
  tr.setAttribute('data-idx', String(lineIdx));
  tr.innerHTML = `
    <td>
      <div class="input-group input-group-sm">
        <input type="hidden" name="producto_id[]" value="">
        <input type="text" name="producto_nombre[]" class="form-control" placeholder="Descripción / producto">
        <button class="btn btn-outline-secondary btnPick" type="button" data-bs-toggle="modal" data-bs-target="#modalProductos" title="Elegir de productos">🔎</button>
        <button class="btn btn-outline-secondary btnClearProd" type="button" title="Limpiar producto">✖</button>
      </div>
    </td>
    <td><input type="number" name="cantidad[]" class="form-control form-control-sm text-end ln-cant" value="1" min="0" step="0.01"></td>
    <td><input type="number" name="precio[]" class="form-control form-control-sm text-end ln-precio" value="0" min="0" step="0.01"></td>
    <td><input type="number" name="descuento[]" class="form-control form-control-sm text-end ln-dto" value="0" min="0" step="0.01"></td>
    <td><input type="number" name="iva[]" class="form-control form-control-sm text-end ln-iva" value="21" min="0" step="0.01"></td>
    <td class="text-end"><span class="ln-subtotal">0,00 €</span></td>
    <td class="text-end cell-actions"><button class="btn btn-sm btn-outline-danger btnDel" type="button">✕</button></td>
  `;
  body.appendChild(tr);
  recalc();
});
body.addEventListener('click', e => {
  if (e.target.classList.contains('btnDel')) {
    e.target.closest('tr')?.remove();
    recalc();
  }
  if (e.target.classList.contains('btnClearProd')) {
    const tr = e.target.closest('tr');
    tr.querySelector("input[name='producto_id[]']").value = "";
    tr.querySelector("input[name='producto_nombre[]']").value = "";
    tr.querySelector(".ln-precio").value = "0";
    tr.querySelector(".ln-iva").value = "21";
    recalc();
  }
});

/* ==== Modal de productos ==== */
let targetLine = null; // <tr> objetivo
const prodBody  = document.getElementById('prodBody');
const prodSearch= document.getElementById('prodBuscar');

function renderProductos(filtro=""){
  const f = filtro.trim().toLowerCase();
  prodBody.innerHTML = "";
  PRODUCTOS.forEach(p => {
    const txt = ((p.referencia||"") + " " + (p.nombre||"")).toLowerCase();
    if (f && !txt.includes(f)) return;
    const tr = document.createElement('tr');
    tr.className = 'prod-row';
    tr.dataset.id = p.id;
    tr.dataset.nombre = p.nombre || '';
    tr.dataset.precio = p.precio_unitario ?? 0;
    tr.dataset.iva = p.iva_porcentaje ?? 21;
    tr.innerHTML = `
      <td>${p.referencia ? h(p.referencia) : ''}</td>
      <td>${h(p.nombre || '')}</td>
      <td class="text-end">${Number(p.precio_unitario||0).toLocaleString('es-ES',{minimumFractionDigits:2,maximumFractionDigits:2})} €</td>
      <td class="text-end">${Number(p.iva_porcentaje??21).toLocaleString('es-ES',{minimumFractionDigits:2,maximumFractionDigits:2})}</td>
      <td class="text-end"><button class="btn btn-sm btn-primary">Elegir</button></td>
    `;
    prodBody.appendChild(tr);
  });
}
function h(s){ return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])); }

renderProductos();

prodSearch?.addEventListener('input', () => renderProductos(prodSearch.value));

document.getElementById('modalProductos')?.addEventListener('show.bs.modal', ev => {
  const trigger = ev.relatedTarget;
  targetLine = trigger ? trigger.closest('tr') : null;
  prodSearch.value = "";
  renderProductos();
  setTimeout(()=>prodSearch.focus(), 100);
});

prodBody?.addEventListener('click', ev => {
  const tr = ev.target.closest('tr');
  if (!tr || !targetLine) return;
  // Colocar en la línea objetivo
  targetLine.querySelector("input[name='producto_id[]']").value = tr.dataset.id || '';
  targetLine.querySelector("input[name='producto_nombre[]']").value = tr.dataset.nombre || '';
  targetLine.querySelector(".ln-precio").value = tr.dataset.precio || '0';
  targetLine.querySelector(".ln-iva").value = tr.dataset.iva || '21';
  recalc();
  // Cerrar modal
  const modalEl = document.getElementById('modalProductos');
  if (modalEl && window.bootstrap) {
    const m = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    m.hide();
  }
});

// Recalcular al cargar
recalc();
</script>
