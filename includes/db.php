<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        try {
            $pdo->query('SELECT 1');
        } catch (PDOException $e) {
            $pdo = null;
        }
        if ($pdo instanceof PDO) {
            return $pdo;
        }
    }

    $host = env('DB_HOST', '127.0.0.1') ?? '127.0.0.1';
    $name = env('DB_NAME', 'novateam_db') ?? 'novateam_db';
    $user = env('DB_USER', 'root') ?? 'root';
    $pass = env('DB_PASS', '') ?? '';
    $port = env('DB_PORT', '3306') ?? '3306';
    $charset = env('DB_CHARSET', 'utf8mb4') ?? 'utf8mb4';

    // Soporte DATABASE_URL de Railway (mysql://user:pass@host:port/dbname)
    $dbUrl = env('DATABASE_URL', '');
    if ($dbUrl !== '' && $dbUrl !== null) {
        $parts = parse_url($dbUrl);
        if (is_array($parts) && isset($parts['host'])) {
            $host = $parts['host'];
            $name = ltrim((string) ($parts['path'] ?? '/'), '/');
            $user = urldecode((string) ($parts['user'] ?? ''));
            $pass = urldecode((string) ($parts['pass'] ?? ''));
            $port = (string) ($parts['port'] ?? '3306');
        }
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        exit('No se pudo conectar a MySQL. Importa database/novateam.sql en phpMyAdmin y revisa el archivo .env');
    }
    return $pdo;
}
