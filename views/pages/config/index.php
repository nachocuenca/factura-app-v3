<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/helpers.php';
$uid = (int)$_SESSION['usuario_id'];

// Carga datos del usuario logueado
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$uid]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$u) { die('Usuario no encontrado'); }

$fuentes = ['system-ui','Arial','Helvetica','Georgia','Times New Roman','Verdana','Tahoma','Courier New','Inter','Roboto'];
?>
<div class="card shadow-sm">
  <div class="card-body">
    <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
      <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-perfil" type="button">Perfil/Empresa</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-facturacion" type="button">Facturación</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-apariencia" type="button">Apariencia</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-seguridad" type="button">Seguridad</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-logo" type="button">Logo</button></li>
    </ul>

    <div class="tab-content">
      <!-- PERFIL -->
      <div class="tab-pane fade show active" id="tab-perfil">
        <form method="post" action="index.php?p=config-guardar" class="row g-3">
          <input type="hidden" name="seccion" value="perfil">
          <div class="col-md-6">
            <label class="form-label">Nombre / Razón social</label>
            <input name="nombre" class="form-control" value="<?= h($u['nombre']) ?>" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">CIF/NIF</label>
            <input name="cif" class="form-control" value="<?= h($u['cif']) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Teléfono</label>
            <input name="telefono" class="form-control" value="<?= h($u['telefono']) ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= h($u['email']) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Dirección</label>
            <input name="direccion" class="form-control" value="<?= h($u['direccion']) ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label">CP</label>
            <input name="cp" class="form-control" value="<?= h($u['cp']) ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label">Localidad</label>
            <input name="localidad" class="form-control" value="<?= h($u['localidad']) ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label">Provincia</label>
            <input name="provincia" class="form-control" value="<?= h($u['provincia']) ?>">
          </div>
          <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary">Guardar</button>
          </div>
        </form>
      </div>

      <!-- FACTURACIÓN -->
      <div class="tab-pane fade" id="tab-facturacion">
        <form method="post" action="index.php?p=config-guardar" class="row g-3">
          <input type="hidden" name="seccion" value="facturacion">
          <div class="col-md-3">
            <label class="form-label">Serie facturas</label>
            <input name="serie_facturas" class="form-control" value="<?= h($u['serie_facturas']) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Inicio numeración</label>
            <input type="number" name="inicio_serie_facturas" class="form-control" value="<?= h($u['inicio_serie_facturas'] ?? 1) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Serie abonos</label>
            <input name="serie_abonos" class="form-control" value="<?= h($u['serie_abonos']) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Inicio abonos</label>
            <input type="number" name="inicio_serie_abonos" class="form-control" value="<?= h($u['inicio_serie_abonos'] ?? 1) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Pie de factura</label>
            <textarea name="pie_factura" class="form-control" rows="3"><?= h($u['pie_factura']) ?></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label">Notas en factura</label>
            <textarea name="notas_factura" class="form-control" rows="3"><?= h($u['notas_factura']) ?></textarea>
          </div>
          <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary">Guardar</button>
          </div>
        </form>
      </div>

      <!-- APARIENCIA -->
      <div class="tab-pane fade" id="tab-apariencia">
        <form method="post" action="index.php?p=config-guardar" class="row g-3">
          <input type="hidden" name="seccion" value="apariencia">
          <div class="col-md-3">
            <label class="form-label">Color primario</label>
            <input type="color" name="color_primario" class="form-control form-control-color" value="<?= h($u['color_primario'] ?: '#000000') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Fuente de factura</label>
            <select name="fuente_factura" class="form-select">
              <option value="">(por defecto)</option>
              <?php foreach($fuentes as $fnt): ?>
                <option value="<?= h($fnt) ?>" <?= ($u['fuente_factura']===$fnt?'selected':'') ?>><?= h($fnt) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary">Guardar</button>
          </div>
        </form>
      </div>

      <!-- SEGURIDAD -->
      <div class="tab-pane fade" id="tab-seguridad">
        <form method="post" action="index.php?p=config-password" class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Contraseña actual</label>
            <input type="password" name="actual" class="form-control">
          </div>
          <div class="col-md-4">
            <label class="form-label">Nueva contraseña</label>
            <input type="password" name="nueva" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Repetir nueva</label>
            <input type="password" name="repetir" class="form-control" required>
          </div>
          <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary">Cambiar contraseña</button>
          </div>
        </form>
      </div>

      <!-- LOGO -->
      <div class="tab-pane fade" id="tab-logo">
        <form method="post" action="index.php?p=config-logo" enctype="multipart/form-data" class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Logo (PNG/JPG/WebP, máx. 1.5 MB)</label>
            <input type="file" name="logo" class="form-control" accept=".png,.jpg,.jpeg,.webp" required>
            <?php if (!empty($u['logo'])): ?>
              <div class="form-text">Logo actual: <?= h($u['logo']) ?></div>
            <?php endif; ?>
          </div>
          <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary">Subir logo</button>
          </div>
        </form>
        <?php if (!empty($u['logo'])): ?>
          <div class="mt-3">
            <img src="../<?= h($u['logo']) ?>" alt="Logo" style="max-height:80px">
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>
