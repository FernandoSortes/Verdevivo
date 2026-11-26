<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Catálogo · VerdeVivo';
$cat = (int)($_GET['cat'] ?? 0);
$q = trim((string)($_GET['q'] ?? ''));
$page = page_param();
$per = 12;
$where = 'p.active = 1';
$params = [];
if ($cat > 0) { $where .= ' AND p.category_id = ?'; $params[] = $cat; }
if ($q !== '') { $where .= ' AND p.name LIKE ?'; $params[] = '%' . $q . '%'; }
$count = db()->prepare("SELECT COUNT(*) FROM products p WHERE $where");
$count->execute($params);
$total = (int)$count->fetchColumn();
$offset = ($page - 1) * $per;
$st = db()->prepare("SELECT p.*, c.name AS category FROM products p JOIN categories c ON c.id = p.category_id WHERE $where ORDER BY p.name LIMIT $per OFFSET $offset");
$st->execute($params);
$products = $st->fetchAll();
$cats = db()->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<h1 class="h4 mb-3">Catálogo</h1>
<form class="row g-2 mb-3" method="get">
  <div class="col-md-5"><input class="form-control" name="q" placeholder="Buscar produto" value="<?= e($q) ?>"></div>
  <div class="col-md-4"><select class="form-select" name="cat"><option value="0">Todas as categorias</option>
    <?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $cat === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><button class="btn btn-success w-100">Filtrar</button></div>
</form>
<div class="row g-3">
  <?php foreach ($products as $p) include __DIR__ . '/includes/product_card.php'; ?>
  <?php if (!$products): ?><p class="text-muted">Nenhum produto encontrado.</p><?php endif; ?>
</div>
<div class="mt-3"><?= paginate($total, $per, $page, ['q' => $q, 'cat' => $cat]) ?></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
