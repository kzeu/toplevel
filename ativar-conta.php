<?php
declare(strict_types=1);
require_once __DIR__ . '/config/store.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$valid = preg_match('/^[a-f0-9]{64}$/', $token) === 1;
$error = '';
if (empty($_SESSION['store_setpass_csrf'])) $_SESSION['store_setpass_csrf'] = bin2hex(random_bytes(32));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['store_setpass_csrf'], (string)($_POST['_csrf'] ?? ''))) { http_response_code(419); exit('Sessão expirada.'); }
    $password = (string)($_POST['password'] ?? ''); $confirm = (string)($_POST['confirm'] ?? '');
    if (!$valid) $error = 'Link inválido.';
    elseif (strlen($password) < 10) $error = 'A senha deve ter pelo menos 10 caracteres.';
    elseif ($password !== $confirm) $error = 'As senhas não conferem.';
    else {
        $db = store_db();
        $q = $db->prepare('SELECT t.id, t.customer_id FROM customer_access_tokens t WHERE t.token_hash = ? AND t.used_at IS NULL AND t.expires_at > NOW() LIMIT 1');
        $q->execute([hash('sha256', $token)]); $row = $q->fetch();
        if (!$row) $error = 'Este link expirou ou já foi utilizado. Solicite um novo acesso.';
        else {
            $db->beginTransaction();
            try {
                $db->prepare('UPDATE customers SET password_hash = ?, updated_at = NOW() WHERE id = ? AND is_active = 1')->execute([password_hash($password, PASSWORD_DEFAULT), (int)$row['customer_id']]);
                $db->prepare('UPDATE customer_access_tokens SET used_at = NOW() WHERE id = ?')->execute([(int)$row['id']]);
                $db->commit();
                session_regenerate_id(true); $_SESSION['store_customer_id'] = (int)$row['customer_id'];
                header('Location: minha-conta.php'); exit;
            } catch (Throwable $e) { $db->rollBack(); throw $e; }
        }
    }
}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Definir senha | Toplevel Store"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{background:#f3f6f4}.panel{max-width:480px;margin:8vh auto;background:#fff;padding:32px;border-radius:18px;box-shadow:0 12px 40px #10201812}</style></head><body><main class="panel"><h1 class="h3 mb-3">Crie sua senha</h1><p class="text-secondary">Use uma senha com pelo menos 10 caracteres.</p><?php if($error): ?><div class="alert alert-danger"><?=store_h($error)?></div><?php endif; ?><?php if($valid): ?><form method="post"><input type="hidden" name="token" value="<?=store_h($token)?>"><input type="hidden" name="_csrf" value="<?=store_h($_SESSION['store_setpass_csrf'])?>"><label class="form-label">Nova senha</label><input class="form-control mb-3" type="password" name="password" minlength="10" autocomplete="new-password" required><label class="form-label">Confirme a senha</label><input class="form-control mb-3" type="password" name="confirm" minlength="10" autocomplete="new-password" required><button class="btn btn-success w-100">Salvar senha e acessar</button></form><?php else: ?><a class="btn btn-success" href="acesso-cliente.php">Solicitar novo link</a><?php endif; ?></main></body></html>
