<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_role('estudiante');

$user = current_user();
$uid = (int) $user['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'comprar') {
    csrf_verify();
    $res = buy_item($uid, (string) ($_POST['item'] ?? ''));
    flash($res['ok'] ? 'ok' : 'error', $res['msg']);
    redirect('tienda.php');
}

$items = shop_items();
$poderes = user_powerups($uid);
$lives = user_lives($uid);

$u = db()->prepare('SELECT puntos FROM usuarios WHERE id_usuario = :id');
$u->execute(['id' => $uid]);
$puntos = (int) $u->fetchColumn();

$cantidad = [
    'vida_extra'   => $lives['vidas'],
    'vidas_full'   => $lives['vidas'],
    'vidas_max'    => $poderes['vidas_max'],
    'escudo_racha' => $poderes['escudos'],
    'comodin_50'   => $poderes['comodines'],
    'doble_puntos' => $poderes['doble'],
];
$tope = [
    'vida_extra'   => $lives['max'] >= 9 ? 9 : $lives['max'],
    'vidas_full'   => $lives['max'],
    'vidas_max'    => 9,
    'escudo_racha' => 3,
    'comodin_50'   => 3,
    'doble_puntos' => 2,
];

$hist = db()->prepare(
    'SELECT c.item, c.costo, c.fecha FROM compras c WHERE c.id_usuario = :u ORDER BY c.fecha DESC LIMIT 8'
);
$hist->execute(['u' => $uid]);
$compras = $hist->fetchAll();

$pageTitle = 'Tienda';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-4 py-lg-5" style="max-width:960px">

  <section class="shop-hero mb-4">
    <span class="mascot mascot-sm" aria-hidden="true">🛍️</span>
    <div>
      <span class="section-eyebrow">Tienda de Nova</span>
      <h1 class="hero-title mb-1">Gasta tus puntos en poderes</h1>
      <p class="hero-sub mb-0">Gana puntos resolviendo guías y canjéalos por vidas y potenciadores.</p>
    </div>
    <div class="shop-wallet">
      <span class="xp-pill"><i class="bi bi-lightning-charge-fill"></i> <?= $puntos ?> pts</span>
      <span class="xp-pill hearts"><i class="bi bi-heart-fill"></i> <?= $lives['vidas'] ?>/<?= $lives['max'] ?></span>
    </div>
  </section>

  <?php render_alerts(); ?>

  <div class="shop-grid">
    <?php foreach ($items as $code => $it):
      $actual = $cantidad[$code];
      $max = $tope[$code];
      $lleno = ($code === 'vida_extra' || $code === 'vidas_full')
          ? ($lives['vidas'] >= $lives['max'])
          : ($actual >= $max);
      $alcanza = $puntos >= (int) $it['precio'];
      $puede = !$lleno && $alcanza;
    ?>
      <article class="shop-item<?= $lleno ? ' shop-item--full' : '' ?>">
        <span class="shop-ico" style="--sc:<?= e($it['color']) ?>"><i class="bi bi-<?= e($it['icono']) ?>"></i></span>
        <h2 class="shop-name"><?= e($it['nombre']) ?></h2>
        <p class="shop-desc"><?= e($it['desc']) ?></p>
        <div class="shop-meta">
          <?php if ($code === 'vida_extra' || $code === 'vidas_full'): ?>
            <span class="shop-stock"><i class="bi bi-heart-fill"></i> <?= $lives['vidas'] ?>/<?= $lives['max'] ?></span>
          <?php else: ?>
            <span class="shop-stock">Tienes <strong><?= $actual ?></strong> / <?= $max ?></span>
          <?php endif; ?>
          <span class="shop-price"><i class="bi bi-lightning-charge-fill"></i> <?= (int) $it['precio'] ?></span>
        </div>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="accion" value="comprar">
          <input type="hidden" name="item" value="<?= e($code) ?>">
          <button class="btn <?= $puede ? 'btn-edu' : 'btn-edu-outline disabled' ?> w-100" type="submit"
                  <?= $puede ? '' : 'disabled' ?>>
            <?php if ($lleno): ?>
              <i class="bi bi-check2-circle"></i> Completo
            <?php elseif (!$alcanza): ?>
              Faltan <?= (int) $it['precio'] - $puntos ?> pts
            <?php else: ?>
              <i class="bi bi-bag-plus-fill"></i> Comprar
            <?php endif; ?>
          </button>
        </form>
      </article>
    <?php endforeach; ?>
  </div>

  <?php if ($compras): ?>
    <section class="card-edu p-3 p-lg-4 mt-4">
      <h2 class="h6 fw-bold mb-3"><i class="bi bi-receipt"></i> Compras recientes</h2>
      <div class="shop-history">
        <?php foreach ($compras as $c):
          $nombre = $items[$c['item']]['nombre'] ?? $c['item'];
        ?>
          <div class="shop-history-row">
            <span><i class="bi bi-bag-check"></i> <?= e($nombre) ?></span>
            <span class="text-muted small"><?= e(date('d/m/Y H:i', strtotime((string) $c['fecha']))) ?></span>
            <span class="shop-price"><i class="bi bi-lightning-charge-fill"></i> -<?= (int) $c['costo'] ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <div class="text-center mt-4">
    <a class="btn-edu-outline" href="estudiante.php"><i class="bi bi-arrow-left"></i> Volver a mis guías</a>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
