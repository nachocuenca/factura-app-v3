<?php
// public/login.php
require_once __DIR__ . '/../includes/session.php';
secure_session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';

/* Si ya hay sesión, vete al dashboard */
if (!empty($_SESSION['usuario_id'])) {
  header('Location: index.php?p=dashboard');
  exit;
}

/* ------------------ manejar POST (login) ------------------ */
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $email = trim($_POST['email'] ?? '');
  $pass  = (string)($_POST['password'] ?? '');
  if ($email === '' || $pass === '') {
    $err = 'Introduce tu email y contraseña.';
  } else {
    $st = $pdo->prepare("SELECT u.id, u.email, u.password, u.nombre, u.rol, u.logo,
                                u.serie_facturas, u.inicio_serie_facturas,
                                u.serie_abonos, u.inicio_serie_abonos,
                                u.role_id, r.nombre AS role_name
                           FROM usuarios u
                           LEFT JOIN roles r ON r.id = u.role_id
                          WHERE u.email = ?
                          LIMIT 1");
    $st->execute([$email]);
    $u = $st->fetch(PDO::FETCH_ASSOC);

    // IMPORTANTE: las contraseñas deben estar con password_hash()
    if (!$u || !password_verify($pass, $u['password'])) {
      $err = 'Credenciales incorrectas.';
    } else {
      session_regenerate_id(true);
      $_SESSION['usuario_id']     = (int)$u['id'];
      $_SESSION['usuario_email']  = $u['email'];
      $_SESSION['usuario_nombre'] = $u['nombre'] ?: $u['email'];
      $_SESSION['role_id']        = (int)($u['role_id'] ?? 0);
      $_SESSION['usuario_rol']    = $u['role_name'] ?: ($u['rol'] ?: 'cliente');
      $_SESSION['usuario_logo']   = $u['logo'] ?: null;

      $_SESSION['serie_facturas']        = $u['serie_facturas'] ?? null;
      $_SESSION['inicio_serie_facturas'] = (int)($u['inicio_serie_facturas'] ?? 1);
      $_SESSION['serie_abonos']          = $u['serie_abonos'] ?? null;
      $_SESSION['inicio_serie_abonos']   = (int)($u['inicio_serie_abonos'] ?? 1);


      header('Location: index.php?p=dashboard');
      exit;
    }
  }
}


/* ------------------ recursos visuales ------------------ */
$basePublic = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\'); // /factura-app-v3/public
$bgPathRel  = 'assets/img/login-bg.jpg';                            // coloca tu imagen aquí
$bgUrl      = $basePublic . '/' . $bgPathRel;

// logo del admin (si existe)
$adminLogo = '';
try {
  $st = $pdo->query("SELECT logo FROM usuarios WHERE rol='admin' ORDER BY id ASC LIMIT 1");
  $adminLogo = (string)($st->fetchColumn() ?: '');
  if ($adminLogo !== '' && !preg_match('~^https?://~i', $adminLogo)) {
    $adminLogo = $basePublic . '/' . ltrim($adminLogo, '/');
  }
} catch (Throwable $e) {
  // si falla, seguimos sin logo
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Acceder · Factura-app V3</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Tema Pastel -->
  <link href="assets/css/theme.css?v=1" rel="stylesheet">

  <style>
    body.login-page{
      min-height: 100vh;
      background: url('<?= h($bgUrl) ?>') center/cover no-repeat fixed, linear-gradient(180deg,#f7f8fb,#eef1f7);
    }
    .auth-card { max-width: 440px; width:100%; }
    .brand img{ max-height:60px; width:auto; object-fit:contain; }
    .shadow-xl{ box-shadow: 0 20px 60px rgba(40,50,70,.12); }
  </style>
</head>
<body class="login-page d-flex align-items-center">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-11 col-sm-9 col-md-7 col-lg-5">
        <div class="card glass auth-card shadow-xl">
          <div class="card-body p-4">
            <div class="mb-3 text-center brand">
              <?php if ($adminLogo): ?>
                <img src="<?= h($adminLogo) ?>" alt="Logo">
              <?php else: ?>
                <div class="brand-title fw-bold">Factura-app V3</div>
              <?php endif; ?>
            </div>

            <h5 class="text-center mb-3">Acceder</h5>

            <?php if (!empty($err)): ?>
              <div class="alert alert-danger py-2"><?= h($err) ?></div>
            <?php elseif (isset($_GET['msg']) && $_GET['msg']==='logged_out_all'): ?>
              <div class="alert alert-info py-2">Tu sesión fue cerrada desde otro dispositivo.</div>
            <?php endif; ?>

            <form method="post" action="login.php" novalidate>
              <?php csrf_field(); ?>

              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" placeholder="tucorreo@dominio.com" required autofocus>
              </div>

              <div class="mb-2">
                <label class="form-label d-flex justify-content-between align-items-center">
                  <span>Contraseña</span>
                  <a class="small text-decoration-none" href="#" onclick="alert('Función pendiente');return false;">¿Olvidaste la contraseña?</a>
                </label>
                <div class="input-group">
                  <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                  <button class="btn btn-outline-secondary" type="button" id="togglePass">👁️</button>
                </div>
              </div>

              <div class="d-grid mt-3">
                <button class="btn btn-primary" type="submit">Entrar</button>
              </div>
            </form>

            <div class="text-center mt-3">
              <form method="post" action="logout.php" class="d-inline"> <?php csrf_field(); ?> <button type="submit" class="btn btn-link small text-muted text-decoration-none p-0">Cerrar sesión actual</button></form>
            </div>
          </div>
        </div>

        <div class="text-center text-muted small mt-3">
          © <?= date('Y') ?> · Factura-app V3
        </div>
      </div>
    </div>
  </div>

  <script>
    // mostrar/ocultar contraseña
    const t = document.getElementById('togglePass');
    const i = document.getElementById('password');
    if (t && i) t.addEventListener('click', ()=>{ const s=i.type==='password'; i.type=s?'text':'password'; t.textContent=s?'🙈':'👁️'; });
  </script>
</body>
</html>
