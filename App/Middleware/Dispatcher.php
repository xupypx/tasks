<?php

namespace App\Middleware;

class Dispatcher
{
    protected array $middleware = [];

    /**
     * Добавляет middleware (строку или объект) в очередь
     *
     * @param string|object $middleware
     * @param array $params
     * @return void
     * @throws \Exception
     */
    public function add($middleware, array $params = []): void
    {
        // Если передали строку — создаем экземпляр
        if (is_string($middleware)) {
            if (!class_exists($middleware)) {
                throw new \Exception("Middleware class not found: $middleware");
            }
            $middleware = new $middleware($params);
        }

        if (!method_exists($middleware, 'handle')) {
            $className = is_object($middleware) ? get_class($middleware) : (string)$middleware;
            throw new \Exception("Middleware class '{$className}' does not have a handle() method");
        }

        $this->middleware[] = $middleware;
    }

    /**
     * Запускает все middleware
     */
    public function handle(): void
    {
        foreach ($this->middleware as $middleware) {
            $middleware->handle();
        }
    }
}
