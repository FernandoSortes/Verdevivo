-- VerdeVivo - Dados de teste. Logins: admin@gmail / admin123 (admin, criado em atualizacao_v2.sql)  |  cliente@verdevivo.com / cliente123
USE verdevivo;
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
