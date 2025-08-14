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
  if (!empty($_FILES['archivo']['name'])) {
    $file = $_FILES['archivo'];
    if ($file['error'] === UPLOAD_ERR_OK) {
      $allowedExt = ['pdf','jpg','jpeg','png','heic'];
      $maxBytes   = 10 * 1024 * 1024; // 10 MB
      $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
      if (!in_array($ext, $allowedExt, true)) { throw new RuntimeException('Extensión no permitida'); }
      if ($file['size'] > $maxBytes) { throw new RuntimeException('Archivo demasiado grande'); }
      $finfo = new finfo(FILEINFO_MIME_TYPE);
      $mime  = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';
      $okMime = (
        ($ext==='pdf'  && $mime==='application/pdf') ||
        (in_array($ext,['jpg','jpeg'],true) && in_array($mime,['image/jpeg','image/pjpeg'],true)) ||
        ($ext==='png'  && $mime==='image/png') ||
        ($ext==='heic' && strpos($mime,'image/')===0)
      );
      if (!$okMime) { throw new RuntimeException('Tipo de archivo inválido'); }
      $y    = date('Y');
      $m    = date('m');
      $base = realpath(dirname(__DIR__, 3)); // raíz del proyecto
      $dir  = $base . '/uploads/gastos/u' . $uid . '/' . $y . '/' . $m;
      if (!is_dir($dir)) { mkdir($dir, 0775, true); }
      $rand = bin2hex(random_bytes(4));
      $name = date('Ymd-His') . '-' . $rand . '.' . $ext;
      $dest = $dir . '/' . $name;
      if (!move_uploaded_file($file['tmp_name'], $dest)) { throw new RuntimeException('No se pudo mover el archivo'); }
      $rutaRelativa = 'uploads/gastos/u' . $uid . '/' . $y . '/' . $m . '/' . $name;
      // 3) Guardar ruta relativa
      $up = $pdo->prepare("UPDATE gastos SET archivo=? WHERE id=? AND usuario_id=?");
      $up->execute([$rutaRelativa, $gid, $uid]);
    }
  }

  $pdo->commit();
  header('Location: index.php?p=gastos-index');
  exit;

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo "Error al guardar el gasto: " . htmlspecialchars($e->getMessage());
}
