<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$user = current_user();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST)) {
        http_response_code(413);
        echo json_encode(['ok' => false, 'error' => 'Los archivos superan el límite: la foto de perfil debe pesar máximo 2 MB y el fondo 3 MB.']);
        exit;
    }
    csrf_verify();

    $bio = trim((string) ($_POST['bio'] ?? ''));
    $tema = (string) ($_POST['tema_color'] ?? 'default');

    if (mb_strlen($bio) > 300) {
        echo json_encode(['ok' => false, 'error' => 'La biografía no puede exceder 300 caracteres.']);
        exit;
    }

    $temas = ['default', 'oscuro', 'verde', 'rosa', 'purpura'];
    if (!in_array($tema, $temas, true)) $tema = 'default';

    $params = ['bio' => $bio !== '' ? $bio : null, 'tema' => $tema, 'id' => $user['id_usuario']];

    if (!empty($_FILES['foto_perfil'])) {
        $arch = $_FILES['foto_perfil'];
        if ($arch['error'] === UPLOAD_ERR_OK) {
            if ($arch['size'] <= 2 * 1024 * 1024) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $arch['tmp_name']);
                finfo_close($finfo);
                if (in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
                    $dir = __DIR__ . '/../uploads/perfiles/' . date('Y') . '/' . date('m');
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                    $ext = strtolower(pathinfo($arch['name'], PATHINFO_EXTENSION));
                    $saved = bin2hex(random_bytes(16)) . '.' . $ext;
                    if (move_uploaded_file($arch['tmp_name'], $dir . '/' . $saved)) {
                        $params['foto'] = 'uploads/perfiles/' . date('Y') . '/' . date('m') . '/' . $saved;
                    }
                }
            }
        }
    }

    if (!empty($_FILES['fondo_perfil'])) {
        $arch = $_FILES['fondo_perfil'];
        if ($arch['error'] === UPLOAD_ERR_OK) {
            if ($arch['size'] <= 3 * 1024 * 1024) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $arch['tmp_name']);
                finfo_close($finfo);
                if (in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
                    $dir = __DIR__ . '/../uploads/perfiles/' . date('Y') . '/' . date('m');
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                    $ext = strtolower(pathinfo($arch['name'], PATHINFO_EXTENSION));
                    $saved = bin2hex(random_bytes(16)) . '.' . $ext;
                    if (move_uploaded_file($arch['tmp_name'], $dir . '/' . $saved)) {
                        $params['fondo'] = 'uploads/perfiles/' . date('Y') . '/' . date('m') . '/' . $saved;
                    }
                }
            }
        }
    }

    $sets = ['bio = :bio', 'tema_color = :tema'];
    if (isset($params['foto'])) $sets[] = 'foto_perfil = :foto';
    if (isset($params['fondo'])) $sets[] = 'fondo_perfil = :fondo';

    $sql = 'UPDATE usuarios SET ' . implode(', ', $sets) . ' WHERE id_usuario = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    refresh_user($user['id_usuario']);
    echo json_encode(['ok' => true, 'msg' => 'Perfil actualizado.']);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
