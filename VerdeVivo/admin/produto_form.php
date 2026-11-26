<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$pdo = db();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$p = ['name' => '', 'category_id' => 0, 'description' => '', 'price' => '', 'stock' => 0, 'image' => null, 'active' => 1];
if ($id) {
    $st = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $st->execute([$id]);
    $p = $st->fetch();
    if (!$p) { flash('danger', 'Produto não encontrado.'); redirect('admin/produtos.php'); }
}
$cats = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $in = [
        'name' => trim((string)($_POST['name'] ?? '')),
        'category_id' => (int)($_POST['category_id'] ?? 0),
        'description' => trim((string)($_POST['description'] ?? '')),
        'price' => str_replace(',', '.', trim((string)($_POST['price'] ?? ''))),
        'stock' => trim((string)($_POST['stock'] ?? '')),
        'active' => isset($_POST['active']) ? 1 : 0,
    ];
    $err = [];
    if (mb_strlen($in['name']) < 2 || mb_strlen($in['name']) > 150) $err[] = 'Nome deve ter entre 2 e 150 caracteres.';
    $ok = $pdo->prepare('SELECT 1 FROM categories WHERE id = ?');
    $ok->execute([$in['category_id']]);
    if (!$ok->fetch()) $err[] = 'Categoria inválida.';
    if (!is_numeric($in['price']) || (float)$in['price'] <= 0 || (float)$in['price'] > 99999999) $err[] = 'Preço inválido.';
    if (!ctype_digit($in['stock'])) $err[] = 'Estoque deve ser um número inteiro não negativo.';
    $newImage = null;
    if (!$err) {
        try { $newImage = handle_image_upload('image'); } catch (RuntimeException $e) { $err[] = $e->getMessage(); }
    }
    if (!$err) {
        $stock = (int)$in['stock'];
        $pdo->beginTransaction();
        try {
            if ($id) {
                $pdo->prepare('UPDATE products SET category_id=?, name=?, description=?, price=?, image=?, active=? WHERE id=?')
                    ->execute([$in['category_id'], $in['name'], $in['description'], round((float)$in['price'], 2), $newImage ?? $p['image'], $in['active'], $id]);
                $delta = $stock - (int)$p['stock'];
                if ($delta !== 0) stock_apply($pdo, $id, $delta, 'ajuste', 'Edição do produto', null, current_user()['id']);
            } else {
                $pdo->prepare('INSERT INTO products (category_id, name, description, price, stock, image, active) VALUES (?,?,?,?,0,?,?)')
                    ->execute([$in['category_id'], $in['name'], $in['description'], round((float)$in['price'], 2), $newImage, $in['active']]);
                $id = (int)$pdo->lastInsertId();
                if ($stock > 0) stock_apply($pdo, $id, $stock, 'entrada', 'Estoque inicial', null, current_user()['id']);
            }
            $pdo->commit();
            if ($newImage && !empty($p['image'])) delete_image($p['image']);
            flash('success', 'Produto salvo com sucesso.');
            redirect('admin/produtos.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if ($newImage) delete_image($newImage);
            error_log($e->getMessage());
            $err[] = 'Erro ao salvar o produto.';
        }
    }
    foreach ($err as $m) flash('danger', $m);
    $p = array_merge($p, $in);
}
$pageTitle = $id ? 'Editar produto' : 'Novo produto';
$adminActive = 'produtos';
include __DIR__ . '/../includes/admin_header.php';
?>
<form method="post" enctype="multipart/form-data" class="row g-3" style="max-width:760px"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$id ?>">
  <div class="col-md-8"><label class="form-label">Nome</label><input class="form-control" name="name" value="<?= e($p['name']) ?>" required></div>
  <div class="col-md-4"><label class="form-label">Categoria</label><select class="form-select" name="category_id" required><option value="">Selecione</option>
    <?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)$p['category_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
  <div class="col-12"><label class="form-label">Descrição</label><textarea class="form-control" name="description" rows="3"><?= e($p['description']) ?></textarea></div>
  <div class="col-md-4"><label class="form-label">Preço (R$)</label><input class="form-control" name="price" value="<?= e($p['price']) ?>" required></div>
  <div class="col-md-4"><label class="form-label">Estoque</label><input type="number" min="0" class="form-control" name="stock" value="<?= e($p['stock']) ?>" required></div>
  <div class="col-md-4 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="active" id="active" <?= $p['active'] ? 'checked' : '' ?>><label class="form-check-label" for="active">Produto ativo</label></div></div>
  <div class="col-12"><label class="form-label">Imagem (JPG, PNG ou WEBP, até 2 MB)</label><input type="file" class="form-control" name="image" id="image" accept="image/jpeg,image/png,image/webp">
    <img id="preview" class="thumb mt-2 <?= $p['image'] ? '' : 'd-none' ?>" style="width:90px;height:90px" src="<?= e(url_imagem($p['image'])) ?>" alt=""></div>
  <div class="col-12"><button class="btn btn-success">Salvar</button> <a class="btn btn-outline-secondary" href="<?= e(url('admin/produtos.php')) ?>">Cancelar</a></div>
</form>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
