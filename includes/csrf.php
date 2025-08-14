<?php
function csrf_token(): string {
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf'];
}
function csrf_field(): void {
  echo '<input type="hidden" name="csrf" value="'.htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8').'">';
}
function csrf_check(): void {
  if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit('Método no permitido');
  }
  $t = $_POST['csrf'] ?? '';
  if (!hash_equals($_SESSION['csrf'] ?? '', $t)) {
    http_response_code(403);
    exit('CSRF inválido');
  }
}
