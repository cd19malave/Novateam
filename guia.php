<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_role('estudiante');

$user = current_user();
$idGuia = (int) ($_GET['id'] ?? 0);

$gstmt = db()->prepare(
    'SELECT * FROM guias WHERE id_guia = :id AND estado = :est AND fecha_expiracion > NOW() LIMIT 1'
);
$gstmt->execute(['id' => $idGuia, 'est' => 'publicada']);
$guia = $gstmt->fetch();
if (!$guia) {
    flash('error', 'Esa guía no está disponible.');
    redirect('estudiante.php');
}

$estmt = db()->prepare(
    'SELECT id_ejercicio, numero_orden, pregunta, opcion_1, opcion_2, opcion_3, opcion_4
     FROM ejercicios WHERE id_guia = :id ORDER BY numero_orden ASC'
);
$estmt->execute(['id' => $idGuia]);
$ejercicios = $estmt->fetchAll();
if (!$ejercicios) {
    flash('error', 'Esta guía aún no tiene ejercicios.');
    redirect('estudiante.php');
}

$archivosGuia = db()->prepare(
    'SELECT * FROM archivos_adjuntos WHERE id_guia = :id ORDER BY fecha_subida'
);
$archivosGuia->execute(['id' => $idGuia]);
$archivosList = $archivosGuia->fetchAll();

$istmt = db()->prepare('SELECT * FROM intentos WHERE id_usuario = :u AND id_guia = :g LIMIT 1');
$istmt->execute(['u' => $user['id_usuario'], 'g' => $idGuia]);
$intento = $istmt->fetch();
if (!$intento) {
    $ins = db()->prepare(
        'INSERT INTO intentos (id_usuario, id_guia, total_ejercicios) VALUES (:u, :g, :t)'
    );
    $ins->execute(['u' => $user['id_usuario'], 'g' => $idGuia, 't' => count($ejercicios)]);
    $istmt->execute(['u' => $user['id_usuario'], 'g' => $idGuia]);
    $intento = $istmt->fetch();
}

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (int) $intento['completado'] !== 1) {
    csrf_verify();
    $opciones = $_POST['respuesta'] ?? [];
    if (!is_array($opciones) || count($opciones) !== count($ejercicios)) {
        flash('error', 'Responde todas las preguntas antes de enviar.');
        redirect('guia.php?id=' . $idGuia);
    }

    $correctStmt = $pdo->prepare(
        'SELECT id_ejercicio, respuesta_correcta FROM ejercicios WHERE id_guia = :id'
    );
    $correctStmt->execute(['id' => $idGuia]);
    $claves = [];
    foreach ($correctStmt->fetchAll() as $row) {
        $claves[(int) $row['id_ejercicio']] = (int) $row['respuesta_correcta'];
    }

    $pdo->beginTransaction();
    try {
        $puntaje = 0;
        $aciertos = 0;
        $save = $pdo->prepare(
            'INSERT INTO respuestas (id_intento, id_ejercicio, opcion_seleccionada, es_correcta, puntos_obtenidos)
             VALUES (:i, :e, :o, :ok, :p)
             ON DUPLICATE KEY UPDATE opcion_seleccionada = VALUES(opcion_seleccionada),
               es_correcta = VALUES(es_correcta), puntos_obtenidos = VALUES(puntos_obtenidos)'
        );
        foreach ($ejercicios as $ex) {
            $eid = (int) $ex['id_ejercicio'];
            $sel = (int) ($opciones[$eid] ?? 0);
            if ($sel < 1 || $sel > 4) {
                throw new RuntimeException('Opción inválida');
            }
            $ok = isset($claves[$eid]) && $claves[$eid] === $sel;
            $pts = $ok ? 10 : 0;
            if ($ok) {
                $aciertos++;
                $puntaje += $pts;
            }
            $save->execute([
                'i' => $intento['id_intento'],
                'e' => $eid,
                'o' => $sel,
                'ok' => $ok ? 1 : 0,
                'p' => $pts,
            ]);
        }

        $updI = $pdo->prepare(
            'UPDATE intentos SET completado = 1, puntaje = :p, total_ejercicios = :t, fecha_completado = NOW()
             WHERE id_intento = :id'
        );
        $updI->execute(['p' => $puntaje, 't' => count($ejercicios), 'id' => $intento['id_intento']]);

        $updU = $pdo->prepare(
            'UPDATE usuarios SET puntos = puntos + :p, ejercicios_resueltos = ejercicios_resueltos + :n
             WHERE id_usuario = :id'
        );
        $updU->execute(['p' => $puntaje, 'n' => $aciertos, 'id' => $user['id_usuario']]);

        $ptsNow = $pdo->prepare('SELECT puntos FROM usuarios WHERE id_usuario = :id');
        $ptsNow->execute(['id' => $user['id_usuario']]);
        award_badges((int) $user['id_usuario'], (int) $ptsNow->fetchColumn());

        $pdo->commit();
        flash('ok', 'Guía enviada. Ganaste ' . $puntaje . ' puntos.');
        redirect('guia.php?id=' . $idGuia);
    } catch (Throwable $e) {
        $pdo->rollBack();
        flash('error', 'No se pudo guardar el intento. Revisa tus respuestas.');
        redirect('guia.php?id=' . $idGuia);
    }
}

$respuestas = [];
if ((int) $intento['completado'] === 1) {
    $rstmt = $pdo->prepare(
        'SELECT r.*, e.pregunta, e.opcion_1, e.opcion_2, e.opcion_3, e.opcion_4, e.respuesta_correcta, e.numero_orden
         FROM respuestas r
         JOIN ejercicios e ON e.id_ejercicio = r.id_ejercicio
         WHERE r.id_intento = :i ORDER BY e.numero_orden'
    );
    $rstmt->execute(['i' => $intento['id_intento']]);
    $respuestas = $rstmt->fetchAll();
}

$pageTitle = $guia['titulo'];
require __DIR__ . '/includes/header.php';
?>
<div class="container py-5" style="max-width:720px">
  <?php render_alerts(); ?>
  <p class="section-eyebrow mb-1"><?= e(categoria_label($guia['categoria'])) ?> · <?= e(dificultad_label($guia['dificultad'])) ?></p>
  <h1 class="h3 fw-bold"><?= e($guia['titulo']) ?></h1>

  <?php if (!empty($archivosList)): ?>
    <div class="card-edu p-3 mb-4">
      <h6 class="fw-bold"><i class="bi bi-paperclip"></i> Archivos adjuntos</h6>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($archivosList as $ar): ?>
          <a href="<?= e($ar['nombre_guardado']) ?>" target="_blank" class="badge-pill text-decoration-none">
            <?= file_icon($ar['tipo_mime']) ?> <?= e($ar['nombre_original']) ?>
            <small class="text-muted">(<?= format_bytes((int) $ar['tamanio']) ?>)</small>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if ((int) $intento['completado'] === 1): ?>
    <div class="card-edu p-4 mb-4">
      <h2 class="h5">Resultado</h2>
      <p class="mb-0">Puntaje: <strong><?= (int) $intento['puntaje'] ?></strong> de <?= (int) $intento['total_ejercicios'] * 10 ?></p>
    </div>
    <?php foreach ($respuestas as $r): ?>
      <div class="card-edu p-3 mb-3">
        <p class="fw-bold mb-2"><?= (int) $r['numero_orden'] ?>. <?= e($r['pregunta']) ?></p>
        <?php for ($i = 1; $i <= 4; $i++):
            $opt = $r['opcion_' . $i] ?? null;
            if ($opt === null || $opt === '') {
                continue;
            }
            $cls = '';
            if ((int) $r['respuesta_correcta'] === $i) {
                $cls = 'text-success';
            } elseif ((int) $r['opcion_seleccionada'] === $i) {
                $cls = 'text-danger';
            }
            ?>
          <div class="<?= $cls ?>"><?= $i ?>) <?= e($opt) ?></div>
        <?php endfor; ?>
      </div>
    <?php endforeach; ?>
    <a class="btn-edu-outline" href="estudiante.php">Volver</a>
  <?php else: ?>
    <form method="post">
      <?= csrf_field() ?>
      <?php foreach ($ejercicios as $ex): ?>
        <fieldset class="card-edu p-4 mb-3">
          <legend class="h6 fw-bold"><?= (int) $ex['numero_orden'] ?>. <?= e($ex['pregunta']) ?></legend>
          <?php for ($i = 1; $i <= 4; $i++):
              $opt = $ex['opcion_' . $i] ?? null;
              if ($opt === null || $opt === '') {
                  continue;
              }
              $id = 'e' . $ex['id_ejercicio'] . 'o' . $i;
              ?>
            <div class="form-check mb-2">
              <input class="form-check-input" type="radio" name="respuesta[<?= (int) $ex['id_ejercicio'] ?>]" id="<?= $id ?>" value="<?= $i ?>" required>
              <label class="form-check-label" for="<?= $id ?>"><?= e($opt) ?></label>
            </div>
          <?php endfor; ?>
        </fieldset>
      <?php endforeach; ?>
      <button class="btn btn-edu" type="submit">Enviar respuestas</button>
    </form>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
