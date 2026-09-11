<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_role('profesor');

$user = current_user();
$pdo = db();
$idGuia = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM guias WHERE id_guia = :id AND id_profesor = :p LIMIT 1');
$stmt->execute(['id' => $idGuia, 'p' => $user['id_usuario']]);
$guia = $stmt->fetch();
if (!$guia) {
    flash('error', 'Guía no encontrada.');
    redirect('profesor.php');
}

$ejStmt = $pdo->prepare('SELECT * FROM ejercicios WHERE id_guia = :id ORDER BY numero_orden');
$ejStmt->execute(['id' => $idGuia]);
$ejercicios = $ejStmt->fetchAll();

$archStmt = $pdo->prepare('SELECT * FROM archivos_adjuntos WHERE id_guia = :id');
$archStmt->execute(['id' => $idGuia]);
$archivos = $archStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $titulo = trim((string) ($_POST['titulo'] ?? ''));
    $dificultad = (string) ($_POST['dificultad'] ?? 'facil');
    $dias = (int) ($_POST['dias'] ?? 30);
    $preguntas = $_POST['pregunta'] ?? [];
    $o1 = $_POST['opcion_1'] ?? [];
    $o2 = $_POST['opcion_2'] ?? [];
    $o3 = $_POST['opcion_3'] ?? [];
    $o4 = $_POST['opcion_4'] ?? [];
    $ok = $_POST['correcta'] ?? [];

    if ($titulo === '' || mb_strlen($titulo) > 150) {
        flash('error', 'El título es obligatorio (máx. 150).');
        redirect('editar-guia.php?id=' . $idGuia);
    }
    if (!in_array($dificultad, ['facil', 'medio', 'dificil'], true)) {
        $dificultad = 'facil';
    }
    if ($dias < 1 || $dias > 365) $dias = 30;
    if (!is_array($preguntas) || count($preguntas) < 1) {
        flash('error', 'Agrega al menos un ejercicio.');
        redirect('editar-guia.php?id=' . $idGuia);
    }

    $pdo->beginTransaction();
    try {
        $upd = $pdo->prepare(
            'UPDATE guias SET titulo = :t, dificultad = :dif, fecha_expiracion = DATE_ADD(fecha_creacion, INTERVAL ' . $dias . ' DAY) WHERE id_guia = :id AND id_profesor = :p'
        );
        $upd->execute(['t' => $titulo, 'dif' => $dificultad, 'id' => $idGuia, 'p' => $user['id_usuario']]);

        $pdo->prepare('DELETE FROM ejercicios WHERE id_guia = :id')->execute(['id' => $idGuia]);

        $insE = $pdo->prepare(
            'INSERT INTO ejercicios (id_guia, numero_orden, pregunta, opcion_1, opcion_2, opcion_3, opcion_4, respuesta_correcta)
             VALUES (:g, :n, :p, :a, :b, :c, :d, :ok)'
        );
        $orden = 0;
        foreach ($preguntas as $i => $preg) {
            $preg = trim((string) $preg);
            $a = trim((string) ($o1[$i] ?? ''));
            $b = trim((string) ($o2[$i] ?? ''));
            $c = trim((string) ($o3[$i] ?? ''));
            $d = trim((string) ($o4[$i] ?? ''));
            $corr = (int) ($ok[$i] ?? 0);
            if ($preg === '' || $a === '' || $b === '') continue;
            if ($corr < 1 || $corr > 4) $corr = 1;
            $orden++;
            $insE->execute([
                'g' => $idGuia, 'n' => $orden, 'p' => mb_substr($preg, 0, 2000),
                'a' => mb_substr($a, 0, 255), 'b' => mb_substr($b, 0, 255),
                'c' => $c === '' ? null : mb_substr($c, 0, 255),
                'd' => $d === '' ? null : mb_substr($d, 0, 255), 'ok' => $corr,
            ]);
        }
        if ($orden < 1) throw new RuntimeException('Sin ejercicios válidos');

        if (!empty($_FILES['archivos']['name'][0])) {
            foreach ($_FILES['archivos']['name'] as $idx => $fname) {
                if ($_FILES['archivos']['error'][$idx] !== UPLOAD_ERR_OK) continue;
                $fakeFile = [
                    'name' => $_FILES['archivos']['name'][$idx],
                    'type' => $_FILES['archivos']['type'][$idx],
                    'tmp_name' => $_FILES['archivos']['tmp_name'][$idx],
                    'error' => $_FILES['archivos']['error'][$idx],
                    'size' => $_FILES['archivos']['size'][$idx],
                ];
                upload_file($fakeFile, $user['id_usuario'], $idGuia);
            }
        }

        $pdo->commit();
        flash('ok', 'Guía actualizada.');
        redirect('profesor.php');
    } catch (Throwable $e) {
        $pdo->rollBack();
        flash('error', 'Error al actualizar la guía.');
        redirect('editar-guia.php?id=' . $idGuia);
    }
}

$pageTitle = 'Editar guía';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-5" style="max-width:760px">
  <h1 class="h3 fw-bold">Editar guía: <?= e($guia['titulo']) ?></h1>
  <p class="text-muted small">Estado: <span class="badge-pill"><?= e($guia['estado']) ?></span> · <?= e(categoria_label($guia['categoria'])) ?> · <?= e(dificultad_label($guia['dificultad'])) ?></p>
  <?php render_alerts(); ?>

  <?php if (!empty($archivos)): ?>
    <div class="card-edu p-3 mb-4">
      <h6 class="fw-bold"><i class="bi bi-paperclip"></i> Archivos actuales</h6>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($archivos as $ar): ?>
          <span class="badge-pill"><?= file_icon($ar['tipo_mime']) ?> <?= e($ar['nombre_original']) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="card-edu p-4 mb-4">
      <label class="form-label" for="titulo">Título</label>
      <input class="form-control mb-3" id="titulo" name="titulo" required maxlength="150" value="<?= e($guia['titulo']) ?>">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label" for="dificultad">Dificultad</label>
          <select class="form-select" id="dificultad" name="dificultad">
            <option value="facil" <?= $guia['dificultad'] === 'facil' ? 'selected' : '' ?>>Fácil</option>
            <option value="medio" <?= $guia['dificultad'] === 'medio' ? 'selected' : '' ?>>Medio</option>
            <option value="dificil" <?= $guia['dificultad'] === 'dificil' ? 'selected' : '' ?>>Difícil</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="dias">Días para expirar</label>
          <input class="form-control" type="number" id="dias" name="dias" value="<?= $dias ?? 30 ?>" min="1" max="365">
        </div>
      </div>
      <div class="mt-3">
        <label class="form-label">Agregar más archivos</label>
        <input class="form-control" type="file" name="archivos[]" multiple accept="image/*,.pdf,.mp3,.mp4,.docx">
      </div>
    </div>

    <div id="ejercicios-wrap">
      <?php foreach ($ejercicios as $idx => $ej): ?>
        <div class="ejercicio-block card-edu p-3 mb-3">
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="fw-bold">Ejercicio <?= $idx + 1 ?></h6>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.ejercicio-block').remove();renumberEjercicios();">✕</button>
          </div>
          <label class="form-label">Pregunta</label>
          <input class="form-control mb-2" name="pregunta[]" required maxlength="500" value="<?= e($ej['pregunta']) ?>">
          <div class="row g-2">
            <div class="col-md-6"><input class="form-control" name="opcion_1[]" placeholder="Opción 1" required maxlength="255" value="<?= e($ej['opcion_1']) ?>"></div>
            <div class="col-md-6"><input class="form-control" name="opcion_2[]" placeholder="Opción 2" required maxlength="255" value="<?= e($ej['opcion_2']) ?>"></div>
            <div class="col-md-6"><input class="form-control" name="opcion_3[]" placeholder="Opción 3" maxlength="255" value="<?= e($ej['opcion_3'] ?? '') ?>"></div>
            <div class="col-md-6"><input class="form-control" name="opcion_4[]" placeholder="Opción 4" maxlength="255" value="<?= e($ej['opcion_4'] ?? '') ?>"></div>
          </div>
          <label class="form-label mt-2">Respuesta correcta</label>
          <select class="form-select" name="correcta[]" required>
            <?php for ($i = 1; $i <= 4; $i++): ?>
              <option value="<?= $i ?>" <?= (int) $ej['respuesta_correcta'] === $i ? 'selected' : '' ?>>Opción <?= $i ?></option>
            <?php endfor; ?>
          </select>
        </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="btn-edu-outline mb-3" id="add-ejercicio">+ Otro ejercicio</button>
    <div class="d-flex gap-2">
      <button class="btn btn-edu" type="submit">Guardar cambios</button>
      <a class="btn btn-edu-outline" href="profesor.php">Cancelar</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
