<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect(home_for_role());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    $correo = strtolower(trim((string) ($_POST['correo'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $grado = (int) ($_POST['grado'] ?? 0);
    $materias = $_POST['materias'] ?? [];

    if (mb_strlen($nombre) < 3) {
        flash('error', 'El nombre debe tener al menos 3 caracteres.');
        redirect('registro.php');
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'El correo no es válido.');
        redirect('registro.php');
    }
    if (strlen($password) < 8) {
        flash('error', 'La contraseña debe tener al menos 8 caracteres.');
        redirect('registro.php');
    }
    if ($grado !== 4) {
        flash('error', 'El registro está disponible solo para 4° grado.');
        redirect('registro.php');
    }
    if (!is_array($materias) || count($materias) < 1) {
        flash('error', 'Selecciona al menos una materia.');
        redirect('registro.php');
    }

    if (register_student($nombre, $correo, $password, $grado, $materias)) {
        flash('ok', 'Cuenta creada. Ya puedes iniciar sesión.');
        redirect('login.php');
    }
    redirect('registro.php');
}

$pageTitle = 'Registro';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-5" style="max-width:520px">
  <h1 class="h3 fw-bold text-center">Crear cuenta de estudiante</h1>
  <p class="text-center text-muted">Completa tus datos para empezar a aprender.</p>
  <div class="card-edu p-4">
    <?php render_alerts(); ?>
    <form method="post">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label" for="nombre">Nombre completo</label>
        <input class="form-control" id="nombre" name="nombre" required minlength="3" maxlength="120">
      </div>
      <div class="mb-3">
        <label class="form-label" for="correo">Correo electrónico</label>
        <input class="form-control" id="correo" name="correo" type="email" required maxlength="150">
      </div>
      <div class="mb-3">
        <label class="form-label" for="password">Contraseña <small class="text-muted">(mín. 8 caracteres)</small></label>
        <div class="input-group">
          <input class="form-control" id="password" name="password" type="password" required minlength="8">
          <button class="btn btn-outline-secondary" type="button" onclick="togglePass('password',this)"><i class="bi bi-eye"></i></button>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label" for="grado">Grado</label>
        <select class="form-select" id="grado" name="grado" required>
          <option value="4" selected>4° Primaria</option>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Materias <small class="text-muted">(selecciona al menos una)</small></label>
        <div class="d-flex gap-3">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="materias[]" value="matematicas" id="mat-mat" checked>
            <label class="form-check-label" for="mat-mat">🧮 Matemáticas</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="materias[]" value="ingles" id="mat-eng" checked>
            <label class="form-check-label" for="mat-eng">🗣️ Inglés</label>
          </div>
        </div>
      </div>
      <button class="btn btn-edu w-100" type="submit">Crear cuenta</button>
    </form>
    <p class="text-center mt-3 mb-0 small">¿Ya tienes cuenta? <a href="login.php" class="text-primary fw-bold">Iniciar sesión</a></p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
