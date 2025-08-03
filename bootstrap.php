<?php
declare(strict_types=1);

session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header_remove('X-Powered-By');

define('BASE_PATH', realpath(__DIR__));
define('ENV', 'development');
/**
 * Определение базового URL с учетом протокола для продакшн-условий
 */
function defineUrlHost() {
    // Проверка HTTPS (прямой или через прокси)
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    // Протокол: HTTPS для продакшн, HTTP для локальной разработки
    $protocol = $isHttps ? 'https' : 'http';

    // Хост для продакшн
    $host = 'zadacha.loc/public';

    // Определение константы
    define('URL_HOST', "$protocol://$host/");
}
// Вызов функции
defineUrlHost();

// Автозагрузка классов
require_once BASE_PATH . '/core/Autoloader.php';
use core\Autoloader;

Autoloader::register();



// БД
$dbConfig = require BASE_PATH . '/config/database.php';

require_once BASE_PATH . '/vendor/autoload.php';

// Инициализация сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Глобальная проверка авторизации
if (php_sapi_name() !== 'cli' && isset($_SERVER['REQUEST_URI'])) {
    $currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $publicRoutes = ['/login', '/forgot-password', '/reset-password'];
    $adminRoutes = ['/register']; // Маршруты только для админов

    // Если пользователь не авторизован и пытается получить доступ к закрытой странице
    if (!isset($_SESSION['user'])) {
        if (!in_array($currentUri, $publicRoutes)) {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            header('Location: /login');
            exit;
        }
    }
    // Если пользователь авторизован, но пытается получить доступ к админской странице без прав
    elseif (in_array($currentUri, $adminRoutes) && $_SESSION['user']['role'] !== 'admin') {
        $_SESSION['error'] = 'У вас нет прав доступа к этой странице';
        header('Location: /'); // Или на другую страницу
        exit;
    }
}



function db(): PDO
{
    static $instance;

    if ($instance === null) {
        global $dbConfig;

        try {
            $instance = new PDO(
                "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}",
                $dbConfig['username'],
                $dbConfig['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            error_log('DB error: ' . $e->getMessage());
            die(ENV === 'development' ? 'DB error: ' . $e->getMessage() : 'Database error');
        }
    }

    return $instance;
}

// Генерация CSRF-токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once BASE_PATH . '/core/helpers.php';
/**
 * Настройка окружения
 */
date_default_timezone_set('Europe/Minsk');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
