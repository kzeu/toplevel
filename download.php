<?php
declare(strict_types=1);

require_once __DIR__ . '/config/store.php';

$token = trim((string)($_GET['token'] ?? ''));
if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    http_response_code(404);
    exit('Link de download inválido.');
}

try {
    $db = store_db();
    $q = $db->prepare("SELECT d.id AS download_id, d.order_id, d.product_id,
                              o.payment_status, p.name, p.file_path
                       FROM downloads d
                       INNER JOIN orders o ON o.id = d.order_id
                       INNER JOIN products p ON p.id = d.product_id
                       WHERE d.token_hash = ? LIMIT 1");
    $q->execute([hash('sha256', $token)]);
    $download = $q->fetch(PDO::FETCH_ASSOC);

    if (!$download || ($download['payment_status'] ?? '') !== 'paid') {
        http_response_code(404);
        exit('Download não encontrado ou pagamento não confirmado.');
    }

    $fileName = basename((string)$download['file_path']);
    $filePath = STORE_DOWNLOAD_DIR . DIRECTORY_SEPARATOR . $fileName;
    if ($fileName === '' || !is_file($filePath) || !is_readable($filePath)) {
        http_response_code(404);
        exit('O instalador não está disponível no momento.');
    }

    $upd = $db->prepare("UPDATE downloads SET downloaded_at = NOW(), download_count = download_count + 1 WHERE id = ?");
    $upd->execute([(int)$download['download_id']]);

    $downloadName = preg_replace('/[^A-Za-z0-9._-]/', '_', (string)$download['name']);
    if ($downloadName === '') $downloadName = 'instalador';
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $downloadName .= '.' . $extension;

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . (string)filesize($filePath));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');
    readfile($filePath);
    exit;
} catch (Throwable $e) {
    error_log('[Toplevel Store Download] ' . $e->getMessage());
    http_response_code(500);
    exit('Não foi possível iniciar o download. Tente novamente mais tarde.');
}
