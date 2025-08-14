<?php
require_once __DIR__ . '/../includes/session.php';
secure_session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/permissions.php';

// ⬇️ usa tu login real (ruta relativa desde /public)
if (!isset($_SESSION['usuario_id'])) {
  header('Location: ../public/login.php'); // <- tu login real
  exit;
}

function render($pageTitle, $viewPath, PDO $pdo, $vars = []) {
  $cliente = $vars['cliente'] ?? null;
  ob_start();
  require $viewPath;
  $content = ob_get_clean();
  require __DIR__ . '/../views/layout.php';
}


$p = $_GET['p'] ?? 'dashboard';

switch ($p) {
	
	
 	// ⬅️ DASHBOARD
 
case 'dashboard':
  require_access('dashboard');
  $pageTitle = 'Dashboard';
  render($pageTitle, __DIR__ . '/../views/pages/dashboard.php', $pdo);
  break;

	
	// ⬅️ CLIENTES
	
        case 'clientes-index':
          require_access('clientes');
          $pageTitle = 'Clientes';
          render($pageTitle, __DIR__ . '/../views/pages/clientes/index.php', $pdo);
          break;

        case 'clientes-nuevo':
          require_access('clientes');
          $pageTitle = 'Nuevo cliente';
          render($pageTitle, __DIR__ . '/../views/pages/clientes/nuevo.php', $pdo);
          break;

        case 'clientes-guardar':
          require_access('clientes');
          require __DIR__ . '/../views/pages/clientes/guardar.php';
          break;

        case 'clientes-editar':
          require_access('clientes');
          $pageTitle = 'Editar cliente';
          render($pageTitle, __DIR__ . '/../views/pages/clientes/editar.php', $pdo);
          break;

        case 'clientes-actualizar':
          require_access('clientes');
          require __DIR__ . '/../views/pages/clientes/actualizar.php';
          break;

        case 'clientes-estado': // activar/desactivar
          require_access('clientes');
          require __DIR__ . '/../views/pages/clientes/estado.php';
          break;

	// ⬅️ PRODUCTOS
	
        case 'productos-index':
        require_access('productos');
        $pageTitle = 'Productos';
        render($pageTitle, __DIR__ . '/../views/pages/productos/index.php', $pdo);
        break;

        case 'productos-nuevo':
        require_access('productos');
        $pageTitle = 'Nuevo producto';
        render($pageTitle, __DIR__ . '/../views/pages/productos/nuevo.php', $pdo);
        break;

        case 'productos-guardar':
        require_access('productos');
        require __DIR__ . '/../views/pages/productos/guardar.php';
        break;

        case 'productos-editar':
        require_access('productos');
        $pageTitle = 'Editar producto';
        render($pageTitle, __DIR__ . '/../views/pages/productos/editar.php', $pdo);
        break;

        case 'productos-actualizar':
        require_access('productos');
        require __DIR__ . '/../views/pages/productos/actualizar.php';
        break;

        case 'productos-estado': // activar/desactivar
        require_access('productos');
        require __DIR__ . '/../views/pages/productos/estado.php';
        break;

	
	// ⬅️ FACTURAS
	

case 'facturas-index':
  require_access('facturas');
  $pageTitle = 'Facturas';
  render($pageTitle, __DIR__ . '/../views/pages/facturas/index.php', $pdo);
  break;

case 'facturas-nuevo':
  require_access('facturas');
  $pageTitle = 'Nueva factura';
  render($pageTitle, __DIR__ . '/../views/pages/facturas/nuevo.php', $pdo);
  break;

case 'facturas-guardar':
  require_access('facturas');
  require __DIR__ . '/../views/pages/facturas/guardar.php';
  break;

case 'facturas-generarpdf': // vista HTML tipo "PDF"
  require_access('facturas');
  $pageTitle = 'Factura';
  render($pageTitle, __DIR__ . '/../views/pages/facturas/generar_pdf.php', $pdo);
  break;

case 'facturas-estado': // cambiar borrador/emitida/pagada
  require_access('facturas');
  require __DIR__ . '/../views/pages/facturas/estado.php';
  break;
  
  
  	// ⬅️ CONFIGURACIÓN
	
	
  case 'config-index':
  require_access('config');
  $pageTitle = 'Configuración';
  render($pageTitle, __DIR__ . '/../views/pages/config/index.php', $pdo);
  break;

case 'config-guardar':      // Perfil / Facturación / Apariencia
  require_access('config');
  require __DIR__ . '/../views/pages/config/guardar.php';
  break;

case 'config-password':     // Seguridad
  require_access('config');
  require __DIR__ . '/../views/pages/config/password.php';
  break;

case 'config-logo':         // Logo (multipart/form-data)
  require_access('config');
  require __DIR__ . '/../views/pages/config/logo.php';
  break;
case 'config-roles':
  if (!is_admin()) { http_response_code(403); exit('Acceso denegado'); }
  require_access('config');
  $pageTitle = 'Roles y permisos';
  render($pageTitle, __DIR__ . '/../views/pages/config/roles.php', $pdo);
  break;

// ⬅️ GASTOS
  
        case 'gastos-index':
        require_access('gastos');
        $pageTitle = 'Gastos';
        render($pageTitle, __DIR__ . '/../views/pages/gastos/index.php', $pdo);
        break;

        case 'gastos-nuevo':
        require_access('gastos');
        $pageTitle = 'Nuevo gasto';
        render($pageTitle, __DIR__ . '/../views/pages/gastos/nuevo.php', $pdo);
        break;

        case 'gastos-guardar':
        require_access('gastos');
        require __DIR__ . '/../views/pages/gastos/guardar.php';
        break;

        case 'gastos-estado': // cambiar a pagado/finalizado/borrador
        require_access('gastos');
        require __DIR__ . '/../views/pages/gastos/estado.php';
        break;

        case 'gastos-adjuntar':
        require_access('gastos');
        $pageTitle = 'Adjuntar archivo a gasto';
        render($pageTitle, __DIR__ . '/../views/pages/gastos/adjuntar.php', $pdo);
        break;

        case 'gastos-subir-adjunto':
        require_access('gastos');
        require __DIR__ . '/../views/pages/gastos/subir_adjunto.php';
        break;

        case 'gastos-quitar-adjunto':
        require_access('gastos');
        require __DIR__ . '/../views/pages/gastos/quitar_adjunto.php';
        break;

        case 'gastos-archivo':
        require_access('gastos');
        require __DIR__ . '/../views/pages/gastos/archivo.php';
        break;


  
  default:
    http_response_code(404);
    echo 'Página no encontrada';
}

