<?php
declare(strict_types=1);
?>
</main>
<footer class="edu-footer text-center py-4 mt-5">
  <div class="container">
    <span class="edu-logo" style="color:#fff"><span class="logo-mark">E</span> EduNova</span>
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
</body>
</html>
