<?php
require __DIR__ . '/config/config.php';

$pageTitle = 'Sobre';
$pageDescription = 'Conheça a ToplevelSistemas, nossa abordagem de desenvolvimento e os princípios que orientam cada projeto.';

require __DIR__ . '/includes/header.php';
?>

<main class="about-page">
    <section class="page-hero about-hero">
        <div class="container">
            <span class="eyebrow">Sobre a ToplevelSistemas</span>
            <h1>Tecnologia com método. Soluções com propósito.</h1>
            <p>
                Criamos software sob demanda a partir de necessidades reais.
                Entendemos o cenário, planejamos a solução e desenvolvemos sistemas
                pensados para serem úteis hoje e evoluírem com o negócio.
            </p>
        </div>
    </section>

    <section class="section-pad about-main">
        <div class="container">
            <div class="row g-4 g-xl-5 align-items-start">
                <div class="col-lg-7 content-prose about-copy">
                    <span class="section-kicker">Nossa abordagem</span>
                    <h2 class="section-title">Boas decisões antes de escrever código.</h2>
                    <p>
                        Um projeto consistente começa pelo entendimento do problema.
                        Por isso, buscamos esclarecer objetivos, regras de negócio,
                        prioridades e critérios de sucesso antes de definir a implementação.
                    </p>
                    <p>
                        A partir daí, escolhemos uma abordagem técnica compatível com
                        o contexto, mantendo atenção à organização do código, à segurança
                        e à facilidade de manutenção.
                    </p>
                    <p>
                        Também vemos a inteligência artificial como uma ferramenta para
                        apoiar a produtividade, acelerar tarefas adequadas e ampliar
                        possibilidades — sempre com revisão humana e responsabilidade
                        sobre o resultado entregue.
                    </p>
                    <a class="btn btn-accent about-inline-cta" href="contato.php">
                        Vamos conversar
                        <i class="bi bi-arrow-up-right ms-2" aria-hidden="true"></i>
                    </a>
                </div>

                <div class="col-lg-5">
                    <div class="about-principles">
                        <article class="card-clean about-principle">
                            <div class="icon-box" aria-hidden="true"><i class="bi bi-search"></i></div>
                            <h3>Entender antes de construir</h3>
                            <p>Levantamos necessidades, regras e prioridades para manter o desenvolvimento ligado ao problema que precisa ser resolvido.</p>
                        </article>
                        <article class="card-clean about-principle">
                            <div class="icon-box" aria-hidden="true"><i class="bi bi-diagram-3"></i></div>
                            <h3>Planejar a solução</h3>
                            <p>Organizamos dados, funcionalidades e integrações de acordo com o contexto, sem adicionar complexidade desnecessária.</p>
                        </article>
                        <article class="card-clean about-principle">
                            <div class="icon-box" aria-hidden="true"><i class="bi bi-tools"></i></div>
                            <h3>Construir para evoluir</h3>
                            <p>Validação, versionamento e documentação proporcional ajudam a manter o sistema compreensível e preparado para melhorias.</p>
                        </article>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="dark-band section-pad-sm about-contact">
        <div class="container">
            <div class="about-contact-inner">
                <div>
                    <span class="section-kicker">Vamos conversar</span>
                    <h2 class="h2 mt-2 mb-2">Sua ideia merece um bom planejamento.</h2>
                    <p class="section-intro mb-0">Conte o que você precisa resolver. Vamos avaliar juntos os próximos passos.</p>
                </div>
                <a class="btn btn-accent px-4 py-3 about-contact-button" href="contato.php">
                    Solicitar uma conversa
                    <i class="bi bi-arrow-up-right ms-2" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </section>
</main>

<style>
/* Estilos restritos à página Sobre para evitar efeitos colaterais no restante do site. */
.about-page { overflow: clip; }
.about-page .about-hero p { max-width: 760px; }
.about-page .about-main { position: relative; isolation: isolate; }
.about-page .about-copy { min-width: 0; }
.about-page .about-copy p { max-width: 680px; }
.about-page .about-inline-cta { margin-top: 1rem; }
.about-page .about-principles { display: grid; gap: 1rem; min-width: 0; }
.about-page .about-principle {
    height: auto; min-height: 0; margin: 0;
    padding: clamp(1.25rem, 2vw, 1.75rem); overflow-wrap: anywhere;
}
.about-page .about-principle h3 { margin: 1rem 0 0.5rem; }
.about-page .about-principle p { margin-bottom: 0; }
.about-page .about-contact { position: relative; z-index: 1; }
.about-page .about-contact-inner {
    display: flex; align-items: center; justify-content: space-between; gap: 2rem;
}
.about-page .about-contact-inner > div { min-width: 0; }
.about-page .about-contact-button { flex: 0 0 auto; white-space: normal; }
@media (max-width: 991.98px) {
    .about-page .about-contact-inner { align-items: flex-start; flex-direction: column; gap: 1.25rem; }
    .about-page .about-contact-button { margin-top: 0; }
}
@media (max-width: 575.98px) {
    .about-page .about-principle { padding: 1.25rem; }
    .about-page .about-contact-button { width: 100%; text-align: center; }
}
@media (prefers-reduced-motion: no-preference) {
    .about-page .about-principle { transition: border-color 180ms ease, transform 180ms ease, box-shadow 180ms ease; }
    .about-page .about-principle:hover { transform: translateY(-3px); }
}
</style>

<?php require __DIR__ . '/includes/footer.php'; ?>
