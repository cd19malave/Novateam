<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_role('estudiante');

$user = current_user();
$earned = db()->prepare(
    'SELECT i.codigo FROM usuario_insignias ui
     JOIN insignias i ON i.id_insignia = ui.id_insignia
     WHERE ui.id_usuario = :id'
);
$earned->execute(['id' => $user['id_usuario']]);
$earnedCodes = array_column($earned->fetchAll(), 'codigo');

$materias = user_materias($user['id_usuario']);
$hist = db()->prepare(
    'SELECT g.titulo, g.categoria, i.puntaje, i.total_ejercicios, i.fecha_completado
     FROM intentos i
     JOIN guias g ON g.id_guia = i.id_guia
     WHERE i.id_usuario = :id AND i.completado = 1
     ORDER BY i.fecha_completado DESC'
);
$hist->execute(['id' => $user['id_usuario']]);

$allBadges = db()->query('SELECT codigo, nombre, icono, puntos_requeridos FROM insignias ORDER BY puntos_requeridos')->fetchAll();

$pageTitle = 'Mi progreso';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-5">
  <div class="d-flex align-items-center gap-3 mb-4">
    <?= user_avatar_html($user, 72) ?>
    <div>
      <h1 class="h3 fw-bold mb-0">Tu progreso</h1>
      <p class="text-muted mb-0"><?= (int) $user['puntos'] ?> puntos · <?= (int) $user['ejercicios_resueltos'] ?> aciertos</p>
      <?php if (!empty($materias)): ?>
        <div class="d-flex gap-2 mt-1">
          <?php foreach ($materias as $m): ?>
            <span class="badge-pill" style="font-size:.7rem;"><?= e(categoria_label($m)) ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <h2 class="h5 fw-bold mt-4">Insignias</h2>
  <div class="row g-3 mb-4">
    <?php foreach ($allBadges as $b): ?>
      <div class="col-md-3">
        <div class="card-edu p-3 text-center" style="<?= in_array($b['codigo'], $earnedCodes, true) ? '' : 'opacity:.45' ?>">
          <div style="font-size:2rem"><?= e($b['icono']) ?></div>
          <strong><?= e($b['nombre']) ?></strong>
          <div class="small text-muted"><?= (int) $b['puntos_requeridos'] ?> pts<?= in_array($b['codigo'], $earnedCodes, true) ? ' · obtenida' : '' ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <h2 class="h5 fw-bold">Historial</h2>
  <div class="table-responsive card-edu p-3">
    <table class="table mb-0">
      <thead><tr><th>Guía</th><th>Área</th><th>Puntaje</th><th>Fecha</th></tr></thead>
      <tbody>
        <?php foreach ($hist as $h): ?>
          <tr>
            <td><?= e($h['titulo']) ?></td>
            <td><?= e(categoria_label($h['categoria'])) ?></td>
            <td><?= (int) $h['puntaje'] ?> / <?= (int) $h['total_ejercicios'] * 10 ?></td>
            <td><?= e(date('d/m/Y H:i', strtotime((string) $h['fecha_completado']))) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
