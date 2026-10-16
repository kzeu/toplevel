<?php
require __DIR__ . '/config/config.php';
$pageTitle = 'Software desenvolvido com método';
$pageDescription = 'Desenvolvimento de sistemas sob medida, produtos digitais e soluções construídas com boas práticas de engenharia de software.';
require __DIR__ . '/includes/header.php';
?>

<section class="hero py-5">
  <div class="container py-lg-5 py-3">
    <div class="row align-items-end g-5">
      <div class="col-lg-8">
        <span class="eyebrow">Engenharia de software · Soluções digitais</span>
        <h1>Ideias bem definidas.<br><span>Software bem construído.</span></h1>
        <p class="hero-lead">Criamos software sob demanda para resolver desafios reais. Unimos análise de requisitos, engenharia de software e automação — incluindo IA quando agrega valor — para desenvolver soluções alinhadas à rotina do seu negócio.</p>
        <div class="hero-actions">
          <a class="btn btn-accent px-4 py-3" href="projetos.php">Conheça os projetos <i class="bi bi-arrow-up-right ms-2"></i></a>
          <a class="btn btn-outline-light px-4 py-3" href="contato.php">Fale sobre sua ideia</a>
          <a class="text-link hero-products-link" href="produtos.php">Explore nossos produtos <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="hero-aside">
          <div class="mb-4"><span class="number">01 / ANÁLISE</span><h2>Entender o problema</h2><p>Requisitos claros e objetivos definidos antes de começar.</p></div>
          <div class="mb-4"><span class="number">02 / PROJETO</span><h2>Planejar a solução</h2><p>Arquitetura e decisões técnicas alinhadas ao contexto.</p></div>
          <div><span class="number">03 / EVOLUÇÃO</span><h2>Construir e aprimorar</h2><p>Código organizado, validação e melhoria contínua.</p></div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section-pad-sm">
  <div class="container">
    <div class="custom-software-band reveal">
      <div>
        <span class="section-kicker">Software sob demanda</span>
        <h2>Seu processo é único. Seu sistema também pode ser.</h2>
        <p>Do levantamento dos requisitos à implantação, desenvolvemos soluções que acompanham a realidade da sua operação. Também exploramos IA e automação para aumentar a produtividade sem perder de vista a qualidade e a segurança.</p>
      </div>
      <a class="btn btn-accent px-4 py-3 flex-shrink-0" href="contato.php?assunto=Software%20sob%20demanda">Vamos conversar <i class="bi bi-arrow-up-right ms-2"></i></a>
    </div>
  </div>
</section>

<section class="section-pad">
  <div class="container">
    <div class="row g-4 align-items-end mb-4">
      <div class="col-lg-7"><span class="section-kicker">O que fazemos</span><h2 class="section-title">Tecnologia aplicada a problemas reais.</h2></div>
      <div class="col-lg-5"><p class="section-intro mb-1">Cada solução começa pela compreensão do negócio. A tecnologia vem para simplificar processos, organizar informações e criar novas possibilidades.</p></div>
    </div>
    <div class="row g-3">
      <div class="col-md-6 col-lg-4"><article class="card-clean"><div class="icon-box"><i class="bi bi-window-stack"></i></div><h3>Sistemas sob medida</h3><p>Aplicações web alinhadas aos processos, às regras e à realidade de cada negócio.</p></article></div>
      <div class="col-md-6 col-lg-4"><article class="card-clean"><div class="icon-box"><i class="bi bi-diagram-3"></i></div><h3>Integrações e automações</h3><p>APIs e fluxos de trabalho que conectam sistemas e reduzem tarefas manuais.</p></article></div>
      <div class="col-md-6 col-lg-4"><article class="card-clean"><div class="icon-box"><i class="bi bi-bar-chart-line"></i></div><h3>Dados e indicadores</h3><p>Ferramentas para organizar informações, acompanhar indicadores e apoiar decisões.</p></article></div>
    </div>
    <div class="mt-4"><a class="text-link" href="solucoes.php">Conheça nossa abordagem <i class="bi bi-arrow-right ms-1"></i></a></div>
  </div>
</section>

<section class="dark-band section-pad">
  <div class="container">
    <span class="section-kicker">Nosso processo</span><h2 class="section-title">Boas práticas em cada etapa.</h2><p class="section-intro mb-5">Um processo transparente ajuda a reduzir retrabalho, alinhar expectativas e manter o desenvolvimento orientado ao objetivo.</p>
    <div class="row g-4">
      <div class="col-6 col-lg-3"><div class="step-card"><span class="step-no">ETAPA 01</span><h3>Análise de requisitos</h3><p class="small-note">Mapear necessidades, regras de negócio e critérios de aceitação.</p></div></div>
      <div class="col-6 col-lg-3"><div class="step-card"><span class="step-no">ETAPA 02</span><h3>Planejamento técnico</h3><p class="small-note">Definir arquitetura, dados, integrações e prioridades.</p></div></div>
      <div class="col-6 col-lg-3"><div class="step-card"><span class="step-no">ETAPA 03</span><h3>Desenvolvimento</h3><p class="small-note">Construir com código organizado, versionamento e validações.</p></div></div>
      <div class="col-6 col-lg-3"><div class="step-card"><span class="step-no">ETAPA 04</span><h3>Entrega e evolução</h3><p class="small-note">Validar o resultado, documentar e planejar melhorias.</p></div></div>
    </div>
  </div>
</section>

<section class="section-pad home-featured-projects">
  <div class="container">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
      <div><span class="section-kicker">Trabalho em destaque</span><h2 class="section-title mb-0">Projetos que demonstram a prática.</h2></div>
      <a class="text-link" href="projetos.php">Ver todos os projetos <i class="bi bi-arrow-right ms-1"></i></a>
    </div>

    <div class="row g-4">
      <div class="col-md-6">
        <article class="project-card home-project-card h-100">
          <div class="home-project-image"><img src="assets/projetos/portal-climatico.png" alt="Página inicial do Portal Climático da Guiné-Bissau" loading="lazy"></div>
          <div class="project-content">
            <span class="section-kicker">Portal web · Dados geográficos</span>
            <h3 class="h4 mt-2">Portal Climático da Guiné-Bissau</h3>
            <p>Portal de informações climáticas, indicadores, relatórios e mapas temáticos.</p>
            <div class="mt-auto"><span class="tag">Portal web</span><span class="tag">Mapas</span><span class="tag">GeoJSON</span></div>
          </div>
        </article>
      </div>
      <div class="col-md-6">
        <article class="project-card home-project-card h-100">
          <div class="home-project-image"><img src="assets/projetos/controle-projetos-financeiro.png" alt="Painel administrativo de controle de projetos e informações financeiras" loading="lazy"></div>
          <div class="project-content">
            <span class="section-kicker">Gestão institucional · Financeiro</span>
            <h3 class="h4 mt-2">Controle de Projetos e Financeiro</h3>
            <p>Painel para acompanhar propostas, projetos e informações financeiras de uma instituição.</p>
            <div class="mt-auto"><span class="tag">Gestão</span><span class="tag">Financeiro</span><span class="tag">Indicadores</span></div>
          </div>
        </article>
      </div>
      <div class="col-md-6">
        <article class="project-card home-project-card h-100">
          <div class="home-project-image"><img src="assets/projetos/erpmei-nfse.png" alt="Tela do ERP MEI com informações pessoais e valores parcialmente ocultos" loading="lazy"></div>
          <div class="project-content">
            <span class="section-kicker">ERP · SaaS · Fiscal</span>
            <h3 class="h4 mt-2">ERP MEI com emissão de NFS-e</h3>
            <p>Sistema de gestão para microempreendedores com recursos operacionais e fiscais.</p>
            <div class="mt-auto"><span class="tag">PHP</span><span class="tag">SaaS</span><span class="tag">NFS-e</span></div>
          </div>
        </article>
      </div>
      <div class="col-md-6">
        <article class="project-card home-project-card h-100">
          <div class="home-project-image"><img src="assets/projetos/sop-planejamento.png" alt="Painel S&OP com descrições e códigos de produtos desfocados e números visíveis" loading="lazy"></div>
          <div class="project-content">
            <span class="section-kicker">Planejamento · Estoque · Demanda</span>
            <h3 class="h4 mt-2">Sistema de planejamento S&OP</h3>
            <p>Ferramenta para analisar estoque, demanda, forecast e cenários de planejamento.</p>
            <div class="mt-auto"><span class="tag">S&OP</span><span class="tag">Estoque</span><span class="tag">Forecast</span></div>
          </div>
        </article>
      </div>
    </div>
  </div>
</section>

<section class="section-pad-sm"><div class="container"><div class="cta-band d-lg-flex justify-content-between align-items-center gap-4"><div><span class="eyebrow">Vamos construir</span><h2 class="section-title text-white mt-3 mb-2">Tem um desafio para resolver?</h2><p class="mb-lg-0">Vamos conversar sobre o problema, entender os requisitos e avaliar a melhor solução técnica.</p></div><div class="mt-4 mt-lg-0 flex-shrink-0"><a class="btn btn-accent px-4 py-3" href="contato.php">Conte sua ideia <i class="bi bi-arrow-up-right ms-2"></i></a></div></div></div></section>

<style>
.home-featured-projects .home-project-card { overflow:hidden; display:flex; flex-direction:column; border-top:3px solid #16865b; box-shadow:0 8px 24px rgba(17,26,32,.045); transition:border-color .2s ease, box-shadow .2s ease, transform .2s ease; }\n.home-featured-projects .home-project-card:hover { border-top-color:#25a66f; box-shadow:0 14px 30px rgba(17,26,32,.09); transform:translateY(-3px); }\n.home-featured-projects .project-content .section-kicker { color:#16865b; }\n.home-featured-projects .project-content .tag { border-color:rgba(22,134,91,.22); background:#f1f7f3; }
.home-featured-projects .home-project-image { aspect-ratio:16/9; overflow:hidden; background:#edf2ee; display:flex; align-items:center; justify-content:center; border-bottom:1px solid rgba(22,134,91,.18); }
.home-featured-projects .home-project-image img { width:100%; height:100%; display:block; object-fit:contain; object-position:center; transition:transform .35s ease; }
.home-featured-projects .home-project-card:hover .home-project-image img { transform:scale(1.01); }
.home-featured-projects .project-content { flex:1; display:flex; flex-direction:column; padding:1.5rem; }
@media (prefers-reduced-motion: reduce) { .home-featured-projects .home-project-image img { transition:none; } }
</style>

<?php require __DIR__ . '/includes/footer.php'; ?>
