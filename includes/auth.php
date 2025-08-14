<?php
function is_admin(): bool {
  $rol = strtolower((string)($_SESSION['rol'] ?? $_SESSION['role'] ?? ''));
  return in_array($rol, ['admin','administrador','super','superadmin','superusuario'])
         || !empty($_SESSION['modo_compacto']) || !empty($_SESSION['is_admin']);
}
?>