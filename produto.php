<?php
require __DIR__ . '/config/config.php';
require __DIR__ . '/config/store.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$csrfToken = store_csrf();

$slug = trim((string)($_GET['slug'] ?? ''));
$q = store_db()->prepare('SELECT * FROM products WHERE slug = ? AND is_published = 1 LIMIT 1');
$q->execute([$slug]);
$product = $q->fetch();

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Produto não encontrado';
    $pageDescription = 'Produto não encontrado.';
} else {
    $pageTitle = (string)$product['name'];
    $pageDescription = (string)$product['short_description'];
}

// Converte links comuns do YouTube em URL de incorporação.
function store_youtube_embed(string $url): ?string {
    $parts = parse_url(trim($url));
    if (!$parts || empty($parts['host'])) return null;
    $host = strtolower(preg_replace('/^www\./', '', $parts['host']));
    $videoId = '';

    if ($host === 'youtu.be') {
        $videoId = trim(explode('/', trim($parts['path'] ?? '', '/'))[0] ?? '');
    } elseif (in_array($host, ['youtube.com', 'm.youtube.com', 'youtube-nocookie.com'], true)) {
        $path = trim($parts['path'] ?? '', '/');
        if ($path === 'watch') {
            parse_str($parts['query'] ?? '', $query);
            $videoId = (string)($query['v'] ?? '');
        } elseif (preg_match('~^(embed|shorts)/([^/]+)~', $path, $m)) {
            $videoId = $m[2];
        }
    }

    if (!preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId)) return null;
    return 'https://www.youtube-nocookie.com/embed/' . $videoId;
}

$demoUrl = (string)($product['demo_url'] ?? '');
$youtubeEmbed = $demoUrl !== '' ? store_youtube_embed($demoUrl) : null;
$coverImage = trim((string)($product['cover_image'] ?? ''));
$coverIsRemote = preg_match('~^https?://~i', $coverImage) === 1;
$coverSrc = $coverImage === '' ? '' : ($coverIsRemote ? $coverImage : ltrim($coverImage, '/'));

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Produto digital</span>
        <h1><?= $product ? store_h((string)$product['name']) : 'Produto não encontrado' ?></h1>
        <?php if ($product): ?><p><?= store_h((string)$product['short_description']) ?></p><?php endif; ?>
    </div>
</section>

<section class="section-pad">
<div class="container" style="max-width:1100px">
<?php if (!$product): ?>
    <div class="empty-state"><p>Este produto não está disponível.</p><a href="loja.php" class="btn btn-dark">Voltar à loja</a></div>
<?php else: ?>
    <?php if ($coverSrc !== ''): ?>
        <div class="mb-5" style="overflow:hidden;border-radius:22px;background:#eef3ef;box-shadow:0 12px 35px rgba(0,0,0,.08)">
            <img src="<?= store_h($coverSrc) ?>" alt="Capa do <?= store_h((string)$product['name']) ?>" style="display:block;width:100%;max-height:470px;object-fit:cover" loading="eager">
        </div>
    <?php endif; ?>

    <div class="row g-5 align-items-start">
        <div class="col-lg-7">
            <span class="section-kicker"><?= store_h((string)($product['category'] ?? 'Software')) ?></span>
            <h2 class="h3 mt-2">Sobre o produto</h2>
            <div class="content-prose"><?= nl2br(store_h((string)$product['description'])) ?></div>
            <p class="small text-secondary mt-4">Versão: <?= store_h((string)($product['version'] ?? '')) ?> · Licença: <?= store_h(['perpetual'=>'
            
            ','annual'=>'Anual','monthly'=>'Mensal','none'=>'Sem licença'][$product['license_type']] ?? 'Não informada') ?></p>
        </div>

        <div class="col-lg-5">
           
                <div class="contact-panel">
                    <div class="text-secondary small">Valor do produto</div>

                    <div class="display-6 fw-bold my-2">
                        R$ <?= number_format((float)$product['price'], 2, ',', '.') ?>
                    </div>

                    <p class="small text-secondary">
                        Pagamento seguro pelo Mercado Pago. Pague com Pix ou cartão.
                    </p>

                    <form action="pagamentos/criar.php" method="POST">
                        <input
                            type="hidden"
                            name="_csrf"
                            value="<?= store_h($csrfToken) ?>"
                        >

                        <input
                            type="hidden"
                            name="slug"
                            value="<?= store_h((string)$product['slug']) ?>"
                        >

                        <div class="mb-3">
                            <label for="customer_name" class="form-label">Seu nome</label>
                            <input
                                type="text"
                                class="form-control"
                                id="customer_name"
                                name="customer_name"
                                maxlength="150"
                                required
                                autocomplete="name"
                                placeholder="Digite seu nome"
                            >
                        </div>

                        <div class="mb-3">
                            <label for="customer_email" class="form-label">Seu e-mail</label>
                            <input
                                type="email"
                                class="form-control"
                                id="customer_email"
                                name="customer_email"
                                maxlength="190"
                                required
                                autocomplete="email"
                                placeholder="seu@email.com"
                            >
                        </div>

                        <div class="mb-3">
                            <label for="customer_email_confirm" class="form-label">Repita seu e-mail</label>
                            <input
                                type="email"
                                class="form-control"
                                id="customer_email_confirm"
                                name="customer_email_confirm"
                                maxlength="190"
                                required
                                autocomplete="off"
                                placeholder="Digite o e-mail novamente"
                            >
                        </div>

                        <div class="mb-3">
                            <label for="customer_phone" class="form-label">
                                WhatsApp <span class="text-secondary">(opcional)</span>
                            </label>
                            <input
                                type="tel"
                                class="form-control"
                                id="customer_phone"
                                name="customer_phone"
                                maxlength="30"
                                autocomplete="tel"
                                placeholder="(41) 99999-9999"
                            >
                        </div>

                        <button type="submit" class="btn btn-dark w-100 py-3">
                            Comprar agora
                        </button>

                        <div class="small text-secondary text-center mt-3">
                            <i class="bi bi-shield-check"></i>
                            Pagamento processado em ambiente seguro.
                        </div>
                    </form>

                    <a href="loja.php" class="btn btn-outline-dark w-100 mt-3">
                        Voltar ao catálogo
                    </a>
                </div>

        </div>
    </div>

    <?php if ($demoUrl !== ''): ?>
        <section class="mt-5 pt-4 border-top">
            <div class="mb-3">
                <span class="section-kicker">Conheça na prática</span>
                <h2 class="h3 mt-2">Veja uma demonstração do <?= store_h((string)$product['name']) ?></h2>
                <p class="text-secondary">Explore a interface e conheça os principais recursos do produto.</p>
            </div>
            <?php if ($youtubeEmbed): ?>
                <div class="ratio ratio-16x9 rounded-4 overflow-hidden shadow-sm">
                    <iframe src="<?= store_h($youtubeEmbed) ?>" title="Demonstração do <?= store_h((string)$product['name']) ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                </div>
            <?php else: ?>
                <div class="contact-panel d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div><strong>Demonstração online disponível</strong><p class="small text-secondary mb-0">Abra o ambiente demonstrativo em uma nova aba.</p></div>
                    <a class="btn btn-dark px-4" href="<?= store_h($demoUrl) ?>" target="_blank" rel="noopener noreferrer">Acessar demonstração ↗</a>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
<?php endif; ?>
</div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
