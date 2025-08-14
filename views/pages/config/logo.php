<?php
require_once __DIR__ . '/../../../includes/session.php';
secure_session_start();
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/conexion.php';

$uid = (int)$_SESSION['usuario_id'];

if (empty($_FILES['logo']['name'])) { exit('No se subió archivo'); }

$err = null;
$maxBytes = 1.5 * 1024 * 1024; // 1.5 MB
$allowed = ['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'];

if ($_FILES['logo']['error'] !== UPLOAD_ERR_OK) $err = 'Error de subida';
if ($_FILES['logo']['size'] > $maxBytes) $err = 'Archivo demasiado grande';
$info = @getimagesize($_FILES['logo']['tmp_name']);
if (!$info || !isset($allowed[$info['mime']])) $err = 'Formato no permitido (usa PNG, JPG o WebP)';

if ($err) { exit($err); }

$ext = $allowed[$info['mime']];
$baseDir = __DIR__ . '/../../../uploads';
if (!is_dir($baseDir)) { @mkdir($baseDir, 0775, true); }
$dir = $baseDir . '/logos';
if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
$filename = bin2hex(random_bytes(16)) . '.' . $ext;
$destAbs = $dir . '/' . $filename;
if (!move_uploaded_file($_FILES['logo']['tmp_name'], $destAbs)) {
  exit('No se pudo mover el archivo subido.');
}

// Ruta relativa para servir desde /uploads
$relative = 'uploads/logos/'.$filename;

// (Opcional) borra logo anterior si existe y está bajo /uploads/logos/
$old = $pdo->prepare("SELECT logo FROM usuarios WHERE id=?");
$old->execute([$uid]);
$prev = $old->fetchColumn();
if ($prev && strpos($prev, 'uploads/logos/') === 0) {
  @unlink(__DIR__ . '/../../../' . $prev);
}

$upd = $pdo->prepare("UPDATE usuarios SET logo=? WHERE id=?");
$upd->execute([$relative, $uid]);

header('Location: index.php?p=config-index');
exit;
