<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$user = current_user();
$pdo = db();

$badges = $pdo->prepare(
    'SELECT i.codigo, i.nombre, i.icono, i.puntos_requeridos,
            CASE WHEN ui.id_usuario IS NOT NULL THEN 1 ELSE 0 END AS obtenida
     FROM insignias i
     LEFT JOIN usuario_insignias ui ON ui.id_insignia = i.id_insignia AND ui.id_usuario = :id
     ORDER BY i.puntos_requeridos'
);
$badges->execute(['id' => $user['id_usuario']]);
$allBadges = $badges->fetchAll();

$ptsStmt = $pdo->prepare(
    'SELECT COALESCE(SUM(puntaje),0) FROM intentos WHERE id_usuario = :id AND completado = 1'
);
$ptsStmt->execute(['id' => $user['id_usuario']]);
$pts = (int) $ptsStmt->fetchColumn();

$rank = $pdo->prepare(
    'SELECT COUNT(*) + 1 FROM (
        SELECT u.id_usuario FROM usuarios u
        LEFT JOIN intentos i ON i.id_usuario = u.id_usuario AND i.completado = 1
        WHERE u.rol = :r AND u.activo = 1
        GROUP BY u.id_usuario
        HAVING COALESCE(SUM(i.puntaje),0) > :p
    ) x'
);
$rank->execute(['r' => $user['rol'], 'p' => $pts]);
$ranking = (int) $rank->fetchColumn();

$materias = user_materias($user['id_usuario']);

$recientes = $pdo->prepare(
    'SELECT g.titulo, g.categoria, i.puntaje, i.total_ejercicios, i.fecha_completado
     FROM intentos i JOIN guias g ON g.id_guia = i.id_guia
     WHERE i.id_usuario = :id AND i.completado = 1
     ORDER BY i.fecha_completado DESC LIMIT 5'
);
$recientes->execute(['id' => $user['id_usuario']]);

$pageTitle = 'Mi perfil';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-5" style="max-width:800px">
  <?php render_alerts(); ?>

  <div class="card-edu p-4 mb-4" id="perfil-header" style="position:relative;overflow:hidden;">
    <?php if (!empty($user['fondo_perfil'])): ?>
      <div style="position:absolute;top:0;left:0;right:0;height:120px;background:linear-gradient(rgba(0,0,0,.3),rgba(0,0,0,.3)),url(<?= e($user['fondo_perfil']) ?>) center/cover;"></div>
    <?php else: ?>
      <div style="position:absolute;top:0;left:0;right:0;height:120px;background:linear-gradient(135deg,var(--edu-primary),var(--edu-accent));"></div>
    <?php endif; ?>

    <div style="position:relative;padding-top:60px;" class="text-center">
      <div style="display:inline-block;position:relative;z-index:2;">
        <?= user_avatar_html($user, 96) ?>
      </div>
      <h2 class="fw-bold mt-2 mb-0"><?= e($user['nombre']) ?></h2>
      <p class="text-muted mb-1"><?= e($user['correo']) ?></p>
      <?php if (!empty($user['bio'])): ?>
        <p class="mt-2" style="max-width:500px;margin-inline:auto;"><?= nl2br(e($user['bio'])) ?></p>
      <?php endif; ?>

      <div class="d-flex justify-content-center gap-3 mt-3 flex-wrap">
        <div class="badge-pill"><i class="bi bi-trophy"></i> <?= $pts ?> pts</div>
        <div class="badge-pill"><i class="bi bi-check-circle"></i> <?= (int) $user['ejercicios_resueltos'] ?> ejercicios</div>
        <div class="badge-pill"><i class="bi bi-bar-chart"></i> #<?= $ranking ?> en ranking</div>
      </div>

      <?php if (!empty($materias)): ?>
        <div class="mt-2">
          <?php foreach ($materias as $m): ?>
            <span class="badge-pill" style="margin:2px;"><?= e(categoria_label($m)) ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card-edu p-4 mb-4">
    <h3 class="h5 fw-bold mb-3"><i class="bi bi-gear"></i> Editar perfil</h3>
    <form id="form-perfil" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Foto de perfil</label>
          <input class="form-control" type="file" name="foto_perfil" accept="image/*">
        </div>
        <div class="col-md-6">
          <label class="form-label">Fondo de perfil</label>
          <input class="form-control" type="file" name="fondo_perfil" accept="image/*">
        </div>
        <div class="col-12">
          <label class="form-label">Biografía <small class="text-muted">(máx. 300)</small></label>
          <textarea class="form-control" name="bio" rows="3" maxlength="300"><?= e($user['bio'] ?? '') ?></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label">Tema de color</label>
          <select class="form-select" name="tema_color">
            <?php
            $temas = ['default','oscuro','verde','rosa','purpura'];
            $temaLabels = ['default' => 'Predeterminado', 'oscuro' => 'Oscuro', 'verde' => 'Verde', 'rosa' => 'Rosa', 'purpura' => 'Púrpura'];
            $currentTema = $user['tema_color'] ?? 'default';
            foreach ($temas as $t): ?>
              <option value="<?= $t ?>" <?= $t === $currentTema ? 'selected' : '' ?>><?= $temaLabels[$t] ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <button class="btn btn-edu mt-3" type="submit">Guardar cambios</button>
    </form>
  </div>

  <div class="card-edu p-4 mb-4">
    <h3 class="h5 fw-bold mb-3"><i class="bi bi-trophy"></i> Logros</h3>
    <div class="row g-3">
      <?php foreach ($allBadges as $b): ?>
        <div class="col-6 col-md-3">
          <div class="card-edu p-3 text-center" style="<?= (int) $b['obtenida'] ? '' : 'opacity:.4' ?>">
            <div style="font-size:2rem"><?= e($b['icono']) ?></div>
            <strong class="small"><?= e($b['nombre']) ?></strong>
            <div class="text-muted" style="font-size:.75rem"><?= (int) $b['puntos_requeridos'] ?> pts<?= (int) $b['obtenida'] ? ' ✓' : '' ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if ($recientes->rowCount() > 0): ?>
    <div class="card-edu p-4">
      <h3 class="h5 fw-bold mb-3"><i class="bi bi-clock-history"></i> Actividad reciente</h3>
      <div class="table-responsive">
        <table class="table mb-0">
          <thead><tr><th>Guía</th><th>Área</th><th>Puntaje</th><th>Fecha</th></tr></thead>
          <tbody>
            <?php foreach ($recientes as $r): ?>
              <tr>
                <td><?= e($r['titulo']) ?></td>
                <td><?= e(categoria_label($r['categoria'])) ?></td>
                <td><?= (int) $r['puntaje'] ?> / <?= (int) $r['total_ejercicios'] * 10 ?></td>
                <td><?= e(date('d/m/Y H:i', strtotime((string) $r['fecha_completado']))) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
document.getElementById('form-perfil').addEventListener('submit', async function(e) {
  e.preventDefault();
  const fd = new FormData(this);
  const btn = this.querySelector('button[type="submit"]');
  const foto = this.querySelector('input[name="foto_perfil"]');
  const fondo = this.querySelector('input[name="fondo_perfil"]');
  if (foto.files[0] && foto.files[0].size > 50 * 1024 * 1024) {
    alert('La foto de perfil no puede superar 50 MB. Elige una imagen más pequeña.');
    return;
  }
  if (fondo.files[0] && fondo.files[0].size > 50 * 1024 * 1024) {
    alert('El fondo de perfil no puede superar 50 MB. Elige una imagen más pequeña.');
    return;
  }
  btn.disabled = true;
  btn.textContent = 'Guardando...';
  let resp;
  try {
    resp = await fetch('api/perfil.php', { method: 'POST', body: fd });
    const data = await resp.json();
    if (data.ok) {
      location.reload();
    } else {
      alert(data.error || 'Error al guardar.');
    }
  } catch(err) {
    let msg = 'Error de conexión. Verifica tu internet y que los archivos no superen el tamaño permitido.';
    try {
      if (resp) {
        const text = await resp.text();
        if (text && !text.includes('<')) msg = text.trim().slice(0, 200);
      }
    } catch(e2) {}
    alert(msg);
  }
  btn.disabled = false;
  btn.textContent = 'Guardar cambios';
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
