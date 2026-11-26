<?php
/**
 * Altera o estoque e registra a movimentação.
 * DEVE ser chamada dentro de uma transação aberta pelo chamador.
 * $delta é com sinal (venda = negativo). Lança exceção se o estoque ficaria negativo.
 */
function stock_apply(PDO $pdo, int $productId, int $delta, string $type, string $reason, ?int $orderId = null, ?int $userId = null): void
{
    $st = $pdo->prepare('SELECT stock FROM products WHERE id = ? FOR UPDATE');   // trava a linha
    $st->execute([$productId]);
    $row = $st->fetch();
    if (!$row) throw new RuntimeException('Produto não encontrado.');
    $new = (int)$row['stock'] + $delta;
    if ($new < 0) throw new RuntimeException('Estoque insuficiente.');
    $pdo->prepare('UPDATE products SET stock = ? WHERE id = ?')->execute([$new, $productId]);
    $pdo->prepare('INSERT INTO stock_movements (product_id, order_id, user_id, type, quantity, reason) VALUES (?,?,?,?,?,?)')
        ->execute([$productId, $orderId, $userId, $type, $delta, $reason]);
}

/** Ajuste manual feito pelo administrador. $mode: 'entrada' (soma) ou 'definir' (novo total). */
function stock_manual(int $productId, int $qty, string $mode, string $reason, int $userId): void
{
    if ($qty < 0) throw new RuntimeException('Quantidade inválida.');
    $pdo = db();
    $pdo->beginTransaction();
    try {
        if ($mode === 'entrada') {
            if ($qty === 0) throw new RuntimeException('Informe uma quantidade maior que zero.');
            stock_apply($pdo, $productId, $qty, 'entrada', $reason ?: 'Entrada de estoque', null, $userId);
        } else {
            $cur = $pdo->prepare('SELECT stock FROM products WHERE id = ? FOR UPDATE');
            $cur->execute([$productId]);
            $row = $cur->fetch();
            if (!$row) throw new RuntimeException('Produto não encontrado.');
            $delta = $qty - (int)$row['stock'];
            if ($delta !== 0) stock_apply($pdo, $productId, $delta, 'ajuste', $reason ?: 'Ajuste manual', null, $userId);
        }
        $pdo->commit();
    } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
}
