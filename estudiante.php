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

$xp = (int) $user['puntos'];
$level = user_level($xp);
$levelProgress = $xp % 100;
apply_streak_shields((int) $user['id_usuario']);
$streak = user_streak((int) $user['id_usuario']);
$lives = user_lives((int) $user['id_usuario']);
$powerups = user_powerups((int) $user['id_usuario']);

$grupos = [];
foreach ($materias as $m) {
    $grupos[$m] = [];
}
foreach ($list as $g) {
    if (!isset($grupos[$g['categoria']])) {
        $grupos[$g['categoria']] = [];
    }
    $grupos[$g['categoria']][] = $g;
}

$nextGuide = null;
$doneCount = 0;
foreach ($list as $g) {
    if ((int) $g['completado'] === 1) {
        $doneCount++;
    } elseif ($nextGuide === null) {
        $nextGuide = $g;
    }
}
$totalCount = count($list);

$ringCirc = 201.06;
$ringOffset = $ringCirc * (1 - ($levelProgress / 100));
$catIcon = static fn(string $c): string => $c === 'ingles' ? 'translate' : 'calculator';

$pageTitle = 'Mis guías';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-4 py-lg-5">

  <!-- Hero -->
  <section class="dash-hero mb-4">
    <div class="dash-hero-main">
      <span class="section-eyebrow">Hola, <?= e($user['nombre']) ?> 👋</span>
      <h1 class="hero-title"><?= $nextGuide ? 'Tu siguiente reto te espera' : '¡Todo completado!' ?></h1>
      <p class="hero-sub">
        <?php if ($nextGuide): ?>
          Continúa donde lo dejaste y sigue sumando puntos.
        <?php else: ?>
          Vuelve pronto para nuevos retos de tus materias.
        <?php endif; ?>
      </p>
      <div class="hero-actions">
        <?php if ($nextGuide): ?>
          <a class="btn btn-edu btn-lg hero-cta" href="guia.php?id=<?= (int) $nextGuide['id_guia'] ?>">
            <i class="bi bi-play-fill"></i>
            <?= ((int) $nextGuide['puntaje'] > 0) ? 'Continuar' : 'Empezar' ?>
          </a>
          <span class="hero-next"><i class="bi bi-bookmark-star"></i> <?= e($nextGuide['titulo']) ?></span>
        <?php else: ?>
          <a class="btn btn-edu btn-lg hero-cta" href="progreso.php">
            <i class="bi bi-graph-up-arrow"></i> Ver mi progreso
          </a>
        <?php endif; ?>
      </div>
    </div>

    <div class="dash-hero-side">
      <span class="mascot mascot-sm" aria-hidden="true">🦉</span>
      <div class="level-ring" role="img" aria-label="Nivel <?= $level ?>">
        <svg viewBox="0 0 80 80" width="116" height="116">
          <defs>
            <linearGradient id="lvlGrad" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0%" stop-color="#4F8FF7"/>
              <stop offset="55%" stop-color="#8B5CF6"/>
              <stop offset="100%" stop-color="#EC6BB2"/>
            </linearGradient>
          </defs>
          <circle cx="40" cy="40" r="32" fill="none" stroke="var(--edu-border)" stroke-width="7"/>
          <circle cx="40" cy="40" r="32" fill="none" stroke="url(#lvlGrad)" stroke-width="7"
                  stroke-linecap="round" stroke-dasharray="<?= $ringCirc ?>"
                  stroke-dashoffset="<?= $ringOffset ?>" transform="rotate(-90 40 40)"/>
        </svg>
        <div class="level-ring-label">
          <span class="level-num"><?= $level ?></span>
          <span class="level-cap">Nivel</span>
        </div>
      </div>
      <span class="level-xp"><?= $levelProgress ?> / 100 XP</span>
    </div>
  </section>

  <!-- Estadísticas -->
  <section class="dash-stats mb-4">
    <div class="stat-card">
      <span class="stat-ico" style="--sc:#FFB84D"><i class="bi bi-lightning-charge-fill"></i></span>
      <div><span class="stat-val"><?= $xp ?></span><span class="stat-lbl">XP total</span></div>
    </div>
    <div class="stat-card">
      <span class="stat-ico" style="--sc:#FF7A45"><i class="bi bi-fire"></i></span>
      <div><span class="stat-val"><?= $streak ?></span><span class="stat-lbl"><?= $streak === 1 ? 'día de racha' : 'días de racha' ?></span></div>
    </div>
    <div class="stat-card">
      <span class="stat-ico" style="--sc:#56D39F"><i class="bi bi-check2-circle"></i></span>
      <div><span class="stat-val"><?= $doneCount ?>/<?= $totalCount ?></span><span class="stat-lbl">guías completadas</span></div>
    </div>
    <div class="stat-card">
      <span class="stat-ico" style="--sc:#4F8FF7"><i class="bi bi-journal-check"></i></span>
      <div><span class="stat-val"><?= (int) $user['ejercicios_resueltos'] ?></span><span class="stat-lbl">ejercicios</span></div>
    </div>
    <div class="stat-card">
      <span class="stat-ico" style="--sc:#FF5C8A"><i class="bi bi-heart-fill"></i></span>
      <div><span class="stat-val"><?= $lives['vidas'] ?>/<?= $lives['max'] ?></span><span class="stat-lbl">vidas</span></div>
    </div>
  </section>

  <section class="power-strip mb-4">
    <span class="power-strip-title"><i class="bi bi-stars"></i> Mis poderes</span>
    <a href="tienda.php" class="power-chip"><i class="bi bi-shield-fill-check"></i> Escudos <strong><?= $powerups['escudos'] ?></strong></a>
    <a href="tienda.php" class="power-chip"><i class="bi bi-patch-question-fill"></i> Comodines <strong><?= $powerups['comodines'] ?></strong></a>
    <a href="tienda.php" class="power-chip"><i class="bi bi-lightning-charge-fill"></i> Doble puntos <strong><?= $powerups['doble'] ?></strong></a>
    <a href="tienda.php" class="power-chip power-chip--cta"><i class="bi bi-bag-heart-fill"></i> Tienda</a>
  </section>

  <?php render_alerts(); ?>

  <?php if ($notifs): ?>
    <div class="card-edu p-3 mb-4">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <h2 class="h6 fw-bold mb-0"><i class="bi bi-bell-fill"></i> Avisos recientes</h2>
        <a class="small text-decoration-none" href="notificaciones.php"><i class="bi bi-chevron-right"></i> Ver todas</a>
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
      <p class="text-muted mb-0">Contacta a tu profesor para que te registre en matemáticas o inglés.</p>
    </div>
  <?php else: ?>
    <section class="mb-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 fw-bold mb-0">Tu ruta de aprendizaje</h2>
        <a class="small text-decoration-none" href="progreso.php">Ver progreso <i class="bi bi-chevron-right"></i></a>
      </div>

      <?php foreach ($grupos as $cat => $items): if (!$items) continue; ?>
        <div class="path-group">
          <div class="path-group-head">
            <span class="path-cat-ico"><i class="bi bi-<?= $catIcon($cat) ?>"></i></span>
            <?= e(categoria_label($cat)) ?>
            <span class="path-cat-count"><?= count($items) ?></span>
          </div>
          <ol class="path">
            <?php foreach ($items as $g):
              $done = (int) $g['completado'] === 1;
              $isNext = $nextGuide && (int) $nextGuide['id_guia'] === (int) $g['id_guia'];
              $n = (int) $g['n_ejercicios'];
              $state = $done ? 'done' : ($isNext ? 'current' : 'pending');
            ?>
              <li class="path-node <?= $state ?>">
                <a class="path-link" href="guia.php?id=<?= (int) $g['id_guia'] ?>">
                  <span class="path-marker">
                    <i class="bi bi-<?= $done ? 'check-lg' : ($isNext ? 'play-fill' : 'star-fill') ?>"></i>
                  </span>
                  <span class="path-body">
                    <span class="path-title"><?= e($g['titulo']) ?></span>
                    <span class="path-meta">
                      <?= $n ?> ejercicio<?= $n === 1 ? '' : 's' ?> · <?= e(dificultad_label($g['dificultad'])) ?>
                      <?php if ($done): ?> · <strong class="text-success"><?= (int) $g['puntaje'] ?> pts</strong><?php endif; ?>
                    </span>
                  </span>
                  <?php if ($isNext): ?><span class="path-cta">EMPEZAR</span><?php endif; ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ol>
        </div>
      <?php endforeach; ?>

      <?php if (!$list): ?>
        <div class="card-edu p-4 text-center text-muted">
          Aún no hay guías publicadas para tus materias.
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
