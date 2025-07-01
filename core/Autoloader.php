<?php

namespace core;

class Autoloader
{
    /**
     * Регистрируем автозагрузчик.
     */
    public static function register(): void
    {
        spl_autoload_register([__CLASS__, 'load']);
    }

    /**
     * Загружаем класс при его необходимости.
     *
     * @param string $class Имя класса для автозагрузки
     */
public static function load(string $class): void
{
    // Определяем директории для поиска классов
    $directories = [
        BASE_PATH . '/',                      // Основная директория
        BASE_PATH . '/core/',                 // Для классов в папке core
        BASE_PATH . '/App/Controllers/',      // Для контроллеров
        BASE_PATH . '/App/Models/',           // Для моделей
        BASE_PATH . '/App/Views/',            // Для представлений
    ];

    foreach ($directories as $dir) {
        $file = $dir . str_replace('\\', '/', $class) . '.php';

        if (file_exists($file)) {
            require_once $file;
            return; // Если файл найден, выходим из метода
        }
    }

    // Логируем ошибку, если файл не найден
    error_log("Autoloader: файл не найден для класса {$class} => {$file}");
}

}
