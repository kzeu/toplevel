<?php
declare(strict_types=1);

// Copie este arquivo para store.php e informe os dados do ambiente.
define('STORE_DB_HOST', 'localhost');
define('STORE_DB_NAME', 'nome_do_banco');
define('STORE_DB_USER', 'usuario_do_banco');
define('STORE_DB_PASS', 'senha_do_banco');
define('STORE_CURRENCY', 'BRL');

if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Toplevel Store');
}

// URL pública do site (ajuste para o domínio do ambiente).
define('STORE_BASE_URL', 'https://seudominio.com');
define('STORE_DOWNLOAD_DIR', dirname(__DIR__) . '/storage/downloads');
