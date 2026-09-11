<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Desplegar NovaTeam gratis';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-5" style="max-width:700px">
  <h1 class="h3 fw-bold text-center mb-4">🚀 Desplegar NovaTeam gratis en internet</h1>

  <div class="card-edu p-4 mb-4">
    <h5 class="fw-bold">Opción 1: Railway (Recomendada)</h5>
    <p class="small text-muted">Hosting gratuito con PHP + MySQL. URL permanente tipo <code>tudominio.up.railway.app</code></p>
    <ol class="small">
      <li>Crea una cuenta gratis en <a href="https://railway.app" target="_blank">railway.app</a> con tu cuenta de GitHub o Google</li>
      <li>Haz clic en <strong>"New Project"</strong> &rarr; <strong>"Empty Project"</strong></li>
      <li>En tu computer, crea una carpeta y sube todos los archivos de NovaTeam</li>
      <li>Inicializa git y súbelo a GitHub:
        <pre class="bg-dark text-light p-2 rounded mt-1"><code>cd novateam
git init
git add .
git commit -m "NovaTeam v1"
# Crea un repo en github.com y copia la URL
git remote add origin https://github.com/TU_USUARIO/novateam.git
git push -u origin main</code></pre>
      </li>
      <li>En Railway: <strong>"New"</strong> &rarr; <strong>"GitHub Repo"</strong> &rarr; selecciona tu repo</li>
      <li>Agrega un servicio <strong>"MySQL"</strong> al proyecto</li>
      <li>En las variables de entorno de Railway, configura:
        <pre class="bg-dark text-light p-2 rounded mt-1"><code>DB_HOST=mysql.railway.internal
DB_NAME=railway
DB_USER=root
DB_PASS=(la que Railway genera automáticamente)</code></pre>
      </li>
      <li>Importa la base de datos: Railway te da acceso SSH o usa <code>mysql</code> client</li>
      <li>¡Listo! Tu app está online en <code>https://tunombre.up.railway.app</code></li>
    </ol>
  </div>

  <div class="card-edu p-4 mb-4">
    <h5 class="fw-bold">Opción 2: InfinityFree (100% gratis, sin tarjeta)</h5>
    <p class="small text-muted">Hosting compartido con PHP + MySQL. Subes archivos por FTP.</p>
    <ol class="small">
      <li>Crea cuenta en <a href="https://infinityfree.com" target="_blank">infinityfree.com</a></li>
      <li>Crea un dominio gratuito (ej: <code>novateam.epizy.com</code>)</li>
      <li>Crea una base de datos MySQL desde el panel</li>
      <li>Sube todos los archivos de NovaTeam por <strong>File Manager</strong> o <strong>FTP</strong></li>
      <li>Importa <code>database/novateam.sql</code> desde phpMyAdmin del panel</li>
      <li>Edita <code>.env</code> con los datos de la BD de InfinityFree</li>
      <li>¡Listo! Tu app está en <code>https://novateam.epizy.com</code></li>
    </ol>
  </div>

  <div class="card-edu p-4 mb-4">
    <h5 class="fw-bold">Opción 3: Cloudways / 000webhost</h5>
    <p class="small text-muted">Alternativas gratuitas con PHP.</p>
    <ul class="small">
      <li><strong>000webhost.com</strong> — gratis, soporta PHP + MySQL, 300MB</li>
      <li><strong>Cloudways</strong> — 3 días gratis con tarjeta, después $14/mes</li>
      <li><strong>InfinityFree</strong> — el más estable para proyectos pequeños</li>
    </ul>
  </div>

  <div class="card-edu p-4 bg-surface">
    <h5 class="fw-bold"><i class="bi bi-lightning"></i> Para compartir con todos</h5>
    <p class="small mb-1">Una vez desplegado, comparte el enlace. Ejemplo:</p>
    <code class="d-block bg-dark text-light p-2 rounded mt-2 text-center" style="font-size:1rem">
      https://novateam.epizy.com
    </code>
    <p class="small text-muted mt-2 mb-0">
      Los usuarios pueden instalarla como app desde su celular (botón 📱 en el menú).
    </p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
