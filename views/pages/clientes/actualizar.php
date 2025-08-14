<?php
require_once __DIR__ . '/../../../includes/session.php';
secure_session_start();
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/csrf.php';
require_once __DIR__ . '/../../../includes/helpers.php';

csrf_check();

$sessionId = (int)$_SESSION['usuario_id'];
$isAdmin   = is_admin();

$id        = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$ownerId   = $isAdmin ? (int)($_POST['owner_id'] ?? 0) : 0;

$nombre    = trim($_POST['nombre'] ?? '');
$cif       = trim($_POST['cif'] ?? '');
$email     = trim($_POST['email'] ?? '');
$telefono  = trim($_POST['telefono'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$cp        = trim($_POST['cp'] ?? '');
$localidad = trim($_POST['localidad'] ?? '');
$provincia = trim($_POST['provincia'] ?? '');
$activo    = isset($_POST['activo']) ? 1 : 0;

if ($nombre === '' || $cif === '') { exit('Nombre y CIF son obligatorios'); }

// Cargar para verificar permisos
if ($isAdmin) {
  $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
  $stmt->execute([$id]);
} else {
  $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ? AND usuario_id = ?");
  $stmt->execute([$id, $sessionId]);
}
$orig = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$orig) { die('Cliente no encontrado o sin permisos'); }

try {
  $pdo->beginTransaction();

  if ($isAdmin && $ownerId > 0 && $ownerId !== (int)$orig['usuario_id']) {
    $upd = $pdo->prepare("UPDATE clientes 
                          SET usuario_id=?, nombre=?, cif=?, direccion=?, cp=?, localidad=?, provincia=?, email=?, telefono=?, activo=?, fecha_actualizacion=NOW()
                          WHERE id=?");
    $upd->execute([$ownerId, $nombre, $cif, $direccion, $cp, $localidad, $provincia, $email, $telefono, $activo, $id]);
  } else {
    $upd = $pdo->prepare("UPDATE clientes 
                          SET nombre=?, cif=?, direccion=?, cp=?, localidad=?, provincia=?, email=?, telefono=?, activo=?, fecha_actualizacion=NOW()
                          WHERE id=?");
    $upd->execute([$nombre, $cif, $direccion, $cp, $localidad, $provincia, $email, $telefono, $activo, $id]);
  }

  $pdo->commit();
  header('Location: index.php?p=clientes-index');
  exit;
} catch (Throwable $e) {
  $pdo->rollBack();
  echo 'Error al actualizar: ' . h($e->getMessage());
}
