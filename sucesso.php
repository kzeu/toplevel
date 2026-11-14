<?php
declare(strict_types=1);

require_once __DIR__ . '/config/store.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pageTitle = 'Resultado da compra';
$pageDescription = 'Acompanhe a confirmação do seu pagamento.';
$orderId = (int)($_SESSION['store_last_order_id'] ?? 0);
$order = null;
$items = [];
$downloadLinks = [];

if ($orderId > 0) {
    $db = store_db();
    $q = $db->prepare("SELECT id, order_number, customer_name, customer_email, total, status, payment_status, paid_at
                       FROM orders WHERE id = ? LIMIT 1");
    $q->execute([$orderId]);
    $order = $q->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($order) {
        $iq = $db->prepare("SELECT oi.id AS order_item_id, oi.product_id, oi.product_name, oi.quantity,
                                   p.file_path, l.license_key, l.license_type, l.expires_at
                            FROM order_items oi
                            INNER JOIN products p ON p.id = oi.product_id
                            LEFT JOIN licenses l ON l.order_item_id = oi.id
                            WHERE oi.order_id = ? ORDER BY oi.id");
        $iq->execute([$orderId]);
        $items = $iq->fetchAll(PDO::FETCH_ASSOC);

        // O link é criado somente após a confirmação do pagamento.
        // Cada visita à página gera um novo token, sem validade ou limite de downloads.
        if (($order['payment_status'] ?? '') === 'paid') {
            foreach ($items as $item) {
                $fileName = basename((string)($item['file_path'] ?? ''));
                $filePath = STORE_DOWNLOAD_DIR . DIRECTORY_SEPARATOR . $fileName;
                if ($fileName === '' || !is_file($filePath) || !is_readable($filePath)) {
                    $downloadLinks[(int)$item['order_item_id']] = null;
                    continue;
                }

                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $ins = $db->prepare("INSERT INTO downloads
                    (order_id, product_id, token_hash, expires_at, download_count, max_downloads, created_at)
                    VALUES (?, ?, ?, NULL, 0, NULL, NOW())");
                $ins->execute([$orderId, (int)$item['product_id'], $tokenHash]);
                $downloadLinks[(int)$item['order_item_id']] = 'download.php?token=' . rawurlencode($token);
            }
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container">
    <span class="eyebrow">Toplevel Store</span>
    <h1>Resultado da compra</h1>
    <p>Confira abaixo a situação atual do seu pedido.</p>
  </div>
</section>
<section class="section-pad">
  <div class="container" style="max-width:850px">
    <?php if (!$order): ?>
      <div class="contact-panel">
        <h2 class="h4">Não encontramos um pedido nesta sessão</h2>
        <p class="text-secondary">Se você concluiu uma compra em outro navegador ou dispositivo, utilize o mesmo navegador em que iniciou o pagamento ou entre em contato com nosso atendimento. Não informe dados do cartão.</p>
        <a href="loja.php" class="btn btn-dark">Voltar à loja</a>
      </div>
    <?php elseif (($order['payment_status'] ?? '') === 'paid'): ?>
      <div class="contact-panel">
        <div class="mb-3" style="font-size:2.5rem;color:#16834a">✓</div>
        <h2 class="h3">Pagamento aprovado!</h2>
        <p>Obrigado, <?= store_h((string)$order['customer_name']) ?>. Seu pagamento foi confirmado.</p>
        <p><strong>Pedido:</strong> <?= store_h((string)$order['order_number']) ?><br>
           <strong>Valor:</strong> R$ <?= number_format((float)$order['total'], 2, ',', '.') ?></p>
        <h3 class="h5 mt-4">Sua licença</h3>
        <?php foreach ($items as $item): ?>
          <div class="border rounded-3 p-3 mb-3">
            <strong><?= store_h((string)$item['product_name']) ?></strong>
            <div class="small text-secondary">Quantidade: <?= (int)$item['quantity'] ?></div>
            <?php if (!empty($item['license_key'])): ?>
              <div class="mt-2">Chave de licença:</div>
              <code style="font-size:1rem;overflow-wrap:anywhere"><?= store_h((string)$item['license_key']) ?></code>
              <div class="small text-secondary mt-1">Tipo: <?= store_h((string)($item['license_type'] ?: 'perpétua')) ?><?php if (!empty($item['expires_at'])): ?> · Validade: <?= store_h(date('d/m/Y', strtotime((string)$item['expires_at']))) ?><?php endif; ?></div>
            <?php else: ?>
              <div class="small text-secondary mt-2">A licença está sendo preparada. Atualize esta página em alguns instantes.</div>
            <?php endif; ?>
            <?php $downloadUrl = $downloadLinks[(int)$item['order_item_id']] ?? null; ?>
            <?php if ($downloadUrl): ?>
              <div class="mt-3"><a href="<?= store_h($downloadUrl) ?>" class="btn btn-success">Baixar instalador</a></div>
              <div class="small text-secondary mt-2">Download sem prazo de validade e sem limite de utilizações.</div>
            <?php else: ?>
              <div class="small text-secondary mt-2">O instalador ainda não está cadastrado ou disponível. Entre em contato com o atendimento.</div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
        <p class="small text-secondary">Guarde sua chave de licença e o e-mail de confirmação. Você poderá retornar a esta página para gerar um novo link de download.</p>
        <a href="loja.php" class="btn btn-dark">Voltar à loja</a>
      </div>
    <?php else: ?>
      <div class="contact-panel">
        <h2 class="h3">Estamos aguardando a confirmação</h2>
        <p>O pedido <strong><?= store_h((string)$order['order_number']) ?></strong> foi registrado, mas o banco ainda não recebeu a confirmação de pagamento do Mercado Pago.</p>
        <p class="text-secondary">Isso pode levar alguns instantes. Não faça outro pagamento. Atualize esta página mais tarde; quando o webhook confirmar a compra, sua licença aparecerá aqui.</p>
        <a href="sucesso.php" class="btn btn-dark">Atualizar situação</a>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
