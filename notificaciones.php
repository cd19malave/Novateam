<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_role('estudiante');

$user = current_user();

db()->prepare('UPDATE notificaciones SET leida = 1 WHERE id_destinatario = :id AND leida = 0')
    ->execute(['id' => $user['id_usuario']]);

$lista = db()->prepare(
    'SELECT n.titulo, n.mensaje, n.fecha_creacion, n.id_guia, u.nombre AS profesor
     FROM notificaciones n
     LEFT JOIN usuarios u ON u.id_usuario = n.id_profesor
     WHERE n.id_destinatario = :id
     ORDER BY n.fecha_creacion DESC LIMIT 100'
);
$lista->execute(['id' => $user['id_usuario']]);
$notifs = $lista->fetchAll();

$pageTitle = 'Notificaciones';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-5" style="max-width:760px">
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
      <span class="section-eyebrow"><?= e($user['nombre']) ?></span>
      <h1 class="h3 fw-bold mb-0"><i class="bi bi-bell"></i> Notificaciones</h1>
    </div>
    <a class="btn btn-edu-outline" href="estudiante.php"><i class="bi bi-journal-bookmark"></i> Mis guías</a>
  </div>
  <?php render_alerts(); ?>

  <?php if ($notifs): ?>
    <div class="list-group">
      <?php foreach ($notifs as $nf): ?>
        <div class="list-group-item card-edu p-3 mb-2">
          <div class="d-flex justify-content-between align-items-start gap-2">
            <div>
              <strong><?= e($nf['titulo']) ?></strong>
              <div class="text-muted small"><?= e($nf['mensaje']) ?></div>
              <div class="text-muted" style="font-size:.75rem;">
                <?= !empty($nf['profesor']) ? e($nf['profesor']) . ' · ' : '' ?><?= e(date('d/m/Y H:i', strtotime((string) $nf['fecha_creacion']))) ?>
              </div>
            </div>
            <?php if (!empty($nf['id_guia'])): ?>
              <a class="btn btn-edu btn-sm py-0" href="guia.php?id=<?= (int) $nf['id_guia'] ?>">Ir a la guía</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="card-edu p-4 text-center">
      <i class="bi bi-bell-slash" style="font-size:3rem;color:var(--edu-primary);opacity:.4;"></i>
      <h5 class="mt-2">No tienes notificaciones</h5>
      <p class="text-muted">Cuando tu profesor publique una guía, aparecerá aquí.</p>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>