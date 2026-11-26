<?php
// Ponto de entrada comum: sessão, banco, helpers e serviços.
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
define('ROOT', dirname(__DIR__));
require_once ROOT . '/config/database.php';
require_once ROOT . '/app/helpers/helpers.php';
require_once ROOT . '/app/helpers/upload.php';
require_once ROOT . '/app/services/StockService.php';
require_once ROOT . '/app/services/OrderService.php';
require_once ROOT . '/app/services/DashboardService.php';
