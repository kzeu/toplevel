</main>
<footer class="site-footer">
  <div class="container py-5">
    <div class="row g-4 align-items-start">
      <div class="col-lg-5">
        <a class="navbar-brand brand-lockup mb-3" href="index.php">
          <span class="brand-mark"><i class="bi bi-braces"></i></span>
          <span><strong><?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?></strong><small>SOFTWARE &amp; TECHNOLOGY</small></span>
        </a>
        <p class="footer-copy mt-3 mb-0">Soluções digitais desenvolvidas a partir de necessidades reais, com método, clareza e atenção à qualidade.</p>
      </div>
      <div class="col-6 col-lg-3 offset-lg-1">
        <h2 class="footer-heading">Explorar</h2>
        <ul class="footer-links">
          <li><a href="solucoes.php">Soluções</a></li><li><a href="projetos.php">Projetos</a></li><li><a href="produtos.php">Produtos</a></li><li><a href="sobre.php">Sobre</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-3">
        <h2 class="footer-heading">Conecte-se</h2>
        <ul class="footer-links">
          <li><a href="<?= htmlspecialchars(GITHUB_URL, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">GitHub <i class="bi bi-arrow-up-right"></i></a></li>
          <li><a href="<?= htmlspecialchars(LINKEDIN_URL, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">LinkedIn <i class="bi bi-arrow-up-right"></i></a></li>
          <li><a href="contato.php">Solicitar orçamento</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom mt-5 pt-4 d-flex flex-column flex-md-row justify-content-between gap-2">
      <span>&copy; <?= date('Y') ?> <?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?>. Todos os direitos reservados.</span>
      <span><?= htmlspecialchars(SITE_TAGLINE, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
  </div>
</footer>
<?php $whatsappUrl = 'https://wa.me/' . preg_replace('/\D+/', '', WHATSAPP_NUMBER) . '?text=' . rawurlencode(WHATSAPP_MESSAGE); ?>
<a class="whatsapp-float" href="<?= htmlspecialchars($whatsappUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="Fale com a ToplevelSistemas pelo WhatsApp" title="Fale conosco pelo WhatsApp"><i class="bi bi-whatsapp"></i></a>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
