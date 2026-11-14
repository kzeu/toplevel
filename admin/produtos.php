<?php
require __DIR__ . '/_init.php';
store_admin_required();
$rows = store_db()->query('SELECT id, name, slug, category, price, version, is_published, updated_at FROM products ORDER BY id DESC')->fetchAll();
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Produtos | Toplevel</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark" style="background:#101820"><div class="container">
<a class="navbar-brand" href="index.php">TOPLEVEL · LOJA</a>
<div class="d-flex gap-3"><a class="text-white" href="index.php">Painel</a><a class="text-white" href="../loja.php" target="_blank" rel="noopener">Ver loja</a><a class="text-white" href="logout.php">Sair</a></div>
</div></nav>
<main class="container py-5">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><span class="section-kicker">Catálogo</span><h1 class="h2 mb-0">Produtos</h1><p class="text-secondary mb-0">Gerencie todos os produtos digitais da ToplevelSistemas.</p></div><a class="btn btn-dark" href="produto-form.php">+ Cadastrar produto</a></div>
<?php if (isset($_GET['salvo'])): ?><div class="alert alert-success">Produto salvo com sucesso.</div><?php endif; ?>
<div class="contact-panel"><div class="table-responsive"><table class="table table-hover align-middle">
<thead><tr><th>Produto</th><th>Categoria</th><th>Preço</th><th>Versão</th><th>Status</th><th class="text-end">Ação</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr>
<td><strong><?= store_h((string)$r['name']) ?></strong><br><small class="text-secondary">/<?= store_h((string)$r['slug']) ?></small></td>
<td><?= store_h((string)$r['category']) ?></td>
<td>R$ <?= number_format((float)$r['price'], 2, ',', '.') ?></td>
<td><?= store_h((string)($r['version'] ?? '-')) ?></td>
<td><?php if ((int)$r['is_published'] === 1): ?><span class="badge bg-success">Publicado</span><?php else: ?><span class="badge bg-secondary">Rascunho</span><?php endif; ?></td>
<td class="text-end"><a class="btn btn-sm btn-outline-dark" href="produto-form.php?id=<?= (int)$r['id'] ?>">Editar</a></td>
</tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="6" class="text-center text-secondary py-4">Nenhum produto cadastrado.</td></tr><?php endif; ?>
</tbody></table></div></div>
</main></body></html>
