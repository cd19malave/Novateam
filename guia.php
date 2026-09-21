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

$poderes = user_powerups((int) $user['id_usuario']);
$lives = user_lives((int) $user['id_usuario']);
$sinVidas = (int) $intento['completado'] !== 1 && (int) $lives['vidas'] < 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'verificar') {
    csrf_verify();
    header('Content-Type: application/json; charset=utf-8');
    if ($sinVidas || (int) $intento['completado'] === 1) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'error' => 'La guía ya no está disponible.']);
        exit;
    }
    $eid = (int) ($_POST['id_ejercicio'] ?? 0);
    $sel = (int) ($_POST['opcion'] ?? 0);
    $vstmt = $pdo->prepare(
        'SELECT respuesta_correcta FROM ejercicios WHERE id_ejercicio = :e AND id_guia = :g LIMIT 1'
    );
    $vstmt->execute(['e' => $eid, 'g' => $idGuia]);
    $correcta = $vstmt->fetchColumn();
    if ($correcta === false) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Ejercicio no encontrado']);
        exit;
    }
    echo json_encode([
        'ok' => true,
        'correcto' => ((int) $correcta === $sel),
        'correcta' => (int) $correcta,
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'comodin') {
    csrf_verify();
    header('Content-Type: application/json; charset=utf-8');
    if ($sinVidas || (int) $intento['completado'] === 1) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'error' => 'No disponible']);
        exit;
    }
    if ($poderes['comodines'] < 1) {
        echo json_encode(['ok' => false, 'error' => 'No tienes comodines']);
        exit;
    }
    $eid = (int) ($_POST['id_ejercicio'] ?? 0);
    $est = $pdo->prepare(
        'SELECT respuesta_correcta, opcion_1, opcion_2, opcion_3, opcion_4
         FROM ejercicios WHERE id_ejercicio = :e AND id_guia = :g LIMIT 1'
    );
    $est->execute(['e' => $eid, 'g' => $idGuia]);
    $ex = $est->fetch();
    if (!$ex) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Ejercicio no encontrado']);
        exit;
    }
    $correcta = (int) $ex['respuesta_correcta'];
    $existentes = [];
    for ($i = 1; $i <= 4; $i++) {
        if (($ex['opcion_' . $i] ?? '') !== null && $ex['opcion_' . $i] !== '') {
            $existentes[] = $i;
        }
    }
    $malas = array_values(array_filter($existentes, static fn(int $i): bool => $i !== $correcta));
    shuffle($malas);
    $ocultar = array_slice($malas, 0, 2);
    $pdo->prepare('UPDATE usuarios SET comodines_50 = comodines_50 - 1 WHERE id_usuario = :id AND comodines_50 > 0')
        ->execute(['id' => $user['id_usuario']]);
    echo json_encode(['ok' => true, 'ocultar' => $ocultar, 'restantes' => max(0, $poderes['comodines'] - 1)]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'enviar' && (int) $intento['completado'] !== 1) {
    csrf_verify();
    if ($sinVidas) {
        flash('error', 'Te quedaste sin vidas. Compra o espera a que se recarguen.');
        redirect('tienda.php');
    }
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

    $usarDoble = isset($_POST['usar_doble']) && $poderes['doble'] > 0;

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

        if ($usarDoble) {
            $puntaje *= 2;
            $pdo->prepare('UPDATE usuarios SET doble_puntos = doble_puntos - 1 WHERE id_usuario = :id')
                ->execute(['id' => $user['id_usuario']]);
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

        $fallos = count($ejercicios) - $aciertos;
        if ($fallos > 0) {
            consume_lives((int) $user['id_usuario'], $fallos);
        }

        $extra = $usarDoble ? ' ¡Doble puntos aplicado! (x2)' : '';
        flash('ok', 'Guía enviada. Ganaste ' . $puntaje . ' puntos.' . $extra);
        redirect('guia.php?id=' . $idGuia);
    } catch (Throwable $e) {
        $pdo->rollBack();
        flash('error', 'No se pudo guardar el intento. Revisa tus respuestas.');
        redirect('guia.php?id=' . $idGuia);
    }
}

$respuestas = [];
$aciertos = 0;
if ((int) $intento['completado'] === 1) {
    $rstmt = $pdo->prepare(
        'SELECT r.*, e.pregunta, e.opcion_1, e.opcion_2, e.opcion_3, e.opcion_4, e.respuesta_correcta, e.numero_orden
         FROM respuestas r
         JOIN ejercicios e ON e.id_ejercicio = r.id_ejercicio
         WHERE r.id_intento = :i ORDER BY e.numero_orden'
    );
    $rstmt->execute(['i' => $intento['id_intento']]);
    $respuestas = $rstmt->fetchAll();
    foreach ($respuestas as $r) {
        if ((int) $r['es_correcta'] === 1) {
            $aciertos++;
        }
    }
}

$total = count($ejercicios);
$maxPuntos = $total * 10;
$pct = $maxPuntos > 0 ? (int) round(((int) $intento['puntaje'] / $maxPuntos) * 100) : 0;

$checkUrl = 'guia.php?id=' . $idGuia;
$tabNow = current_tab();
if ($tabNow !== null) {
    $checkUrl .= '&tab=' . urlencode($tabNow);
}

$pageTitle = $guia['titulo'];
require __DIR__ . '/includes/header.php';
?>
<div class="container py-4" style="max-width:760px">
  <?php render_alerts(); ?>

  <?php if ((int) $intento['completado'] === 1): ?>
    <div class="result-hero mb-4">
      <div class="score-donut" style="--pct:<?= $pct ?>"><span><?= $pct ?>%</span></div>
      <div class="result-info">
        <span class="section-eyebrow"><?= e(categoria_label($guia['categoria'])) ?></span>
        <h1 class="h4 fw-bold mb-1"><?= e($guia['titulo']) ?></h1>
        <p class="result-line">
          <strong><?= (int) $intento['puntaje'] ?></strong> / <?= $maxPuntos ?> puntos
          · <strong><?= $aciertos ?></strong> / <?= $total ?> correctas
        </p>
        <p class="result-msg mb-0">
          <?php if ($pct >= 90): ?>
            ¡Excelente trabajo! Dominaste esta guía.
          <?php elseif ($pct >= 60): ?>
            ¡Buen trabajo! Puedes repasar las que fallaste.
          <?php else: ?>
            Sigue practicando, cada intento te acerca a la meta.
          <?php endif; ?>
        </p>
      </div>
    </div>

    <?php if (!empty($archivosList)): ?>
      <div class="card-edu p-3 mb-4">
        <h6 class="fw-bold"><i class="bi bi-paperclip"></i> Archivos de apoyo</h6>
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

    <h2 class="h6 fw-bold mb-3">Revisión</h2>
    <?php foreach ($respuestas as $r): ?>
      <div class="card-edu p-3 mb-3 review-item <?= (int) $r['es_correcta'] === 1 ? 'review-ok' : 'review-bad' ?>">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <p class="fw-bold mb-2"><?= (int) $r['numero_orden'] ?>. <?= e($r['pregunta']) ?></p>
          <span class="review-badge"><?= (int) $r['es_correcta'] === 1 ? '<i class="bi bi-check-lg"></i> Correcta' : '<i class="bi bi-x-lg"></i> Incorrecta' ?></span>
        </div>
        <?php for ($i = 1; $i <= 4; $i++):
            $opt = $r['opcion_' . $i] ?? null;
            if ($opt === null || $opt === '') {
                continue;
            }
            $cls = '';
            $tag = '';
            if ((int) $r['respuesta_correcta'] === $i) {
                $cls = 'text-success fw-bold';
                $tag = ' <i class="bi bi-check-circle-fill"></i>';
            } elseif ((int) $r['opcion_seleccionada'] === $i) {
                $cls = 'text-danger';
                $tag = ' <i class="bi bi-x-circle-fill"></i>';
            }
            ?>
          <div class="<?= $cls ?>"><?= $i ?>) <?= e($opt) ?><?= $tag ?></div>
        <?php endfor; ?>
      </div>
    <?php endforeach; ?>

    <div class="d-flex gap-2 flex-wrap">
      <a class="btn btn-edu" href="estudiante.php"><i class="bi bi-house-door"></i> Volver al inicio</a>
      <a class="btn-edu-outline" href="progreso.php">Ver mi progreso</a>
    </div>

  <?php else: ?>

    <?php if ($sinVidas): ?>
      <div class="card-edu p-4 text-center shop-empty">
        <span class="mascot" aria-hidden="true">💔</span>
        <h2 class="h5 fw-bold mt-2">Te quedaste sin vidas</h2>
        <p class="text-muted mb-2">
          Las vidas se recargan solas: 1 cada <?= lives_regen_hours() ?> horas.
          <?php if ($lives['segundos'] > 0): ?>
            Próxima vida en <strong id="livesCountdown"><?= e(gmdate('H:i:s', $lives['segundos'])) ?></strong>.
          <?php endif; ?>
        </p>
        <div class="d-flex gap-2 justify-content-center flex-wrap">
          <a class="btn btn-edu" href="tienda.php"><i class="bi bi-bag-heart-fill"></i> Ir a la tienda</a>
          <a class="btn-edu-outline" href="estudiante.php">Volver</a>
        </div>
      </div>
    <?php else: ?>

    <?php if (!empty($archivosList)): ?>
      <details class="card-edu p-3 mb-3">
        <summary class="fw-bold" style="cursor:pointer"><i class="bi bi-paperclip"></i> Archivos de apoyo</summary>
        <div class="d-flex flex-wrap gap-2 mt-2">
          <?php foreach ($archivosList as $ar): ?>
            <a href="<?= e($ar['nombre_guardado']) ?>" target="_blank" class="badge-pill text-decoration-none">
              <?= file_icon($ar['tipo_mime']) ?> <?= e($ar['nombre_original']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </details>
    <?php endif; ?>

    <div class="quiz" id="quiz"
         data-check-url="<?= e($checkUrl) ?>"
         data-total="<?= $total ?>"
         data-lives="<?= (int) $lives['vidas'] ?>"
         data-lives-max="<?= (int) $lives['max'] ?>"
         data-comodines="<?= (int) $poderes['comodines'] ?>">
      <div class="quiz-top">
        <a class="quiz-close" href="estudiante.php" aria-label="Salir del quiz"><i class="bi bi-x-lg"></i></a>
        <div class="quiz-bar"><span class="quiz-bar-fill" id="quizBar" style="width:0%"></span></div>
        <span class="quiz-count" id="quizCount">1/<?= $total ?></span>
      </div>

      <div class="quiz-hud">
        <span class="quiz-lives" id="quizLives" aria-label="Vidas"></span>
        <div class="quiz-powers">
          <?php if ($poderes['comodines'] > 0): ?>
            <button type="button" class="quiz-power" id="quizComodin" title="Comodín 50/50: elimina 2 respuestas incorrectas">
              <i class="bi bi-patch-question-fill"></i> 50/50
              <span class="qp-count" id="quizComodinCount"><?= (int) $poderes['comodines'] ?></span>
            </button>
          <?php endif; ?>
          <?php if ($poderes['doble'] > 0): ?>
            <label class="quiz-power quiz-power--toggle" title="Duplica los puntos de este quiz">
              <input type="checkbox" name="usar_doble" value="1" id="quizDoble" form="quizForm" hidden>
              <i class="bi bi-lightning-charge-fill"></i> x2
              <span class="qp-count"><?= (int) $poderes['doble'] ?></span>
            </label>
          <?php endif; ?>
        </div>
      </div>

      <form method="post" id="quizForm" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="accion" value="enviar">
        <?php foreach ($ejercicios as $idx => $ex): ?>
          <section class="quiz-step<?= $idx === 0 ? ' active' : '' ?>" data-index="<?= $idx ?>" data-eid="<?= (int) $ex['id_ejercicio'] ?>">
            <p class="quiz-eyebrow">Pregunta <?= $idx + 1 ?> de <?= $total ?></p>
            <h2 class="quiz-question"><?= e($ex['pregunta']) ?></h2>
            <div class="quiz-options">
              <?php for ($i = 1; $i <= 4; $i++):
                  $opt = $ex['opcion_' . $i] ?? null;
                  if ($opt === null || $opt === '') {
                      continue;
                  }
                  $id = 'e' . $ex['id_ejercicio'] . 'o' . $i;
                  ?>
                <label class="quiz-option" for="<?= $id ?>">
                  <input class="quiz-radio" type="radio"
                         name="respuesta[<?= (int) $ex['id_ejercicio'] ?>]"
                         id="<?= $id ?>" value="<?= $i ?>">
                  <span class="quiz-opt-key"><?= chr(64 + $i) ?></span>
                  <span class="quiz-opt-text"><?= e($opt) ?></span>
                  <span class="quiz-opt-mark"><i class="bi bi-check-lg"></i></span>
                </label>
              <?php endfor; ?>
            </div>
          </section>
        <?php endforeach; ?>
      </form>

      <div class="quiz-footer">
        <div class="quiz-feedback" id="quizFeedback" hidden>
          <span class="quiz-feedback-ico" id="quizFeedbackIco"></span>
          <span class="quiz-feedback-text" id="quizFeedbackText"></span>
        </div>
        <button class="btn btn-edu btn-lg w-100" type="button" id="quizAction" disabled>Comprobar</button>
      </div>
    </div>
    <?php endif; ?>

  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
