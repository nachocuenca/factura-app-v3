<?php
// public/logout.php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

// Vaciar sesión
$_SESSION = [];

// Borrar cookie de sesión
if (ini_get('session.use_cookies')) {
  $p = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

// Destruir
session_destroy();

// Regenerar por seguridad
session_start(); session_regenerate_id(true); session_write_close();

// al final de public/logout.php
$basePublic = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
header('Location: ' . $basePublic . '/login.php');
exit;
