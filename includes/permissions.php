<?php
require_once __DIR__ . '/auth.php';
function has_access(string $module): bool {
    if (is_admin()) return true;
    $uid = (int)($_SESSION['usuario_id'] ?? 0);
    if ($uid <= 0) return false;
    global $pdo;
    $st = $pdo->prepare("SELECT COUNT(*) FROM usuarios u
                           JOIN roles_modulos rm ON rm.role_id = u.role_id
                           JOIN modulos m ON m.id = rm.modulo_id
                          WHERE u.id=? AND m.nombre=?");
    $st->execute([$uid, $module]);
    return $st->fetchColumn() > 0;
}

function require_access(string $module): void {
    if (!has_access($module)) {
        http_response_code(403);
        exit('Acceso denegado');
    }
}
?>
