<?php /* Espera $adminActive e $pageTitle. Quem inclui já chamou require_admin(). */
$menu = ['dashboard' => 'Dashboard', 'produtos' => 'Produtos', 'estoque' => 'Estoque', 'pedidos' => 'Pedidos', 'categorias' => 'Categorias', 'usuarios' => 'Clientes']; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Admin') ?> · VerdeVivo Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="d-md-flex min-vh-100">
  <aside class="vv-side p-3">
    <a class="fw-bold fs-5 text-white text-decoration-none d-block mb-3" href="<?= e(url('index.php')) ?>">🌿 VerdeVivo</a>
    <nav class="nav flex-md-column flex-row flex-wrap gap-1">
      <?php foreach ($menu as $k => $label): ?>
        <a class="nav-link <?= ($adminActive ?? '') === $k ? 'active' : '' ?>" href="<?= e(url("admin/$k.php")) ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
      <a class="nav-link" target="_blank" href="<?= e(url('index.php')) ?>">Ver loja</a>
      <a class="nav-link" href="<?= e(url('logout.php')) ?>">Sair</a>
    </nav>
  </aside>
  <main class="flex-grow-1 p-3 p-md-4">
  <h1 class="h4 mb-3"><?= e($pageTitle ?? '') ?></h1>
  <?php foreach (flashes() as [$t, $m]): ?>
    <div class="alert alert-<?= e($t) ?> alert-dismissible fade show"><?= e($m) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endforeach; ?>
