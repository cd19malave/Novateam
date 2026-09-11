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

    $stmt = db()->prepare('SELECT id_usuario, nombre FROM usuarios WHERE correo = :c AND activo = 1 LIMIT 1');
    $stmt->execute(['c' => $correo]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $upd = db()->prepare('UPDATE usuarios SET remember_token = :t, token_expires = :e WHERE id_usuario = :id');
        $upd->execute(['t' => $token, 'e' => $expires, 'id' => $user['id_usuario']]);
        $resetUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
            . '://' . $_SERVER['HTTP_HOST'] . '/nueva-clave.php?token=' . $token;
        flash('ok', 'Si el correo existe, se envió un enlace de recuperación. (Prueba: <a href="' . e($resetUrl) . '" class="fw-bold">Restablecer contraseña</a>)');
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
