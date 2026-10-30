<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect(home_for_role());
}

$errorMsg = flash('error');
$okMsg = flash('ok');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $correo = strtolower(trim((string) ($_POST['correo'] ?? '')));

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Ingresa un correo válido.');
        redirect('recuperar.php');
    }

    $stmt = db()->prepare('SELECT id_usuario, nombre, correo FROM usuarios WHERE correo = :c AND activo = 1 LIMIT 1');
    $stmt->execute(['c' => $correo]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $upd = db()->prepare('UPDATE usuarios SET remember_token = :t, token_expires = :e WHERE id_usuario = :id');
        $upd->execute(['t' => $token, 'e' => $expires, 'id' => $user['id_usuario']]);

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'novateam-production.up.railway.app';
        $resetUrl = $scheme . '://' . $host . '/nueva-clave.php?token=' . $token;

        $html = '
        <div style="font-family:\'Instrument Sans\',Arial,Helvetica,sans-serif;background:#F5EFE4;padding:24px;border-radius:16px;max-width:480px;margin:0 auto;color:#161A1D">
          <div style="text-align:center;margin-bottom:16px">
            <span style="display:inline-block;width:44px;height:44px;line-height:44px;border-radius:12px;background:#161A1D;color:#F5EFE4;font-weight:800;font-size:22px;text-align:center">N</span>
            <div style="color:#161A1D;font-family:Georgia,\'Times New Roman\',serif;font-size:22px;font-weight:700;margin-top:8px">NovaTeam</div>
          </div>
          <div style="background:#FFFDF8;border:2px solid #161A1D;border-radius:14px;padding:20px;color:#161A1D">
            <h1 style="font-family:Georgia,\'Times New Roman\',serif;font-size:18px;margin:0 0 8px;color:#161A1D">Restablecer contraseña</h1>
            <p style="font-size:14px;line-height:1.55;margin:0 0 16px">
              Hola ' . e($user['nombre']) . ', recibimos una solicitud para restablecer tu contraseña de NovaTeam.
              El enlace es válido por 1 hora.
            </p>
            <a href="' . e($resetUrl) . '" style="display:inline-block;background:#B73420;color:#FFFDF8;text-decoration:none;font-weight:700;font-size:14px;padding:12px 20px;border-radius:12px">Restablecer contraseña</a>
            <p style="font-size:12px;color:#4E5358;margin:16px 0 0">Si no solicitaste esto, ignora este correo.</p>
          </div>
        </div>';

        $sent = send_email($user['correo'], 'NovaTeam: restablecer tu contraseña', $html);

        if ($sent) {
            flash('ok', 'Te enviamos un enlace de recuperación a <strong>' . e($user['correo']) . '</strong>. Revisa tu bandeja de entrada (y la de spam).');
        } else {
            flash('error', 'No se pudo enviar el correo en este momento. Usa este enlace temporal: <a href="' . e($resetUrl) . '" class="fw-bold">Restablecer contraseña</a>');
        }
    } else {
        flash('ok', 'Si el correo existe en nuestro sistema, recibirás un enlace de recuperación.');
    }
    redirect('recuperar.php');
}

$pageTitle = 'Recuperar contraseña';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-5" style="max-width:440px">
  <h1 class="h3 fw-bold text-center">Recuperar contraseña</h1>
  <p class="text-center text-muted">Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.</p>
  <div class="card-edu p-4">
    <?php render_alerts(); ?>
    <form method="post">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label" for="correo">Correo electrónico</label>
        <input class="form-control" id="correo" name="correo" type="email" required maxlength="150" placeholder="tu@correo.com">
      </div>
      <button class="btn btn-edu w-100" type="submit">Enviar enlace</button>
    </form>
    <p class="text-center mt-3 mb-0 small"><a href="login.php" class="text-primary fw-bold">Volver al inicio de sesión</a></p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
