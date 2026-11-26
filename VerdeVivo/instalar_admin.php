<?php
/**
 * Diagnóstico e reparo da conta administrativa.
 * - Mostra se a conta existe, se é admin e se a senha confere (password_verify).
 * - O botão cria/repara a conta usando password_hash() do próprio PHP.
 * Só funciona em localhost. Depois de usar, APAGUE este arquivo.
 */
require_once __DIR__ . '/includes/auth.php';

const ADMIN_NOME = 'Fernando';
const ADMIN_EMAIL = 'admin@gmail';
const ADMIN_SENHA_INICIAL = 'admin123';

if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Disponível apenas em localhost.');
}

function buscar_conta_admin(PDO $pdo): ?array
{
    $st = $pdo->prepare('SELECT id, name, email, password, role FROM users WHERE email = ?');
    $st->execute([ADMIN_EMAIL]);
    return $st->fetch() ?: null;
}

$pdo = db();
$resultado = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pdo->beginTransaction();
    try {
        $hash = password_hash(ADMIN_SENHA_INICIAL, PASSWORD_DEFAULT);
        $conta = buscar_conta_admin($pdo);
        if ($conta) {                                   // já existe: não duplica, só repara
            $pdo->prepare("UPDATE users SET name = ?, password = ?, role = 'admin' WHERE id = ?")
                ->execute([ADMIN_NOME, $hash, $conta['id']]);
            $resultado = 'Conta existente reparada (senha redefinida e perfil de administrador garantido).';
        } else {
            $antiga = $pdo->prepare('SELECT id FROM users WHERE email IN (?, ?) ORDER BY email = ? DESC LIMIT 1');
            $antiga->execute(['fernando.sortes@outlook.com', 'fernando@gmail', 'fernando.sortes@outlook.com']);   // contas das versões anteriores
            $id = $antiga->fetchColumn();
            if ($id) {                                  // migra a conta antiga em vez de duplicar
                $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ?, role = 'admin' WHERE id = ?")
                    ->execute([ADMIN_NOME, ADMIN_EMAIL, $hash, $id]);
                $resultado = 'Conta anterior do Fernando migrada para o novo e-mail.';
            } else {
                $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')")
                    ->execute([ADMIN_NOME, ADMIN_EMAIL, $hash]);
                $resultado = 'Conta administrativa criada.';
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log($e->getMessage());
        $resultado = 'ERRO ao gravar: ' . $e->getMessage();
    }
}

$conta = buscar_conta_admin($pdo);
$existe = $conta !== null;
$ehAdmin = $existe && $conta['role'] === 'admin';
$senhaOk = $existe && password_verify(ADMIN_SENHA_INICIAL, $conta['password']);
$tipoHash = $existe ? (str_starts_with($conta['password'], '$2y$') ? 'bcrypt (password_hash)' : 'outro formato (será convertido no 1º login)') : '-';
$totalAdmins = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

$pageTitle = 'Diagnóstico do administrador';
include __DIR__ . '/includes/header.php';
$linhas = [
    ['Versão do PHP', PHP_VERSION, version_compare(PHP_VERSION, '8.0.0', '>=')],
    ['Conexão com o banco', 'OK', true],
    ['Conta ' . ADMIN_EMAIL . ' existe', $existe ? 'sim' : 'não', $existe],
    ['Perfil de administrador', $existe ? $conta['role'] : '-', $ehAdmin],
    ['Formato do hash', $tipoHash, $existe],
    ['password_verify("' . ADMIN_SENHA_INICIAL . '")', $senhaOk ? 'confere' : 'NÃO confere', $senhaOk],
];
?>
<h1 class="h4">Diagnóstico do administrador</h1>
<?php if ($resultado): ?><div class="alert alert-info"><?= e($resultado) ?></div><?php endif; ?>
<table class="table bg-white" style="max-width:640px"><tbody>
<?php foreach ($linhas as [$a, $b, $ok]): ?><tr><td><?= e($a) ?></td><td><?= e($b) ?></td><td><?= $ok ? '✅' : '❌' ?></td></tr><?php endforeach; ?>
</tbody></table>
<p class="small text-muted">Administradores cadastrados: <?= $totalAdmins ?>.</p>
<?php if ($existe && $ehAdmin && $senhaOk): ?>
  <div class="alert alert-success">Tudo certo. <a href="<?= e(url('login.php')) ?>">Ir para o login</a> e depois <b>apague o arquivo instalar_admin.php</b>.</div>
<?php else: ?>
  <form method="post"><?= csrf_field() ?><button class="btn btn-success">Criar / reparar conta do administrador</button></form>
  <p class="small text-muted mt-2">Redefine a senha para "<?= e(ADMIN_SENHA_INICIAL) ?>". Outros usuários, produtos e pedidos não são alterados.</p>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
