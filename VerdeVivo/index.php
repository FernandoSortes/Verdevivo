<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'VerdeVivo Jardinagem';
$products = db()->query('SELECT p.*, c.name AS category FROM products p JOIN categories c ON c.id = p.category_id WHERE p.active = 1 ORDER BY p.id DESC LIMIT 8')->fetchAll();
$cats = db()->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<div class="hero mb-4"><h1 class="h3">Seu jardim começa aqui</h1><p class="mb-0">Plantas, vasos, ferramentas e insumos.</p></div>
<div class="d-flex flex-wrap gap-2 mb-3">
  <?php foreach ($cats as $c): ?><a class="btn btn-outline-success btn-sm" href="<?= e(url('produtos.php?cat=' . (int)$c['id'])) ?>"><?= e($c['name']) ?></a><?php endforeach; ?>
</div>
<h2 class="h5">Novidades</h2>
<div class="row g-3">
  <?php foreach ($products as $p) include __DIR__ . '/includes/product_card.php'; ?>
  <?php if (!$products): ?><p class="text-muted">Nenhum produto cadastrado.</p><?php endif; ?>
</div>
<div class="mt-3"><a href="<?= e(url('produtos.php')) ?>" class="btn btn-success">Ver catálogo completo</a></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
