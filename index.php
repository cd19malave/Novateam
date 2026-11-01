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
    $body = '<div style="font-family:\'Instrument Sans\',Arial,Helvetica,sans-serif;background:#F5EFE4;padding:24px;border-radius:16px;max-width:520px;margin:0 auto">'
        . '<div style="background:#FFFDF8;border:2px solid #161A1D;border-radius:14px;padding:20px;color:#161A1D">'
        . '<h1 style="font-family:Georgia,\'Times New Roman\',serif;font-size:18px;margin:0 0 12px;color:#161A1D">Nueva solicitud de institución</h1>'
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

$pageTitle = 'Aprender practicando';
$bodyClass = 'landing-page';
$themeColor = '#FFF7FD';
$metaDescription = 'NovaTeam convierte las guías de matemáticas e inglés de 4.º en retos con puntos, vidas e insignias. El docente ve el avance del grupo sin planillas.';
$canonical = site_url('/');
$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'EducationalOrganization',
    'name' => 'NovaTeam',
    'url' => $canonical,
    'description' => $metaDescription,
    'areaServed' => ['@type' => 'Country', 'name' => 'Colombia'],
    'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Cúcuta', 'addressCountry' => 'CO'],
];
$extraHead = '<link rel="stylesheet" href="assets/css/landing.css?v=2">'
    . '<script type="application/ld+json">' . json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
require __DIR__ . '/includes/header.php';
?>
<script>document.documentElement.classList.add('has-reveal');</script>

<section class="l-hero" id="inicio" aria-labelledby="l-hero-title">
  <div class="l-hero__bg" aria-hidden="true">
    <span class="l-blob l-blob--1"></span>
    <span class="l-blob l-blob--2"></span>
    <span class="l-blob l-blob--3"></span>
    <span class="l-float l-float--1">🚀</span>
    <span class="l-float l-float--2">🌈</span>
    <span class="l-float l-float--3">⭐</span>
    <span class="l-float l-float--4">🦉</span>
    <span class="l-float l-float--5">✨</span>
  </div>

  <div class="l-wrap l-hero__grid">
    <div class="l-hero__copy">
      <p class="l-eyebrow"><span class="l-dot" aria-hidden="true"></span>Piloto 2026 · 4.º de primaria · SENA ADSO</p>
      <h1 id="l-hero-title">Aprender es una <span class="l-grad">aventura</span> que se juega.</h1>
      <p class="l-lead">
        NovaTeam convierte las guías de matemáticas e inglés en retos con puntos, vidas e insignias.
        El estudiante practica a su ritmo; el docente ve el avance el mismo día, sin planillas.
      </p>
      <div class="l-actions">
        <a class="l-btn l-btn--ink" href="registro.php">Empezar la aventura <span aria-hidden="true">→</span></a>
        <a class="l-btn l-btn--ghost" href="#como-funciona">Ver cómo funciona</a>
      </div>
      <ul class="l-stats">
        <li><strong>2</strong><span>materias</span></li>
        <li><strong>+6</strong><span>retos listos</span></li>
        <li><strong>100%</strong><span>en el navegador</span></li>
      </ul>
      <p class="l-note">Se instala como app <i>·</i> Corre en el navegador del colegio</p>
    </div>

    <div class="l-hero__visual">
      <div class="l-score" role="img" aria-label="Ejemplo de hoja de puntaje: Sara Méndez, 4.º, guía 03 de secuencias, 240 puntos, sexto lugar del ranking, racha de 6 días y 4 de 5 vidas.">
        <div class="l-score__top">
          <span class="l-score__label">Hoja de puntaje</span>
          <span class="l-stamp" aria-hidden="true">Publicada</span>
        </div>
        <div class="l-score__who">
          <span class="l-avatar" aria-hidden="true">SM</span>
          <div>
            <strong>Sara Méndez</strong>
            <span>4.º · Guía 03 · Secuencias</span>
          </div>
        </div>
        <div class="l-score__pts">
          <strong>240</strong>
          <span>puntos<br>6.º en el ranking</span>
        </div>
        <div class="l-score__chips">
          <span>Racha 6 días</span>
          <span>Vidas 4/5</span>
        </div>
        <div class="l-quiz">
          <p class="l-quiz__q">¿Qué número sigue?</p>
          <p class="l-quiz__seq">2 · 4 · 8 · 16 · ___</p>
          <div class="l-quiz__opts">
            <span>24</span><span class="is-on">32</span><span>20</span>
          </div>
        </div>
        <p class="l-score__foot">Vista de ejemplo del reto</p>
      </div>
      <span class="l-tag" aria-hidden="true">🏅 Insignia: Genio lógico</span>
      <span class="l-spark l-spark--1" aria-hidden="true">✦</span>
      <span class="l-spark l-spark--2" aria-hidden="true">✦</span>
    </div>
  </div>

  <div class="l-wave" aria-hidden="true">
    <svg viewBox="0 0 1440 120" preserveAspectRatio="none" focusable="false">
      <path d="M0,64L60,58.7C120,53,240,43,360,48C480,53,600,75,720,80C840,85,960,75,1080,64C1200,53,1320,43,1380,37.3L1440,32L1440,120L0,120Z"></path>
    </svg>
  </div>
</section>

<div class="l-ticker" aria-hidden="true">
  <div class="l-ticker__track">
    <span>🧮 Guías con opción múltiple</span>
    <span>🏆 Puntos y ranking</span>
    <span>🏅 Insignias por logros</span>
    <span>❤️ Vidas que se recargan</span>
    <span>🎲 Comodín 50/50</span>
    <span>💬 Mensajes con archivos</span>
    <span>🔔 Notificaciones</span>
    <span>📊 Panel docente</span>
    <span>📱 App instalable</span>
    <span>🧮 Guías con opción múltiple</span>
    <span>🏆 Puntos y ranking</span>
    <span>🏅 Insignias por logros</span>
    <span>❤️ Vidas que se recargan</span>
    <span>🎲 Comodín 50/50</span>
    <span>💬 Mensajes con archivos</span>
    <span>🔔 Notificaciones</span>
    <span>📊 Panel docente</span>
    <span>📱 App instalable</span>
  </div>
</div>

<section class="l-section l-explore" id="explora" aria-labelledby="l-explore-title">
  <div class="l-wrap">
    <div class="l-explore__head reveal">
      <span class="l-kicker">Explora NovaTeam</span>
      <h2 id="l-explore-title">Elige tu próxima <span class="l-grad">aventura</span></h2>
      <p class="l-lead">Cinco rincones para aprender jugando: retos, insignias, práctica y un panel para el docente.</p>
    </div>
    <div class="l-explore__grid">
      <a class="l-card l-card--math reveal" href="#materias">
        <span class="l-card__tag">Lógica y cálculo</span>
        <span class="l-card__icon" aria-hidden="true">🧮</span>
        <h3>Matemáticas</h3>
        <p>Secuencias, operaciones y comparaciones con dificultad que sube paso a paso.</p>
        <span class="l-card__link">Ver retos <span aria-hidden="true">→</span></span>
        <span class="l-card__watermark" aria-hidden="true">🔢</span>
      </a>
      <a class="l-card l-card--eng reveal" href="#materias" style="--d:1">
        <span class="l-card__tag">Vocabulary</span>
        <span class="l-card__icon" aria-hidden="true">🇬🇧</span>
        <h3>Inglés</h3>
        <p>Vocabulario, colores, animales y lecturas cortas para practicar sin miedo.</p>
        <span class="l-card__link">Ver retos <span aria-hidden="true">→</span></span>
        <span class="l-card__watermark" aria-hidden="true">💬</span>
      </a>
      <a class="l-card l-card--play reveal" href="#como-funciona" style="--d:2">
        <span class="l-card__tag">4 pasos</span>
        <span class="l-card__icon" aria-hidden="true">🎮</span>
        <h3>Cómo funciona</h3>
        <p>Publicar, practicar, sumar puntos y revisar el avance. Así de simple.</p>
        <span class="l-card__link">Conocer el circuito <span aria-hidden="true">→</span></span>
        <span class="l-card__watermark" aria-hidden="true">🚀</span>
      </a>
      <a class="l-card l-card--teach reveal" href="#docente" style="--d:3">
        <span class="l-card__tag">Panel</span>
        <span class="l-card__icon" aria-hidden="true">👩‍🏫</span>
        <h3>Docentes</h3>
        <p>Menos planilla: puntos, intentos e insignias del grupo en un solo lugar.</p>
        <span class="l-card__link">Ver el panel <span aria-hidden="true">→</span></span>
        <span class="l-card__watermark" aria-hidden="true">📊</span>
      </a>
      <a class="l-card l-card--join reveal" href="registro.php" style="--d:4">
        <span class="l-card__tag">Piloto 2026</span>
        <span class="l-card__icon" aria-hidden="true">🌟</span>
        <h3>Únete</h3>
        <p>Crea tu cuenta de estudiante y empieza a ganar puntos e insignias.</p>
        <span class="l-card__link">Crear mi cuenta <span aria-hidden="true">→</span></span>
        <span class="l-card__watermark" aria-hidden="true">🎈</span>
      </a>
    </div>
  </div>
</section>

<section class="l-section" id="problema" aria-labelledby="l-prob-title">
  <div class="l-wrap l-prob">
    <div class="reveal">
      <p class="l-kicker"><span>01</span> El reto</p>
      <h2 id="l-prob-title">La teoría sola no alcanza.</h2>
      <p class="l-lead">
        En 4.º se acumulan las bases. Si la práctica en casa es poca o siempre igual,
        la brecha crece antes de que el docente pueda verla.
      </p>
    </div>
    <ol class="l-facts">
      <li class="reveal" style="--d:1">
        <span class="l-facts__n">01</span>
        <div>
          <h3>Ritmos distintos</h3>
          <p>Un grupo avanza parejo en el tablero, no en la práctica. Quien necesita repetir, repite menos.</p>
        </div>
      </li>
      <li class="reveal" style="--d:2">
        <span class="l-facts__n">02</span>
        <div>
          <h3>Práctica desigual</h3>
          <p>Sin acompañamiento en casa, la ejercitación se vuelve esporádica y las dudas se quedan para el día siguiente.</p>
        </div>
      </li>
      <li class="reveal" style="--d:3">
        <span class="l-facts__n">03</span>
        <div>
          <h3>Corrección manual</h3>
          <p>El tiempo de clase se va revisando lo que un panel podría mostrar en segundos.</p>
        </div>
      </li>
    </ol>
  </div>
  <div class="l-wrap">
    <p class="l-pull reveal">
      NovaTeam no reemplaza la clase: extiende la práctica y devuelve al docente
      datos concretos para decidir qué reforzar.
    </p>
  </div>
</section>

<section class="l-section l-section--ink" id="como-funciona" aria-labelledby="l-loop-title">
  <div class="l-wrap">
    <div class="reveal">
      <p class="l-kicker"><span>02</span> El circuito</p>
      <h2 id="l-loop-title">Publicar, practicar, medir.</h2>
      <p class="l-lead">Cuatro pasos, sin instalar nada en los equipos del colegio.</p>
    </div>
    <div class="l-loop">
      <div class="l-loop__step reveal" style="--d:1">
        <span class="l-loop__emoji" aria-hidden="true">📝</span>
        <span class="l-loop__n">01</span>
        <h3>El docente publica la guía</h3>
        <p>Elige materia, escribe preguntas de opción múltiple y decide cuándo se abre.</p>
      </div>
      <div class="l-loop__step reveal" style="--d:2">
        <span class="l-loop__emoji" aria-hidden="true">🧠</span>
        <span class="l-loop__n">02</span>
        <h3>El estudiante resuelve</h3>
        <p>Cada intento consume una vida. Puede gastar un comodín 50/50; las respuestas se revelan al enviar.</p>
      </div>
      <div class="l-loop__step reveal" style="--d:3">
        <span class="l-loop__emoji" aria-hidden="true">🏆</span>
        <span class="l-loop__n">03</span>
        <h3>Sube puntos y ranking</h3>
        <p>Los aciertos suman puntos. Las insignias y la racha premian la constancia, no solo la prisa.</p>
      </div>
      <div class="l-loop__step reveal" style="--d:4">
        <span class="l-loop__emoji" aria-hidden="true">📊</span>
        <span class="l-loop__n">04</span>
        <h3>El docente revisa</h3>
        <p>Puntos, intentos, guías completadas e insignias por estudiante, sin hojas de cálculo.</p>
      </div>
    </div>
    <div class="reveal">
      <a class="l-btn l-btn--ink" href="registro.php">Crear una cuenta <span aria-hidden="true">→</span></a>
    </div>
  </div>
</section>

<section class="l-section l-section--soft" id="docente" aria-labelledby="l-teach-title">
  <div class="l-wrap l-teach">
    <div class="reveal">
      <p class="l-kicker"><span>03</span> Para el docente</p>
      <h2 id="l-teach-title">Menos planilla, más clase.</h2>
      <p class="l-lead">
        Al abrir una guía, el panel reúne el avance del grupo: quién la completó,
        cuántos intentos hizo, su promedio y las insignias que ganó.
      </p>
      <ul class="l-check">
        <li>Crear, editar y publicar guías por materia.</li>
        <li>Ver el avance de cada estudiante en su materia.</li>
        <li>Recibir notificaciones cuando una guía se publica.</li>
        <li>Escribir mensajes con archivos adjuntos.</li>
      </ul>
    </div>
    <div class="reveal" style="--d:1">
      <div class="l-table">
        <div class="l-table__head">
          <span>Alumnos · Guía 03</span>
          <span>Matemáticas</span>
        </div>
        <div class="l-table__row is-top">
          <span>Sara Méndez</span>
          <span>240 pts</span>
          <span>3/3</span>
        </div>
        <div class="l-table__row">
          <span>Diego Salas</span>
          <span>220 pts</span>
          <span>3/3</span>
        </div>
        <div class="l-table__row">
          <span>Tomás Ríos</span>
          <span>180 pts</span>
          <span>2/3</span>
        </div>
        <div class="l-table__row">
          <span>Valeria Paz</span>
          <span>100 pts</span>
          <span>1/3</span>
        </div>
        <div class="l-table__foot">Vista de ejemplo · puntos, guías completadas</div>
      </div>
    </div>
  </div>
</section>

<section class="l-section" id="materias" aria-labelledby="l-subj-title">
  <div class="l-wrap">
    <div class="reveal">
      <p class="l-kicker"><span>04</span> Contenido</p>
      <h2 id="l-subj-title">Dos materias, una misma mecánica.</h2>
    </div>
    <div class="l-subj">
      <div class="l-subj__row reveal" style="--d:1">
        <div class="l-subj__name"><small>Materia 01</small><span class="l-subj__emoji" aria-hidden="true">🧮</span>Matemáticas</div>
        <div class="l-subj__body">
          <p>Retos de lógica y cálculo con dificultad progresiva: primero reconocer el patrón, después resolverlo con fluidez.</p>
          <div class="l-tags">
            <span>Lógica</span><span>Secuencias</span><span>Operaciones básicas</span><span>Porcentajes</span><span>Comparaciones</span>
          </div>
        </div>
      </div>
      <div class="l-subj__row reveal" style="--d:2">
        <div class="l-subj__name"><small>Materia 02</small><span class="l-subj__emoji" aria-hidden="true">🇬🇧</span>Inglés</div>
        <div class="l-subj__body">
          <p>Vocabulario y estructuras de uso diario. Cada guía mezcla reconocimiento, lectura corta y respuesta rápida.</p>
          <div class="l-tags">
            <span>Vocabulario</span><span>Colores</span><span>Animales</span><span>Saludos</span><span>Gramática básica</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="l-section l-section--soft" id="piloto" aria-labelledby="l-pilot-title">
  <div class="l-wrap">
    <div class="reveal">
      <p class="l-kicker"><span>05</span> Puesta en marcha</p>
      <h2 id="l-pilot-title">Se coordina en una reunión.</h2>
      <p class="l-lead">
        El piloto puede correr con un grupo o con toda la institución. No requiere infraestructura nueva:
        funciona en el navegador y se puede instalar como app.
      </p>
    </div>
    <div class="l-steps">
      <div class="l-steps__item reveal" style="--d:1">
        <span class="l-steps__n">01</span>
        <div>
          <h3>Presentación</h3>
          <p>Reunión con directivos para revisar alcance, condiciones y fecha de inicio del piloto.</p>
        </div>
      </div>
      <div class="l-steps__item reveal" style="--d:2">
        <span class="l-steps__n">02</span>
        <div>
          <h3>Modalidad</h3>
          <p>Piloto con un grupo o implementación institucional completa.</p>
        </div>
      </div>
      <div class="l-steps__item reveal" style="--d:3">
        <span class="l-steps__n">03</span>
        <div>
          <h3>Orientación docente</h3>
          <p>Jornada básica de uso: crear guías, publicar y leer el panel.</p>
        </div>
      </div>
      <div class="l-steps__item reveal" style="--d:4">
        <span class="l-steps__n">04</span>
        <div>
          <h3>Evaluación</h3>
          <p>Medición del impacto y ajustes con la retroalimentación del equipo docente.</p>
        </div>
      </div>
    </div>
    <div class="reveal" style="margin-top:2rem">
      <a class="l-btn l-btn--ghost" href="descargar.php">Descargar la app <span aria-hidden="true">↓</span></a>
    </div>
  </div>
</section>

<section class="l-section" id="equipo" aria-labelledby="l-team-title">
  <div class="l-wrap">
    <div class="reveal">
      <p class="l-kicker"><span>06</span> Quién lo construye</p>
      <h2 id="l-team-title">Un equipo de ADSO, no una agencia.</h2>
    </div>
    <div class="l-team reveal" style="--d:1">
      <span>Carlos Damian Malave Diaz</span>
      <span>Danna Alexandra Ortega Saavedra</span>
      <span>Giselle Nayeli Jaimes Galvis</span>
      <span>Jhoan Andres Ortiz Galvis</span>
      <span>Carlos Andrey Delgado Castañeda</span>
    </div>
    <p class="l-team__meta reveal" style="--d:2">SENA · Análisis y Desarrollo de Software · Ficha 3235781 · Cúcuta</p>
  </div>
</section>

<section class="l-cta">
  <div class="l-wrap">
    <div class="l-cta__card reveal">
      <span class="l-cta__emoji l-cta__emoji--1" aria-hidden="true">🎈</span>
      <span class="l-cta__emoji l-cta__emoji--2" aria-hidden="true">⭐</span>
      <span class="l-cta__emoji l-cta__emoji--3" aria-hidden="true">🪁</span>
      <span class="l-cta__emoji l-cta__emoji--4" aria-hidden="true">🌈</span>
      <h2>¿Listo para empezar la aventura?</h2>
      <p>Únete al piloto de NovaTeam y convierte la práctica de matemáticas e inglés en un juego con puntos, vidas e insignias.</p>
      <a class="l-btn l-btn--light" href="registro.php">Quiero mi cuenta <span aria-hidden="true">→</span></a>
    </div>
  </div>
</section>

<section class="l-section l-section--soft" id="contacto" aria-labelledby="l-contact-title">
  <div class="l-wrap l-contact">
    <div class="reveal">
      <p class="l-kicker"><span>07</span> Contacto</p>
      <h2 id="l-contact-title">Coordina el piloto en tu institución.</h2>
      <p class="l-lead">
        Escríbenos con el nombre de la institución y el grado. Respondemos con la propuesta
        técnica, los requisitos del piloto y una fecha para la reunión.
      </p>
      <p class="l-note">También puedes <a class="l-link" href="login.php">iniciar sesión</a> si ya tienes cuenta.</p>
    </div>
    <div class="reveal" style="--d:1">
      <?php render_alerts(); ?>
      <form method="post" class="l-form" action="index.php#contacto">
        <?= csrf_field() ?>
        <div class="l-form__row">
          <div class="l-field">
            <label for="nombre">Nombre</label>
            <input class="l-input" id="nombre" name="nombre" type="text" required maxlength="120" autocomplete="name">
          </div>
          <div class="l-field">
            <label for="correo">Correo</label>
            <input class="l-input" id="correo" name="correo" type="email" required maxlength="150" autocomplete="email">
          </div>
        </div>
        <div class="l-field">
          <label for="institucion">Institución</label>
          <input class="l-input" id="institucion" name="institucion" type="text" maxlength="150" autocomplete="organization">
        </div>
        <div class="l-field">
          <label for="mensaje">Mensaje</label>
          <textarea class="l-input" id="mensaje" name="mensaje" rows="4" required maxlength="2000"></textarea>
        </div>
        <button class="l-btn l-btn--ink" type="submit">Enviar solicitud <span aria-hidden="true">→</span></button>
        <p class="l-form__note">Usamos tus datos solo para responder esta solicitud. No los publicamos ni los vendemos. <a class="l-link" href="politica-privacidad.html">Política de privacidad</a>.</p>
      </form>
    </div>
  </div>
</section>

<script>
(function () {
  var els = document.querySelectorAll('.reveal');
  if (!els.length) return;
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (reduce || !('IntersectionObserver' in window)) {
    els.forEach(function (el) { el.classList.add('is-in'); });
    return;
  }
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-in');
        io.unobserve(entry.target);
      }
    });
  }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
  els.forEach(function (el) { io.observe(el); });
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
