<?php
require_once __DIR__ . '/../../../includes/session.php';
secure_session_start();
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/conexion.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/csrf.php';

csrf_check();

$uid = (int)$_SESSION['usuario_id'];
$id     = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$accion = ($_POST['accion'] ?? '') === 'activar' ? 1 : 0;
$stmt = $pdo->prepare("UPDATE productos SET activo = ? WHERE id = ? AND usuario_id = ?");
$stmt->execute([$accion, $id, $uid]);

header('Location: index.php?p=productos-index');
exit;
