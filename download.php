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
    $q = $db->prepare("
        SELECT d.id AS download_id, d.expires_at, d.download_count,
               d.max_downloads, o.payment_status, p.name, p.file_path
        FROM downloads d
        INNER JOIN orders o ON o.id = d.order_id
        INNER JOIN products p ON p.id = d.product_id
        WHERE d.token_hash = ? LIMIT 1
    ");
    $q->execute([hash('sha256', $token)]);
    $download = $q->fetch(PDO::FETCH_ASSOC);

    if (!$download || ($download['payment_status'] ?? '') !== 'paid') {
        http_response_code(404);
        exit('Download não encontrado ou pagamento não confirmado.');
    }

    if (!empty($download['expires_at']) && strtotime((string)$download['expires_at']) < time()) {
        http_response_code(410);
        exit('Este link de download expirou. Entre em contato com o atendimento.');
    }

    $count = (int)($download['download_count'] ?? 0);
    $max = $download['max_downloads'];
    if ($max !== null && $count >= (int)$max) {
        http_response_code(429);
        exit('Este link atingiu o limite de downloads. Entre em contato com o atendimento.');
    }

    $fileName = basename((string)$download['file_path']);
    $filePath = STORE_DOWNLOAD_DIR . DIRECTORY_SEPARATOR . $fileName;
    if ($fileName === '' || !is_file($filePath) || !is_readable($filePath)) {
        http_response_code(404);
        exit('O instalador não está disponível no momento.');
    }

    clearstatcache(true, $filePath);
    $fileSize = filesize($filePath);
    if ($fileSize === false || $fileSize <= 0) {
        throw new RuntimeException('Arquivo vazio ou tamanho indisponível.');
    }

    $handle = fopen($filePath, 'rb');
    if ($handle === false) {
        throw new RuntimeException('Não foi possível abrir o arquivo.');
    }

    // Mantém o controle de validade e limite existente.
    $upd = $db->prepare("
        UPDATE downloads
        SET downloaded_at = NOW(), download_count = COALESCE(download_count, 0) + 1
        WHERE id = ?
          AND (expires_at IS NULL OR expires_at >= NOW())
          AND (max_downloads IS NULL OR COALESCE(download_count, 0) < max_downloads)
    ");
    $upd->execute([(int)$download['download_id']]);
    if ($upd->rowCount() !== 1) {
        fclose($handle);
        http_response_code(410);
        exit('Este link não está mais disponível. Solicite atendimento.');
    }

    $downloadName = preg_replace('/[^A-Za-z0-9._-]/', '_', (string)$download['name']);
    if ($downloadName === '') $downloadName = 'instalador';
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if ($extension !== '') $downloadName .= '.' . $extension;

    // Remove qualquer saída pendente e desativa compressão/buffering do PHP.
    ini_set('zlib.output_compression', '0');
    ini_set('output_buffering', '0');
    while (ob_get_level() > 0) {
        if (!@ob_end_clean()) break;
    }
    if (function_exists('apache_setenv')) @apache_setenv('no-gzip', '1');
    @set_time_limit(0);
    @ignore_user_abort(true);
    if (function_exists('header_remove')) header_remove('Content-Encoding');

    header('Content-Description: File Transfer');
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $downloadName . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
    header('Content-Length: ' . (string)$fileSize);
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('X-Content-Type-Options: nosniff');

    // Envia os bytes originais, em blocos, sem alterar o conteúdo do ZIP.
    $sent = 0;
    while (!feof($handle)) {
        $chunk = fread($handle, 1024 * 1024);
        if ($chunk === false || $chunk === '') break;
        echo $chunk;
        $sent += strlen($chunk);
        flush();
        if (connection_aborted()) break;
    }
    fclose($handle);

    if ($sent !== $fileSize) {
        error_log('[Toplevel Store Download] Transmissão incompleta: ' . $sent . ' de ' . $fileSize . ' bytes.');
    }
    exit;

} catch (Throwable $e) {
    error_log('[Toplevel Store Download] ' . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
        exit('Não foi possível iniciar o download. Tente novamente mais tarde.');
    }
    exit;
}
