<?php
require_once __DIR__ . '/../../../includes/session.php';
secure_session_start();
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/csrf.php';

csrf_check();

$sessionId = (int)$_SESSION['usuario_id'];
$isAdmin   = is_admin();

$norm = function($v){ $v = str_replace(' ', '', str_replace(',', '.', (string)$v)); return (float)$v; };

$id            = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$ownerId       = $isAdmin ? (int)($_POST['owner_id'] ?? 0) : 0;
$referencia    = trim($_POST['referencia'] ?? '');
$nombre        = trim($_POST['nombre'] ?? '');
$descripcion   = trim($_POST['descripcion'] ?? '');
$precio        = $norm($_POST['precio_unitario'] ?? 0);
$iva           = ($_POST['iva_porcentaje'] === '' || $_POST['iva_porcentaje'] === null) ? 21.00 : $norm($_POST['iva_porcentaje']);
$activo        = isset($_POST['activo']) ? 1 : 0;

if ($nombre === '' || $precio <= 0) { exit('Nombre y precio unitario > 0 son obligatorios'); }

// Cargar para verificar permisos
if ($isAdmin) {
  $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
  $stmt->execute([$id]);
} else {
  $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ? AND usuario_id = ?");
  $stmt->execute([$id, $sessionId]);
}
$orig = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$orig) { die('Producto no encontrado o sin permisos'); }

try {
  $pdo->beginTransaction();

  if ($isAdmin && $ownerId > 0 && $ownerId !== (int)$orig['usuario_id']) {
    $upd = $pdo->prepare("UPDATE productos
                          SET usuario_id=?, referencia=?, nombre=?, descripcion=?, precio_unitario=?, iva_porcentaje=?, activo=?
                          WHERE id=?");
    $upd->execute([$ownerId, $referencia ?: null, $nombre, $descripcion ?: null, $precio, $iva, $activo, $id]);
  } else {
    $upd = $pdo->prepare("UPDATE productos
                          SET referencia=?, nombre=?, descripcion=?, precio_unitario=?, iva_porcentaje=?, activo=?
                          WHERE id=?");
    $upd->execute([$referencia ?: null, $nombre, $descripcion ?: null, $precio, $iva, $activo, $id]);
  }

  $pdo->commit();
  header('Location: index.php?p=productos-index');
  exit;
} catch (Throwable $e) {
  $pdo->rollBack();
  echo 'Error al actualizar: ' . $e->getMessage();
}
