<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/auth.php';

// ⬇️ usa tu login real (ruta relativa desde /public)
if (!isset($_SESSION['usuario_id'])) {
  header('Location: ../public/login.php'); // <- tu login real
  exit;
}

function render($pageTitle, $viewPath, $vars = []) {
  global $pdo;           // <-- importante
  extract($vars);
  ob_start();
  require $viewPath;
  $content = ob_get_clean();
  require __DIR__ . '/../views/layout.php';
}


$p = $_GET['p'] ?? 'dashboard';

switch ($p) {
	
	
 	// ⬅️ DASHBOARD
 
 case 'dashboard':
  $pageTitle = 'Dashboard';
  render($pageTitle, __DIR__ . '/../views/pages/dashboard.php');
  break;

	
	// ⬅️ CLIENTES
	
	case 'clientes-index':
	  $pageTitle = 'Clientes';
	  render($pageTitle, __DIR__ . '/../views/pages/clientes/index.php');
	  break;

	case 'clientes-nuevo':
	  $pageTitle = 'Nuevo cliente';
	  render($pageTitle, __DIR__ . '/../views/pages/clientes/nuevo.php');
	  break;

	case 'clientes-guardar':
	  require __DIR__ . '/../views/pages/clientes/guardar.php';
	  break;

	case 'clientes-editar':
	  $pageTitle = 'Editar cliente';
	  render($pageTitle, __DIR__ . '/../views/pages/clientes/editar.php');
	  break;

	case 'clientes-actualizar':
	  require __DIR__ . '/../views/pages/clientes/actualizar.php';
	  break;

	case 'clientes-estado': // activar/desactivar
	  require __DIR__ . '/../views/pages/clientes/estado.php';
	  break;

	// ⬅️ PRODUCTOS
	
	case 'productos-index':
	$pageTitle = 'Productos';
	render($pageTitle, __DIR__ . '/../views/pages/productos/index.php');
	break;

	case 'productos-nuevo':
	$pageTitle = 'Nuevo producto';
	render($pageTitle, __DIR__ . '/../views/pages/productos/nuevo.php');
	break;

	case 'productos-guardar':
	require __DIR__ . '/../views/pages/productos/guardar.php';
	break;

	case 'productos-editar':
	$pageTitle = 'Editar producto';
	render($pageTitle, __DIR__ . '/../views/pages/productos/editar.php');
	break;

	case 'productos-actualizar':
	require __DIR__ . '/../views/pages/productos/actualizar.php';
	break;

	case 'productos-estado': // activar/desactivar
	require __DIR__ . '/../views/pages/productos/estado.php';
	break;

	
	// ⬅️ FACTURAS
	

case 'facturas-index':
  $pageTitle = 'Facturas';
  render($pageTitle, __DIR__ . '/../views/pages/facturas/index.php');
  break;

case 'facturas-nuevo':
  $pageTitle = 'Nueva factura';
  render($pageTitle, __DIR__ . '/../views/pages/facturas/nuevo.php');
  break;

case 'facturas-guardar':
  require __DIR__ . '/../views/pages/facturas/guardar.php';
  break;

case 'facturas-generarpdf': // vista HTML tipo "PDF"
  $pageTitle = 'Factura';
  render($pageTitle, __DIR__ . '/../views/pages/facturas/generar_pdf.php');
  break;

case 'facturas-estado': // cambiar borrador/emitida/pagada
  require __DIR__ . '/../views/pages/facturas/estado.php';
  break;
  
  
  	// ⬅️ CONFIGURACIÓN
	
	
  case 'config-index':
  $pageTitle = 'Configuración';
  render($pageTitle, __DIR__ . '/../views/pages/config/index.php');
  break;

case 'config-guardar':      // Perfil / Facturación / Apariencia
  require __DIR__ . '/../views/pages/config/guardar.php';
  break;

case 'config-password':     // Seguridad
  require __DIR__ . '/../views/pages/config/password.php';
  break;

case 'config-logo':         // Logo (multipart/form-data)
  require __DIR__ . '/../views/pages/config/logo.php';
  break;

// ⬅️ GASTOS
  
	case 'gastos-index':
	$pageTitle = 'Gastos';
	render($pageTitle, __DIR__ . '/../views/pages/gastos/index.php');
	break;

	case 'gastos-nuevo':
	$pageTitle = 'Nuevo gasto';
	render($pageTitle, __DIR__ . '/../views/pages/gastos/nuevo.php');
	break;

	case 'gastos-guardar':
	require __DIR__ . '/../views/pages/gastos/guardar.php';
	break;

	case 'gastos-estado': // cambiar a pagado/finalizado/borrador
	require __DIR__ . '/../views/pages/gastos/estado.php';
	break;

	case 'gastos-adjuntar':
	$pageTitle = 'Adjuntar archivo a gasto';
	render($pageTitle, __DIR__ . '/../views/pages/gastos/adjuntar.php');
	break;

	case 'gastos-subir-adjunto':
	require __DIR__ . '/../views/pages/gastos/subir_adjunto.php';
	break;

	case 'gastos-quitar-adjunto':
	require __DIR__ . '/../views/pages/gastos/quitar_adjunto.php';
	break;


  
  default:
    http_response_code(404);
    echo 'Página no encontrada';
}
?>