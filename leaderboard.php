<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$rows = db()->query('SELECT * FROM vw_leaderboard ORDER BY puntos DESC, ejercicios_resueltos DESC LIMIT 50')->fetchAll();

$pageTitle = 'Ranking';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-5">
  <h1 class="h3 fw-bold">Leaderboard</h1>
  <p class="text-muted">Estudiantes con más puntos en EduNova.</p>
  <div class="table-responsive card-edu p-3">
    <table class="table align-middle mb-0">
      <thead><tr><th>#</th><th>Estudiante</th><th>Grado</th><th>Puntos</th><th>Ejercicios</th><th>Insignias</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $i => $r): ?>
          <tr>
            <td>
              <?php if ($i < 3): ?>
                <span style="font-size:1.2rem;"><?= ['🥇','🥈',''][$i] ?></span>
              <?php else: ?>
                <?= $i + 1 ?>
              <?php endif; ?>
            </td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <?php
                $avatarUser = ['nombre' => $r['nombre'], 'foto_perfil' => $r['foto_perfil'] ?? null, 'marco_perfil' => $r['marco_perfil'] ?? null];
                echo user_avatar_html($avatarUser, 32);
                ?>
                <span class="fw-bold"><?= e($r['nombre']) ?></span>
              </div>
            </td>
            <td><?= (int) $r['grado'] ?>°</td>
            <td><strong><?= (int) $r['puntos'] ?></strong></td>
            <td><?= (int) $r['ejercicios_resueltos'] ?></td>
            <td><?= (int) $r['total_insignias'] ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
