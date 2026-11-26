<?php
require_once __DIR__ . '/includes/auth.php';
$st = db()->prepare('SELECT p.*, c.name AS category FROM products p JOIN categories c ON c.id = p.category_id WHERE p.id = ? AND (p.active = 1 OR ?)');
$st->execute([(int)($_GET['id'] ?? 0), is_admin() ? 1 : 0]);
$p = $st->fetch();
if (!$p) { http_response_code(404); flash('warning', 'Produto não encontrado.'); redirect('produtos.php'); }
$pageTitle = $p['name'] . ' · VerdeVivo';
[$label, $cls] = stock_status((int)$p['stock']);
include __DIR__ . '/includes/header.php';
?>
<div class="row g-4">
  <div class="col-md-5"><?= product_image($p, 'big-ph') ?></div>
  <div class="col-md-7">
    <small class="text-muted"><?= e($p['category']) ?></small>
    <?php if (!$p['active']): ?><div class="alert alert-secondary py-1">Pré-visualização: produto inativo (não aparece na loja).</div><?php endif; ?>
    <h1 class="h3"><?= e($p['name']) ?></h1>
    <div class="vv-price fs-4 mb-2"><?= money($p['price']) ?></div>
    <p><?= nl2br(e($p['description'])) ?></p>
    <p><span class="badge text-bg-<?= $cls ?>"><?= e($label) ?></span> <small class="text-muted"><?= (int)$p['stock'] ?> disponíveis</small></p>
    <?php if ((int)$p['stock'] > 0): ?>
    <form method="post" action="<?= e(url('carrinho.php')) ?>" class="d-flex gap-2" style="max-width:260px">
      <?= csrf_field() ?><input type="hidden" name="action" value="add"><input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
      <input type="number" name="qty" class="form-control" value="1" min="1" max="<?= (int)$p['stock'] ?>">
      <button class="btn btn-success">Adicionar</button>
    </form><?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
