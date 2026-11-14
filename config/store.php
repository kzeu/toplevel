<?php
declare(strict_types=1);

// Configure os dados do MySQL do seu servidor. Não publique credenciais no Git.
define('STORE_DB_HOST', 'localhost');
define('STORE_DB_NAME', 'toplevel_loja');
define('STORE_DB_USER', 'root');
define('STORE_DB_PASS', '');
define('STORE_CURRENCY', 'BRL');
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Toplevel Store');
}

// URL pública usada nos retornos do Mercado Pago (atualize quando o túnel mudar).
define('STORE_BASE_URL', 'https://cole-substantially-blanket-fairfield.trycloudflare.com/toplevel');
define('STORE_DOWNLOAD_DIR', dirname(__DIR__) . '/storage/downloads');

function store_db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $dsn = 'mysql:host=' . STORE_DB_HOST . ';dbname=' . STORE_DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, STORE_DB_USER, STORE_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
function store_h(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function store_csrf(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['store_csrf'])) $_SESSION['store_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['store_csrf'];
}
function store_check_csrf(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $sent = (string)($_POST['_csrf'] ?? '');
    if (empty($_SESSION['store_csrf']) || !hash_equals($_SESSION['store_csrf'], $sent)) {
        http_response_code(419); exit('Sua sessão expirou. Atualize a página e tente novamente.');
    }
}
function store_admin_required(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['store_admin_id'])) { header('Location: login.php'); exit; }
}
