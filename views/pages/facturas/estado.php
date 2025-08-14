<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/../../../includes/auth.php';

$sessionId = (int)$_SESSION['usuario_id'];
$isAdmin   = is_admin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$e  = $_GET['e'] ?? 'borrador';
if (!in_array($e, ['borrador','emitida','pagada'], true)) $e = 'borrador';

if ($isAdmin) {
  $stmt = $pdo->prepare("UPDATE facturas SET estado = ?, fecha_actualizacion = NOW() WHERE id = ?");
  $stmt->execute([$e, $id]);
} else {
  $stmt = $pdo->prepare("UPDATE facturas SET estado = ?, fecha_actualizacion = NOW() WHERE id = ? AND usuario_id = ?");
  $stmt->execute([$e, $id, $sessionId]);
}

header('Location: index.php?p=facturas-index');
exit;
