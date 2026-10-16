<?php
$pageTitle = $pageTitle ?? 'Desenvolvimento de software com método';
$pageDescription = $pageDescription ?? 'Sistemas sob medida, produtos digitais e soluções construídas com boas práticas de engenharia de software.';
$currentPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$navItems = [
    'index.php' => 'Início',
    'solucoes.php' => 'Soluções',
    'projetos.php' => 'Projetos',
    'produtos.php' => 'Produtos',
    'sobre.php' => 'Sobre',
];
?><!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
  <meta name="theme-color" content="#101820">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  <style>
    /* Ajustes visuais exclusivos do cabeçalho da ToplevelSistemas */
    .site-header {
      background: #101820;
      border-bottom: 1px solid rgba(131, 197, 160, .22);
    }

    .site-header .navbar {
      min-height: 86px;
      padding-top: .85rem !important;
      padding-bottom: .85rem !important;
    }

    .site-header .brand-lockup {
      display: inline-flex;
      align-items: center;
      gap: .7rem;
      margin-right: 1.5rem;
    }

    .site-header .brand-mark {
      width: 46px;
      height: 46px;
      flex: 0 0 46px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 13px;
      background: #DCECE2;
      color: #101820;
      font-size: 1.35rem;
    }

    .site-header .brand-lockup strong {
      display: block;
      color: #fff;
      font-size: 1.32rem;
      line-height: 1.15;
      font-weight: 800;
      letter-spacing: -.025em;
    }

    .site-header .brand-lockup small {
      display: block;
      margin-top: .2rem;
      color: #B7C9BF;
      font-size: .61rem;
      line-height: 1.2;
      letter-spacing: .14em;
    }

    .site-header .navbar-nav {
      gap: .35rem;
    }

    .site-header .navbar-nav .nav-link {
      padding: .55rem .62rem;
      color: #E7ECE9;
      font-size: 1rem;
      font-weight: 700;
      transition: color .18s ease;
    }

    .site-header .navbar-nav .nav-link:hover,
    .site-header .navbar-nav .nav-link:focus,
    .site-header .navbar-nav .nav-link.active {
      color: #83C5A0;
    }

    .site-header .btn-accent {
      padding: .62rem 1rem !important;
      border-radius: 10px;
      font-size: .96rem;
      font-weight: 750;
      white-space: nowrap;
      box-shadow: none;
      transition: background-color .18s ease, transform .18s ease;
    }

    .site-header .btn-accent:hover {
      background: #11734D;
      transform: translateY(-1px);
    }

    .site-header .navbar-toggler {
      border-color: rgba(220, 236, 226, .35);
      padding: .45rem .6rem;
    }

    .site-header .navbar-toggler:focus {
      box-shadow: 0 0 0 .18rem rgba(131, 197, 160, .25);
    }

    @media (max-width: 991.98px) {
      .site-header .navbar {
        min-height: 74px;
      }

      .site-header .navbar-collapse {
        padding: .6rem 0 1rem;
      }

      .site-header .navbar-nav {
        align-items: stretch !important;
        gap: .15rem;
      }

      .site-header .navbar-nav .nav-link {
        padding: .7rem .25rem;
      }

      .site-header .navbar-nav .btn-accent {
        margin-top: .5rem;
      }
    }

    @media (max-width: 575.98px) {
      .site-header .brand-mark {
        width: 42px;
        height: 42px;
        flex-basis: 42px;
        border-radius: 11px;
      }

      .site-header .brand-lockup strong {
        font-size: 1.15rem;
      }

      .site-header .brand-lockup small {
        font-size: .54rem;
        letter-spacing: .11em;
      }
    }

    @media (prefers-reduced-motion: reduce) {
      .site-header .navbar-nav .nav-link,
      .site-header .btn-accent {
        transition: none;
      }
    }
  </style>
</head>
<body>
<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
<header class="site-header">
  <nav class="navbar navbar-expand-lg navbar-dark container py-3" aria-label="Navegação principal">
    <a class="navbar-brand brand-lockup" href="index.php" aria-label="<?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?> - página inicial">
      <span class="brand-mark"><i class="bi bi-braces"></i></span>
      <span><strong><?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?></strong><small>SOFTWARE &amp; TECHNOLOGY</small></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Abrir menu"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3 mt-3 mt-lg-0">
        <?php foreach ($navItems as $file => $label): ?>
          <li class="nav-item"><a class="nav-link <?= $currentPage === $file ? 'active' : '' ?>" <?= $currentPage === $file ? 'aria-current="page"' : '' ?> href="<?= $file ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a></li>
        <?php endforeach; ?>
        <li class="nav-item ms-lg-2"><a class="btn btn-accent btn-sm px-3" href="contato.php">Vamos conversar <i class="bi bi-arrow-up-right ms-1"></i></a></li>
      </ul>
    </div>
  </nav>
</header>
<main id="conteudo">
