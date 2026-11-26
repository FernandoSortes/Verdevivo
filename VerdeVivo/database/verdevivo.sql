-- VerdeVivo Jardinagem - Estrutura do banco (MySQL/MariaDB, XAMPP)
CREATE DATABASE IF NOT EXISTS verdevivo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE verdevivo;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS stock_movements, order_items, orders, products, categories, users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','customer') NOT NULL DEFAULT 'customer',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  description TEXT NULL,
  price DECIMAL(10,2) NOT NULL,
  stock INT UNSIGNED NOT NULL DEFAULT 0,   -- UNSIGNED: o banco recusa estoque negativo
  image VARCHAR(255) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_products_category (category_id),
  INDEX idx_products_active_stock (active, stock),
  INDEX idx_products_name (name),
  CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Endereço de entrega fica no próprio pedido (ship_*): preserva o histórico e evita uma tabela extra.
CREATE TABLE orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  payment_method ENUM('pix','cartao','boleto') NOT NULL,
  status ENUM('pendente','confirmado','em_preparacao','enviado','entregue','cancelado') NOT NULL DEFAULT 'confirmado',
  stock_deducted TINYINT(1) NOT NULL DEFAULT 0,  -- trava contra baixa/devolução duplicada
  ship_name VARCHAR(120) NOT NULL,
  ship_zip VARCHAR(12) NOT NULL,
  ship_address VARCHAR(200) NOT NULL,
  ship_city VARCHAR(80) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_orders_user (user_id),
  INDEX idx_orders_status (status),
  INDEX idx_orders_created (created_at),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  product_name VARCHAR(150) NOT NULL,       -- cópia do nome no momento da compra
  quantity INT UNSIGNED NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  INDEX idx_items_order (order_id),
  INDEX idx_items_product (product_id),
  CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE stock_movements (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  order_id INT UNSIGNED NULL,
  user_id INT UNSIGNED NULL,
  type ENUM('entrada','saida','ajuste','devolucao') NOT NULL,
  quantity INT NOT NULL,                    -- com sinal: venda = negativo
  reason VARCHAR(200) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_mov_product (product_id),
  INDEX idx_mov_created (created_at),
  CONSTRAINT fk_mov_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_mov_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
  CONSTRAINT fk_mov_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- VerdeVivo - Dados de teste. Logins: admin@gmail / admin123 (admin, criado em atualizacao_v2.sql)  |  cliente@verdevivo.com / cliente123
INSERT INTO users (name,email,password,role) VALUES
('Cliente Teste','cliente@verdevivo.com','$6$verdevivo2$NLbWzITDFGrmR0qIZw1gkWi.V44gZfmvc9Xzt7kUOxBvlOMyDN2R149zCcmXycQXXFExo2j1zI3D/tquMNvX4/','customer');
INSERT INTO categories (name,description) VALUES
('Plantas','Plantas ornamentais, suculentas e orquídeas'),
('Vasos','Vasos de cerâmica, plástico e autoirrigáveis'),
('Ferramentas','Ferramentas manuais para jardinagem'),
('Insumos','Terra, adubos e substratos');
INSERT INTO products (category_id,name,description,price,stock) VALUES
(1,'Samambaia Americana','Planta clássica para ambientes sombreados.',39.90,12),
(1,'Suculenta Echeveria','Fácil de cuidar, ideal para iniciantes.',19.90,30),
(1,'Orquídea Phalaenopsis','Flores duradouras, ótima para presente.',89.90,7),
(2,'Vaso de Cerâmica M','Vaso de cerâmica com furo de drenagem.',45.00,18),
(2,'Vaso Autoirrigável','Reservatório de água embutido.',59.90,9),
(3,'Pá de Jardinagem','Pá de aço com cabo ergonômico.',24.50,25),
(3,'Tesoura de Poda','Lâmina de aço inox para podas leves.',54.90,11),
(4,'Terra Vegetal 5kg','Terra preparada para vasos e canteiros.',22.00,40),
(4,'Adubo Orgânico 1kg','Adubo orgânico para todas as plantas.',17.90,0);
INSERT INTO stock_movements (product_id,type,quantity,reason) SELECT id,'entrada',stock,'Estoque inicial' FROM products WHERE stock>0;

-- VerdeVivo - Atualização v2 (IDEMPOTENTE: pode rodar mais de uma vez; não apaga nada)
-- Para bancos já existentes: importe só este arquivo no phpMyAdmin (banco verdevivo).
-- Conta administrativa inicial (admin@gmail / senha inicial: admin123). Hash bcrypt compatível com password_verify().
INSERT INTO users (name,email,password,role) SELECT 'Fernando','admin@gmail','$2y$10$fuoCPkHMgLYJzIU8l.tHYO.amP0lCpy9jGmauCIKIpDhQ7wXsh3pW','admin' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='admin@gmail');
-- Categorias novas
INSERT INTO categories (name,description) SELECT 'Plantas Ornamentais','Flores e arbustos ornamentais' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name='Plantas Ornamentais');
INSERT INTO categories (name,description) SELECT 'Suculentas e Cactos','Plantas resistentes e de pouca rega' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name='Suculentas e Cactos');
INSERT INTO categories (name,description) SELECT 'Plantas Frutíferas','Mudas de árvores e plantas frutíferas' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name='Plantas Frutíferas');
INSERT INTO categories (name,description) SELECT 'Plantas de Interior','Plantas para ambientes internos' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name='Plantas de Interior');
INSERT INTO categories (name,description) SELECT 'Plantas de Exterior','Plantas para jardins e varandas' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name='Plantas de Exterior');
INSERT INTO categories (name,description) SELECT 'Vasos Decorativos','Vasos e cachepôs decorativos' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name='Vasos Decorativos');
INSERT INTO categories (name,description) SELECT 'Terra e Substratos','Terra, húmus e substratos' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name='Terra e Substratos');
INSERT INTO categories (name,description) SELECT 'Adubos e Fertilizantes','Nutrição para as plantas' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name='Adubos e Fertilizantes');
INSERT INTO categories (name,description) SELECT 'Sementes','Sementes de flores, temperos e hortaliças' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name='Sementes');
-- Produtos novos (a foto é o arquivo em assets/imagens/produtos/; sem arquivo, a loja mostra a imagem padrão)
INSERT INTO products (category_id,name,description,price,stock,image) SELECT c.id,'Rosa do Deserto','Adenium de flores rosadas, ideal para sol pleno.',69.90,14,'rosa-do-deserto.jpg' FROM categories c WHERE c.name='Plantas Ornamentais' AND NOT EXISTS (SELECT 1 FROM products p WHERE p.name='Rosa do Deserto');
INSERT INTO products (category_id,name,description,price,stock,image) SELECT c.id,'Hibisco Tropical','Arbusto de flores grandes e coloridas.',49.90,10,'hibisco-tropical.jpg' FROM categories c WHERE c.name='Plantas Ornamentais' AND NOT EXISTS (SELECT 1 FROM products p WHERE p.name='Hibisco Tropical');
INSERT INTO products (category_id,name,description,price,stock,image) SELECT c.id,'Lavanda Inglesa','Perfumada, atrai polinizadores e combina com sol direto.',34.90,20,'lavanda-inglesa.jpg' FROM categories c WHERE c.name='Plantas Ornamentais' AND NOT EXISTS (SELECT 1 FROM products p WHERE p.name='Lavanda Inglesa');
INSERT INTO products (category_id,name,description,price,stock,image) SELECT c.id,'Cacto Mandacaru Mini','Cacto colunar de crescimento lento e baixa manutenção.',24.90,22,'cacto-mandacaru-mini.jpg' FROM categories c WHERE c.name='Suculentas e Cactos' AND NOT EXISTS (SELECT 1 FROM products p WHERE p.name='Cacto Mandacaru Mini');
INSERT INTO products (category_id,name,description,price,stock,image) SELECT c.id,'Haworthia Zebrina','Suculenta listrada que aceita meia-sombra.',16.90,35,'haworthia-zebrina.jpg' FROM categories c WHERE c.name='Suculentas e Cactos' AND NOT EXISTS (SELECT 1 FROM products p WHERE p.name='Haworthia Zebrina');
INSERT INTO products (category_id,name,description,price,stock,image) SELECT c.id,'Aloe Vera (Babosa)','Planta medicinal e decorativa, rega espaçada.',21.90,26,'aloe-vera-babosa.jpg' FROM categories c WHERE c.name='Suculentas e Cactos' AND NOT EXISTS (SELECT 1 FROM products p WHERE p.name='Aloe Vera (Babosa)');
INSERT INTO products (category_id,name,description,price,stock,image) SELECT c.id,'Muda de Limoeiro Siciliano','Muda enxertada, produz limões aromáticos.',79.90,8,'muda-de-limoeiro-siciliano.jpg' FROM categories c WHERE c.name='Plantas Frutíferas' AND NOT EXISTS (SELECT 1 FROM products p WHERE p.name='Muda de Limoeiro Siciliano');
INSERT INTO products (category_id,name,description,price,stock,image) SELECT c.id,'Muda de Pitangueira','Fruta nativa, adaptada a vasos grandes e quintais.',59.90,6,'muda-de-pitangueira.jpg' FROM categories c WHERE c.name='Plantas Frutíferas' AND NOT EXISTS (SELECT 1 FROM products p WHERE p.name='Muda de Pitangueira');
INSERT INTO products (category_id,name,description,price,stock,image) SELECT c.id,'Muda de Morangueiro','Muda de morango para vasos e jardineiras.',12.90,40,'muda-de-morangueiro.jpg' FROM categories c WHERE c.name='Plantas Frutíferas' AND NOT EXISTS (SELECT 1 FROM products p WHERE p.name='Muda de Morangueiro');
INSERT INTO products (category_id,name,description,price,stock,image) SELECT c.id,'Jiboia','Pendente, resistente e ótima para iniciantes.',29.90,28,'jiboia.jpg' FROM categories c WHERE c.name='Plantas de Interior' AND NOT EXISTS (SELECT 1 FROM products p WHERE p.name='Jiboia');
INSERT INTO products (category_id,name,description,price,stock,image) SELECT c.id,'Zamioculca','Folhas brilhantes, tolera pouca luz.',64.90,15,'zamioculca.jpg' FROM categories c WHERE c.name='Plantas de Interior' AND NOT EXISTS (SELECT 1 FROM products p WHERE p.name='Zamioculca');
INSERT INTO products (category_id,name,description,price,stock,image) SELECT c.id,'Costela-de-Adão','Folhas recortadas que dão ar tropical ao ambiente.',74.90,12,'costela-de-adao.jpg' FROM categories c WHERE c.name='Plantas de Interior' AND NOT EXISTS (SELECT 1 FROM products p WHERE p.name='Costela-de-Adão');
INSERT INTO products (category_id,name,description,price,stock,image) SELECT c.id,'Buganvília','Trepadeira de floração intensa para muros e pergolados.',54.90,9,'buganvilia.jpg' FROM categories c WHERE c.name='Plantas de Exterior' AND NOT EXISTS (SELECT 1 FROM products p WHERE p.name='Buganvília');
INSERT INTO products (category_id,name,description,price,stock,image) SELECT c.id,'Jasmim-Manga','Arbusto perfumado para jardins ensolarados.',44.90,4,'jasmim-manga.jpg' FROM categories c WHERE c.name='Plantas de Exterior' AND NOT EXISTS (SELECT 1 FROM products p WHERE p.name='Jasmim-Manga');
INSERT INTO products (category_id,name,description,price,stock,image) SELECT c.id,'Vaso Decorativo Concreto G','Vaso grande de concreto com acabamento rústico.',79.90,10,'vaso-decorativo-concreto-g.jpg' FROM categories c WHERE c.name='Vasos Decorativos' AND NOT EXISTS (SELECT 1 FROM products p WHERE p.name='Vaso Decorativo Concreto G');
INSERT INTO products (category_id,name,description,price,stock,image) SELECT c.id,'Cachepô de Vime','Cachepô artesanal que realça plantas de interior.',39.90,16,'cachepo-de-vime.jpg' FROM categories c WHERE c.name='Vasos Decorativos' AND NOT EXISTS (SELECT 1 FROM products p WHERE p.name='Cachepô de Vime');
INSERT INTO products (category_id,name,description,price,stock,image) SELECT c.id,'Substrato para Suculentas 2kg','Mistura drenante pronta para cactos e suculentas.',18.90,30,'substrato-para-suculentas-2kg.jpg' FROM categories c WHERE c.name='Terra e Substratos' AND NOT EXISTS (SELECT 1 FROM products p WHERE p.name='Substrato para Suculentas 2kg');
INSERT INTO products (category_id,name,description,price,stock,image) SELECT c.id,'Húmus de Minhoca 4kg','Condicionador orgânico rico em nutrientes.',26.90,24,'humus-de-minhoca-4kg.jpg' FROM categories c WHERE c.name='Terra e Substratos' AND NOT EXISTS (SELECT 1 FROM products p WHERE p.name='Húmus de Minhoca 4kg');
INSERT INTO products (category_id,name,description,price,stock,image) SELECT c.id,'Fertilizante NPK 10-10-10 500g','Adubo mineral balanceado para manutenção geral.',19.90,33,'fertilizante-npk-10-10-10-500g.jpg' FROM categories c WHERE c.name='Adubos e Fertilizantes' AND NOT EXISTS (SELECT 1 FROM products p WHERE p.name='Fertilizante NPK 10-10-10 500g');
INSERT INTO products (category_id,name,description,price,stock,image) SELECT c.id,'Adubo Líquido para Orquídeas','Fertilizante líquido específico para orquídeas.',23.90,3,'adubo-liquido-para-orquideas.jpg' FROM categories c WHERE c.name='Adubos e Fertilizantes' AND NOT EXISTS (SELECT 1 FROM products p WHERE p.name='Adubo Líquido para Orquídeas');
INSERT INTO products (category_id,name,description,price,stock,image) SELECT c.id,'Luvas de Jardinagem','Luvas resistentes com punho ajustável.',21.90,27,'luvas-de-jardinagem.jpg' FROM categories c WHERE c.name='Ferramentas' AND NOT EXISTS (SELECT 1 FROM products p WHERE p.name='Luvas de Jardinagem');
INSERT INTO products (category_id,name,description,price,stock,image) SELECT c.id,'Regador 5 Litros','Regador plástico com bico removível.',34.90,14,'regador-5-litros.jpg' FROM categories c WHERE c.name='Ferramentas' AND NOT EXISTS (SELECT 1 FROM products p WHERE p.name='Regador 5 Litros');
INSERT INTO products (category_id,name,description,price,stock,image) SELECT c.id,'Sementes de Manjericão','Envelope com sementes de manjericão genovês.',6.90,60,'sementes-de-manjericao.jpg' FROM categories c WHERE c.name='Sementes' AND NOT EXISTS (SELECT 1 FROM products p WHERE p.name='Sementes de Manjericão');
INSERT INTO products (category_id,name,description,price,stock,image) SELECT c.id,'Sementes de Girassol','Envelope com sementes de girassol gigante.',7.90,50,'sementes-de-girassol.jpg' FROM categories c WHERE c.name='Sementes' AND NOT EXISTS (SELECT 1 FROM products p WHERE p.name='Sementes de Girassol');
INSERT INTO products (category_id,name,description,price,stock,image) SELECT c.id,'Sementes de Tomate Cereja','Envelope com sementes de tomate cereja.',8.90,45,'sementes-de-tomate-cereja.jpg' FROM categories c WHERE c.name='Sementes' AND NOT EXISTS (SELECT 1 FROM products p WHERE p.name='Sementes de Tomate Cereja');
-- Nome de foto para os produtos antigos que ainda não têm imagem
UPDATE products SET image='samambaia-americana.jpg' WHERE name='Samambaia Americana' AND image IS NULL;
UPDATE products SET image='suculenta-echeveria.jpg' WHERE name='Suculenta Echeveria' AND image IS NULL;
UPDATE products SET image='orquidea-phalaenopsis.jpg' WHERE name='Orquídea Phalaenopsis' AND image IS NULL;
UPDATE products SET image='vaso-de-ceramica-m.jpg' WHERE name='Vaso de Cerâmica M' AND image IS NULL;
UPDATE products SET image='vaso-autoirrigavel.jpg' WHERE name='Vaso Autoirrigável' AND image IS NULL;
UPDATE products SET image='pa-de-jardinagem.jpg' WHERE name='Pá de Jardinagem' AND image IS NULL;
UPDATE products SET image='tesoura-de-poda.jpg' WHERE name='Tesoura de Poda' AND image IS NULL;
UPDATE products SET image='terra-vegetal-5kg.jpg' WHERE name='Terra Vegetal 5kg' AND image IS NULL;
UPDATE products SET image='adubo-organico-1kg.jpg' WHERE name='Adubo Orgânico 1kg' AND image IS NULL;
-- Histórico inicial de estoque dos produtos novos
INSERT INTO stock_movements (product_id,type,quantity,reason) SELECT p.id,'entrada',p.stock,'Estoque inicial' FROM products p WHERE p.stock>0 AND NOT EXISTS (SELECT 1 FROM stock_movements m WHERE m.product_id=p.id);

-- VerdeVivo - Conta administrativa: admin@gmail / senha inicial admin123
-- Preserva os demais usuários, produtos e pedidos. Pode ser executado mais de uma vez.
-- ATENÇÃO: redefine a senha desta conta para 'admin123' (troque depois).

-- 1) Migra a conta antiga do Fernando (sem duplicar), se a nova ainda não existir.
UPDATE users SET email='admin@gmail', password='$2y$10$fuoCPkHMgLYJzIU8l.tHYO.amP0lCpy9jGmauCIKIpDhQ7wXsh3pW', role='admin'
 WHERE email IN ('fernando.sortes@outlook.com','fernando@gmail')
   AND NOT EXISTS (SELECT 1 FROM (SELECT id FROM users WHERE email='admin@gmail') t)
 ORDER BY (email='fernando.sortes@outlook.com') DESC LIMIT 1;

-- 2) Se ainda não existe nenhuma conta com o e-mail, cria.
INSERT INTO users (name,email,password,role)
SELECT 'Fernando','admin@gmail','$2y$10$fuoCPkHMgLYJzIU8l.tHYO.amP0lCpy9jGmauCIKIpDhQ7wXsh3pW','admin' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='admin@gmail');

-- 3) Garante senha (bcrypt de password_hash) e perfil de administrador.
UPDATE users SET name='Fernando', password='$2y$10$fuoCPkHMgLYJzIU8l.tHYO.amP0lCpy9jGmauCIKIpDhQ7wXsh3pW', role='admin' WHERE email='admin@gmail';
