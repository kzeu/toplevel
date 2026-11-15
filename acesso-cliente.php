<?php
declare(strict_types=1);

require_once __DIR__ . '/config/store.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$message = 'Se o e-mail estiver cadastrado na loja, enviaremos um link seguro para acessar suas compras.';
$error = '';

if (empty($_SESSION['store_customer_csrf'])) {
    $_SESSION['store_customer_csrf'] = bin2hex(random_bytes(32));
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!hash_equals(
        (string)$_SESSION['store_customer_csrf'],
        (string)($_POST['_csrf'] ?? '')
    )) {
        http_response_code(419);
        exit('Sessão expirada. Atualize a página.');
    }

    $email = strtolower(trim((string)($_POST['email'] ?? '')));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Informe um e-mail válido.';
    } else {
        try {
            $db = store_db();
            $q = $db->prepare(
                'SELECT id, name, email FROM customers
                 WHERE email = ? AND is_active = 1 LIMIT 1'
            );
            $q->execute([$email]);
            $customer = $q->fetch(PDO::FETCH_ASSOC);

            if ($customer) {
                $token = bin2hex(random_bytes(32));
                $hash = hash('sha256', $token);

                $db->prepare(
                    'UPDATE customer_access_tokens
                     SET used_at = NOW()
                     WHERE customer_id = ? AND used_at IS NULL'
                )->execute([(int)$customer['id']]);

                $db->prepare(
                    'INSERT INTO customer_access_tokens
                     (customer_id, token_hash, expires_at, created_at)
                     VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW())'
                )->execute([(int)$customer['id'], $hash]);

                $autoload = __DIR__ . '/vendor/autoload.php';
                $configPath = __DIR__ . '/config/email.php';
                if (!is_file($autoload) || !is_file($configPath)) {
                    throw new RuntimeException('Serviço de e-mail indisponível.');
                }

                require_once $autoload;
                $cfg = require $configPath;
                if (!is_array($cfg) || empty($cfg['host']) ||
                    empty($cfg['username']) || empty($cfg['password']) ||
                    empty($cfg['from_email'])) {
                    throw new RuntimeException('Configuração SMTP incompleta.');
                }

                // No XAMPP local, gera um link que aponta para este projeto.
                // Em produção, continua usando STORE_BASE_URL.
                $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));

                if (preg_match('/^(localhost|127\\.0\\.0\\.1)(:\\d+)?$/', $host)) {
                    $projectPath = str_replace('\\\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/')));
                    $projectPath = rtrim($projectPath, '/');
                    $base = 'http://' . $host . ($projectPath === '' || $projectPath === '.' ? '' : $projectPath);
                } else {
                    $base = rtrim((string)STORE_BASE_URL, '/');
                }

                $link = $base . '/ativar-conta.php?token=' . rawurlencode($token);
                $h = static fn($v): string => htmlspecialchars(
                    (string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'
                );

                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = (string)$cfg['host'];
                $mail->SMTPAuth = true;
                $mail->Username = (string)$cfg['username'];
                $mail->Password = (string)$cfg['password'];
                $mail->Port = (int)($cfg['port'] ?? 587);
                $enc = strtolower((string)($cfg['encryption'] ?? 'tls'));
                $mail->SMTPSecure = $enc === 'ssl'
                    ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                    : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->CharSet = 'UTF-8';
                $mail->setFrom(
                    (string)$cfg['from_email'],
                    (string)($cfg['from_name'] ?? 'Toplevel Store')
                );
                $mail->addAddress((string)$customer['email'], (string)$customer['name']);
                $mail->isHTML(true);
                $mail->Subject = 'Seu link de acesso - Toplevel Store';
                $mail->Body =
                    '<div style="font-family:Arial,sans-serif;max-width:600px;margin:auto;color:#24312b">' .
                    '<h2>Área do cliente</h2>' .
                    '<p>Olá, ' . $h($customer['name']) . '!</p>' .
                    '<p>Clique no botão abaixo para consultar suas compras, licenças e downloads. Não é necessário criar senha.</p>' .
                    '<p><a style="display:inline-block;padding:13px 22px;background:#16834a;color:#fff;text-decoration:none;border-radius:8px" href="' . $h($link) . '">Acessar minha conta</a></p>' .
                    '<p>Por segurança, o link expira em 1 hora e só pode ser usado uma vez.</p>' .
                    '<p>Se você não solicitou este acesso, ignore esta mensagem.</p></div>';
                $mail->AltBody =
                    "Acesse sua Área do Cliente Toplevel Store pelo link abaixo. Não é necessário criar senha.\n\n" .
                    $link . "\n\nLink válido por 1 hora e uso único.";
                $mail->send();
            }

        } catch (Throwable $e) {
            error_log('[Toplevel Customer Access] ' . $e->getMessage());
            $error = 'Não foi possível enviar o e-mail agora. Tente novamente mais tarde.';
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Acesso do cliente | Toplevel Store</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#f3f6f4;min-height:100vh;padding:20px}
.panel{max-width:480px;margin:8vh auto;background:#fff;padding:32px;border-radius:18px;box-shadow:0 12px 40px #10201812}
.brand{color:#16834a;font-weight:800;letter-spacing:1px}
.btn-success{background:#16834a;border-color:#16834a}
.btn-success:hover{background:#116b3c;border-color:#116b3c}
</style>
</head>
<body>
<main class="panel">
    <div class="brand mb-2">TOPLEVEL STORE</div>
    <h1 class="h3 mb-3">Acesse suas compras</h1>
    <p class="text-secondary">Informe o e-mail usado na compra. Enviaremos um link seguro para consultar seus pedidos, licenças e downloads. Você não precisa criar senha.</p>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?=store_h($error)?></div>
    <?php elseif (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'): ?>
        <div class="alert alert-success"><?=store_h($message)?></div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="_csrf" value="<?=store_h($_SESSION['store_customer_csrf'])?>">
        <label class="form-label" for="email">E-mail usado na compra</label>
        <input class="form-control mb-3" id="email" name="email" type="email" maxlength="190" required autocomplete="email">
        <button type="submit" class="btn btn-success w-100">Enviar link de acesso</button>
    </form>

    <p class="small text-center mt-4 mb-0"><a href="loja.php">Voltar à loja</a></p>
</main>
</body>
</html>
