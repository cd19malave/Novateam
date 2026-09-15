<?php
declare(strict_types=1);

const LOGIN_WINDOW_MINUTES = 15;
const LOGIN_MAX_ATTEMPTS = 8;

function public_user(array $row): array
{
    unset($row['contrasena_hash']);
    return $row;
}

function refresh_user(int $id): void
{
    $stmt = db()->prepare(
        'SELECT id_usuario, nombre, correo, rol, grado, materia, puntos, ejercicios_resueltos, activo,
                foto_perfil, marco_perfil, bio, fondo_perfil, tema_color
         FROM usuarios WHERE id_usuario = :id LIMIT 1'
    );
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    if (!$row || !(int) $row['activo']) {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        session_start();
        flash('error', 'Tu cuenta no está activa. Habla con un administrador.');
        redirect('login.php');
    }
    $_SESSION['user'] = $row;
    $tab = current_tab();
    if ($tab !== null) {
        $_SESSION['tabs'][$tab]['user'] = $row;
    }
}

function require_login(): void
{
    if (!current_user()) {
        flash('error', 'Inicia sesión para continuar.');
        redirect('login.php');
    }
}

function require_role(string ...$roles): void
{
    require_login();
    if (!is_role(...$roles)) {
        flash('error', 'No tienes permiso para entrar a esa sección.');
        redirect(home_for_role());
    }
}

function login_blocked(string $correo, string $ip): bool
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM intentos_acceso
         WHERE exitoso = 0
           AND fecha > DATE_SUB(NOW(), INTERVAL :mins MINUTE)
           AND (correo = :correo OR ip = :ip)'
    );
    $stmt->execute([
        'mins' => LOGIN_WINDOW_MINUTES,
        'correo' => $correo,
        'ip' => $ip,
    ]);
    return (int) $stmt->fetchColumn() >= LOGIN_MAX_ATTEMPTS;
}

function log_login_attempt(string $correo, string $ip, bool $ok, ?int $userId = null): void
{
    $stmt = db()->prepare(
        'INSERT INTO intentos_acceso (correo, ip, exitoso, id_usuario) VALUES (:c, :ip, :ok, :uid)'
    );
    $stmt->execute(['c' => $correo, 'ip' => $ip, 'ok' => $ok ? 1 : 0, 'uid' => $userId]);
}

function attempt_login(string $correo, string $password): bool
{
    $ip = client_ip();
    if (login_blocked($correo, $ip)) {
        flash('error', 'Demasiados intentos fallidos. Espera 15 minutos e inténtalo de nuevo.');
        return false;
    }

    $stmt = db()->prepare(
        'SELECT * FROM usuarios WHERE correo = :correo LIMIT 1'
    );
    $stmt->execute(['correo' => $correo]);
    $user = $stmt->fetch();

    $valid = $user
        && (int) $user['activo'] === 1
        && password_verify($password, $user['contrasena_hash']);

    log_login_attempt($correo, $ip, $valid, $valid ? (int) $user['id_usuario'] : null);

    if (!$valid) {
        flash('error', 'Correo o contraseña incorrectos.');
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user'] = public_user($user);
    $tab = current_tab();
    if ($tab !== null) {
        $_SESSION['tabs'][$tab]['user'] = $_SESSION['user'];
    }

    $upd = db()->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = :id');
    $upd->execute(['id' => $user['id_usuario']]);
    return true;
}

function register_student(string $nombre, string $correo, string $password, int $grado, array $materias = []): bool
{
    $stmt = db()->prepare('SELECT 1 FROM usuarios WHERE correo = :correo LIMIT 1');
    $stmt->execute(['correo' => $correo]);
    if ($stmt->fetch()) {
        flash('error', 'Ese correo ya está registrado.');
        return false;
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $ins = $pdo->prepare(
            'INSERT INTO usuarios (nombre, correo, contrasena_hash, rol, grado)
             VALUES (:n, :c, :h, :r, :g)'
        );
        $ins->execute([
            'n' => $nombre,
            'c' => $correo,
            'h' => password_hash($password, PASSWORD_DEFAULT),
            'r' => 'estudiante',
            'g' => $grado,
        ]);
        $idNew = (int) $pdo->lastInsertId();

        if (!empty($materias)) {
            $insMat = $pdo->prepare('INSERT INTO matriculas (id_usuario, materia) VALUES (:uid, :mat)');
            foreach ($materias as $mat) {
                if (in_array($mat, ['matematicas', 'ingles'], true)) {
                    $insMat->execute(['uid' => $idNew, 'mat' => $mat]);
                }
            }
        }

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        flash('error', 'Error al registrar.');
        return false;
    }
}
