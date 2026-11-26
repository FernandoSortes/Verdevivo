<?php
require_once __DIR__ . '/includes/auth.php';
if (current_user()) redirect('index.php');
$name = $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $pass = (string)($_POST['password'] ?? '');
    $err = [];
    if (mb_strlen($name) < 3) $err[] = 'Informe seu nome.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err[] = 'E-mail inválido.';
    if (strlen($pass) < 6) $err[] = 'A senha precisa de pelo menos 6 caracteres.';
    if (!$err) {
        $ex = db()->prepare('SELECT 1 FROM users WHERE email = ?');
        $ex->execute([$email]);
        if ($ex->fetch()) $err[] = 'Este e-mail já está cadastrado.';
    }
    if (!$err) {
        // role é sempre 'customer': ninguém vira admin pelo formulário
        db()->prepare("INSERT INTO users (name, email, password, role) VALUES (?,?,?, 'customer')")->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
        flash('success', 'Conta criada! Faça login.');
        redirect('login.php');
    }
    foreach ($err as $m) flash('danger', $m);
}
$pageTitle = 'Cadastro · VerdeVivo';
include __DIR__ . '/includes/header.php';
?>
<div class="mx-auto" style="max-width:420px"><h1 class="h4">Criar conta</h1>
<form method="post" class="card card-body vv-card"><?= csrf_field() ?>
  <label class="form-label">Nome</label><input name="name" class="form-control" value="<?= e($name) ?>" required>
  <label class="form-label mt-2">E-mail</label><input type="email" name="email" class="form-control" value="<?= e($email) ?>" required>
  <label class="form-label mt-2">Senha</label><input type="password" name="password" class="form-control" minlength="6" required>
  <button class="btn btn-success mt-3">Cadastrar</button></form></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
