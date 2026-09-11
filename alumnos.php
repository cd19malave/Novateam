<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_role('profesor');

$user = current_user();
$materia = (string) $user['materia'];

$alumnos = db()->prepare(
    'SELECT u.id_usuario, u.nombre, u.correo, u.puntos, u.ejercicios_resueltos,
            u.foto_perfil, u.marco_perfil, u.ultimo_acceso,
            COUNT(DISTINCT i.id_intento) AS intentos,
            SUM(CASE WHEN i.completado = 1 THEN 1 ELSE 0 END) AS completados,
            AVG(CASE WHEN i.completado = 1 THEN i.puntaje ELSE NULL END) AS promedio_puntaje,
            COUNT(DISTINCT ui2.id_insignia) AS insignias
     FROM usuarios u
     INNER JOIN matriculas m ON m.id_usuario = u.id_usuario AND m.materia = :mat
     LEFT JOIN intentos i ON i.id_usuario = u.id_usuario
     LEFT JOIN guias g ON g.id_guia = i.id_guia AND g.categoria = :mat2
     LEFT JOIN usuario_insignias ui2 ON ui2.id_usuario = u.id_usuario
     WHERE u.rol = \'estudiante\' AND u.activo = 1
     GROUP BY u.id_usuario, u.nombre, u.correo, u.puntos, u.ejercicios_resueltos,
              u.foto_perfil, u.marco_perfil, u.ultimo_acceso
     ORDER BY u.puntos DESC'
);
$alumnos->execute(['mat' => $materia, 'mat2' => $materia]);
$list = $alumnos->fetchAll();

$detId = (int) ($_GET['ver'] ?? 0);
$detalle = null;
if ($detId) {
    $dst = db()->prepare(
        'SELECT u.*, COUNT(DISTINCT i.id_intento) AS total_intentos,
                SUM(CASE WHEN i.completado = 1 THEN 1 ELSE 0 END) AS completados
         FROM usuarios u
         LEFT JOIN intentos i ON i.id_usuario = u.id_usuario
         LEFT JOIN guias g ON g.id_guia = i.id_guia AND g.categoria = :mat
         WHERE u.id_usuario = :id AND u.rol = \'estudiante\'
         GROUP BY u.id_usuario'
    );
    $dst->execute(['mat' => $materia, 'id' => $detId]);
    $detalle = $dst->fetch();

    if ($detalle) {
        $hist = db()->prepare(
            'SELECT g.titulo, g.categoria, i.puntaje, i.total_ejercicios, i.fecha_completado
             FROM intentos i JOIN guias g ON g.id_guia = i.id_guia
             WHERE i.id_usuario = :uid AND g.categoria = :mat AND i.completado = 1
             ORDER BY i.fecha_completado DESC'
        );
        $hist->execute(['uid' => $detId, 'mat' => $materia]);
        $detalle['historial'] = $hist->fetchAll();

        $badges = db()->prepare(
            'SELECT i.codigo, i.nombre, i.icono FROM usuario_insignias ui
             JOIN insignias i ON i.id_insignia = ui.id_insignia
             WHERE ui.id_usuario = :id'
        );
        $badges->execute(['id' => $detId]);
        $detalle['badges'] = $badges->fetchAll();
    }
}

$pageTitle = 'Mis alumnos';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-5">
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
      <span class="section-eyebrow">Profesor · <?= e(categoria_label($materia)) ?></span>
      <h1 class="h3 fw-bold mb-0">Mis alumnos</h1>
      <p class="text-muted small"><?= count($list) ?> estudiantes inscritos en <?= e(categoria_label($materia)) ?></p>
    </div>
    <a class="btn btn-edu-outline" href="profesor.php"><i class="bi bi-arrow-left"></i> Volver al panel</a>
  </div>
  <?php render_alerts(); ?>

  <?php if ($detalle): ?>
    <div class="card-edu p-4 mb-4">
      <div class="d-flex align-items-center gap-3 mb-3">
        <?= user_avatar_html($detalle, 64) ?>
        <div>
          <h2 class="h5 fw-bold mb-0"><?= e($detalle['nombre']) ?></h2>
          <p class="text-muted mb-0 small"><?= e($detalle['correo']) ?></p>
        </div>
      </div>
      <?php if (!empty($detalle['bio'])): ?>
        <p class="text-muted"><?= nl2br(e($detalle['bio'])) ?></p>
      <?php endif; ?>
      <div class="d-flex gap-3 flex-wrap mb-3">
        <div class="badge-pill"><i class="bi bi-trophy"></i> <?= (int) $detalle['puntos'] ?> pts</div>
        <div class="badge-pill"><i class="bi bi-check-circle"></i> <?= (int) $detalle['ejercicios_resueltos'] ?> ejercicios</div>
        <div class="badge-pill"><i class="bi bi-journal-check"></i> <?= (int) ($detalle['completados'] ?? 0) ?> guías completadas</div>
        <div class="badge-pill"><i class="bi bi-trophy-fill"></i> <?= count($detalle['badges']) ?> insignias</div>
      </div>
      <?php if ($detalle['badges']): ?>
        <div class="d-flex gap-2 flex-wrap mb-3">
          <?php foreach ($detalle['badges'] as $b): ?>
            <span class="badge-pill" title="<?= e($b['nombre']) ?>"><?= e($b['icono']) ?> <?= e($b['nombre']) ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php if ($detalle['historial']): ?>
        <h6 class="fw-bold mt-3">Historial en <?= e(categoria_label($materia)) ?></h6>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead><tr><th>Guía</th><th>Puntaje</th><th>Fecha</th></tr></thead>
            <tbody>
              <?php foreach ($detalle['historial'] as $h): ?>
                <tr>
                  <td><?= e($h['titulo']) ?></td>
                  <td><?= (int) $h['puntaje'] ?> / <?= (int) $h['total_ejercicios'] * 10 ?></td>
                  <td><?= e(date('d/m/Y H:i', strtotime((string) $h['fecha_completado']))) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
      <div class="mt-3">
        <a class="btn btn-edu btn-sm" href="mensajes.php?para=<?= (int) $detalle['id_usuario'] ?>"><i class="bi bi-chat-dots"></i> Enviar mensaje</a>
        <a class="btn btn-edu-outline btn-sm" href="alumnos.php"><i class="bi bi-arrow-left"></i> Volver a la lista</a>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!$detalle): ?>
    <?php if (empty($list)): ?>
      <div class="card-edu p-4 text-center">
        <i class="bi bi-people" style="font-size:3rem;color:var(--edu-primary);opacity:.4;"></i>
        <p class="mt-2 text-muted">Aún no hay alumnos inscritos en <?= e(categoria_label($materia)) ?>.</p>
      </div>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($list as $a): ?>
          <div class="col-md-6 col-lg-4">
            <a href="alumnos.php?ver=<?= (int) $a['id_usuario'] ?>" class="text-decoration-none">
              <div class="card-edu p-3 h-100">
                <div class="d-flex align-items-center gap-3">
                  <?= user_avatar_html($a, 48) ?>
                  <div>
                    <h6 class="fw-bold mb-0 text-dark"><?= e($a['nombre']) ?></h6>
                    <small class="text-muted"><?= (int) $a['puntos'] ?> pts · <?= (int) $a['ejercicios_resueltos'] ?> ejercicios</small>
                  </div>
                </div>
                <div class="d-flex gap-2 mt-2 flex-wrap">
                  <span class="badge-pill" style="font-size:.7rem"><i class="bi bi-journal-check"></i> <?= (int) ($a['completados'] ?? 0) ?> guías</span>
                  <span class="badge-pill" style="font-size:.7rem"><i class="bi bi-trophy-fill"></i> <?= (int) $a['insignias'] ?></span>
                </div>
                <?php if (!empty($a['ultimo_acceso'])): ?>
                  <small class="text-muted d-block mt-2" style="font-size:.7rem">Último acceso: <?= e(date('d/m/Y H:i', strtotime((string) $a['ultimo_acceso']))) ?></small>
                <?php endif; ?>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
