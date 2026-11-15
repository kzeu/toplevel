# Simplificação da navegação

- O menu principal agora apresenta Início, Soluções, Projetos, Loja, Área do Cliente e Sobre.
- A página Soluções concentra os serviços e capacidades da ToplevelSistemas.
- A Loja permanece como catálogo de produtos digitais disponíveis para aquisição.
- O link antigo `produtos.php` redireciona para `loja.php`, preservando favoritos e links antigos.
- O rodapé e o botão da página inicial foram ajustados para apontar para a Loja.

## Publicação

O pacote não contém a pasta `.git`. Copie os arquivos alterados para o seu projeto local, revise no navegador e publique pelo fluxo Git habitual.

`config/store.php` permanece como configuração local e está no `.gitignore`. Use `config/store.example.php` como modelo; mantenha as credenciais de produção diretamente no servidor e fora do GitHub.

## Arquivo único por produto

O cadastro possui um único campo de arquivo para entrega. Aceita ZIP, EXE, MSI, TXT ou PDF.
Assim, cada produto pode disponibilizar um instalador ou, alternativamente, um arquivo de instruções.
O arquivo é entregue pela Área do Cliente após a compra aprovada; o e-mail de confirmação direciona o cliente para essa área, em vez de anexar instaladores grandes.

## Segurança de configuração

`config/store.php` e `config/email.php` são arquivos locais/de produção e não devem ser versionados.
Depois de extrair este pacote, configure as credenciais SMTP reais no `config/email.php` do ambiente correspondente.
