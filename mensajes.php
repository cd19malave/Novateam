<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$user = current_user();
$pdo = db();

$contacts = [];
if ($user['rol'] === 'profesor') {
    $stmt = $pdo->prepare(
        'SELECT DISTINCT u.id_usuario, u.nombre, u.foto_perfil, u.marco_perfil
         FROM usuarios u
         INNER JOIN matriculas m ON m.id_usuario = u.id_usuario AND m.materia = :mat
         WHERE u.rol = \'estudiante\' AND u.activo = 1
         ORDER BY u.nombre'
    );
    $stmt->execute(['mat' => $user['materia']]);
    $contacts = $stmt->fetchAll();
} elseif ($user['rol'] === 'estudiante') {
    $stmt = $pdo->prepare(
        'SELECT DISTINCT u.id_usuario, u.nombre, u.foto_perfil, u.marco_perfil
         FROM usuarios u
         INNER JOIN guias g ON g.id_profesor = u.id_usuario
         INNER JOIN matriculas m ON m.id_usuario = :uid AND m.materia = g.categoria
         WHERE u.rol = \'profesor\' AND u.activo = 1
         ORDER BY u.nombre'
    );
    $stmt->execute(['uid' => $user['id_usuario']]);
    $contacts = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare(
        'SELECT id_usuario, nombre, foto_perfil, marco_perfil
         FROM usuarios WHERE rol <> :r AND activo = 1 ORDER BY nombre'
    );
    $stmt->execute(['r' => $user['rol']]);
    $contacts = $stmt->fetchAll();
}

$paraId = (int) ($_GET['para'] ?? 0);
$chatMensajes = [];
$contacto = null;
if ($paraId) {
    $cst = $pdo->prepare('SELECT id_usuario, nombre, foto_perfil, marco_perfil FROM usuarios WHERE id_usuario = :id LIMIT 1');
    $cst->execute(['id' => $paraId]);
    $contacto = $cst->fetch();

    if ($contacto) {
        $mst = $pdo->prepare(
            'SELECT m.*, u.nombre AS nombre_emisor
             FROM mensajes m JOIN usuarios u ON u.id_usuario = m.id_emisor
             WHERE (m.id_emisor = :a AND m.id_receptor = :b) OR (m.id_emisor = :b2 AND m.id_receptor = :a2)
             ORDER BY m.fecha_envio ASC LIMIT 200'
        );
        $mst->execute(['a' => $user['id_usuario'], 'b' => $paraId, 'b2' => $paraId, 'a2' => $user['id_usuario']]);
        $chatMensajes = $mst->fetchAll();

        $pdo->prepare('UPDATE mensajes SET leido = 1 WHERE id_emisor = :e AND id_receptor = :r AND leido = 0')
            ->execute(['e' => $paraId, 'r' => $user['id_usuario']]);
    }
}

$pageTitle = 'Mensajes';
require __DIR__ . '/includes/header.php';
?>
<style>
  .chat-wrapper{height:calc(100vh - 130px);max-height:700px;}
  .chat-sidebar{overflow-y:auto;max-height:100%;}
  .chat-main{display:flex;flex-direction:column;height:100%;}
  .chat-messages{flex:1 1 auto;overflow-y:auto;padding:1rem;background:var(--edu-messages-bg);}
  .chat-input-bar{padding:.75rem 1rem;border-top:1px solid var(--edu-border);background:var(--edu-surface);display:flex;align-items:flex-end;gap:.5rem;}
  .chat-input-bar textarea{flex:1;resize:none;min-height:40px;max-height:120px;border-radius:12px;padding:.5rem .75rem;}
  .chat-input-bar .file-label{flex:0 0 auto;cursor:pointer;padding:.5rem;border-radius:12px;border:1px solid var(--edu-border);background:var(--edu-surface);color:var(--edu-primary);font-size:1.1rem;}
  .chat-input-bar .file-label:hover{background:var(--edu-hover);}
  .chat-input-bar input[type="file"]{display:none;}
  .chat-input-bar .btn-send{flex:0 0 auto;border-radius:50%;width:40px;height:40px;display:grid;place-items:center;}
  .file-preview{font-size:.72rem;color:var(--edu-muted);margin-top:4px;display:flex;align-items:center;gap:4px;}
  .file-preview i{color:var(--edu-primary);}
</style>

<div class="container-fluid py-3" style="max-width:1100px;">
  <div class="card-edu chat-wrapper d-flex flex-column">
    <div class="row g-0 flex-grow-1" style="min-height:0;">
      <!-- Sidebar contacts -->
      <div class="col-md-4 border-end chat-sidebar" style="max-height:100%;">
        <div class="p-3 border-bottom sticky-top bg-surface" style="z-index:2;">
          <h5 class="fw-bold mb-0"><i class="bi bi-chat-dots"></i> Mensajes</h5>
        </div>
        <div class="list-group list-group-flush">
          <?php foreach ($contacts as $c): ?>
            <?php
            $unread = $pdo->prepare('SELECT COUNT(*) FROM mensajes WHERE id_emisor = :e AND id_receptor = :r AND leido = 0');
            $unread->execute(['e' => $c['id_usuario'], 'r' => $user['id_usuario']]);
            $uc = (int) $unread->fetchColumn();
            ?>
            <a href="mensajes.php?para=<?= (int) $c['id_usuario'] ?>"
               class="list-group-item list-group-item-action <?= $paraId === (int) $c['id_usuario'] ? 'active' : '' ?> d-flex align-items-center gap-2"
               style="<?= $paraId === (int) $c['id_usuario'] ? 'background:var(--edu-primary);color:#fff;' : '' ?>">
              <?= user_avatar_html($c, 36) ?>
              <div class="flex-grow-1 min-w-0">
                <div class="fw-bold small text-truncate"><?= e($c['nombre']) ?></div>
              </div>
              <?php if ($uc > 0): ?>
                <span class="badge bg-danger rounded-pill" style="font-size:.6rem;"><?= $uc ?></span>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
          <?php if (empty($contacts)): ?>
            <div class="p-3 text-muted text-center small">No hay contactos disponibles.</div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Chat area -->
      <div class="col-md-8 chat-main">
        <?php if ($contacto): ?>
          <!-- Header -->
          <div class="p-3 border-bottom d-flex align-items-center gap-2 bg-surface" style="flex:0 0 auto;">
            <?= user_avatar_html($contacto, 36) ?>
            <strong class="text-truncate"><?= e($contacto['nombre']) ?></strong>
          </div>

          <!-- Messages -->
          <div id="chat-box" class="chat-messages">
            <?php foreach ($chatMensajes as $msg): ?>
              <?php $isMe = (int) $msg['id_emisor'] === $user['id_usuario']; ?>
              <div class="mb-2 d-flex <?= $isMe ? 'justify-content-end' : 'justify-content-start' ?>">
                <div class="px-3 py-2 rounded-3 <?= $isMe ? '' : 'chat-bubble-recv' ?>"
                     style="max-width:75%;background:<?= $isMe ? 'var(--edu-primary);color:#fff;' : 'var(--edu-surface);border:1px solid var(--edu-border);' ?>;word-break:break-word;">
                  <div style="font-size:.9rem;line-height:1.4;"><?= nl2br(e($msg['contenido'])) ?></div>
                  <?php
                  $archivos = $pdo->prepare(
                      'SELECT a.nombre_original, a.nombre_guardado, a.tipo_mime
                       FROM mensajes_archivos ma JOIN archivos_adjuntos a ON a.id_archivo = ma.id_archivo
                       WHERE ma.id_mensaje = :mid'
                  );
                  $archivos->execute(['mid' => $msg['id_mensaje']]);
                  $archList = $archivos->fetchAll();
                  foreach ($archList as $ar): ?>
                    <a href="<?= e($ar['nombre_guardado']) ?>" target="_blank" class="chat-archivo <?= $isMe ? '' : '' ?>" style="<?= $isMe ? 'color:#fff;' : 'color:var(--edu-primary);' ?>">
                      <?= file_icon($ar['tipo_mime']) ?>
                      <span class="text-truncate" style="max-width:140px;"><?= e($ar['nombre_original']) ?></span>
                    </a>
                  <?php endforeach; ?>
                  <div class="text-end mt-1" style="font-size:.6rem;opacity:.5;"><?= e(date('H:i', strtotime((string) $msg['fecha_envio']))) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
            <?php if (empty($chatMensajes)): ?>
              <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                <div class="text-center">
                  <i class="bi bi-chat-dots" style="font-size:3rem;opacity:.2;"></i>
                  <p class="mt-2">Inicia la conversación con <?= e($contacto['nombre']) ?>.</p>
                </div>
              </div>
            <?php endif; ?>
          </div>

          <!-- Input -->
          <form id="form-msg" class="chat-input-bar" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="id_receptor" value="<?= (int) $contacto['id_usuario'] ?>">
            <label class="file-label" title="Adjuntar archivo">
              <i class="bi bi-paperclip"></i>
              <input type="file" name="archivo" accept="image/*,.pdf,.mp3,.mp4,.docx">
            </label>
            <div id="file-info" class="file-preview" style="display:none;"><i class="bi bi-file-earmark"></i> <span></span></div>
            <textarea class="form-control" name="contenido" placeholder="Escribe un mensaje..." maxlength="2000" rows="1" required></textarea>
            <button class="btn btn-edu btn-send" type="submit" title="Enviar"><i class="bi bi-send-fill"></i></button>
          </form>
        <?php else: ?>
          <div class="d-flex align-items-center justify-content-center h-100 text-muted">
            <div class="text-center">
              <i class="bi bi-chat-dots" style="font-size:3rem;opacity:.3;"></i>
              <p class="mt-2">Selecciona una conversación para comenzar.</p>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const box = document.getElementById('chat-box');
  if (box) box.scrollTop = box.scrollHeight;

  // Auto-resize textarea
  const textarea = document.querySelector('#form-msg textarea');
  if (textarea) {
    textarea.addEventListener('input', function(){
      this.style.height = 'auto';
      this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });
    textarea.addEventListener('keydown', function(e){
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        this.closest('form').dispatchEvent(new Event('submit'));
      }
    });
  }

  // File preview
  const fileInput = document.querySelector('#form-msg input[type="file"]');
  const fileInfo = document.getElementById('file-info');
  if (fileInput && fileInfo) {
    fileInput.addEventListener('change', function(){
      if (this.files.length > 0) {
        fileInfo.style.display = '';
        fileInfo.querySelector('span').textContent = this.files[0].name;
      } else {
        fileInfo.style.display = 'none';
      }
    });
  }

  // Send form
  const form = document.getElementById('form-msg');
  if (!form) return;
  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    const ta = this.querySelector('textarea');
    const contenido = ta ? ta.value.trim() : '';
    const hasFile = fileInput && fileInput.files.length > 0;
    if (!contenido && !hasFile) return;
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    try {
      const resp = await fetch('api/mensajes.php', { method: 'POST', body: fd });
      const data = await resp.json();
      if (data.ok) {
        ta.value = '';
        ta.style.height = 'auto';
        if (fileInput) fileInput.value = '';
        if (fileInfo) fileInfo.style.display = 'none';
        location.reload();
      } else {
        alert(data.error || 'Error al enviar.');
      }
    } catch(err) {
      alert('Error de conexión.');
    }
    btn.disabled = false;
  });

  <?php if ($paraId): ?>
  setInterval(async () => {
    try {
      const resp = await fetch('api/mensajes.php?accion=unread');
      const data = await resp.json();
      if (data.ok && data.count > 0) {
        const badge = document.getElementById('msg-badge');
        if (badge) { badge.textContent = data.count; badge.style.display = ''; }
      } else {
        const badge = document.getElementById('msg-badge');
        if (badge) badge.style.display = 'none';
      }
    } catch(e) {}
  }, 30000);
  <?php endif; ?>
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
