<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect(home_for_role());
}

$token = (string) ($_GET['token'] ?? '');
if ($token === '') {
    flash('error', 'Token no válido.');
    redirect('login.php');
}

$stmt = db()->prepare(
    'SELECT id_usuario, nombre, token_expires FROM usuarios WHERE remember_token = :t AND activo = 1 LIMIT 1'
);
$stmt->execute(['t' => $token]);
$user = $stmt->fetch();

if (!$user) {
    flash('error', 'Enlace no válido o ya fue usado.');
    redirect('login.php');
}
if (strtotime((string) $user['token_expires']) < time()) {
    flash('error', 'El enlace expiró. Solicita uno nuevo.');
    redirect('recuperar.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $pass = (string) ($_POST['password'] ?? '');
    $pass2 = (string) ($_POST['password2'] ?? '');

    if (strlen($pass) < 8) {
        flash('error', 'La contraseña debe tener al menos 8 caracteres.');
        redirect('nueva-clave.php?token=' . $token);
    }
    if ($pass !== $pass2) {
        flash('error', 'Las contraseñas no coinciden.');
        redirect('nueva-clave.php?token=' . $token);
    }

    $upd = db()->prepare(
        'UPDATE usuarios SET contrasena_hash = :h, remember_token = NULL, token_expires = NULL WHERE id_usuario = :id'
    );
    $upd->execute(['h' => password_hash($pass, PASSWORD_DEFAULT), 'id' => $user['id_usuario']]);
    flash('ok', 'Contraseña actualizada. Ya puedes iniciar sesión.');
    redirect('login.php');
}

$pageTitle = 'Nueva contraseña';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-5" style="max-width:440px">
  <h1 class="h3 fw-bold text-center">Nueva contraseña</h1>
  <p class="text-center text-muted">Hola <?= e($user['nombre']) ?>. Ingresa tu nueva contraseña.</p>
  <div class="card-edu p-4">
    <?php render_alerts(); ?>
    <form method="post">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Nueva contraseña <small class="text-muted">(mín. 8 caracteres)</small></label>
        <div class="input-group">
          <input class="form-control" type="password" name="password" id="pass1" required minlength="8">
          <button class="btn btn-outline-secondary" type="button" onclick="togglePass('pass1',this)"><i class="bi bi-eye"></i></button>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Confirmar contraseña</label>
        <div class="input-group">
          <input class="form-control" type="password" name="password2" id="pass2" required minlength="8">
          <button class="btn btn-outline-secondary" type="button" onclick="togglePass('pass2',this)"><i class="bi bi-eye"></i></button>
        </div>
      </div>
      <button class="btn btn-edu w-100" type="submit">Guardar contraseña</button>
    </form>
  </div>
</div>
<script>
function togglePass(id, btn) {
  const input = document.getElementById(id);
  const icon = btn.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    icon.className = 'bi bi-eye-slash';
  } else {
    input.type = 'password';
    icon.className = 'bi bi-eye';
  }
}
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
