<?php
// public/logout.php
require_once __DIR__ . '/../includes/session.php';
secure_session_start();

// Vaciar sesión
$_SESSION = [];

// Borrar cookie de sesión
$p = session_get_cookie_params();
setcookie(session_name(), '', [
  'expires' => time() - 42000,
  'path' => $p['path'],
  'domain' => $p['domain'],
  'secure' => $p['secure'],
  'httponly' => $p['httponly'],
  'samesite' => $p['samesite'] ?? 'Lax',
]);

// Destruir
session_destroy();

// Cabeceras para evitar caché
header('Cache-Control: no-store');
header('Pragma: no-cache');

// al final de public/logout.php
$basePublic = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
header('Location: ' . $basePublic . '/login.php');
exit;
