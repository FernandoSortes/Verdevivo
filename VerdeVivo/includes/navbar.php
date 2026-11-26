<nav class="navbar navbar-expand-md navbar-dark vv-nav">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= e(url('index.php')) ?>">🌿 VerdeVivo</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nv"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nv">
      <ul class="navbar-nav ms-auto align-items-md-center gap-md-2">
        <li class="nav-item"><a class="nav-link" href="<?= e(url('produtos.php')) ?>">Catálogo</a></li>
        <?php if (is_admin()): ?><li class="nav-item"><a class="nav-link" href="<?= e(url('admin/dashboard.php')) ?>">Admin</a></li><?php endif; ?>
        <?php if (current_user()): ?>
          <li class="nav-item"><span class="nav-link">Olá, <?= e(explode(' ', current_user()['name'])[0]) ?></span></li>
          <li class="nav-item"><a class="nav-link" href="<?= e(url('logout.php')) ?>">Sair</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= e(url('login.php')) ?>">Entrar</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= e(url('cadastro.php')) ?>">Cadastrar</a></li>
        <?php endif; ?>
        <li class="nav-item"><a class="nav-link" href="<?= e(url('carrinho.php')) ?>">🛒 Carrinho <span class="badge bg-light text-success"><?= cart_count() ?></span></a></li>
      </ul>
    </div>
  </div>
</nav>
