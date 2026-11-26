<?php
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money($v): string { return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }
function redirect(string $path): void { header('Location: ' . url($path)); exit; }

function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void
{
    $t = $_POST['csrf'] ?? '';
    if (!is_string($t) || !hash_equals($_SESSION['csrf'] ?? '', $t)) {
        http_response_code(419);
        exit('Requisição inválida (token CSRF). Volte e tente novamente.');
    }
}

function current_user(): ?array { return $_SESSION['user'] ?? null; }
function is_admin(): bool { return (current_user()['role'] ?? '') === 'admin'; }
function require_login(): void
{
    if (!current_user()) { flash('warning', 'Faça login para continuar.'); redirect('login.php'); }
}
function require_admin(): void
{
    require_login();
    if (!is_admin()) { http_response_code(403); exit('Acesso negado: área restrita a administradores.'); }
}

function cart_count(): int { return array_sum($_SESSION['cart'] ?? []); }

const LOW_STOCK = 5;
function stock_status(int $n): array
{
    if ($n <= 0) return ['Esgotado', 'danger'];
    if ($n <= LOW_STOCK) return ['Estoque baixo', 'warning'];
    return ['Normal', 'success'];
}

const ORDER_STATUS = [
    'pendente' => 'Pendente', 'confirmado' => 'Confirmado', 'em_preparacao' => 'Em preparação',
    'enviado' => 'Enviado', 'entregue' => 'Entregue', 'cancelado' => 'Cancelado',
];
const PAYMENTS = ['pix' => 'PIX', 'cartao' => 'Cartão de crédito', 'boleto' => 'Boleto'];

function category_emoji(string $cat): string
{
    return ['Plantas' => '🌿', 'Vasos' => '🏺', 'Ferramentas' => '✂️', 'Insumos' => '🌱'][$cat] ?? '🪴';
}
function url_imagem(?string $arquivo): string
{
    // Usa a foto do produto; se não existir no disco, cai na imagem padrão.
    $arquivo = $arquivo ? basename($arquivo) : '';
    if ($arquivo !== '' && is_file(ROOT . '/assets/imagens/produtos/' . $arquivo)) {
        return url('assets/imagens/produtos/' . $arquivo);
    }
    return url('assets/imagens/padrao.svg');
}
function product_image(array $p, string $class = 'ph'): string
{
    return '<img src="' . e(url_imagem($p['image'] ?? null)) . '" alt="' . e($p['name']) . '" class="' . $class . '-img" loading="lazy">';
}
function page_param(): int { return max(1, (int)($_GET['page'] ?? 1)); }
function paginate(int $total, int $per, int $page, array $query = []): string
{
    $pages = (int)ceil($total / $per);
    if ($pages <= 1) return '';
    $h = '<nav><ul class="pagination pagination-sm">';
    for ($i = 1; $i <= $pages; $i++) {
        $q = http_build_query(array_merge($query, ['page' => $i]));
        $h .= '<li class="page-item' . ($i === $page ? ' active' : '') . '"><a class="page-link" href="?' . e($q) . '">' . $i . '</a></li>';
    }
    return $h . '</ul></nav>';
}
