<?php
declare(strict_types=1);

require_once __DIR__ . '/config/store.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$validFormat = preg_match('/^[a-f0-9]{64}$/', $token) === 1;
$error = '';

if (empty($_SESSION['store_magic_csrf'])) {
    $_SESSION['store_magic_csrf'] = bin2hex(random_bytes(32));
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!hash_equals(
        (string)$_SESSION['store_magic_csrf'],
        (string)($_POST['_csrf'] ?? '')
    )) {
        http_response_code(419);
        exit('Sessão expirada. Atualize a página.');
    }

    if (!$validFormat) {
        $error = 'Link inválido. Solicite um novo link de acesso.';
    } else {
        $db = store_db();
        $q = $db->prepare(
            'SELECT t.id, t.customer_id
             FROM customer_access_tokens t
             INNER JOIN customers c ON c.id = t.customer_id
             WHERE t.token_hash = ?
               AND t.used_at IS NULL
               AND t.expires_at > NOW()
               AND c.is_active = 1
             LIMIT 1'
        );
        $q->execute([hash('sha256', $token)]);
        $row = $q->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $error = 'Este link expirou ou já foi utilizado. Solicite um novo acesso.';
        } else {
            $db->beginTransaction();
            try {
                $consume = $db->prepare(
                    'UPDATE customer_access_tokens
                     SET used_at = NOW()
                     WHERE id = ? AND used_at IS NULL AND expires_at > NOW()'
                );
                $consume->execute([(int)$row['id']]);

                if ($consume->rowCount() !== 1) {
                    throw new RuntimeException('Link já utilizado ou expirado.');
                }

                $db->commit();
                session_regenerate_id(true);
                $_SESSION['store_customer_id'] = (int)$row['customer_id'];
                header('Location: minha-conta.php');
                exit;
            } catch (Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                error_log('[Toplevel Magic Link] ' . $e->getMessage());
                $error = 'Não foi possível validar o link. Solicite um novo acesso.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Acessar minha conta | Toplevel Store</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#f3f6f4;min-height:100vh;padding:20px}
.panel{max-width:480px;margin:8vh auto;background:#fff;padding:32px;border-radius:18px;box-shadow:0 12px 40px #10201812}
.btn-success{background:#16834a;border-color:#16834a}
</style>
</head>
<body>
<main class="panel">
    <h1 class="h3 mb-3">Acessar Área do Cliente</h1>
    <p class="text-secondary">Confirme para consultar suas compras, licenças e downloads. Não é necessário criar senha.</p>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?=store_h($error)?></div>
        <a class="btn btn-outline-success w-100" href="acesso-cliente.php">Solicitar novo link</a>
    <?php elseif ($validFormat): ?>
        <form method="post">
            <input type="hidden" name="token" value="<?=store_h($token)?>">
            <input type="hidden" name="_csrf" value="<?=store_h($_SESSION['store_magic_csrf'])?>">
            <button type="submit" class="btn btn-success w-100">Entrar na minha conta</button>
        </form>
        <p class="small text-secondary mt-3 mb-0">Por segurança, o link é de uso único e expira em 1 hora.</p>
    <?php else: ?>
        <div class="alert alert-danger">Link inválido. Solicite um novo acesso.</div>
        <a class="btn btn-outline-success w-100" href="acesso-cliente.php">Solicitar novo link</a>
    <?php endif; ?>
</main>
</body>
</html>
