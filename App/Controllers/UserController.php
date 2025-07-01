<?php
namespace App\Controllers;

use core\View;
use PDO;
use function \redirect;

class UserController {
    public function create(): void {
        require_role('admin');

        View::render('users/create', [
            'title' => 'Регистрация пользователя',
            'csrf_token' => $_SESSION['csrf_token'] ?? ''
        ]);
    }

    public function store(): void {
        require_role('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Метод не разрешён');
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf_token($token)) {
            flash('error', 'Неверный CSRF токен');
            redirect('/register');
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';

        if ($username === '' || $password === '') {
            flash('error', 'Имя и пароль обязательны');
            redirect('/register');
        }

        $allowedRoles = ['admin', 'manager', 'user'];
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'user';
        }

        global $db;

        $stmt = $db->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        if ($stmt->fetch()) {
            flash('error', 'Пользователь уже существует');
            redirect('/register');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT INTO users (username, password_hash, role) VALUES (:username, :hash, :role)");
        $stmt->execute([
            'username' => $username,
            'hash' => $hash,
            'role' => $role,
        ]);

        flash('success', 'Пользователь создан');
        redirect('/login');
    }

    // Получить всех менеджеров
        public function getManagers(): array {
            global $db;

            try {
                $stmt = $db->prepare("SELECT id, username FROM users WHERE role = 'manager'");
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (\PDOException $e) {
                // Обработка ошибки
                // Можно записать ошибку в лог или вывести в отладочный режим
                error_log('Ошибка при получении менеджеров: ' . $e->getMessage());
                return []; // Возвращаем пустой массив в случае ошибки
            }
        }

    // Восстановление пароля
     public function sendPasswordReset(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email'] ?? '');

        if (empty($email)) {
            $_SESSION['error'] = 'Введите email';
            header('Location: /forgot-password');
            exit;
        }

        $userModel = new User($GLOBALS['db']);
        $user = $userModel->getByEmail($email);

        if (!$user) {
            $_SESSION['error'] = 'Пользователь с таким email не найден';
            header('Location: /forgot-password');
            exit;
        }

        // Генерация токена
        $token = bin2hex(random_bytes(32));

        // Сохраняем в БД
        $userModel->updateResetToken($user['id'], $token);

        // Отправляем письмо
        $resetLink = 'http://yourdomain.com/reset-password?token=' . $token;
        $subject = 'Восстановление пароля';
        $message = "Перейдите по ссылке для сброса пароля: $resetLink";

        mail($email, $subject, $message);

        $_SESSION['success'] = 'Инструкция отправлена на email';
        header('Location: /login');
        exit;
    }

    // GET-запрос: показываем форму ввода email
    View::render('auth/forgot-password', [
        'csrf_token' => $_SESSION['csrf_token'] ?? ''
    ]);
}

public function resetPassword(): void
{
    $token = $_GET['token'] ?? '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($password)) {
            $_SESSION['error'] = 'Введите новый пароль';
            header('Location: /reset-password?token=' . urlencode($token));
            exit;
        }

        $userModel = new User($GLOBALS['db']);
        $user = $userModel->getByResetToken($token);

        if (!$user) {
            http_response_code(404);
            echo 'Неверный или истекший токен';
            exit;
        }

        // Хешируем пароль
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $userModel->updatePassword($user['id'], $passwordHash);

        // Удаляем токен
        $userModel->clearResetToken($user['id']);

        $_SESSION['success'] = 'Пароль успешно изменён';
        header('Location: /login');
        exit;
    }

    // GET-запрос: форма смены пароля
    View::render('auth/reset-password', [
        'token' => $token,
        'csrf_token' => $_SESSION['csrf_token'] ?? ''
    ]);
}

public function forgotPassword(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = $_POST['email'] ?? '';

        // Проверяем, есть ли пользователь с таким email
        $userModel = new User($GLOBALS['db']);
        $user = $userModel->getByEmail($email);

        if ($user) {
            // Генерация кода восстановления
            $resetCode = bin2hex(random_bytes(16));  // Генерация случайного кода

            // Сохранение кода в БД (временная таблица)
            $userModel->storeResetCode($user['id'], $resetCode);

            // Ссылка для восстановления
            $resetLink = "http://yourdomain.com/reset-password?code=$resetCode";

            // Отправка email с ссылкой для восстановления
            $emailSender = new \App\Utils\EmailSender();
            $success = $emailSender->send(
                $email,
                'Восстановление пароля',
                "Для сброса пароля перейдите по следующей ссылке: <a href='$resetLink'>$resetLink</a>"
            );

            if ($success) {
                $_SESSION['success'] = 'Письмо с инструкциями отправлено.';
            } else {
                $_SESSION['error'] = 'Ошибка отправки письма.';
            }
        } else {
            $_SESSION['error'] = 'Пользователь с таким email не найден.';
        }

        header('Location: /forgot-password');
        exit;
    }

    // Отображаем форму восстановления
    View::render('auth/forgot-password');
}


}
