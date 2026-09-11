<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_role('administrador');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $accion = (string) ($_POST['accion'] ?? '');

    if ($accion === 'editar_usuario') {
        $id = (int) ($_POST['id_usuario'] ?? 0);
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $correo = strtolower(trim((string) ($_POST['correo'] ?? '')));
        $grado = (int) ($_POST['grado'] ?? 0);
        $materia = (string) ($_POST['materia'] ?? '');
        $nuevaPass = (string) ($_POST['nueva_password'] ?? '');

        if ($id < 1 || mb_strlen($nombre) < 3 || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Datos inválidos.');
            redirect('admin.php');
        }
        if ($grado < 1 || $grado > 5) $grado = 0;
        if (!in_array($materia, ['matematicas', 'ingles'], true)) $materia = null;

        $sets = ['nombre = :n', 'correo = :c'];
        $params = ['n' => $nombre, 'c' => $correo, 'id' => $id];
        if ($grado > 0) { $sets[] = 'grado = :g'; $params['g'] = $grado; }
        if ($materia)   { $sets[] = 'materia = :m'; $params['m'] = $materia; }
        if (strlen($nuevaPass) >= 8) {
            $sets[] = 'contrasena_hash = :h';
            $params['h'] = password_hash($nuevaPass, PASSWORD_DEFAULT);
        }
        $sql = 'UPDATE usuarios SET ' . implode(', ', $sets) . ' WHERE id_usuario = :id';
        try {
            db()->prepare($sql)->execute($params);
            flash('ok', 'Usuario actualizado.');
        } catch (PDOException $e) {
            flash('error', 'No se pudo actualizar (¿correo duplicado?).');
        }
    } elseif ($accion === 'toggle') {
        $id = (int) ($_POST['id_usuario'] ?? 0);
        if ($id === (int) current_user()['id_usuario']) {
            flash('error', 'No puedes desactivar tu propia cuenta.');
            redirect('admin.php');
        }
        $upd = db()->prepare(
            'UPDATE usuarios SET activo = IF(activo = 1, 0, 1) WHERE id_usuario = :id'
        );
        $upd->execute(['id' => $id]);
        flash('ok', 'Estado de usuario actualizado.');
    } elseif ($accion === 'crear_profesor') {
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $correo = strtolower(trim((string) ($_POST['correo'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $materia = (string) ($_POST['materia'] ?? '');
        if (mb_strlen($nombre) < 3 || !filter_var($correo, FILTER_VALIDATE_EMAIL)
            || strlen($password) < 8 || !in_array($materia, ['matematicas', 'ingles'], true)) {
            flash('error', 'Datos del profesor incompletos o inválidos.');
            redirect('admin.php');
        }
        try {
            $ins = db()->prepare(
                'INSERT INTO usuarios (nombre, correo, contrasena_hash, rol, materia)
                 VALUES (:n, :c, :h, :r, :m)'
            );
            $ins->execute([
                'n' => $nombre,
                'c' => $correo,
                'h' => password_hash($password, PASSWORD_DEFAULT),
                'r' => 'profesor',
                'm' => $materia,
            ]);
            flash('ok', 'Profesor creado.');
        } catch (PDOException $e) {
            flash('error', 'Ese correo ya existe o no se pudo crear el profesor.');
        }
    } elseif ($accion === 'responder_solicitud') {
        $idSol = (int) ($_POST['id_solicitud'] ?? 0);
        $estado = (string) ($_POST['estado'] ?? 'pendiente');
        $respuesta = trim((string) ($_POST['respuesta'] ?? ''));
        if (!in_array($estado, ['pendiente', 'revisada', 'cerrada'], true)) $estado = 'pendiente';
        $upd = db()->prepare(
            'UPDATE solicitudes_contacto SET estado = :e, respondido_por = :uid, respuesta = :r WHERE id_solicitud = :id'
        );
        $upd->execute(['e' => $estado, 'uid' => current_user()['id_usuario'], 'r' => $respuesta !== '' ? $respuesta : null, 'id' => $idSol]);
        flash('ok', 'Solicitud actualizada.');
    } elseif ($accion === 'matricular') {
        $idUsr = (int) ($_POST['id_usuario'] ?? 0);
        $mat = (string) ($_POST['materia'] ?? '');
        if ($idUsr && in_array($mat, ['matematicas', 'ingles'], true)) {
            try {
                $ins = db()->prepare('INSERT IGNORE INTO matriculas (id_usuario, materia) VALUES (:u, :m)');
                $ins->execute(['u' => $idUsr, 'm' => $mat]);
                flash('ok', 'Matrícula registrada.');
            } catch (PDOException $e) {
                flash('error', 'No se pudo matricular.');
            }
        }
    }
    redirect('admin.php');
}

$stats = [
    'estudiantes' => (int) db()->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'estudiante'")->fetchColumn(),
    'profesores' => (int) db()->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'profesor'")->fetchColumn(),
    'guias' => (int) db()->query('SELECT COUNT(*) FROM guias')->fetchColumn(),
    'intentos' => (int) db()->query('SELECT COUNT(*) FROM intentos WHERE completado = 1')->fetchColumn(),
];
$usuarios = db()->query(
    'SELECT id_usuario, nombre, correo, rol, grado, materia, puntos, activo, fecha_registro
     FROM usuarios ORDER BY fecha_registro DESC'
)->fetchAll();
$solicitudes = db()->query(
    'SELECT s.*, u.nombre AS admin_nombre FROM solicitudes_contacto s
     LEFT JOIN usuarios u ON u.id_usuario = s.respondido_por
     ORDER BY s.fecha DESC LIMIT 30'
)->fetchAll();

$pageTitle = 'Administración';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-5">
  <h1 class="h3 fw-bold">Panel de administración</h1>
  <?php render_alerts(); ?>
  <div class="row g-3 mb-4">
    <?php foreach (['estudiantes' => 'Estudiantes', 'profesores' => 'Profesores', 'guias' => 'Guías', 'intentos' => 'Intentos'] as $k => $label): ?>
      <div class="col-6 col-md-3">
        <div class="card-edu p-3 text-center">
          <h2 class="h3 fw-bold mb-0" style="color:var(--edu-primary)"><?= $stats[$k] ?></h2>
          <p class="mb-0 text-muted"><?= e($label) ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card-edu p-4 mb-4">
    <h2 class="h5 fw-bold">Crear profesor</h2>
    <form method="post" class="row g-3">
      <?= csrf_field() ?>
      <input type="hidden" name="accion" value="crear_profesor">
      <div class="col-md-3"><input class="form-control" name="nombre" placeholder="Nombre" required minlength="3" maxlength="120"></div>
      <div class="col-md-3"><input class="form-control" type="email" name="correo" placeholder="Correo" required></div>
      <div class="col-md-2">
        <select class="form-select" name="materia" required>
          <option value="matematicas">Matemáticas</option>
          <option value="ingles">Inglés</option>
        </select>
      </div>
      <div class="col-md-2"><input class="form-control" type="password" name="password" placeholder="Contraseña" required minlength="8"></div>
      <div class="col-md-2"><button class="btn btn-edu w-100" type="submit">Crear</button></div>
    </form>
  </div>

  <h2 class="h5 fw-bold">Usuarios</h2>
  <div class="table-responsive card-edu p-3 mb-5">
    <table class="table align-middle mb-0">
      <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Puntos</th><th>Estado</th><th>Acciones</th></tr></thead>
      <tbody>
        <?php foreach ($usuarios as $u): ?>
          <tr>
            <td><?= e($u['nombre']) ?></td>
            <td><?= e($u['correo']) ?></td>
            <td>
              <?= e($u['rol']) ?>
              <?php if ($u['rol'] === 'estudiante'): ?>
                <?php
                $mats = user_materias((int) $u['id_usuario']);
                foreach ($mats as $mm): ?>
                  <span class="badge-pill" style="font-size:.6rem;"><?= e(categoria_label($mm)) ?></span>
                <?php endforeach; ?>
              <?php endif; ?>
            </td>
            <td><?= (int) $u['puntos'] ?></td>
            <td><?= (int) $u['activo'] ? 'Activo' : 'Inactivo' ?></td>
            <td class="d-flex gap-1 flex-wrap">
              <button class="btn btn-sm btn-outline-primary" type="button" title="Editar"
                onclick="abrirEditar(<?= (int) $u['id_usuario'] ?>, '<?= e(addslashes($u['nombre'])) ?>', '<?= e($u['correo']) ?>', <?= (int) ($u['grado'] ?? 0) ?>, '<?= e($u['materia'] ?? '') ?>')">
                <i class="bi bi-pencil"></i>
              </button>
              <form method="post" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="toggle">
                <input type="hidden" name="id_usuario" value="<?= (int) $u['id_usuario'] ?>">
                <button class="btn btn-sm btn-outline-secondary" type="submit" title="Activar/Desactivar"><i class="bi bi-toggle-on"></i></button>
              </form>
              <?php if ($u['rol'] === 'estudiante'): ?>
                <form method="post" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="accion" value="matricular">
                  <input type="hidden" name="id_usuario" value="<?= (int) $u['id_usuario'] ?>">
                  <select name="materia" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                    <option value="">+ Matricular en...</option>
                    <option value="matematicas">Matemáticas</option>
                    <option value="ingles">Inglés</option>
                  </select>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <h2 class="h5 fw-bold">Solicitudes de instituciones</h2>
  <div class="row g-3">
    <?php foreach ($solicitudes as $s): ?>
      <div class="col-md-6">
        <div class="card-edu p-3">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <strong><?= e($s['nombre']) ?></strong>
              <span class="badge-pill ms-2" style="font-size:.65rem;"><?= e($s['estado']) ?></span>
            </div>
            <small class="text-muted"><?= e(date('d/m/Y', strtotime((string) $s['fecha']))) ?></small>
          </div>
          <div class="small text-muted"><?= e($s['correo']) ?> · <?= e((string) $s['institucion']) ?></div>
          <p class="mb-2 mt-2"><?= e($s['mensaje']) ?></p>
          <?php if (!empty($s['respuesta'])): ?>
            <div class="alert alert-light small mb-2"><strong>Respuesta:</strong> <?= e($s['respuesta']) ?></div>
          <?php endif; ?>
          <form method="post" class="d-flex gap-2">
            <?= csrf_field() ?>
            <input type="hidden" name="accion" value="responder_solicitud">
            <input type="hidden" name="id_solicitud" value="<?= (int) $s['id_solicitud'] ?>">
            <input class="form-control form-control-sm" name="respuesta" placeholder="Responder..." maxlength="500" value="<?= e($s['respuesta'] ?? '') ?>">
            <select name="estado" class="form-select form-select-sm" style="width:auto;">
              <option value="pendiente" <?= $s['estado'] === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
              <option value="revisada" <?= $s['estado'] === 'revisada' ? 'selected' : '' ?>>Revisada</option>
              <option value="cerrada" <?= $s['estado'] === 'cerrada' ? 'selected' : '' ?>>Cerrada</option>
            </select>
            <button class="btn btn-sm btn-edu" type="submit"><i class="bi bi-send"></i></button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (!$solicitudes): ?>
      <p class="text-muted">No hay solicitudes todavía.</p>
    <?php endif; ?>
  </div>
  </div>

  <!-- Modal editar usuario -->
  <div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content card-edu p-4">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="fw-bold">Editar usuario</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="post" class="mt-3">
          <?= csrf_field() ?>
          <input type="hidden" name="accion" value="editar_usuario">
          <input type="hidden" name="id_usuario" id="eu_id">
          <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input class="form-control" name="nombre" id="eu_nombre" required minlength="3" maxlength="120">
          </div>
          <div class="mb-3">
            <label class="form-label">Correo</label>
            <input class="form-control" type="email" name="correo" id="eu_correo" required>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label">Grado <small class="text-muted">(1-5)</small></label>
              <select class="form-select" name="grado" id="eu_grado">
                <option value="0">—</option>
                <option value="1">1°</option><option value="2">2°</option>
                <option value="3">3°</option><option value="4">4°</option><option value="5">5°</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label">Materia</label>
              <select class="form-select" name="materia" id="eu_materia">
                <option value="">—</option>
                <option value="matematicas">Matemáticas</option>
                <option value="ingles">Inglés</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Nueva contraseña <small class="text-muted">(dejar vacío para no cambiar)</small></label>
            <input class="form-control" type="password" name="nueva_password" minlength="8" placeholder="Mín. 8 caracteres">
          </div>
          <button class="btn btn-edu w-100" type="submit">Guardar cambios</button>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
function abrirEditar(id, nombre, correo, grado, materia) {
  document.getElementById('eu_id').value = id;
  document.getElementById('eu_nombre').value = nombre;
  document.getElementById('eu_correo').value = correo;
  document.getElementById('eu_grado').value = grado || 0;
  document.getElementById('eu_materia').value = materia || '';
  new bootstrap.Modal(document.getElementById('modalEditar')).show();
}
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
