<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_role('profesor');

$user = current_user();

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

    $materia = (string) $user['materia'];
    if ($titulo === '' || mb_strlen($titulo) > 150) {
        flash('error', 'El título es obligatorio (máx. 150).');
        redirect('crear-guia.php');
    }
    if (!in_array($dificultad, ['facil', 'medio', 'dificil'], true)) {
        flash('error', 'Dificultad no válida.');
        redirect('crear-guia.php');
    }
    if ($dias < 1 || $dias > 365) {
        $dias = 30;
    }
    if (!is_array($preguntas) || count($preguntas) < 1) {
        flash('error', 'Agrega al menos un ejercicio.');
        redirect('crear-guia.php');
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $insG = $pdo->prepare(
            'INSERT INTO guias (titulo, categoria, dificultad, modo_creacion, estado, id_profesor, fecha_expiracion)
             VALUES (:t, :cat, :dif, :m, :est, :p, DATE_ADD(NOW(), INTERVAL ' . $dias . ' DAY))'
        );
        $insG->execute([
            't' => $titulo,
            'cat' => $materia,
            'dif' => $dificultad,
            'm' => 'manual',
            'est' => 'borrador',
            'p' => $user['id_usuario'],
        ]);
        $idGuia = (int) $pdo->lastInsertId();

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
            if ($preg === '' || $a === '' || $b === '') {
                continue;
            }
            if ($corr < 1 || $corr > 4) {
                throw new RuntimeException('Respuesta correcta inválida');
            }
            $orden++;
            $insE->execute([
                'g' => $idGuia,
                'n' => $orden,
                'p' => mb_substr($preg, 0, 2000),
                'a' => mb_substr($a, 0, 255),
                'b' => mb_substr($b, 0, 255),
                'c' => $c === '' ? null : mb_substr($c, 0, 255),
                'd' => $d === '' ? null : mb_substr($d, 0, 255),
                'ok' => $corr,
            ]);
        }
        if ($orden < 1) {
            throw new RuntimeException('Sin ejercicios válidos');
        }

        if (!empty($_FILES['archivos'])) {
            foreach ($_FILES['archivos']['name'] as $idx => $fname) {
                if ($_FILES['archivos']['error'][$idx] !== UPLOAD_ERR_OK) continue;
                $fakeFile = [
                    'name'     => $_FILES['archivos']['name'][$idx],
                    'type'     => $_FILES['archivos']['type'][$idx],
                    'tmp_name' => $_FILES['archivos']['tmp_name'][$idx],
                    'error'    => $_FILES['archivos']['error'][$idx],
                    'size'     => $_FILES['archivos']['size'][$idx],
                ];
                upload_file($fakeFile, $user['id_usuario'], $idGuia);
            }
        }

        $pdo->commit();
        flash('ok', 'Guía guardada como borrador. Publícala cuando esté lista.');
        redirect('profesor.php');
    } catch (Throwable $e) {
        $pdo->rollBack();
        flash('error', 'No se pudo crear la guía. Revisa los ejercicios.');
        redirect('crear-guia.php');
    }
}

$pageTitle = 'Nueva guía';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-5" style="max-width:760px">
  <h1 class="h3 fw-bold">Crear guía de <?= e(categoria_label((string) $user['materia'])) ?></h1>
  <?php render_alerts(); ?>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="card-edu p-4 mb-4">
      <label class="form-label" for="titulo">Título</label>
      <input class="form-control mb-3" id="titulo" name="titulo" required maxlength="150">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label" for="dificultad">Dificultad</label>
          <select class="form-select" id="dificultad" name="dificultad">
            <option value="facil">Fácil</option>
            <option value="medio">Medio</option>
            <option value="dificil">Difícil</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label" for="dias">Días hasta que expire</label>
          <input class="form-control" type="number" id="dias" name="dias" value="30" min="1" max="365">
        </div>
        <div class="col-md-4 d-flex align-items-end">
          <div class="w-100">
            <label class="form-label">Archivos adjuntos (opcional)</label>
            <input class="form-control form-control-sm" type="file" name="archivos[]" multiple accept="image/*,.pdf,.mp3,.mp4,.docx">
          </div>
        </div>
      </div>
    </div>

    <div class="card-edu p-4 mb-4" style="background:linear-gradient(135deg,#e8f5e9,#fff3e0);border:2px dashed var(--edu-primary);">
      <div class="d-flex align-items-center gap-3 mb-3">
        <div style="font-size:2rem;">🤖</div>
        <div>
          <h6 class="fw-bold mb-0">Generar ejercicios con IA</h6>
          <small class="text-muted">Usa Google Gemini para crear ejercicios automáticamente</small>
        </div>
      </div>
      <div class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label small">Tema (opcional)</label>
          <input class="form-control form-control-sm" id="ia-tema" placeholder="Ej: fracciones, animales...">
        </div>
        <div class="col-md-3">
          <label class="form-label small">Cantidad</label>
          <select class="form-select form-select-sm" id="ia-cantidad">
            <option value="3">3 ejercicios</option>
            <option value="5" selected>5 ejercicios</option>
            <option value="7">7 ejercicios</option>
            <option value="10">10 ejercicios</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small">Dificultad</label>
          <select class="form-select form-select-sm" id="ia-dificultad">
            <option value="facil">Fácil</option>
            <option value="medio">Medio</option>
            <option value="dificil">Difícil</option>
          </select>
        </div>
        <div class="col-md-2">
          <button type="button" class="btn btn-edu btn-sm w-100" id="btn-generar-ia">
            <i class="bi bi-magic"></i> Generar
          </button>
        </div>
      </div>
      <div id="ia-status" class="mt-2 small" style="display:none;"></div>
    </div>

    <div id="ejercicios-wrap">
      <div class="ejercicio-block card-edu p-3 mb-3">
        <h6 class="fw-bold">Ejercicio 1</h6>
        <label class="form-label">Pregunta</label>
        <input class="form-control mb-2" name="pregunta[]" required maxlength="500">
        <div class="row g-2">
          <div class="col-md-6"><input class="form-control" name="opcion_1[]" placeholder="Opción 1" required maxlength="255"></div>
          <div class="col-md-6"><input class="form-control" name="opcion_2[]" placeholder="Opción 2" required maxlength="255"></div>
          <div class="col-md-6"><input class="form-control" name="opcion_3[]" placeholder="Opción 3" maxlength="255"></div>
          <div class="col-md-6"><input class="form-control" name="opcion_4[]" placeholder="Opción 4" maxlength="255"></div>
        </div>
        <label class="form-label mt-2">Respuesta correcta</label>
        <select class="form-select" name="correcta[]" required>
          <option value="1">Opción 1</option>
          <option value="2">Opción 2</option>
          <option value="3">Opción 3</option>
          <option value="4">Opción 4</option>
        </select>
      </div>
    </div>
    <button type="button" class="btn-edu-outline mb-3" id="add-ejercicio">+ Otro ejercicio</button>
    <div>
      <button class="btn btn-edu" type="submit">Guardar borrador</button>
    </div>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
