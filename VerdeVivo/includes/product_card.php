<?php /* Espera $p (produto + category). */ ?>
<div class="col-6 col-md-4 col-lg-3">
  <div class="card h-100 vv-card">
    <a href="<?= e(url('produto.php?id=' . (int)$p['id'])) ?>" class="text-decoration-none"><?= product_image($p) ?></a>
    <div class="card-body d-flex flex-column gap-1">
      <small class="text-muted"><?= e($p['category']) ?></small>
      <h6 class="card-title mb-0"><?= e($p['name']) ?></h6>
      <div class="vv-price"><?= money($p['price']) ?></div>
      <?php if ((int)$p['stock'] > 0): ?>
        <form method="post" action="<?= e(url('carrinho.php')) ?>" class="mt-auto">
          <?= csrf_field() ?><input type="hidden" name="action" value="add"><input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
          <button class="btn btn-success btn-sm w-100">Adicionar</button>
        </form>
      <?php else: ?><button class="btn btn-outline-secondary btn-sm w-100 mt-auto" disabled>Esgotado</button><?php endif; ?>
    </div>
  </div>
</div>
