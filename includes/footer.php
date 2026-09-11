<?php
declare(strict_types=1);
?>
</main>
<footer class="edu-footer text-center py-4 mt-5">
  <div class="container">
    <span class="edu-logo" style="color:#fff"><span class="logo-mark">E</span> EduNova</span>
    <div class="d-flex justify-content-center gap-3 mt-2 flex-wrap">
      <a href="descargar.php" class="text-decoration-none small"><i class="bi bi-phone"></i> Descargar app</a>
      <a href="login.php" class="text-decoration-none small"><i class="bi bi-box-arrow-in-right"></i> Iniciar sesión</a>
    </div>
    <p class="mt-2 mb-0 small">© <?= date('Y') ?> NovaTeam · SENA, Análisis y Desarrollo de Software · Ficha 3235781 · Cúcuta, Norte de Santander</p>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
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
  const btn = document.getElementById('theme-toggle');
  if (!btn) return;
  const icon = btn.querySelector('i');
  const updateIcon = (t) => {
    if (icon) icon.className = t === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars';
  };
  const updateMeta = (t) => {
    const m = document.querySelector('meta[name="theme-color"]');
    if (m) m.setAttribute('content', t === 'dark' ? '#141a33' : '#6d5dfc');
  };
  btn.addEventListener('click', () => {
    const cur = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
    const next = cur === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-bs-theme', next);
    localStorage.setItem('edunova-theme', next);
    updateIcon(next);
    updateMeta(next);
  });
  updateIcon(document.documentElement.getAttribute('data-bs-theme'));
  updateMeta(document.documentElement.getAttribute('data-bs-theme'));
})();
</script>
</body>
</html>
