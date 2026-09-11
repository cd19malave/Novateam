<?php
declare(strict_types=1);
/** @var string $pageTitle */
$pageTitle = $pageTitle ?? 'NovaTeam';
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
      var t = localStorage.getItem('novateam-theme');
      if (t !== 'light' && t !== 'dark') t = 'dark';
      document.documentElement.setAttribute('data-bs-theme', t);
    })();
  </script>
  <title><?= e($pageTitle) ?> | NovaTeam</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700;800&family=Comic+Neue:wght@700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/app.css?v=3">
  <link rel="manifest" href="manifest.json?v=3">
  <meta name="theme-color" content="#17181A">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="NovaTeam">
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
    <div class="d-flex flex-wrap align-items-center gap-2 w-100">
<a class="edu-logo" href="<?= $user ? e(home_for_role()) : 'index.php' ?>">
        <span class="logo-mark">N</span> NovaTeam
      </a>
      <ul class="navbar-nav align-items-lg-center gap-lg-2 w-100 justify-content-end">
        <?php if (!$user): ?>
          <li class="nav-item"><a class="nav-link" href="index.php#problema">El reto</a></li>
          <li class="nav-item"><a class="nav-link" href="index.php#features">Características</a></li>
          <li class="nav-item"><a class="nav-link" href="index.php#materias">Materias</a></li>
          <li class="nav-item"><a class="nav-link" href="login.php">Entrar</a></li>
          <li class="nav-item"><a class="btn btn-edu" href="registro.php">Registrarme</a></li>
          <li class="nav-item ms-lg-2"><button class="theme-toggle" type="button" id="theme-toggle" title="Cambiar tema claro/oscuro" aria-label="Cambiar tema"><i class="bi bi-sun"></i></button></li>
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
          <li class="nav-item ms-lg-2"><button class="theme-toggle" type="button" id="theme-toggle" title="Cambiar tema claro/oscuro" aria-label="Cambiar tema"><i class="bi bi-sun"></i></button></li>
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
          <li class="nav-item ms-lg-2"><button class="theme-toggle" type="button" id="theme-toggle" title="Cambiar tema claro/oscuro" aria-label="Cambiar tema"><i class="bi bi-sun"></i></button></li>
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
