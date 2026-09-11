<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    $correo = filter_var(trim((string) ($_POST['correo'] ?? '')), FILTER_VALIDATE_EMAIL);
    $institucion = trim((string) ($_POST['institucion'] ?? ''));
    $mensaje = trim((string) ($_POST['mensaje'] ?? ''));

    if ($nombre === '' || !$correo || $mensaje === '' || mb_strlen($mensaje) > 2000) {
        flash('error', 'Completa nombre, correo válido y un mensaje (máx. 2000 caracteres).');
        redirect('index.php#contacto');
    }

    $stmt = db()->prepare(
        'INSERT INTO solicitudes_contacto (nombre, correo, institucion, mensaje)
         VALUES (:n, :c, :i, :m)'
    );
    $stmt->execute([
        'n' => mb_substr($nombre, 0, 120),
        'c' => $correo,
        'i' => mb_substr($institucion, 0, 150),
        'm' => $mensaje,
    ]);

    $contactEmail = env('CONTACTO_EMAIL', 'novateamrecuperacion@gmail.com') ?? 'novateamrecuperacion@gmail.com';
    $body = '<div style="font-family:Arial,Helvetica,sans-serif;background:#17181A;padding:24px;border-radius:16px;max-width:520px;margin:0 auto">'
        . '<div style="background:#1F2126;border:1px solid #2A2D33;border-radius:14px;padding:20px;color:#E8E8F2">'
        . '<h1 style="font-size:16px;margin:0 0 12px;color:#fff">Nueva solicitud de institución</h1>'
        . '<p style="margin:0 0 6px;font-size:14px"><strong>Nombre:</strong> ' . e($nombre) . '</p>'
        . '<p style="margin:0 0 6px;font-size:14px"><strong>Correo:</strong> ' . e($correo) . '</p>'
        . '<p style="margin:0 0 6px;font-size:14px"><strong>Institución:</strong> ' . e($institucion) . '</p>'
        . '<p style="margin:0 0 6px;font-size:14px"><strong>Mensaje:</strong></p>'
        . '<p style="margin:0;font-size:14px;line-height:1.6">' . nl2br(e($mensaje)) . '</p>'
        . '</div></div>';
    send_email($contactEmail, 'NovaTeam: nueva solicitud de contacto', $body);

    flash('ok', 'Recibimos tu solicitud. NovaTeam te contactará pronto.');
    redirect('index.php#contacto');
}

$pageTitle = 'Aprende jugando';
require __DIR__ . '/includes/header.php';
?>
<section class="hero" id="inicio">
  <div class="container text-center">
    <span class="section-eyebrow">NovaTeam presenta</span>
    <h1 class="mt-2">Aprende <span class="accent">Matemáticas</span> e <span class="accent2">Inglés</span><br>jugando con NovaTeam 🚀</h1>
    <p class="lead mt-3 mx-auto" style="max-width:640px">
      Recurso didáctico multimedia para estudiantes de cuarto de primaria: actividades,
      juegos y retos interactivos que se adaptan al ritmo de cada niño.
    </p>
    <div class="d-flex justify-content-center gap-3 mt-4 flex-wrap">
      <a href="descargar.php" class="btn btn-edu btn-lg"><i class="bi bi-download"></i> Descargar app</a>
      <a href="registro.php" class="btn-edu-outline"><i class="bi bi-person-plus"></i> Empezar el piloto</a>
      <a href="#como-funciona" class="btn-edu-outline">Ver cómo funciona</a>
    </div>
  </div>
</section>

<section id="problema" class="container py-5">
  <div class="row align-items-center g-4">
    <div class="col-lg-6">
      <span class="section-eyebrow">¿Por qué NovaTeam?</span>
      <h2 class="fw-bold mt-2">Un apoyo para el aula, pensado desde el aula</h2>
      <p class="text-muted">
        Muchos estudiantes de cuarto de primaria tienen dificultades para comprender
        lógica matemática e inglés cuando solo se usan métodos tradicionales.
      </p>
      <ul class="list-unstyled problem-list">
        <li><i class="bi bi-check-circle-fill"></i>Pocos materiales interactivos para reforzar en casa.</li>
        <li><i class="bi bi-check-circle-fill"></i>Ritmos de aprendizaje distintos en cada estudiante.</li>
        <li><i class="bi bi-check-circle-fill"></i>Niños acostumbrados a pantallas, sonidos e imágenes.</li>
        <li><i class="bi bi-check-circle-fill"></i>Lógica matemática e inglés: bases difíciles de enseñar solo con teoría.</li>
      </ul>
    </div>
    <div class="col-lg-6">
      <div class="card-edu p-4">
        <div class="row g-3 text-center">
          <div class="col-6 stat-box"><h2>2</h2><p class="mb-0 text-muted">Áreas de aprendizaje</p></div>
          <div class="col-6 stat-box"><h2>4°</h2><p class="mb-0 text-muted">Grado objetivo</p></div>
          <div class="col-6 stat-box"><h2>100%</h2><p class="mb-0 text-muted">Software libre</p></div>
          <div class="col-6 stat-box"><h2>1</h2><p class="mb-0 text-muted">Piloto por institución</p></div>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="features" class="container py-5">
  <div class="text-center mb-4">
    <span class="section-eyebrow">Lo que incluye</span>
    <h2 class="fw-bold mt-2">¿Por qué NovaTeam?</h2>
  </div>
  <div class="row g-4">
    <div class="col-md-4">
      <div class="card-edu p-4 h-100">
        <div class="card-icon"><i class="bi bi-controller"></i></div>
        <h5>Aprendizaje gamificado</h5>
        <p class="text-muted mb-0">Puntos, niveles e insignias por resolver retos de lógica matemática e inglés.</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card-edu p-4 h-100">
        <div class="card-icon"><i class="bi bi-mortarboard"></i></div>
        <h5>Profesores empoderados</h5>
        <p class="text-muted mb-0">Crean guías, las publican cuando quieran y ven el avance de cada estudiante.</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card-edu p-4 h-100">
        <div class="card-icon"><i class="bi bi-graph-up-arrow"></i></div>
        <h5>Progreso visible</h5>
        <p class="text-muted mb-0">Cada estudiante y su docente consultan puntajes, aciertos y avance en tiempo real.</p>
      </div>
    </div>
  </div>
</section>

<section id="materias" class="container py-5">
  <div class="text-center mb-4">
    <span class="section-eyebrow">Contenido</span>
    <h2 class="fw-bold mt-2">Materias disponibles</h2>
  </div>
  <div class="row g-4 justify-content-center">
    <div class="col-md-5">
      <div class="card-edu p-4 text-center">
        <h3>🧮 Matemáticas</h3>
        <p class="text-muted mb-0">Lógica, secuencias, operaciones básicas, porcentajes y comparaciones.</p>
      </div>
    </div>
    <div class="col-md-5">
      <div class="card-edu p-4 text-center">
        <h3>🗣️ Inglés</h3>
        <p class="text-muted mb-0">Vocabulario, colores, animales, saludos y gramática básica.</p>
      </div>
    </div>
  </div>
</section>

<section id="como-funciona" class="container py-5">
  <div class="text-center mb-4">
    <span class="section-eyebrow">Implementación</span>
    <h2 class="fw-bold mt-2">Así se pone en marcha en tu institución</h2>
  </div>
  <div class="row g-4">
    <div class="col-md-3">
      <div class="d-flex align-items-start gap-3">
        <div class="step-num">1</div>
        <div><h6 class="fw-bold mb-1">Presentación</h6><p class="text-muted small mb-0">Reunión con directivos para revisar alcance y condiciones.</p></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="d-flex align-items-start gap-3">
        <div class="step-num">2</div>
        <div><h6 class="fw-bold mb-1">Modalidad</h6><p class="text-muted small mb-0">Piloto con un grupo o implementación institucional completa.</p></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="d-flex align-items-start gap-3">
        <div class="step-num">3</div>
        <div><h6 class="fw-bold mb-1">Orientación docente</h6><p class="text-muted small mb-0">Jornada básica de uso del recurso para el personal docente.</p></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="d-flex align-items-start gap-3">
        <div class="step-num">4</div>
        <div><h6 class="fw-bold mb-1">Evaluación</h6><p class="text-muted small mb-0">Medición del impacto y ajustes según retroalimentación.</p></div>
      </div>
    </div>
  </div>
</section>

<section id="equipo" class="container py-5">
  <div class="text-center mb-4">
    <span class="section-eyebrow">NovaTeam</span>
    <h2 class="fw-bold mt-2">Equipo de desarrollo</h2>
  </div>
  <div class="equipo-grid">
    <div class="card-edu p-4 text-center"><div class="equipo-avatar"><i class="bi bi-person-fill"></i></div><p class="fw-bold mb-0 small">Carlos Damian Malave Diaz</p></div>
    <div class="card-edu p-4 text-center"><div class="equipo-avatar"><i class="bi bi-person-fill"></i></div><p class="fw-bold mb-0 small">Danna Alexandra Ortega Saavedra</p></div>
    <div class="card-edu p-4 text-center"><div class="equipo-avatar"><i class="bi bi-person-fill"></i></div><p class="fw-bold mb-0 small">Giselle Nayeli Jaimes Galvis</p></div>
    <div class="card-edu p-4 text-center"><div class="equipo-avatar"><i class="bi bi-person-fill"></i></div><p class="fw-bold mb-0 small">Jhoan Andres Ortiz Galvis</p></div>
    <div class="card-edu p-4 text-center"><div class="equipo-avatar"><i class="bi bi-person-fill"></i></div><p class="fw-bold mb-0 small">Carlos Andrey Delgado Castañeda</p></div>
  </div>
</section>

<section id="contacto" class="container py-5">
  <div class="card-edu contacto-grad p-4 p-md-5">
    <div class="row g-4 align-items-center">
      <div class="col-lg-5">
        <h2 class="fw-bold">¿Listo para llevar NovaTeam a tu institución?</h2>
        <p class="mb-0">Déjanos tus datos y coordinamos una reunión para la propuesta técnica.</p>
      </div>
      <div class="col-lg-7">
        <?php render_alerts(); ?>
        <form method="post" class="row g-3">
          <?= csrf_field() ?>
          <div class="col-md-6">
            <label class="form-label" for="nombre">Nombre</label>
            <input class="form-control" id="nombre" name="nombre" required maxlength="120">
          </div>
          <div class="col-md-6">
            <label class="form-label" for="correo">Correo</label>
            <input class="form-control" id="correo" name="correo" type="email" required maxlength="150">
          </div>
          <div class="col-12">
            <label class="form-label" for="institucion">Institución</label>
            <input class="form-control" id="institucion" name="institucion" maxlength="150">
          </div>
          <div class="col-12">
            <label class="form-label" for="mensaje">Mensaje</label>
            <textarea class="form-control" id="mensaje" name="mensaje" rows="3" required maxlength="2000"></textarea>
          </div>
          <div class="col-12">
            <button class="btn btn-light fw-bold rounded-pill px-4" type="submit">Enviar solicitud</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
