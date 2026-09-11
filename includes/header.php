<?php
declare(strict_types=1);
/** @var string $pageTitle */
$pageTitle = $pageTitle ?? 'EduNova';
$user = current_user();
$unreadCount = $user ? unread_messages_count((int) $user['id_usuario']) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script>
    (function(){
      var t = localStorage.getItem('edunova-theme') ||
        (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
      document.documentElement.setAttribute('data-bs-theme', t);
    })();
  </script>
  <title><?= e($pageTitle) ?> | EduNova</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700;800&family=Comic+Neue:wght@700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/app.css">
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#6d5dfc">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="EduNova">
  <link rel="apple-touch-icon" href="assets/icons/icon-192.png">
</head>
<body>
<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js').catch(() => {});
}
</script>
<nav class="navbar navbar-expand-lg navbar-light navbar-edu sticky-top px-3">
  <div class="container">
    <a class="edu-logo" href="<?= $user ? e(home_for_role()) : 'index.php' ?>">
      <span class="logo-mark">E</span> EduNova
    </a>
    <div class="d-flex align-items-center gap-2">
      <button class="theme-toggle" type="button" id="theme-toggle" title="Cambiar tema claro/oscuro" aria-label="Cambiar tema">
        <i class="bi bi-moon-stars"></i>
      </button>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Menú">
        <i class="bi bi-list"></i>
      </button>
    </div>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
        <?php if (!$user): ?>
          <li class="nav-item"><a class="nav-link" href="index.php#problema">El reto</a></li>
          <li class="nav-item"><a class="nav-link" href="index.php#features">Características</a></li>
          <li class="nav-item"><a class="nav-link" href="index.php#materias">Materias</a></li>
          <li class="nav-item"><a class="nav-link" href="login.php">Entrar</a></li>
          <li class="nav-item"><a class="btn btn-edu" href="registro.php">Registrarme</a></li>
        <?php elseif ($user['rol'] === 'estudiante'): ?>
          <li class="nav-item"><a class="nav-link" href="estudiante.php">Mis guías</a></li>
          <li class="nav-item"><a class="nav-link" href="leaderboard.php">Ranking</a></li>
          <li class="nav-item"><a class="nav-link" href="progreso.php">Mi progreso</a></li>
          <li class="nav-item"><a class="nav-link" href="mensajes.php">
            Mensajes
            <?php if ($unreadCount > 0): ?>
              <span class="badge bg-danger rounded-pill ms-1" id="msg-badge" style="font-size:.65rem;"><?= $unreadCount ?></span>
            <?php endif; ?>
          </a></li>
          <li class="nav-item"><a class="nav-link" href="perfil.php"><?= user_avatar_html($user, 28) ?></a></li>
          <li class="nav-item"><a class="nav-link" href="descargar.php" title="Descargar app"><i class="bi bi-phone"></i> <span class="d-lg-none">Descargar</span></a></li>
          <li class="nav-item"><a class="btn btn-edu-outline py-1" href="logout.php">Salir</a></li>
        <?php elseif ($user['rol'] === 'profesor'): ?>
          <li class="nav-item"><a class="nav-link" href="profesor.php">Panel</a></li>
          <li class="nav-item"><a class="nav-link" href="crear-guia.php">Nueva guía</a></li>
          <li class="nav-item"><a class="nav-link" href="alumnos.php">Mis alumnos</a></li>
          <li class="nav-item"><a class="nav-link" href="leaderboard.php">Ranking</a></li>
          <li class="nav-item"><a class="nav-link" href="mensajes.php">
            Mensajes
            <?php if ($unreadCount > 0): ?>
              <span class="badge bg-danger rounded-pill ms-1" id="msg-badge" style="font-size:.65rem;"><?= $unreadCount ?></span>
            <?php endif; ?>
          </a></li>
          <li class="nav-item"><a class="nav-link" href="perfil.php"><?= user_avatar_html($user, 28) ?></a></li>
          <li class="nav-item"><a class="nav-link" href="descargar.php" title="Descargar app"><i class="bi bi-phone"></i> <span class="d-lg-none">Descargar</span></a></li>
          <li class="nav-item"><a class="btn btn-edu-outline py-1" href="logout.php">Salir</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="admin.php">Administración</a></li>
          <li class="nav-item"><a class="nav-link" href="leaderboard.php">Ranking</a></li>
          <li class="nav-item"><a class="nav-link" href="mensajes.php">Mensajes</a></li>
          <li class="nav-item"><a class="nav-link" href="perfil.php"><?= user_avatar_html($user, 28) ?></a></li>
          <li class="nav-item"><a class="nav-link" href="descargar.php" title="Descargar app"><i class="bi bi-phone"></i> <span class="d-lg-none">Descargar</span></a></li>
          <li class="nav-item"><a class="btn btn-edu-outline py-1" href="logout.php">Salir</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<main>
