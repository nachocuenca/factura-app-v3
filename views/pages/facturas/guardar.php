<?php
// views/pages/facturas/guardar.php
require_once __DIR__ . '/../../../includes/auth.php';

$uid            = (int)$_SESSION['usuario_id'];
$fecha          = $_POST['fecha'] ?? date('Y-m-d');
$cliente_id     = (int)($_POST['cliente_id'] ?? 0);
$irpf_percent   = isset($_POST['irpf']) ? (float)$_POST['irpf'] : 0;
$total          = isset($_POST['total']) ? (float)$_POST['total'] : 0;
$base_imponible = isset($_POST['suma_subtotales']) ? (float)$_POST['suma_subtotales'] : 0;
$iva_total      = isset($_POST['suma_iva']) ? (float)$_POST['suma_iva'] : 0;
$irpf_total     = $irpf_percent > 0 ? round($base_imponible * ($irpf_percent / 100), 2) : 0;

// Arrays de líneas
$producto_ids     = $_POST['producto_id'] ?? [];
$producto_nombres = $_POST['producto_nombre'] ?? [];
$cantidades       = $_POST['cantidad'] ?? [];
$precios          = $_POST['precio'] ?? [];
$descuentos       = $_POST['descuento'] ?? [];
$ivas             = $_POST['iva'] ?? [];

// Helpers
function resolver_serie_guardado(?string $cfgSerie, string $fecha): string {
  $ts = strtotime($fecha ?: date('Y-m-d'));
  $yy = date('y', $ts);
  $yyyy = date('Y', $ts);
  if ($cfgSerie) {
    $serie = str_replace(['{yy}','{yyyy}'], [$yy, $yyyy], $cfgSerie);
    return trim($serie) !== '' ? $serie : $yy;
  }
  return $yy;
}

try {
  $pdo->beginTransaction();

  // Config usuario (por si no está en sesión)
  $stU = $pdo->prepare("SELECT serie_facturas, inicio_serie_facturas FROM usuarios WHERE id=?");
  $stU->execute([$uid]);
  $cfg = $stU->fetch(PDO::FETCH_ASSOC) ?: ['serie_facturas'=>null,'inicio_serie_facturas'=>1];

  // Serie definitiva
  $serie = resolver_serie_guardado($cfg['serie_facturas'] ?? null, $fecha);

  // Número definitivo (FOR UPDATE para evitar colisiones)
  $stMax = $pdo->prepare("SELECT MAX(CAST(numero AS UNSIGNED)) FROM facturas WHERE usuario_id=? AND serie=? FOR UPDATE");
  $stMax->execute([$uid, $serie]);
  $maxN   = (int)$stMax->fetchColumn();
  $inicio = (int)($cfg['inicio_serie_facturas'] ?? 1);
  $numero = $maxN > 0 ? $maxN + 1 : max(1, $inicio);

  // Insert cabecera
  $stI = $pdo->prepare("INSERT INTO facturas
    (usuario_id, cliente_id, fecha, serie, numero, base_imponible, iva, irpf, total, estado, fecha_creacion, fecha_actualizacion)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'emitida', NOW(), NOW())");
  $stI->execute([$uid, $cliente_id, $fecha, $serie, (string)$numero, $base_imponible, $iva_total, $irpf_total, $total]);

  $factura_id = (int)$pdo->lastInsertId();

  // Insert líneas
  $n = max(count($cantidades), count($precios), count($ivas));
  for ($i = 0; $i < $n; $i++) {
    $cantidad = isset($cantidades[$i]) ? (float)$cantidades[$i] : 0;
    if ($cantidad <= 0) continue;

    $precio_unit = isset($precios[$i]) ? (float)$precios[$i] : 0;
    $desc        = isset($descuentos[$i]) ? (float)$descuentos[$i] : 0;
    $iva_pct     = isset($ivas[$i]) ? (float)$ivas[$i] : 0;
    $subtotal    = $cantidad * $precio_unit * (1 - $desc/100);

    // Nombre/ID producto (opcional)
    $producto_id = null;
    $nombre = 'Producto';
    if (!empty($producto_ids[$i])) {
      $pid = (int)$producto_ids[$i];
      $stP = $pdo->prepare("SELECT nombre FROM productos WHERE id=? AND usuario_id=?");
      $stP->execute([$pid, $uid]);
      $rowP = $stP->fetch(PDO::FETCH_ASSOC);
      $producto_id = $pid;
      $nombre = $rowP ? $rowP['nombre'] : 'Producto';
    } else {
      $nombre = trim((string)($producto_nombres[$i] ?? 'Producto'));
      if ($nombre === '') $nombre = 'Producto';
    }

    $stL = $pdo->prepare("INSERT INTO factura_productos
      (factura_id, producto_id, nombre, cantidad, precio_unitario, iva_porcentaje, subtotal, usuario_id)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stL->execute([$factura_id, $producto_id, $nombre, $cantidad, $precio_unit, $iva_pct, $subtotal, $uid]);
  }

  $pdo->commit();
  header('Location: index.php?p=facturas-index&ok=1');
  exit;

} catch (Throwable $e) {
  $pdo->rollBack();
  http_response_code(500);
  echo "Error al guardar la factura: " . htmlspecialchars($e->getMessage());
}
