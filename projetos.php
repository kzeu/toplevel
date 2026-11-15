<?php
require __DIR__ . '/config/config.php';
$pageTitle = 'Projetos';
$pageDescription = 'Conheça projetos de software, plataformas de gestão e soluções de planejamento desenvolvidas pela ToplevelSistemas.';
require __DIR__ . '/includes/header.php';

$projects = [
    [
        'category' => 'Portal web · Clima · Geodados',
        'title' => 'Portal Climático da Guiné-Bissau',
        'description' => 'Portal de acesso a informações climáticas, indicadores, relatórios e mapas temáticos para apoiar a adaptação e a mitigação climática.',
        'image' => 'assets/projetos/portal-climatico.png',
        'alt' => 'Página inicial do Portal Climático da Guiné-Bissau',
        'tags' => ['Portal web', 'Mapas interativos', 'GeoJSON'],
        'kind' => 'Projeto institucional'
    ],
    [
        'category' => 'Gestão institucional · Projetos · Financeiro',
        'title' => 'Controle de Projetos e Financeiro',
        'description' => 'Painel de acompanhamento de propostas, projetos, tranches e informações financeiras para apoiar a organização e a gestão institucional.',
        'image' => 'assets/projetos/controle-projetos-financeiro.png',
        'alt' => 'Painel administrativo de controle de projetos e informações financeiras',
        'tags' => ['Gestão de projetos', 'Financeiro', 'Indicadores'],
        'kind' => 'Sistema de gestão'
    ],
    [
        'category' => 'ERP · SaaS · Fiscal',
        'title' => 'ERP MEI com emissão de NFS-e',
        'description' => 'Sistema de gestão para MEI com módulos operacionais e recursos fiscais, incluindo acompanhamento de serviços e emissão de nota fiscal de serviço eletrônica.',
        'image' => 'assets/projetos/erpmei-nfse.png',
        'alt' => 'Tela do ERP MEI com dados do cliente e valores parcialmente ocultos',
        'tags' => ['PHP', 'Gestão empresarial', 'NFS-e'],
        'kind' => 'Produto em evolução'
    ],
    [
        'category' => 'S&OP · Estoque · Demanda',
        'title' => 'Sistema de planejamento S&OP',
        'description' => 'Ferramenta de planejamento que reúne informações de estoque, demanda, forecast e cenários para apoiar decisões operacionais e financeiras.',
        'image' => 'assets/projetos/sop-planejamento.png',
        'alt' => 'Painel S&OP com descrições e códigos de produtos suavemente desfocados e indicadores numéricos visíveis',
        'tags' => ['Planejamento', 'Estoque e demanda', 'Análise de cenários'],
        'kind' => 'Sistema de planejamento'
    ],
];
?>

<section class="page-hero projects-hero">
    <div class="container">
        <span class="eyebrow">Projetos selecionados</span>
        <h1>Software construído para desafios reais.</h1>
        <p>Uma seleção de plataformas, sistemas de gestão e ferramentas de planejamento. Cada projeto representa uma necessidade de negócio traduzida em recursos digitais.</p>
    </div>
</section>

<section class="section-pad projects-section">
    <div class="container">
        <div class="projects-intro">
            <span class="section-kicker">Em destaque</span>
            <h2 class="section-title">Projetos e soluções</h2>
            <p class="section-intro">Da gestão institucional ao planejamento de operações, desenvolvemos software para resolver desafios reais e transformar necessidades em soluções funcionais.</p>
        </div>

        <div class="row g-4 projects-grid">
            <?php foreach ($projects as $project): ?>
                <div class="col-md-6">
                    <article class="project-card project-card-featured h-100">
                        <div class="project-image-wrap">
                            <img
                                class="project-image"
                                src="<?= htmlspecialchars($project['image'], ENT_QUOTES, 'UTF-8') ?>"
                                alt="<?= htmlspecialchars($project['alt'], ENT_QUOTES, 'UTF-8') ?>"
                                loading="lazy"
                            >
                            <span class="project-kind"><?= htmlspecialchars($project['kind'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="project-content">
                            <span class="section-kicker"><?= htmlspecialchars($project['category'], ENT_QUOTES, 'UTF-8') ?></span>
                            <h2 class="h3 mt-2"><?= htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                            <p><?= htmlspecialchars($project['description'], ENT_QUOTES, 'UTF-8') ?></p>
                            <div class="project-tags">
                                <?php foreach ($project['tags'] as $tag): ?>
                                    <span class="tag"><?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="projects-note">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            <p>As demonstrações públicas e os detalhes adicionais de cada projeto poderão ser incluídos posteriormente, após a revisão das informações e dos links autorizados.</p>
        </div>

        <div class="projects-cta">
            <div>
                <span class="section-kicker">Tem um desafio parecido?</span>
                <h2 class="h2">Vamos planejar uma solução para o seu negócio.</h2>
                <p class="mb-0">Desenvolvimento sob demanda, com requisitos claros e tecnologia adequada ao contexto.</p>
            </div>
            <a class="btn btn-accent px-4 py-3" href="contato.php?assunto=Projeto%20sob%20demanda">
                Fale sobre seu projeto <i class="bi bi-arrow-up-right ms-2" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</section>

<style>
.projects-hero p { max-width: 780px; }
.projects-intro { display:block; max-width:780px; margin-bottom:2.25rem; }
.projects-intro .section-title { margin: .35rem 0 .75rem; }
.projects-intro .section-intro { max-width:700px; margin:0; line-height:1.8; }

.project-card-featured { display:flex; flex-direction:column; overflow:hidden; height:100%; }
.project-image-wrap { position:relative; aspect-ratio:16 / 9; overflow:hidden; background:#edf2ee; display:flex; align-items:center; justify-content:center; border-bottom:1px solid rgba(22,134,91,.18); }
.project-image { display:block; width:100%; height:100%; object-fit:contain; object-position:center; transition:transform .35s ease; }
.project-card-featured { border-top:3px solid #16865b; box-shadow:0 8px 24px rgba(17,26,32,.045); transition:border-color .2s ease, box-shadow .2s ease, transform .2s ease; }\n.project-card-featured:hover { border-top-color:#25a66f; box-shadow:0 14px 30px rgba(17,26,32,.09); transform:translateY(-3px); }\n.project-card-featured:hover .project-image { transform:scale(1.01); }
.project-kind { position:absolute; left:1rem; bottom:1rem; display:inline-block; padding:.45rem .7rem; border-radius:999px; background:rgba(22,134,91,.96); color:#fff; font-size:.78rem; }
.project-card-featured .section-kicker { color:#16865b; }\n.project-card-featured .project-content { display:flex; flex-direction:column; flex:1; padding:clamp(1.25rem,2vw,1.75rem); }
.project-card-featured .project-content > p { flex:1; }
.project-tags { display:flex; flex-wrap:wrap; gap:.45rem; margin:.35rem 0 0; }\n.project-tags .tag { border-color:rgba(22,134,91,.22); background:#f1f7f3; }
.projects-note { display:flex; gap:.75rem; align-items:flex-start; margin-top:1.75rem; padding:1rem 1.1rem; border:1px solid rgba(38,54,64,.12); border-radius:1rem; color:#52625a; background:#f6f8f6; }
.projects-note i { color:#16865b; margin-top:.15rem; }
.projects-note p { margin:0; }
.projects-cta { display:flex; align-items:center; justify-content:space-between; gap:2rem; margin-top:3rem; padding:clamp(1.5rem,4vw,2.5rem); border-radius:1.25rem; background:#111a20; color:#fff; }
.projects-cta .h2 { margin:.75rem 0; }
.projects-cta p { color:#cbd5d9; }
.projects-cta .section-kicker { color:#83c5a0; }
.projects-cta .btn { flex-shrink:0; }
@media (max-width:767.98px) {
    .projects-cta { align-items:flex-start; flex-direction:column; }
    .projects-cta .btn { width:100%; text-align:center; }
}
@media (prefers-reduced-motion: reduce) {
    .project-image { transition:none; }
}
</style>

<?php require __DIR__ . '/includes/footer.php'; ?>
