<?php
// Conexão PDO. Ajuste os valores se o seu XAMPP tiver senha no root.
const BASE_URL = '/VerdeVivo';   // nome da pasta dentro de htdocs
const DB_HOST = '127.0.0.1';
const DB_NAME = 'verdevivo';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            error_log($e->getMessage());
            http_response_code(500);
            exit('Não foi possível conectar ao banco. Verifique se o MySQL está ativo no XAMPP e se o banco "verdevivo" foi importado.');
        }
    }
    return $pdo;
}
