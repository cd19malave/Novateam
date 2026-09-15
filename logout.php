<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$tab = current_tab();
if ($tab !== null) {
    unset($_SESSION['tabs'][$tab]);
    unset($_SESSION['active_tab']);
    unset($_SESSION['user']);
    $_SESSION['tab_flash'][$tab] = ['ok' => 'Cerraste la sesión de esta pestaña.'];
    redirect('login.php');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $p['path'],
        'domain' => $p['domain'],
        'secure' => $p['secure'],
        'httponly' => $p['httponly'],
        'samesite' => $p['samesite'] ?? 'Lax',
    ]);
}
session_destroy();
header('Location: index.php');
exit;