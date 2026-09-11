<?php
// Instalador web de EduNova - BORRA ESTE ARCHIVO después de usarlo
// Accede a: TU_URL/instalar.php?token=novateam2026
declare(strict_types=1);

if (($_GET['token'] ?? '') !== 'novateam2026') {
    exit('Acceso denegado.');
}

require __DIR__ . '/config/config.php';

$url = (string) env('DATABASE_URL', '');
if ($url !== '') {
    $p = parse_url($url);
    $host = $p['host'] ?? '127.0.0.1';
    $port = (string) ($p['port'] ?? '3306');
    $user = urldecode((string) ($p['user'] ?? 'root'));
    $pass = urldecode((string) ($p['pass'] ?? ''));
    $name = ltrim((string) ($p['path'] ?? '/novateam_db'), '/');
} else {
    $host = (string) env('DB_HOST', '127.0.0.1');
    $port = (string) env('DB_PORT', '3306');
    $user = (string) env('DB_USER', 'root');
    $pass = (string) env('DB_PASS', '');
    $name = (string) env('DB_NAME', 'novateam_db');
}

header('Content-Type: text/plain; charset=utf-8');
echo "=== Instalador EduNova ===\n\n";
echo "Servidor: $host:$port\n";
echo "Base: $name\n";
echo "Usuario: $user\n\n";

try {
    if ($url !== '') {
        // Conectar sin seleccionar BD y crearla si no existe
        $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$name}`");
    } else {
        $pdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }
    echo "Conexión OK ✓\n";
} catch (Throwable $e) {
    echo "ERROR de conexión: {$e->getMessage()}\n";
    echo "\n1. Revisa tus credenciales en el panel de tu hosting\n";
    echo "2. Crea la base de datos con el nombre correcto\n";
    echo "3. Edita el archivo .env con los datos\n";
    exit(1);
}

$sqlFile = __DIR__ . '/database/novateam.sql';
if (!is_file($sqlFile)) {
    echo "ERROR: No se encontró database/novateam.sql\n";
    exit(1);
}

$sql = file_get_contents($sqlFile);
// El SQL usa CREATE DATABASE novateam_db y USE - los quitamos (ya usamos la BD activa)
$sql = preg_replace('/(CREATE DATABASE IF NOT EXISTS novateam_db.*?USE novateam_db;\s*)/s', '', $sql);
$sql = preg_replace('/(CREATE DATABASE novateam_db.*?USE novateam_db;\s*)/s', '', $sql);
// Quitar líneas de comentario antes de dividir por ';'
$sql = preg_replace('/^--.*$/m', '', $sql);
$sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

try {
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0;');
    foreach (explode(';', $sql) as $stmt) {
        $stmt = trim($stmt);
        if ($stmt !== '') {
            $pdo->exec($stmt);
        }
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1;');
    echo "Base de datos creada/actualizada ✓\n\n";
} catch (Throwable $e) {
    echo "ERROR ejecutando SQL: {$e->getMessage()}\n";
    exit(1);
}

echo "Tablas: ";
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo implode(', ', $tables) . "\n\n";
echo "=== INSTALACIÓN COMPLETADA ===\n";
echo "Usuarios de prueba (contraseña: EduNova2026!)\n";
echo "  admin@novateam.edu.co       (administrador)\n";
echo "  jenniffer.yepes@colegio.edu.co (profesor)\n";
echo "  ana.torres@colegio.edu.co   (estudiante)\n";
echo "\n⚠ IMPORTANTE: BORRA este archivo (instalar.php) ahora.\n";