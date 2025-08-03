<?php
namespace App\Controllers;

use core\View;
use PDO;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Services\AuthService;
use App\Policies\AuthPolicy;
use App\Models\User;
use App\Middleware\RoleMiddleware;
use App\Models\Task;
use core\PaginationService;

class UserController
{
public function show(int $userId)
{
    // Получаем сервис аутентификации
    $authService = new AuthService(db()); // Лучше внедрить через конструктор
    $currentUser = $authService->user();

    // Проверяем авторизацию
    if (!$currentUser) {
        flash('error', 'Неавторизованный доступ');
        redirectBack();
    }

    // Проверяем права доступа
    $isAdmin = AuthPolicy::isAdmin($currentUser);

    // Проверяем, имеет ли пользователь право просматривать профиль
    if (!$isAdmin && $userId !== $currentUser['id']) {
        flash('error', 'Доступ запрещен: вы не можете просматривать чужую страницу');
        redirectBack();
    }

    // Проверяем валидность ID
    if ($userId <= 0) {
        flash('error', 'Неверный ID пользователя');
        redirectBack();
    }

    // Получаем данные пользователя
    $db = db();
    $userModel = new User($db);
    $user = $userModel->getById($userId);

    if (!$user) {
        flash('error', 'Пользователь не найден');
        redirectBack();
    }

    // Получаем данные задач
    $taskModel = new Task($db);
    $solutionsCount = $taskModel->countSolvedByUser($userId);
    $tasksSolved = $taskModel->getSolvedTasksByUser($userId, 10, 0);

    // Рендерим представление
    View::render('users/show', [
        'user' => $user,
        'solutionsCount' => $solutionsCount,
        'tasksSolved' => $tasksSolved,
    ]);
}




    public function create(): void
    {
        require_role('admin');

        View::render('users/create', [
            'title' => 'Регистрация пользователя',
            'csrf_token' => $_SESSION['csrf_token'] ?? '',
            'roles' => ['admin', 'manager', 'user']
        ]);
    }

    public function store(): void
    {
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
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';

        if (empty($username) || empty($email) || empty($password)) {
            flash('error', 'Все поля обязательны для заполнения');
            redirect('/register');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Некорректный email');
            redirect('/register');
        }

        if (strlen($password) < 8) {
            flash('error', 'Пароль должен содержать минимум 8 символов');
            redirect('/register');
        }

        $allowedRoles = ['admin', 'manager', 'user'];
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'user';
        }

        try {
            $userModel = new User(db());

            if ($userModel->usernameExists($username)) {
                flash('error', 'Пользователь с таким именем уже существует');
                redirect('/register');
            }

            if ($userModel->emailExists($email)) {
                flash('error', 'Пользователь с таким email уже существует');
                redirect('/register');
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $userModel->create([
                'username' => $username,
                'email' => $email,
                'password_hash' => $hash,
                'role' => $role
            ]);

            flash('success', 'Пользователь успешно зарегистрирован');
            redirect('/register');

        } catch (\PDOException $e) {
            error_log('User creation error: ' . $e->getMessage());
            flash('error', 'Ошибка при создании пользователя');
            redirect('/register');
        }
    }

    public function getManagers(): array
    {
        $db = db();

        try {
            $stmt = $db->prepare("SELECT id, username FROM users WHERE role = 'manager'");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('Ошибка при получении менеджеров: ' . $e->getMessage());
            return [];
        }
    }

    public function sendPasswordReset(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');

            if (empty($email)) {
                $_SESSION['error'] = 'Введите email';
                header('Location: /forgot-password');
                exit;
            }

            $userModel = new User(db());
            $user = $userModel->getByEmail($email);

            if (!$user) {
                $_SESSION['error'] = 'Пользователь с таким email не найден';
                header('Location: /forgot-password');
                exit;
            }

            // Генерация токена
            // $token = bin2hex(random_bytes(32));
            $token = bin2hex(random_bytes(16));
            $userModel->updateResetToken($user['id'], $token);

            // Загрузка настроек SMTP
            $smtpConfig = require BASE_PATH . '/config/smtp.php';

            $resetLink = config('config.url_repass', '') . '/reset-password?token=' . $token;
            $subject = 'Восстановление пароля';
            $message = "Перейдите по ссылке для сброса пароля:<br><a href='$resetLink'>$resetLink</a>";

            try {
                $mail = new PHPMailer(true);

                $mail->isSMTP();
                $mail->CharSet = $smtpConfig['charset'] ?? 'UTF-8';
                $mail->Host = $smtpConfig['smtp']['host'];
                $mail->SMTPAuth = true;
                $mail->Username = $smtpConfig['smtp']['username'];
                $mail->Password = $smtpConfig['smtp']['password'];
                $mail->SMTPSecure = $smtpConfig['smtp']['secure'];
                $mail->Port = $smtpConfig['smtp']['port'];

                $mail->setFrom($smtpConfig['smtp']['from'], $smtpConfig['smtp']['from_name']);
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $message;

                $mail->send();

                $_SESSION['success'] = 'Инструкция отправлена на email';
                header('Location: /login');
                exit;

            } catch (Exception $e) {
                $_SESSION['error'] = "Ошибка при отправке письма: {$mail->ErrorInfo}";
                header('Location: /forgot-password');
                exit;
            }
        }

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

            $userModel = new User(db());
            $user = $userModel->getByResetToken($token);

            if (!$user) {
                http_response_code(404);
                $_SESSION['error'] = 'Неверный или истекший токен';
                header('Location: /login');
                exit;
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $userModel->updatePassword($user['id'], $passwordHash);
            $userModel->clearResetToken($user['id']);

            $_SESSION['success'] = 'Пароль успешно изменён';
            header('Location: /login');
            exit;
        }

        View::render('auth/reset-password', [
            'token' => $token,
            'csrf_token' => $_SESSION['csrf_token'] ?? ''
        ]);
    }

public function edit($id)
    {
        // RoleMiddleware::check('admin');
        $middleware = new RoleMiddleware(['role' => 'admin']);
        $middleware->handle();

        $user = User::find($id);
        if (!$user) {
            http_response_code(404);
            exit('Пользователь не найден');
        }

        // Генерация CSRF токена при необходимости
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }


        View::render('users/edit', [
        'layout' => 'dashboard',
        'csrf_token' => $_SESSION['csrf_token'],
        'user' => $user
        ]);
    }

 public function update($id)
{
    // RoleMiddleware::check('admin');
    $middleware = new RoleMiddleware(['role' => 'admin']);
    $middleware->handle();

    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        flash('error', 'Неверный CSRF токен');
        redirect("/admin/users/edit/$id");
    }

    $user = User::find($id);
    if (!$user) {
        http_response_code(404);
        exit('Пользователь не найден');
    }

    // Валидация входных данных
    $name = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $role === '') {
        exit('Все поля обязательны');
    }

    // Обновление
    $user->username = $name;
    $user->email = $email;
    $user->role = $role;

    if (!empty($password)) {
        $user->password_hash = password_hash($password, PASSWORD_DEFAULT);
    }

    $user->save();

    flash('success', "Пользователь $name успешно обновлен!");
    redirect('/admin/users');
}



}
