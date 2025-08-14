<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/conexion.php';

$uid = (int)$_SESSION['usuario_id'];

$actual = (string)($_POST['actual'] ?? '');
$nueva  = (string)($_POST['nueva'] ?? '');
$repetir= (string)($_POST['repetir'] ?? '');

if ($nueva === '' || $nueva !== $repetir) {
  exit('La nueva contraseña no coincide.');
}

$stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
$stmt->execute([$uid]);
$stored = (string)($stmt->fetchColumn() ?? '');

$ok = false;
if ($stored === '' || $stored === null) {
  // si no hay contraseña guardada, no exigimos la actual
  $ok = true;
} else {
  if (preg_match('/^\$(2y|argon2id|argon2i)\$/', $stored)) {
    $ok = password_verify($actual, $stored);
  } else {
    $ok = hash_equals($stored, $actual);
  }
}

if (!$ok) {
  exit('La contraseña actual no es válida.');
}

$hash = password_hash($nueva, PASSWORD_DEFAULT);
$upd  = $pdo->prepare("UPDATE usuarios SET password=? WHERE id=?");
$upd->execute([$hash, $uid]);

header('Location: index.php?p=config-index');
exit;
