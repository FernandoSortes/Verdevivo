<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $name = trim((string)($_POST['name'] ?? ''));
    $desc = trim((string)($_POST['description'] ?? ''));
    try {
        if (($_POST['action'] ?? '') === 'delete') {
            $n = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
            $n->execute([$id]);
            if ((int)$n->fetchColumn() > 0) throw new RuntimeException('Categoria em uso por produtos: não pode ser excluída.');
            $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            flash('success', 'Categoria excluída.');
        } else {
            if (mb_strlen($name) < 2 || mb_strlen($name) > 80) throw new RuntimeException('Nome da categoria inválido.');
            if ($id) $pdo->prepare('UPDATE categories SET name=?, description=? WHERE id=?')->execute([$name, $desc, $id]);
            else $pdo->prepare('INSERT INTO categories (name, description) VALUES (?,?)')->execute([$name, $desc]);
            flash('success', 'Categoria salva.');
        }
    } catch (PDOException $e) { flash('danger', $e->getCode() === '23000' ? 'Já existe uma categoria com esse nome.' : 'Erro ao salvar.'); }
    catch (RuntimeException $e) { flash('danger', $e->getMessage()); }
    redirect('admin/categorias.php');
}
$edit = null;
if (isset($_GET['edit'])) { $s = $pdo->prepare('SELECT * FROM categories WHERE id = ?'); $s->execute([(int)$_GET['edit']]); $edit = $s->fetch() ?: null; }
$rows = $pdo->query('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS qty FROM categories c ORDER BY c.name')->fetchAll();
$pageTitle = 'Categorias';
$adminActive = 'categorias';
include __DIR__ . '/../includes/admin_header.php';
?>
<form method="post" class="row g-2 mb-3" style="max-width:700px"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
  <div class="col-md-4"><input class="form-control" name="name" placeholder="Nome" value="<?= e($edit['name'] ?? '') ?>" required></div>
  <div class="col-md-5"><input class="form-control" name="description" placeholder="Descrição" value="<?= e($edit['description'] ?? '') ?>"></div>
  <div class="col-md-3"><button class="btn btn-success"><?= $edit ? 'Salvar' : 'Adicionar' ?></button><?php if ($edit): ?> <a href="categorias.php" class="btn btn-outline-secondary">Cancelar</a><?php endif; ?></div></form>
<table class="table table-sm bg-white"><thead><tr><th>Nome</th><th>Descrição</th><th>Produtos</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $c): ?><tr><td><?= e($c['name']) ?></td><td><?= e($c['description']) ?></td><td><?= (int)$c['qty'] ?></td><td class="text-nowrap">
  <a class="btn btn-sm btn-outline-success" href="?edit=<?= (int)$c['id'] ?>">Editar</a>
  <form method="post" class="d-inline" data-confirm="Excluir esta categoria?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-sm btn-outline-danger">Excluir</button></form></td></tr><?php endforeach; ?></tbody></table>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
