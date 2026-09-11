<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $idGuia = !empty($_POST['id_guia']) ? (int) $_POST['id_guia'] : null;
    $idEjercicio = !empty($_POST['id_ejercicio']) ? (int) $_POST['id_ejercicio'] : null;

    if (empty($_FILES['archivo'])) {
        echo json_encode(['ok' => false, 'error' => 'No se envió ningún archivo.']);
        exit;
    }
    $arch = $_FILES['archivo'];
    if ($arch['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['ok' => false, 'error' => 'Error al subir el archivo.']);
        exit;
    }
    if ($arch['size'] > 5 * 1024 * 1024) {
        echo json_encode(['ok' => false, 'error' => 'El archivo excede 5 MB.']);
        exit;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $arch['tmp_name']);
    finfo_close($finfo);

    $allowed = allowed_mime_types();
    if (!in_array($mime, $allowed, true)) {
        echo json_encode(['ok' => false, 'error' => 'Tipo de archivo no permitido.']);
        exit;
    }

    $ext = strtolower(pathinfo($arch['name'], PATHINFO_EXTENSION));
    $year = date('Y');
    $month = date('m');
    $dir = __DIR__ . '/../uploads/archivos/' . $year . '/' . $month;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $hash = bin2hex(random_bytes(16));
    $saved = $hash . '.' . $ext;
    if (!move_uploaded_file($arch['tmp_name'], $dir . '/' . $saved)) {
        echo json_encode(['ok' => false, 'error' => 'No se pudo guardar el archivo.']);
        exit;
    }
    $rel = 'uploads/archivos/' . $year . '/' . $month . '/' . $saved;
    $stmt = db()->prepare(
        'INSERT INTO archivos_adjuntos (id_usuario, id_guia, id_ejercicio, nombre_original, nombre_guardado, tipo_mime, tamanio)
         VALUES (:u, :g, :e, :no, :ng, :tm, :t)'
    );
    $stmt->execute([
        'u'  => $user['id_usuario'],
        'g'  => $idGuia,
        'e'  => $idEjercicio,
        'no' => mb_substr($arch['name'], 0, 255),
        'ng' => $rel,
        'tm' => $mime,
        't'  => $arch['size'],
    ]);
    $id = (int) db()->lastInsertId();
    echo json_encode([
        'ok'       => true,
        'id'       => $id,
        'nombre'   => $arch['name'],
        'url'      => $rel,
        'mime'     => $mime,
        'tamanio'  => $arch['size'],
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $idArchivo = (int) ($input['id_archivo'] ?? 0);
    if (!$idArchivo) {
        echo json_encode(['ok' => false, 'error' => 'ID inválido.']);
        exit;
    }
    $stmt = db()->prepare('SELECT * FROM archivos_adjuntos WHERE id_archivo = :id AND id_usuario = :u LIMIT 1');
    $stmt->execute(['id' => $idArchivo, 'u' => $user['id_usuario']]);
    $arch = $stmt->fetch();
    if (!$arch) {
        echo json_encode(['ok' => false, 'error' => 'Archivo no encontrado.']);
        exit;
    }
    $file = __DIR__ . '/../' . $arch['nombre_guardado'];
    if (file_exists($file)) {
        unlink($file);
    }
    $del = db()->prepare('DELETE FROM archivos_adjuntos WHERE id_archivo = :id');
    $del->execute(['id' => $idArchivo]);
    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
