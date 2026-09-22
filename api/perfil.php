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
        echo json_encode(['ok' => false, 'error' => 'Los archivos superan el límite: la foto de perfil y el fondo deben pesar máximo 50 MB.']);
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

    $uploadError = null;
    $saveImage = function (string $field) use (&$params, &$uploadError): void {
        if (!isset($_FILES[$field]) || empty($_FILES[$field]['name'])) {
            return;
        }
        $arch = $_FILES[$field];
        if ($arch['error'] !== UPLOAD_ERR_OK) {
            $uploadError = $arch['error'] === UPLOAD_ERR_INI_SIZE || $arch['error'] === UPLOAD_ERR_FORM_SIZE
                ? 'El archivo supera el límite de 50 MB.'
                : 'No se pudo subir el archivo.';
            return;
        }
        if ($arch['size'] > 50 * 1024 * 1024) {
            $uploadError = 'El archivo supera el límite de 50 MB.';
            return;
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $arch['tmp_name']) : '';
        if ($finfo) finfo_close($finfo);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
            $uploadError = 'El archivo debe ser una imagen (JPG, PNG, GIF o WEBP).';
            return;
        }
        $dir = __DIR__ . '/../uploads/perfiles/' . date('Y') . '/' . date('m');
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            $uploadError = 'No se pudo crear la carpeta de uploads. Reintenta más tarde.';
            return;
        }
        $ext = strtolower(pathinfo($arch['name'], PATHINFO_EXTENSION));
        $saved = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($arch['tmp_name'], $dir . '/' . $saved)) {
            $uploadError = 'No se pudo guardar la imagen. Reintenta más tarde.';
            return;
        }
        $key = $field === 'foto_perfil' ? 'foto' : 'fondo';
        $params[$key] = 'uploads/perfiles/' . date('Y') . '/' . date('m') . '/' . $saved;
    };

    $saveImage('foto_perfil');
    $saveImage('fondo_perfil');

    if ($uploadError !== null) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $uploadError]);
        exit;
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
