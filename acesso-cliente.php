
<?php


require_once __DIR__ . '/config/store.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$message = 'Se o e-mail estiver cadastrado na loja, enviaremos um link para criar ou redefinir sua senha.';
$error = '';

if (empty($_SESSION['store_customer_csrf'])) {
    $_SESSION['store_customer_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['_csrf']) ||
        !hash_equals(
            $_SESSION['store_customer_csrf'],
            (string) $_POST['_csrf']
        )
    ) {
        http_response_code(419);
        exit('Sessão expirada. Atualize a página.');
    }

    $email = strtolower(trim((string) ($_POST['email'] ?? '')));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Informe um e-mail válido.';

    } else {

        try {

            $db = store_db();

            $q = $db->prepare(
                'SELECT id, name, email
                 FROM customers
                 WHERE email = ? AND is_active = 1
                 LIMIT 1'
            );

            $q->execute([$email]);
            $customer = $q->fetch();

            if ($customer) {

                // Gera token seguro
                $token = bin2hex(random_bytes(32));
                $hash = hash('sha256', $token);

                // Invalida tokens anteriores
                $db->prepare(
                    'UPDATE customer_access_tokens
                     SET used_at = NOW()
                     WHERE customer_id = ? AND used_at IS NULL'
                )->execute([(int) $customer['id']]);

                // Registra novo token com validade de 1 hora
                $db->prepare(
                    'INSERT INTO customer_access_tokens
                     (customer_id, token_hash, expires_at, created_at)
                     VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW())'
                )->execute([
                    (int) $customer['id'],
                    $hash
                ]);

                // Verifica dependências de e-mail
                $autoload = __DIR__ . '/vendor/autoload.php';
                $configPath = __DIR__ . '/config/email.php';

                if (!is_file($autoload) || !is_file($configPath)) {
                    throw new RuntimeException(
                        'Serviço de e-mail indisponível.'
                    );
                }

                require_once $autoload;

                $cfg = require $configPath;

                if (
                    !is_array($cfg) ||
                    empty($cfg['host']) ||
                    empty($cfg['username']) ||
                    empty($cfg['password']) ||
                    empty($cfg['from_email'])
                ) {
                    throw new RuntimeException(
                        'Configuração SMTP incompleta.'
                    );
                }

                // Monta link de ativação
                $base = rtrim((string) STORE_BASE_URL, '/');

                $link = $base .
                    '/ativar-conta.php?token=' .
                    urlencode($token);

                // Proteção de conteúdo HTML
                $h = static fn($v): string =>
                    htmlspecialchars(
                        (string) $v,
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                    );

                // Configura PHPMailer
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);

                $mail->isSMTP();

                $mail->Host = (string) $cfg['host'];
                $mail->SMTPAuth = true;

                $mail->Username = (string) $cfg['username'];
                $mail->Password = (string) $cfg['password'];

                $mail->Port = (int) ($cfg['port'] ?? 587);

                $enc = strtolower(
                    (string) ($cfg['encryption'] ?? 'tls')
                );

                if ($enc === 'ssl') {
                    $mail->SMTPSecure =
                        PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                } else {
                    $mail->SMTPSecure =
                        PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                }

                $mail->CharSet = 'UTF-8';

                $mail->setFrom(
                    (string) $cfg['from_email'],
                    (string) ($cfg['from_name'] ?? 'Toplevel Store')
                );

                $mail->addAddress(
                    (string) $customer['email'],
                    (string) $customer['name']
                );

                $mail->isHTML(true);

                $mail->Subject = 'Acesse sua conta - Toplevel Store';

                $mail->Body = '
                    <div style="font-family:Arial,sans-serif;max-width:600px;margin:auto;color:#24312b">
                        <h2>Área do cliente</h2>

                        <p>Olá, ' . $h($customer['name']) . '!</p>

                        <p>
                            Use o botão abaixo para criar ou redefinir
                            sua senha e acessar suas compras, licenças
                            e instaladores.
                        </p>

                        <p>
                            <a
                                style="display:inline-block;padding:13px 22px;background:#16834a;color:#fff;text-decoration:none;border-radius:8px"
                                href="' . $h($link) . '"
                            >
                                Acessar minha conta
                            </a>
                        </p>

                        <p>
                            Este link é válido por 1 hora e pode ser
                            usado uma única vez.
                        </p>
                    </div>
                ';

                $mail->AltBody =
                    'Acesse sua conta Toplevel Store: ' .
                    $link .
                    ' (válido por 1 hora e uso único).';

                $mail->send();
            }

        } catch (Throwable $e) {

            error_log(
                '[Toplevel Customer Access] ' . $e->getMessage()
            );

            $error = 'Não foi possível enviar o e-mail agora. Confira a configuração SMTP e tente novamente.';
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

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            background: #f3f6f4;
            min-height: 100vh;
            padding: 20px;
        }

        .panel {
            max-width: 480px;
            margin: 8vh auto;
            background: #fff;
            padding: 32px;
            border-radius: 18px;
            box-shadow: 0 12px 40px #10201812;
        }

        .brand {
            color: #16834a;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .btn-success {
            background: #16834a;
            border-color: #16834a;
        }

        .btn-success:hover {
            background: #116b3c;
            border-color: #116b3c;
        }
    </style>
</head>

<body>

    <main class="panel">

        <div class="brand mb-2">
            TOPLEVEL STORE
        </div>

        <h1 class="h3 mb-3">
            Acesse sua conta
        </h1>

        <p class="text-secondary">
            Informe o e-mail usado na compra.
            Enviaremos um link seguro para criar ou redefinir sua senha.
        </p>

        <?php if ($error): ?>

            <div class="alert alert-danger">
                <?= store_h($error) ?>
            </div>

        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>

            <div class="alert alert-success">
                <?= store_h($message) ?>
            </div>

        <?php endif; ?>

        <form method="post">

            <input
                type="hidden"
                name="_csrf"
                value="<?= store_h($_SESSION['store_customer_csrf']) ?>"
            >

            <label class="form-label" for="email">
                E-mail da compra
            </label>

            <input
                class="form-control mb-3"
                id="email"
                name="email"
                type="email"
                maxlength="190"
                required
                autocomplete="email"
            >

            <button
                type="submit"
                class="btn btn-success w-100"
            >
                Enviar link de acesso
            </button>

        </form>

        <p class="small text-center mt-4 mb-0">
            <a href="loja.php">
                Voltar à loja
            </a>
        </p>

    </main>

</body>
</html>
