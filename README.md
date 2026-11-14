# Nexo Software — Portfólio PHP

Site institucional responsivo para uma marca de desenvolvimento de software, com páginas de início, soluções, projetos, produtos, sobre e contato.

## Tecnologias
- PHP 8.2 ou superior (sem dependências PHP externas)
- Bootstrap 5.3 via CDN
- HTML5, CSS3 e JavaScript
- Preparado para adicionar MySQL em futuras funcionalidades

## Executar localmente
1. Instale PHP 8.2+ ou use XAMPP com uma versão recente do PHP.
2. Copie a pasta `portfolio-software` para o diretório público local (por exemplo, `htdocs`).
3. Edite `config/config.php` com nome da marca, e-mail e URLs corretas.
4. Acesse `http://localhost/portfolio-software/`.

Alternativamente, a partir da pasta do projeto, execute `php -S localhost:8000` e acesse `http://localhost:8000`.

## Configuração obrigatória antes de publicar
Edite `config/config.php`:
- `SITE_NAME`: nome real da marca.
- `CONTACT_EMAIL`: e-mail real do domínio. O formulário usa a função `mail()` do PHP, que precisa estar habilitada e configurada no servidor. Em produção, prefira SMTP autenticado usando uma biblioteca mantida, como PHPMailer, com credenciais fora da pasta pública.
- `GITHUB_URL`: URL do seu perfil GitHub.
- `LINKEDIN_URL`: URL do seu perfil LinkedIn.
- `SITE_URL`: domínio definitivo, caso vá implementar URLs canônicas ou sitemap.

## Personalização antes de publicar
- Troque os projetos conceituais por projetos reais que você tenha autorização para divulgar.
- Atualize a descrição profissional na página `sobre.php`.
- Troque o nome provisório `Nexo Software` pelo nome escolhido e verifique domínio/marca antes de investir em identidade.
- Revise os links externos e o e-mail.
- Teste o formulário no servidor real. O ambiente local normalmente não envia e-mail automaticamente.

## Segurança e produção
- O formulário valida os campos no servidor, escapa valores renderizados e inclui um campo honeypot básico. Isso não substitui rate limiting, CAPTCHA adaptativo ou proteção antispam adicional.
- O `.htaccess` bloqueia listagem de diretórios e define cabeçalhos básicos em Apache. Confirme que o servidor usa Apache e permite essas diretivas; em Nginx, configure os equivalentes no servidor.
- Use HTTPS. Mantenha PHP e o servidor atualizados.
- Não coloque senhas ou chaves de API no repositório.
- Para entrega profissional, configure envio por SMTP autenticado e logs de erro sem expor mensagens internas ao visitante.
- Bootstrap e Bootstrap Icons são carregados por CDN; para funcionar sem internet, baixe e hospede os arquivos localmente.

## Próximas evoluções sugeridas
1. Gerenciar projetos e produtos via painel administrativo.
2. Persistir contatos em MySQL, com consentimento e política de privacidade adequados.
3. Adicionar página de estudos de caso e imagens reais.
4. Criar catálogo de produtos e integração de pagamentos quando houver produtos definidos.
5. Adicionar área de artigos e futura página de agentes de IA.


## Personalização da ToplevelSistemas

- Ajuste `WHATSAPP_NUMBER` em `config/config.php` para o número com DDI e DDD, somente dígitos (ex.: `5511999999999`).
- Atualize `GITHUB_URL`, `LINKEDIN_URL` e `CONTACT_EMAIL` no mesmo arquivo.
- Os produtos são cadastrados no array `$products` de `produtos.php`. Copie um item, altere nome, categoria, descrição, ícone, status e links.
- Para exibir o botão de demonstração, informe a URL em `demo_url`. Se ficar vazio, a página mostra “Demonstração em preparação”.
- A página inicial inclui posicionamento para software sob demanda e produtividade com uso criterioso de IA.
- As animações são sutis e respeitam a preferência do sistema por movimento reduzido.

Antes de publicar, configure o número real do WhatsApp e substitua links de exemplo e descrições conceituais por informações da empresa.
