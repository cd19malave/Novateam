<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function current_tab(): ?string
{
    $t = $_SESSION['active_tab'] ?? null;
    if (is_string($t) && preg_match('/^[A-Za-z0-9_-]{16,64}$/', $t)) {
        return $t;
    }
    return null;
}

function redirect(string $path): never
{
    $tab = current_tab();
    if ($tab !== null) {
        $frag = '';
        $base = $path;
        if (str_contains($base, '#')) {
            [$base, $frag] = explode('#', $base, 2);
        }
        if (str_contains($base, '://')) {
            $parts = parse_url($base);
            $q = ($parts['query'] ?? '');
            $q = ($q !== '' ? $q . '&' : '') . 'tab=' . urlencode($tab);
            $base = ($parts['scheme'] ?? '') . '://' . ($parts['host'] ?? '') . ($parts['path'] ?? '') . '?' . $q;
        } else {
            $base .= (str_contains($base, '?') ? '&' : '?') . 'tab=' . urlencode($tab);
        }
        if ($frag !== '') {
            $base .= '#' . $frag;
        }
        $path = $base;
    }
    header('Location: ' . $path);
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    $tab = current_tab();
    $scope = $tab !== null ? 'tab_flash' : 'flash';
    $bucket = $tab !== null ? ($_SESSION[$scope][$tab] ?? []) : ($_SESSION[$scope] ?? []);
    if ($message !== null) {
        $bucket[$key] = $message;
        if ($tab !== null) {
            $_SESSION[$scope][$tab] = $bucket;
        } else {
            $_SESSION[$scope] = $bucket;
        }
        return null;
    }
    $stored = $bucket[$key] ?? null;
    unset($bucket[$key]);
    if ($tab !== null) {
        $_SESSION[$scope][$tab] = $bucket;
    } else {
        $_SESSION[$scope] = $bucket;
    }
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
    $sizeStyle = 'width:' . $size . 'px;height:' . $size . 'px;';
    $initials = mb_strtoupper(mb_substr($user['nombre'], 0, 1));
    if (!empty($user['foto_perfil'])) {
        $fallback = '<div style="width:' . $size . 'px;height:' . $size . 'px;border-radius:50%;background:linear-gradient(135deg,var(--edu-primary),var(--edu-accent));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:' . ($size * 0.4) . 'px;">' . e($initials) . '</div>';
        $onerror = 'this.outerHTML=' . json_encode($fallback);
        return '<img src="' . e($user['foto_perfil']) . '" alt="' . e($user['nombre']) . '" class="avatar-frame" style="' . $sizeStyle . 'object-fit:cover;border-radius:50%;" onerror="' . e($onerror) . '">';
    }
    return '<div class="avatar-frame" style="' . $sizeStyle . 'background:linear-gradient(135deg,var(--edu-primary),var(--edu-accent));display:grid;place-items:center;color:#fff;font-weight:800;font-size:' . ($size * 0.4) . 'px;">' . e($initials) . '</div>';
}

/* ── Mensajes ── */

function unread_messages_count(int $userId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM mensajes WHERE id_receptor = :id AND leido = 0');
    $stmt->execute(['id' => $userId]);
    return (int) $stmt->fetchColumn();
}

/* ── Notificaciones ── */

function unread_notifications_count(int $userId): int
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM notificaciones WHERE id_destinatario = :id AND leida = 0'
    );
    $stmt->execute(['id' => $userId]);
    return (int) $stmt->fetchColumn();
}

/* ── Progreso / gamificación ── */

function user_level(int $points): int
{
    return (int) floor($points / 100) + 1;
}

function streak_days(int $userId): array
{
    $days = [];
    try {
        $stmt = db()->prepare(
            'SELECT DISTINCT DATE(r.fecha_respuesta) AS d
             FROM respuestas r
             JOIN intentos i ON i.id_intento = r.id_intento
             WHERE i.id_usuario = :u'
        );
        $stmt->execute(['u' => $userId]);
        $days = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $stmt = db()->prepare('SELECT fecha FROM racha_escudos WHERE id_usuario = :u');
        $stmt->execute(['u' => $userId]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $d) {
            $days[] = $d;
        }
    } catch (Throwable $e) {
        return [];
    }
    return array_values(array_unique(array_filter($days)));
}

function user_streak(int $userId): int
{
    $days = streak_days($userId);
    if (!$days) {
        return 0;
    }
    $set = array_flip($days);
    $cursor = new DateTimeImmutable('today');
    if (!isset($set[$cursor->format('Y-m-d')])) {
        $cursor = $cursor->modify('-1 day');
        if (!isset($set[$cursor->format('Y-m-d')])) {
            return 0;
        }
    }
    $streak = 0;
    while (isset($set[$cursor->format('Y-m-d')])) {
        $streak++;
        $cursor = $cursor->modify('-1 day');
    }
    return $streak;
}

function notify_guide_published(array $guia, int $profesorId): void
{
    $pdo = db();
    $sel = $pdo->prepare(
        'SELECT DISTINCT u.id_usuario
         FROM matriculas m
         JOIN usuarios u ON u.id_usuario = m.id_usuario
         WHERE m.materia = :m AND u.rol = :r AND u.activo = 1'
    );
    $sel->execute(['m' => $guia['categoria'], 'r' => 'estudiante']);
    $ids = array_map('intval', $sel->fetchAll(PDO::FETCH_COLUMN));
    if (!$ids) {
        return;
    }

    $ins = $pdo->prepare(
        'INSERT INTO notificaciones (titulo, mensaje, id_profesor, id_guia, id_destinatario)
         VALUES (:t, :m, :p, :g, :d)'
    );
    $titulo = mb_substr('Nueva guía: ' . $guia['titulo'], 0, 150);
    $mensaje = 'Tu profesor publicó la guía «' . $guia['titulo'] . '» de '
        . categoria_label($guia['categoria']) . '. Ya puedes resolverla.';
    foreach ($ids as $dest) {
        $ins->execute([
            't' => $titulo,
            'm' => $mensaje,
            'p' => $profesorId,
            'g' => (int) $guia['id_guia'],
            'd' => $dest,
        ]);
    }
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

/* ── Vidas, tienda y potenciadores ── */

function lives_regen_hours(): int
{
    return 4;
}

function user_lives(int $userId): array
{
    $stmt = db()->prepare('SELECT vidas, vidas_max, vidas_actualizadas FROM usuarios WHERE id_usuario = :id');
    $stmt->execute(['id' => $userId]);
    $row = $stmt->fetch();
    if (!$row) {
        return ['vidas' => 0, 'max' => 0, 'next' => null, 'segundos' => 0];
    }
    $vidas = (int) $row['vidas'];
    $max   = max(1, (int) $row['vidas_max']);
    $stamp = $row['vidas_actualizadas'];

    if ($vidas >= $max) {
        if ($stamp !== null) {
            db()->prepare('UPDATE usuarios SET vidas_actualizadas = NULL WHERE id_usuario = :id')->execute(['id' => $userId]);
        }
        return ['vidas' => $max, 'max' => $max, 'next' => null, 'segundos' => 0];
    }

    $regen = lives_regen_hours();
    if ($stamp === null) {
        $stamp = date('Y-m-d H:i:s');
        db()->prepare('UPDATE usuarios SET vidas_actualizadas = :t WHERE id_usuario = :id')
            ->execute(['t' => $stamp, 'id' => $userId]);
    }
    $base = new DateTimeImmutable($stamp);
    $now  = new DateTimeImmutable();
    $gain = intdiv(max(0, $now->getTimestamp() - $base->getTimestamp()), $regen * 3600);
    if ($gain > 0) {
        $nuevas = min($max, $vidas + $gain);
        if ($nuevas >= $max) {
            db()->prepare('UPDATE usuarios SET vidas = :v, vidas_actualizadas = NULL WHERE id_usuario = :id')
                ->execute(['v' => $max, 'id' => $userId]);
            return ['vidas' => $max, 'max' => $max, 'next' => null, 'segundos' => 0];
        }
        $base = $base->modify('+' . ($gain * $regen) . ' hours');
        db()->prepare('UPDATE usuarios SET vidas = :v, vidas_actualizadas = :t WHERE id_usuario = :id')
            ->execute(['v' => $nuevas, 't' => $base->format('Y-m-d H:i:s'), 'id' => $userId]);
        $vidas = $nuevas;
    }
    $next = $base->modify('+' . $regen . ' hours');
    return [
        'vidas'    => $vidas,
        'max'      => $max,
        'next'     => $next->format('Y-m-d H:i:s'),
        'segundos' => max(0, $next->getTimestamp() - $now->getTimestamp()),
    ];
}

function consume_lives(int $userId, int $n): void
{
    if ($n <= 0) {
        return;
    }
    $cur = user_lives($userId);
    $vidas = max(0, $cur['vidas'] - $n);
    if ($cur['vidas'] >= $cur['max']) {
        db()->prepare('UPDATE usuarios SET vidas = :v, vidas_actualizadas = :t WHERE id_usuario = :id')
            ->execute(['v' => $vidas, 't' => date('Y-m-d H:i:s'), 'id' => $userId]);
    } else {
        db()->prepare('UPDATE usuarios SET vidas = :v WHERE id_usuario = :id')
            ->execute(['v' => $vidas, 'id' => $userId]);
    }
}

function shop_items(): array
{
    return [
        'vida_extra' => [
            'nombre' => 'Vida extra', 'icono' => 'heart-fill', 'precio' => 15,
            'desc' => 'Recupera 1 vida al instante (hasta tu máximo).', 'color' => '#FF5C8A',
        ],
        'vidas_full' => [
            'nombre' => 'Recarga total', 'icono' => 'heart', 'precio' => 40,
            'desc' => 'Llena todas tus vidas de una vez.', 'color' => '#FF7A45',
        ],
        'vidas_max' => [
            'nombre' => '+1 vida máxima', 'icono' => 'plus-circle', 'precio' => 80,
            'desc' => 'Amplía tu máximo de vidas en 1 (permanente).', 'color' => '#8B5CF6',
        ],
        'escudo_racha' => [
            'nombre' => 'Escudo de racha', 'icono' => 'shield-fill-check', 'precio' => 30,
            'desc' => 'Protege tu racha si un día no juegas. Acumula hasta 3.', 'color' => '#4F8FF7',
        ],
        'comodin_50' => [
            'nombre' => 'Comodín 50/50', 'icono' => 'patch-question', 'precio' => 25,
            'desc' => 'En el quiz elimina 2 respuestas incorrectas.', 'color' => '#FFB84D',
        ],
        'doble_puntos' => [
            'nombre' => 'Doble puntos', 'icono' => 'lightning-charge-fill', 'precio' => 45,
            'desc' => 'Duplica los puntos del próximo quiz que completes.', 'color' => '#56D39F',
        ],
    ];
}

function user_powerups(int $userId): array
{
    $stmt = db()->prepare('SELECT escudos_racha, comodines_50, doble_puntos, vidas_max FROM usuarios WHERE id_usuario = :id');
    $stmt->execute(['id' => $userId]);
    $row = $stmt->fetch() ?: [];
    return [
        'escudos'   => (int) ($row['escudos_racha'] ?? 0),
        'comodines' => (int) ($row['comodines_50'] ?? 0),
        'doble'     => (int) ($row['doble_puntos'] ?? 0),
        'vidas_max' => (int) ($row['vidas_max'] ?? 5),
    ];
}

function buy_item(int $userId, string $code): array
{
    $items = shop_items();
    if (!isset($items[$code])) {
        return ['ok' => false, 'msg' => 'Artículo no válido.'];
    }
    $item = $items[$code];
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'SELECT puntos, vidas, vidas_max, escudos_racha, comodines_50, doble_puntos
             FROM usuarios WHERE id_usuario = :id FOR UPDATE'
        );
        $stmt->execute(['id' => $userId]);
        $u = $stmt->fetch();
        if (!$u) {
            throw new RuntimeException('Usuario no encontrado.');
        }
        if ((int) $u['puntos'] < (int) $item['precio']) {
            throw new RuntimeException('Te faltan puntos: tienes ' . (int) $u['puntos'] . ' y cuesta ' . $item['precio'] . '.');
        }
        $lleno = (int) $u['vidas'] >= (int) $u['vidas_max'];
        switch ($code) {
            case 'vida_extra':
                if ($lleno) { throw new RuntimeException('Ya tienes todas tus vidas llenas.'); }
                break;
            case 'vidas_full':
                if ($lleno) { throw new RuntimeException('Ya tienes todas tus vidas llenas.'); }
                break;
            case 'vidas_max':
                if ((int) $u['vidas_max'] >= 9) { throw new RuntimeException('Ya alcanzaste el máximo de vidas.'); }
                break;
            case 'escudo_racha':
                if ((int) $u['escudos_racha'] >= 3) { throw new RuntimeException('Ya tienes 3 escudos de racha.'); }
                break;
            case 'comodin_50':
                if ((int) $u['comodines_50'] >= 3) { throw new RuntimeException('Ya tienes 3 comodines.'); }
                break;
            case 'doble_puntos':
                if ((int) $u['doble_puntos'] >= 2) { throw new RuntimeException('Ya tienes 2 dobles puntos.'); }
                break;
        }

        $pdo->prepare('UPDATE usuarios SET puntos = puntos - :p WHERE id_usuario = :id')
            ->execute(['p' => $item['precio'], 'id' => $userId]);

        switch ($code) {
            case 'vida_extra':
                $nv = min((int) $u['vidas_max'], (int) $u['vidas'] + 1);
                if ($nv >= (int) $u['vidas_max']) {
                    $pdo->prepare('UPDATE usuarios SET vidas = :v, vidas_actualizadas = NULL WHERE id_usuario = :id')
                        ->execute(['v' => $nv, 'id' => $userId]);
                } else {
                    $pdo->prepare('UPDATE usuarios SET vidas = :v WHERE id_usuario = :id')
                        ->execute(['v' => $nv, 'id' => $userId]);
                }
                break;
            case 'vidas_full':
                $pdo->prepare('UPDATE usuarios SET vidas = vidas_max, vidas_actualizadas = NULL WHERE id_usuario = :id')
                    ->execute(['id' => $userId]);
                break;
            case 'vidas_max':
                $pdo->prepare('UPDATE usuarios SET vidas_max = vidas_max + 1, vidas = vidas + 1, vidas_actualizadas = NULL WHERE id_usuario = :id')
                    ->execute(['id' => $userId]);
                break;
            case 'escudo_racha':
                $pdo->prepare('UPDATE usuarios SET escudos_racha = escudos_racha + 1 WHERE id_usuario = :id')
                    ->execute(['id' => $userId]);
                break;
            case 'comodin_50':
                $pdo->prepare('UPDATE usuarios SET comodines_50 = comodines_50 + 1 WHERE id_usuario = :id')
                    ->execute(['id' => $userId]);
                break;
            case 'doble_puntos':
                $pdo->prepare('UPDATE usuarios SET doble_puntos = doble_puntos + 1 WHERE id_usuario = :id')
                    ->execute(['id' => $userId]);
                break;
        }

        $pdo->prepare('INSERT INTO compras (id_usuario, item, costo) VALUES (:u, :i, :c)')
            ->execute(['u' => $userId, 'i' => $code, 'c' => $item['precio']]);
        $pdo->commit();
        return ['ok' => true, 'msg' => '¡Compraste «' . $item['nombre'] . '»!'];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'msg' => $e->getMessage()];
    }
}

function apply_streak_shields(int $userId): void
{
    try {
        $stmt = db()->prepare(
            'SELECT DISTINCT DATE(r.fecha_respuesta) AS d
             FROM respuestas r
             JOIN intentos i ON i.id_intento = r.id_intento
             WHERE i.id_usuario = :u'
        );
        $stmt->execute(['u' => $userId]);
        $actRaw = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (!$actRaw) {
            return;
        }
        $stmt = db()->prepare('SELECT escudos_racha FROM usuarios WHERE id_usuario = :u');
        $stmt->execute(['u' => $userId]);
        $escudos = (int) $stmt->fetchColumn();
        if ($escudos <= 0) {
            return;
        }
        $stmt = db()->prepare('SELECT fecha FROM racha_escudos WHERE id_usuario = :u');
        $stmt->execute(['u' => $userId]);
        $bridged = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));
        $last = new DateTimeImmutable(max($actRaw));
        $act = array_flip($actRaw);

        $today = new DateTimeImmutable('today');
        $cursor = $today->modify('-1 day');
        $insert = [];
        while ($cursor > $last && $escudos > 0) {
            $d = $cursor->format('Y-m-d');
            if (!isset($act[$d]) && !isset($bridged[$d])) {
                $insert[] = $d;
                $escudos--;
            }
            $cursor = $cursor->modify('-1 day');
        }
        if ($insert) {
            $ins = db()->prepare('INSERT IGNORE INTO racha_escudos (id_usuario, fecha) VALUES (:u, :f)');
            foreach ($insert as $f) {
                $ins->execute(['u' => $userId, 'f' => $f]);
            }
            db()->prepare('UPDATE usuarios SET escudos_racha = :e WHERE id_usuario = :u')
                ->execute(['e' => $escudos, 'u' => $userId]);
        }
    } catch (Throwable $e) {
        // silencioso: la racha no debe romper la página
    }
}
