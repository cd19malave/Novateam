<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

if (!headers_sent()) {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_name(env('SESSION_NAME', 'NOVATEAMSESS') ?? 'NOVATEAMSESS');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/mailer.php';

if (!empty($_SESSION['user']['id_usuario'])) {
    $lastRefresh = (int) ($_SESSION['_last_refresh'] ?? 0);
    $now = time();
    if ($now - $lastRefresh > 60) {
        try {
            refresh_user((int) $_SESSION['user']['id_usuario']);
            $_SESSION['_last_refresh'] = $now;
        } catch (Throwable $e) {
            // Si falla la conexión, mantener la sesión actual sin destruirla
            error_log('NovaTeam: refresh_user error: ' . $e->getMessage());
        }
    }
}

if (!empty($_SESSION['user']['tema_color']) && $_SESSION['user']['tema_color'] !== 'default') {
    $tc = tema_colors($_SESSION['user']['tema_color']);
    echo '<style>:root{--edu-bg:' . $tc['bg'] . ';--edu-dark:' . $tc['text'] . ';--edu-primary:' . $tc['primary'] . ';}body{background:' . $tc['bg'] . ';color:' . $tc['text'] . ';}</style>';
}
