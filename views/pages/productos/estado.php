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

$id     = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$accion = ($_POST['a'] ?? '') === 'activar' ? 'activar' : 'desactivar';
$nuevo  = $accion === 'activar' ? 1 : 0;

if ($isAdmin) {
  $stmt = $pdo->prepare("UPDATE productos SET activo = ? WHERE id = ?");
  $stmt->execute([$nuevo, $id]);
} else {
  $stmt = $pdo->prepare("UPDATE productos SET activo = ? WHERE id = ? AND usuario_id = ?");
  $stmt->execute([$nuevo, $id, $sessionId]);
}

header('Location: index.php?p=productos-index');
exit;
