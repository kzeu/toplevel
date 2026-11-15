<?php
declare(strict_types=1);

require __DIR__ . '/config/config.php';
require __DIR__ . '/config/store.php';

$pageTitle = 'Loja de produtos digitais';
$pageDescription = 'Encontre sistemas, ferramentas e produtos digitais da ToplevelSistemas.';

try {
    $products = store_db()->query(
        "SELECT id,name,slug,category,short_description,price,currency,cover_image,version
         FROM products
         WHERE is_published = 1
         ORDER BY display_order ASC, name ASC"
    )->fetchAll();
    $dbError = false;
} catch (Throwable $e) {
    error_log('Toplevel loja: ' . $e->getMessage());
    $products = [];
    $dbError = true;
}

// Categorias são obtidas dos próprios produtos cadastrados: não exige alteração no banco.
$categories = [];
foreach ($products as $product) {
    $category = trim((string)($product['category'] ?? ''));
    if ($category !== '') {
        $categories[mb_strtolower($category, 'UTF-8')] = $category;
    }
}
$categories = array_values($categories);
natcasesort($categories);

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Loja digital Toplevel</span>
        <h1>Software para simplificar o trabalho e impulsionar negócios.</h1>
        <p>Encontre sistemas, ferramentas e materiais digitais para facilitar sua rotina e desenvolver seu negócio.</p>
    </div>
</section>

<section class="section-pad">
    <div class="container">
        <div class="mb-4">
            <span class="section-kicker">Catálogo digital</span>
            <h2 class="section-title">Encontre o que você precisa</h2>
            <p class="text-secondary mb-0">Pesquise pelo nome ou filtre por categoria.</p>
        </div>

        <?php if ($dbError): ?>
            <div class="alert alert-info">Não foi possível carregar o catálogo neste momento.</div>
        <?php elseif (!$products): ?>
            <div class="empty-state">
                <h3 class="h5">Estamos preparando os primeiros produtos.</h3>
                <p class="mb-0 text-secondary">Em breve você encontrará aqui as soluções digitais da ToplevelSistemas.</p>
            </div>
        <?php else: ?>
            <div class="store-toolbar mb-4">
                <div class="store-search-wrap">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input
                        type="search"
                        class="form-control store-search"
                        id="storeSearch"
                        placeholder="Buscar produtos, sistemas, dashboards..."
                        aria-label="Buscar produtos na loja"
                        autocomplete="off"
                    >
                    <button type="button" class="store-search-clear" id="storeSearchClear" aria-label="Limpar busca" hidden>
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="store-category-area mt-3">
                    <span class="store-filter-label">Categorias</span>
                    <div class="store-category-tags" id="storeCategoryTags" aria-label="Filtrar produtos por categoria">
                        <button type="button" class="store-category-tag is-active" data-category="all" aria-pressed="true">Todos</button>
                        <?php foreach ($categories as $category): ?>
                            <button
                                type="button"
                                class="store-category-tag"
                                data-category="<?= store_h($category) ?>"
                                aria-pressed="false"
                            ><?= store_h($category) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="store-results-line" aria-live="polite">
                    <span id="storeResultsCount"><?= count($products) ?> produto(s)</span>
                </div>
            </div>

            <div class="row g-4" id="storeProductGrid">
                <?php foreach ($products as $p): ?>
                    <?php
                    $category = trim((string)($p['category'] ?? ''));
                    $searchText = implode(' ', [
                        (string)$p['name'],
                        $category,
                        (string)$p['short_description'],
                    ]);
                    ?>
                    <div
                        class="col-md-6 col-xl-4 store-product-item"
                        data-category="<?= store_h($category) ?>"
                        data-search="<?= store_h($searchText) ?>"
                    >
                        <article class="product-card h-100 d-flex flex-column">
                            <?php if (!empty($p['cover_image'])): ?>
                                <img src="<?= store_h((string)$p['cover_image']) ?>" alt="<?= store_h((string)$p['name']) ?>" class="w-100 rounded-3 mb-3" style="height:190px;object-fit:cover" loading="lazy">
                            <?php else: ?>
                                <div class="project-visual rounded-3 mb-3" style="min-height:150px"><i class="bi bi-grid-1x2"></i></div>
                            <?php endif; ?>
                            <?php if ($category !== ''): ?><span class="section-kicker"><?= store_h($category) ?></span><?php endif; ?>
                            <h3><?= store_h((string)$p['name']) ?></h3>
                            <p><?= store_h((string)$p['short_description']) ?></p>
                            <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                                <strong class="fs-5">R$ <?= number_format((float)$p['price'], 2, ',', '.') ?></strong>
                                <span class="small text-secondary">Versão <?= store_h((string)($p['version'] ?? '-')) ?></span>
                            </div>
                            <a class="btn btn-dark w-100 mt-3" href="produto.php?slug=<?= rawurlencode((string)$p['slug']) ?>">Ver produto <i class="bi bi-arrow-right ms-1"></i></a>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="empty-state mt-4" id="storeNoResults" hidden>
                <i class="bi bi-search fs-2 mb-2" aria-hidden="true"></i>
                <h3 class="h5">Nenhum produto encontrado</h3>
                <p class="mb-3 text-secondary">Tente outro termo ou selecione uma categoria diferente.</p>
                <button type="button" class="btn btn-outline-dark" id="storeResetFilters">Limpar filtros</button>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.store-toolbar{padding:1.25rem;border:1px solid #e5ebe7;border-radius:18px;background:#fff;box-shadow:0 8px 28px rgba(16,24,32,.045)}
.store-search-wrap{position:relative;display:flex;align-items:center}
.store-search-wrap>.bi-search{position:absolute;left:1rem;color:#718078;z-index:1}
.store-search{min-height:52px;padding: .75rem 3rem .75rem 2.8rem;border-radius:12px;border-color:#dce5df}
.store-search:focus{border-color:#83c5a0;box-shadow:0 0 0 .2rem rgba(131,197,160,.18)}
.store-search-clear{position:absolute;right:.65rem;width:34px;height:34px;border:0;border-radius:8px;background:#eef3ef;color:#42564a}
.store-filter-label{display:block;margin-bottom:.65rem;color:#53645a;font-size:.82rem;font-weight:700}
.store-category-tags{display:flex;flex-wrap:wrap;gap:.55rem}
.store-category-tag{border:1px solid #dbe5de;border-radius:999px;padding:.48rem .95rem;background:#fff;color:#34443a;font-size:.9rem;font-weight:650;transition:all .18s ease}
.store-category-tag:hover{border-color:#83c5a0;background:#f3f8f4;color:#1e4932}
.store-category-tag.is-active{border-color:#173d2b;background:#173d2b;color:#fff}
.store-results-line{margin-top:1rem;color:#718078;font-size:.85rem}
.store-product-item[hidden]{display:none!important}
@media(max-width:575.98px){.store-toolbar{padding:1rem}.store-category-tags{gap:.45rem}.store-category-tag{padding:.42rem .78rem;font-size:.84rem}}
</style>

<script>
(() => {
    const searchInput = document.getElementById('storeSearch');
    const clearButton = document.getElementById('storeSearchClear');
    const tagContainer = document.getElementById('storeCategoryTags');
    const cards = Array.from(document.querySelectorAll('.store-product-item'));
    const countLabel = document.getElementById('storeResultsCount');
    const noResults = document.getElementById('storeNoResults');
    const resetButton = document.getElementById('storeResetFilters');
    if (!searchInput || !tagContainer || !cards.length) return;

    let selectedCategory = 'all';
    const normalize = value => (value || '').toString().toLocaleLowerCase('pt-BR')
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();

    function applyFilters() {
        const term = normalize(searchInput.value);
        let visible = 0;
        cards.forEach(card => {
            const category = normalize(card.dataset.category);
            const searchable = normalize(card.dataset.search);
            const matchesCategory = selectedCategory === 'all' || category === normalize(selectedCategory);
            const matchesSearch = term === '' || searchable.includes(term);
            const show = matchesCategory && matchesSearch;
            card.hidden = !show;
            if (show) visible++;
        });
        countLabel.textContent = `${visible} ${visible === 1 ? 'produto' : 'produtos'}`;
        noResults.hidden = visible !== 0;
        clearButton.hidden = searchInput.value.length === 0;
    }

    tagContainer.addEventListener('click', event => {
        const button = event.target.closest('[data-category]');
        if (!button) return;
        selectedCategory = button.dataset.category || 'all';
        tagContainer.querySelectorAll('[data-category]').forEach(tag => {
            const active = tag === button;
            tag.classList.toggle('is-active', active);
            tag.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        applyFilters();
    });

    searchInput.addEventListener('input', applyFilters);
    clearButton.addEventListener('click', () => {
        searchInput.value = '';
        searchInput.focus();
        applyFilters();
    });
    resetButton?.addEventListener('click', () => {
        searchInput.value = '';
        selectedCategory = 'all';
        const allButton = tagContainer.querySelector('[data-category="all"]');
        allButton?.click();
        applyFilters();
    });
    applyFilters();
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
