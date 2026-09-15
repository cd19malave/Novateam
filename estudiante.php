<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_role('estudiante');

$user = current_user();
$materias = user_materias($user['id_usuario']);

$notifsStmt = db()->prepare(
    'SELECT titulo, mensaje, fecha_creacion, id_guia
     FROM notificaciones
     WHERE id_destinatario = :id AND leida = 0
     ORDER BY fecha_creacion DESC LIMIT 5'
);
$notifsStmt->execute(['id' => $user['id_usuario']]);
$notifs = $notifsStmt->fetchAll();

if (!empty($materias)) {
    $named = [];
    $params = ['uid' => $user['id_usuario']];
    foreach ($materias as $i => $m) {
        $key = ':mat' . $i;
        $named[] = $key;
        $params[$key] = $m;
    }
    $inClause = implode(',', $named);
    $guias = db()->prepare(
        "SELECT g.*,
                i.completado, i.puntaje, i.total_ejercicios,
                (SELECT COUNT(*) FROM ejercicios e WHERE e.id_guia = g.id_guia) AS n_ejercicios
         FROM guias g
         LEFT JOIN intentos i ON i.id_guia = g.id_guia AND i.id_usuario = :uid
         WHERE g.estado = 'publicada'
           AND g.fecha_expiracion > NOW()
           AND g.categoria IN ({$inClause})
         ORDER BY g.fecha_publicacion DESC"
    );
    $guias->execute($params);
} else {
    $guias = db()->prepare(
        "SELECT g.*,
                i.completado, i.puntaje, i.total_ejercicios,
                (SELECT COUNT(*) FROM ejercicios e WHERE e.id_guia = g.id_guia) AS n_ejercicios
         FROM guias g
         LEFT JOIN intentos i ON i.id_guia = g.id_guia AND i.id_usuario = :uid
         WHERE g.estado = 'publicada'
           AND g.fecha_expiracion > NOW()
         ORDER BY g.fecha_publicacion DESC"
    );
    $guias->execute(['uid' => $user['id_usuario']]);
}
$list = $guias->fetchAll();

$pageTitle = 'Mis guías';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-5">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
      <span class="section-eyebrow">Hola, <?= e($user['nombre']) ?></span>
      <h1 class="h3 fw-bold mb-0">Tus retos de hoy</h1>
    </div>
    <div class="card-edu px-4 py-3">
      <strong><?= (int) $user['puntos'] ?></strong> puntos · <?= (int) $user['ejercicios_resueltos'] ?> ejercicios
    </div>
  </div>
  <?php render_alerts(); ?>

  <?php if ($notifs): ?>
    <div class="card-edu p-3 mb-4">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <h2 class="h6 fw-bold mb-0"><i class="bi bi-bell-fill"></i> Notificaciones</h2>
        <a class="small" href="notificaciones.php"><i class="bi bi-chevron-right"></i> Ver todas</a>
      </div>
      <?php foreach ($notifs as $nf): ?>
        <div class="d-flex justify-content-between align-items-start border-top pt-2 mt-2">
          <div>
            <strong><?= e($nf['titulo']) ?></strong>
            <div class="text-muted small"><?= e($nf['mensaje']) ?> · <?= e(date('d/m/Y H:i', strtotime((string) $nf['fecha_creacion']))) ?></div>
          </div>
          <?php if (!empty($nf['id_guia'])): ?>
            <a class="btn btn-edu-outline btn-sm py-0 ms-2" href="guia.php?id=<?= (int) $nf['id_guia'] ?>">Jugar</a>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (empty($materias)): ?>
    <div class="card-edu p-4 text-center">
      <i class="bi bi-bookmark-plus" style="font-size:3rem;color:var(--edu-primary);opacity:.4;"></i>
      <h5 class="mt-2">Aún no estás inscrito en ninguna materia</h5>
      <p class="text-muted">Contacta a tu profesor para que te registre en matemáticas o inglés.</p>
    </div>
  <?php else: ?>
    <div class="d-flex gap-2 mb-3 flex-wrap">
      <?php foreach ($materias as $m): ?>
        <span class="badge-pill"><?= e(categoria_label($m)) ?></span>
      <?php endforeach; ?>
    </div>
    <div class="row g-4">
      <?php foreach ($list as $g): ?>
        <div class="col-md-6">
          <div class="card-edu p-4 h-100">
            <span class="badge-pill"><?= e(categoria_label($g['categoria'])) ?></span>
            <span class="badge-pill"><?= e(dificultad_label($g['dificultad'])) ?></span>
            <span class="badge-pill"><?= e($g['modo_creacion'] === 'ia' ? '🤖 IA' : '✏️ Manual') ?></span>
            <h2 class="h5 fw-bold mt-3"><?= e($g['titulo']) ?></h2>
            <p class="text-muted small"><?= (int) $g['n_ejercicios'] ?> ejercicios · vence <?= e(date('d/m/Y', strtotime((string) $g['fecha_expiracion']))) ?></p>
            <?php if ((int) $g['completado'] === 1): ?>
              <p class="mb-2 text-success fw-bold">Completada · <?= (int) $g['puntaje'] ?> / <?= (int) $g['total_ejercicios'] * 10 ?> pts</p>
              <a class="btn-edu-outline" href="guia.php?id=<?= (int) $g['id_guia'] ?>">Ver resultados</a>
            <?php else: ?>
              <a class="btn btn-edu" href="guia.php?id=<?= (int) $g['id_guia'] ?>">Jugar ahora</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$list): ?>
        <p class="text-muted">Aún no hay guías publicadas para tus materias.</p>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
