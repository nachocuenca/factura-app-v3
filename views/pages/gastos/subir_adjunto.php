<?php
require_once __DIR__ . '/../../../includes/session.php';
secure_session_start();
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/conexion.php';

$uid = (int)$_SESSION['usuario_id'];
$id  = isset($_POST['id']) ? (int)$_POST['id'] : 0;

try {
  // Verificar propiedad y obtener adjunto anterior
  $st = $pdo->prepare("SELECT archivo FROM gastos WHERE id=? AND usuario_id=?");
  $st->execute([$id, $uid]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  if (!$row) {
    throw new RuntimeException('Gasto no encontrado o sin permisos.');
  }

  if (empty($_FILES['archivo']['name']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    throw new RuntimeException('No se recibió archivo válido.');
  }

  // Validaciones
  $maxBytes = 15 * 1024 * 1024; // 15MB
  if ($_FILES['archivo']['size'] > $maxBytes) {
    throw new RuntimeException('El archivo supera el tamaño máximo (15 MB).');
  }

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime  = $finfo->file($_FILES['archivo']['tmp_name']) ?: 'application/octet-stream';
  $allow = [
    'application/pdf' => 'pdf',
    'image/jpeg'      => 'jpg',
    'image/png'       => 'png',
    'image/webp'      => 'webp',
    'image/heic'      => 'heic',
    'image/heif'      => 'heif',
  ];
  if (!isset($allow[$mime])) {
    throw new RuntimeException('Formato no permitido. Sube JPG, PNG, WEBP, HEIC/HEIF o PDF.');
  }
  $ext = $allow[$mime];

  // Directorio destino
  $subdir = 'uploads/gastos/u' . $uid . '/' . date('Y') . '/' . date('m');
  $absPublic = realpath(__DIR__ . '/../../../public');
  if ($absPublic === false) {
    throw new RuntimeException('No existe carpeta /public.');
  }
  $destDir = $absPublic . DIRECTORY_SEPARATOR . $subdir;
  if (!is_dir($destDir)) { @mkdir($destDir, 0775, true); }

  // Nombre único
  $filename = 'g'.$id.'_'.date('Ymd_His').'.'.$ext;
  $destAbs  = $destDir . DIRECTORY_SEPARATOR . $filename;
  $destRel  = $subdir . '/' . $filename;

  if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $destAbs)) {
    throw new RuntimeException('No se pudo mover el archivo subido.');
  }

  // Actualizar BD
  $up = $pdo->prepare("UPDATE gastos SET archivo=? WHERE id=? AND usuario_id=?");
  $up->execute([$destRel, $id, $uid]);

  // Borrar adjunto anterior (si existía)
  if (!empty($row['archivo'])) {
    $oldAbs = $absPublic . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $row['archivo']);
    if (is_file($oldAbs)) { @unlink($oldAbs); }
  }

  header('Location: index.php?p=gastos-adjuntar&id=' . $id);
  exit;

} catch (Throwable $e) {
  http_response_code(400);
  echo "Error al subir adjunto: " . htmlspecialchars($e->getMessage());
}
