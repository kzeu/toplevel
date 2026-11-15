<?php
require __DIR__ . '/_init.php';
store_admin_required();
$pdo = store_db();

$stats = $pdo->query("
    SELECT
      (SELECT COUNT(*) FROM products) AS products,
      (SELECT COUNT(*) FROM products WHERE is_published = 1) AS published,
      (SELECT COUNT(*) FROM orders) AS orders,
      (SELECT COUNT(*) FROM orders WHERE payment_status = 'paid') AS paid,
      (SELECT COALESCE(SUM(total), 0) FROM orders WHERE payment_status = 'paid') AS revenue
")->fetch();

$recent = $pdo->query("
    SELECT o.id, o.order_number, o.customer_name, o.customer_email,
           GROUP_CONCAT(oi.product_name ORDER BY oi.id SEPARATOR ', ') AS product_name,
           o.total AS amount, o.payment_status AS status, o.created_at
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    GROUP BY o.id, o.order_number, o.customer_name, o.customer_email,
             o.total, o.payment_status, o.created_at
    ORDER BY o.id DESC
    LIMIT 8
")->fetchAll();
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Painel | Toplevel</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark" style="background:#101820"><div class="container">
<a class="navbar-brand fw-bold" href="index.php">TOPLEVEL · LOJA</a>
<div class="d-flex gap-3"><a class="text-white" href="produtos.php">Produtos</a><a class="text-white" href="../loja.php" target="_blank" rel="noopener">Ver loja</a><a class="text-white" href="logout.php">Sair</a></div>
</div></nav>
<main class="container py-5">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div>
<span class="section-kicker">Painel administrativo</span>
<h1 class="h2">Olá, <?= store_h((string)($_SESSION['store_admin_name'] ?? '')) ?></h1>
<p class="text-secondary mb-0">Visão geral de todos os produtos da Toplevel.</p>
</div><a class="btn btn-dark" href="produto-form.php">+ Novo produto</a></div>
<div class="row g-3 mb-5">
<?php $cards = [
 ['Produtos', (string)$stats['products']],
 ['Publicados', (string)$stats['published']],
 ['Pedidos', (string)$stats['orders']],
 ['Pagos', (string)$stats['paid']],
 ['Receita recebida', 'R$ ' . number_format((float)$stats['revenue'], 2, ',', '.')]
]; foreach ($cards as $card): ?>
<div class="col-6 col-lg"><div class="contact-panel h-100"><div class="text-secondary small"><?= store_h($card[0]) ?></div><div class="fs-3 fw-bold"><?= store_h($card[1]) ?></div></div></div>
<?php endforeach; ?>
</div>
<div class="contact-panel"><h2 class="h5 mb-3">Pedidos recentes</h2><div class="table-responsive"><table class="table align-middle">
<thead><tr><th>Pedido</th><th>Cliente</th><th>Produto</th><th>Valor</th><th>Status</th><th>Data</th></tr></thead><tbody>
<?php foreach ($recent as $r): ?><tr>
<td><?= store_h((string)$r['order_number']) ?></td>
<td><strong><?= store_h((string)$r['customer_name']) ?></strong><br><small class="text-secondary"><?= store_h((string)$r['customer_email']) ?></small></td>
<td><?= store_h((string)($r['product_name'] ?? 'Produto não informado')) ?></td>
<td>R$ <?= number_format((float)$r['amount'], 2, ',', '.') ?></td>
<td><?php if ($r['status'] === 'paid'): ?><span class="badge bg-success">Pago</span><?php elseif ($r['status'] === 'pending'): ?><span class="badge bg-warning text-dark">Pendente</span><?php elseif ($r['status'] === 'cancelled'): ?><span class="badge bg-danger">Cancelado</span><?php else: ?><span class="badge bg-secondary"><?= store_h((string)$r['status']) ?></span><?php endif; ?></td>
<td><?= date('d/m/Y H:i', strtotime((string)$r['created_at'])) ?></td>
</tr><?php endforeach; ?>
<?php if (!$recent): ?><tr><td colspan="6" class="text-secondary">Ainda não há pedidos.</td></tr><?php endif; ?>
</tbody></table></div></div>
</main></body></html>
