<?php
function csrf_token(): string {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}

function csrf_check(): void {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
  if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    exit('CSRF token inválido');
  }
}
?>
