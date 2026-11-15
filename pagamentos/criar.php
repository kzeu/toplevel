<?php
/*
|--------------------------------------------------------------------------
| TOPLEVELSISTEMAS - CRIAÇÃO DE PEDIDO NO MERCADO PAGO
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config/store.php';
require_once dirname(__DIR__) . '/config/mercadopago.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$orderId = null;
$paymentId = null;
$db = null;

try {

    // 1. Validar método de acesso
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Método não permitido.');
    }

    // 2. Validar proteção CSRF
    store_check_csrf();

    // 3. Validar configuração do Mercado Pago
    if (
        !defined('MP_ACCESS_TOKEN') ||
        MP_ACCESS_TOKEN === '' ||
        MP_ACCESS_TOKEN === 'COLE_SEU_ACCESS_TOKEN_DE_TESTE_AQUI'
    ) {
        throw new RuntimeException(
            'Access Token do Mercado Pago não configurado.'
        );
    }

    if (!extension_loaded('curl')) {
        throw new RuntimeException('A extensão CURL do PHP não está habilitada.');
    }

    // 4. Receber os dados do comprador
    $slug = trim((string)($_POST['slug'] ?? ''));
    $name = trim((string)($_POST['customer_name'] ?? ''));
    $email = strtolower(trim((string)($_POST['customer_email'] ?? '')));
    $emailConfirm = strtolower(trim((string)($_POST['customer_email_confirm'] ?? '')));
    $phone = trim((string)($_POST['customer_phone'] ?? ''));

    if ($slug === '' || $name === '' || $email === '' || $emailConfirm === '') {
        throw new RuntimeException('Preencha o nome e os dois campos de e-mail.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Informe um e-mail válido.');
    }

    if (!hash_equals($email, $emailConfirm)) {
        throw new RuntimeException('Os e-mails informados não são iguais. Confira os dois campos.');
    }

    if (mb_strlen($name) > 150 || mb_strlen($email) > 190 || mb_strlen($phone) > 30) {
        throw new RuntimeException('Os dados informados são muito extensos.');
    }

    // WhatsApp é opcional; quando informado, aceita somente números e o sinal de +.
    if ($phone !== '' && !preg_match('/^[0-9+() .-]{8,30}$/', $phone)) {
        throw new RuntimeException('Confira o número de WhatsApp informado.');
    }

    // 5. Conectar ao banco
    $db = store_db();

    // 6. Buscar produto publicado
    $stmt = $db->prepare(
        'SELECT id, name, slug, price
         FROM products
         WHERE slug = ?
           AND is_published = 1
         LIMIT 1'
    );

    $stmt->execute([$slug]);
    $product = $stmt->fetch();

    if (!$product) {
        throw new RuntimeException('Produto não encontrado ou indisponível.');
    }

    $price = number_format((float)$product['price'], 2, '.', '');

    if ((float)$price <= 0) {
        throw new RuntimeException('Preço do produto inválido.');
    }

    // 7. Criar pedido no banco
    $db->beginTransaction();

    // Procurar cliente pelo e-mail
    $stmt = $db->prepare(
        'SELECT id
         FROM customers
         WHERE email = ?
         LIMIT 1'
    );

    $stmt->execute([$email]);
    $customer = $stmt->fetch();

    if ($customer) {

        $customerId = (int)$customer['id'];

        $stmt = $db->prepare(
            'UPDATE customers
             SET name = ?, phone = ?, updated_at = NOW()
             WHERE id = ?'
        );

        $stmt->execute([$name, $phone !== '' ? $phone : null, $customerId]);

    } else {

        $stmt = $db->prepare(
            'INSERT INTO customers
             (name, email, phone, is_active, created_at, updated_at)
             VALUES (?, ?, ?, 1, NOW(), NOW())'
        );

        $stmt->execute([$name, $email, $phone !== '' ? $phone : null]);

        $customerId = (int)$db->lastInsertId();
    }

    // Número único do pedido
    $orderNumber = 'TOP-' .
        date('Ymd') . '-' .
        strtoupper(bin2hex(random_bytes(4)));

    // Inserir pedido
    $stmt = $db->prepare(
        "INSERT INTO orders
        (
            order_number,
            customer_id,
            subtotal,
            discount,
            total,
            currency,
            status,
            payment_status,
            customer_name,
            customer_email,
            created_at,
            updated_at
        )
        VALUES (?, ?, ?, 0, ?, 'BRL', 'pending', 'pending', ?, ?, NOW(), NOW())"
    );

    $stmt->execute([
        $orderNumber,
        $customerId,
        $price,
        $price,
        $name,
        $email
    ]);

    $orderId = (int)$db->lastInsertId();

    // Inserir item do pedido
    $stmt = $db->prepare(
        'INSERT INTO order_items
        (
            order_id,
            product_id,
            product_name,
            quantity,
            unit_price,
            total_price,
            created_at
        )
        VALUES (?, ?, ?, 1, ?, ?, NOW())'
    );

    $stmt->execute([
        $orderId,
        (int)$product['id'],
        (string)$product['name'],
        $price,
        $price
    ]);

    // Criar registro inicial do pagamento
    $stmt = $db->prepare(
        "INSERT INTO payments
        (
            order_id,
            gateway,
            gateway_payment_id,
            gateway_reference,
            payment_method,
            amount,
            status,
            created_at,
            updated_at
        )
        VALUES (?, 'mercadopago', NULL, ?, NULL, ?, 'pending', NOW(), NOW())"
    );

    $stmt->execute([
        $orderId,
        $orderNumber,
        $price
    ]);

    $paymentId = (int)$db->lastInsertId();

    // Confirmar gravação local
    $db->commit();

    // 8. Preparar requisição para o Mercado Pago
    $payload = [
        'type' => 'online',

        'processing_mode' => 'manual',

        'capture_mode' => 'automatic_async',

        'total_amount' => $price,

        'external_reference' => (string)$orderId,

        'description' => 'Compra ' .
            (string)$product['name'] .
            ' - ' . $orderNumber,

        'payer' => [
            'email' => $email,
            'first_name' => $name
        ],

        'items' => [
            [
                'title' => (string)$product['name'],
                'quantity' => 1,
                'unit_price' => $price
            ]
        ],

        // Retorno automático do Checkout Pro (Orders API).
        // A confirmação real continua sendo responsabilidade do webhook.
        'config' => [
            'online' => [
                'success_url' => STORE_BASE_URL . '/sucesso.php',
                'failure_url' => STORE_BASE_URL . '/sucesso.php?resultado=falha',
                'pending_url' => STORE_BASE_URL . '/sucesso.php?resultado=pendente',
                'auto_return' => 'all'
            ]
        ]
    ];

    $jsonPayload = json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    if ($jsonPayload === false) {
        throw new RuntimeException('Não foi possível montar os dados do pagamento.');
    }

    // 9. Criar chave de idempotência
    $idempotencyKey = bin2hex(random_bytes(16));

    // 10. Enviar requisição ao Mercado Pago
    $ch = curl_init('https://api.mercadopago.com/v1/orders');

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $jsonPayload,

        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . MP_ACCESS_TOKEN,
            'Content-Type: application/json',
            'Accept: application/json',
            'X-Idempotency-Key: ' . $idempotencyKey
        ],

        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 40
    ]);

    $response = curl_exec($ch);

    $httpCode = (int)curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

    $curlError = curl_error($ch);

    curl_close($ch);

    if ($response === false || $curlError !== '') {
        throw new RuntimeException(
            'Erro de comunicação com o Mercado Pago: ' . $curlError
        );
    }

    // 11. Interpretar resposta
    $mpData = json_decode($response, true);

    if (!is_array($mpData)) {
        throw new RuntimeException(
            'Resposta inválida recebida do Mercado Pago.'
        );
    }

    if ($httpCode < 200 || $httpCode >= 300) {

        $apiMessage = $mpData['message']
            ?? $mpData['error']
            ?? 'Erro não especificado';

        throw new RuntimeException(
            'Mercado Pago retornou HTTP ' .
            $httpCode . ': ' . (string)$apiMessage .
            ' | Resposta: ' . substr($response, 0, 1500)
        );
    }

    // 12. Recuperar dados do checkout
    $mpOrderId = (string)($mpData['id'] ?? '');

    $checkoutUrl = (string)($mpData['checkout_url'] ?? '');

    if ($mpOrderId === '' || $checkoutUrl === '') {
        throw new RuntimeException(
            'O Mercado Pago não retornou o ID ou a URL do checkout.'
        );
    }

    // Aceitar somente URL HTTPS de checkout do Mercado Pago
    $urlParts = parse_url($checkoutUrl);

    $checkoutHost = strtolower((string)($urlParts['host'] ?? ''));

    $validHost = (
        $checkoutHost === 'mercadopago.com.br' ||
        str_ends_with($checkoutHost, '.mercadopago.com.br') ||
        $checkoutHost === 'mercadopago.com' ||
        str_ends_with($checkoutHost, '.mercadopago.com')
    );

    if (
        ($urlParts['scheme'] ?? '') !== 'https' ||
        !$validHost
    ) {
        throw new RuntimeException(
            'O Mercado Pago retornou uma URL de checkout inválida.'
        );
    }

    // 13. Atualizar pagamento local
    $stmt = $db->prepare(
        'UPDATE payments
         SET
            gateway_payment_id = ?,
            checkout_url = ?,
            raw_response = ?,
            updated_at = NOW()
         WHERE id = ?'
    );

    $stmt->execute([
        $mpOrderId,
        $checkoutUrl,
        $response,
        $paymentId
    ]);

    // Guarda o pedido na sessão atual para reconhecer o retorno do checkout.
    // A sessão não é prova de pagamento: sucesso.php sempre consulta o banco.
    $_SESSION['store_last_order_id'] = $orderId;

    // 14. Redirecionar comprador
    header('Location: ' . $checkoutUrl, true, 303);
    exit;

} catch (Throwable $e) {

    // Desfazer somente uma transação que ainda esteja aberta.
    if ($db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }

    // Registrar o erro completo no log do PHP.
    error_log('[Toplevel Mercado Pago] ' . $e->getMessage());

    // Se já existe pagamento local, não marcar como pago.
    // Mantemos pending quando o resultado remoto pode ser incerto.
    // Se a API retornou rejeição HTTP, a mensagem será exibida no ambiente local.

    http_response_code(500);

    echo '<!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Erro ao iniciar pagamento</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f5f7f5; padding: 40px 15px; }
            .box { background: #fff; max-width: 760px; margin: 30px auto; padding: 28px; border-radius: 14px; box-shadow: 0 5px 25px rgba(0,0,0,.07); }
            h2 { color: #a12622; }
            pre { white-space: pre-wrap; overflow-wrap: anywhere; background: #f7f7f7; padding: 15px; border-radius: 8px; color: #8b1e1e; }
            a { display: inline-block; margin-top: 15px; padding: 12px 22px; background: #198754; color: #fff; text-decoration: none; border-radius: 8px; }
        </style>
    </head>
    <body>
        <div class="box">
            <h2>Não foi possível iniciar o pagamento</h2>
            <p>Ocorreu um erro ao preparar sua compra. Como estamos em ambiente local de desenvolvimento, mostramos o detalhe abaixo para diagnóstico:</p>
            <pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>
            <a href="../loja.php">Voltar à loja</a>
        </div>
    </body>
    </html>';
    exit;
}