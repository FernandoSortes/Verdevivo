<?php
/** Todos os indicadores saem de consultas SQL. Nada fixo no código. */
function dashboard_stats(): array
{
    $pdo = db();
    $r = revenue_in();
    return [
        'products' => (int)$pdo->query('SELECT COUNT(*) FROM products WHERE active = 1')->fetchColumn(),
        'orders'   => (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
        'revenue'  => (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status IN ($r)")->fetchColumn(),
        'sold'     => (int)$pdo->query("SELECT COALESCE(SUM(oi.quantity),0) FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.status IN ($r)")->fetchColumn(),
        'low'      => (int)$pdo->query('SELECT COUNT(*) FROM products WHERE active = 1 AND stock BETWEEN 1 AND ' . LOW_STOCK)->fetchColumn(),
        'out'      => (int)$pdo->query('SELECT COUNT(*) FROM products WHERE active = 1 AND stock = 0')->fetchColumn(),
    ];
}
function recent_orders(int $limit = 8): array
{
    return db()->query('SELECT o.id, o.total, o.status, o.created_at, u.name customer FROM orders o JOIN users u ON u.id = o.user_id ORDER BY o.id DESC LIMIT ' . (int)$limit)->fetchAll();
}
function top_products(int $limit = 5): array
{
    $r = revenue_in();
    return db()->query("SELECT oi.product_name, SUM(oi.quantity) qty, SUM(oi.subtotal) amount FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.status IN ($r) GROUP BY oi.product_id, oi.product_name ORDER BY qty DESC LIMIT " . (int)$limit)->fetchAll();
}
function sales_by_day(int $days = 7): array
{
    $r = revenue_in();
    return db()->query("SELECT DATE(created_at) d, COUNT(*) n, SUM(total) amount FROM orders WHERE status IN ($r) AND created_at >= (CURDATE() - INTERVAL " . (int)$days . " DAY) GROUP BY DATE(created_at) ORDER BY d DESC")->fetchAll();
}
