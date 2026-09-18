<?php
declare(strict_types=1);
?>
</main>
<?php if (!empty($user)): $cp = basename($_SERVER['SCRIPT_NAME'] ?? ''); ?>
<?php
  if ($user['rol'] === 'estudiante') {
    $navItems = [
      ['estudiante.php', 'house-door-fill', 'Inicio'],
      ['leaderboard.php', 'trophy-fill', 'Ranking'],
      ['progreso.php', 'graph-up-arrow', 'Progreso'],
      ['mensajes.php', 'chat-dots-fill', 'Mensajes'],
      ['perfil.php', 'person-fill', 'Perfil'],
    ];
  } elseif ($user['rol'] === 'profesor') {
    $navItems = [
      ['profesor.php', 'speedometer2', 'Panel'],
      ['crear-guia.php', 'plus-square-fill', 'Crear'],
      ['alumnos.php', 'people-fill', 'Alumnos'],
      ['mensajes.php', 'chat-dots-fill', 'Mensajes'],
      ['perfil.php', 'person-fill', 'Perfil'],
    ];
  } else {
    $navItems = [
      ['admin.php', 'shield-lock-fill', 'Admin'],
      ['leaderboard.php', 'trophy-fill', 'Ranking'],
      ['mensajes.php', 'chat-dots-fill', 'Mensajes'],
      ['perfil.php', 'person-fill', 'Perfil'],
    ];
  }
?>
<nav class="bottom-nav d-lg-none" aria-label="Navegación principal">
  <div class="bottom-nav-inner">
    <?php foreach ($navItems as $item):
      [$href, $icon, $label] = $item;
      $active = ($cp === $href);
    ?>
      <a class="bn-item<?= $active ? ' active' : '' ?>" href="<?= e($href) ?>"<?= $active ? ' aria-current="page"' : '' ?>>
        <span class="bn-icon">
          <i class="bi bi-<?= e($icon) ?>"></i>
          <?php if ($href === 'mensajes.php' && $unreadCount > 0): ?>
            <span class="bn-badge"><?= (int) $unreadCount ?></span>
          <?php endif; ?>
        </span>
        <span class="bn-label"><?= e($label) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</nav>
<?php endif; ?>
<footer class="edu-footer text-center py-4 mt-5">
  <div class="container">
    <span class="edu-logo" style="color:#fff"><span class="logo-mark">N</span> NovaTeam</span>
    <div class="d-flex justify-content-center gap-3 mt-2 flex-wrap">
      <a href="descargar.php" class="text-decoration-none small"><i class="bi bi-phone"></i> Descargar app</a>
      <a href="login.php" class="text-decoration-none small"><i class="bi bi-box-arrow-in-right"></i> Iniciar sesión</a>
    </div>
    <p class="mt-2 mb-0 small">© <?= date('Y') ?> NovaTeam · SENA, Análisis y Desarrollo de Software · Ficha 3235781 · Cúcuta, Norte de Santander</p>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/tab.js?v=3"></script>
<script src="assets/js/notificaciones.js?v=1"></script>
<script src="assets/js/app.js?v=3"></script>
<script>
function togglePass(id, btn) {
  const input = document.getElementById(id);
  const icon = btn.querySelector('i');
  if (input.type === 'password') { input.type = 'text'; icon.className = 'bi bi-eye-slash'; }
  else { input.type = 'password'; icon.className = 'bi bi-eye'; }
}
</script>
<script>
(function(){
  const btns = document.querySelectorAll('[data-theme-toggle]');
  if (!btns.length) return;
  const updateMeta = (t) => {
    const m = document.querySelector('meta[name="theme-color"]');
    if (m) m.setAttribute('content', t === 'dark' ? '#17181A' : '#4F8FF7');
  };
  const syncIcons = (t) => {
    btns.forEach(b => {
      const i = b.querySelector('i');
      if (i) i.className = t === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
    });
  };
  btns.forEach(btn => btn.addEventListener('click', () => {
    const cur = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
    const next = cur === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-bs-theme', next);
    localStorage.setItem('novateam-theme', next);
    syncIcons(next);
    updateMeta(next);
  }));
  syncIcons(document.documentElement.getAttribute('data-bs-theme'));
  updateMeta(document.documentElement.getAttribute('data-bs-theme'));
})();
</script>
</body>
</html>
