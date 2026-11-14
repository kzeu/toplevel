<?php

declare(strict_types=1);

/**
 * Webhook Mercado Pago - Orders API
 *
 * Requer MP_ACCESS_TOKEN e MP_WEBHOOK_SECRET em config/mercadopago.php.
 * Endpoint local: /toplevel/pagamentos/webhook.php
 */

require_once dirname(__DIR__) . '/config/store.php';
require_once dirname(__DIR__) . '/config/mercadopago.php';

function mpwh_reply(int $status, array $data): never
{
    if ($status >= 400) {
        error_log(
            '[Toplevel MP Webhook] RESPOSTA HTTP ' .
            $status . ': ' .
            json_encode($data, JSON_UNESCAPED_UNICODE)
        );
    }

    http_response_code($status);

    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}


function mpwh_log(string $message): void
{
    error_log('[Toplevel MP Webhook] ' . $message);
}


function mpwh_header(string $name): string
{
    return trim(
        (string)($_SERVER[
            'HTTP_' . strtoupper(str_replace('-', '_', $name))
        ] ?? '')
    );
}


/**
 * Valida a assinatura enviada pelo Mercado Pago.
 */
function mpwh_valid_signature(
    string $secret,
    string $dataId,
    string $requestId,
    string $header
): bool {

    if (
        $secret === '' ||
        $dataId === '' ||
        $requestId === '' ||
        $header === ''
    ) {
        return false;
    }

    $parts = [];

    foreach (explode(',', $header) as $part) {
        $pair = explode('=', trim($part), 2);

        if (count($pair) === 2) {
            $parts[trim($pair[0])] = trim($pair[1]);
        }
    }

    $ts = $parts['ts'] ?? '';
    $v1 = strtolower($parts['v1'] ?? '');

    if (
        $ts === '' ||
        !preg_match('/^[a-f0-9]{64}$/', $v1)
    ) {
        return false;
    }

   // O Mercado Pago exige ID alfanumérico em minúsculas
// para validar a assinatura do webhook.
$dataIdSignature = strtolower($dataId);

    $manifest = 'id:' . $dataIdSignature .
        ';request-id:' . $requestId .
        ';ts:' . $ts . ';';

    $calculatedSignature = hash_hmac(
        'sha256',
        $manifest,
        $secret
    );

    return hash_equals($calculatedSignature, $v1);
}


/**
 * Consulta os dados reais da ordem no Mercado Pago.
 */
function mpwh_get_order(string $orderId): array
{
    if (
        !defined('MP_ACCESS_TOKEN') ||
        trim((string)MP_ACCESS_TOKEN) === ''
    ) {
        throw new RuntimeException(
            'MP_ACCESS_TOKEN não configurado.'
        );
    }

    $base = defined('MP_API_URL')
        ? rtrim((string)MP_API_URL, '/')
        : 'https://api.mercadopago.com';

    $ch = curl_init(
        $base . '/v1/orders/' . rawurlencode($orderId)
    );

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . MP_ACCESS_TOKEN,
            'Accept: application/json'
        ],
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 15,
    ]);

    $response = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    if ($response === false || $error !== '') {
        throw new RuntimeException(
            'Falha na consulta MP: ' . $error
        );
    }

    $data = json_decode($response, true);

    if (!is_array($data)) {
        throw new RuntimeException(
            'Resposta inválida da API Mercado Pago.'
        );
    }

    if ($http < 200 || $http >= 300) {
        throw new RuntimeException(
            'API Mercado Pago HTTP ' . $http . ': ' .
            substr((string)($data['message'] ?? $response), 0, 400)
        );
    }

    return $data;
}


/**
 * Gera uma chave de licença.
 */
function mpwh_new_license_key(): string
{
    $v = strtoupper(bin2hex(random_bytes(12)));

    return 'TOP-' .
        substr($v, 0, 6) . '-' .
        substr($v, 6, 6) . '-' .
        substr($v, 12, 6) . '-' .
        substr($v, 18, 6);
}


/**
 * Envia o e-mail de confirmação de um pedido pago.
 * A tabela email_outbox controla tentativas e impede duplicidade.
 * Falhas no SMTP não desfazem o pagamento nem a geração da licença.
 */
function mpwh_send_order_confirmation(PDO $db, int $orderId): bool
{
    try {
        $claim = $db->prepare("UPDATE email_outbox
            SET status = 'sending', attempts = attempts + 1
            WHERE order_id = ?
              AND notification_type = 'payment_approved'
              AND status = 'pending'");
        $claim->execute([$orderId]);

        if ($claim->rowCount() !== 1) {
            return false;
        }

        $q = $db->prepare("SELECT
                o.id, o.order_number, o.customer_name, o.customer_email,
                o.total, o.paid_at, c.email AS account_email
            FROM orders o
            INNER JOIN customers c ON c.id = o.customer_id
            WHERE o.id = ? AND o.payment_status = 'paid'
            LIMIT 1");
        $q->execute([$orderId]);
        $order = $q->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            throw new RuntimeException('Pedido pago não encontrado para envio de e-mail.');
        }

        $itemsQ = $db->prepare("SELECT
                oi.product_name, oi.quantity,
                l.license_key, l.license_type, l.expires_at
            FROM order_items oi
            LEFT JOIN licenses l ON l.order_item_id = oi.id
            WHERE oi.order_id = ?
            ORDER BY oi.id");
        $itemsQ->execute([$orderId]);
        $items = $itemsQ->fetchAll(PDO::FETCH_ASSOC);

        $autoload = dirname(__DIR__) . '/vendor/autoload.php';
        $configPath = dirname(__DIR__) . '/config/email.php';
        if (!is_file($autoload) || !is_file($configPath)) {
            throw new RuntimeException('PHPMailer ou config/email.php não encontrado.');
        }

        require_once $autoload;
        $mailConfig = require $configPath;
        if (!is_array($mailConfig) || empty($mailConfig['host']) ||
            empty($mailConfig['username']) || empty($mailConfig['password'])) {
            throw new RuntimeException('Configuração SMTP incompleta.');
        }

        $h = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $rows = '';
        foreach ($items as $item) {
            $rows .= '<tr><td style="padding:12px;border-bottom:1px solid #e5e7eb">' . $h($item['product_name']) .
                ' × ' . (int)$item['quantity'] . '</td><td style="padding:12px;border-bottom:1px solid #e5e7eb">';
            if (!empty($item['license_key'])) {
                $rows .= '<strong style="font-family:monospace">' . $h($item['license_key']) . '</strong>';
                $rows .= '<br><small>Licença: ' . $h($item['license_type'] ?: 'perpétua') . '</small>';
                if (!empty($item['expires_at'])) {
                    $rows .= '<br><small>Válida até: ' . $h(date('d/m/Y H:i', strtotime($item['expires_at']))) . '</small>';
                }
            } else {
                $rows .= 'Licença em processamento';
            }
            $rows .= '</td></tr>';
        }

        $recipient = trim((string)$order['customer_email']);
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('E-mail do comprador inválido no pedido.');
        }

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = (string)$mailConfig['host'];
        $mail->SMTPAuth = true;
        $mail->Username = (string)$mailConfig['username'];
        $mail->Password = (string)$mailConfig['password'];
        $mail->Port = (int)($mailConfig['port'] ?? 587);
        $encryption = strtolower((string)($mailConfig['encryption'] ?? 'tls'));
        $mail->SMTPSecure = $encryption === 'ssl'
            ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom((string)$mailConfig['from_email'], (string)$mailConfig['from_name']);
        $mail->addAddress($recipient, (string)$order['customer_name']);
        $mail->isHTML(true);
        $mail->Subject = 'Pagamento aprovado - Pedido ' . (string)$order['order_number'];
        $mail->Body = '<div style="font-family:Arial,sans-serif;max-width:650px;margin:auto;color:#1f2937">' .
            '<h2 style="color:#16834a">Pagamento aprovado!</h2>' .
            '<p>Olá, ' . $h($order['customer_name']) . '.</p>' .
            '<p>Recebemos seu pagamento. Confira os dados da sua compra:</p>' .
            '<p><strong>Pedido:</strong> ' . $h($order['order_number']) . '<br>' .
            '<strong>Valor:</strong> R$ ' . number_format((float)$order['total'], 2, ',', '.') . '<br>' .
            '<strong>Data:</strong> ' . $h($order['paid_at'] ? date('d/m/Y H:i', strtotime($order['paid_at'])) : date('d/m/Y H:i')) . '</p>' .
            '<table style="width:100%;border-collapse:collapse"><thead><tr><th align="left" style="padding:12px">Produto</th><th align="left" style="padding:12px">Licença</th></tr></thead><tbody>' . $rows . '</tbody></table>' .
            '<p style="margin-top:22px">Guarde este e-mail: ele contém os dados da sua licença.</p>' .
            '<p>O instalador ainda não está disponível para download. Assim que estiver liberado, você poderá acessá-lo pela loja.</p>' .
            '<p>Obrigado por comprar na <strong>ToplevelSistemas</strong>!</p></div>';
        $mail->AltBody = 'Pagamento aprovado. Pedido ' . $order['order_number'] . '. Valor: R$ ' .
            number_format((float)$order['total'], 2, ',', '.') . '. Consulte a licença neste e-mail em formato HTML.';
        $mail->send();

        $done = $db->prepare("UPDATE email_outbox SET status = 'sent', sent_at = NOW(), last_error = NULL
            WHERE order_id = ? AND notification_type = 'payment_approved'");
        $done->execute([$orderId]);
        mpwh_log('E-mail de confirmação enviado para pedido ' . $orderId . '.');
        return true;

    } catch (Throwable $e) {
        try {
            $fail = $db->prepare("UPDATE email_outbox SET status = 'pending', last_error = ?
                WHERE order_id = ? AND notification_type = 'payment_approved'");
            $fail->execute([substr($e->getMessage(), 0, 1000), $orderId]);
        } catch (Throwable $ignored) {
            // Preserva o erro original no log.
        }
        mpwh_log('Falha ao enviar confirmação do pedido ' . $orderId . ': ' . $e->getMessage());
        return false;
    }
}


/* =========================================================
   VALIDAÇÕES INICIAIS
========================================================= */

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');

    mpwh_reply(405, [
        'ok' => false,
        'message' => 'Método não permitido.'
    ]);
}

if (!extension_loaded('curl')) {
    mpwh_reply(500, [
        'ok' => false,
        'message' => 'cURL indisponível.'
    ]);
}

if (
    !defined('MP_WEBHOOK_SECRET') ||
    trim((string)MP_WEBHOOK_SECRET) === ''
) {
    mpwh_log('MP_WEBHOOK_SECRET não configurado.');

    mpwh_reply(500, [
        'ok' => false,
        'message' => 'Webhook não configurado.'
    ]);
}


/* =========================================================
   RECEBE A NOTIFICAÇÃO
========================================================= */

$raw = file_get_contents('php://input') ?: '';

$notice = json_decode($raw, true);



if (!is_array($notice)) {
    mpwh_log('ERRO 400: JSON inválido. Corpo recebido: ' . substr($raw, 0, 500));
    mpwh_reply(400, ['ok' => false, 'message' => 'JSON inválido.']);
}


$dataId = trim(
    (string)($_GET['data_id'] ?? $_GET['data.id'] ?? '')
);

$bodyDataId = trim(
    (string)($notice['data']['id'] ?? '')
);

if ($dataId === '') {
    $dataId = $bodyDataId;
}

if ($dataId === '' || ($bodyDataId !== '' && !hash_equals($dataId, $bodyDataId))) {
    mpwh_log('ERRO 400: ID da ordem inválido. GET=' . $dataId . ' BODY=' . $bodyDataId);
    mpwh_reply(400, ['ok' => false, 'message' => 'ID da ordem inválido.']);
}


/* =========================================================
   VALIDA ASSINATURA
========================================================= */

if (
    !mpwh_valid_signature(
        (string)MP_WEBHOOK_SECRET,
        $dataId,
        mpwh_header('X-Request-Id'),
        mpwh_header('X-Signature')
    )
) {
    mpwh_log(
        'Assinatura inválida para data.id=' . $dataId
    );

    mpwh_reply(401, [
        'ok' => false,
        'message' => 'Assinatura inválida.'
    ]);
}


/* =========================================================
   IDENTIFICA EVENTO
========================================================= */
$eventType = trim(
    (string)($notice['action'] ?? $notice['type'] ?? 'order.notification')
);

$topic = strtolower(
    trim((string)($notice['type'] ?? ''))
);

// O Mercado Pago pode enviar notificações sem ID na raiz.
// Nesse caso, criamos uma chave determinística para deduplicação.
$eventId = trim((string)($notice['id'] ?? ''));

if ($eventId === '') {
    $eventId = 'generated_' . hash(
        'sha256',
        'mercadopago|' . $topic . '|' . $eventType . '|' . $dataId
    );
}


/* =========================================================
   PROCESSAMENTO DA ORDEM
========================================================= */

try {

    $db = store_db();

    // Consulta a ordem diretamente na API.
    // O corpo do webhook sozinho não autoriza pagamento.
    $mp = mpwh_get_order($dataId);

    $externalRef = trim(
        (string)($mp['external_reference'] ?? '')
    );

    if (
        $externalRef === '' ||
        !ctype_digit($externalRef)
    ) {
        throw new RuntimeException(
            'external_reference inválida.'
        );
    }


    /* =====================================================
       DADOS DO MERCADO PAGO
    ===================================================== */

    $mpStatus = strtolower(
        (string)($mp['status'] ?? '')
    );

    $mpDetail = strtolower(
        (string)($mp['status_detail'] ?? '')
    );

    $currency = strtoupper(
        (string)($mp['currency_id'] ?? $mp['currency'] ?? '')
    );

    $total = (float)($mp['total_amount'] ?? 0);

    $paid = (float)($mp['total_paid_amount'] ?? 0);


    /* =====================================================
       VERIFICA TRANSAÇÃO APROVADA
    ===================================================== */

    $approvedTransaction = null;

    foreach (($mp['transactions']['payments'] ?? []) as $transaction) {

        if (
            is_array($transaction) &&
            strtolower((string)($transaction['status'] ?? '')) === 'processed' &&
            strtolower((string)($transaction['status_detail'] ?? '')) === 'accredited'
        ) {
            $approvedTransaction = $transaction;
            break;
        }
    }


    $approved =
        $mpStatus === 'processed' &&
        $mpDetail === 'accredited' &&
        $currency === 'BRL' &&
        $paid > 0 &&
        $approvedTransaction !== null;


    /* =====================================================
       TRANSAÇÃO NO BANCO
    ===================================================== */

    $db->beginTransaction();


    // O índice UNIQUE (gateway, gateway_event_id)
    // protege contra reenvio do mesmo evento.

    try {

        $ins = $db->prepare("
            INSERT INTO payment_events
                (
                    gateway,
                    gateway_event_id,
                    event_type,
                    payment_id,
                    payload,
                    processing_status,
                    created_at
                )
            VALUES (
                'mercadopago',
                ?,
                ?,
                NULL,
                ?,
                'received',
                NOW()
            )
        ");

        $ins->execute([
            $eventId,
            $eventType,
            $raw
        ]);

        $eventRowId = (int)$db->lastInsertId();

    } catch (PDOException $e) {

        if ((string)$e->getCode() === '23000') {

            $db->rollBack();

            // Se o evento já foi processado, tenta novamente um e-mail que
            // tenha ficado pendente por indisponibilidade temporária do SMTP.
            mpwh_send_order_confirmation($db, (int)$externalRef);

            mpwh_reply(200, [
                'ok' => true,
                'duplicate' => true
            ]);
        }

        throw $e;
    }


    /* =====================================================
       LOCALIZA PEDIDO E PAGAMENTO
    ===================================================== */

    $q = $db->prepare("
        SELECT
            o.id AS order_id,
            o.customer_id,
            o.total AS order_total,
            o.currency AS order_currency,

            p.id AS payment_id,
            p.amount AS payment_amount

        FROM orders o

        INNER JOIN payments p
            ON p.order_id = o.id

        WHERE
            o.id = ?
            AND p.gateway = 'mercadopago'
            AND p.gateway_payment_id = ?

        LIMIT 1
        FOR UPDATE
    ");

    $q->execute([
        (int)$externalRef,
        $dataId
    ]);

    $local = $q->fetch(PDO::FETCH_ASSOC);

    if (!$local) {
        throw new RuntimeException(
            'Pedido local correspondente não encontrado.'
        );
    }


    /* =====================================================
       VINCULA EVENTO AO PAGAMENTO
    ===================================================== */

    $eventUpdate = $db->prepare("
        UPDATE payment_events
        SET payment_id = ?
        WHERE id = ?
    ");

    $eventUpdate->execute([
        (int)$local['payment_id'],
        $eventRowId
    ]);


    /* =====================================================
       CONFERE VALORES
    ===================================================== */

    $expected = (float)$local['order_total'];

    if (
        strtoupper((string)$local['order_currency']) !== 'BRL' ||

        abs(
            $expected - (float)$local['payment_amount']
        ) > 0.009 ||

        abs($expected - $total) > 0.009 ||

        (
            $approved &&
            abs($expected - $paid) > 0.009
        )
    ) {
        throw new RuntimeException(
            'Valor/moeda divergente entre pedido local e Mercado Pago.'
        );
    }


    /* =====================================================
       PAGAMENTO AINDA NÃO APROVADO
    ===================================================== */

    if (!$approved) {

        $done = $db->prepare("
            UPDATE payment_events
            SET
                processing_status = 'processed',
                processed_at = NOW()
            WHERE id = ?
        ");

        $done->execute([$eventRowId]);

        $db->commit();

        mpwh_reply(200, [
            'ok' => true,
            'approved' => false,
            'mp_status' => $mpStatus,
            'mp_status_detail' => $mpDetail
        ]);
    }


    /* =====================================================
       IDENTIFICA MÉTODO DE PAGAMENTO
    ===================================================== */

    $method =
        $approvedTransaction['payment_method']['type']
        ?? $approvedTransaction['payment_method']['id']
        ?? null;

    $method = $method !== null
        ? substr((string)$method, 0, 40)
        : null;


    /* =====================================================
       ATUALIZA PAGAMENTO
    ===================================================== */

    $upd = $db->prepare("
        UPDATE payments
        SET
            status = 'paid',
            payment_method = COALESCE(?, payment_method),
            paid_at = COALESCE(paid_at, NOW()),
            raw_response = ?,
            updated_at = NOW()
        WHERE id = ?
    ");

    $upd->execute([
        $method,
        json_encode(
            $mp,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ),
        (int)$local['payment_id']
    ]);


    /* =====================================================
       ATUALIZA PEDIDO
    ===================================================== */

    $upd = $db->prepare("
        UPDATE orders
        SET
            status = 'paid',
            payment_status = 'paid',
            paid_at = COALESCE(paid_at, NOW()),
            updated_at = NOW()
        WHERE id = ?
    ");

    $upd->execute([
        (int)$local['order_id']
    ]);


    /* =====================================================
       GERA LICENÇAS DOS PRODUTOS
    ===================================================== */

    $itemsQ = $db->prepare("
        SELECT
            oi.id AS order_item_id,
            oi.product_id,
            pr.license_type,
            pr.license_duration_days,
            pr.max_activations

        FROM order_items oi

        INNER JOIN products pr
            ON pr.id = oi.product_id

        WHERE oi.order_id = ?
    ");

    $itemsQ->execute([
        (int)$local['order_id']
    ]);

    $items = $itemsQ->fetchAll(PDO::FETCH_ASSOC);


    foreach ($items as $item) {

        // Evita criar mais de uma licença para o mesmo item.
        $exists = $db->prepare("
            SELECT id
            FROM licenses
            WHERE order_item_id = ?
            LIMIT 1
        ");

        $exists->execute([
            (int)$item['order_item_id']
        ]);

        if ($exists->fetchColumn()) {
            continue;
        }


        /* =============================================
           VALIDADE DA LICENÇA
        ============================================= */

        $days = $item['license_duration_days'] !== null
            ? (int)$item['license_duration_days']
            : null;

        $expires = (
            $days !== null &&
            $days > 0
        )
            ? (new DateTimeImmutable())
                ->modify('+' . $days . ' days')
                ->format('Y-m-d H:i:s')
            : null;


        /* =============================================
           INSERE LICENÇA
        ============================================= */

        $create = $db->prepare("
            INSERT INTO licenses
            (
                order_id,
                order_item_id,
                customer_id,
                product_id,
                license_key,
                license_type,
                status,
                max_activations,
                starts_at,
                expires_at,
                created_at,
                updated_at
            )
            VALUES (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                'active',
                ?,
                NOW(),
                ?,
                NOW(),
                NOW()
            )
        ");

        $create->execute([
            (int)$local['order_id'],
            (int)$item['order_item_id'],
            (int)$local['customer_id'],
            (int)$item['product_id'],
            mpwh_new_license_key(),
            (string)($item['license_type'] ?: 'perpetual'),
            max(1, (int)$item['max_activations']),
            $expires
        ]);
    }


    /* =====================================================
       REGISTRA E-MAIL DE CONFIRMAÇÃO NA FILA
    ===================================================== */

    $outbox = $db->prepare("INSERT INTO email_outbox
        (order_id, notification_type, status, attempts, created_at)
        VALUES (?, 'payment_approved', 'pending', 0, NOW())
        ON DUPLICATE KEY UPDATE order_id = VALUES(order_id)");
    $outbox->execute([(int)$local['order_id']]);


    /* =====================================================
       FINALIZA EVENTO
    ===================================================== */

    $done = $db->prepare("
        UPDATE payment_events
        SET
            processing_status = 'processed',
            processed_at = NOW()
        WHERE id = ?
    ");

    $done->execute([$eventRowId]);

    $db->commit();

    // O pagamento e a licença já estão confirmados no banco.
    // Se o SMTP falhar, o pedido continua pago e o e-mail fica pendente.
    mpwh_send_order_confirmation($db, (int)$local['order_id']);


    /* =====================================================
       RESPOSTA DE SUCESSO
    ===================================================== */

    mpwh_reply(200, [
        'ok' => true,
        'approved' => true,
        'order_id' => (int)$local['order_id'],
        'licenses_created_or_confirmed' => count($items)
    ]);


} catch (Throwable $e) {

    if (
        isset($db) &&
        $db instanceof PDO &&
        $db->inTransaction()
    ) {
        $db->rollBack();
    }

    mpwh_log($e->getMessage());

    mpwh_reply(500, [
        'ok' => false,
        'message' => 'Erro interno ao processar notificação.'
    ]);
}
