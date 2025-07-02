/www                                          # project-root
    ├── App/
    │       ├── Controllers/
    │       │       ├── Api/
    │       │       │       └── TaskApiController.php
    │       │       │
    │       │       ├── AuthController.php
    │       │       ├── HomeController.php
    │       │       ├── TaskController.php
    │       │       └── UserController.php
    │       │
    │       ├── Models/
    │       │       └── Task.php
    │       │
    │       └── Views/
    │               ├── auth/
    │               │       ├── login.php
    │               │       └── register.php
    │               │
    │               ├── errors/
    │               │       ├── 403.php
    │               │       ├── 404.php
    │               │       └── 500.php
    │               │
    │               ├── home/
    │               │       ├── dashboard.php
    │               │       └── index.php
    │               │
    │               ├──layouts/
    │               │       ├── main.php          # Базовый публичный layout (главная, контакты, статьи).
    │               │       ├── auth.php          # для страниц логина/регистрации
    │               │       ├── dashboard.php     # для личного кабинета / админки
    │               │       ├── error.php         # для 404, 500
    │               │       └── partials/         # Части, которые можно подключать через include или View::insert() – header, footer, sidebar.
    │               │           ├── header.php
    │               │           ├── footer.php
    │               │           └── sidebar.php
    │               │
    │               │
    │               ├── tasks/
    │               │       ├── create.php
    │               │       ├── edit.php
    │               │       ├── list.php
    │               │       ├── show.php
    │               │       └── success.php
    │               └── user/
    │                       └── create.php
    │
    ├── config/
    │       ├── config.php                   # Общие настройки
    │       └── database.php                 # Настройки БД
    │
    ├── core/
    │       ├── Autoloader.php               # PSR-4 автозагрузка
    │       ├── helpers.php                  # Утилиты и функции
    │       ├── Router.php                   # Роутинг
    │       └── View.php
    │
    ├── public/                              # Веб-доступ только сюда
    │       ├── index.php                    # Единая точка входа с основными маршрутами
    │       └── .htaccess                    # Apache,
    │
    ├── views/
    │       └── tasks/
    │               └── tasks
    │
    ├── bootstrap.php                        # Инициализация
    └── .htaccess                            # Apache (если нужен) пока не делал

