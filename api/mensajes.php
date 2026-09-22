<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$user = current_user();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $accion = (string) ($_GET['accion'] ?? 'lista');

    if ($accion === 'lista') {
        $stmt = $pdo->prepare(
            'SELECT m.*, u.nombre AS nombre_emisor, u.foto_perfil,
                    (SELECT COUNT(*) FROM mensajes WHERE id_receptor = :uid AND id_emisor = m.id_emisor AND leido = 0) AS no_leidos,
                    (SELECT contenido FROM mensajes WHERE id_emisor = m.id_emisor AND id_receptor = :uid2 ORDER BY fecha_envio DESC LIMIT 1) AS ultimo_mensaje
             FROM mensajes m
             JOIN usuarios u ON u.id_usuario = m.id_emisor
             WHERE m.id_receptor = :uid3
             GROUP BY m.id_emisor
             ORDER BY MAX(m.fecha_envio) DESC'
        );
        $stmt->execute(['uid' => $user['id_usuario'], 'uid2' => $user['id_usuario'], 'uid3' => $user['id_usuario']]);
        echo json_encode(['ok' => true, 'conversaciones' => $stmt->fetchAll()]);
        exit;
    }

    if ($accion === 'chat') {
        $idOtro = (int) ($_GET['id'] ?? 0);
        if (!$idOtro) {
            echo json_encode(['ok' => false, 'error' => 'ID inválido.']);
            exit;
        }
        $despues = (string) ($_GET['despues'] ?? '');

        $sql = 'SELECT m.*, u.nombre AS nombre_emisor
                FROM mensajes m JOIN usuarios u ON u.id_usuario = m.id_emisor
                WHERE ((m.id_emisor = :a AND m.id_receptor = :b) OR (m.id_emisor = :b2 AND m.id_receptor = :a2))';
        $params = ['a' => $user['id_usuario'], 'b' => $idOtro, 'b2' => $idOtro, 'a2' => $user['id_usuario']];
        if ($despues !== '') {
            $sql .= ' AND m.fecha_envio > :d';
            $params['d'] = $despues;
        }
        $sql .= ' ORDER BY m.fecha_envio ASC LIMIT 100';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $msgs = $stmt->fetchAll();

        $pdo->prepare('UPDATE mensajes SET leido = 1 WHERE id_emisor = :e AND id_receptor = :r AND leido = 0')
            ->execute(['e' => $idOtro, 'r' => $user['id_usuario']]);

        echo json_encode(['ok' => true, 'mensajes' => $msgs]);
        exit;
    }

    if ($accion === 'unread') {
        $cnt = unread_messages_count($user['id_usuario']);
        echo json_encode(['ok' => true, 'count' => $cnt]);
        exit;
    }

    echo json_encode(['ok' => false, 'error' => 'Acción no válida.']);
    exit;
}

if ($method === 'POST') {
    if (empty($_POST)) {
        http_response_code(413);
        echo json_encode(['ok' => false, 'error' => 'El archivo adjunto supera el límite de 5 MB.']);
        exit;
    }
    csrf_verify();

    $idReceptor = (int) ($_POST['id_receptor'] ?? 0);
    $contenido = trim((string) ($_POST['contenido'] ?? ''));
    $idGuia = !empty($_POST['id_guia']) ? (int) $_POST['id_guia'] : null;

    if (!$idReceptor || $contenido === '') {
        echo json_encode(['ok' => false, 'error' => 'Datos incompletos.']);
        exit;
    }
    if (mb_strlen($contenido) > 2000) {
        echo json_encode(['ok' => false, 'error' => 'El mensaje no puede exceder 2000 caracteres.']);
        exit;
    }

    $receptor = $pdo->prepare('SELECT id_usuario FROM usuarios WHERE id_usuario = :id AND activo = 1 LIMIT 1');
    $receptor->execute(['id' => $idReceptor]);
    if (!$receptor->fetch()) {
        echo json_encode(['ok' => false, 'error' => 'Receptor no válido.']);
        exit;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO mensajes (id_emisor, id_receptor, id_guia, contenido) VALUES (:e, :r, :g, :c)'
    );
    $stmt->execute(['e' => $user['id_usuario'], 'r' => $idReceptor, 'g' => $idGuia, 'c' => $contenido]);
    $idMsg = (int) $pdo->lastInsertId();

    if (!empty($_FILES['archivo'])) {
        $arch = $_FILES['archivo'];
        if ($arch['error'] === UPLOAD_ERR_OK && $arch['size'] <= 5 * 1024 * 1024) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $arch['tmp_name']);
            finfo_close($finfo);
            $allowed = allowed_mime_types();
            if (in_array($mime, $allowed, true)) {
                $ext = strtolower(pathinfo($arch['name'], PATHINFO_EXTENSION));
                $dir = __DIR__ . '/../uploads/archivos/' . date('Y') . '/' . date('m');
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $saved = bin2hex(random_bytes(16)) . '.' . $ext;
                if (move_uploaded_file($arch['tmp_name'], $dir . '/' . $saved)) {
                    $rel = 'uploads/archivos/' . date('Y') . '/' . date('m') . '/' . $saved;
                    $ins = $pdo->prepare(
                        'INSERT INTO archivos_adjuntos (id_usuario, nombre_original, nombre_guardado, tipo_mime, tamanio)
                         VALUES (:u, :no, :ng, :tm, :t)'
                    );
                    $ins->execute([
                        'u'  => $user['id_usuario'],
                        'no' => mb_substr($arch['name'], 0, 255),
                        'ng' => $rel,
                        'tm' => $mime,
                        't'  => $arch['size'],
                    ]);
                    $idArch = (int) $pdo->lastInsertId();
                    $pdo->prepare('INSERT INTO mensajes_archivos (id_mensaje, id_archivo) VALUES (:m, :a)')
                        ->execute(['m' => $idMsg, 'a' => $idArch]);
                }
            }
        }
    }

    echo json_encode(['ok' => true, 'id' => $idMsg]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
