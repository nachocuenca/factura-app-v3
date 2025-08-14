<?php
function secure_session_start(): void {
  if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443);
    session_set_cookie_params([
      'secure'   => $isHttps,
      'httponly' => true,
      'samesite' => 'Lax',
      'path'     => '/',
    ]);
    session_start();
  }
}

