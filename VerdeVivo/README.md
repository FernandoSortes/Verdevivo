# VerdeVivo Jardinagem (PHP + MySQL/MariaDB + PDO + Bootstrap)

## Como executar no XAMPP
1. Copie a pasta `VerdeVivo` para `C:\xampp\htdocs\` (PHP 8.0 ou superior).
2. Abra o XAMPP e inicie **Apache** e **MySQL**.
3. Em http://localhost/phpmyadmin → **Importar** → escolha `database/verdevivo.sql` → Executar (cria o banco, as tabelas, o admin Fernando, 10+ categorias e 34 produtos).
4. Se o root do MySQL tiver senha, ajuste `config/database.php`. Se a pasta não se chamar `VerdeVivo`, ajuste `BASE_URL`.
5. Acesse http://localhost/VerdeVivo/

**Painel administrativo:** http://localhost/VerdeVivo/login.php → e-mail `admin@gmail` e senha inicial `admin123` → abre `/admin/dashboard.php`.
Cliente de teste: `cliente@verdevivo.com` / `cliente123` (não acessa o admin: recebe 403).

## Estrutura
- `index.php, produtos.php, produto.php, carrinho.php, checkout.php, login.php, cadastro.php, logout.php`: loja.
- `admin/`: dashboard, produtos (+ `produto_form.php`), estoque, pedidos, categorias, usuários.
- `app/services/`: regras de negócio (`OrderService`, `StockService`, `DashboardService`). `app/helpers/`: funções utilitárias e upload.
- `includes/`: header, navbar, footer, auth (sessão + carregamento). `config/database.php`: PDO.
- `database/`: `schema.sql`, `seed.sql` e `verdevivo.sql` (os dois juntos, para importar de uma vez).

## Regras de negócio
- Pagamento **simulado**: o pedido nasce **Confirmado** e o estoque baixa nessa hora (nunca ao adicionar ao carrinho).
- Contam como **venda/faturamento**: Confirmado, Em preparação, Enviado, Entregue. **Não contam**: Pendente e Cancelado.
- Cancelar devolve o estoque **uma única vez** (coluna `orders.stock_deducted`). Pedido cancelado não reabre.
- Compra = transação SQL com `SELECT ... FOR UPDATE` nos produtos: valida estoque no servidor, grava pedido e itens, baixa estoque e registra `stock_movements`. Qualquer erro dá ROLLBACK.
- Preços e total vêm do banco, nunca do navegador.
- Excluir produto com vendas apenas o **desativa**; sem vendas, exclui de fato.
- Estoque baixo: 1 a 5 unidades. Esgotado: 0.

## Roteiro de testes (manual)
1. **Cadastro**: Admin → Produtos → + Novo produto (10 un.) → aparece em phpMyAdmin (`products`) e no catálogo.
2. **Edição**: altere nome e preço → conferir na lista e na loja; o ID não muda.
3. **Exclusão**: produto sem vendas some; produto com vendas fica inativo e some da loja, mas o pedido antigo continua mostrando o nome.
4. **Estoque**: compre 3 de um produto com 10 → estoque 7 (Admin → Estoque, e movimentação "saída").
5. **Insuficiente**: coloque no carrinho, reduza o estoque pelo admin e finalize → compra bloqueada, estoque inalterado.
6. **Faturamento/Vendidos**: compra de R$ 100 → Dashboard soma R$ 100 e produtos vendidos sobe pela quantidade.
7. **Cancelamento**: Admin → Pedidos → Cancelar → faturamento e estoque corrigidos; cancelar de novo é recusado.
8. **Persistência**: feche o navegador, abra de novo; tudo continua.
9. **Segurança**: sem login, `/admin/dashboard.php` redireciona ao login; logado como cliente, retorna 403.

## Implementado
Estrutura/PDO/SQL, catálogo com busca, filtro e paginação, produto, carrinho em sessão, checkout transacional, login/cadastro com hash, CSRF, escape de saída, CRUD de produtos com upload validado, estoque com histórico, pedidos com status e cancelamento, categorias, clientes com histórico, dashboard dinâmico (7 indicadores, mais vendidos, vendas por dia).

## Pendente / limitações
- Não há "Meus pedidos" para o cliente (só o admin vê o histórico).
- Sem recuperação de senha, sem limite de tentativas de login.
- Imagens só por upload (os produtos de exemplo usam ícone).
- O código não foi executado contra o MySQL no ambiente de geração: faça o roteiro acima e reporte qualquer erro.

## Atualização v2 (catálogo, fotos e admin Fernando)
**Banco já existente com dados?** Importe SOMENTE `database/atualizacao_v2.sql` (é idempotente e não apaga nada). Banco novo: `verdevivo.sql` já inclui tudo.
Se existir o admin antigo `admin@verdevivo.com` (senha admin123), apague-o ou troque a senha.

**Fotos reais dos produtos:** o pacote NÃO traz fotografias, pois foi gerado sem acesso à internet. Duas formas de colocá-las:
1. Painel admin → Produtos → Editar → Imagem (upload do seu computador, JPG/PNG/WEBP até 2 MB); ou
2. Baixe fotos de Unsplash, Pexels ou Wikimedia Commons (confira a licença) e salve em `assets/imagens/produtos/` com os nomes de `assets/imagens/produtos/LEIA-ME.txt`.
Produto sem foto exibe `assets/imagens/padrao.svg`. Imagem usada por outro produto nunca é apagada.

**Arquivos modificados na v2:** `app/helpers/helpers.php`, `app/helpers/upload.php`, `login.php`, `produto.php`, `admin/dashboard.php`, `admin/produtos.php`, `admin/produto_form.php`, `includes/admin_header.php`, `database/seed.sql`, `database/verdevivo.sql`. **Novos:** `database/atualizacao_v2.sql`, `assets/imagens/padrao.svg`, `assets/imagens/produtos/LEIA-ME.txt`. A pasta `assets/images/products` passou a `assets/imagens/produtos`: se você já enviou fotos pelo painel, mova-as para a nova pasta.
**Nomes em português:** não renomeei funções/arquivos antigos para não quebrar o que funciona; é uma melhoria opcional futura.

## Correção do acesso administrativo (v3)
**Conta:** Fernando · `fernando.sortes@outlook.com` · senha inicial `fernando` · perfil `admin`.
**Aplicar no XAMPP (escolha uma):**
1. phpMyAdmin → banco `verdevivo` → Importar → `database/correcao_admin.sql`. Preserva usuários, produtos e pedidos; não duplica a conta; migra a antiga `fernando@gmail`; redefine a senha para `fernando`.
2. Ou abra http://localhost/VerdeVivo/instalar_admin.php (só em localhost): mostra o diagnóstico e o botão "Criar / reparar" grava a conta com `password_hash()`. **Apague esse arquivo depois.**
Banco novo: `database/verdevivo.sql` já traz a conta correta.
**Causa provável da falha anterior:** o código de login não tinha erro de lógica, então o problema estava nos dados: (a) a conta `fernando@gmail` só existe se o `atualizacao_v2.sql` foi importado (bancos antigos só tinham `admin@verdevivo.com`); (b) o hash inicial tinha sido gerado fora do PHP; (c) o login era um e-mail incomum, fácil de digitar errado. Agora o hash é bcrypt (`$2y$`) e o diagnóstico mostra o estado real da conta.

## Conta administrativa atual (v4)
**E-mail `admin@gmail` · senha inicial `admin123` · perfil `admin`** (nome: Fernando). Substitui as contas das versões anteriores.
Aplicar: importe `database/correcao_admin.sql` no phpMyAdmin (migra a conta antiga sem duplicar, preserva os demais dados e redefine a senha para `admin123`) ou use `instalar_admin.php` (localhost; apague-o depois). Troque a senha após o primeiro acesso.
