<?php
require __DIR__ . '/_init.php';
$count=(int)store_db()->query('SELECT COUNT(*) FROM admins')->fetchColumn();
if($count>0){http_response_code(403);exit('Já existe um administrador. Apague este arquivo do servidor.');}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 store_check_csrf();$name=trim((string)($_POST['name']??''));$email=strtolower(trim((string)($_POST['email']??'')));$pass=(string)($_POST['password']??'');
 if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($pass)<12){$error='Informe nome, e-mail válido e senha com pelo menos 12 caracteres.';}
 else{store_db()->prepare('INSERT INTO admins(name,email,password_hash) VALUES(?,?,?)')->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT)]);header('Location: login.php');exit;}
}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Criar administrador</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="../assets/css/style.css" rel="stylesheet"></head><body class="min-vh-100 d-flex align-items-center"><main class="container" style="max-width:500px"><div class="contact-panel"><h1 class="h3">Criar administrador inicial</h1><p class="text-secondary">Use uma senha exclusiva com pelo menos 12 caracteres.</p><?php if($error):?><div class="alert alert-danger"><?=store_h($error)?></div><?php endif;?><form method="post"><input type="hidden" name="_csrf" value="<?=store_h(store_csrf())?>"><label class="form-label">Nome</label><input class="form-control mb-3" name="name" required><label class="form-label">E-mail</label><input class="form-control mb-3" type="email" name="email" required><label class="form-label">Senha</label><input class="form-control mb-4" type="password" name="password" minlength="12" required><button class="btn btn-dark w-100">Criar administrador</button></form></div></main></body></html>
