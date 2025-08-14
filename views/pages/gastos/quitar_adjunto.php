<?php
require_once __DIR__ . '/../../../includes/session.php';
secure_session_start();
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/../../../includes/helpers.php';

$uid = (int)$_SESSION['usuario_id'];
$id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
  // Obtener ruta actual y validar propiedad
  $st = $pdo->prepare("SELECT archivo FROM gastos WHERE id=? AND usuario_id=?");
  $st->execute([$id, $uid]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  if (!$row) {
    throw new RuntimeException('Gasto no encontrado o sin permisos.');
  }

  // Si hay archivo, borrarlo físicamente
  if (!empty($row['archivo'])) {
    $absPublic = realpath(__DIR__ . '/../../../public');
    if ($absPublic !== false) {
      $oldAbs = $absPublic . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $row['archivo']);
      if (is_file($oldAbs)) { @unlink($oldAbs); }
    }
  }

  // Dejar archivo en NULL
  $up = $pdo->prepare("UPDATE gastos SET archivo=NULL WHERE id=? AND usuario_id=?");
  $up->execute([$id, $uid]);

  header('Location: index.php?p=gastos-adjuntar&id=' . $id);
  exit;

} catch (Throwable $e) {
  http_response_code(400);
  echo "Error al quitar adjunto: " . h($e->getMessage());
}
