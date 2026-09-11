<?php
declare(strict_types=1);

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    $ok = is_string($sent) && hash_equals(csrf_token(), $sent);
    if (!$ok) {
        http_response_code(400);
        flash('error', 'La sesión expiró o el formulario no es válido. Inténtalo de nuevo.');
        $ref = (string) ($_SERVER['HTTP_REFERER'] ?? 'index.php');
        $host = parse_url($ref, PHP_URL_HOST);
        $own = $_SERVER['HTTP_HOST'] ?? '';
        if ($host !== null && $host !== '' && !hash_equals((string) $own, (string) $host)) {
            $ref = 'index.php';
        }
        redirect($ref);
    }
}
