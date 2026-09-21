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

$tabIn = $_REQUEST['tab'] ?? '';
$hasTab = is_string($tabIn) && preg_match('/^[A-Za-z0-9_-]{16,64}$/', $tabIn);

if ($hasTab) {
    $_SESSION['active_tab'] = $tabIn;
    if (!isset($_SESSION['tabs'][$tabIn]) || !is_array($_SESSION['tabs'][$tabIn])) {
        // Pestaña nueva: hereda la sesión global en vez de quedar vacía (evita que
        // reabrir la app o abrir una pestaña nueva cierre la sesión).
        $_SESSION['tabs'][$tabIn] = ['user' => $_SESSION['user'] ?? null];
        if (count($_SESSION['tabs']) > 24) {
            foreach ($_SESSION['tabs'] as $k => $slot) {
                if ($k === $tabIn || !empty($slot['user'])) {
                    continue;
                }
                unset($_SESSION['tabs'][$k]);
                if (count($_SESSION['tabs']) <= 20) {
                    break;
                }
            }
        }
    }
} else {
    // Sin token de pestaña NO se destruye la sesión: se mantiene la global.
    unset($_SESSION['active_tab']);
}

$activeTab = current_tab();
if ($activeTab !== null) {
    $slotUser = $_SESSION['tabs'][$activeTab]['user'] ?? null;
    if (is_array($slotUser) && !empty($slotUser['id_usuario'])) {
        $_SESSION['user'] = $slotUser;
    } elseif (is_array($_SESSION['user'] ?? null) && !empty($_SESSION['user']['id_usuario'])) {
        // La pestaña no tiene usuario explícito pero hay sesión global: heredarla.
        $_SESSION['tabs'][$activeTab]['user'] = $_SESSION['user'];
    } else {
        $_SESSION['user'] = null;
    }
}

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
