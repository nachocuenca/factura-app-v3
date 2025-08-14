<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/csrf.php';

csrf_check();

$sessionId = (int)$_SESSION['usuario_id'];
$isAdmin   = is_admin();

$nombre    = trim($_POST['nombre'] ?? '');
$cif       = trim($_POST['cif'] ?? '');
$email     = trim($_POST['email'] ?? '');
$telefono  = trim($_POST['telefono'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$cp        = trim($_POST['cp'] ?? '');
$localidad = trim($_POST['localidad'] ?? '');
$provincia = trim($_POST['provincia'] ?? '');

$ownerId   = $isAdmin ? (int)($_POST['owner_id'] ?? $sessionId) : $sessionId;

if ($nombre === '' || $cif === '') {
  exit('Nombre y CIF son obligatorios');
}

$sql = "INSERT INTO clientes
        (usuario_id, nombre, cif, direccion, cp, localidad, provincia, email, telefono, activo, fecha_creacion, fecha_actualizacion)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())";
$stmt = $pdo->prepare($sql);
$stmt->execute([$ownerId, $nombre, $cif, $direccion, $cp, $localidad, $provincia, $email, $telefono]);

header('Location: index.php?p=clientes-index');
exit;
