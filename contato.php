<?php
require __DIR__ . '/config/config.php';
$pageTitle = 'Contato';
$pageDescription = 'Entre em contato para conversar sobre sistemas, integrações, automações ou produtos digitais.';
$errors = [];
$success = false;
$name = '';
$email = '';
$subject = trim((string)($_GET['assunto'] ?? ''));
$message = '';
$honeypot = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $subject = trim((string)($_POST['subject'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));
    $honeypot = trim((string)($_POST['website'] ?? ''));
    if ($honeypot !== '') { $success = true; }
    else {
        if ($name === '' || mb_strlen($name) > 100) $errors[] = 'Informe seu nome (até 100 caracteres).';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 180) $errors[] = 'Informe um e-mail válido.';
        if ($subject === '' || mb_strlen($subject) > 120) $errors[] = 'Informe o assunto (até 120 caracteres).';
        if ($message === '' || mb_strlen($message) < 10 || mb_strlen($message) > 5000) $errors[] = 'A mensagem deve ter entre 10 e 5.000 caracteres.';
        if (!$errors) {
            $safeName = str_replace(["\r", "\n"], ' ', $name);
            $safeSubject = str_replace(["\r", "\n"], ' ', $subject);
            $mailSubject = 'Contato pelo site: ' . $safeSubject;
            $mailBody = "Nome: {$safeName}\nE-mail: {$email}\nAssunto: {$safeSubject}\n\nMensagem:\n{$message}\n";
            $host = preg_replace('/[^a-z0-9.-]/i', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
            $fromDomain = $host !== '' ? $host : 'localhost';
            $headers = "From: site@{$fromDomain}\r\n" . "Reply-To: {$email}\r\n" . "Content-Type: text/plain; charset=UTF-8\r\n";
            if (CONTACT_EMAIL !== 'contato@seudominio.com.br' && filter_var(CONTACT_EMAIL, FILTER_VALIDATE_EMAIL) && function_exists('mail') && @mail(CONTACT_EMAIL, '=?UTF-8?B?' . base64_encode($mailSubject) . '?=', $mailBody, $headers)) {
                $success = true;
                $name = $email = $subject = $message = '';
            } else {
                $errors[] = 'O formulário foi validado, mas o envio de e-mail ainda não está configurado. Atualize CONTACT_EMAIL em config/config.php e confirme que o servidor permite envio de e-mails.';
            }
        }
    }
}
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container"><span class="eyebrow">Contato</span><h1>Conte um pouco sobre o que você precisa.</h1><p>Uma boa conversa ajuda a entender o contexto, os objetivos e o caminho mais adequado para sua solução.</p></div></section>
<section class="section-pad"><div class="container"><div class="row g-5"><div class="col-lg-5"><span class="section-kicker">Vamos começar</span><h2 class="section-title">Qual problema você quer resolver?</h2><p class="section-intro">Compartilhe o contexto, os principais desafios e o resultado que gostaria de alcançar. Não precisa ter todos os requisitos definidos ainda.</p><div class="mt-4"><p><i class="bi bi-envelope me-2"></i><?= htmlspecialchars(CONTACT_EMAIL, ENT_QUOTES, 'UTF-8') ?></p><p class="small-note">Substitua o e-mail de exemplo nas configurações antes de publicar.</p></div></div>
<div class="col-lg-7"><div class="contact-panel">
<?php if ($success): ?><div class="alert alert-success" role="status"><strong>Obrigado pela mensagem!</strong> Se o envio de e-mail estiver configurado, ela foi encaminhada.</div><?php endif; ?>
<?php if ($errors): ?><div class="alert alert-danger" role="alert"><strong>Verifique os dados:</strong><ul class="mb-0 mt-2"><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" action="contato.php" data-contact-form>
  <div class="visually-hidden" aria-hidden="true"><label for="website">Não preencha este campo</label><input type="text" name="website" id="website" tabindex="-1" autocomplete="off"></div>
  <div class="mb-3"><label class="form-label" for="name">Nome</label><input class="form-control" id="name" name="name" autocomplete="name" maxlength="100" required value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"></div>
  <div class="mb-3"><label class="form-label" for="email">E-mail</label><input class="form-control" type="email" id="email" name="email" autocomplete="email" maxlength="180" required value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"></div>
  <div class="mb-3"><label class="form-label" for="subject">Assunto</label><select class="form-select" id="subject" name="subject" required><option value="">Selecione uma opção</option><?php foreach (['Sistema sob medida','Integração ou automação','Dados e indicadores','Produto digital','Inteligência artificial','Outro assunto'] as $option): ?><option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>" <?= $subject === $option ? 'selected' : '' ?>><?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
  <div class="mb-4"><label class="form-label" for="message">Conte sobre sua necessidade</label><textarea class="form-control" id="message" name="message" rows="5" maxlength="5000" minlength="10" required><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></textarea><div class="form-text">Não inclua senhas, dados pessoais sensíveis ou informações confidenciais.</div></div>
  <button class="btn btn-accent px-4 py-3" type="submit">Enviar mensagem <i class="bi bi-arrow-right ms-2"></i></button>
</form></div></div></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
