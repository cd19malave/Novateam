<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_role('profesor');

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id = (int) ($_POST['id_guia'] ?? 0);
    $accion = (string) ($_POST['accion'] ?? '');

    $own = db()->prepare('SELECT * FROM guias WHERE id_guia = :id AND id_profesor = :p LIMIT 1');
    $own->execute(['id' => $id, 'p' => $user['id_usuario']]);
    $guia = $own->fetch();
    if (!$guia) {
        flash('error', 'No puedes modificar esa guía.');
        redirect('profesor.php');
    }

    if ($accion === 'publicar') {
        $n = db()->prepare('SELECT COUNT(*) FROM ejercicios WHERE id_guia = :id');
        $n->execute(['id' => $id]);
        if ((int) $n->fetchColumn() < 1) {
            flash('error', 'Agrega al menos un ejercicio antes de publicar.');
            redirect('profesor.php');
        }
        $upd = db()->prepare(
            'UPDATE guias SET estado = :e, fecha_publicacion = NOW() WHERE id_guia = :id'
        );
        $upd->execute(['e' => 'publicada', 'id' => $id]);
        $nt = db()->prepare(
            'INSERT INTO notificaciones (titulo, mensaje, id_profesor) VALUES (:t, :m, :p)'
        );
        $nt->execute([
            't' => 'Nueva guía: ' . $guia['titulo'],
            'm' => 'Ya puedes resolverla en EduNova.',
            'p' => $user['id_usuario'],
        ]);
        flash('ok', 'Guía publicada.');
    } elseif ($accion === 'borrar') {
        $del = db()->prepare('DELETE FROM guias WHERE id_guia = :id AND id_profesor = :p');
        $del->execute(['id' => $id, 'p' => $user['id_usuario']]);
        flash('ok', 'Guía eliminada.');
    }
    redirect('profesor.php');
}

$guias = db()->prepare(
    'SELECT g.*,
            (SELECT COUNT(*) FROM ejercicios e WHERE e.id_guia = g.id_guia) AS n_ejercicios,
            (SELECT COUNT(*) FROM intentos i WHERE i.id_guia = g.id_guia AND i.completado = 1) AS n_completados
     FROM guias g WHERE g.id_profesor = :p ORDER BY g.fecha_creacion DESC'
);
$guias->execute(['p' => $user['id_usuario']]);

$avance = db()->prepare(
    'SELECT u.nombre, g.titulo, i.puntaje, i.total_ejercicios, i.fecha_completado
     FROM intentos i
     JOIN usuarios u ON u.id_usuario = i.id_usuario
     JOIN guias g ON g.id_guia = i.id_guia
     WHERE g.id_profesor = :p AND i.completado = 1
     ORDER BY i.fecha_completado DESC
     LIMIT 20'
);
$avance->execute(['p' => $user['id_usuario']]);

$pageTitle = 'Panel docente';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-5">
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
      <span class="section-eyebrow">Profesor · <?= e(categoria_label((string) $user['materia'])) ?></span>
      <h1 class="h3 fw-bold mb-0">Tus guías</h1>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-edu-outline" href="alumnos.php"><i class="bi bi-people"></i> Mis alumnos</a>
      <a class="btn btn-edu" href="crear-guia.php">Nueva guía</a>
    </div>
  </div>
  <?php render_alerts(); ?>

  <div class="row g-4 mb-5">
    <?php foreach ($guias as $g): ?>
      <div class="col-md-6">
        <div class="card-edu p-4">
          <span class="badge-pill"><?= e($g['estado']) ?></span>
          <span class="badge-pill"><?= e(dificultad_label($g['dificultad'])) ?></span>
          <span class="badge-pill"><?= e($g['modo_creacion'] === 'ia' ? '🤖 IA' : '✏️ Manual') ?></span>
          <h2 class="h5 mt-3"><?= e($g['titulo']) ?></h2>
          <p class="text-muted small"><?= (int) $g['n_ejercicios'] ?> ejercicios · <?= (int) $g['n_completados'] ?> estudiantes la terminaron</p>
          <form method="post" class="d-flex gap-2 flex-wrap" onsubmit="return confirm('¿Confirmar acción?');">
            <?= csrf_field() ?>
            <input type="hidden" name="id_guia" value="<?= (int) $g['id_guia'] ?>">
            <?php if ($g['estado'] !== 'publicada'): ?>
              <a class="btn btn-edu-outline py-1" href="editar-guia.php?id=<?= (int) $g['id_guia'] ?>"><i class="bi bi-pencil"></i> Editar</a>
              <button class="btn btn-edu py-1" name="accion" value="publicar">Publicar</button>
            <?php endif; ?>
            <button class="btn btn-outline-danger rounded-pill py-1" name="accion" value="borrar">Eliminar</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <h2 class="h4 fw-bold">Avance reciente</h2>
  <div class="table-responsive card-edu p-3">
    <table class="table mb-0">
      <thead><tr><th>Estudiante</th><th>Guía</th><th>Puntaje</th><th>Fecha</th></tr></thead>
      <tbody>
        <?php foreach ($avance as $a): ?>
          <tr>
            <td><?= e($a['nombre']) ?></td>
            <td><?= e($a['titulo']) ?></td>
            <td><?= (int) $a['puntaje'] ?> / <?= (int) $a['total_ejercicios'] * 10 ?></td>
            <td><?= e(date('d/m/Y H:i', strtotime((string) $a['fecha_completado']))) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
