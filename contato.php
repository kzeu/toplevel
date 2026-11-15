<?php
declare(strict_types=1);

require __DIR__ . '/config/config.php';

$pageTitle = 'Contato';
$pageDescription = 'Entre em contato para conversar sobre sistemas, integrações, automações ou produtos digitais.';

$errors = [];
$success = false;
$name = '';
$email = '';
$subject = trim((string)($_GET['assunto'] ?? ''));
$message = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $subject = trim((string)($_POST['subject'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));
    $honeypot = trim((string)($_POST['website'] ?? ''));

    // Campo oculto anti-spam: não processa mensagens preenchidas por robôs.
    if ($honeypot !== '') {
        $success = true;
    } else {
        if ($name === '' || mb_strlen($name) > 100) {
            $errors[] = 'Informe seu nome (até 100 caracteres).';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 180) {
            $errors[] = 'Informe um e-mail válido.';
        }

        if ($subject === '' || mb_strlen($subject) > 120) {
            $errors[] = 'Informe o assunto (até 120 caracteres).';
        }

        if ($message === '' || mb_strlen($message) < 10 || mb_strlen($message) > 5000) {
            $errors[] = 'A mensagem deve ter entre 10 e 5.000 caracteres.';
        }

        if (!$errors) {
            try {
                $autoload = __DIR__ . '/vendor/autoload.php';
                $configPath = __DIR__ . '/config/email.php';

                if (!is_file($autoload)) {
                    throw new RuntimeException('Autoload do Composer não encontrado.');
                }

                if (!is_file($configPath)) {
                    throw new RuntimeException('Arquivo config/email.php não encontrado.');
                }

                require_once $autoload;

                $mailConfig = require $configPath;

                if (
                    !is_array($mailConfig) ||
                    empty($mailConfig['host']) ||
                    empty($mailConfig['username']) ||
                    empty($mailConfig['password']) ||
                    empty($mailConfig['from_email'])
                ) {
                    throw new RuntimeException('Configuração SMTP incompleta.');
                }

                $safeName = str_replace(["\r", "\n"], ' ', $name);
                $safeSubject = str_replace(["\r", "\n"], ' ', $subject);

                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = (string)$mailConfig['host'];
                $mail->SMTPAuth = true;
                $mail->Username = (string)$mailConfig['username'];
                $mail->Password = (string)$mailConfig['password'];
                $mail->Port = (int)($mailConfig['port'] ?? 587);

                $encryption = strtolower((string)($mailConfig['encryption'] ?? 'tls'));
                if ($encryption === 'ssl') {
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                } else {
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                }

                $mail->CharSet = 'UTF-8';
                $mail->setFrom(
                    (string)$mailConfig['from_email'],
                    (string)($mailConfig['from_name'] ?? SITE_NAME)
                );
                $mail->addAddress(CONTACT_EMAIL);
                $mail->addReplyTo($email, $safeName);

                $mail->isHTML(false);
                $mail->Subject = 'Contato pelo site: ' . $safeSubject;
                $mail->Body =
                    "Nome: {$safeName}\n" .
                    "E-mail: {$email}\n" .
                    "Assunto: {$safeSubject}\n\n" .
                    "Mensagem:\n{$message}\n";
                $mail->AltBody = $mail->Body;

                $mail->send();

                $success = true;
                $name = '';
                $email = '';
                $subject = '';
                $message = '';
            } catch (Throwable $e) {
                error_log('[Toplevel Contato] Falha no envio: ' . $e->getMessage());
                $errors[] = 'Não foi possível enviar sua mensagem agora. Tente novamente mais tarde.';
            }
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Contato</span>
        <h1>Conte um pouco sobre o que você precisa.</h1>
        <p>Uma boa conversa ajuda a entender o contexto, os objetivos e o caminho mais adequado para sua solução.</p>
    </div>
</section>

<section class="section-pad">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-5">
                <span class="section-kicker">Vamos começar</span>
                <h2 class="section-title">Qual problema você quer resolver?</h2>
                <p class="section-intro">
                    Compartilhe o contexto, os principais desafios e o resultado que gostaria de alcançar.
                    Não precisa ter todos os requisitos definidos ainda.
                </p>

                <div class="mt-4">
                    <p>
                        <i class="bi bi-envelope me-2"></i>
                        <?= htmlspecialchars(CONTACT_EMAIL, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="contact-panel">
                    <?php if ($success): ?>
                        <div class="alert alert-success" role="status">
                            <strong>Obrigado pela mensagem!</strong>
                            Sua mensagem foi enviada com sucesso. Em breve entraremos em contato.
                        </div>
                    <?php endif; ?>

                    <?php if ($errors): ?>
                        <div class="alert alert-danger" role="alert">
                            <strong>Verifique os dados:</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="contato.php" data-contact-form>
                        <div class="visually-hidden" aria-hidden="true">
                            <label for="website">Não preencha este campo</label>
                            <input
                                type="text"
                                name="website"
                                id="website"
                                tabindex="-1"
                                autocomplete="off"
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="name">Nome</label>
                            <input
                                class="form-control"
                                id="name"
                                name="name"
                                autocomplete="name"
                                maxlength="100"
                                required
                                value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="email">E-mail</label>
                            <input
                                class="form-control"
                                type="email"
                                id="email"
                                name="email"
                                autocomplete="email"
                                maxlength="180"
                                required
                                value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="subject">Assunto</label>
                            <select class="form-select" id="subject" name="subject" required>
                                <option value="">Selecione uma opção</option>
                                <?php foreach ([
                                    'Sistema sob medida',
                                    'Integração ou automação',
                                    'Dados e indicadores',
                                    'Produto digital',
                                    'Inteligência artificial',
                                    'Outro assunto'
                                ] as $option): ?>
                                    <option
                                        value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $subject === $option ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="message">Conte sobre sua necessidade</label>
                            <textarea
                                class="form-control"
                                id="message"
                                name="message"
                                rows="5"
                                maxlength="5000"
                                minlength="10"
                                required
                            ><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></textarea>
                            <div class="form-text">
                                Não inclua senhas, dados pessoais sensíveis ou informações confidenciais.
                            </div>
                        </div>

                        <button class="btn btn-accent px-4 py-3" type="submit">
                            Enviar mensagem <i class="bi bi-arrow-right ms-2"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
