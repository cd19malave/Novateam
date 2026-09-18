<?php
declare(strict_types=1);
/** @var string $pageTitle */
$pageTitle = $pageTitle ?? 'NovaTeam';
$user = current_user();
$unreadCount = $user ? unread_messages_count((int) $user['id_usuario']) : 0;
$notifCount = $user ? unread_notifications_count((int) $user['id_usuario']) : 0;
$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
$showBottomNav = (bool) $user;
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
  <link rel="stylesheet" href="assets/css/app.css?v=6">
  <link rel="manifest" href="manifest.json?v=3">
  <meta name="theme-color" content="#17181A">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="NovaTeam">
  <link rel="apple-touch-icon" href="assets/icons/icon-192.png">
</head>
<body class="<?= $showBottomNav ? 'has-bottom-nav' : '' ?>">
<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js').catch(() => {});
}
</script>
<nav class="navbar navbar-expand-lg navbar-light navbar-edu sticky-top px-3">
  <div class="container">
    <a class="edu-logo" href="<?= $user ? e(home_for_role()) : 'index.php' ?>">
      <span class="logo-mark">N</span> NovaTeam
    </a>

    <!-- Acciones compactas (solo móvil, con sesión) -->
    <?php if ($showBottomNav): ?>
    <div class="topbar-actions d-flex d-lg-none align-items-center gap-2 ms-auto">
        <?php if ($user['rol'] === 'estudiante'): ?>
          <span class="xp-pill" title="Puntos"><i class="bi bi-lightning-charge-fill"></i> <?= (int) $user['puntos'] ?></span>
        <?php endif; ?>
        <?php if ($user['rol'] === 'estudiante'): ?>
          <a class="topbar-btn" href="notificaciones.php" title="Notificaciones" aria-label="Notificaciones">
            <i class="bi bi-bell-fill"></i>
            <span class="bn-dot js-notif-badge<?= $notifCount > 0 ? '' : ' d-none' ?>" id="notif-dot"><?= $notifCount > 0 ? (int) $notifCount : '' ?></span>
          </a>
        <?php endif; ?>
        <div class="dropdown">
          <button class="topbar-btn" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" aria-label="Más opciones">
            <i class="bi bi-list"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end mt-2">
            <?php if ($user['rol'] === 'estudiante'): ?>
              <li><a class="dropdown-item" href="estudiante.php"><i class="bi bi-house-door me-2"></i>Mis guías</a></li>
              <li><a class="dropdown-item" href="leaderboard.php"><i class="bi bi-trophy me-2"></i>Ranking</a></li>
              <li><a class="dropdown-item" href="progreso.php"><i class="bi bi-graph-up-arrow me-2"></i>Mi progreso</a></li>
              <li><a class="dropdown-item" href="mensajes.php"><i class="bi bi-chat-dots me-2"></i>Mensajes<?php if ($unreadCount > 0): ?> <span class="badge bg-danger rounded-pill ms-1" id="msg-badge"><?= (int) $unreadCount ?></span><?php endif; ?></a></li>
              <li><a class="dropdown-item" href="notificaciones.php"><i class="bi bi-bell me-2"></i>Notificaciones</a></li>
              <li><a class="dropdown-item" href="perfil.php"><i class="bi bi-person me-2"></i>Mi perfil</a></li>
              <li><a class="dropdown-item" href="descargar.php"><i class="bi bi-phone me-2"></i>Descargar app</a></li>
            <?php elseif ($user['rol'] === 'profesor'): ?>
              <li><a class="dropdown-item" href="profesor.php"><i class="bi bi-speedometer2 me-2"></i>Panel</a></li>
              <li><a class="dropdown-item" href="crear-guia.php"><i class="bi bi-plus-square me-2"></i>Nueva guía</a></li>
              <li><a class="dropdown-item" href="alumnos.php"><i class="bi bi-people me-2"></i>Mis alumnos</a></li>
              <li><a class="dropdown-item" href="leaderboard.php"><i class="bi bi-trophy me-2"></i>Ranking</a></li>
              <li><a class="dropdown-item" href="mensajes.php"><i class="bi bi-chat-dots me-2"></i>Mensajes<?php if ($unreadCount > 0): ?> <span class="badge bg-danger rounded-pill ms-1" id="msg-badge"><?= (int) $unreadCount ?></span><?php endif; ?></a></li>
              <li><a class="dropdown-item" href="perfil.php"><i class="bi bi-person me-2"></i>Mi perfil</a></li>
              <li><a class="dropdown-item" href="descargar.php"><i class="bi bi-phone me-2"></i>Descargar app</a></li>
            <?php else: ?>
              <li><a class="dropdown-item" href="admin.php"><i class="bi bi-shield-lock me-2"></i>Administración</a></li>
              <li><a class="dropdown-item" href="leaderboard.php"><i class="bi bi-trophy me-2"></i>Ranking</a></li>
              <li><a class="dropdown-item" href="mensajes.php"><i class="bi bi-chat-dots me-2"></i>Mensajes</a></li>
              <li><a class="dropdown-item" href="perfil.php"><i class="bi bi-person me-2"></i>Mi perfil</a></li>
              <li><a class="dropdown-item" href="descargar.php"><i class="bi bi-phone me-2"></i>Descargar app</a></li>
            <?php endif; ?>
            <li><hr class="dropdown-divider"></li>
            <li><button class="dropdown-item" type="button" data-theme-toggle><i class="bi bi-circle-half me-2"></i>Cambiar tema</button></li>
            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Salir</a></li>
          </ul>
        </div>
    </div>
    <?php endif; ?>

    <!-- Menú completo (escritorio; en móvil se oculta si hay sesión) -->
    <ul class="navbar-nav align-items-lg-center gap-lg-2 ms-auto <?= $showBottomNav ? 'd-none d-lg-flex' : 'w-100 justify-content-end' ?>">
      <?php if (!$user): ?>
        <li class="nav-item"><a class="nav-link" href="index.php#problema">El reto</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php#features">Características</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php#materias">Materias</a></li>
        <li class="nav-item"><a class="nav-link" href="login.php">Entrar</a></li>
        <li class="nav-item"><a class="btn btn-edu" href="registro.php">Registrarme</a></li>
        <li class="nav-item ms-lg-2"><button class="theme-toggle" type="button" data-theme-toggle title="Cambiar tema claro/oscuro" aria-label="Cambiar tema"><i class="bi bi-sun"></i></button></li>
      <?php elseif ($user['rol'] === 'estudiante'): ?>
        <li class="nav-item"><a class="nav-link" href="estudiante.php">Mis guías</a></li>
        <li class="nav-item"><a class="nav-link" href="leaderboard.php">Ranking</a></li>
        <li class="nav-item"><a class="nav-link" href="progreso.php">Mi progreso</a></li>
        <li class="nav-item"><a class="nav-link" href="notificaciones.php" title="Notificaciones">
          <i class="bi bi-bell"></i>
          <?php if ($notifCount > 0): ?>
            <span class="badge bg-danger rounded-pill ms-1 js-notif-badge" style="font-size:.65rem;"><?= $notifCount ?></span>
          <?php endif; ?>
        </a></li>
        <li class="nav-item"><a class="nav-link" href="mensajes.php">
          Mensajes
          <?php if ($unreadCount > 0): ?>
            <span class="badge bg-danger rounded-pill ms-1" style="font-size:.65rem;"><?= $unreadCount ?></span>
          <?php endif; ?>
        </a></li>
        <li class="nav-item"><a class="nav-link" href="perfil.php"><?= user_avatar_html($user, 28) ?></a></li>
        <li class="nav-item"><a class="nav-link" href="descargar.php" title="Descargar app"><i class="bi bi-phone"></i></a></li>
        <li class="nav-item"><a class="btn btn-edu-outline py-1" href="logout.php">Salir</a></li>
        <li class="nav-item ms-lg-2"><button class="theme-toggle" type="button" data-theme-toggle title="Cambiar tema claro/oscuro" aria-label="Cambiar tema"><i class="bi bi-sun"></i></button></li>
      <?php elseif ($user['rol'] === 'profesor'): ?>
        <li class="nav-item"><a class="nav-link" href="profesor.php">Panel</a></li>
        <li class="nav-item"><a class="nav-link" href="crear-guia.php">Nueva guía</a></li>
        <li class="nav-item"><a class="nav-link" href="alumnos.php">Mis alumnos</a></li>
        <li class="nav-item"><a class="nav-link" href="leaderboard.php">Ranking</a></li>
        <li class="nav-item"><a class="nav-link" href="mensajes.php">
          Mensajes
          <?php if ($unreadCount > 0): ?>
            <span class="badge bg-danger rounded-pill ms-1" style="font-size:.65rem;"><?= $unreadCount ?></span>
          <?php endif; ?>
        </a></li>
        <li class="nav-item"><a class="nav-link" href="perfil.php"><?= user_avatar_html($user, 28) ?></a></li>
        <li class="nav-item"><a class="nav-link" href="descargar.php" title="Descargar app"><i class="bi bi-phone"></i></a></li>
        <li class="nav-item"><a class="btn btn-edu-outline py-1" href="logout.php">Salir</a></li>
        <li class="nav-item ms-lg-2"><button class="theme-toggle" type="button" data-theme-toggle title="Cambiar tema claro/oscuro" aria-label="Cambiar tema"><i class="bi bi-sun"></i></button></li>
      <?php else: ?>
        <li class="nav-item"><a class="nav-link" href="admin.php">Administración</a></li>
        <li class="nav-item"><a class="nav-link" href="leaderboard.php">Ranking</a></li>
        <li class="nav-item"><a class="nav-link" href="mensajes.php">Mensajes</a></li>
        <li class="nav-item"><a class="nav-link" href="perfil.php"><?= user_avatar_html($user, 28) ?></a></li>
        <li class="nav-item"><a class="nav-link" href="descargar.php" title="Descargar app"><i class="bi bi-phone"></i></a></li>
        <li class="nav-item"><a class="btn btn-edu-outline py-1" href="logout.php">Salir</a></li>
      <?php endif; ?>
    </ul>
  </div>
</nav>
<main>
