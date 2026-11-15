<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/mercadopago.php';

header('Content-Type: text/plain; charset=utf-8');

// Coloque o ID da ordem que já foi criada.
$orderId = 'ORDTST01M6RSTH1ZT8BM4QB2PTR4SBKS';

if (!defined('MP_ACCESS_TOKEN') || MP_ACCESS_TOKEN === '') {
    exit("ERRO: Access Token não configurado.");
}

$base = defined('MP_API_URL')
    ? rtrim(MP_API_URL, '/')
    : 'https://api.mercadopago.com';

$url = $base . '/v1/orders/' . rawurlencode($orderId);

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPGET => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . MP_ACCESS_TOKEN,
        'Accept: application/json'
    ],
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

echo "HTTP: " . $httpCode . "\n\n";

if ($response === false) {
    exit("ERRO CURL: " . $error);
}

$data = json_decode($response, true);

if (!is_array($data)) {
    exit("Resposta não é JSON válido:\n" . $response);
}

echo "RESPOSTA DA API:\n\n";

echo json_encode(
    $data,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
);