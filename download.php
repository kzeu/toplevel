
<?php


require_once __DIR__ . '/config/store.php';

// Recebe o token do download
$token = trim((string)($_GET['token'] ?? ''));

if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    http_response_code(404);
    exit('Link de download inválido.');
}

try {
    $db = store_db();

    // Busca o download, incluindo validade e limite
    $q = $db->prepare("
        SELECT
            d.id AS download_id,
            d.order_id,
            d.product_id,
            d.expires_at,
            d.download_count,
            d.max_downloads,
            o.payment_status,
            p.name,
            p.file_path
        FROM downloads d
        INNER JOIN orders o ON o.id = d.order_id
        INNER JOIN products p ON p.id = d.product_id
        WHERE d.token_hash = ?
        LIMIT 1
    ");

    $q->execute([hash('sha256', $token)]);
    $download = $q->fetch(PDO::FETCH_ASSOC);

    // Verifica se o download existe e o pagamento foi aprovado
    if (!$download || ($download['payment_status'] ?? '') !== 'paid') {
        http_response_code(404);
        exit('Download não encontrado ou pagamento não confirmado.');
    }

    // Verifica validade somente quando houver data definida
    if (
        !empty($download['expires_at']) &&
        strtotime((string)$download['expires_at']) < time()
    ) {
        http_response_code(410);
        exit('Este link de download expirou. Entre em contato com o atendimento.');
    }

    // Verifica limite somente quando houver limite definido
    $downloadCount = (int)($download['download_count'] ?? 0);
    $maxDownloads = $download['max_downloads'];

    if (
        $maxDownloads !== null &&
        $downloadCount >= (int)$maxDownloads
    ) {
        http_response_code(429);
        exit('Este link atingiu o limite de downloads. Entre em contato com o atendimento.');
    }

    // Localiza o instalador
    $fileName = basename((string)$download['file_path']);
    $filePath = STORE_DOWNLOAD_DIR . DIRECTORY_SEPARATOR . $fileName;

    if (
        $fileName === '' ||
        !is_file($filePath) ||
        !is_readable($filePath)
    ) {
        http_response_code(404);
        exit('O instalador não está disponível no momento.');
    }

    // Registra o download respeitando validade e limite, inclusive NULL
    $upd = $db->prepare("
        UPDATE downloads
        SET
            downloaded_at = NOW(),
            download_count = COALESCE(download_count, 0) + 1
        WHERE id = ?
          AND (expires_at IS NULL OR expires_at >= NOW())
          AND (
              max_downloads IS NULL
              OR COALESCE(download_count, 0) < max_downloads
          )
    ");

    $upd->execute([(int)$download['download_id']]);

    if ($upd->rowCount() !== 1) {
        http_response_code(410);
        exit('Este link não está mais disponível. Solicite atendimento.');
    }

    // Prepara o nome do arquivo para o navegador
    $downloadName = preg_replace(
        '/[^A-Za-z0-9._-]/',
        '_',
        (string)$download['name']
    );

    if ($downloadName === '') {
        $downloadName = 'instalador';
    }

    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $downloadName .= '.' . $extension;

    // Envia o arquivo
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
