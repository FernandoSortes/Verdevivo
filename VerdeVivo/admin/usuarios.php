<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$pdo = db();
$r = revenue_in();
$id = (int)($_GET['id'] ?? 0);
$pageTitle = 'Clientes';
$adminActive = 'usuarios';
include __DIR__ . '/../includes/admin_header.php';
if ($id) {
    $u = $pdo->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = ?');
    $u->execute([$id]);
    $user = $u->fetch();
    $o = $pdo->prepare('SELECT id, total, status, created_at FROM orders WHERE user_id = ? ORDER BY id DESC');
    $o->execute([$id]);
    ?>
    <a href="usuarios.php" class="small">← Voltar</a>
    <?php if ($user): ?><h2 class="h6 mt-2"><?= e($user['name']) ?> · <?= e($user['email']) ?></h2>
    <table class="table table-sm bg-white"><thead><tr><th>Pedido</th><th>Data</th><th>Valor</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($o->fetchAll() as $x): ?><tr><td><a href="<?= e(url('admin/pedidos.php?id=' . (int)$x['id'])) ?>">#<?= (int)$x['id'] ?></a></td><td><?= e(date('d/m/Y H:i', strtotime($x['created_at']))) ?></td><td><?= money($x['total']) ?></td><td><?= e(ORDER_STATUS[$x['status']]) ?></td></tr><?php endforeach; ?></tbody></table>
    <?php else: ?><p>Usuário não encontrado.</p><?php endif;
} else {
    $rows = $pdo->query("SELECT u.id, u.name, u.email, u.role, u.created_at, COUNT(o.id) orders, COALESCE(SUM(CASE WHEN o.status IN ($r) THEN o.total END),0) spent FROM users u LEFT JOIN orders o ON o.user_id = u.id GROUP BY u.id ORDER BY u.id")->fetchAll();
    ?>
    <div class="table-responsive"><table class="table table-sm bg-white"><thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Pedidos</th><th>Total comprado</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $u): ?><tr><td><?= e($u['name']) ?></td><td><?= e($u['email']) ?></td><td><?= $u['role'] === 'admin' ? 'Admin' : 'Cliente' ?></td><td><?= (int)$u['orders'] ?></td><td><?= money($u['spent']) ?></td><td><a class="btn btn-sm btn-outline-success" href="?id=<?= (int)$u['id'] ?>">Histórico</a></td></tr><?php endforeach; ?></tbody></table></div>
<?php }
include __DIR__ . '/../includes/admin_footer.php'; ?>
