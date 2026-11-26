<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$user = current_user();

// Tela de confirmação (?ok=ID): só o dono do pedido (ou admin) enxerga
if (isset($_GET['ok'])) {
    $st = db()->prepare('SELECT * FROM orders WHERE id = ? AND (user_id = ? OR ?)');
    $st->execute([(int)$_GET['ok'], $user['id'], is_admin() ? 1 : 0]);
    $order = $st->fetch();
    if (!$order) redirect('index.php');
    $it = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
    $it->execute([$order['id']]);
    $items = $it->fetchAll();
    $pageTitle = 'Pedido confirmado';
    include __DIR__ . '/includes/header.php';
    ?>
    <div class="alert alert-success"><h1 class="h4">✅ Pedido #<?= (int)$order['id'] ?> realizado!</h1>Pagamento simulado via <?= e(PAYMENTS[$order['payment_method']]) ?>.</div>
    <table class="table bg-white"><thead><tr><th>Produto</th><th>Qtd</th><th>Subtotal</th></tr></thead><tbody>
    <?php foreach ($items as $i): ?><tr><td><?= e($i['product_name']) ?></td><td><?= (int)$i['quantity'] ?></td><td><?= money($i['subtotal']) ?></td></tr><?php endforeach; ?>
    </tbody><tfoot><tr><th colspan="2">Total</th><th><?= money($order['total']) ?></th></tr></tfoot></table>
    <a class="btn btn-success" href="<?= e(url('produtos.php')) ?>">Continuar comprando</a>
    <?php
    include __DIR__ . '/includes/footer.php';
    exit;
}

$cart = $_SESSION['cart'] ?? [];
if (!$cart) { flash('warning', 'Seu carrinho está vazio.'); redirect('carrinho.php'); }

$f = ['name' => $user['name'], 'zip' => '', 'address' => '', 'city' => '', 'payment' => 'pix'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($f as $k => $_) $f[$k] = trim((string)($_POST[$k] ?? ''));
    $errors = [];
    if (mb_strlen($f['name']) < 3) $errors[] = 'Informe o nome completo.';
    if (!preg_match('/^\d{5}-?\d{3}$/', $f['zip'])) $errors[] = 'CEP inválido (use 00000-000).';
    if (mb_strlen($f['address']) < 5) $errors[] = 'Informe o endereço.';
    if (mb_strlen($f['city']) < 2) $errors[] = 'Informe a cidade.';
    if (!isset(PAYMENTS[$f['payment']])) $errors[] = 'Forma de pagamento inválida.';
    if (!$errors) {
        try {
            $id = create_order((int)$user['id'], $cart, ['name' => $f['name'], 'zip' => $f['zip'], 'address' => $f['address'], 'city' => $f['city']], $f['payment']);
            unset($_SESSION['cart']);
            redirect('checkout.php?ok=' . $id);
        } catch (RuntimeException $e) {
            flash('danger', $e->getMessage());       // erro de regra (ex.: estoque): nada foi gravado
        } catch (Throwable $e) {
            error_log($e->getMessage());
            flash('danger', 'Erro ao finalizar o pedido. Tente novamente.');
        }
    } else foreach ($errors as $er) flash('danger', $er);
}

// Resumo calculado com preços do banco
$ids = array_map('intval', array_keys($cart));
$ph = implode(',', array_fill(0, count($ids), '?'));
$st = db()->prepare("SELECT id, name, price FROM products WHERE id IN ($ph)");
$st->execute($ids);
$total = 0;
$rows = $st->fetchAll();
foreach ($rows as $r) $total += $r['price'] * $cart[$r['id']];
$pageTitle = 'Checkout · VerdeVivo';
include __DIR__ . '/includes/header.php';
?>
<h1 class="h4 mb-3">Checkout</h1>
<div class="row g-3">
  <form method="post" class="col-lg-8"><?= csrf_field() ?><div class="row g-2">
    <div class="col-md-6"><label class="form-label">Nome completo</label><input class="form-control" name="name" value="<?= e($f['name']) ?>" required></div>
    <div class="col-md-3"><label class="form-label">CEP</label><input class="form-control" name="zip" value="<?= e($f['zip']) ?>" placeholder="00000-000" required></div>
    <div class="col-md-3"><label class="form-label">Cidade</label><input class="form-control" name="city" value="<?= e($f['city']) ?>" required></div>
    <div class="col-12"><label class="form-label">Endereço</label><input class="form-control" name="address" value="<?= e($f['address']) ?>" required></div>
    <div class="col-md-6"><label class="form-label">Pagamento (simulado)</label><select class="form-select" name="payment">
      <?php foreach (PAYMENTS as $k => $v): ?><option value="<?= e($k) ?>" <?= $f['payment'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
    <div class="col-12"><button class="btn btn-success">Confirmar pedido</button></div></div></form>
  <div class="col-lg-4"><div class="stat"><?php foreach ($rows as $r): ?><div class="d-flex justify-content-between"><span><?= (int)$cart[$r['id']] ?>× <?= e($r['name']) ?></span><span><?= money($r['price'] * $cart[$r['id']]) ?></span></div><?php endforeach; ?>
    <hr><div class="d-flex justify-content-between"><b>Total</b><b><?= money($total) ?></b></div></div></div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
