<?php

namespace core;

// use core\View;

class View {
/**
* Хранилище регионов
*/
private static array $regions = [];

/**
* Установить значение региона (перезаписывает)
*/
public static function set(string $key, $value): void
{
    self::$regions[$key] = $value;
}

/**
* Добавить значение к региону (как стек)
*/
public static function append(string $key, $value): void
{
if (!isset(self::$regions[$key])) {
    self::$regions[$key] = [];
}
self::$regions[$key][] = $value;
}

/**
* Получить значение региона
*/
public static function get(string $key, $default = '')
{
return self::$regions[$key] ?? $default;
}

/**
* Проверить наличие региона
*/
public static function has(string $key): bool
{
return isset(self::$regions[$key]);
}

/**
* Получить все элементы стека региона как строку
*/
public static function stack(string $key, string $separator = "\n"): string
{
if (!isset(self::$regions[$key]) || !is_array(self::$regions[$key])) {
    return '';
}
return implode($separator, self::$regions[$key]);
}

/**
* Рендер layout
*/
public static function renderLayout(string $layout = 'main'): void
{
    $layoutPath = BASE_PATH . '/App/Views/layouts/' . $layout . '.php';

    if (!file_exists($layoutPath)) {
        http_response_code(500);
        echo "Layout not found: $layoutPath";
        return;
    }

    include $layoutPath;
}

/**
* Добавить значение в начало стека региона
*/
public static function prepend(string $key, $value): void
{
    if (!isset(self::$regions[$key])) {
        self::$regions[$key] = [];
    }
    array_unshift(self::$regions[$key], $value);
}

/**
 * Рендер JSON ответа
 */
public static function renderJSON(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
* Рендер partial view без layout
*/
public static function renderPartial(string $view, array $params = []): void
{
    $viewPath = BASE_PATH . '/App/Views/' . str_replace('.', '/', $view) . '.php';

    if (!file_exists($viewPath)) {
        http_response_code(500);
        echo "Partial view not found: $viewPath";
        return;
    }

    extract($params, EXTR_SKIP);
    include $viewPath;
}


public static function render(string $view, array $params = []): void
{
    $viewPath = BASE_PATH . '/App/Views/' . str_replace('.', '/', $view) . '.php';

    if (!file_exists($viewPath)) {
        http_response_code(500);
        echo "View not found: $viewPath";
        return;
    }

    extract($params, EXTR_SKIP);

    ob_start();
    include $viewPath;
    self::set('content', ob_get_clean());

    // Используем layout из параметров, если передан
    $layout = $params['layout'] ?? 'main';
    self::renderLayout($layout);
}

public static function insert(string $view, array $data = [])
{
    $viewFile = BASE_PATH . '/App/Views/' . $view . '.php';

    if (!file_exists($viewFile)) {
        throw new \Exception("Partial view not found: $viewFile");
    }

    extract($data, EXTR_SKIP);
    include $viewFile;
}

// Реализация View::component
public static function component(string $name, array $params = []): void
{
    extract($params);
    require BASE_PATH . '/App/Views/components/' . $name . '.php';
}


}

