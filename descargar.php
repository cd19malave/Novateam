<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Descargar app';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-5">
  <div class="text-center mb-4">
    <span class="section-eyebrow">Descarga NovaTeam</span>
    <h1 class="fw-bold mt-2">Llévala a tu PC o a tu teléfono</h1>
    <p class="text-muted mx-auto" style="max-width:620px">
      NovaTeam es una <strong>app web progresiva (PWA)</strong>: la instalas una sola vez y funciona como una
      aplicación normal, incluso sin conexión.
    </p>
  </div>

  <?php render_alerts(); ?>

  <?php
  $apks = glob(__DIR__ . '/assets/downloads/*.apk') ?: [];
  $apk = $apks ? basename($apks[0]) : null;
  $apkVersion = '1.1.0';
  $apkTamano = $apk ? number_format(filesize(__DIR__ . '/assets/downloads/' . $apk) / 1048576, 1, ',', '') . ' MB' : '';
  ?>

  <?php if ($apk): ?>
  <div class="text-center mb-5">
    <div class="card-edu p-4 mx-auto" style="max-width:640px">
      <div class="os-icon mb-2"><i class="bi bi-android2"></i></div>
      <h3 class="h4 fw-bold mb-1">App oficial para Android</h3>
      <p class="text-muted small mb-3">Descarga el instalador <strong>NovaTeam v<?= $apkVersion ?></strong> (<?= $apkTamano ?>) y ábrelo desde tu celular.</p>
      <a href="<?= e('assets/downloads/' . $apk) ?>" class="btn btn-edu btn-lg" download>
        <i class="bi bi-download"></i> Descargar APK
      </a>
      <p class="text-muted small mt-3 mb-0">Si Android muestra un aviso, toca <strong>«Más información» → «Instalar de todos modos»</strong> (por ser una app externa a Play Store).</p>
    </div>
  </div>
  <?php endif; ?>

  <div class="row g-4 justify-content-center">

    <!-- Windows / PC -->
    <div class="col-md-6 col-lg-3">
      <div class="download-os p-4 text-center os-windows">
        <div class="os-icon"><i class="bi bi-windows"></i></div>
        <h3 class="h5 fw-bold">PC · Windows</h3>
        <p class="text-muted small">Chrome y Edge la instalan como una app de escritorio.</p>
        <div class="text-start mt-3">
          <div class="step-card"><span class="step-num">1</span><p>Entra a <a href="https://novateam-production.up.railway.app" target="_blank" rel="noopener">novateam</a> desde Chrome o Edge.</p></div>
          <div class="step-card"><span class="step-num">2</span><p>Haz clic en el icono <i class="bi bi-arrow-down-circle"></i> de la barra de direcciones.</p></div>
          <div class="step-card"><span class="step-num">3</span><p>Elige <strong>«Instalar NovaTeam»</strong> y listo.</p></div>
        </div>
      </div>
    </div>

    <!-- Android -->
    <div class="col-md-6 col-lg-3">
      <div class="download-os p-4 text-center os-android">
        <div class="os-icon"><i class="bi bi-android2"></i></div>
        <h3 class="h5 fw-bold">Android</h3>
        <p class="text-muted small">Con Chrome se añade a la pantalla de inicio como una app.</p>
        <div class="text-start mt-3">
          <div class="step-card"><span class="step-num">1</span><p>Abre la URL en <strong>Chrome</strong>.</p></div>
          <div class="step-card"><span class="step-num">2</span><p>Toca los <i class="bi bi-three-dots-vertical"></i> del menú.</p></div>
          <div class="step-card"><span class="step-num">3</span><p>Pulsa <strong>«Añadir a pantalla de inicio»</strong> o <strong>«Instalar app»</strong>.</p></div>
        </div>
      </div>
    </div>

    <!-- iPhone / iPad -->
    <div class="col-md-6 col-lg-3">
      <div class="download-os p-4 text-center os-apple">
        <div class="os-icon"><i class="bi bi-apple"></i></div>
        <h3 class="h5 fw-bold">iPhone / iPad</h3>
        <p class="text-muted small">Con Safari se guarda en la pantalla de inicio.</p>
        <div class="text-start mt-3">
          <div class="step-card"><span class="step-num">1</span><p>Abre la URL en <strong>Safari</strong>.</p></div>
          <div class="step-card"><span class="step-num">2</span><p>Toca el botón <i class="bi bi-square"></i><i class="bi bi-arrow-up"></i> <strong>Compartir</strong>.</p></div>
          <div class="step-card"><span class="step-num">3</span><p>Pulsa <strong>«Añadir a pantalla de inicio»</strong>.</p></div>
        </div>
      </div>
    </div>

    <!-- Móvil genérico (PWA) -->
    <div class="col-md-6 col-lg-3">
      <div class="download-os p-4 text-center os-web">
        <div class="os-icon"><i class="bi bi-globe2"></i></div>
        <h3 class="h5 fw-bold">Sin instalar</h3>
        <p class="text-muted small">También puedes usarla directo en el navegador.</p>
        <div class="text-start mt-3">
          <div class="step-card"><span class="step-num">1</span><p>Entra con cualquier navegador moderno.</p></div>
          <div class="step-card"><span class="step-num">2</span><p>Inicia sesión con tu cuenta institucional.</p></div>
          <div class="step-card"><span class="step-num">3</span><p>No necesitas descargar nada.</p></div>
        </div>
      </div>
    </div>

  </div>

  <div class="text-center mt-4">
    <button class="btn btn-edu btn-lg" id="btn-instalar-pwa" type="button">
      <i class="bi bi-phone"></i> Instalar en este dispositivo
    </button>
    <p class="text-muted small mt-2 mb-0">Si ya la tienes instalada, este botón no hará nada.</p>
  </div>
</div>

<script>
(function(){
  let deferredPrompt = null;
  const btn = document.getElementById('btn-instalar-pwa');
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    if (btn) { btn.style.display = ''; btn.disabled = false; }
  });
  if (btn) {
    btn.addEventListener('click', async () => {
      if (!deferredPrompt) {
        btn.textContent = 'Prueba los pasos de arriba para tu dispositivo';
        setTimeout(() => { btn.textContent = 'Instalar en este dispositivo'; }, 2500);
        return;
      }
      deferredPrompt.prompt();
      await deferredPrompt.userChoice;
      deferredPrompt = null;
      btn.style.display = 'none';
    });
  }
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>