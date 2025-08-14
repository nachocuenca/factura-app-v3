<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/conexion.php';

$uid   = (int)$_SESSION['usuario_id'];
$fecha = $_GET['fecha'] ?? date('Y-m-d');
$ts    = strtotime($fecha);
$yy    = date('y', $ts);
$yyyy  = date('Y', $ts);

$serieCfg = $_SESSION['serie_facturas'] ?? null;
$serie = $serieCfg ? str_replace(['{yy}','{yyyy}'], [$yy, $yyyy], $serieCfg) : $yy;
$inicio = (int)($_SESSION['inicio_serie_facturas'] ?? 1);

$st = $pdo->prepare("SELECT MAX(CAST(numero AS UNSIGNED)) FROM facturas WHERE usuario_id=? AND serie=?");
$st->execute([$uid, $serie]);
$max = (int)$st->fetchColumn();

header('Content-Type: application/json');
echo json_encode([
  'serie'  => $serie,
  'numero' => $max > 0 ? $max + 1 : max(1, $inicio),
]);
exit;

