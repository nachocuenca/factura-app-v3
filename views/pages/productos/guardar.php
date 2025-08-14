<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/csrf.php';

csrf_check();

$sessionId = (int)$_SESSION['usuario_id'];
$isAdmin   = is_admin();

$norm = function($v){ $v = str_replace(' ', '', str_replace(',', '.', (string)$v)); return (float)$v; };

$ownerId        = $isAdmin ? (int)($_POST['owner_id'] ?? $sessionId) : $sessionId;
$referencia     = trim($_POST['referencia'] ?? '');
$nombre         = trim($_POST['nombre'] ?? '');
$descripcion    = trim($_POST['descripcion'] ?? '');
$precio_unitario= $norm($_POST['precio_unitario'] ?? 0);
$iva_porcentaje = ($_POST['iva_porcentaje'] === '' || $_POST['iva_porcentaje'] === null)
                  ? 21.00 : $norm($_POST['iva_porcentaje']);

if ($nombre === '' || $precio_unitario <= 0) {
  exit('Nombre y precio unitario > 0 son obligatorios');
}

$sql = "INSERT INTO productos (usuario_id, referencia, nombre, descripcion, precio_unitario, iva_porcentaje, activo)
        VALUES (?, ?, ?, ?, ?, ?, 1)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$ownerId, $referencia ?: null, $nombre, $descripcion ?: null, $precio_unitario, $iva_porcentaje]);

header('Location: index.php?p=productos-index');
exit;
