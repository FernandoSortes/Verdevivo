<?php
require_once __DIR__ . '/includes/auth.php';
if (current_user()) redirect('index.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $st = db()->prepare('SELECT * FROM users WHERE email = ?');
    $st->execute([mb_strtolower(trim((string)($_POST['email'] ?? '')))]);   // e-mail sem espaços e em minúsculas
    $u = $st->fetch();
    if ($u && password_verify((string)($_POST['password'] ?? ''), $u['password'])) {
        if (password_needs_rehash($u['password'], PASSWORD_DEFAULT)) {   // converte hashes antigos para password_hash()
            db()->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash((string)$_POST['password'], PASSWORD_DEFAULT), $u['id']]);
        }
        session_regenerate_id(true);               // evita fixação de sessão
        $_SESSION['user'] = ['id' => (int)$u['id'], 'name' => $u['name'], 'role' => $u['role']];
        redirect($u['role'] === 'admin' ? 'admin/dashboard.php' : 'index.php');
    }
    flash('danger', 'E-mail ou senha incorretos.');
}
$pageTitle = 'Entrar · VerdeVivo';
include __DIR__ . '/includes/header.php';
?>
<div class="mx-auto" style="max-width:420px"><h1 class="h4">Entrar</h1>
<form method="post" class="card card-body vv-card"><?= csrf_field() ?>
  <label class="form-label">E-mail ou login</label><input type="text" name="email" class="form-control" autocomplete="username" required>
  <label class="form-label mt-2">Senha</label><input type="password" name="password" class="form-control" required>
  <button class="btn btn-success mt-3">Entrar</button>
  <a href="<?= e(url('cadastro.php')) ?>" class="small mt-2">Criar conta</a></form></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
