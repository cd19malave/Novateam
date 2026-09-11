<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $stored = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $stored;
}

function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_role(string ...$roles): bool
{
    $user = current_user();
    return $user !== null && in_array($user['rol'], $roles, true);
}

function home_for_role(?string $rol = null): string
{
    $rol ??= current_user()['rol'] ?? '';
    return match ($rol) {
        'profesor' => 'profesor.php',
        'administrador' => 'admin.php',
        default => 'estudiante.php',
    };
}

function categoria_label(string $cat): string
{
    return $cat === 'ingles' ? 'Inglés' : 'Matemáticas';
}

function dificultad_label(string $dif): string
{
    return match ($dif) {
        'facil' => 'Fácil',
        'medio' => 'Medio',
        'dificil' => 'Difícil',
        default => $dif,
    };
}

function award_badges(int $userId, int $points): void
{
    $stmt = db()->prepare(
        'SELECT i.id_insignia
         FROM insignias i
         LEFT JOIN usuario_insignias ui
           ON ui.id_insignia = i.id_insignia AND ui.id_usuario = :uid
         WHERE i.puntos_requeridos <= :pts AND ui.id_usuario IS NULL'
    );
    $stmt->execute(['uid' => $userId, 'pts' => $points]);
    $ins = db()->prepare(
        'INSERT INTO usuario_insignias (id_usuario, id_insignia) VALUES (:uid, :iid)'
    );
    foreach ($stmt->fetchAll() as $row) {
        $ins->execute(['uid' => $userId, 'iid' => $row['id_insignia']]);
    }
}

function render_alerts(): void
{
    $error = flash('error');
    $ok = flash('ok');
    if ($error) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . e($error) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    if ($ok) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . e($ok) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

/* ── Archivos adjuntos ── */

function allowed_mime_types(): array
{
    return [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
        'audio/mpeg', 'audio/mp3',
        'video/mp4',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/msword',
    ];
}

function allowed_extensions(): array
{
    return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'mp3', 'mp4', 'docx', 'doc'];
}

function max_upload_bytes(): int
{
    return 5 * 1024 * 1024;
}

function file_icon(string $mime): string
{
    return match (true) {
        str_starts_with($mime, 'image/') => '<i class="bi bi-image text-primary"></i>',
        $mime === 'application/pdf' => '<i class="bi bi-file-earmark-pdf text-danger"></i>',
        str_starts_with($mime, 'audio/') => '<i class="bi bi-music-note-beamed text-success"></i>',
        str_starts_with($mime, 'video/') => '<i class="bi bi-play-circle text-warning"></i>',
        default => '<i class="bi bi-file-earmark text-secondary"></i>',
    };
}

function format_bytes(int $bytes): string
{
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . ' MB';
    }
    return round($bytes / 1024, 1) . ' KB';
}

function upload_file(array $file, ?int $idUser, ?int $idGuia = null, ?int $idEjercicio = null): ?int
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    if ($file['size'] > max_upload_bytes()) {
        flash('error', 'El archivo excede 5 MB.');
        return null;
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, allowed_mime_types(), true)) {
        flash('error', 'Tipo de archivo no permitido.');
        return null;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $year = date('Y');
    $month = date('m');
    $dir = __DIR__ . '/../uploads/archivos/' . $year . '/' . $month;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $hash = bin2hex(random_bytes(16));
    $saved = $hash . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $saved)) {
        flash('error', 'No se pudo guardar el archivo.');
        return null;
    }
    $rel = 'uploads/archivos/' . $year . '/' . $month . '/' . $saved;
    $stmt = db()->prepare(
        'INSERT INTO archivos_adjuntos (id_usuario, id_guia, id_ejercicio, nombre_original, nombre_guardado, tipo_mime, tamanio)
         VALUES (:u, :g, :e, :no, :ng, :tm, :t)'
    );
    $stmt->execute([
        'u'  => $idUser,
        'g'  => $idGuia,
        'e'  => $idEjercicio,
        'no' => mb_substr($file['name'], 0, 255),
        'ng' => $rel,
        'tm' => $mime,
        't'  => $file['size'],
    ]);
    return (int) db()->lastInsertId();
}

/* ── Perfil ── */

function marco_css_class(?string $marco): string
{
    return match ($marco) {
        'dorado'   => 'marco-dorado',
        'plateado' => 'marco-plateado',
        'arcoiris' => 'marco-arcoiris',
        'fuego'    => 'marco-fuego',
        'estrella' => 'marco-estrella',
        default    => '',
    };
}

function marco_label(?string $marco): string
{
    return match ($marco) {
        'dorado'   => 'Dorado',
        'plateado' => 'Plateado',
        'arcoiris' => 'Arcoíris',
        'fuego'    => 'Fuego',
        'estrella' => 'Estrella',
        default    => 'Ninguno',
    };
}

function tema_colors(string $tema): array
{
    return match ($tema) {
        'oscuro'  => ['bg' => '#1a1a2e', 'text' => '#e0e0e0', 'card' => '#16213e', 'primary' => '#e94560'],
        'verde'   => ['bg' => '#e8f5e9', 'text' => '#1b5e20', 'card' => '#ffffff', 'primary' => '#2e7d32'],
        'rosa'    => ['bg' => '#fce4ec', 'text' => '#880e4f', 'card' => '#ffffff', 'primary' => '#e91e63'],
        'purpura' => ['bg' => '#ede7f6', 'text' => '#4a148c', 'card' => '#ffffff', 'primary' => '#7b1fa2'],
        default   => ['bg' => '#f5f8ff', 'text' => '#2a2d5b', 'card' => '#ffffff', 'primary' => '#5b8def'],
    };
}

function user_avatar_html(array $user, int $size = 40): string
{
    $cls = marco_css_class($user['marco_perfil'] ?? null);
    $sizeStyle = 'width:' . $size . 'px;height:' . $size . 'px;';
    if (!empty($user['foto_perfil'])) {
        return '<img src="' . e($user['foto_perfil']) . '" alt="' . e($user['nombre']) . '" class="avatar-frame ' . $cls . '" style="' . $sizeStyle . '">';
    }
    $initials = mb_strtoupper(mb_substr($user['nombre'], 0, 1));
    return '<div class="avatar-frame ' . $cls . '" style="' . $sizeStyle . 'background:linear-gradient(135deg,var(--edu-primary),var(--edu-accent));display:grid;place-items:center;color:#fff;font-weight:800;font-size:' . ($size * 0.4) . 'px;">' . e($initials) . '</div>';
}

/* ── Mensajes ── */

function unread_messages_count(int $userId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM mensajes WHERE id_receptor = :id AND leido = 0');
    $stmt->execute(['id' => $userId]);
    return (int) $stmt->fetchColumn();
}

/* ── Matriculas ── */

function user_materias(int $userId): array
{
    $stmt = db()->prepare('SELECT materia FROM matriculas WHERE id_usuario = :id');
    $stmt->execute(['id' => $userId]);
    return array_column($stmt->fetchAll(), 'materia');
}

function is_enrolled(int $userId, string $materia): bool
{
    $stmt = db()->prepare('SELECT 1 FROM matriculas WHERE id_usuario = :id AND materia = :m LIMIT 1');
    $stmt->execute(['id' => $userId, 'm' => $materia]);
    return (bool) $stmt->fetch();
}
