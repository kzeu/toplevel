<?php
require __DIR__ . '/config/config.php';
$pageTitle = 'Produtos digitais';
$pageDescription = 'Conheça produtos digitais e soluções que podem ser desenvolvidas pela ToplevelSistemas.';
require __DIR__ . '/includes/header.php';

// Cadastre novos produtos copiando um item deste array. Use '' quando ainda não houver demonstração.
$products = [
    [
        'name' => 'Gestão de processos',
        'category' => 'Gestão empresarial',
        'description' => 'Uma aplicação para organizar etapas, responsáveis, prazos e o acompanhamento das atividades do negócio.',
        'icon' => 'bi-kanban',
        'status' => 'Sob consulta',
        'demo_url' => '',
        'details_url' => 'contato.php?assunto=Gest%C3%A3o%20de%20processos',
    ],
    [
        'name' => 'Painel de indicadores',
        'category' => 'Dados e BI',
        'description' => 'Consolidação de informações e indicadores em uma visão prática para apoiar decisões e acompanhar resultados.',
        'icon' => 'bi-bar-chart-line',
        'status' => 'Sob consulta',
        'demo_url' => '',
        'details_url' => 'contato.php?assunto=Painel%20de%20indicadores',
    ],
    [
        'name' => 'Automação inteligente',
        'category' => 'Automação e IA',
        'description' => 'Automatização de tarefas repetitivas e integração de ferramentas, com possibilidade de incorporar IA conforme o caso de uso.',
        'icon' => 'bi-cpu',
        'status' => 'Projeto personalizado',
        'demo_url' => '',
        'details_url' => 'contato.php?assunto=Automa%C3%A7%C3%A3o%20inteligente',
    ],
    [
        'name' => 'Sistemas de Supply Chain',
        'category' => 'Supply Chain e planejamento',
        'description' => 'Soluções para planejamento de demanda, gestão de estoques, abastecimento e processos integrados da cadeia de suprimentos, desenvolvidas com participação de profissionais especialistas na área.',
        'icon' => 'bi-boxes',
        'status' => 'Sob consulta',
        'demo_url' => '',
        'details_url' => 'contato.php?assunto=Sistemas%20de%20Supply%20Chain',
    ],

    [
    'name' => 'Agentes de IA com LLM, RAG e MCP',
    'category' => 'Inteligência artificial aplicada',
    'description' => 'Agentes inteligentes que utilizam modelos de linguagem (LLMs), recuperação de conhecimento com RAG e integração com ferramentas e sistemas via MCP. Soluções pensadas para automatizar processos, consultar informações contextualizadas e apoiar decisões com supervisão e controle.',
    'icon' => 'bi-robot',
    'status' => 'Em breve',
    'demo_url' => '',
    'details_url' => 'contato.php?assunto=Agentes%20de%20IA%20LLM%20RAG%20MCP',
],
];
?>
<section class="page-hero"><div class="container"><span class="eyebrow">Produtos e soluções</span><h1>Ferramentas pensadas para resolver problemas reais.</h1><p>Conheça ideias de produtos que podem ser adaptados ao contexto do seu negócio. Quando houver uma demonstração disponível, você poderá testá-la diretamente por aqui.</p></div></section>
<section class="section-pad"><div class="container">
  <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-5"><div><span class="section-kicker">Catálogo de soluções</span><h2 class="section-title mb-2">Produtos que podem evoluir com o seu negócio.</h2><p class="section-intro mb-0">Cada item pode ser validado, personalizado e desenvolvido de acordo com os requisitos definidos em conjunto.</p></div><a class="btn btn-accent px-4 py-3 flex-shrink-0" href="contato.php?assunto=Novo%20produto">Propor uma solução <i class="bi bi-arrow-up-right ms-2"></i></a></div>
  <div class="row g-4">
  <?php foreach ($products as $product): ?>
    <div class="col-md-6 col-xl-4 reveal"><article class="product-card h-100"><div class="product-card-top"><div class="icon-box"><i class="bi <?= htmlspecialchars($product['icon'], ENT_QUOTES, 'UTF-8') ?>"></i></div><span class="product-status"><?= htmlspecialchars($product['status'], ENT_QUOTES, 'UTF-8') ?></span></div><span class="section-kicker"><?= htmlspecialchars($product['category'], ENT_QUOTES, 'UTF-8') ?></span><h3><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h3><p><?= htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8') ?></p><div class="product-actions mt-auto"><?php if (!empty($product['demo_url'])): ?><a class="btn btn-dark-soft" href="<?= htmlspecialchars($product['demo_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Ver demonstração <i class="bi bi-box-arrow-up-right ms-1"></i></a><?php else: ?><span class="small-note">
    <?= $product['status'] === 'Em breve'
        ? 'Novidades em breve'
        : 'Disponível sob consulta' ?>
</span><?php endif; ?><a class="text-link" href="<?= htmlspecialchars($product['details_url'], ENT_QUOTES, 'UTF-8') ?>">Tenho interesse <i class="bi bi-arrow-right ms-1"></i></a></div></article></div>
  <?php endforeach; ?>
  </div>
  <div class="custom-software-band mt-5 reveal"><div><span class="section-kicker">Desenvolvimento sob demanda</span><h2>Não encontrou exatamente o que precisa?</h2><p>Desenvolvemos sistemas personalizados a partir dos seus processos, requisitos e objetivos. A solução é pensada para o seu cenário — não o contrário.</p></div><a class="btn btn-accent px-4 py-3 flex-shrink-0" href="contato.php?assunto=Software%20sob%20demanda">Descreva seu projeto <i class="bi bi-arrow-up-right ms-2"></i></a></div>
</div></section>
<section class="dark-band section-pad-sm"><div class="container"><div class="row g-4 align-items-center"><div class="col-lg-8"><span class="eyebrow">Produtividade com tecnologia</span><h2 class="section-title">IA como ferramenta. Conhecimento do negócio como direção.</h2><p class="section-intro mb-0">Aplicamos automação e recursos de inteligência artificial quando fazem sentido para o problema, buscando reduzir tarefas repetitivas, acelerar etapas de trabalho e apoiar decisões — com validação humana e atenção à segurança dos dados.</p></div><div class="col-lg-4"><div class="ai-note"><i class="bi bi-lightning-charge"></i><strong>Mais tempo para o que importa.</strong><span>Processos mais claros, trabalho bem direcionado e tecnologia aplicada com critério.</span></div></div></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
