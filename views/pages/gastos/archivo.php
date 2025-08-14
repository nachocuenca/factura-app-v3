<?php
// views/pages/gastos/archivo.php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
  http_response_code(401);
  exit('No autorizado');
}

$uid = (int)$_SESSION['usuario_id'];
$id  = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

$stmt = $pdo->prepare("SELECT archivo FROM gastos WHERE id=? AND usuario_id=?");
$stmt->execute([$id, $uid]);
$rel = $stmt->fetchColumn();

if (!$rel || strpos($rel, 'uploads/gastos/u' . $uid . '/') !== 0) {
  http_response_code(404);
  exit('No encontrado');
}

$base = realpath(dirname(__DIR__, 3) . '/uploads');
$path = $base . DIRECTORY_SEPARATOR . str_replace(['uploads/', '\\', '..'], ['', DIRECTORY_SEPARATOR, ''], $rel);
$real = realpath($path);

if (!$real || strpos($real, $base) !== 0 || !is_file($real)) {
  http_response_code(404);
  exit('No encontrado');
}

$mime = function_exists('mime_content_type')
  ? (mime_content_type($real) ?: 'application/octet-stream')
  : 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($real));
header('Content-Disposition: inline; filename="' . basename($real) . '"');
readfile($real);
exit;

