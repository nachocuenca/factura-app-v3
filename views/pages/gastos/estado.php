<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/../../../includes/csrf.php';

csrf_check();

$uid = (int)$_SESSION['usuario_id'];
$id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$to  = $_GET['to'] ?? 'finalizado';
if (!in_array($to, ['borrador','finalizado','pagado'], true)) $to = 'finalizado';

// Solo del propietario
$st = $pdo->prepare("UPDATE gastos SET estado=? WHERE id=? AND usuario_id=?");
$st->execute([$to, $id, $uid]);

header('Location: index.php?p=gastos-index');
exit;
