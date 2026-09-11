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