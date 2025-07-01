<?php

function abort($code = 404, $message = '') {
    http_response_code($code);

    $defaultMessages = [
        403 => 'Доступ запрещён',
        404 => 'Страница не найдена',
        500 => 'Внутренняя ошибка сервера'
    ];

    // Если сообщение не передано, берём дефолтное
    $message = $message ?: ($defaultMessages[$code] ?? 'Ошибка');

    // Рендерим view ошибки, если хочешь красивые страницы ошибок
    $viewFile = __DIR__ . "/../App/Views/errors/{$code}.php";
    if (file_exists($viewFile)) {
        require $viewFile;
    } else {
        echo "<h1>{$code} {$message}</h1>";
    }

    exit;
}

function abort403($message = '') {
    abort(403, $message);
}

function abort404($message = '') {
    abort(404, $message);
}

function abort500($message = '') {
    abort(500, $message);
}

function flash(string $key, ?string $message = null): ?string {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }

    return null;
}

function require_login(): void {
    if (!isset($_SESSION['user'])) {
        flash('error', 'Для доступа нужно войти');
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: /login');
        exit;
    }
}

function require_role(string $role): void {
    require_login();
    if ($_SESSION['user']['role'] !== $role) {
        http_response_code(403);
        exit('Доступ запрещён');
    }
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool {
    return isset($_SESSION['user']);
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function verify_csrf_token(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
