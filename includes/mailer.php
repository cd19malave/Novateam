<?php
declare(strict_types=1);

/**
 * Envío de correo por SMTP directo (sin PHPMailer, sin mail()).
 * Lee las variables: SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS,
 * SMTP_FROM, SMTP_FROM_NAME.
 * Devuelve true si el mensaje fue aceptado por el servidor.
 */
function send_email(string $to, string $subject, string $bodyHtml): bool
{
    $host     = env('SMTP_HOST', 'smtp.gmail.com');
    $port     = (int) (env('SMTP_PORT', '587'));
    $user     = env('SMTP_USER', '');
    $pass     = env('SMTP_PASS', '');
    $from     = env('SMTP_FROM', $user !== '' ? $user : 'no-responder@novateam.local');
    $fromName = env('SMTP_FROM_NAME', 'NovaTeam');

    if ($user === '' || $pass === '') {
        error_log('NovaTeam: SMTP sin configurar (SMTP_USER / SMTP_PASS)');
        return false;
    }

    $errno = 0;
    $errstr = '';
    // Si el host ya viene con ssl:// o tls:// se usa directo; si no, TCP plano.
    $target = (str_starts_with($host, 'ssl://') || str_starts_with($host, 'tls://'))
        ? $host
        : 'tcp://' . $host . ':' . $port;

    $sock = @stream_socket_client($target, $errno, $errstr, 15);
    if (!$sock) {
        error_log("NovaTeam: SMTP conexión fallida ($errno) $errstr");
        return false;
    }
    stream_set_timeout($sock, 15);

    $readReply = function () use ($sock): string {
        $line = '';
        while (!feof($sock)) {
            $chunk = fgets($sock, 1024);
            if ($chunk === false) {
                break;
            }
            $line .= $chunk;
            // La respuesta termina cuando el 4º carácter es un espacio.
            if (strlen($chunk) >= 4 && $chunk[3] === ' ') {
                break;
            }
        }
        return trim($line);
    };

    $cmd = function (string $command) use ($sock, $readReply): string {
        fwrite($sock, $command . "\r\n");
        return $readReply();
    };

    $banner = $readReply();
    if (preg_match('/^2\d\d/', $banner) !== 1) {
        error_log('NovaTeam: SMTP saludo rechazado: ' . $banner);
        fclose($sock);
        return false;
    }

    fwrite($sock, "EHLO novateam\r\n");
    $ehlo = $readReply();

    // STARTTLS en puertos 587/25 (tras un segundo EHLO para anunciar capacidades)
    if (!$port || $port === 587 || $port === 25) {
        if (str_contains($ehlo, 'STARTTLS') || true) {
            fwrite($sock, "STARTTLS\r\n");
            if (preg_match('/^2\d\d/', $readReply()) === 1) {
                $tls = @stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if (!$tls) {
                    error_log('NovaTeam: SMTP handshake TLS falló');
                    fclose($sock);
                    return false;
                }
                fwrite($sock, "EHLO novateam\r\n");
                $readReply();
            }
        }
    }

    fwrite($sock, "AUTH LOGIN\r\n");
    $auth = $readReply();
    if (preg_match('/^3\d\d/', $auth) !== 1) {
        error_log('NovaTeam: SMTP AUTH LOGIN no soportado: ' . $auth);
        fclose($sock);
        return false;
    }

    fwrite($sock, base64_encode($user) . "\r\n");
    $userResp = $readReply();
    if (preg_match('/^3\d\d/', $userResp) !== 1) {
        error_log('NovaTeam: SMTP usuario rechazado: ' . $userResp);
        fclose($sock);
        return false;
    }

    fwrite($sock, base64_encode($pass) . "\r\n");
    $passResp = $readReply();
    if (preg_match('/^2\d\d/', $passResp) !== 1) {
        error_log('NovaTeam: SMTP contraseña rechazada: ' . $passResp);
        fclose($sock);
        return false;
    }

    $mailFrom = $cmd('MAIL FROM:<' . $from . '>');
    if (preg_match('/^2\d\d/', $mailFrom) !== 1) {
        error_log('NovaTeam: SMTP MAIL FROM falló: ' . $mailFrom);
        fclose($sock);
        return false;
    }

    $rcpt = $cmd('RCPT TO:<' . $to . '>');
    if (preg_match('/^2\d\d/', $rcpt) !== 1) {
        error_log('NovaTeam: SMTP RCPT TO falló: ' . $rcpt);
        fclose($sock);
        return false;
    }

    $data = $cmd('DATA');
    if (preg_match('/^3\d\d/', $data) !== 1) {
        error_log('NovaTeam: SMTP DATA rechazado: ' . $data);
        fclose($sock);
        return false;
    }

    // Cuerpo alternativo: texto plano derivado del HTML.
    $text = preg_replace('/<br\s*\/?>|<\/p>/i', "\n", $bodyHtml);
    $text = trim(strip_tags((string) $text));

    $headers  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$from}>\r\n";
    $headers .= 'To: <' . $to . ">\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Date: " . date('r') . "\r\n";
    $headers .= "Message-ID: <" . bin2hex(random_bytes(8)) . '@novateam>' . "\r\n";
    $headers .= "X-Mailer: NovaTeam\r\n";

    $message  = $headers;
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $message .= chunk_split(base64_encode($bodyHtml)) . "\r\n";
    $message .= '.' . "\r\n";

    fwrite($sock, $message);
    $final = fgets($sock, 1024);
    fclose($sock);

    $ok = is_string($final) && preg_match('/^2\d\d/', trim($final)) === 1;
    if (!$ok) {
        error_log('NovaTeam: SMTP envío final falló: ' . trim((string) $final));
    }
    return $ok;
}