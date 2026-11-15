<?php
declare(strict_types=1);
require_once __DIR__ . '/config/store.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$customerId = (int)($_SESSION['store_customer_id'] ?? 0);
if ($customerId < 1) { header('Location: acesso-cliente.php'); exit; }
$db = store_db();
$q = $db->prepare('SELECT id, name, email FROM customers WHERE id = ? AND is_active = 1 LIMIT 1');
$q->execute([$customerId]); $customer = $q->fetch();
if (!$customer) { unset($_SESSION['store_customer_id']); header('Location: acesso-cliente.php'); exit; }

$sql = "SELECT o.id AS order_id, o.order_number, o.total, o.currency, o.paid_at, o.created_at AS order_date,
               oi.id AS order_item_id, oi.product_id, oi.product_name, oi.quantity,
               l.license_key, l.license_type, l.status AS license_status, l.expires_at AS license_expires,
               p.name AS current_product_name, p.file_path
        FROM orders o
        INNER JOIN order_items oi ON oi.order_id = o.id
        LEFT JOIN licenses l ON l.order_item_id = oi.id
        LEFT JOIN products p ON p.id = oi.product_id
        WHERE o.customer_id = ? AND o.payment_status = 'paid'
        ORDER BY o.paid_at DESC, o.id DESC, oi.id";
$q = $db->prepare($sql); $q->execute([$customerId]); $items = $q->fetchAll();
$downloads = [];

/*
 * Cria um novo token para a área do cliente sem sobrescrever tokens enviados
 * anteriormente por e-mail. Assim, reabrir a área não invalida o e-mail original.
 */
foreach ($items as $item) {
    $productId = (int)$item['product_id'];
    $orderId = (int)$item['order_id'];
    if (empty($item['file_path'])) continue;

    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $insert = $db->prepare(
        'INSERT INTO downloads
         (order_id, product_id, token_hash, expires_at, download_count, max_downloads, created_at)
         VALUES (?, ?, ?, NULL, 0, NULL, NOW())'
    );
    $insert->execute([$orderId, $productId, $hash]);
    $downloads[$orderId . ':' . $productId] = 'download.php?token=' . urlencode($token);
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Minha conta | Toplevel Store</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body{background:#f3f6f4;color:#1d2923}.top{background:#14231d;color:white}.wrap{max-width:1050px}.product{border:1px solid #e4ebe6;border-radius:14px}.key{font-family:ui-monospace,monospace;overflow-wrap:anywhere;background:#f3f6f4;padding:8px;border-radius:7px}
</style>
</head>
<body>
<header class="top py-4">
    <div class="container wrap d-flex justify-content-between align-items-center">
        <div><div class="fw-bold fs-4">Toplevel Store</div><div class="text-white-50 small">Área do cliente</div></div>
        <a class="btn btn-outline-light btn-sm" href="sair-cliente.php">Sair</a>
    </div>
</header>
<main class="container wrap py-5">
    <h1 class="h3">Olá, <?=store_h($customer['name'])?>!</h1>
    <p class="text-secondary">Aqui estão suas compras, licenças e instaladores.</p>
    <div class="mb-4 small text-secondary"><i class="bi bi-envelope me-1"></i><?=store_h($customer['email'])?></div>

    <?php if (!$items): ?>
        <div class="alert alert-info">Ainda não encontramos compras com pagamento confirmado nesta conta.</div>
    <?php else: ?>
        <div class="row g-3">
        <?php foreach ($items as $item): $key = (int)$item['order_id'] . ':' . (int)$item['product_id']; ?>
            <div class="col-12">
                <section class="product bg-white p-4">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div>
                            <h2 class="h5 mb-1"><?=store_h($item['product_name'])?></h2>
                            <div class="small text-secondary">Pedido <?=store_h($item['order_number'])?> · <?=date('d/m/Y', strtotime((string)($item['paid_at'] ?: $item['order_date'])))?></div>
                        </div>
                        <span class="badge text-bg-success align-self-start">Pagamento aprovado</span>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-7">
                            <?php if ($item['license_key']): ?>
                                <div class="small text-secondary mb-1">Sua licença</div>
                                <div class="key"><?=store_h($item['license_key'])?></div>
                                <div class="small text-secondary mt-2">
                                    Tipo: <?=store_h($item['license_type'] ?: 'perpétua')?> ·
                                    Status: <?=store_h($item['license_status'] ?: 'ativa')?>
                                    <?php if ($item['license_expires']): ?>
                                        · Válida até <?=date('d/m/Y', strtotime($item['license_expires']))?>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="small text-secondary">Licença não localizada para este item.</div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-5 d-flex align-items-center justify-content-md-end">
                            <div>
                                <div class="fw-semibold mb-2">Instalador</div>
                                <?php if (isset($downloads[$key])): ?>
                                    <a class="btn btn-success" href="<?=store_h($downloads[$key])?>">
                                        <i class="bi bi-download me-2"></i>Baixar novamente
                                    </a>
                                <?php else: ?>
                                    <span class="text-secondary small">Instalador ainda não cadastrado.</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="mt-4"><a href="loja.php" class="btn btn-outline-success"><i class="bi bi-arrow-left me-1"></i>Voltar à loja</a></div>
</main>
</body>
</html>
