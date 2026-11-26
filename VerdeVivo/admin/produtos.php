<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $st = $pdo->prepare('SELECT id, image FROM products WHERE id = ?');
    $st->execute([$id]);
    $p = $st->fetch();
    if (!$p) { flash('danger', 'Produto não encontrado.'); }
    elseif (($_POST['action'] ?? '') === 'delete') {
        $h = $pdo->prepare('SELECT COUNT(*) FROM order_items WHERE product_id = ?');
        $h->execute([$id]);
        if ((int)$h->fetchColumn() > 0) {          // tem histórico de vendas: só desativa
            $pdo->prepare('UPDATE products SET active = 0 WHERE id = ?')->execute([$id]);
            flash('warning', 'O produto tem vendas registradas e foi DESATIVADO (histórico preservado).');
        } else {
            $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
            delete_image($p['image']);
            flash('success', 'Produto excluído.');
        }
    } elseif (($_POST['action'] ?? '') === 'toggle') {
        $pdo->prepare('UPDATE products SET active = 1 - active WHERE id = ?')->execute([$id]);
        flash('success', 'Situação atualizada.');
    }
    redirect('admin/produtos.php');
}
$q = trim((string)($_GET['q'] ?? ''));
$cat = (int)($_GET['cat'] ?? 0);
$sf = (string)($_GET['stock'] ?? '');
$where = '1=1';
$params = [];
if ($q !== '') { $where .= ' AND p.name LIKE ?'; $params[] = "%$q%"; }
if ($cat > 0) { $where .= ' AND p.category_id = ?'; $params[] = $cat; }
if ($sf === 'baixo') $where .= ' AND p.stock BETWEEN 1 AND ' . LOW_STOCK;
elseif ($sf === 'esgotado') $where .= ' AND p.stock = 0';
elseif ($sf === 'normal') $where .= ' AND p.stock > ' . LOW_STOCK;
$page = page_param();
$per = 10;
$c = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $where");
$c->execute($params);
$total = (int)$c->fetchColumn();
$off = ($page - 1) * $per;
$st = $pdo->prepare("SELECT p.*, c.name AS category FROM products p JOIN categories c ON c.id = p.category_id WHERE $where ORDER BY p.id DESC LIMIT $per OFFSET $off");
$st->execute($params);
$rows = $st->fetchAll();
$cats = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$pageTitle = 'Produtos';
$adminActive = 'produtos';
include __DIR__ . '/../includes/admin_header.php';
?>
<div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
  <form class="d-flex flex-wrap gap-2" method="get">
    <input class="form-control form-control-sm" style="width:180px" name="q" placeholder="Buscar nome" value="<?= e($q) ?>">
    <select class="form-select form-select-sm" style="width:150px" name="cat"><option value="0">Categoria</option><?php foreach ($cats as $ct): ?><option value="<?= (int)$ct['id'] ?>" <?= $cat === (int)$ct['id'] ? 'selected' : '' ?>><?= e($ct['name']) ?></option><?php endforeach; ?></select>
    <select class="form-select form-select-sm" style="width:130px" name="stock"><option value="">Estoque</option><?php foreach (['normal' => 'Normal', 'baixo' => 'Baixo', 'esgotado' => 'Esgotado'] as $k => $v): ?><option value="<?= $k ?>" <?= $sf === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select>
    <button class="btn btn-sm btn-outline-success">Filtrar</button>
  </form>
  <a class="btn btn-success btn-sm" href="<?= e(url('admin/produto_form.php')) ?>">+ Novo produto</a>
</div>
<div class="table-responsive"><table class="table table-sm align-middle bg-white">
<thead><tr><th></th><th>Produto</th><th>Categoria</th><th>Preço</th><th>Estoque</th><th>Situação</th><th>Ações</th></tr></thead><tbody>
<?php foreach ($rows as $p): [$lb, $cl] = stock_status((int)$p['stock']); ?>
<tr class="<?= $p['active'] ? '' : 'table-secondary' ?>">
  <td><img class="thumb" src="<?= e(url_imagem($p['image'])) ?>" alt=""></td>
  <td><?= e($p['name']) ?></td><td><?= e($p['category']) ?></td><td><?= money($p['price']) ?></td>
  <td><?= (int)$p['stock'] ?> <span class="badge text-bg-<?= $cl ?>"><?= e($lb) ?></span></td>
  <td><?= $p['active'] ? 'Ativo' : 'Inativo' ?></td>
  <td class="text-nowrap">
    <a class="btn btn-sm btn-outline-secondary" target="_blank" href="<?= e(url('produto.php?id=' . (int)$p['id'])) ?>">Ver na loja</a>
    <a class="btn btn-sm btn-outline-success" href="<?= e(url('admin/produto_form.php?id=' . (int)$p['id'])) ?>">Editar</a>
    <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="action" value="toggle"><button class="btn btn-sm btn-outline-secondary"><?= $p['active'] ? 'Desativar' : 'Ativar' ?></button></form>
    <form method="post" class="d-inline" data-confirm="Tem certeza de que deseja excluir este produto?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-sm btn-outline-danger">Excluir</button></form>
  </td></tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="7" class="text-muted">Nenhum produto encontrado.</td></tr><?php endif; ?>
</tbody></table></div>
<?= paginate($total, $per, $page, ['q' => $q, 'cat' => $cat, 'stock' => $sf]) ?>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
