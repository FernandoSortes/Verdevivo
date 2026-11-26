<?php
require_once __DIR__ . '/includes/auth.php';
// O carrinho vive na sessão como [id => qtd]. Preço e estoque são sempre lidos do banco.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $pid = (int)($_POST['product_id'] ?? 0);
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    $st = db()->prepare('SELECT id, name, stock FROM products WHERE id = ? AND active = 1');
    $st->execute([$pid]);
    $p = $st->fetch();
    if ($action === 'remove') {
        unset($_SESSION['cart'][$pid]);
    } elseif (!$p) {
        flash('danger', 'Produto indisponível.');
    } elseif ($action === 'add') {
        $new = ($_SESSION['cart'][$pid] ?? 0) + $qty;
        if ($new > (int)$p['stock']) { $new = (int)$p['stock']; flash('warning', 'Quantidade ajustada ao estoque de "' . $p['name'] . '".'); }
        else flash('success', 'Adicionado ao carrinho.');
        $_SESSION['cart'][$pid] = $new;
    } elseif ($action === 'set') {
        if ($qty > (int)$p['stock']) { $qty = (int)$p['stock']; flash('warning', 'Quantidade ajustada ao estoque disponível.'); }
        $_SESSION['cart'][$pid] = $qty;
    }
    $back = ($action === 'add') ? ($_SERVER['HTTP_REFERER'] ?? url('carrinho.php')) : url('carrinho.php');
    header('Location: ' . (str_starts_with($back, 'http') || str_starts_with($back, '/') ? $back : url('carrinho.php')));
    exit;
}
$items = [];
$total = 0;
if (!empty($_SESSION['cart'])) {
    $ids = array_map('intval', array_keys($_SESSION['cart']));
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $st = db()->prepare("SELECT p.*, c.name AS category FROM products p JOIN categories c ON c.id = p.category_id WHERE p.id IN ($ph) AND p.active = 1");
    $st->execute($ids);
    foreach ($st->fetchAll() as $p) {
        $p['qty'] = min((int)$_SESSION['cart'][$p['id']], (int)$p['stock']);
        $p['sub'] = $p['qty'] * (float)$p['price'];
        $total += $p['sub'];
        $items[] = $p;
    }
}
$pageTitle = 'Carrinho · VerdeVivo';
include __DIR__ . '/includes/header.php';
?>
<h1 class="h4 mb-3">Seu carrinho</h1>
<?php if (!$items): ?>
  <p class="text-muted">Nenhum item ainda.</p><a class="btn btn-success" href="<?= e(url('produtos.php')) ?>">Ver catálogo</a>
<?php else: ?>
<div class="row g-3"><div class="col-lg-8"><div class="table-responsive"><table class="table align-middle bg-white">
  <thead><tr><th>Produto</th><th style="width:150px">Qtd</th><th>Preço</th><th>Subtotal</th><th></th></tr></thead><tbody>
  <?php foreach ($items as $i): ?><tr>
    <td><?= e($i['name']) ?></td>
    <td><form method="post" class="d-flex gap-1"><?= csrf_field() ?><input type="hidden" name="action" value="set"><input type="hidden" name="product_id" value="<?= (int)$i['id'] ?>">
      <input type="number" name="qty" class="form-control form-control-sm" value="<?= (int)$i['qty'] ?>" min="1" max="<?= (int)$i['stock'] ?>"><button class="btn btn-sm btn-outline-success">OK</button></form></td>
    <td><?= money($i['price']) ?></td><td><?= money($i['sub']) ?></td>
    <td><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="product_id" value="<?= (int)$i['id'] ?>"><button class="btn btn-sm btn-outline-danger">✕</button></form></td>
  </tr><?php endforeach; ?></tbody></table></div></div>
  <div class="col-lg-4"><div class="stat"><div>Total</div><b><?= money($total) ?></b><a class="btn btn-success w-100 mt-2" href="<?= e(url('checkout.php')) ?>">Finalizar compra</a></div></div></div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
