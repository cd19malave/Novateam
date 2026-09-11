<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect(home_for_role());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $correo = strtolower(trim((string) ($_POST['correo'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || $password === '') {
        flash('error', 'Ingresa un correo y una contraseña válidos.');
        redirect('login.php');
    }
    if (attempt_login($correo, $password)) {
        redirect(home_for_role());
    }
    redirect('login.php');
}

$pageTitle = 'Iniciar sesión';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-5" style="max-width:480px">
  <div class="card-edu p-4">
    <h1 class="h3 fw-bold">Entrar a EduNova</h1>
    <p class="text-muted">Usa tu correo institucional.</p>
    <?php render_alerts(); ?>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label" for="correo">Correo</label>
        <input class="form-control" type="email" id="correo" name="correo" required maxlength="150" autocomplete="username">
      </div>
      <div class="mb-3">
        <label class="form-label" for="password">Contraseña</label>
        <div class="input-group">
          <input class="form-control" type="password" id="password" name="password" required maxlength="72" autocomplete="current-password">
          <button class="btn btn-outline-secondary" type="button" onclick="togglePass('password',this)"><i class="bi bi-eye"></i></button>
        </div>
      </div>
      <button class="btn btn-edu w-100" type="submit">Entrar</button>
    </form>
    <p class="mt-3 mb-0 small"><a href="recuperar.php" class="text-primary fw-bold">¿Olvidaste tu contraseña?</a></p>
    <p class="mt-2 mb-0 small text-muted">¿Aún no tienes cuenta de estudiante? <a href="registro.php">Regístrate</a></p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
