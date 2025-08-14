<?php
require_once __DIR__ . '/../../../includes/session.php';
secure_session_start();
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/../../../includes/csrf.php';

csrf_check();

$uid = (int)$_SESSION['usuario_id'];

// Helper parseo números
function toFloat($s){
  if ($s===null) return 0.0;
  $s = trim((string)$s);
  if ($s==='') return 0.0;
  $s = str_replace(' ', '', $s);
  if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
    if (strrpos($s, ',') > strrpos($s, '.')) $s = str_replace('.', '', $s);
    $s = str_replace(',', '.', $s);
  } elseif (strpos($s, ',') !== false) {
    $s = str_replace(',', '.', $s);
  }
  return (float)$s;
}

$fecha       = $_POST['fecha'] ?? date('Y-m-d');
$fecha_valor = $_POST['fecha_valor'] ?? null;
$numero      = trim($_POST['numero'] ?? '');
$categoria   = trim($_POST['categoria'] ?? '');
$base        = toFloat($_POST['base_imponible'] ?? '0');
$tipo_iva    = toFloat($_POST['tipo_iva'] ?? '21');
$irpfP       = toFloat($_POST['irpf'] ?? '0');
$deducible   = isset($_POST['soportado_deducible']) ? (int)$_POST['soportado_deducible'] : 1;
$estado      = $_POST['estado'] ?? 'finalizado';
if (!in_array($estado, ['borrador','finalizado','pagado'], true)) $estado = 'finalizado';

// Cálculos
$iva_cuota  = round($base * $tipo_iva / 100, 2);
$irpf_cuota = round($base * $irpfP / 100, 2);
$total      = round($base + $iva_cuota - $irpf_cuota, 2);

try {
  $pdo->beginTransaction();

  // 1) Insertar gasto (archivo NULL de momento)
  $sql = "INSERT INTO gastos (fecha, fecha_valor, numero, base_imponible, iva, tipo_iva, soportado_deducible, irpf, total, estado, categoria, usuario_id, archivo)
          VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NULL)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    $fecha ?: null,
    $fecha_valor ?: null,
    $numero !== '' ? $numero : null,
    $base,
    $iva_cuota,
    $tipo_iva,
    $deducible,
    $irpfP,        // porcentaje
    $total,
    $estado,
    $categoria !== '' ? $categoria : null,
    $uid
  ]);

  $gid = (int)$pdo->lastInsertId();

  // 2) Subida de archivo (opcional)
  if (!empty($_FILES['archivo']['name']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
    $maxBytes = 15 * 1024 * 1024; // 15 MB
    if ($_FILES['archivo']['size'] > $maxBytes) {
      throw new RuntimeException('El archivo supera el tamaño máximo (15 MB).');
    }

    // MIME real
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($_FILES['archivo']['tmp_name']) ?: 'application/octet-stream';

    // Permitidos
    $allow = [
      'application/pdf' => 'pdf',
      'image/jpeg'      => 'jpg',
      'image/png'       => 'png',
      'image/webp'      => 'webp',
      // iPhone podría subir HEIC/HEIF; los aceptamos, pero el navegador no los previsualiza siempre.
      'image/heic'      => 'heic',
      'image/heif'      => 'heif',
    ];
    if (!isset($allow[$mime])) {
      throw new RuntimeException('Formato no permitido. Sube JPG, PNG, WEBP o PDF.');
    }
    $ext = $allow[$mime];

    // Carpeta: /public/uploads/gastos/u{uid}/YYYY/MM
    $subdir = 'uploads/gastos/u' . $uid . '/' . date('Y') . '/' . date('m');
    $absDir = realpath(__DIR__ . '/../../../public');
    if ($absDir === false) { throw new RuntimeException('No existe carpeta /public.'); }
    $destDir = $absDir . DIRECTORY_SEPARATOR . $subdir;
    if (!is_dir($destDir)) { @mkdir($destDir, 0775, true); }

    // Nombre único
    $filename = 'g'.$gid.'_'.date('Ymd_His').'.'.$ext;
    $destAbs  = $destDir . DIRECTORY_SEPARATOR . $filename;
    $destRel  = $subdir . '/' . $filename; // lo que guardamos en BD

    if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $destAbs)) {
      throw new RuntimeException('No se pudo mover el archivo subido.');
    }

    // Seguridad (opcional): impedir ejecución en uploads con .htaccess (ver nota al final)

    // 3) Guardar ruta relativa
    $up = $pdo->prepare("UPDATE gastos SET archivo=? WHERE id=? AND usuario_id=?");
    $up->execute([$destRel, $gid, $uid]);
  }

  $pdo->commit();
  header('Location: index.php?p=gastos-index');
  exit;

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo "Error al guardar el gasto: " . htmlspecialchars($e->getMessage());
}
