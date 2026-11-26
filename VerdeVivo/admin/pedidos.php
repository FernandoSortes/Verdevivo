<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $oid = (int)($_POST['id'] ?? 0);
    try {
        update_order_status($oid, (string)($_POST['status'] ?? ''), (int)current_user()['id']);
        flash('success', 'Pedido #' . $oid . ' atualizado.');
    } catch (RuntimeException $e) { flash('danger', $e->getMessage()); }
    redirect('admin/pedidos.php?id=' . $oid);
}
$id = (int)($_GET['id'] ?? 0);
$pageTitle = $id ? "Pedido #$id" : 'Pedidos';
$adminActive = 'pedidos';
if ($id) {
    $st = $pdo->prepare('SELECT o.*, u.name customer, u.email FROM orders o JOIN users u ON u.id = o.user_id WHERE o.id = ?');
    $st->execute([$id]);
    $o = $st->fetch();
    if (!$o) { flash('danger', 'Pedido não encontrado.'); redirect('admin/pedidos.php'); }
    $it = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
    $it->execute([$id]);
    $items = $it->fetchAll();
    include __DIR__ . '/../includes/admin_header.php';
    ?>
    <a href="<?= e(url('admin/pedidos.php')) ?>" class="small">← Voltar</a>
    <div class="row g-3 mt-1"><div class="col-lg-8">
      <table class="table table-sm bg-white"><thead><tr><th>Produto</th><th>Qtd</th><th>Unitário</th><th>Subtotal</th></tr></thead><tbody>
      <?php foreach ($items as $i): ?><tr><td><?= e($i['product_name']) ?></td><td><?= (int)$i['quantity'] ?></td><td><?= money($i['unit_price']) ?></td><td><?= money($i['subtotal']) ?></td></tr><?php endforeach; ?>
      </tbody><tfoot><tr><th colspan="3">Total</th><th><?= money($o['total']) ?></th></tr></tfoot></table></div>
    <div class="col-lg-4"><div class="stat">
      <p class="mb-1"><b><?= e($o['customer']) ?></b><br><small><?= e($o['email']) ?></small></p>
      <p class="small mb-1"><?= e($o['ship_name']) ?><br><?= e($o['ship_address']) ?> — <?= e($o['ship_city']) ?> (<?= e($o['ship_zip']) ?>)</p>
      <p class="small">Pagamento: <?= e(PAYMENTS[$o['payment_method']]) ?><br>Data: <?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></p>
      <?php if ($o['status'] === 'cancelado'): ?><span class="badge text-bg-danger">Cancelado</span>
      <?php else: ?>
      <form method="post" class="d-flex gap-1 mb-2"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$id ?>">
        <select name="status" class="form-select form-select-sm"><?php foreach (ORDER_STATUS as $k => $v): if ($k === 'cancelado') continue; ?><option value="<?= $k ?>" <?= $o['status'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select><button class="btn btn-sm btn-success">Atualizar</button></form>
      <form method="post" data-confirm="Cancelar este pedido? O estoque será devolvido."><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$id ?>"><input type="hidden" name="status" value="cancelado"><button class="btn btn-sm btn-outline-danger">Cancelar pedido</button></form>
      <?php endif; ?></div></div></div>
    <?php
    include __DIR__ . '/../includes/admin_footer.php';
    exit;
}
$sf = (string)($_GET['status'] ?? '');
$where = isset(ORDER_STATUS[$sf]) ? 'o.status = ?' : '1=1';
$params = isset(ORDER_STATUS[$sf]) ? [$sf] : [];
$page = page_param();
$per = 15;
$c = $pdo->prepare("SELECT COUNT(*) FROM orders o WHERE $where");
$c->execute($params);
$total = (int)$c->fetchColumn();
$off = ($page - 1) * $per;
$st = $pdo->prepare("SELECT o.id, o.total, o.status, o.created_at, u.name customer FROM orders o JOIN users u ON u.id = o.user_id WHERE $where ORDER BY o.id DESC LIMIT $per OFFSET $off");
$st->execute($params);
include __DIR__ . '/../includes/admin_header.php';
?>
<form class="mb-3 d-flex gap-2" method="get"><select name="status" class="form-select form-select-sm" style="width:180px"><option value="">Todos os status</option><?php foreach (ORDER_STATUS as $k => $v): ?><option value="<?= $k ?>" <?= $sf === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select><button class="btn btn-sm btn-outline-success">Filtrar</button></form>
<div class="table-responsive"><table class="table table-sm bg-white"><thead><tr><th>#</th><th>Cliente</th><th>Data</th><th>Valor</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($st->fetchAll() as $o): ?><tr><td><?= (int)$o['id'] ?></td><td><?= e($o['customer']) ?></td><td><?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></td><td><?= money($o['total']) ?></td><td><?= e(ORDER_STATUS[$o['status']]) ?></td><td><a class="btn btn-sm btn-outline-success" href="?id=<?= (int)$o['id'] ?>">Detalhes</a></td></tr><?php endforeach; ?>
</tbody></table></div><?= paginate($total, $per, $page, ['status' => $sf]) ?>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
