<?php
namespace core;

class Router {
    private array $routes = [];

    /**
     * Регистрация GET маршрута с поддержкой динамических параметров
     */
    public function get(string $uri, callable|array $action): void {
        $this->addRoute('GET', $uri, $action);
    }

    /**
     * Регистрация POST маршрута с поддержкой динамических параметров
     */
    public function post(string $uri, callable|array $action): void {
        $this->addRoute('POST', $uri, $action);
    }

    /**
     * Добавление маршрута
     */
    private function addRoute(string $method, string $uri, callable|array $action): void {
        // Преобразуем динамические параметры {param} в регулярные выражения
        $uri = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[^/]+)', $uri);
        $this->routes[$method][] = [
            'route' => "#^$uri$#", // Преобразуем в регулярное выражение
            'action' => $action
        ];
    }

// Обработка запроса
public function dispatch(string $uri): void {
    $uri = parse_url($uri, PHP_URL_PATH); // Извлекаем путь
    $uri = rtrim($uri, '/') ?: '/'; // Убираем слэш в конце

    $method = $_SERVER['REQUEST_METHOD']; // Получаем метод запроса

    // Ищем подходящий маршрут
    foreach ($this->routes[$method] ?? [] as $route) {
        if (preg_match($route['route'], $uri, $matches)) {
            // Извлекаем параметры маршрута
            $params = [];
            foreach ($matches as $key => $value) {
                if (!is_numeric($key)) {
                    $params[$key] = $value;
                }
            }

            // Приводим параметры к нужному типу
            if (isset($params['id'])) {
                $params['id'] = (int)$params['id']; // Приводим параметр id к целому числу
            }

            // Выполняем действие (контроллер и метод)
            $action = $route['action'];
            if (is_array($action)) {
                [$class, $method] = $action;

            if (!class_exists($class)) {
                abort500("Class not found: $class");
            }

            if (!method_exists($class, $method)) {
                abort500("Method not found: $method in $class");
            }

                // Вызов метода класса с параметрами
                (new $class)->$method(...array_values($params));
            } else {
                // Вызов функции с параметрами
                call_user_func($action, ...array_values($params));
            }
            return;
        }
    }

    // Если не найден маршрут
    $this->abort404();
}

public function abort404()
{
    http_response_code(404);
    \core\View::render('errors/404');
    exit;
}


}
