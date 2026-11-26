<?php
/**
 * REGRAS DE NEGÓCIO (pagamento simulado):
 *  - Ao finalizar a compra o pedido nasce como "confirmado" e o estoque é baixado na hora.
 *  - Status que contam como VENDA (faturamento): confirmado, em_preparacao, enviado, entregue.
 *  - "pendente" e "cancelado" NÃO contam. Cancelar devolve o estoque uma única vez (stock_deducted).
 */
const REVENUE_STATUSES = ['confirmado', 'em_preparacao', 'enviado', 'entregue'];
function revenue_in(): string { return "'" . implode("','", REVENUE_STATUSES) . "'"; }   // constantes fixas, sem entrada do usuário

/** Cria o pedido. Preços e total vêm SEMPRE do banco. $cart = [produto_id => quantidade]. */
function create_order(int $userId, array $cart, array $ship, string $payment): int
{
    if (!$cart) throw new RuntimeException('Carrinho vazio.');
    $pdo = db();
    $ids = array_map('intval', array_keys($cart));
    sort($ids);                                   // ordem fixa evita deadlock entre compras simultâneas
    $pdo->beginTransaction();
    try {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $st = $pdo->prepare("SELECT id, name, price, stock, active FROM products WHERE id IN ($ph) ORDER BY id FOR UPDATE");
        $st->execute($ids);
        $prods = [];
        foreach ($st->fetchAll() as $r) $prods[(int)$r['id']] = $r;

        $total = 0;
        $lines = [];
        foreach ($ids as $id) {
            $qty = (int)$cart[$id];
            $p = $prods[$id] ?? null;
            if (!$p || !$p['active']) throw new RuntimeException('Um produto do carrinho não está mais disponível.');
            if ($qty < 1) throw new RuntimeException('Quantidade inválida.');
            if ($qty > (int)$p['stock']) throw new RuntimeException('Estoque insuficiente para "' . $p['name'] . '" (disponível: ' . $p['stock'] . ').');
            $sub = round($qty * (float)$p['price'], 2);
            $total += $sub;
            $lines[] = [$id, $p['name'], $qty, $p['price'], $sub];
        }

        $pdo->prepare('INSERT INTO orders (user_id, total, payment_method, status, stock_deducted, ship_name, ship_zip, ship_address, ship_city) VALUES (?,?,?,?,1,?,?,?,?)')
            ->execute([$userId, round($total, 2), $payment, 'confirmado', $ship['name'], $ship['zip'], $ship['address'], $ship['city']]);
        $orderId = (int)$pdo->lastInsertId();

        $ins = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, subtotal) VALUES (?,?,?,?,?,?)');
        foreach ($lines as [$pid, $name, $qty, $price, $sub]) {
            $ins->execute([$orderId, $pid, $name, $qty, $price, $sub]);
            stock_apply($pdo, $pid, -$qty, 'saida', 'Venda - pedido #' . $orderId, $orderId, $userId);
        }
        $pdo->commit();
        return $orderId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();   // desfaz tudo: banco nunca fica pela metade
        throw $e;
    }
}

/** Cancela e devolve o estoque UMA vez. */
function cancel_order(int $orderId, int $adminId): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('SELECT id, status, stock_deducted FROM orders WHERE id = ? FOR UPDATE');
        $st->execute([$orderId]);
        $o = $st->fetch();
        if (!$o) throw new RuntimeException('Pedido não encontrado.');
        if ($o['status'] === 'cancelado') throw new RuntimeException('Pedido já está cancelado.');
        if ((int)$o['stock_deducted'] === 1) {
            $it = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?');
            $it->execute([$orderId]);
            foreach ($it->fetchAll() as $i) {
                stock_apply($pdo, (int)$i['product_id'], (int)$i['quantity'], 'devolucao', 'Cancelamento - pedido #' . $orderId, $orderId, $adminId);
            }
        }
        $pdo->prepare("UPDATE orders SET status = 'cancelado', stock_deducted = 0 WHERE id = ?")->execute([$orderId]);
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}

function update_order_status(int $orderId, string $status, int $adminId): void
{
    if (!isset(ORDER_STATUS[$status])) throw new RuntimeException('Status inválido.');
    if ($status === 'cancelado') { cancel_order($orderId, $adminId); return; }
    $st = db()->prepare('SELECT status FROM orders WHERE id = ?');
    $st->execute([$orderId]);
    $cur = $st->fetchColumn();
    if ($cur === false) throw new RuntimeException('Pedido não encontrado.');
    if ($cur === 'cancelado') throw new RuntimeException('Pedido cancelado não pode ser reaberto.');
    db()->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $orderId]);
}
