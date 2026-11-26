-- VerdeVivo - Conta administrativa: admin@gmail / senha inicial admin123
-- Preserva os demais usuários, produtos e pedidos. Pode ser executado mais de uma vez.
-- ATENÇÃO: redefine a senha desta conta para 'admin123' (troque depois).
USE verdevivo;

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
