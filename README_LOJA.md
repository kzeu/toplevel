# Módulo inicial da Loja Digital Toplevel

Este pacote acrescenta a base de administração e catálogo genérico à estrutura PHP atual. Não é exclusivo do LocaTop: produtos futuros usam o mesmo cadastro.

## O que está incluído
- Banco MySQL para administradores, produtos, clientes, tokens de acesso, pedidos, pagamentos, downloads, licenças, ativações, eventos de gateway e fila de e-mails.
- Login administrativo com senha armazenada por `password_hash()`.
- Painel inicial com indicadores e pedidos recentes.
- Cadastro/edição de produtos genéricos.
- Catálogo público em `loja.php` e página de detalhe em `produto.php`.
- Área do Cliente com criação/recuperação de senha por link de uso único enviado por e-mail.
- Consulta de compras pagas, licenças e acesso aos instaladores na Área do Cliente.
- Diretório `storage` bloqueado para acesso HTTP.

## Instalação
1. Faça backup do site atual.
2. Crie um banco MySQL (ou importe `database/schema.sql`, que cria `toplevel_loja`).
3. Edite `config/store.php` com host, nome do banco, usuário e senha MySQL.
4. Garanta que `storage/downloads` e `storage/logs` tenham permissão de escrita pelo PHP, sem permitir execução de scripts.
5. Acesse `admin/criar-admin.php` uma única vez e crie o administrador. **Depois de criar, apague `admin/criar-admin.php` do servidor.**
6. Acesse `admin/login.php`, cadastre os produtos e publique-os.
7. Acesse `loja.php` para ver o catálogo público.

## Fluxo de venda implementado
O checkout cria o pedido e inicia o pagamento no Mercado Pago. A confirmação definitiva depende do webhook validado no servidor; somente após a confirmação o pedido é marcado como pago, a licença é criada e a notificação é processada. A Área do Cliente permite ao comprador criar ou redefinir a senha por link de uso único e consultar compras, licenças e instaladores.

Os instaladores devem permanecer em `storage/downloads`, fora da área pública. O download é servido por `download.php`, que valida o token e o status do pagamento. Configure e teste o SMTP e o endereço público de retorno antes de publicar.

## Segurança antes de produção
- Use HTTPS.
- Proteja `config/store.php` e mantenha credenciais fora do Git.
- O usuário MySQL deve ter permissões apenas no banco da loja.
- Restrinja o acesso administrativo (senha forte; idealmente MFA ou proteção adicional no servidor).
- Configure backup do MySQL e dos arquivos privados.
- Revise política de privacidade, termos de compra, cancelamento/reembolso e emissão fiscal.
