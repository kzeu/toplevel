
<?php
require __DIR__ . '/_init.php';
store_admin_required();

$pdo = store_db();
$id = (int)($_GET['id'] ?? 0);

$p = [
    'name' => '',
    'slug' => '',
    'category' => 'Software',
    'short_description' => '',
    'description' => '',
    'price' => '0.00',
    'license_type' => 'perpetual',
    'max_activations' => 1,
    'version' => '1.0.0',
    'cover_image' => '',
    'file_path' => '',
    'demo_url' => '',
    'is_published' => 0
];

$error = '';
$success = '';

// --------------------------------------------------
// CARREGAR PRODUTO PARA EDIÇÃO
// --------------------------------------------------

if ($id > 0) {
    $q = $pdo->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
    $q->execute([$id]);

    $found = $q->fetch(PDO::FETCH_ASSOC);

    if (!$found) {
        http_response_code(404);
        exit('Produto não encontrado.');
    }

    $p = array_merge($p, $found);
}

// --------------------------------------------------
// FUNÇÃO DE UPLOAD
// --------------------------------------------------

function store_upload_file(
    array $file,
    string $type,
    string $slug
): string {

    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Erro durante o envio do arquivo.');
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Arquivo enviado inválido.');
    }

    $originalName = $file['name'];

    $extension = strtolower(
        pathinfo($originalName, PATHINFO_EXTENSION)
    );

    $size = (int)$file['size'];

    // CAPA
    if ($type === 'cover') {

        $allowed = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp'
        ];

        if (!isset($allowed[$extension])) {
            throw new RuntimeException(
                'A capa deve ser JPG, JPEG, PNG ou WEBP.'
            );
        }

        if ($size > 5 * 1024 * 1024) {
            throw new RuntimeException(
                'A imagem deve ter no máximo 5 MB.'
            );
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if ($mime !== $allowed[$extension]) {
            throw new RuntimeException(
                'O conteúdo da imagem não corresponde ao formato informado.'
            );
        }

        $directory = dirname(__DIR__) . '/assets/produtos';

        $relativeDirectory = 'assets/produtos';

    // INSTALADOR
    } elseif ($type === 'installer') {

        $allowed = ['zip', 'exe', 'msi', 'txt', 'pdf'];

        if (!in_array($extension, $allowed, true)) {
            throw new RuntimeException(
                'O arquivo para entrega deve ser ZIP, EXE, MSI, TXT ou PDF.'
            );
        }

        if ($size > 500 * 1024 * 1024) {
            throw new RuntimeException(
                'O instalador deve ter no máximo 500 MB.'
            );
        }

        $directory = STORE_DOWNLOAD_DIR;

        $relativeDirectory = '';

    } else {
        throw new RuntimeException('Tipo de arquivo inválido.');
    }

    if (!is_dir($directory)) {
        if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException(
                'Não foi possível criar a pasta de armazenamento.'
            );
        }
    }

    if (!is_writable($directory)) {
        throw new RuntimeException(
            'A pasta de armazenamento não possui permissão de escrita.'
        );
    }

    $safeSlug = preg_replace('/[^a-z0-9-]/', '', $slug);

    $filename = $safeSlug . '-' . bin2hex(random_bytes(8)) . '.' . $extension;

    $destination = $directory . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException(
            'Não foi possível armazenar o arquivo enviado.'
        );
    }

    if ($type === 'cover') {
        return $relativeDirectory . '/' . $filename;
    }

    // Caminho relativo à pasta privada STORE_DOWNLOAD_DIR.
    return $filename;
}

// --------------------------------------------------
// PROCESSAR FORMULÁRIO
// --------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    store_check_csrf();

    foreach ([
        'name',
        'slug',
        'category',
        'short_description',
        'description',
        'price',
        'license_type',
        'version',
        'demo_url'
    ] as $key) {

        $p[$key] = trim((string)($_POST[$key] ?? ''));
    }

    $p['max_activations'] = max(
        1,
        (int)($_POST['max_activations'] ?? 1)
    );

    $p['is_published'] =
        (int)($_POST['is_published'] ?? 0) === 1 ? 1 : 0;

    // Gerar slug
    if ($p['slug'] === '') {
        $p['slug'] = $p['name'];
    }

    $slugSource = iconv(
        'UTF-8',
        'ASCII//TRANSLIT//IGNORE',
        $p['slug']
    );

    $p['slug'] = strtolower(
        trim(
            (string)preg_replace(
                '/[^a-zA-Z0-9]+/',
                '-',
                $slugSource
            ),
            '-'
        )
    );

    // Validar campos
    if (
        $p['name'] === '' ||
        $p['slug'] === '' ||
        $p['short_description'] === ''
    ) {

        $error = 'Preencha nome, endereço e resumo do produto.';

    } elseif (
        !is_numeric($p['price']) ||
        (float)$p['price'] < 0
    ) {

        $error = 'Informe um preço válido.';

    } elseif (
        !in_array(
            $p['license_type'],
            ['perpetual', 'annual', 'monthly', 'none'],
            true
        )
    ) {

        $error = 'Modelo de licença inválido.';

    } elseif (
        $p['demo_url'] !== '' &&
        (
            !filter_var($p['demo_url'], FILTER_VALIDATE_URL) ||
            !in_array(
                strtolower((string)parse_url($p['demo_url'], PHP_URL_SCHEME)),
                ['http', 'https'],
                true
            )
        )
    ) {

        $error = 'Informe um endereço válido para a demonstração.';

    } else {

        // Verificar slug duplicado
        $check = $pdo->prepare(
            'SELECT id FROM products WHERE slug = ? AND id <> ? LIMIT 1'
        );

        $check->execute([$p['slug'], $id]);

        if ($check->fetch()) {

            $error = 'Este endereço já está sendo utilizado. Escolha outro slug.';

        } else {

            $newCover = '';
            $newInstaller = '';

            try {

                // Upload da capa, se selecionada
                if (
                    isset($_FILES['cover_file']) &&
                    $_FILES['cover_file']['error'] !== UPLOAD_ERR_NO_FILE
                ) {
                    $newCover = store_upload_file(
                        $_FILES['cover_file'],
                        'cover',
                        $p['slug']
                    );

                    $p['cover_image'] = $newCover;
                }

                // Upload do instalador, se selecionado
                if (
                    isset($_FILES['installer_file']) &&
                    $_FILES['installer_file']['error'] !== UPLOAD_ERR_NO_FILE
                ) {
                    $newInstaller = store_upload_file(
                        $_FILES['installer_file'],
                        'installer',
                        $p['slug']
                    );

                    $p['file_path'] = $newInstaller;
                }

                $values = [
                    $p['name'],
                    $p['slug'],
                    $p['category'] ?: 'Software',
                    $p['short_description'],
                    $p['description'],
                    number_format((float)$p['price'], 2, '.', ''),
                    $p['license_type'],
                    $p['max_activations'],
                    $p['version'],
                    $p['cover_image'],
                    $p['file_path'],
                    $p['demo_url'],
                    $p['is_published']
                ];

                if ($id > 0) {

                    $sql = "
                        UPDATE products SET
                            name=?,
                            slug=?,
                            category=?,
                            short_description=?,
                            description=?,
                            price=?,
                            license_type=?,
                            max_activations=?,
                            version=?,
                            cover_image=?,
                            file_path=?,
                            demo_url=?,
                            is_published=?
                        WHERE id=?
                    ";

                    $values[] = $id;

                } else {

                    $sql = "
                        INSERT INTO products (
                            name,
                            slug,
                            category,
                            short_description,
                            description,
                            price,
                            license_type,
                            max_activations,
                            version,
                            cover_image,
                            file_path,
                            demo_url,
                            is_published
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ";
                }

                $pdo->prepare($sql)->execute($values);

                // Remover arquivos antigos após salvar com sucesso.
                // Somente arquivos dentro das pastas controladas.

                if ($id > 0 && $newCover !== '' && !empty($found['cover_image'])) {

                    $oldCover = dirname(__DIR__) . '/' . $found['cover_image'];

                    $allowedCoverDir = realpath(dirname(__DIR__) . '/assets/produtos');
                    $oldCoverDir = is_file($oldCover) ? realpath(dirname($oldCover)) : false;

                    if (
                        $allowedCoverDir &&
                        $oldCoverDir === $allowedCoverDir &&
                        is_file($oldCover)
                    ) {
                        unlink($oldCover);
                    }
                }

                if ($id > 0 && $newInstaller !== '' && !empty($found['file_path'])) {

                    $oldInstaller = STORE_DOWNLOAD_DIR . '/' . basename($found['file_path']);

                    $allowedDownloadDir = realpath(STORE_DOWNLOAD_DIR);
                    $oldInstallerDir = is_file($oldInstaller) ? realpath(dirname($oldInstaller)) : false;

                    if (
                        $allowedDownloadDir &&
                        $oldInstallerDir === $allowedDownloadDir &&
                        is_file($oldInstaller)
                    ) {
                        unlink($oldInstaller);
                    }
                }

                header('Location: produtos.php?salvo=1');
                exit;

            } catch (Throwable $e) {

                // Remover novos arquivos se a gravação falhar
                if ($newCover !== '') {
                    $path = dirname(__DIR__) . '/' . $newCover;

                    if (is_file($path)) {
                        unlink($path);
                    }
                }

                if ($newInstaller !== '') {
                    $path = STORE_DOWNLOAD_DIR . '/' . basename($newInstaller);

                    if (is_file($path)) {
                        unlink($path);
                    }
                }

                error_log('Toplevel produto-form: ' . $e->getMessage());

                $error = $e instanceof RuntimeException
                    ? $e->getMessage()
                    : 'Não foi possível salvar o produto. Consulte o log do PHP.';
            }
        }
    }
}

?>

<!doctype html>
<html lang="pt-BR">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        <?= $id ? 'Editar' : 'Cadastrar' ?> produto | Toplevel
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link href="../assets/css/style.css" rel="stylesheet">

</head>

<body>

<nav class="navbar navbar-dark" style="background:#101820">

    <div class="container">

        <a class="navbar-brand" href="index.php">
            TOPLEVEL · LOJA
        </a>

        <a class="text-white" href="logout.php">
            Sair
        </a>

    </div>

</nav>

<main class="container py-5" style="max-width:950px">

    <a href="produtos.php" class="text-decoration-none">
        ← Voltar aos produtos
    </a>

    <h1 class="h2 mt-3 mb-4">
        <?= $id ? 'Editar produto' : 'Cadastrar produto' ?>
    </h1>

    <?php if ($error): ?>

        <div class="alert alert-danger">
            <?= store_h($error) ?>
        </div>

    <?php endif; ?>

    <form
        method="post"
        enctype="multipart/form-data"
        class="contact-panel"
    >

        <input
            type="hidden"
            name="_csrf"
            value="<?= store_h(store_csrf()) ?>"
        >

        <div class="row g-3">

            <div class="col-md-8">

                <label class="form-label">
                    Nome do produto *
                </label>

                <input
                    class="form-control"
                    name="name"
                    required
                    value="<?= store_h((string)$p['name']) ?>"
                >

            </div>

            <div class="col-md-4">

                <label class="form-label">
                    Endereço (slug)
                </label>

                <input
                    class="form-control"
                    name="slug"
                    value="<?= store_h((string)$p['slug']) ?>"
                    placeholder="Gerado pelo nome"
                >

            </div>

            <div class="col-md-6">

                <label class="form-label">
                    Categoria
                </label>

                <input
                    class="form-control"
                    name="category"
                    value="<?= store_h((string)$p['category']) ?>"
                >

            </div>

            <div class="col-md-3">

                <label class="form-label">
                    Preço (R$) *
                </label>

                <input
                    class="form-control"
                    type="number"
                    min="0"
                    step="0.01"
                    name="price"
                    required
                    value="<?= store_h((string)$p['price']) ?>"
                >

            </div>

            <div class="col-md-3">

                <label class="form-label">
                    Versão
                </label>

                <input
                    class="form-control"
                    name="version"
                    value="<?= store_h((string)$p['version']) ?>"
                >

            </div>

            <div class="col-12">

                <label class="form-label">
                    Resumo para o catálogo *
                </label>

                <input
                    class="form-control"
                    maxlength="500"
                    name="short_description"
                    required
                    value="<?= store_h((string)$p['short_description']) ?>"
                >

            </div>

            <div class="col-12">

                <label class="form-label">
                    Descrição completa
                </label>

                <textarea
                    class="form-control"
                    rows="6"
                    name="description"
                ><?= store_h((string)$p['description']) ?></textarea>

            </div>

            <div class="col-md-4">

                <label class="form-label">
                    Modelo de licença
                </label>

                <select class="form-select" name="license_type">

                    <?php foreach ([
                        'perpetual' => 'Vitalicío',
                        'annual' => 'Anual',
                        'monthly' => 'Mensal',
                        'none' => 'Sem licença'
                    ] as $key => $label): ?>

                        <option
                            value="<?= store_h($key) ?>"
                            <?= $p['license_type'] === $key ? 'selected' : '' ?>
                        >
                            <?= store_h($label) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="col-md-4">

                <label class="form-label">
                    Limite de computadores
                </label>

                <input
                    class="form-control"
                    type="number"
                    min="1"
                    name="max_activations"
                    value="<?= store_h((string)$p['max_activations']) ?>"
                >

            </div>

            <div class="col-md-4">

                <label class="form-label">
                    Publicação
                </label>

                <select class="form-select" name="is_published">

                    <option
                        value="0"
                        <?= (int)$p['is_published'] === 0 ? 'selected' : '' ?>
                    >
                        Rascunho
                    </option>

                    <option
                        value="1"
                        <?= (int)$p['is_published'] === 1 ? 'selected' : '' ?>
                    >
                        Publicado
                    </option>

                </select>

            </div>

            <!-- CAPA -->

            <div class="col-md-6">

                <label class="form-label">
                    Imagem de capa
                </label>

                <input
                    class="form-control"
                    type="file"
                    name="cover_file"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                >

                <div class="form-text">
                    JPG, PNG ou WEBP. Máximo 5 MB.
                </div>

                <?php if (!empty($p['cover_image'])): ?>

                    <div class="mt-2">

                        <img
                            src="../<?= store_h((string)$p['cover_image']) ?>"
                            alt="Capa atual"
                            style="max-width:220px;max-height:130px;object-fit:contain;border-radius:8px"
                        >

                        <div class="small text-secondary mt-1">
                            Capa atual. Se não enviar outra, ela será mantida.
                        </div>

                    </div>

                <?php endif; ?>

            </div>

            <!-- INSTALADOR -->

            <div class="col-md-6">

                <label class="form-label">
                    Arquivo para entrega (instalador ou instruções)
                </label>

                <input
                    class="form-control"
                    type="file"
                    name="installer_file"
                    accept=".zip,.exe,.msi,.txt,.pdf"
                >

                <div class="form-text">
                    Envie um único arquivo: ZIP, EXE, MSI, TXT ou PDF. Máximo 500 MB.
                </div>

                <?php if (!empty($p['file_path'])): ?>

                    <div class="small text-success mt-2">
                        <i class="bi bi-check-circle"></i>
                        Arquivo cadastrado: <?= store_h(basename((string)$p['file_path'])) ?>
                    </div>

                    <div class="small text-secondary">
                        Se não enviar outro, o arquivo atual será mantido.
                    </div>

                <?php endif; ?>

            </div>

            <!-- DEMONSTRAÇÃO -->

            <div class="col-12">

                <label class="form-label">
                    Link da demonstração
                </label>

                <input
                    class="form-control"
                    type="url"
                    name="demo_url"
                    value="<?= store_h((string)$p['demo_url']) ?>"
                    placeholder="https://www.youtube.com/watch?v=... ou https://demo.seusistema.com.br"
                >

                <div class="form-text">
                    Aceita vídeo do YouTube ou endereço de demonstração online.
                </div>

            </div>

            <div class="col-12">

                <button
                    class="btn btn-dark px-4 py-3"
                    type="submit"
                >
                    Salvar produto
                </button>

                <a
                    href="produtos.php"
                    class="btn btn-outline-secondary px-4 py-3"
                >
                    Cancelar
                </a>

            </div>

        </div>

    </form>

</main>

</body>
</html>
