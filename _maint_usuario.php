<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');
if (env('MAINT_KEY', '') === '' || ($_GET['k'] ?? '') !== env('MAINT_KEY', '')) {
    http_response_code(403);
    echo 'Acceso denegado';
    exit;
}

$pdo = db();

// Modo prueba de envío: ?k=...&envio=1
if (isset($_GET['envio'])) {
    $envHost = (string) (getenv('SMTP_HOST') ?: 'no-getenv');
    $envUser = (string) (getenv('SMTP_USER') ?: 'no-getenv');
    file_put_contents('php://stdout', "DEBUG env: host=$envHost user=$envUser port=" . (string) getenv('SMTP_PORT') . "\n");

    $host = env('SMTP_HOST', '?');
    $user = env('SMTP_USER', '?');
    $pass = env('SMTP_PASS', '?');
    echo "config: host=$host user=$user pass=" . substr($pass, 0, 4) . "... len=" . strlen($pass) . "\n";

    $ok = send_email('cd19malave@gmail.com', 'NovaTeam: prueba desde produccion', '<h1>Prueba</h1><p>SMTP funcionando en Railway.</p>');
    echo "ENVIO_OK=" . ($ok ? 'true' : 'false') . "\n";

    // Segundo intento: puerto 465 con SSL implícito
    $c = @fsockopen('ssl://smtp.gmail.com', 465, $e1, $e2, 10);
    echo "SSL465=" . ($c ? 'ok' : "fail ($e1) $e2") . "\n";
    if ($c) fclose($c);
    // Puerto 587
    $c2 = @fsockopen('smtp.gmail.com', 587, $e3, $e4, 10);
    echo "PLAIN587=" . ($c2 ? 'ok' : "fail ($e3) $e4") . "\n";
    if ($c2) fclose($c2);
    $c3 = @fsockopen('www.google.com', 443, $e5, $e6, 10);
    echo "GOOGLE443=" . ($c3 ? 'ok' : "fail ($e5) $e6") . "\n";
    if ($c3) fclose($c3);
    exit;
}

// 1. Mostrar usuarios existentes con ese correo
$stmt = $pdo->prepare('SELECT id_usuario, nombre, correo, rol, activo FROM usuarios WHERE correo = :c');
$stmt->execute(['c' => 'cd19malave@gmail.com']);
$found = $stmt->fetchAll();

if ($found) {
    echo "EXISTE:\n";
    foreach ($found as $u) {
        echo implode(' | ', $u) . "\n";
    }
    exit;
}

// 2. Insertarlo (profesor de matemáticas, con la contraseña por defecto EduNova2026!)
$hash = '$2y$10$DBKPP9me3lfvh94H2b7NU.S5UXdqtIDKewEIzSgsb9v7MR5ZFpwOm';
$ins = $pdo->prepare(
    'INSERT INTO usuarios (nombre, correo, contrasena_hash, rol, materia) VALUES (:n, :c, :h, :r, :m)'
);
$ins->execute([
    'n' => 'Carlos Malave',
    'c' => 'cd19malave@gmail.com',
    'h' => $hash,
    'r' => 'profesor',
    'm' => 'matematicas',
]);
echo "INSERTADO ID=" . $pdo->lastInsertId() . "\n";

// 3. Inscripción en materia (si la tabla matriculas existe)
try {
    $id = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO matriculas (id_usuario, materia) VALUES (:u, "matematicas")')
        ->execute(['u' => $id]);
    echo "MATRICULADO\n";
} catch (Throwable $e) {
    echo "matriculas: " . $e->getMessage() . "\n";
}