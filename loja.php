<?php
require __DIR__ . '/config/config.php';
require __DIR__ . '/config/store.php';
$pageTitle = 'Loja de produtos digitais';
$pageDescription = 'Conheça os produtos digitais da ToplevelSistemas.';
try {
    $products = store_db()->query("SELECT id,name,slug,category,short_description,price,currency,cover_image,version FROM products WHERE is_published = 1 ORDER BY display_order ASC, name ASC")->fetchAll();
    $dbError = false;
} catch (Throwable $e) {
    error_log('Toplevel loja: ' . $e->getMessage());
    $products = [];
    $dbError = true;
}
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container"><span class="eyebrow">Loja digital Toplevel</span><h1>Software para simplificar o trabalho e impulsionar negócios.</h1><p>Conheça nossas ferramentas digitais. Escolha o produto e confira as informações para adquiri-lo.</p></div></section>
<section class="section-pad"><div class="container"><div class="mb-4"><span class="section-kicker">Catálogo</span><h2 class="section-title">Nossos produtos</h2></div>
<?php if ($dbError): ?><div class="alert alert-info">Não foi possível carregar o catálogo neste momento.</div>
<?php elseif (!$products): ?><div class="empty-state"><h3 class="h5">Estamos preparando os primeiros produtos.</h3><p class="mb-0 text-secondary">Em breve você encontrará aqui as soluções digitais da ToplevelSistemas.</p></div>
<?php else: ?><div class="row g-4"><?php foreach ($products as $p): ?><div class="col-md-6 col-xl-4"><article class="product-card h-100">
<?php if (!empty($p['cover_image'])): ?><img src="<?= store_h((string)$p['cover_image']) ?>" alt="<?= store_h((string)$p['name']) ?>" class="w-100 rounded-3 mb-3" style="height:190px;object-fit:cover"><?php else: ?><div class="project-visual rounded-3 mb-3" style="min-height:150px"><i class="bi bi-grid-1x2"></i></div><?php endif; ?>
<span class="section-kicker"><?= store_h((string)$p['category']) ?></span><h3><?= store_h((string)$p['name']) ?></h3><p><?= store_h((string)$p['short_description']) ?></p>
<div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center"><strong class="fs-5">R$ <?= number_format((float)$p['price'],2,',','.') ?></strong><span class="small text-secondary">Versão <?= store_h((string)($p['version'] ?? '-')) ?></span></div>
<a class="btn btn-dark w-100 mt-3" href="produto.php?slug=<?= rawurlencode((string)$p['slug']) ?>">Ver produto <i class="bi bi-arrow-right ms-1"></i></a>
</article></div><?php endforeach; ?></div><?php endif; ?></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
