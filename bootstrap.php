<?php
declare(strict_types=1);

session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header_remove('X-Powered-By');

define('BASE_PATH', __DIR__);

// Автозагрузка классов
require_once BASE_PATH . '/core/Autoloader.php';
use core\Autoloader;

Autoloader::register();


// Конфиг
$config = require BASE_PATH . '/config/config.php';
define('ENV', $config['env'] ?? 'production');

// БД
$dbConfig = require BASE_PATH . '/config/database.php';

// SMTP
$smtpConfig = require BASE_PATH . '/config/smtp.php';

try {
    $db = new PDO(
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

// Генерация CSRF-токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once BASE_PATH . '/core/helpers.php';

/**
 * Проверка CSRF-токена
 */
// function verify_csrf_token(string $token): bool {
//     return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
// }
