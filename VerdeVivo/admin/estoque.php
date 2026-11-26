<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        $mode = ($_POST['mode'] ?? '') === 'entrada' ? 'entrada' : 'definir';
        $qtyRaw = trim((string)($_POST['qty'] ?? ''));
        if (!ctype_digit($qtyRaw)) throw new RuntimeException('Quantidade deve ser um inteiro não negativo.');
        stock_manual((int)($_POST['product_id'] ?? 0), (int)$qtyRaw, $mode, trim((string)($_POST['reason'] ?? '')), (int)current_user()['id']);
        flash('success', 'Estoque atualizado.');
    } catch (RuntimeException $e) { flash('danger', $e->getMessage()); }
    redirect('admin/estoque.php');
}
$f = (string)($_GET['f'] ?? '');
$where = $f === 'baixo' ? 'p.stock BETWEEN 1 AND ' . LOW_STOCK : ($f === 'esgotado' ? 'p.stock = 0' : '1=1');
$rows = $pdo->query("SELECT p.id, p.name, p.stock, p.active, c.name AS category FROM products p JOIN categories c ON c.id = p.category_id WHERE $where ORDER BY p.stock ASC, p.name")->fetchAll();
$moves = $pdo->query("SELECT m.*, p.name AS product, u.name AS who FROM stock_movements m JOIN products p ON p.id = m.product_id LEFT JOIN users u ON u.id = m.user_id ORDER BY m.id DESC LIMIT 30")->fetchAll();
$types = ['entrada' => 'Entrada', 'saida' => 'Saída por venda', 'ajuste' => 'Ajuste manual', 'devolucao' => 'Devolução'];
$pageTitle = 'Estoque';
$adminActive = 'estoque';
include __DIR__ . '/../includes/admin_header.php';
?>
<div class="mb-3 d-flex gap-2"><?php foreach (['' => 'Todos', 'baixo' => 'Estoque baixo', 'esgotado' => 'Esgotados'] as $k => $v): ?><a class="btn btn-sm btn-<?= $f === $k ? 'success' : 'outline-success' ?>" href="?f=<?= e($k) ?>"><?= e($v) ?></a><?php endforeach; ?></div>
<div class="table-responsive"><table class="table table-sm align-middle bg-white"><thead><tr><th>Produto</th><th>Categoria</th><th>Qtd</th><th>Situação</th><th>Ajustar</th></tr></thead><tbody>
<?php foreach ($rows as $r): [$lb, $cl] = stock_status((int)$r['stock']); ?><tr>
  <td><?= e($r['name']) ?><?= $r['active'] ? '' : ' <small class="text-muted">(inativo)</small>' ?></td><td><?= e($r['category']) ?></td><td><?= (int)$r['stock'] ?></td><td><span class="badge text-bg-<?= $cl ?>"><?= e($lb) ?></span></td>
  <td><form method="post" class="d-flex gap-1"><?= csrf_field() ?><input type="hidden" name="product_id" value="<?= (int)$r['id'] ?>">
    <select name="mode" class="form-select form-select-sm" style="width:120px"><option value="entrada">Somar</option><option value="definir">Definir total</option></select>
    <input name="qty" type="number" min="0" class="form-control form-control-sm" style="width:80px" required>
    <input name="reason" class="form-control form-control-sm" placeholder="Motivo" style="width:150px"><button class="btn btn-sm btn-success">OK</button></form></td></tr>
<?php endforeach; ?><?php if (!$rows): ?><tr><td colspan="5" class="text-muted">Nenhum produto neste filtro.</td></tr><?php endif; ?></tbody></table></div>
<h2 class="h6 mt-4">Últimas movimentações</h2>
<div class="table-responsive"><table class="table table-sm bg-white"><thead><tr><th>Data</th><th>Produto</th><th>Tipo</th><th>Qtd</th><th>Motivo</th><th>Por</th></tr></thead><tbody>
<?php foreach ($moves as $m): ?><tr><td><?= e(date('d/m H:i', strtotime($m['created_at']))) ?></td><td><?= e($m['product']) ?></td><td><?= e($types[$m['type']]) ?></td><td><?= ($m['quantity'] > 0 ? '+' : '') . (int)$m['quantity'] ?></td><td><?= e($m['reason']) ?></td><td><?= e($m['who'] ?? '-') ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
