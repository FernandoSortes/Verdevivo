<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$s = dashboard_stats();
$recent = recent_orders(8);
$top = top_products(5);
$days = sales_by_day(7);
$baixo = db()->query('SELECT id, name, stock FROM products WHERE active = 1 AND stock <= ' . LOW_STOCK . ' ORDER BY stock, name LIMIT 10')->fetchAll();
$pageTitle = 'Dashboard';
$adminActive = 'dashboard';
include __DIR__ . '/../includes/admin_header.php';
$cards = [
  ['Produtos ativos', $s['products']], ['Pedidos', $s['orders']], ['Faturamento', money($s['revenue'])],
  ['Produtos vendidos', $s['sold']], ['Estoque baixo', $s['low']], ['Esgotados', $s['out']],
];
?>
<a class="btn btn-success mb-3" href="<?= e(url('admin/produto_form.php')) ?>">+ Adicionar produto</a>
<div class="row g-3 mb-4">
  <?php foreach ($cards as [$l, $v]): ?><div class="col-6 col-lg-4"><div class="stat"><span class="text-muted"><?= e($l) ?></span><b><?= e($v) ?></b></div></div><?php endforeach; ?>
</div>
<p class="small text-muted">Faturamento e produtos vendidos consideram pedidos com status: <?= e(implode(', ', array_map(fn($k) => ORDER_STATUS[$k], REVENUE_STATUSES))) ?>.</p>
<h2 class="h6">Produtos com estoque baixo ou esgotados</h2>
<table class="table table-sm bg-white mb-4"><thead><tr><th>Produto</th><th>Estoque</th><th></th></tr></thead><tbody>
<?php foreach ($baixo as $b): [$lb, $cl] = stock_status((int)$b['stock']); ?><tr><td><?= e($b['name']) ?></td><td><span class="badge text-bg-<?= $cl ?>"><?= (int)$b['stock'] ?> · <?= e($lb) ?></span></td><td><a class="btn btn-sm btn-outline-success" href="<?= e(url('admin/estoque.php?f=baixo')) ?>">Repor</a></td></tr><?php endforeach; ?>
<?php if (!$baixo): ?><tr><td colspan="3" class="text-muted">Nenhum produto com estoque baixo.</td></tr><?php endif; ?></tbody></table>
<div class="row g-3">
  <div class="col-lg-7"><h2 class="h6">Pedidos recentes</h2><div class="table-responsive"><table class="table table-sm bg-white">
    <thead><tr><th>#</th><th>Cliente</th><th>Data</th><th>Valor</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($recent as $o): ?><tr><td><a href="<?= e(url('admin/pedidos.php?id=' . (int)$o['id'])) ?>"><?= (int)$o['id'] ?></a></td><td><?= e($o['customer']) ?></td><td><?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></td><td><?= money($o['total']) ?></td><td><?= e(ORDER_STATUS[$o['status']]) ?></td></tr><?php endforeach; ?>
    <?php if (!$recent): ?><tr><td colspan="5" class="text-muted">Nenhum pedido ainda.</td></tr><?php endif; ?></tbody></table></div></div>
  <div class="col-lg-5"><h2 class="h6">Mais vendidos</h2><table class="table table-sm bg-white"><thead><tr><th>Produto</th><th>Qtd</th><th>Valor</th></tr></thead><tbody>
    <?php foreach ($top as $t): ?><tr><td><?= e($t['product_name']) ?></td><td><?= (int)$t['qty'] ?></td><td><?= money($t['amount']) ?></td></tr><?php endforeach; ?>
    <?php if (!$top): ?><tr><td colspan="3" class="text-muted">Sem vendas.</td></tr><?php endif; ?></tbody></table>
    <h2 class="h6 mt-3">Vendas (7 dias)</h2><table class="table table-sm bg-white"><thead><tr><th>Dia</th><th>Pedidos</th><th>Valor</th></tr></thead><tbody>
    <?php foreach ($days as $d): ?><tr><td><?= e(date('d/m', strtotime($d['d']))) ?></td><td><?= (int)$d['n'] ?></td><td><?= money($d['amount']) ?></td></tr><?php endforeach; ?>
    <?php if (!$days): ?><tr><td colspan="3" class="text-muted">Sem vendas no período.</td></tr><?php endif; ?></tbody></table></div>
</div>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
