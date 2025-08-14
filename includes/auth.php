<?php
function is_admin(): bool {
  $rol = strtolower((string)($_SESSION['usuario_rol'] ?? $_SESSION['rol'] ?? $_SESSION['role'] ?? ''));
  if (in_array($rol, ['admin','administrador','super','superadmin','superusuario'])) {
    return true;
  }
  $rid = (int)($_SESSION['role_id'] ?? 0);
  if ($rid > 0) {
    global $pdo;
    try {
      $st = $pdo->prepare("SELECT nombre FROM roles WHERE id=?");
      $st->execute([$rid]);
      $rname = strtolower((string)$st->fetchColumn());
      if (in_array($rname, ['admin','administrador','super','superadmin','superusuario'])) {
        return true;
      }
    } catch (Throwable $e) {
      // ignore
    }
  }
  return !empty($_SESSION['modo_compacto']) || !empty($_SESSION['is_admin']);
}

