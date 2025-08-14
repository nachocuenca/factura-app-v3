<?php
require_once __DIR__ . '/../../../includes/session.php';
secure_session_start();
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/conexion.php';

$uid = (int)$_SESSION['usuario_id'];
$seccion = $_POST['seccion'] ?? '';

if ($seccion === 'perfil') {
  $sql = "UPDATE usuarios SET 
            nombre=?, cif=?, telefono=?, email=?, direccion=?, cp=?, localidad=?, provincia=?
          WHERE id=?";
  $vals = [
    trim($_POST['nombre'] ?? ''),
    trim($_POST['cif'] ?? ''),
    trim($_POST['telefono'] ?? ''),
    trim($_POST['email'] ?? ''),
    trim($_POST['direccion'] ?? ''),
    trim($_POST['cp'] ?? ''),
    trim($_POST['localidad'] ?? ''),
    trim($_POST['provincia'] ?? ''),
    $uid
  ];
} elseif ($seccion === 'facturacion') {
  $sql = "UPDATE usuarios SET 
            serie_facturas=?, inicio_serie_facturas=?, serie_abonos=?, inicio_serie_abonos=?,
            pie_factura=?, notas_factura=?
          WHERE id=?";
  $vals = [
    trim($_POST['serie_facturas'] ?? ''),
    (int)($_POST['inicio_serie_facturas'] ?? 1),
    trim($_POST['serie_abonos'] ?? ''),
    (int)($_POST['inicio_serie_abonos'] ?? 1),
    trim($_POST['pie_factura'] ?? ''),
    trim($_POST['notas_factura'] ?? ''),
    $uid
  ];
} elseif ($seccion === 'apariencia') {
  // validar color hex #RRGGBB
  $color = trim($_POST['color_primario'] ?? '#000000');
  if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) $color = '#000000';
  $sql = "UPDATE usuarios SET color_primario=?, fuente_factura=? WHERE id=?";
  $vals = [
    $color,
    trim($_POST['fuente_factura'] ?? ''),
    $uid
  ];
} else {
  die('Sección no válida');
}

$stmt = $pdo->prepare($sql);
$stmt->execute($vals);

header('Location: index.php?p=config-index');
exit;
