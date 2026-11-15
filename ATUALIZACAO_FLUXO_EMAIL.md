# Atualização do fluxo de compra e entrega por e-mail

Este pacote mantém a estrutura completa do projeto enviado e altera o fluxo de entrega digital.

## O que foi alterado
- `loja.php`: aviso simples informando que o produto será enviado por e-mail após a aprovação do pagamento.
- `pagamentos/webhook.php`: após validar a confirmação diretamente com o Mercado Pago, marca o pedido como pago, gera/confirma as licenças, cria links individuais de download e envia o e-mail ao endereço registrado no pedido.
- `download.php`: valida o token, o pagamento aprovado, a validade e o limite técnico do link antes de entregar o arquivo.
- `sucesso.php`: alinha a criação dos links de download às colunas obrigatórias da tabela `downloads` do SQL enviado.
- `README_LOJA.md`: atualiza a descrição do fluxo.

## Antes de enviar ao servidor
1. Faça backup da pasta atual do site e do banco.
2. **Não substitua** os arquivos privados `config/store.php`, `config/email.php` e `config/mercadopago.php` que já existem no servidor.
3. Os arquivos `config/*.example.php` são apenas modelos, sem credenciais. Em instalação nova, copie cada modelo para o nome sem `.example` e preencha as configurações reais.
4. Confirme que o `STORE_BASE_URL` aponta para o domínio público definitivo, não para um endereço temporário de túnel.
5. Confirme que os instaladores estão em `storage/downloads` e que o caminho cadastrado em `products.file_path` corresponde ao nome do arquivo.
6. Faça uma compra de teste no ambiente de teste do Mercado Pago e confira o log do PHP, o estado do pedido, a licença e a chegada do e-mail.

## Banco de dados
O código foi alinhado ao `database/schema.sql` que veio no ZIP. Não execute novamente esse schema em um banco que já contém pedidos: ele contém comandos `DROP TABLE` e apagaria as tabelas existentes. Esta atualização não exige importar o schema novamente.
