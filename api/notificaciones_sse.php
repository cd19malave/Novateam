<?php
declare(strict_types=1);

// Notificaciones en tiempo real vía Server-Sent Events (SSE).
// El navegador abre una conexión con EventSource y el servidor le empuja
// cada notificación nueva SIN que el cliente tenga que recargar la página.

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

@ini_set('output_buffering', '0');
@ini_set('zlib.output_compression', '0');
@ini_set('implicit_flush', '1');
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', '1');
}

header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Accel-Buffering: no');

// Misma configuración de sesión que includes/bootstrap.php
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_name(env('SESSION_NAME', 'NOVATEAMSESS') ?? 'NOVATEAMSESS');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
@ini_set('session.use_strict_mode', '1');
@ini_set('session.use_only_cookies', '1');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$tabIn = $_GET['tab'] ?? '';
$user = null;
if (is_string($tabIn) && preg_match('/^[A-Za-z0-9_-]{16,64}$/', $tabIn)) {
    $user = $_SESSION['tabs'][$tabIn]['user'] ?? null;
}
if (!$user) {
    // Respaldo: sesión global (por si la pestaña aún no tiene slot propio).
    $user = $_SESSION['user'] ?? null;
}

// Liberar la sesión para no bloquear las demás peticiones de la pestaña.
session_write_close();

if (!$user || !is_array($user) || ($user['rol'] ?? '') !== 'estudiante' || empty($user['id_usuario'])) {
    echo "event: cerrar\ndata: {\"ok\":false,\"razon\":\"no_estudiante\"}\n\n";
    flush();
    exit;
}

$userId = (int) $user['id_usuario'];

while (ob_get_level() > 0) {
    ob_end_clean();
}

// Cursor: en reconexión se retoma desde el último id enviado (Last-Event-ID);
// en conexión nueva se inicia en el máximo actual para no repetir notificaciones viejas.
$lastId = 0;
$h = $_SERVER['HTTP_LAST_EVENT_ID'] ?? '';
if (is_string($h) && ctype_digit($h) && (int) $h > 0) {
    $lastId = (int) $h;
} else {
    $g = $_GET['lastid'] ?? '';
    if (is_string($g) && ctype_digit($g) && (int) $g > 0) {
        $lastId = (int) $g;
    }
}

$maxId = (int) db()->query("SELECT COALESCE(MAX(id_notificacion), 0) FROM notificaciones WHERE id_destinatario = {$userId}")->fetchColumn();

$countStmt = db()->prepare('SELECT COUNT(*) FROM notificaciones WHERE id_destinatario = :u AND leida = 0');

$countStmt->execute(['u' => $userId]);
$count = (int) $countStmt->fetchColumn();

echo "event: ready\ndata: " . json_encode(['maxId' => $maxId, 'count' => $count], JSON_UNESCAPED_UNICODE) . "\n\n";
flush();

set_time_limit(0);
ignore_user_abort(false);

$cursor = $lastId > 0 ? $lastId : $maxId;

$stmt = db()->prepare(
    'SELECT n.id_notificacion, n.titulo, n.mensaje, n.fecha_creacion, n.id_guia, u.nombre AS profesor
     FROM notificaciones n
     LEFT JOIN usuarios u ON u.id_usuario = n.id_profesor
     WHERE n.id_destinatario = :u AND n.leida = 0 AND n.id_notificacion > :c
     ORDER BY n.id_notificacion'
);

$start = microtime(true);
$lastBeat = $start;
$maxRun = 600; // la conexión se cierra cada 10 min; EventSource reconecta sola

while (true) {
    if (microtime(true) - $start > $maxRun) {
        break;
    }
    if (connection_aborted()) {
        break;
    }

    try {
        $stmt->execute(['u' => $userId, 'c' => $cursor]);
        $items = $stmt->fetchAll();
    } catch (Throwable $e) {
        echo ": error_db\n\n";
        flush();
        break;
    }

    if ($items) {
        $newCursor = $cursor;
        foreach ($items as $it) {
            $nid = (int) $it['id_notificacion'];
            if ($nid > $newCursor) {
                $newCursor = $nid;
            }
        }
        $countStmt->execute(['u' => $userId]);
        $count = (int) $countStmt->fetchColumn();

        echo "id: {$newCursor}\n";
        echo "event: notificacion\n";
        echo 'data: ' . json_encode(['items' => $items, 'count' => $count], JSON_UNESCAPED_UNICODE) . "\n\n";
        flush();
        $cursor = $newCursor;
    }

    $now = microtime(true);
    if ($now - $lastBeat >= 15) {
        echo ": ping\n\n";
        flush();
        $lastBeat = $now;
    }

    for ($i = 0; $i < 4; $i++) {
        if (connection_aborted()) {
            break 2;
        }
        usleep(500000);
        if (microtime(true) - $start > $maxRun) {
            break 2;
        }
    }
}

exit;