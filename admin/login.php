<?php
require __DIR__ . '/_init.php';

if (!empty($_SESSION['store_admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    store_check_csrf();
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');

    $stmt = store_db()->prepare(
        'SELECT id, name, email, password_hash FROM admins WHERE email = ? AND is_active = 1 LIMIT 1'
    );
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['store_admin_id'] = (int)$admin['id'];
        $_SESSION['store_admin_name'] = $admin['name'];
        header('Location: index.php');
        exit;
    }
    $error = 'E-mail ou senha inválidos.';
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Entrar | Administração Toplevel</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="min-vh-100 d-flex align-items-center">
<main class="container" style="max-width:460px">
<div class="contact-panel shadow-sm">
<a href="../index.php" class="text-decoration-none fw-bold">TOPLEVELSISTEMAS</a>
<h1 class="h3 mt-4">Administração da loja</h1>
<p class="text-secondary">Acesse para gerenciar todos os produtos digitais.</p>
<?php if ($error): ?><div class="alert alert-danger"><?= store_h($error) ?></div><?php endif; ?>
<form method="post">
<input type="hidden" name="_csrf" value="<?= store_h(store_csrf()) ?>">
<label class="form-label">E-mail</label>
<input class="form-control mb-3" type="email" name="email" required autocomplete="username">
<label class="form-label">Senha</label>
<input class="form-control mb-4" type="password" name="password" required autocomplete="current-password">
<button class="btn btn-dark w-100 py-3" type="submit">Entrar</button>
</form>
</div>
</main>
</body>
</html>
