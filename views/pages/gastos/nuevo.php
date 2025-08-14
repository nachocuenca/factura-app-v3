<?php
require_once __DIR__ . '/../../../includes/auth.php';
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Sugerir categorías previas (texto libre)
$uid = (int)$_SESSION['usuario_id'];
$st = $pdo->prepare("SELECT DISTINCT categoria FROM gastos WHERE usuario_id=? AND categoria IS NOT NULL AND categoria<>'' ORDER BY categoria");
$st->execute([$uid]);
$cats = $st->fetchAll(PDO::FETCH_COLUMN);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="m-0">Nuevo gasto</h5>
  <a class="btn btn-outline-secondary" href="index.php?p=gastos-index">Volver</a>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <form method="post" action="index.php?p=gastos-guardar" class="row g-3" enctype="multipart/form-data">
      <div class="col-md-3">
        <label class="form-label">Fecha</label>
        <input type="date" name="fecha" class="form-control" value="<?= h(date('Y-m-d')) ?>" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Fecha valor</label>
        <input type="date" name="fecha_valor" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label">Número documento</label>
        <input type="text" name="numero" class="form-control" placeholder="Nº factura proveedor">
      </div>
      <div class="col-md-3">
        <label class="form-label">Categoría</label>
        <input list="cats" name="categoria" class="form-control" placeholder="p.ej. Suministros">
        <datalist id="cats">
          <?php foreach($cats as $c): ?><option value="<?= h($c) ?>"><?php endforeach; ?>
        </datalist>
      </div>

      <div class="col-md-3">
        <label class="form-label">Base imponible</label>
        <input type="text" name="base_imponible" id="base" class="form-control" inputmode="decimal" placeholder="0,00" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">IVA %</label>
        <input type="text" name="tipo_iva" id="tipo_iva" class="form-control" inputmode="decimal" value="21">
      </div>
      <div class="col-md-3">
        <label class="form-label">IRPF %</label>
        <input type="text" name="irpf" id="irpf" class="form-control" inputmode="decimal" value="0">
      </div>
      <div class="col-md-3">
        <label class="form-label">Deducible (IVA soportado)</label>
        <select name="soportado_deducible" class="form-select">
          <option value="1" selected>Sí</option>
          <option value="0">No</option>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label">Adjunto (foto o PDF)</label>
        <!-- En móviles abre cámara si se puede -->
        <input type="file"
               name="archivo"
               class="form-control"
               accept="image/*,application/pdf"
               capture="environment">
        <div class="form-text">Puedes hacer una foto del ticket/factura o subir un PDF. Máx. 15&nbsp;MB.</div>
      </div>

      <div class="col-md-3">
        <label class="form-label">Estado</label>
        <select name="estado" class="form-select">
          <option value="finalizado" selected>Finalizado</option>
          <option value="pagado">Pagado</option>
          <option value="borrador">Borrador</option>
        </select>
      </div>

      <div class="col-md-9">
        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label">IVA (cuota)</label>
            <input type="text" id="iva_cuota" class="form-control" readonly>
          </div>
          <div class="col-md-4">
            <label class="form-label">Total</label>
            <input type="text" id="total_v" class="form-control" readonly>
          </div>
        </div>
      </div>

      <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary">Guardar</button>
        <a class="btn btn-outline-secondary" href="index.php?p=gastos-index">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<script>
function parseNum(v){
  if (v===null || v===undefined) return 0;
  let s = String(v).trim();
  if (s==='') return 0;
  s = s.replace(/\s/g,'');
  const hasC = s.includes(','), hasD = s.includes('.');
  if (hasC && hasD){
    if (s.lastIndexOf(',') > s.lastIndexOf('.')) s = s.replace(/\./g,'').replace(',', '.');
    else s = s.replace(/,/g,'');
  } else if (hasC && !hasD){
    s = s.replace(',', '.');
  }
  const n = parseFloat(s);
  return Number.isFinite(n) ? n : 0;
}
function nf(x){ return (Number(x)||0).toLocaleString('es-ES',{minimumFractionDigits:2, maximumFractionDigits:2}); }

function recalc(){
  const base = parseNum(document.getElementById('base').value);
  const ivaP = parseNum(document.getElementById('tipo_iva').value);
  const irpfP= parseNum(document.getElementById('irpf').value);

  const ivaC = +(base * ivaP / 100).toFixed(2);
  const irpfC= +(base * irpfP / 100).toFixed(2);
  const total = +(base + ivaC - irpfC).toFixed(2);

  document.getElementById('iva_cuota').value = nf(ivaC) + ' €';
  document.getElementById('total_v').value   = nf(total) + ' €';
}
['base','tipo_iva','irpf'].forEach(id=>{
  const el = document.getElementById(id);
  el.addEventListener('input', recalc);
  el.addEventListener('blur', recalc);
});
recalc();
</script>
