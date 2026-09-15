<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$user = current_user();

if (($_GET['accion'] ?? '') === 'marcar_leidas') {
    db()->prepare('UPDATE notificaciones SET leida = 1 WHERE id_destinatario = :id AND leida = 0')
        ->execute(['id' => $user['id_usuario']]);
    echo json_encode(['ok' => true, 'count' => 0]);
    exit;
}

echo json_encode(['ok' => true, 'count' => unread_notifications_count((int) $user['id_usuario'])]);